<?php
/**
 * Email template for WooCommerce Reminder.
 *
 * @var string   $email_heading
 * @var string   $body
 * @var WC_Order $order
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <style>
            body { font-family: Arial, sans-serif; color: #222; }
            .wrapper { max-width: 600px; margin: 0 auto; }
            h1 { font-size: 22px; }
            p { line-height: 1.6; }
            .order-summary { margin-top: 20px; border-collapse: collapse; width: 100%; }
            .order-summary th, .order-summary td { border: 1px solid #e2e2e2; padding: 8px; text-align: left; }
            .order-summary th { background-color: #f7f7f7; }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <h1><?php echo esc_html( $email_heading ); ?></h1>
            <div><?php echo wp_kses_post( $body ); ?></div>

            <h2><?php esc_html_e( 'Order details', 'woocommerce-reminder' ); ?></h2>
            <table class="order-summary">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Product', 'woocommerce-reminder' ); ?></th>
                        <th><?php esc_html_e( 'Quantity', 'woocommerce-reminder' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'woocommerce-reminder' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $order->get_items() as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $item->get_name() ); ?></td>
                            <td><?php echo esc_html( $item->get_quantity() ); ?></td>
                            <td><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><?php esc_html_e( 'Total', 'woocommerce-reminder' ); ?></td>
                        <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </body>
</html>
