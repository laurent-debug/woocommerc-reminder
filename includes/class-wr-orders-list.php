<?php
/**
 * Admin order list column for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Orders_List {

    const COLUMN_KEY = 'wr-reminder';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ), 20 );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column' ), 20, 2 );
        add_action( 'admin_head', array( $this, 'styles' ) );
    }

    /**
     * Register reminder column.
     *
     * @param array $columns Registered columns.
     *
     * @return array
     */
    public function add_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            if ( 'order_total' === $key ) {
                $new_columns[ self::COLUMN_KEY ] = __( 'Rappel', 'woocommerce-reminder' );
            }
        }

        if ( ! isset( $new_columns[ self::COLUMN_KEY ] ) ) {
            $new_columns[ self::COLUMN_KEY ] = __( 'Rappel', 'woocommerce-reminder' );
        }

        return $new_columns;
    }

    /**
     * Render reminder column content.
     *
     * @param string $column  Current column key.
     * @param int    $post_id Order post ID.
     */
    public function render_column( $column, $post_id ) {
        if ( self::COLUMN_KEY !== $column ) {
            return;
        }

        $last_sent = (int) get_post_meta( $post_id, '_wr_last_reminder_sent', true );

        if ( empty( $last_sent ) ) {
            echo '&mdash;';
            return;
        }

        $timezone    = wp_timezone();
        $now         = new DateTimeImmutable( 'now', $timezone );
        $today_start = $now->setTime( 0, 0, 0 );
        $last_sent_dt = ( new DateTimeImmutable( '@' . $last_sent ) )->setTimezone( $timezone );

        $is_today     = $last_sent_dt >= $today_start;
        $display_text = $is_today ? __( 'Today', 'woocommerce-reminder' ) : wp_date( get_option( 'date_format' ), $last_sent, $timezone );
        $tooltip      = sprintf(
            __( 'Last reminder sent on %s', 'woocommerce-reminder' ),
            wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_sent, $timezone )
        );

        $classes = array( 'wr-reminder-status' );
        $classes[] = $is_today ? 'wr-reminder-status--today' : 'wr-reminder-status--past';

        printf(
            '<span class="%1$s" aria-label="%2$s" data-wr-tooltip="%2$s"><span class="dashicons dashicons-backup"></span><span class="wr-reminder-status__text">%3$s</span><span class="screen-reader-text">%2$s</span></span>',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $tooltip ),
            esc_html( $display_text )
        );
    }

    /**
     * Print admin column styles.
     */
    public function styles() {
        global $typenow;

        if ( 'shop_order' !== $typenow ) {
            return;
        }
        ?>
        <style>
            .column-<?php echo esc_attr( self::COLUMN_KEY ); ?> {
                width: 110px;
            }

            .wr-reminder-status {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 600;
                color: #50575e;
                cursor: default;
            }

            .wr-reminder-status .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .wr-reminder-status--today {
                color: #1d9d74;
            }

            .wr-reminder-status--past {
                color: #646970;
            }

            .wr-reminder-status[data-wr-tooltip]:hover::after,
            .wr-reminder-status[data-wr-tooltip]:focus::after {
                content: attr(data-wr-tooltip);
                position: absolute;
                bottom: calc(100% + 6px);
                left: 50%;
                transform: translateX(-50%);
                background: #1e1e1e;
                color: #fff;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 11px;
                white-space: nowrap;
                z-index: 10;
            }

            .wr-reminder-status[data-wr-tooltip]:hover::before,
            .wr-reminder-status[data-wr-tooltip]:focus::before {
                content: '';
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 5px solid transparent;
                border-top-color: #1e1e1e;
            }
        </style>
        <?php
    }
}
