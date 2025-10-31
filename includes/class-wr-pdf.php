<?php
/**
 * PDF invoice generator for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

class WR_PDF {

    /**
     * Generate an invoice PDF for a given order identifier.
     *
     * @param int $order_id Order identifier.
     *
     * @return string|null Absolute path to the generated file or null on failure.
     */
    public static function generate_invoice_pdf( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return null;
        }

        $html = self::render_invoice_html( $order );
        if ( '' === trim( $html ) ) {
            return null;
        }

        if ( ! class_exists( Dompdf::class ) ) {
            if ( defined( 'WR_DEBUG' ) && WR_DEBUG ) {
                error_log( sprintf( 'WR: Dompdf library not available when generating invoice for order %d.', $order->get_id() ) );
            }

            return null;
        }

        try {
            $dompdf = new Dompdf( self::create_options() );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->loadHtml( $html, 'UTF-8' );
            $dompdf->render();
            $output = $dompdf->output();
        } catch ( \Throwable $exception ) {
            if ( defined( 'WR_DEBUG' ) && WR_DEBUG ) {
                error_log( sprintf( 'WR: unable to render invoice PDF for order %d (%s).', $order->get_id(), $exception->getMessage() ) );
            }

            return null;
        }

        if ( empty( $output ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
            return null;
        }

        $directory = trailingslashit( $upload_dir['basedir'] ) . 'wr-invoices';

        if ( ! wp_mkdir_p( $directory ) ) {
            return null;
        }

        $filename = sprintf( 'invoice-%d.pdf', $order->get_id() );
        $filepath = trailingslashit( $directory ) . sanitize_file_name( $filename );

        $written = @file_put_contents( $filepath, $output, LOCK_EX );
        if ( false === $written ) {
            return null;
        }

        return file_exists( $filepath ) ? $filepath : null;
    }

    /**
     * Render the invoice template to HTML.
     *
     * @param WC_Order $order Order instance.
     *
     * @return string
     */
    protected static function render_invoice_html( WC_Order $order ) {
        $template = WR_PLUGIN_PATH . 'templates/pdf/wr-invoice.php';

        if ( ! file_exists( $template ) ) {
            return '';
        }

        $data = self::prepare_invoice_data( $order );

        if ( empty( $data ) ) {
            return '';
        }

        $store          = $data['store'];
        $invoice_number = $data['invoice_number'];
        $invoice_date   = $data['invoice_date'];
        $billing_lines  = $data['billing_lines'];
        $items          = $data['items'];
        $totals         = $data['totals'];
        $order_total    = $data['order_total'];
        $qr_label       = $data['qr_label'];
        $order          = $data['order'];

        ob_start();
        include $template;

        return (string) ob_get_clean();
    }

    /**
     * Prepare data exposed to the invoice template.
     *
     * @param WC_Order $order Order instance.
     *
     * @return array
     */
    protected static function prepare_invoice_data( WC_Order $order ) {
        $store = array(
            'name'  => get_bloginfo( 'name' ),
            'lines' => self::get_store_address_lines(),
        );

        $invoice_number = $order->get_order_number();
        if ( '' === $invoice_number ) {
            $invoice_number = (string) $order->get_id();
        }

        $invoice_date = wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) );

        $billing_lines = self::format_address_lines( $order->get_formatted_billing_address() );

        if ( empty( $billing_lines ) ) {
            $billing_lines = array_filter(
                array(
                    $order->get_formatted_billing_full_name(),
                    $order->get_billing_address_1(),
                    $order->get_billing_address_2(),
                    trim( sprintf( '%s %s', $order->get_billing_postcode(), $order->get_billing_city() ) ),
                    $order->get_billing_country(),
                )
            );
        }

        $items = array();

        foreach ( $order->get_items() as $item ) {
            $items[] = array(
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total'    => wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ),
            );
        }

        $totals = array();

        foreach ( (array) $order->get_order_item_totals() as $total ) {
            $label = isset( $total['label'] ) ? wp_strip_all_tags( $total['label'] ) : '';
            $value = isset( $total['value'] ) ? wp_strip_all_tags( $total['value'] ) : '';

            if ( '' === $label && '' === $value ) {
                continue;
            }

            $totals[] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        $order_total = wp_strip_all_tags( $order->get_formatted_order_total() );

        return array(
            'order'          => $order,
            'store'          => $store,
            'invoice_number' => $invoice_number,
            'invoice_date'   => $invoice_date,
            'billing_lines'  => array_values( array_filter( array_map( 'trim', $billing_lines ) ) ),
            'items'          => $items,
            'totals'         => $totals,
            'order_total'    => $order_total,
            'qr_label'       => apply_filters( 'wr_invoice_qr_label', __( 'Scan to pay', 'woocommerce-reminder' ), $order ),
        );
    }

    /**
     * Retrieve store address lines.
     *
     * @return array
     */
    protected static function get_store_address_lines() {
        $lines = array();

        $address_1 = get_option( 'woocommerce_store_address' );
        $address_2 = get_option( 'woocommerce_store_address_2' );
        $postcode  = get_option( 'woocommerce_store_postcode' );
        $city      = get_option( 'woocommerce_store_city' );
        $state     = get_option( 'woocommerce_store_state' );
        $country   = get_option( 'woocommerce_default_country' );

        if ( $address_1 ) {
            $lines[] = $address_1;
        }

        if ( $address_2 ) {
            $lines[] = $address_2;
        }

        $city_line = trim( sprintf( '%s %s', $postcode, $city ) );
        if ( $city_line ) {
            $lines[] = $city_line;
        }

        $country_line = self::format_country_state_label( $country, $state );
        if ( $country_line ) {
            $lines[] = $country_line;
        }

        return array_values( array_filter( array_map( 'trim', $lines ) ) );
    }

    /**
     * Convert a country and state code into a printable label.
     *
     * @param string $country_code Country code.
     * @param string $state_code   State code.
     *
     * @return string
     */
    protected static function format_country_state_label( $country_code, $state_code ) {
        if ( empty( $country_code ) ) {
            return '';
        }

        $country_label = $country_code;
        $state_label   = $state_code;

        if ( function_exists( 'WC' ) ) {
            $countries = WC()->countries;

            if ( $countries ) {
                $all_countries = $countries->get_countries();

                if ( isset( $all_countries[ $country_code ] ) ) {
                    $country_label = $all_countries[ $country_code ];
                }

                if ( $state_code ) {
                    $states = $countries->get_states( $country_code );

                    if ( isset( $states[ $state_code ] ) ) {
                        $state_label = $states[ $state_code ];
                    }
                }
            }
        }

        if ( $state_label ) {
            return trim( sprintf( '%s, %s', $country_label, $state_label ) );
        }

        return trim( $country_label );
    }

    /**
     * Turn a HTML address block into individual lines.
     *
     * @param string $address_html Address HTML.
     *
     * @return array
     */
    protected static function format_address_lines( $address_html ) {
        if ( empty( $address_html ) ) {
            return array();
        }

        $parts = preg_split( '/<br\s*\/?>(?:\s)*/i', (string) $address_html );
        $parts = array_map( 'wp_strip_all_tags', $parts );

        return array_values( array_filter( array_map( 'trim', $parts ) ) );
    }

    /**
     * Build default Dompdf options.
     *
     * @return Options
     */
    protected static function create_options() {
        $options = new Options();
        $options->setDefaultFont( 'Helvetica' );

        if ( method_exists( $options, 'setIsRemoteEnabled' ) ) {
            $options->setIsRemoteEnabled( false );
        } else {
            $options->set( 'isRemoteEnabled', false );
        }

        if ( method_exists( $options, 'setDpi' ) ) {
            $options->setDpi( 96 );
        } else {
            $options->set( 'dpi', 96 );
        }

        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['basedir'] ) ) {
            if ( method_exists( $options, 'setChroot' ) ) {
                $options->setChroot( $upload_dir['basedir'] );
            } else {
                $options->set( 'chroot', $upload_dir['basedir'] );
            }
        }

        return $options;
    }
}
