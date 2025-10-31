<?php
/**
 * Admin settings for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Admin {

    const OPTION_KEY = 'wr_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting( 'wr_settings_group', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );

        add_settings_section(
            'wr_general_section',
            __( 'Reminder Settings', 'woocommerce-reminder' ),
            '__return_false',
            'wr_settings_page'
        );

        add_settings_field(
            'enabled',
            __( 'Enable reminders', 'woocommerce-reminder' ),
            array( $this, 'render_enabled_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'days_after',
            __( 'Send after (days)', 'woocommerce-reminder' ),
            array( $this, 'render_days_after_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'subject',
            __( 'Email subject', 'woocommerce-reminder' ),
            array( $this, 'render_subject_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'heading',
            __( 'Email heading', 'woocommerce-reminder' ),
            array( $this, 'render_heading_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'body',
            __( 'Email body', 'woocommerce-reminder' ),
            array( $this, 'render_body_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'attach_invoice',
            __( 'Attach invoice summary', 'woocommerce-reminder' ),
            array( $this, 'render_attach_field' ),
            'wr_settings_page',
            'wr_general_section'
        );
    }

    /**
     * Register settings page.
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Invoice Reminders', 'woocommerce-reminder' ),
            __( 'Invoice Reminders', 'woocommerce-reminder' ),
            'manage_woocommerce',
            'wr-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render settings page content.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WooCommerce Invoice Reminders', 'woocommerce-reminder' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wr_settings_group' );
                do_settings_sections( 'wr_settings_page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $settings Raw settings.
     *
     * @return array
     */
    public function sanitize_settings( $settings ) {
        $defaults = self::get_default_settings();
        $settings = wp_parse_args( $settings, $defaults );

        $settings['enabled']        = ! empty( $settings['enabled'] ) ? 1 : 0;
        $settings['days_after']     = max( 1, absint( $settings['days_after'] ) );
        $settings['subject']        = sanitize_text_field( $settings['subject'] );
        $settings['heading']        = sanitize_text_field( $settings['heading'] );
        $settings['body']           = wp_kses_post( $settings['body'] );
        $settings['attach_invoice'] = ! empty( $settings['attach_invoice'] ) ? 1 : 0;

        return $settings;
    }

    /**
     * Render enable checkbox.
     */
    public function render_enabled_field() {
        $settings = self::get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
            <?php esc_html_e( 'Send automatic reminders for unpaid orders', 'woocommerce-reminder' ); ?>
        </label>
        <?php
    }

    /**
     * Render days after field.
     */
    public function render_days_after_field() {
        $settings = self::get_settings();
        ?>
        <input type="number" min="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[days_after]" value="<?php echo esc_attr( $settings['days_after'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Number of days after the order creation to send the first reminder.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render subject field.
     */
    public function render_subject_field() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[subject]" value="<?php echo esc_attr( $settings['subject'] ); ?>" />
        <?php
    }

    /**
     * Render heading field.
     */
    public function render_heading_field() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[heading]" value="<?php echo esc_attr( $settings['heading'] ); ?>" />
        <?php
    }

    /**
     * Render body field.
     */
    public function render_body_field() {
        $settings = self::get_settings();
        ?>
        <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[body]" rows="6" class="large-text"><?php echo esc_textarea( $settings['body'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Use {{order_number}}, {{order_date}}, {{billing_first_name}}, and {{total}} placeholders.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render attach invoice field.
     */
    public function render_attach_field() {
        $settings = self::get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[attach_invoice]" value="1" <?php checked( $settings['attach_invoice'], 1 ); ?> />
            <?php esc_html_e( 'Attach an invoice summary (HTML formatted).', 'woocommerce-reminder' ); ?>
        </label>
        <?php
    }

    /**
     * Retrieve settings from the options table.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = self::get_default_settings();

        return wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );
    }

    /**
     * Default settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            'enabled'        => 0,
            'days_after'     => 7,
            'subject'        => __( 'Friendly reminder for order {{order_number}}', 'woocommerce-reminder' ),
            'heading'        => __( 'We are still waiting on your payment', 'woocommerce-reminder' ),
            'body'           => __( 'Hello {{billing_first_name}},<br><br>This is a gentle reminder that order {{order_number}} placed on {{order_date}} is still pending payment. The outstanding total is {{total}}.<br><br>Please complete your payment at your earliest convenience.<br><br>Thank you!', 'woocommerce-reminder' ),
            'attach_invoice' => 1,
        );
    }
}
