<?php
/**
 * Cron handler for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Cron {

    const HOOK = 'wr_send_reminders';

    /**
     * Mailer dependency.
     *
     * @var WR_Mailer
     */
    protected $mailer;

    /**
     * PDF generator dependency.
     *
     * @var WR_PDF
     */
    protected $pdf;

    /**
     * Constructor.
     *
     * @param WR_Mailer $mailer Mailer instance.
     * @param WR_PDF    $pdf    PDF generator instance.
     */
    public function __construct( WR_Mailer $mailer, WR_PDF $pdf ) {
        $this->mailer = $mailer;
        $this->pdf    = $pdf;

        add_action( self::HOOK, array( $this, 'process_queue' ) );
    }

    /**
     * Schedule cron events if they are not already queued.
     */
    public function schedule_events() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
        }
    }

    /**
     * Clear scheduled events.
     */
    public function clear_events() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }

    /**
     * Process reminder queue.
     */
    public function process_queue() {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
            return;
        }

        $settings = WR_Admin::get_settings();

        $orders = $this->get_eligible_orders( $settings['days_after'] );

        foreach ( $orders as $order ) {
            $sent = $this->mailer->send_reminder( $order, $settings, $this->pdf );
            if ( $sent ) {
                $order->update_meta_data( '_wr_last_reminder_sent', time() );
                $count = (int) $order->get_meta( '_wr_reminder_count', true );
                $order->update_meta_data( '_wr_reminder_count', $count + 1 );
                $order->save();
            }
        }
    }

    /**
     * Get orders that should receive a reminder.
     *
     * @param int $days_after Days after order creation.
     *
     * @return WC_Order[]
     */
    protected function get_eligible_orders( $days_after ) {
        $threshold = time() - DAY_IN_SECONDS * absint( $days_after );

        $query_args = array(
            'status'        => array( 'pending', 'on-hold' ),
            'limit'         => -1,
            'date_created'  => '<' . $threshold,
            'meta_query'    => array(
                'relation' => 'OR',
                array(
                    'key'     => '_wr_last_reminder_sent',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_wr_last_reminder_sent',
                    'value'   => time() - DAY_IN_SECONDS,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'return'        => 'objects',
        );

        $orders = wc_get_orders( $query_args );

        return array_filter(
            $orders,
            function ( $order ) {
                $last_sent = (int) $order->get_meta( '_wr_last_reminder_sent', true );
                if ( ! $last_sent ) {
                    return true;
                }

                // Send at most once per day.
                return ( time() - $last_sent ) >= DAY_IN_SECONDS;
            }
        );
    }
}
