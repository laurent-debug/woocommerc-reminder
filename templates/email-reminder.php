<?php
/**
 * Email template for WooCommerce Reminder.
 *
 * @var string $subject
 * @var string $body
 * @var string $email_heading
 * @var WC_Email|null $email
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$heading = isset( $email_heading ) && $email_heading ? $email_heading : ( isset( $subject ) ? $subject : '' );
$email   = isset( $email ) ? $email : null;

/**
 * Fires before the WooCommerce email content.
 *
 * Mirrors the default WooCommerce template structure so merchants receive
 * reminders using their store's email branding.
 */
do_action( 'woocommerce_email_header', $heading, $email );

if ( ! empty( $body ) ) {
    echo wpautop( wp_kses_post( $body ) );
}

/**
 * Fires after the WooCommerce email content.
 */
do_action( 'woocommerce_email_footer', $email );
