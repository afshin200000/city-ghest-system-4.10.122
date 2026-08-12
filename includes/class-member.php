<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Member {

    public static function init() {
        add_shortcode( 'cgs_dashboard', array( __CLASS__, 'render_dashboard' ) );
        add_shortcode( 'cgs_login', array( __CLASS__, 'render_login' ) );

        // Protect dashboard page
        add_action( 'template_redirect', array( __CLASS__, 'protect_pages' ) );
    }

    public static function protect_pages() {
        $dash_id  = (int) cgs_get_option( 'dashboard_page_id' );
        $login_id = (int) cgs_get_option( 'login_page_id' );

        if ( is_page( $dash_id ) && ! is_user_logged_in() ) {
            cgs_redirect( cgs_get_login_url() );
        }

        // If logged-in member visits login page → go to dashboard
        if ( is_page( $login_id ) && is_user_logged_in() && cgs_is_member() ) {
            cgs_redirect( cgs_get_dashboard_url() );
        }
    }

    public static function render_login( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>شما قبلاً وارد شده‌اید. <a href="' . esc_url( cgs_get_dashboard_url() ) . '">ورود به پنل</a></p>';
        }

        ob_start();
        include CGS_PLUGIN_DIR . 'public/views/login.php';
        return ob_get_clean();
    }

    public static function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>لطفاً ابتدا <a href="' . esc_url( cgs_get_login_url() ) . '">وارد شوید</a>.</p>';
        }

        if ( ! cgs_is_member() && ! current_user_can( 'manage_options' ) ) {
            return '<p>شما به این بخش دسترسی ندارید.</p>';
        }

        ob_start();
        include CGS_PLUGIN_DIR . 'public/views/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Get current member's application
     */
    public static function get_current_application() {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ) );
    }
}
