<?php
/**
 * Cron handler for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Cron {

    const HOOK = 'wr_daily_scan';

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

        add_action( self::HOOK, array( $this, 'scan' ) );
        add_action( 'wr_send_reminder_for_order', array( $this, 'process_single' ), 10, 1 );
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
        return $this->scan();
    }

    /**
     * Scan WooCommerce orders and enqueue reminders.
     *
     * @return int Number of orders queued or processed.
     */
    public function scan() {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_orders' ) ) {
            return 0;
        }

        $settings   = WR_Admin::get_settings();
        $days_after = isset( $settings['days_after'] ) ? max( 1, absint( $settings['days_after'] ) ) : 1;

        $statuses = array();
        if ( isset( $settings['statuses'] ) && is_array( $settings['statuses'] ) ) {
            $statuses = array_filter( array_map( 'sanitize_key', $settings['statuses'] ) );
        }

        if ( empty( $statuses ) ) {
            $option_statuses = get_option( 'wr_order_statuses', array() );
            if ( is_array( $option_statuses ) ) {
                $statuses = array_filter( array_map( 'sanitize_key', $option_statuses ) );
            }
        }

        if ( empty( $statuses ) ) {
            $statuses = array( 'pending', 'on-hold' );
        }

        $timezone      = wp_timezone();
        $now           = new DateTimeImmutable( 'now', $timezone );
        $threshold     = $now->modify( sprintf( '-%d days', $days_after ) );
        $day_start     = $now->setTime( 0, 0, 0 );
        $day_start_ts  = $day_start->getTimestamp();
        $use_scheduler = function_exists( 'as_enqueue_async_action' );

        $orders = wc_get_orders(
            array(
                'status'       => $statuses,
                'limit'        => -1,
                'date_created' => '<' . $threshold->format( 'Y-m-d H:i:s' ),
                'return'       => 'ids',
            )
        );

        $count      = 0;
        $processed  = 0;
        foreach ( $orders as $order_id ) {
            $last_sent = (int) get_post_meta( $order_id, '_wr_last_reminder_sent', true );

            if ( $last_sent && $last_sent >= $day_start_ts ) {
                continue;
            }

            if ( $use_scheduler ) {
                as_enqueue_async_action( 'wr_send_reminder_for_order', array( 'order_id' => $order_id ), 'wr' );
                $count++;
            } else {
                $this->process_single( $order_id );
                $count++;
                $processed++;

                if ( $processed >= 20 ) {
                    break;
                }
            }
        }

        error_log( sprintf( 'WR: queued %d orders (threshold=%s)', $count, $threshold->format( 'Y-m-d' ) ) );

        return $count;
    }

    /**
     * Send a reminder for a single order.
     *
     * @param int|array $order_id Order identifier or argument array.
     *
     * @return bool
     */
    public function process_single( $order_id ) {
        if ( is_array( $order_id ) ) {
            if ( isset( $order_id['order_id'] ) ) {
                $order_id = $order_id['order_id'];
            } else {
                $order_id = reset( $order_id );
            }
        }

        $order_id = absint( $order_id );

        if ( ! $order_id ) {
            return false;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return false;
        }

        $settings = WR_Admin::get_settings();

        $sent = $this->mailer->send_reminder( $order, $settings, $this->pdf );

        if ( ! $sent ) {
            return false;
        }

        $timestamp = current_time( 'timestamp' );

        $order->update_meta_data( '_wr_last_reminder_sent', $timestamp );
        $count = (int) $order->get_meta( '_wr_reminder_count', true );
        $order->update_meta_data( '_wr_reminder_count', $count + 1 );
        $order->save();

        return true;
    }
}
