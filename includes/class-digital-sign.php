<?php
/**
 * Digital signature integration layer for third-party guarantors.
 *
 * Real verification requires a certified Iranian digital signature provider
 * (e.g. GICA, licensed CA, or bank e-sign APIs). Configure API credentials
 * in settings when available.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Digital_Sign {

    public static function init() {
        add_action( 'wp_ajax_cgs_request_guarantor_sign', array( __CLASS__, 'ajax_request_sign' ) );
        add_action( 'wp_ajax_cgs_check_sign_status', array( __CLASS__, 'ajax_check_status' ) );
    }

    /**
     * Request digital signature from third-party guarantor.
     * Placeholder: stores pending status and fires action for providers.
     */
    public static function ajax_request_sign() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $app_id   = absint( $_POST['application_id'] ?? 0 );
        $mobile   = sanitize_text_field( $_POST['guarantor_mobile'] ?? '' );
        $national = sanitize_text_field( $_POST['guarantor_national_id'] ?? '' );
        $name     = sanitize_text_field( $_POST['guarantor_name'] ?? '' );

        if ( ! $app_id || ! $mobile || ! $national ) {
            wp_send_json_error( 'اطلاعات صاحب سند ناقص است.' );
        }

        // Store pending signature request
        $meta = array(
            'status'     => 'pending',
            'requested_at' => current_time( 'mysql' ),
            'name'       => $name,
            'national_id'=> $national,
            'mobile'     => $mobile,
            'provider'   => cgs_get_option( 'digital_sign_provider', '' ),
        );
        update_option( 'cgs_sign_request_' . $app_id, $meta );

        /**
         * Hook for digital signature providers.
         * Connect your certified API here.
         */
        do_action( 'cgs_request_digital_signature', $app_id, $meta );

        // Update application form data status field if present
        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT form_data FROM $table WHERE id = %d", $app_id ), ARRAY_A );
        if ( $row ) {
            $data = json_decode( $row['form_data'], true ) ?: array();
            $data['guarantor_sign_status'] = 'لینک امضا ارسال شده';
            $wpdb->update( $table, array( 'form_data' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ), array( 'id' => $app_id ) );
        }

        wp_send_json_success( array(
            'message' => 'درخواست امضای دیجیتال ثبت شد. پس از اتصال به ارائه‌دهنده معتبر، لینک احراز هویت برای صاحب سند ارسال می‌شود.',
            'status'  => 'pending',
        ) );
    }

    public static function ajax_check_status() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_applications' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $app_id = absint( $_POST['application_id'] ?? 0 );
        $meta = get_option( 'cgs_sign_request_' . $app_id, array() );
        wp_send_json_success( $meta ?: array( 'status' => 'none' ) );
    }

    /**
     * Mark signature as verified (called by provider webhook or admin).
     */
    public static function mark_verified( $app_id, $provider_ref = '' ) {
        $meta = get_option( 'cgs_sign_request_' . $app_id, array() );
        $meta['status'] = 'verified';
        $meta['verified_at'] = current_time( 'mysql' );
        $meta['provider_ref'] = $provider_ref;
        update_option( 'cgs_sign_request_' . $app_id, $meta );

        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT form_data FROM $table WHERE id = %d", $app_id ), ARRAY_A );
        if ( $row ) {
            $data = json_decode( $row['form_data'], true ) ?: array();
            $data['guarantor_sign_status'] = 'امضا شده و تأیید شده';
            $wpdb->update( $table, array( 'form_data' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ), array( 'id' => $app_id ) );
        }
        do_action( 'cgs_digital_signature_verified', $app_id, $meta );
    }
}
