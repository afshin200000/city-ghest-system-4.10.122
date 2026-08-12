<?php
/**
 * Lightweight CRM module for City Ghest
 * Stores contacts, activities, pipeline stages + hooks for external CRM
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_CRM {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
        add_action( 'wp_ajax_cgs_crm_save_contact', array( __CLASS__, 'ajax_save_contact' ) );
        add_action( 'wp_ajax_cgs_crm_add_activity', array( __CLASS__, 'ajax_add_activity' ) );
        add_action( 'wp_ajax_cgs_crm_update_stage', array( __CLASS__, 'ajax_update_stage' ) );
        // Sync hooks when application status changes
        add_action( 'cgs_application_status_changed', array( __CLASS__, 'on_application_status' ), 10, 3 );
    }

    public static function menu() {
        add_submenu_page(
            'city-ghest',
            'CRM مشتریان',
            'CRM',
            'cgs_manage_applications',
            'cgs-crm',
            array( __CLASS__, 'page' )
        );
    }

    public static function stages() {
        $labels = function_exists( 'cgs_get_crm_stage_labels' ) ? cgs_get_crm_stage_labels() : array(
            'lead'        => 'سرنخ',
            'contacted'   => 'تماس گرفته‌شده',
            'qualified'   => 'واجد شرایط',
            'proposal'    => 'پیشنهاد / قرارداد',
            'won'         => 'موفق',
            'lost'        => 'از دست‌رفته',
        );
        return apply_filters( 'cgs_crm_stages', $labels );
    }

    public static function get_contacts() {
        global $wpdb;
        $table = CGS_Database::get_table( 'crm_contacts' );
        // Fallback if table not yet created
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( ! $exists ) {
            return array();
        }
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY updated_at DESC LIMIT 200", ARRAY_A );
    }

    public static function page() {
        $contacts = self::get_contacts();
        $stages = self::stages();
        $external = cgs_get_option( 'crm_external_provider', '' );
        include CGS_PLUGIN_DIR . 'admin/views/crm.php';
    }

    public static function ajax_save_contact() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        global $wpdb;
        $table = CGS_Database::get_table( 'crm_contacts' );
        $data = array(
            'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
            'mobile'     => sanitize_text_field( $_POST['mobile'] ?? '' ),
            'email'      => sanitize_email( $_POST['email'] ?? '' ),
            'type'       => sanitize_key( $_POST['type'] ?? 'applicant' ),
            'stage'      => sanitize_key( $_POST['stage'] ?? 'lead' ),
            'notes'      => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            'app_id'     => absint( $_POST['app_id'] ?? 0 ),
            'updated_at' => current_time( 'mysql' ),
        );
        $id = absint( $_POST['id'] ?? 0 );
        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            $id = $wpdb->insert_id;
        }
        do_action( 'cgs_crm_contact_saved', $id, $data );
        // External CRM push
        do_action( 'cgs_crm_sync_external', $id, $data );
        wp_send_json_success( array( 'id' => $id ) );
    }

    public static function ajax_add_activity() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        global $wpdb;
        $table = CGS_Database::get_table( 'crm_activities' );
        $wpdb->insert( $table, array(
            'contact_id' => absint( $_POST['contact_id'] ?? 0 ),
            'type'       => sanitize_key( $_POST['type'] ?? 'note' ),
            'content'    => sanitize_textarea_field( $_POST['content'] ?? '' ),
            'created_at' => current_time( 'mysql' ),
            'user_id'    => get_current_user_id(),
        ) );
        wp_send_json_success();
    }

    public static function ajax_update_stage() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        global $wpdb;
        $table = CGS_Database::get_table( 'crm_contacts' );
        $id = absint( $_POST['id'] ?? 0 );
        $stage = sanitize_key( $_POST['stage'] ?? 'lead' );
        $wpdb->update( $table, array( 'stage' => $stage, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
        do_action( 'cgs_crm_stage_changed', $id, $stage );
        wp_send_json_success();
    }

    public static function on_application_status( $app_id, $old_status, $new_status ) {
        // Auto-create / update CRM contact from application
        do_action( 'cgs_crm_from_application', $app_id, $new_status );
    }
}
