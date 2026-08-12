<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Standardized AJAX security gate.
 */
class CGS_Ajax {

    public static function verify( $nonce_action = 'cgs_admin_nonce', $nonce_field = 'nonce', $cap = 'manage_options' ) {
        if ( ! check_ajax_referer( $nonce_action, $nonce_field, false ) ) {
            wp_send_json_error( array( 'message' => 'نشست نامعتبر است. صفحه را رفرش کنید.' ), 403 );
        }
        if ( $cap && ! current_user_can( $cap ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
        }
        return true;
    }

    public static function verify_public( $nonce_action = 'cgs_public_nonce', $nonce_field = 'nonce' ) {
        if ( ! check_ajax_referer( $nonce_action, $nonce_field, false ) ) {
            wp_send_json_error( array( 'message' => 'نشست نامعتبر است.' ), 403 );
        }
        return true;
    }
}
