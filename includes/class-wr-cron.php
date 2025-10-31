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
     * Order statuses eligible for reminders.
     *
     * @var string[]
     */
    protected $target_statuses = array( 'pending', 'on-hold' );

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

        $sent_count = 0;

        foreach ( $orders as $order ) {
            if ( $this->process_single( $order->get_id() ) ) {
                $sent_count++;
            }
        }

        return $sent_count;
    }

    /**
     * Process a single order reminder.
     *
     * @param int $order_id WooCommerce order ID.
     *
     * @return bool True on success, false otherwise.
     */
    public function process_single( $order_id ) {
        if ( empty( $order_id ) || ! function_exists( 'wc_get_order' ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            return false;
        }

        if ( ! in_array( $order->get_status(), $this->target_statuses, true ) ) {
            return false;
        }

        $settings = WR_Admin::get_settings();

        $pdf_path = null;
        if ( ! empty( $settings['attach_invoice'] ) ) {
            if ( method_exists( $this->pdf, 'generate_invoice_pdf' ) ) {
                $pdf_path = $this->pdf->generate_invoice_pdf( $order_id );
            } else {
                $pdf_path = $this->pdf->generate_invoice( $order );
            }

            if ( ! $pdf_path ) {
                $pdf_path = null;
            }
        }

        $sent = $this->mailer->send_reminder( $order, $pdf_path );

        if ( ! $sent ) {
            return false;
        }

        $order->update_meta_data( '_wr_last_reminder_sent', current_time( 'mysql', true ) );

        $count = (int) $order->get_meta( '_wr_reminder_count', true );
        $order->update_meta_data( '_wr_reminder_count', $count + 1 );
        $order->save();

        return true;
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
            'status'        => $this->target_statuses,
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
                    'value'   => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
                    'compare' => '<=',
                    'type'    => 'DATETIME',
                ),
            ),
            'return'        => 'objects',
        );

        $orders = wc_get_orders( $query_args );

        return array_filter(
            $orders,
            function ( $order ) {
                $last_sent = $order->get_meta( '_wr_last_reminder_sent', true );
                if ( empty( $last_sent ) ) {
                    return true;
                }

                $last_sent_timestamp = strtotime( $last_sent . ' GMT' );

                if ( ! $last_sent_timestamp ) {
                    return true;
                }

                // Send at most once per day.
                return ( time() - $last_sent_timestamp ) >= DAY_IN_SECONDS;
            }
        );
    }
}
