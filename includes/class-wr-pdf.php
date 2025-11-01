<?php
/**
 * PDF invoice locator for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_PDF {

    /**
     * Retrieve an existing JimSoft-generated invoice PDF for a given order.
     *
     * @param int $order_id Order identifier.
     *
     * @return string|null Absolute path to the PDF or null when not found.
     */
    public static function generate_invoice_pdf( $order_id ) {
        $order_id = absint( $order_id );

        if ( $order_id <= 0 ) {
            return null;
        }

        $base_dir = trailingslashit( WP_CONTENT_DIR ) . 'jimsoft_invoices_temp';

        /**
         * Filter the base directory where JimSoft invoices are stored.
         *
         * This preserves backward compatibility with previous customisations
         * that pointed the generator to an alternate directory.
         *
         * @param string $base_dir Base directory for invoices.
         * @param int    $order_id Order identifier.
         */
        $base_dir = apply_filters( 'wr_jimsoft_invoice_dir', $base_dir, $order_id );

        if ( ! is_string( $base_dir ) ) {
            $base_dir = '';
        }

        $base_dir = rtrim( $base_dir );

        if ( '' === $base_dir ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf( 'WR_PDF no JimSoft PDF for order %d.', $order_id ) );
            }

            return null;
        }

        $directory = trailingslashit( $base_dir );

        $candidates = array(
            sprintf( 'invoice-%d.pdf', $order_id ),
            sprintf( 'qr-invoice-%d.pdf', $order_id ),
            sprintf( 'invoice_%d.pdf', $order_id ),
            sprintf( 'qr-invoice_%d.pdf', $order_id ),
        );

        /**
         * Filter the ordered list of JimSoft invoice filename candidates.
         *
         * @param array  $candidates Filename candidates relative to the base directory.
         * @param int    $order_id   Order identifier.
         * @param string $directory  Absolute base directory after filtering.
         */
        $candidates = apply_filters( 'wr_jimsoft_invoice_candidates', $candidates, $order_id, $directory );

        foreach ( (array) $candidates as $candidate ) {
            $candidate = (string) $candidate;

            if ( '' === $candidate ) {
                continue;
            }

            $path = $directory . ltrim( $candidate, '/\\' );

            if ( is_readable( $path ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( sprintf( 'WR_PDF found JimSoft PDF at %s for order %d.', $path, $order_id ) );
                }

                return $path;
            }
        }

        $pattern = $directory . '*' . $order_id . '*.pdf';

        foreach ( (array) glob( $pattern ) as $path ) {
            if ( is_readable( $path ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( sprintf( 'WR_PDF found JimSoft PDF at %s for order %d.', $path, $order_id ) );
                }

                return $path;
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 'WR_PDF no JimSoft PDF for order %d.', $order_id ) );
        }

        return null;
    }
}
