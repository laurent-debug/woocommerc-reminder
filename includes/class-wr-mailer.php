<?php
/**
 * Mailer for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Mailer {

    /**
     * Send reminder email for an order.
     *
     * @param WC_Order   $order     WooCommerce order object.
     * @param string|null $pdf_path Optional path to an invoice PDF.
     *
     * @return bool
     */
    public function send_reminder( $order, $pdf_path = null ) {
        if ( ! $order instanceof WC_Order ) {
            return false;
        }

        $recipient = $order->get_billing_email();
        if ( ! $recipient ) {
            return false;
        }

        $settings = WR_Admin::get_settings();

        $placeholders = $this->get_placeholders( $order );

        $subject = $this->replace_placeholders( $settings['wr_subject'], $placeholders );
        $body    = $this->replace_placeholders( $settings['wr_body'], $placeholders );

        if ( ! function_exists( 'WC' ) ) {
            return false;
        }

        $email = WC()->mailer();

        if ( ! $email ) {
            return false;
        }

        $html = wc_get_template_html(
            'emails/wr-reminder.php',
            array(
                'order'         => $order,
                'email_heading' => $subject,
                'plain_text'    => false,
                'email'         => $email,
                'body'          => $body,
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => null,
                'brand_logo_id' => isset( $settings['wr_brand_logo'] ) ? absint( $settings['wr_brand_logo'] ) : 0,
                'brand_color'   => isset( $settings['wr_brand_color'] ) ? sanitize_hex_color( $settings['wr_brand_color'] ) : '',
            ),
            '',
            WR_PLUGIN_PATH . 'templates/'
        );

        if ( is_callable( array( $email, 'style_inline' ) ) ) {
            $html = $email->style_inline( $html );
        }

        $attachments = array();
        if ( $pdf_path && is_readable( $pdf_path ) ) {
            $attachments[] = $pdf_path;
        }

        $headers = is_callable( array( $email, 'get_headers' ) ) ? $email->get_headers() : '';

        if ( empty( $headers ) ) {
            $headers = 'Content-Type: text/html; charset=UTF-8';
        }

        return $email->send( $recipient, $subject, $html, $headers, $attachments );
    }

    /**
     * Prepare placeholders for message content.
     *
     * @param WC_Order $order Order instance.
     *
     * @return array
     */
    protected function get_placeholders( WC_Order $order ) {
        $customer_name = $order->get_formatted_billing_full_name();
        if ( empty( $customer_name ) ) {
            $customer_name = $order->get_billing_first_name();
        }

        $order_total = $order->get_formatted_order_total();

        return array(
            '{customer_name}'       => $customer_name,
            '{order_number}'        => $order->get_order_number(),
            '{order_total}'         => $order_total,
            '{{order_number}}'      => $order->get_order_number(),
            '{{order_date}}'        => wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) ),
            '{{billing_first_name}}'=> $order->get_billing_first_name(),
            '{{total}}'             => $order_total,
        );
    }

    /**
     * Replace placeholders in a string.
     *
     * @param string $text         Content to replace.
     * @param array  $placeholders Key-value map of placeholders.
     *
     * @return string
     */
    protected function replace_placeholders( $text, array $placeholders ) {
        return strtr( (string) $text, $placeholders );
    }
}
