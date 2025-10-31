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

$heading       = isset( $email_heading ) && $email_heading ? $email_heading : ( isset( $subject ) ? $subject : '' );
$email         = isset( $email ) ? $email : null;
$brand_logo_id = isset( $brand_logo_id ) ? absint( $brand_logo_id ) : 0;
$brand_color   = isset( $brand_color ) ? sanitize_hex_color( $brand_color ) : '';

/**
 * Fires before the WooCommerce email content.
 *
 * Mirrors the default WooCommerce template structure so merchants receive
 * reminders using their store's email branding.
 */
do_action( 'woocommerce_email_header', $heading, $email );

if ( $brand_logo_id ) {
    $logo = wp_get_attachment_image( $brand_logo_id, 'medium', false, array( 'style' => 'max-width:200px;height:auto;margin:0 0 16px 0;' ) );
    if ( $logo ) {
        echo wp_kses_post( $logo );
    }
}

$wrapper_style = '';
if ( $brand_color ) {
    $wrapper_style = sprintf( 'border-left: 4px solid %s; padding-left: 16px; margin: 0 0 1.5em;', $brand_color );
}

if ( ! empty( $body ) ) {
    if ( $wrapper_style ) {
        printf( "<div style='%s'>", esc_attr( $wrapper_style ) );
    }

    echo wpautop( wp_kses_post( $body ) );

    if ( $wrapper_style ) {
        echo '</div>';
    }
}

/**
 * Fires after the WooCommerce email content.
 */
do_action( 'woocommerce_email_footer', $email );
