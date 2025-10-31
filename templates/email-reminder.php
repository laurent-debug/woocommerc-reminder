<?php
/**
 * Email template for WooCommerce Reminder.
 *
 * @var string $subject
 * @var string $body
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
            .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
            h1 { font-size: 22px; margin-bottom: 20px; }
            .content { line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <?php if ( ! empty( $subject ) ) : ?>
                <h1><?php echo esc_html( $subject ); ?></h1>
            <?php endif; ?>

            <div class="content"><?php echo wp_kses_post( $body ); ?></div>
        </div>
    </body>
</html>
