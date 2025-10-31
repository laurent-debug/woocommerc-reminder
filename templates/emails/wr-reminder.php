<?php
/**
 * Reminder email template for WooCommerce Reminder.
 *
 * @var string        $email_heading Email heading text.
 * @var string        $body          Configurable reminder body content.
 * @var WC_Order      $order         Related WooCommerce order.
 * @var WC_Email|null $email         WooCommerce email object.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$heading = isset( $email_heading ) && $email_heading ? $email_heading : ( isset( $subject ) ? $subject : '' );
$email   = isset( $email ) ? $email : null;
$order   = isset( $order ) && $order instanceof WC_Order ? $order : null;

if ( ! $order ) {
    return;
}

do_action( 'woocommerce_email_header', $heading, $email );

if ( ! empty( $body ) ) {
    echo wpautop( wp_kses_post( $body ) );
}

$order_number   = $order->get_order_number();
$order_total    = $order->get_formatted_order_total();
$payment_url    = $order->get_checkout_payment_url();
$payment_button = sprintf(
    '<a class="button" href="%1$s" style="display:inline-block;padding:12px 24px;margin:20px 0;color:#ffffff;background-color:#7f54b3;border-radius:4px;text-decoration:none;">%2$s</a>',
    esc_url( $payment_url ),
    esc_html__( 'Payer maintenant', 'woocommerce-reminder' )
);
?>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-top:20px;">
    <tr>
        <td style="padding:12px 0;">
            <strong><?php printf( esc_html__( 'Commande #%s', 'woocommerce-reminder' ), esc_html( $order_number ) ); ?></strong>
        </td>
    </tr>
    <tr>
        <td style="padding:12px 0;">
            <span><?php esc_html_e( 'Montant restant dû :', 'woocommerce-reminder' ); ?></span>
            <span style="display:block;margin-top:4px;font-size:18px;font-weight:bold;">
                <?php echo wp_kses_post( $order_total ); ?>
            </span>
        </td>
    </tr>
    <tr>
        <td style="padding:12px 0;">
            <?php echo $payment_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </td>
    </tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
