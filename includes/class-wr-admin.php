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
            __( 'Paramètres de relance', 'woocommerce-reminder' ),
            '__return_false',
            'wr_settings_page'
        );

        add_settings_field(
            'days_after',
            __( 'Envoyer après (jours)', 'woocommerce-reminder' ),
            array( $this, 'render_days_after_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'subject',
            __( 'Sujet de l’e-mail', 'woocommerce-reminder' ),
            array( $this, 'render_subject_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'body',
            __( 'Corps de l’e-mail', 'woocommerce-reminder' ),
            array( $this, 'render_body_field' ),
            'wr_settings_page',
            'wr_general_section'
        );

        add_settings_field(
            'attach_pdf',
            __( 'Pièce jointe', 'woocommerce-reminder' ),
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

        $clean_settings               = array();
        $clean_settings['days_after'] = max( 1, absint( $settings['days_after'] ) );
        $clean_settings['subject']    = sanitize_text_field( $settings['subject'] );
        $clean_settings['body']       = wp_kses_post( $settings['body'] );

        $attach_flag = null;
        if ( array_key_exists( 'attach_pdf', $settings ) ) {
            $attach_flag = $settings['attach_pdf'];
        } elseif ( array_key_exists( 'attach_invoice', $settings ) ) {
            $attach_flag = $settings['attach_invoice'];
        }

        $clean_settings['attach_pdf'] = ! empty( $attach_flag ) ? 1 : 0;

        return $clean_settings;
    }

    /**
     * Render days after field.
     */
    public function render_days_after_field() {
        $settings = self::get_settings();
        ?>
        <input type="number" min="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[days_after]" value="<?php echo esc_attr( $settings['days_after'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Nombre de jours après la création de la commande avant d’envoyer le rappel.', 'woocommerce-reminder' ); ?></p>
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
     * Render body field.
     */
    public function render_body_field() {
        $settings = self::get_settings();
        ?>
        <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[body]" rows="6" class="large-text"><?php echo esc_textarea( $settings['body'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Placeholders disponibles : {customer_name}, {order_number}, {order_total}.', 'woocommerce-reminder' ); ?></p>
        <?php
    }

    /**
     * Render attach invoice field.
     */
    public function render_attach_field() {
        $settings = self::get_settings();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[attach_pdf]" value="1" <?php checked( $settings['attach_pdf'], 1 ); ?> />
            <?php esc_html_e( 'Joindre la facture PDF', 'woocommerce-reminder' ); ?>
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

        $settings = wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );

        if ( isset( $settings['attach_invoice'] ) && ! isset( $settings['attach_pdf'] ) ) {
            $settings['attach_pdf'] = ! empty( $settings['attach_invoice'] ) ? 1 : 0;
        }

        $settings['attach_pdf'] = ! empty( $settings['attach_pdf'] ) ? 1 : 0;

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
            'days_after'     => 7,
            'subject'        => __( 'Relance concernant la commande {order_number}', 'woocommerce-reminder' ),
            'body'           => __( 'Bonjour {customer_name},<br><br>Nous vous rappelons que la commande {order_number} présente un solde de {order_total}.<br><br>Merci de finaliser votre paiement dès que possible.', 'woocommerce-reminder' ),
            'attach_pdf'     => 1,
        );
    }
}
