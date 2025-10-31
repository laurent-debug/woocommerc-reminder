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
     * @param WC_Order $order    WooCommerce order object.
     * @param array    $settings Plugin settings.
     * @param WR_PDF   $pdf      PDF generator.
     *
     * @return bool
     */
    public function send_reminder( $order, $settings, WR_PDF $pdf ) {
        if ( ! $order instanceof WC_Order ) {
            return false;
        }

        $email = $order->get_billing_email();
        if ( ! $email ) {
            return false;
        }

        $placeholders = $this->get_placeholders( $order );

        $subject = $this->replace_placeholders( $settings['subject'], $placeholders );
        $heading = $this->replace_placeholders( $settings['heading'], $placeholders );
        $body    = $this->replace_placeholders( $settings['body'], $placeholders );

        $content = wc_get_template_html(
            'email-reminder.php',
            array(
                'email_heading' => $heading,
                'body'          => $body,
                'order'         => $order,
            ),
            '',
            WR_PLUGIN_PATH . 'templates/'
        );

        if ( ! function_exists( 'WC' ) ) {
            return false;
        }

        $mailer = WC()->mailer();

        $attachments = array();
        if ( ! empty( $settings['attach_invoice'] ) ) {
            $invoice_path = $pdf->generate_invoice( $order );
            if ( $invoice_path ) {
                $attachments[] = $invoice_path;
            }
        }

        return $mailer->send( $email, $subject, $content, '', $attachments );
    }

    /**
     * Prepare placeholders for message content.
     *
     * @param WC_Order $order Order instance.
     *
     * @return array
     */
    protected function get_placeholders( WC_Order $order ) {
        return array(
            '{{order_number}}'       => $order->get_order_number(),
            '{{order_date}}'         => wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) ),
            '{{billing_first_name}}' => $order->get_billing_first_name(),
            '{{total}}'              => $order->get_formatted_order_total(),
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
        return strtr( $text, $placeholders );
    }
}
