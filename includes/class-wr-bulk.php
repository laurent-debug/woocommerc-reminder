<?php
/**
 * Bulk actions handler for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Bulk {

    const TRANSIENT_KEY_PREFIX = 'wr_bulk_reminders_scheduled_';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'render_notice' ) );
    }

    /**
     * Register the bulk action.
     *
     * @param array $actions Existing bulk actions.
     *
     * @return array
     */
    public function register_bulk_action( $actions ) {
        $actions['wr_send_reminder_bulk'] = __( 'Envoyer rappel de paiement', 'woocommerce-reminder' );

        return $actions;
    }

    /**
     * Handle the custom bulk action.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      Current action key.
     * @param array  $post_ids    Selected post IDs.
     *
     * @return string
     */
    public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( 'wr_send_reminder_bulk' !== $action ) {
            return $redirect_to;
        }

        check_admin_referer( 'bulk-posts' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return $redirect_to;
        }

        $order_ids = array_filter( array_map( 'absint', (array) $post_ids ) );
        $scheduled = 0;

        foreach ( $order_ids as $order_id ) {
            if ( ! $order_id ) {
                continue;
            }

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                continue;
            }

            if ( function_exists( 'as_enqueue_async_action' ) ) {
                as_enqueue_async_action( 'wr_send_reminder_for_order', array( 'order_id' => $order_id ), 'wr' );
            } else {
                do_action( 'wr_send_reminder_for_order', array( 'order_id' => $order_id ) );
            }
            $scheduled++;
        }

        if ( $scheduled > 0 ) {
            $this->store_scheduled_count( $scheduled );
        }

        return $redirect_to;
    }

    /**
     * Store the number of scheduled reminders for the current user.
     *
     * @param int $count Number of scheduled reminders.
     */
    protected function store_scheduled_count( $count ) {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        set_transient( self::TRANSIENT_KEY_PREFIX . $user_id, (int) $count, MINUTE_IN_SECONDS );
    }

    /**
     * Display a notice after scheduling reminders.
     */
    public function render_notice() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! $screen || 'edit-shop_order' !== $screen->id ) {
            return;
        }

        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        $key   = self::TRANSIENT_KEY_PREFIX . $user_id;
        $count = (int) get_transient( $key );

        if ( ! $count ) {
            return;
        }

        delete_transient( $key );

        $message = sprintf(
            /* translators: %s: number of scheduled reminders */
            _n(
                'Un rappel de paiement a été planifié.',
                '%s rappels de paiement ont été planifiés.',
                $count,
                'woocommerce-reminder'
            ),
            number_format_i18n( $count )
        );

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html( $message )
        );
    }
}
