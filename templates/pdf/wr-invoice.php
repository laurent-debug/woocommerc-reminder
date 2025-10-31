<?php
/**
 * Invoice template rendered for PDF generation.
 *
 * Variables provided by WR_PDF::prepare_invoice_data().
 *
 * @var array    $store
 * @var string   $invoice_number
 * @var string   $invoice_date
 * @var string[] $billing_lines
 * @var array[]  $items
 * @var array[]  $totals
 * @var string   $order_total
 * @var string   $qr_label
 * @var WC_Order $order
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!doctype html>
<html lang="<?php echo esc_attr( substr( get_locale(), 0, 2 ) ); ?>">
<head>
    <meta charset="utf-8" />
    <title><?php echo esc_html( sprintf( __( 'Invoice #%s', 'woocommerce-reminder' ), $invoice_number ) ); ?></title>
</head>
<body style="margin:0; padding:0; font-family:'Helvetica', Arial, sans-serif; font-size:12px; color:#1f2937; background:#ffffff;">
<div style="padding:24px;">
    <div style="border:1px solid #e2e8f0; padding:24px; border-radius:4px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
            <div>
                <div style="font-size:20px; font-weight:700; letter-spacing:0.5px; margin-bottom:8px;" data-wr-line="logo"><?php echo esc_html( $store['name'] ); ?></div>
                <?php if ( ! empty( $store['lines'] ) ) : ?>
                    <?php foreach ( $store['lines'] as $line ) : ?>
                        <div style="font-size:11px; color:#4a5568; line-height:1.4;" data-wr-line="store-line"><?php echo esc_html( $line ); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#4a5568;"><?php esc_html_e( 'Invoice', 'woocommerce-reminder' ); ?></div>
                <div style="font-size:18px; font-weight:700; margin-top:4px;" data-wr-line="invoice-number">#<?php echo esc_html( $invoice_number ); ?></div>
                <div style="font-size:12px; color:#4a5568; margin-top:4px;" data-wr-line="invoice-date"><?php echo esc_html( $invoice_date ); ?></div>
            </div>
        </div>
        <div style="margin-bottom:24px;">
            <div style="font-size:12px; font-weight:600; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px; color:#4a5568;" data-wr-line="billing-title"><?php esc_html_e( 'Billed to', 'woocommerce-reminder' ); ?></div>
            <?php if ( ! empty( $billing_lines ) ) : ?>
                <?php foreach ( $billing_lines as $line ) : ?>
                    <div style="font-size:12px; line-height:1.4;" data-wr-line="billing-line"><?php echo esc_html( $line ); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:8px; border-bottom:1px solid #cbd5e0; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#4a5568;"><?php esc_html_e( 'Product', 'woocommerce-reminder' ); ?></th>
                    <th style="text-align:center; padding:8px; border-bottom:1px solid #cbd5e0; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#4a5568; width:70px;"><?php esc_html_e( 'Qty', 'woocommerce-reminder' ); ?></th>
                    <th style="text-align:right; padding:8px; border-bottom:1px solid #cbd5e0; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#4a5568; width:120px;"><?php esc_html_e( 'Total', 'woocommerce-reminder' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $items ) ) : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td style="padding:8px; border-bottom:1px solid #edf2f7; font-size:12px;" data-wr-line="item-name"><?php echo esc_html( $item['name'] ); ?></td>
                            <td style="padding:8px; border-bottom:1px solid #edf2f7; text-align:center; font-size:12px;" data-wr-line="item-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                            <td style="padding:8px; border-bottom:1px solid #edf2f7; text-align:right; font-size:12px;" data-wr-line="item-total"><?php echo esc_html( $item['total'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" style="padding:12px; text-align:center; font-size:12px; color:#4a5568;" data-wr-line="no-items"><?php esc_html_e( 'No items found for this order.', 'woocommerce-reminder' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if ( ! empty( $totals ) ) : ?>
                <tfoot>
                    <?php foreach ( $totals as $total ) : ?>
                        <tr>
                            <td colspan="2" style="padding:8px; text-align:right; font-size:12px; font-weight:600; border-top:1px solid #cbd5e0;" data-wr-line="total-label"><?php echo esc_html( $total['label'] ); ?></td>
                            <td style="padding:8px; text-align:right; font-size:12px; font-weight:600; border-top:1px solid #cbd5e0;" data-wr-line="total-value"><?php echo esc_html( $total['value'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tfoot>
            <?php endif; ?>
        </table>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:12px; color:#4a5568;" data-wr-line="pending-label"><?php esc_html_e( 'Amount due', 'woocommerce-reminder' ); ?></div>
                <div style="font-size:16px; font-weight:700; margin-top:4px;" data-wr-line="pending-total"><?php echo esc_html( $order_total ); ?></div>
            </div>
            <div style="width:120px; height:120px; border:1px dashed #a0aec0; border-radius:4px; display:flex; align-items:center; justify-content:center; padding:12px; text-align:center;">
                <span style="font-size:10px; text-transform:uppercase; letter-spacing:1px; color:#4a5568;" data-wr-line="qr-placeholder"><?php echo esc_html( $qr_label ); ?></span>
            </div>
        </div>
    </div>
</div>
</body>
</html>
