<?php
/**
 * Admin settings for WooCommerce Reminder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WR_Admin {

    const OPTION_KEY = 'wr_settings';
    const MENU_SLUG  = 'wr-settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting(
            'wr_settings_group',
            self::OPTION_KEY,
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array_merge(
                    self::get_default_settings(),
                    array(
                        'wr_days_after' => 30,
                    )
                ),
            )
        );

        add_settings_section(
            'wr_general_section',
            __( 'Invoice reminder settings', 'woocommerce-reminder' ),
            '__return_false',
            'wr_settings_page'
        );

        add_settings_field(
            'wr_days_after',
            __( 'Send after (days)', 'woocommerce-reminder' ),
            array( $this, 'render_days_after_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_statuses',
            __( 'Order statuses', 'woocommerce-reminder' ),
            array( $this, 'render_statuses_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_subject',
            __( 'Email subject', 'woocommerce-reminder' ),
            array( $this, 'render_subject_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_body',
            __( 'Email body', 'woocommerce-reminder' ),
            array( $this, 'render_body_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_attach_pdf',
            __( 'Attachments', 'woocommerce-reminder' ),
            array( $this, 'render_attach_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_brand_logo',
            __( 'Brand logo', 'woocommerce-reminder' ),
            array( $this, 'render_brand_logo_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'wr_brand_color',
            __( 'Accent color', 'woocommerce-reminder' ),
            array( $this, 'render_brand_color_field' ),
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
            self::MENU_SLUG,
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( 'woocommerce_page_' . self::MENU_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_script(
            'wr-admin-settings',
            WR_PLUGIN_URL . 'assets/js/admin-settings.js',
            array( 'jquery', 'wp-color-picker' ),
            WR_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'wr-admin-settings',
            'wrAdminSettings',
            array(
                'mediaTitle' => __( 'Select a logo', 'woocommerce-reminder' ),
                'mediaButton'=> __( 'Use this logo', 'woocommerce-reminder' ),
                'noLogo'     => __( 'No logo selected.', 'woocommerce-reminder' ),
            )
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
        $settings = wp_parse_args( (array) $settings, $defaults );

        $clean_settings                     = array();
        $clean_settings['wr_days_after']    = max( 1, absint( $settings['wr_days_after'] ) );
        $clean_settings['wr_subject']       = sanitize_text_field( $settings['wr_subject'] );
        $clean_settings['wr_body']          = wp_kses_post( $settings['wr_body'] );
        $clean_settings['wr_attach_pdf']    = ! empty( $settings['wr_attach_pdf'] ) ? 1 : 0;

        $submitted_statuses = array();
        if ( isset( $settings['wr_statuses'] ) ) {
            $submitted_statuses = (array) $settings['wr_statuses'];
        } elseif ( isset( $settings['statuses'] ) ) {
            $submitted_statuses = (array) $settings['statuses'];
        }

        $order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
        $order_statuses['wc-transmettre-a-planzer'] = __( 'Transmettre à Planzer', 'woocommerce-reminder' );

        $allowed_statuses = array();
        foreach ( array_keys( $order_statuses ) as $status_key ) {
            if ( 0 === strpos( $status_key, 'wc-' ) ) {
                $status_key = substr( $status_key, 3 );
            }

            $allowed_statuses[] = sanitize_key( $status_key );
        }
        $allowed_statuses = array_unique( $allowed_statuses );

        $normalized_statuses = array();
        foreach ( $submitted_statuses as $status_key ) {
            $status_key = sanitize_key( $status_key );

            if ( 0 === strpos( $status_key, 'wc-' ) ) {
                $status_key = substr( $status_key, 3 );
            }

            if ( empty( $status_key ) || ! in_array( $status_key, $allowed_statuses, true ) ) {
                continue;
            }

            $normalized_statuses[] = $status_key;
        }

        $normalized_statuses               = array_values( array_unique( $normalized_statuses ) );
        $clean_settings['wr_statuses']     = $normalized_statuses;
        $clean_settings['statuses']        = $normalized_statuses;

        $logo_id = isset( $settings['wr_brand_logo'] ) ? absint( $settings['wr_brand_logo'] ) : 0;
        if ( $logo_id > 0 && ! get_post( $logo_id ) ) {
            $logo_id = 0;
        }
        $clean_settings['wr_brand_logo'] = $logo_id;

        $color = isset( $settings['wr_brand_color'] ) ? sanitize_hex_color( $settings['wr_brand_color'] ) : '';
        $clean_settings['wr_brand_color'] = $color ? $color : '';

        return $clean_settings;
    }

    /**
     * Render order statuses field.
     */
    public function render_statuses_field() {
        $settings           = self::get_settings();
        $selected_statuses  = isset( $settings['wr_statuses'] ) && is_array( $settings['wr_statuses'] ) ? $settings['wr_statuses'] : array();
        $selected_statuses  = array_map( 'sanitize_key', $selected_statuses );
        $selected_prefixed  = array();
        foreach ( $selected_statuses as $status ) {
            if ( 0 === strpos( $status, 'wc-' ) ) {
                $selected_prefixed[] = $status;
            } else {
                $selected_prefixed[] = 'wc-' . $status;
            }
        }
        $selected_prefixed = array_values( array_unique( $selected_prefixed ) );

        $order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
        $order_statuses['wc-transmettre-a-planzer'] = __( 'Transmettre à Planzer', 'woocommerce-reminder' );

        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_statuses][]" multiple="multiple" size="6" style="min-width: 220px;">
            <?php foreach ( $order_statuses as $status_key => $status_label ) : ?>
                <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( in_array( $status_key, $selected_prefixed, true ) ); ?>><?php echo esc_html( $status_label ); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Select the order statuses that should receive reminder emails.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render days after field.
     */
    public function render_days_after_field() {
        $settings = self::get_settings();
        ?>
        <input type="number" min="1" step="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_days_after]" value="<?php echo esc_attr( $settings['wr_days_after'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Number of days to wait after an order is created before sending the reminder. Par défaut : 30 jours.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render subject field.
     */
    public function render_subject_field() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_subject]" value="<?php echo esc_attr( $settings['wr_subject'] ); ?>" />
        <?php
    }

    /**
     * Render body field.
     */
    public function render_body_field() {
        $settings = self::get_settings();
        ?>
        <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_body]" rows="8" class="large-text"><?php echo esc_textarea( $settings['wr_body'] ); ?></textarea>
        <div class="wr-placeholder-cheatsheet">
            <p><strong><?php esc_html_e( 'Available placeholders:', 'woocommerce-reminder' ); ?></strong></p>
            <ul>
                <li><code>{customer_name}</code> – <?php esc_html_e( 'Customer full name', 'woocommerce-reminder' ); ?></li>
                <li><code>{order_number}</code> – <?php esc_html_e( 'Order number', 'woocommerce-reminder' ); ?></li>
                <li><code>{order_total}</code> – <?php esc_html_e( 'Order total formatted for the customer', 'woocommerce-reminder' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render attach PDF field.
     */
    public function render_attach_field() {
        $settings = self::get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_attach_pdf]" value="1" <?php checked( $settings['wr_attach_pdf'], 1 ); ?> />
            <?php esc_html_e( 'Attach the invoice PDF to reminder emails.', 'woocommerce-reminder' ); ?>
        </label>
        <?php
    }

    /**
     * Render brand logo field.
     */
    public function render_brand_logo_field() {
        $settings      = self::get_settings();
        $attachment_id = ! empty( $settings['wr_brand_logo'] ) ? absint( $settings['wr_brand_logo'] ) : 0;
        $preview       = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'style' => 'max-width:150px;height:auto;' ) ) : '';
        ?>
        <div class="wr-brand-logo-field">
            <div class="wr-brand-logo-preview" style="margin-bottom: 10px;">
                <?php
                if ( $preview ) {
                    echo wp_kses_post( $preview );
                } else {
                    echo '<span class="description">' . esc_html__( 'No logo selected.', 'woocommerce-reminder' ) . '</span>';
                }
                ?>
            </div>
            <input type="hidden" class="wr-brand-logo-id" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_brand_logo]" value="<?php echo esc_attr( $attachment_id ); ?>" />
            <button type="button" class="button wr-brand-logo-upload"><?php esc_html_e( 'Choose logo', 'woocommerce-reminder' ); ?></button>
            <button type="button" class="button wr-brand-logo-remove" <?php disabled( ! $attachment_id ); ?>><?php esc_html_e( 'Remove', 'woocommerce-reminder' ); ?></button>
        </div>
        <p class="description"><?php esc_html_e( 'Optional logo displayed in reminder emails.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render brand color field.
     */
    public function render_brand_color_field() {
        $settings = self::get_settings();
        ?>
        <input type="text" class="wr-color-field" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[wr_brand_color]" value="<?php echo esc_attr( $settings['wr_brand_color'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Optional accent color applied to reminder templates.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Retrieve settings from the options table.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = self::get_default_settings();

        $settings = wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );

        $settings['wr_days_after'] = absint( $settings['wr_days_after'] );

        if ( $settings['wr_days_after'] < 1 ) {
            $settings['wr_days_after'] = 30;
        }

        if ( isset( $settings['attach_invoice'] ) && ! isset( $settings['attach_pdf'] ) ) {
            $settings['attach_pdf'] = ! empty( $settings['attach_invoice'] ) ? 1 : 0;
        }

        $settings['attach_pdf'] = ! empty( $settings['attach_pdf'] ) ? 1 : 0;

        $statuses = array();
        if ( isset( $settings['wr_statuses'] ) && is_array( $settings['wr_statuses'] ) ) {
            $statuses = $settings['wr_statuses'];
        } elseif ( isset( $settings['statuses'] ) && is_array( $settings['statuses'] ) ) {
            $statuses = $settings['statuses'];
        }

        $statuses = array_values( array_unique( array_filter( array_map( 'sanitize_key', $statuses ) ) ) );

        $settings['wr_statuses'] = $statuses;
        $settings['statuses']     = $statuses;

        unset( $settings['attach_invoice'] );

        return $settings;
    }

    /**
     * Default settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            'wr_days_after'  => 30,
            'wr_subject'     => __( 'Payment reminder for order {order_number}', 'woocommerce-reminder' ),
            'wr_body'        => __( 'Hello {customer_name},<br><br>This is a friendly reminder that order {order_number} still has an outstanding balance of {order_total}.<br><br>Thank you for your business.', 'woocommerce-reminder' ),
            'wr_attach_pdf'  => 1,
            'wr_brand_logo'  => 0,
            'wr_brand_color' => '',
            'wr_statuses'    => array( 'pending', 'on-hold' ),
            'statuses'       => array( 'pending', 'on-hold' ),
        );
    }
}
