<?php
/**
 * Plugin Name: WooCommerce Reminder
 * Plugin URI:  https://example.com/
 * Description: Send automated reminder emails for unpaid WooCommerce orders.
 * Version:     1.0.0
 * Author:      Your Name
 * Text Domain: woocommerce-reminder
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WR_PLUGIN_FILE' ) ) {
    define( 'WR_PLUGIN_FILE', __FILE__ );
}

define( 'WR_PLUGIN_PATH', plugin_dir_path( WR_PLUGIN_FILE ) );
define( 'WR_PLUGIN_URL', plugin_dir_url( WR_PLUGIN_FILE ) );
define( 'WR_PLUGIN_VERSION', '1.0.0' );

require_once WR_PLUGIN_PATH . 'includes/class-wr-admin.php';
require_once WR_PLUGIN_PATH . 'includes/class-wr-bulk.php';
require_once WR_PLUGIN_PATH . 'includes/class-wr-cron.php';
require_once WR_PLUGIN_PATH . 'includes/class-wr-mailer.php';
require_once WR_PLUGIN_PATH . 'includes/class-wr-pdf.php';

/**
 * Main plugin bootstrap.
 */
class WooCommerce_Reminder {

    /**
     * Singleton instance.
     *
     * @var WooCommerce_Reminder|null
     */
    protected static $instance = null;

    /**
     * Admin handler.
     *
     * @var WR_Admin
     */
    protected $admin;

    /**
     * Bulk handler.
     *
     * @var WR_Bulk
     */
    protected $bulk;

    /**
     * Cron handler.
     *
     * @var WR_Cron
     */
    protected $cron;

    /**
     * Retrieve singleton instance.
     *
     * @return WooCommerce_Reminder
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct() {
        $this->admin = new WR_Admin();
        $this->bulk  = new WR_Bulk();
        $this->cron  = new WR_Cron( new WR_Mailer(), new WR_PDF() );

        register_activation_hook( WR_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( WR_PLUGIN_FILE, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'woocommerce-reminder', false, dirname( plugin_basename( WR_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Plugin activation callback.
     */
    public function activate() {
        $this->cron->schedule_events();
    }

    /**
     * Plugin deactivation callback.
     */
    public function deactivate() {
        $this->cron->clear_events();
    }
}

WooCommerce_Reminder::instance();
