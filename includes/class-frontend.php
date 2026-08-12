<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Frontend {

    public static function init() {
        add_action( 'wp_ajax_cgs_get_cities', array( __CLASS__, 'ajax_get_cities' ) );
        add_action( 'wp_ajax_nopriv_cgs_get_cities', array( __CLASS__, 'ajax_get_cities' ) );

        add_shortcode( 'cgs_form', array( __CLASS__, 'render_form' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    
    public static function should_load_assets() {
        if ( is_admin() ) return false;
        global $post;
        if ( $post && is_a( $post, 'WP_Post' ) ) {
            if ( has_shortcode( $post->post_content, 'cgs_form' )
                || has_shortcode( $post->post_content, 'cgs_dashboard' )
                || has_shortcode( $post->post_content, 'cgs_login' )
                || has_shortcode( $post->post_content, 'city_ghest_form' ) ) {
                return true;
            }
        }
        // صفحات اختصاصی افزونه
        if ( function_exists( 'cgs_get_option' ) ) {
            $pages = array( 'login_page_id', 'dashboard_page_id' );
            foreach ( $pages as $k ) {
                $pid = (int) cgs_get_option( $k, 0 );
                if ( $pid && is_page( $pid ) ) return true;
            }
        }
        return false;
    }

    public static function enqueue_assets() {
        if ( ! self::should_load_assets() ) {
            return;
        }
        // Only load on relevant pages to keep it light
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_shortcode = has_shortcode( $post->post_content, 'cgs_form' )
                      || has_shortcode( $post->post_content, 'cgs_dashboard' )
                      || has_shortcode( $post->post_content, 'cgs_login' );

        if ( ! $has_shortcode ) {
            return;
        }

        wp_enqueue_style( 'cgs-icons', CGS_PLUGIN_URL . 'assets/icons/fontawesome/cgs-icons.css', array(), CGS_VERSION );
        wp_enqueue_style( 'cgs-persian-fonts', CGS_PLUGIN_URL . 'assets/fonts/persian-fonts.css', array(), CGS_VERSION );
        wp_enqueue_style( 'cgs-vazirmatn', 'https://cdn.jsdelivr.net/npm/vazirmatn@33.003/Vazirmatn-font-face.css', array(), null );
        wp_enqueue_style( 'cgs-public', CGS_PLUGIN_URL . 'public/css/public.css', array(), CGS_VERSION );
        wp_enqueue_script( 'cgs-jalali-dp', CGS_PLUGIN_URL . 'public/js/jalali-datepicker.js', array( 'jquery' ), CGS_VERSION, true );
        
        wp_enqueue_style( 'cgs-form-ds', CGS_PLUGIN_URL . 'public/css/form-design-system.css', array(), CGS_VERSION );
        wp_enqueue_script( 'cgs-public', CGS_PLUGIN_URL . 'public/js/public.js', array( 'jquery', 'cgs-jalali-dp' ), CGS_VERSION, true );

        wp_localize_script( 'cgs-public', 'cgsPublic', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'cgs_frontend_nonce' ),
            'btn_sound'       => 1,
            'sound_enabled'   => (int) cgs_get_option( 'sound_enabled', 1 ),
            'sound_volume'    => (int) cgs_get_option( 'sound_volume', 40 ),
            'locations'       => function_exists( 'cgs_get_iran_locations' ) ? cgs_get_iran_locations() : array(),
            'jalaliSettings'  => function_exists( 'cgs_get_jalali_settings' ) ? cgs_get_jalali_settings() : array(),
        ) );
    }

    public static function render_form( $atts ) {
        $atts = shortcode_atts( array(
            'type' => 'representative',
        ), $atts, 'cgs_form' );

        $type = sanitize_key( $atts['type'] );
        if ( ! cgs_get_application_type( $type ) ) {
            return '<p>نوع فرم نامعتبر است.</p>';
        }

        $steps = CGS_Form_Builder::get_fields_by_step( $type );
        if ( empty( $steps ) ) {
            return '<p>هنوز فیلدی برای این فرم تعریف نشده است.</p>';
        }

        ob_start();
        // Inject custom form styles
        echo '<style>' . CGS_Form_Styles::get_css( $type ) . '</style>';
        include CGS_PLUGIN_DIR . 'public/views/form-multi-step.php';
        return ob_get_clean();
    }

    public static function ajax_get_cities() {
        $province = isset( $_GET['province'] ) ? sanitize_text_field( wp_unslash( $_GET['province'] ) ) : ( isset( $_POST['province'] ) ? sanitize_text_field( wp_unslash( $_POST['province'] ) ) : '' );
        $all = function_exists( 'cgs_get_iran_locations' ) ? cgs_get_iran_locations() : array();
        $cities = array();
        $code = '';
        if ( $province && isset( $all[ $province ] ) ) {
            $cities = $all[ $province ]['cities'] ?? array();
            $code   = $all[ $province ]['code'] ?? '';
        }
        wp_send_json_success( array( 'cities' => $cities, 'code' => $code, 'province' => $province ) );
    }
}
