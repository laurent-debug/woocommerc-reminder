<?php
/**
 * Simple invoice generator for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_PDF {

    /**
     * Generate invoice from an order identifier.
     *
     * @param int $order_id Order identifier that can be resolved by wc_get_order.
     *
     * @return string|false Absolute path to the generated file or false on failure.
     */
    public function generate_invoice_pdf( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return false;
        }

        return $this->generate_invoice( $order );
    }

    /**
     * Generate invoice summary as an HTML-based attachment.
     *
     * This is a lightweight implementation that writes an HTML document to a
     * .html file and returns its path. Email clients will still display it
     * correctly as an attachment. Developers may swap this logic for a real
     * PDF generator if desired.
     *
     * @param WC_Order $order Order instance.
     *
     * @return string|false Absolute path to the generated file or false on failure.
     */
    public function generate_invoice( WC_Order $order ) {
        $upload_dir = wp_upload_dir();
        if ( empty( $upload_dir['path'] ) || empty( $upload_dir['basedir'] ) ) {
            return false;
        }

        $dir = trailingslashit( $upload_dir['basedir'] ) . 'wr-invoices';
        wp_mkdir_p( $dir );

        $filename = sprintf( 'order-%s-reminder.html', $order->get_order_number() );
        $filepath = trailingslashit( $dir ) . $filename;

        $content = $this->get_invoice_html( $order );

        $written = file_put_contents( $filepath, $content );

        return $written ? $filepath : false;
    }

    /**
     * Build invoice HTML content.
     *
     * @param WC_Order $order Order instance.
     *
     * @return string
     */
    protected function get_invoice_html( WC_Order $order ) {
        ob_start();
        ?>
        <!doctype html>
        <html>
            <head>
                <meta charset="utf-8" />
                <title><?php echo esc_html( sprintf( __( 'Invoice for order %s', 'woocommerce-reminder' ), $order->get_order_number() ) ); ?></title>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    h1 { font-size: 20px; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                    th { background: #f7f7f7; }
                    tfoot td { font-weight: bold; }
                </style>
            </head>
            <body>
                <h1><?php echo esc_html( sprintf( __( 'Invoice #%s', 'woocommerce-reminder' ), $order->get_order_number() ) ); ?></h1>
                <p><?php echo esc_html__( 'Order summary:', 'woocommerce-reminder' ); ?></p>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Product', 'woocommerce-reminder' ); ?></th>
                            <th><?php esc_html_e( 'Qty', 'woocommerce-reminder' ); ?></th>
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
            </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }
}
