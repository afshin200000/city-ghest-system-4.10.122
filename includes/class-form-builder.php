<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Form_Builder {

    public static function init() {
        add_action( 'wp_ajax_cgs_save_form_fields', array( __CLASS__, 'ajax_save_fields' ) );
        add_action( 'wp_ajax_cgs_get_form_fields', array( __CLASS__, 'ajax_get_fields' ) );
        add_action( 'wp_ajax_cgs_add_field', array( __CLASS__, 'ajax_add_field' ) );
        add_action( 'wp_ajax_cgs_update_field', array( __CLASS__, 'ajax_update_field' ) );
        add_action( 'wp_ajax_cgs_delete_field', array( __CLASS__, 'ajax_delete_field' ) );
        add_action( 'wp_ajax_cgs_save_layout', array( __CLASS__, 'ajax_save_layout' ) );
        add_action( 'wp_ajax_cgs_save_step_meta', array( __CLASS__, 'ajax_save_step_meta' ) );
        add_action( 'admin_init', array( __CLASS__, 'ensure_location_fields' ) );
    }

    /**
     * Ensure province is select and city is select for all types
     */
    public static function ensure_location_fields() {
        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );
        // استان / شهر
        $wpdb->query( "UPDATE $table SET field_type = 'select' WHERE field_key LIKE '%province%' AND field_type != 'select'" );
        $wpdb->query( "UPDATE $table SET field_type = 'select' WHERE field_key LIKE '%city%' AND field_type != 'select'" );
        // تلفن ثابت خودکار از برچسب
        $rows = $wpdb->get_results( "SELECT id, type_key, field_key, label, field_type FROM $table WHERE field_key NOT IN ('landline','area_code','mobile')", ARRAY_A );
        if ( $rows ) {
            foreach ( $rows as $r ) {
                $lab = $r['label'] . ' ' . $r['field_key'];
                $is_land = ( false !== strpos( $lab, 'تلفن ثابت' ) || false !== strpos( $lab, 'تلفن‌ثابت' ) || false !== strpos( $lab, 'شماره ثابت' ) || false !== strpos( $r['field_key'], 'landline' ) || false !== strpos( $r['field_key'], 'phone_fixed' ) );
                if ( ! $is_land && $r['field_type'] === 'tel' && ( false !== strpos( $lab, 'ثابت' ) ) ) {
                    $is_land = true;
                }
                if ( $is_land ) {
                    // اگر landline برای این type وجود ندارد، این فیلد را landline کن
                    $exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM $table WHERE type_key = %s AND field_key = 'landline' AND id != %d",
                        $r['type_key'], $r['id']
                    ) );
                    if ( ! $exists ) {
                        $wpdb->update( $table, array( 'field_key' => 'landline', 'field_type' => 'tel' ), array( 'id' => (int) $r['id'] ) );
                    }
                }
            }
        }
    }

    /**
     * Get fields for a specific application type
     * کش در سطح درخواست + CGS_Database برای جلوگیری از N+1
     */
    public static function get_fields( $type_key, $active_only = true ) {
        static $memo = array();
        $type_key = sanitize_key( $type_key );
        $mk = $type_key . ( $active_only ? '_a' : '_all' );
        if ( isset( $memo[ $mk ] ) ) {
            return $memo[ $mk ];
        }
        if ( class_exists( 'CGS_Database' ) && method_exists( 'CGS_Database', 'get_fields' ) ) {
            $results = CGS_Database::get_fields( $type_key, $active_only );
        } else {
            global $wpdb;
            $table = $wpdb->prefix . 'cgs_form_fields';
            if ( $active_only ) {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE type_key = %s AND (is_active = 1 OR is_active IS NULL) ORDER BY step_number ASC, sort_order ASC",
                        $type_key
                    ),
                    ARRAY_A
                );
            } else {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE type_key = %s ORDER BY step_number ASC, sort_order ASC",
                        $type_key
                    ),
                    ARRAY_A
                );
            }
            $results = $results ? $results : array();
        }
        $memo[ $mk ] = is_array( $results ) ? $results : array();
        return $memo[ $mk ];
    }

    /**
     * Group fields by step — یک بار خواندن فیلدها، بدون کوئری اضافه
     * @param array|null $preloaded اگر فیلدها از قبل لود شده‌اند، کوئری نمی‌زند
     */
    public static function get_fields_by_step( $type_key, $active_only = false, $preloaded = null ) {
        $fields = is_array( $preloaded ) ? $preloaded : self::get_fields( $type_key, $active_only );
        $grouped = array();
        foreach ( $fields as $field ) {
            $step = max( 1, (int) ( $field['step_number'] ?? 1 ) );
            if ( ! isset( $grouped[ $step ] ) ) {
                $grouped[ $step ] = array();
            }
            $grouped[ $step ][] = $field;
        }
        ksort( $grouped );
        return $grouped;
    }

    /**
     * Save full list (used after drag & drop reorder)
     */
    public static function ajax_save_fields() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $type_key = sanitize_text_field( $_POST['type_key'] ?? '' );
        $fields   = isset( $_POST['fields'] ) ? json_decode( stripslashes( $_POST['fields'] ), true ) : array();

        if ( empty( $type_key ) || ! is_array( $fields ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }

        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );

        // Update sort_order and step_number only (safer than delete+insert)
        foreach ( $fields as $index => $field ) {
            if ( empty( $field['id'] ) ) {
                continue;
            }
            $wpdb->update(
                $table,
                array(
                    'sort_order'  => absint( $index + 1 ),
                    'step_number' => absint( $field['step_number'] ?? 1 ),
                ),
                array( 'id' => absint( $field['id'] ) )
            );
        }

        if ( class_exists( 'CGS_Database' ) ) { CGS_Database::invalidate_fields_cache( $type_key ?? '' ); }
        wp_send_json_success( 'ترتیب فیلدها ذخیره شد.' );
    }

    public static function ajax_get_fields() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $type_key = sanitize_text_field( $_POST['type_key'] ?? '' );
        $fields   = self::get_fields( $type_key, false );
        wp_send_json_success( $fields );
    }

    /**
     * Add new field
     */
    public static function ajax_add_field() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $type_key    = sanitize_text_field( $_POST['type_key'] ?? '' );
        $label       = sanitize_text_field( $_POST['label'] ?? '' );
        $field_type  = sanitize_text_field( $_POST['field_type'] ?? 'text' );
        $placeholder = sanitize_text_field( $_POST['placeholder'] ?? '' );
        $is_required = ! empty( $_POST['is_required'] ) ? 1 : 0;
        $step_number = absint( $_POST['step_number'] ?? 1 );
        $options_raw = sanitize_textarea_field( $_POST['options'] ?? '' );

        if ( empty( $type_key ) || empty( $label ) ) {
            wp_send_json_error( 'برچسب فیلد الزامی است.' );
        }

        // Generate unique field_key - prefer special role if selected
        $field_role = sanitize_key( $_POST['field_role'] ?? '' );
        // تشخیص خودکار تلفن ثابت از برچسب یا نوع
        if ( ! $field_role ) {
            $hint = $label . ' ' . $field_type;
            if ( false !== strpos( $label, 'تلفن ثابت' ) || false !== strpos( $label, 'شماره ثابت' ) || false !== strpos( $label, 'تلفن‌ثابت' ) ) {
                $field_role = 'landline';
            } elseif ( $field_type === 'tel' && false !== strpos( $label, 'ثابت' ) ) {
                $field_role = 'landline';
            } elseif ( false !== strpos( $label, 'استان' ) ) {
                $field_role = 'province';
            } elseif ( false !== strpos( $label, 'شهر' ) && false === strpos( $label, 'کد' ) ) {
                $field_role = 'city';
            } elseif ( false !== strpos( $label, 'موبایل' ) || false !== strpos( $label, 'همراه' ) ) {
                $field_role = 'mobile';
            }
        }
        $special_keys = array( 'province', 'city', 'mobile', 'landline', 'area_code', 'national_id', 'email', 'full_name', 'postal_code', 'address', 'birth_date', 'id_card_front', 'id_card_back', 'website', 'person_type', 'business_type', 'business_detail', 'company_name', 'economic_code', 'national_id_company', 'bank_account', 'bank_card', 'card_name', 'bank_name', 'bank_branch', 'branch_code', 'sheba', 'account_holder', 'guarantee_type', 'check_bank', 'check_date', 'check_subject', 'check_sheba', 'check_series', 'check_serial', 'check_sayad_image', 'promissory_count', 'promissory_amount', 'promissory_date', 'promissory_serial', 'promissory_image', 'guarantee_owner', 'guarantor_name', 'guarantor_national_id', 'guarantor_mobile', 'guarantor_relation', 'guarantor_sign_status' );
        if ( $field_role && in_array( $field_role, $special_keys, true ) ) {
            $field_key = $field_role;
            // Ensure unique if already exists for this type
            global $wpdb;
            $table_check = CGS_Database::get_table( 'form_fields' );
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table_check WHERE type_key = %s AND field_key = %s",
                $type_key, $field_key
            ) );
            if ( $exists ) {
                $field_key = $field_role . '_' . time();
            }
        } else {
            $field_key = self::generate_field_key( $label, $type_key );
        }

        $options = null;
        if ( in_array( $field_type, array( 'select', 'radio', 'checkbox' ), true ) && $options_raw ) {
            $opts = array_filter( array_map( 'trim', explode( "\n", $options_raw ) ) );
            $options = wp_json_encode( array_values( $opts ) );
        }
        $max_length = absint( $_POST['max_length'] ?? 0 );
        $file_types = sanitize_text_field( $_POST['file_types'] ?? 'jpg,jpeg,png,pdf,webp' );
        $file_max_kb = absint( $_POST['file_max_kb'] ?? 2048 );
        $val_arr = array();
        if ( $max_length > 0 ) $val_arr['max_length'] = $max_length;
        $ft_save = sanitize_key( $_POST['field_type'] ?? ( $_POST['type'] ?? '' ) );
        if ( in_array( $ft_save, array( 'tel', 'number' ), true ) ) {
            $val_arr['charset'] = 'numeric';
        }
        $role = sanitize_key( $_POST['special_role'] ?? ( $_POST['role'] ?? '' ) );
        if ( in_array( $role, array( 'mobile', 'landline', 'national_id', 'area_code', 'bank_card', 'postal_code' ), true ) ) {
            $val_arr['charset'] = 'numeric';
        }
        if ( $field_type === 'file' ) {
            $val_arr['file_types'] = array_filter( array_map( 'trim', explode( ',', strtolower( $file_types ) ) ) );
            $val_arr['file_max_kb'] = max( 50, $file_max_kb );
        }
        if ( isset( $_POST['min_age'] ) && (int) $_POST['min_age'] > 0 ) {
            $val_arr['min_age'] = absint( $_POST['min_age'] );
        }
        if ( isset( $_POST['max_age'] ) && (int) $_POST['max_age'] > 0 ) {
            $val_arr['max_age'] = absint( $_POST['max_age'] );
        }

        if ( $field_type === 'table' ) {
            $val_arr['table_cols']       = max( 2, min( 12, absint( $_POST['table_cols'] ?? 3 ) ) );
            $val_arr['table_rows']       = max( 1, min( 30, absint( $_POST['table_rows'] ?? 2 ) ) );
            $val_arr['table_max_rows']   = max( 1, min( 50, absint( $_POST['table_max_rows'] ?? 10 ) ) );
            $val_arr['table_color']      = sanitize_hex_color( $_POST['table_color'] ?? '#1a237e' ) ?: '#1a237e';
            $val_arr['table_color_text'] = sanitize_hex_color( $_POST['table_color_text'] ?? '#ffffff' ) ?: '#ffffff';
            $val_arr['table_striped']    = ! empty( $_POST['table_striped'] ) ? 1 : 0;
            $val_arr['table_bordered']   = ! empty( $_POST['table_bordered'] ) ? 1 : 0;
            $val_arr['table_compact']    = ! empty( $_POST['table_compact'] ) ? 1 : 0;
            $val_arr['table_addrow']     = ! empty( $_POST['table_addrow'] ) ? 1 : 0;
            $formula = sanitize_key( $_POST['table_formula'] ?? '' );
            if ( in_array( $formula, array( 'sum', 'avg', 'count', 'min', 'max' ), true ) ) {
                $val_arr['table_formula'] = $formula;
            }
            if ( ! empty( $_POST['table_label'] ) ) {
                $val_arr['table_label'] = sanitize_text_field( $_POST['table_label'] );
            }
            if ( ! empty( $_POST['table_headers'] ) ) {
                $hdrs = array_map( 'trim', explode( ',', sanitize_text_field( $_POST['table_headers'] ) ) );
                $val_arr['table_headers'] = array_values( array_filter( $hdrs ) );
            }
        }

        
        // منطق شرطی
        if ( ! empty( $_POST['conditions'] ) ) {
            $cond_raw = wp_unslash( $_POST['conditions'] );
            $cond = is_string( $cond_raw ) ? json_decode( $cond_raw, true ) : $cond_raw;
            if ( is_array( $cond ) && ! empty( $cond['enabled'] ) ) {
                $val_arr['conditions'] = array(
                    'enabled' => true,
                    'action'  => sanitize_key( $cond['action'] ?? 'show' ),
                    'logic'   => sanitize_key( $cond['logic'] ?? 'and' ),
                    'rules'   => array(),
                );
                foreach ( (array) ( $cond['rules'] ?? array() ) as $rule ) {
                    if ( ! is_array( $rule ) ) continue;
                    $val_arr['conditions']['rules'][] = array(
                        'field' => sanitize_key( $rule['field'] ?? '' ),
                        'op'    => sanitize_key( $rule['op'] ?? 'equals' ),
                        'value' => sanitize_text_field( $rule['value'] ?? '' ),
                    );
                }
            }
        }

        $validation = ! empty( $val_arr ) ? wp_json_encode( $val_arr ) : null;

        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );

        // Get max sort_order for this type + step
        $max_order = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(sort_order) FROM $table WHERE type_key = %s AND step_number = %d",
            $type_key, $step_number
        ) );

        $inserted = $wpdb->insert( $table, array(
            'type_key'    => $type_key,
            'field_key'   => $field_key,
            'label'       => $label,
            'field_type'  => $field_type,
            'placeholder' => $placeholder,
            'options'     => $options,
            'validation'  => $validation,
            'is_required' => $is_required,
            'step_number' => $step_number,
            'sort_order'  => $max_order + 1,
            'css_class'   => sanitize_text_field( $_POST['field_width'] ?? '100' ),
            'is_active'   => 1,
        ) );

        if ( ! $inserted ) {
            wp_send_json_error( 'خطا در ذخیره فیلد.' );
        }

        $new_id = $wpdb->insert_id;
        $field  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $new_id ), ARRAY_A );

        if ( class_exists( 'CGS_Database' ) ) { CGS_Database::invalidate_fields_cache( $type_key ?? '' ); }
        wp_send_json_success( array(
            'message' => 'فیلد با موفقیت اضافه شد.',
            'field'   => $field,
        ) );
    }

    /**
     * Update existing field
     */
    public static function ajax_update_field() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $id          = absint( $_POST['id'] ?? 0 );
        $label       = sanitize_text_field( $_POST['label'] ?? '' );
        $field_type  = sanitize_text_field( $_POST['field_type'] ?? 'text' );
        $placeholder = sanitize_text_field( $_POST['placeholder'] ?? '' );
        $is_required = ! empty( $_POST['is_required'] ) ? 1 : 0;
        $step_number = absint( $_POST['step_number'] ?? 1 );
        $options_raw = sanitize_textarea_field( $_POST['options'] ?? '' );
        $max_length  = absint( $_POST['max_length'] ?? 0 );
        $validation  = $max_length > 0 ? wp_json_encode( array( 'max_length' => $max_length ) ) : null;

        if ( ! $id || empty( $label ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }

        $options = null;
        if ( in_array( $field_type, array( 'select', 'radio', 'checkbox' ), true ) && $options_raw ) {
            $opts = array_filter( array_map( 'trim', explode( "\n", $options_raw ) ) );
            $options = wp_json_encode( array_values( $opts ) );
        }
        $max_length = absint( $_POST['max_length'] ?? 0 );
        $file_types = sanitize_text_field( $_POST['file_types'] ?? 'jpg,jpeg,png,pdf,webp' );
        $file_max_kb = absint( $_POST['file_max_kb'] ?? 2048 );
        $val_arr = array();
        if ( $max_length > 0 ) $val_arr['max_length'] = $max_length;
        if ( $field_type === 'file' ) {
            $val_arr['file_types'] = array_filter( array_map( 'trim', explode( ',', strtolower( $file_types ) ) ) );
            $val_arr['file_max_kb'] = max( 50, $file_max_kb );
        }
        if ( isset( $_POST['min_age'] ) && (int) $_POST['min_age'] > 0 ) {
            $val_arr['min_age'] = absint( $_POST['min_age'] );
        }
        if ( isset( $_POST['max_age'] ) && (int) $_POST['max_age'] > 0 ) {
            $val_arr['max_age'] = absint( $_POST['max_age'] );
        }

        if ( $field_type === 'table' ) {
            $val_arr['table_cols']       = max( 2, min( 12, absint( $_POST['table_cols'] ?? 3 ) ) );
            $val_arr['table_rows']       = max( 1, min( 30, absint( $_POST['table_rows'] ?? 2 ) ) );
            $val_arr['table_max_rows']   = max( 1, min( 50, absint( $_POST['table_max_rows'] ?? 10 ) ) );
            $val_arr['table_color']      = sanitize_hex_color( $_POST['table_color'] ?? '#1a237e' ) ?: '#1a237e';
            $val_arr['table_color_text'] = sanitize_hex_color( $_POST['table_color_text'] ?? '#ffffff' ) ?: '#ffffff';
            $val_arr['table_striped']    = ! empty( $_POST['table_striped'] ) ? 1 : 0;
            $val_arr['table_bordered']   = ! empty( $_POST['table_bordered'] ) ? 1 : 0;
            $val_arr['table_compact']    = ! empty( $_POST['table_compact'] ) ? 1 : 0;
            $val_arr['table_addrow']     = ! empty( $_POST['table_addrow'] ) ? 1 : 0;
            $formula = sanitize_key( $_POST['table_formula'] ?? '' );
            if ( in_array( $formula, array( 'sum', 'avg', 'count', 'min', 'max' ), true ) ) {
                $val_arr['table_formula'] = $formula;
            }
            if ( ! empty( $_POST['table_label'] ) ) {
                $val_arr['table_label'] = sanitize_text_field( $_POST['table_label'] );
            }
            if ( ! empty( $_POST['table_headers'] ) ) {
                $hdrs = array_map( 'trim', explode( ',', sanitize_text_field( $_POST['table_headers'] ) ) );
                $val_arr['table_headers'] = array_values( array_filter( $hdrs ) );
            }
        }

        
        // منطق شرطی
        if ( ! empty( $_POST['conditions'] ) ) {
            $cond_raw = wp_unslash( $_POST['conditions'] );
            $cond = is_string( $cond_raw ) ? json_decode( $cond_raw, true ) : $cond_raw;
            if ( is_array( $cond ) && ! empty( $cond['enabled'] ) ) {
                $val_arr['conditions'] = array(
                    'enabled' => true,
                    'action'  => sanitize_key( $cond['action'] ?? 'show' ),
                    'logic'   => sanitize_key( $cond['logic'] ?? 'and' ),
                    'rules'   => array(),
                );
                foreach ( (array) ( $cond['rules'] ?? array() ) as $rule ) {
                    if ( ! is_array( $rule ) ) continue;
                    $val_arr['conditions']['rules'][] = array(
                        'field' => sanitize_key( $rule['field'] ?? '' ),
                        'op'    => sanitize_key( $rule['op'] ?? 'equals' ),
                        'value' => sanitize_text_field( $rule['value'] ?? '' ),
                    );
                }
            }
        }

        $validation = ! empty( $val_arr ) ? wp_json_encode( $val_arr ) : null;

        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );

        $update_data = array(
                'label'       => $label,
                'field_type'  => $field_type,
                'placeholder' => $placeholder,
                'options'     => $options,
                'validation'  => isset($validation) ? $validation : null,
                'is_required' => $is_required,
                'step_number' => $step_number,
                'css_class'   => sanitize_text_field( $_POST['field_width'] ?? '100' ),
            );
        // If special role selected on edit, update field_key
        $field_role = sanitize_key( $_POST['field_role'] ?? '' );
        $special_keys = array( 'province', 'city', 'mobile', 'landline', 'area_code', 'national_id', 'email', 'full_name', 'postal_code', 'address', 'birth_date', 'id_card_front', 'id_card_back', 'website', 'person_type', 'business_type', 'business_detail', 'company_name', 'economic_code', 'national_id_company', 'bank_account', 'bank_card', 'card_name', 'bank_name', 'bank_branch', 'branch_code', 'sheba', 'account_holder', 'guarantee_type', 'check_bank', 'check_date', 'check_subject', 'check_sheba', 'check_series', 'check_serial', 'check_sayad_image', 'promissory_count', 'promissory_amount', 'promissory_date', 'promissory_serial', 'promissory_image', 'guarantee_owner', 'guarantor_name', 'guarantor_national_id', 'guarantor_mobile', 'guarantor_relation', 'guarantor_sign_status' );
        if ( $field_role && in_array( $field_role, $special_keys, true ) ) {
            $update_data['field_key'] = $field_role;
        }
        $updated = $wpdb->update( $table, $update_data, array( 'id' => $id ) );

        if ( false === $updated ) {
            wp_send_json_error( 'خطا در به‌روزرسانی.' );
        }

        $field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
        if ( class_exists( 'CGS_Database' ) ) { CGS_Database::invalidate_fields_cache( $type_key ?? '' ); }
        wp_send_json_success( array(
            'message' => 'فیلد به‌روزرسانی شد.',
            'field'   => $field,
        ) );
    }

    /**
     * Delete field
     */
    public static function ajax_delete_field() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( 'شناسه نامعتبر' );
        }

        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );
        $deleted = $wpdb->delete( $table, array( 'id' => $id ) );

        if ( ! $deleted ) {
            wp_send_json_error( 'خطا در حذف فیلد.' );
        }

        if ( class_exists( 'CGS_Database' ) ) { CGS_Database::invalidate_fields_cache( $type_key ?? '' ); }
        wp_send_json_success( 'فیلد حذف شد.' );
    }

    /**
     * Generate unique field_key from label
     */
    private static function generate_field_key( $label, $type_key ) {
        // Simple transliteration for common Persian words + sanitize
        $key = sanitize_title( $label );
        if ( empty( $key ) || strlen( $key ) < 2 ) {
            $key = 'field_' . time();
        }

        // Make sure unique for this type
        global $wpdb;
        $table = CGS_Database::get_table( 'form_fields' );
        $base  = $key;
        $i     = 1;
        while ( $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE type_key = %s AND field_key = %s",
            $type_key, $key
        ) ) ) {
            $key = $base . '_' . $i;
            $i++;
        }
        return $key;
    }

    private static function can_manage() {
        return current_user_can( 'cgs_manage_forms' ) || current_user_can( 'manage_options' );
    }


    public static function ajax_save_step_meta() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! self::can_manage() ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $type_key = sanitize_key( $_POST['type_key'] ?? '' );
        if ( ! $type_key ) {
            wp_send_json_error( 'نوع فرم نامعتبر است' );
        }
        // پشتیبانی از JSON string (ارسال امن‌تر از JS)
        $meta = isset( $_POST['meta'] ) ? $_POST['meta'] : array();
        if ( is_string( $meta ) ) {
            $decoded = json_decode( wp_unslash( $meta ), true );
            if ( is_array( $decoded ) ) {
                $meta = $decoded;
            }
        }
        if ( ! is_array( $meta ) ) {
            wp_send_json_error( 'داده مراحل نامعتبر است' );
        }
        $clean = array();
        foreach ( $meta as $step => $data ) {
            if ( ! is_array( $data ) ) {
                continue;
            }
            $sn = absint( $step );
            if ( $sn < 1 || $sn > 20 ) {
                continue;
            }
            $files = array();
            if ( ! empty( $data['files'] ) && is_array( $data['files'] ) ) {
                foreach ( $data['files'] as $f ) {
                    if ( ! is_array( $f ) ) {
                        continue;
                    }
                    $url = esc_url_raw( $f['url'] ?? '' );
                    if ( ! $url ) {
                        continue;
                    }
                    $files[] = array(
                        'url'  => $url,
                        'type' => sanitize_key( $f['type'] ?? 'image' ),
                    );
                }
            }
            // سازگاری با فیلد image قدیمی
            if ( empty( $files ) && ! empty( $data['image'] ) ) {
                $files[] = array( 'url' => esc_url_raw( $data['image'] ), 'type' => 'image' );
            }
            $clean[ $sn ] = array(
                'name'     => sanitize_text_field( $data['name'] ?? '' ),
                'icon'     => sanitize_key( $data['icon'] ?? '' ),
                'icon_url' => esc_url_raw( $data['icon_url'] ?? '' ),
                'columns'  => max( 1, min( 6, absint( $data['columns'] ?? 2 ) ) ),
                'files'    => $files,
                'image'    => ! empty( $files[0]['url'] ) ? $files[0]['url'] : '',
            );
        }
        update_option( 'cgs_step_meta_' . $type_key, $clean, false );
        if ( class_exists( 'CGS_Database' ) ) {
            CGS_Database::invalidate_fields_cache( $type_key );
        }
        wp_send_json_success( array(
            'message' => 'مراحل ذخیره شد',
            'meta'    => $clean,
        ) );
    }

    /**
     * Available field types
     */
    public static function get_field_types() {
        return array(
            'text'     => 'متن کوتاه',
            'textarea' => 'متن بلند',
            'email'    => 'ایمیل',
            'url'      => 'نشانی اینترنتی (URL)',
            'tel'      => 'تلفن / موبایل',
            'number'   => 'عدد',
            'select'   => 'لیست کشویی',
            'radio'    => 'دکمه‌های رادیویی',
            'checkbox' => 'چک‌باکس',
            'file'     => 'آپلود فایل',
            'date'     => 'تاریخ',
            'divider'  => 'فاصله / بخش‌بندی (عنوان اختیاری)',
            'table'    => 'ماتریس داده — ستون / ردیف / رنگ / محاسبه',
        );
    }

    public static function ajax_save_layout() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_forms' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'cgs_form_fields';
        $items = isset( $_POST['items'] ) ? (array) $_POST['items'] : array();
        if ( empty( $items ) ) {
            wp_send_json_error( 'آیتمی نیست' );
        }
        foreach ( $items as $it ) {
            $id = absint( $it['id'] ?? 0 );
            if ( ! $id ) continue;
            $width = sanitize_text_field( $it['width'] ?? '100' );
            // عرض آزاد ۱۵–۱۰۰٪ (نه فقط مقادیر ثابت)
            if ( is_numeric( $width ) ) {
                $width = (string) max( 15, min( 100, (int) $width ) );
            } elseif ( ! in_array( $width, array( '25', '33', '50', '100' ), true ) ) {
                $width = '100';
            }
            $order = absint( $it['sort_order'] ?? 0 );
            $wpdb->update(
                $table,
                array(
                    'css_class'  => $width,
                    'sort_order' => $order,
                ),
                array( 'id' => $id ),
                array( '%s', '%d' ),
                array( '%d' )
            );
        }
        $type_key = sanitize_key( $_POST['type_key'] ?? '' );
        if ( class_exists( 'CGS_Database' ) ) {
            CGS_Database::invalidate_fields_cache( $type_key );
        }
        wp_send_json_success( array( 'saved' => count( $items ), 'message' => 'چیدمان ذخیره شد' ) );
    }

}
