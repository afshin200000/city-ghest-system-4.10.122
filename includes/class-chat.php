<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Chat {

    public static function init() {
        add_action( 'wp_ajax_cgs_send_message', array( __CLASS__, 'ajax_send' ) );
        add_action( 'wp_ajax_cgs_get_messages', array( __CLASS__, 'ajax_get' ) );
    }

    public static function ajax_send() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'لطفاً وارد شوید.' );
        }

        $app_id  = absint( $_POST['application_id'] ?? 0 );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( ! $app_id || empty( $message ) ) {
            wp_send_json_error( 'پیام نامعتبر است.' );
        }

        $user_id = get_current_user_id();
        $is_admin = current_user_can( 'cgs_manage_applications' ) || current_user_can( 'manage_options' );

        // Permission check
        if ( ! $is_admin ) {
            $app = CGS_Application::get( $app_id );
            if ( ! $app || (int) $app->user_id !== $user_id ) {
                wp_send_json_error( 'دسترسی غیرمجاز.' );
            }
        }

        global $wpdb;
        $table = CGS_Database::get_table( 'messages' );

        $wpdb->insert( $table, array(
            'application_id' => $app_id,
            'sender_id'      => $user_id,
            'sender_type'    => $is_admin ? 'admin' : 'member',
            'message'        => $message,
            'is_read'        => 0,
            'created_at'     => current_time( 'mysql' ),
        ) );

        wp_send_json_success( array(
            'id'      => $wpdb->insert_id,
            'message' => $message,
            'time'    => cgs_format_date( current_time( 'timestamp' ), 'Y/m/d H:i' ),
        ) );
    }

    public static function ajax_get() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'لطفاً وارد شوید.' );
        }

        $app_id = absint( $_POST['application_id'] ?? 0 );
        if ( ! $app_id ) {
            wp_send_json_error( 'شناسه نامعتبر' );
        }

        // رفع باگ امنیتی حیاتی: قبلاً این تابع هیچ بررسی مالکیتی نداشت و هر کاربر
        // لاگین‌شده می‌توانست با حدس زدن application_id، مکالمات خصوصی سایر
        // متقاضیان را بخواند (IDOR / کنترل دسترسی ناقص).
        $user_id  = get_current_user_id();
        $is_admin = current_user_can( 'cgs_manage_applications' ) || current_user_can( 'manage_options' );
        if ( ! $is_admin ) {
            $app = CGS_Application::get( $app_id );
            if ( ! $app || (int) $app->user_id !== $user_id ) {
                wp_send_json_error( 'دسترسی غیرمجاز.' );
            }
        }

        $messages = self::get_messages( $app_id );
        wp_send_json_success( $messages );
    }

    public static function get_messages( $application_id ) {
        global $wpdb;
        $table = CGS_Database::get_table( 'messages' );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE application_id = %d ORDER BY created_at ASC",
            $application_id
        ), ARRAY_A );

        foreach ( $rows as &$row ) {
            $row['time_formatted'] = cgs_format_date( strtotime( $row['created_at'] ), 'Y/m/d H:i' );
        }

        return $rows;
    }
}
