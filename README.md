# WooCommerce Reminder

This plugin adds automated reminder emails for WooCommerce orders that are still pending payment. Emails can optionally include an HTML invoice summary attachment.

## Features

* Daily cron job that finds pending or on-hold orders past a configurable age.
* Customisable email subject, heading, and body with placeholders.
* Optional HTML invoice attachment showing the order items and totals.
* WooCommerce admin submenu for managing reminder settings.

## Installation

1. Upload the plugin folder to your WordPress installation in `wp-content/plugins/`.
2. Activate **WooCommerce Reminder** through the **Plugins** screen in WordPress.
3. Visit **WooCommerce → Invoice Reminders** to configure the reminder email settings.

## Development

The plugin is intentionally lightweight and does not include a PDF library. If you need true PDF attachments, replace the implementation in `includes/class-wr-pdf.php` with a generator such as Dompdf or TCPDF.
