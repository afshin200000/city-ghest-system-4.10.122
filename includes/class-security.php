<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Security & WordPress footprint removal for members
 */
class CGS_Security {

    public static function init() {
        // Block members from wp-admin
        add_action( 'admin_init', array( __CLASS__, 'block_admin_access' ) );

        // Hide admin bar for members
        add_action( 'after_setup_theme', array( __CLASS__, 'hide_admin_bar' ) );

        // Custom login redirect
        add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
        add_filter( 'logout_redirect', array( __CLASS__, 'logout_redirect' ), 10, 3 );

        // Prevent members from seeing WP login page branding (optional extra)
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'custom_login_styles' ) );

        // Remove WP generator and version info
        remove_action( 'wp_head', 'wp_generator' );
        add_filter( 'the_generator', '__return_empty_string' );

        // Disable XML-RPC if not needed (extra hardening)
        add_filter( 'xmlrpc_enabled', '__return_false' );

        // Restrict REST API for non-logged users (basic)
        add_filter( 'rest_authentication_errors', array( __CLASS__, 'restrict_rest' ) );
    }

    /**
     * Completely block cg_member from accessing wp-admin
     */
    public static function block_admin_access() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( cgs_is_member() && ! current_user_can( 'manage_options' ) ) {
            // Allow AJAX
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return;
            }
            cgs_redirect( cgs_get_dashboard_url() );
        }
    }

    public static function hide_admin_bar() {
        if ( cgs_is_member() && ! current_user_can( 'manage_options' ) ) {
            show_admin_bar( false );
        }
    }

    public static function login_redirect( $redirect_to, $requested, $user ) {
        if ( is_wp_error( $user ) || ! $user ) {
            return $redirect_to;
        }

        if ( in_array( 'cg_member', (array) $user->roles, true ) ) {
            return cgs_get_dashboard_url();
        }

        return $redirect_to;
    }

    public static function logout_redirect( $redirect_to, $requested, $user ) {
        return cgs_get_login_url();
    }

    public static function custom_login_styles() {
        // Only if using default wp-login (we prefer custom page)
        ?>
        <style>
            body.login { background: #f5f7fa; }
            #login h1 a { background-image: none !important; width: auto; height: auto; text-indent: 0; font-size: 24px; font-weight: bold; color: #1a237e; }
        </style>
        <?php
    }

    public static function restrict_rest( $result ) {
        if ( ! empty( $result ) ) {
            return $result;
        }
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_disabled', 'REST API محدود شده است.', array( 'status' => 401 ) );
        }
        return $result;
    }

    /**
     * Secure file upload directory (outside public if possible, or protected)
     */
    public static function get_upload_dir() {
        $upload = wp_upload_dir();
        $dir    = $upload['basedir'] . '/city-ghest-files';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
            // Protect with .htaccess
            $htaccess = $dir . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
            }
            // Index protection
            file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
        }
        return $dir;
    }

    public static function get_upload_url() {
        $upload = wp_upload_dir();
        return $upload['baseurl'] . '/city-ghest-files';
    }
}
