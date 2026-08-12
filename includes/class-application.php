<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Application {

    private static function validate_national_id( $code ) {
        $code = preg_replace( '/\D/', '', $code );
        if ( ! preg_match( '/^\d{10}$/', $code ) ) return false;
        if ( preg_match( '/^(\d)\1{9}$/', $code ) ) return false;
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum += intval( $code[ $i ] ) * ( 10 - $i );
        }
        $r = $sum % 11;
        $check = intval( $code[ 9 ] );
        return ( $r < 2 && $check === $r ) || ( $r >= 2 && $check === ( 11 - $r ) );
    }

    private static function validate_sheba( $sheba ) {
        $sheba = strtoupper( preg_replace( '/\s+/', '', $sheba ) );
        if ( strpos( $sheba, 'IR' ) === 0 ) {
            $sheba = substr( $sheba, 2 );
        }
        if ( ! preg_match( '/^\d{24}$/', $sheba ) ) return false;
        $rearranged = $sheba . '1827';
        $remainder = 0;
        $len = strlen( $rearranged );
        for ( $i = 0; $i < $len; $i++ ) {
            $remainder = ( $remainder * 10 + intval( $rearranged[ $i ] ) ) % 97;
        }
        return $remainder === 1;
    }


    public static function init() {
        add_action( 'wp_ajax_cgs_submit_application', array( __CLASS__, 'ajax_submit' ) );
        add_action( 'wp_ajax_nopriv_cgs_submit_application', array( __CLASS__, 'ajax_submit' ) );
        add_action( 'wp_ajax_cgs_update_status', array( __CLASS__, 'ajax_update_status' ) );
        add_action( 'wp_ajax_cgs_get_application_detail', array( __CLASS__, 'ajax_get_detail' ) );
        add_action( 'wp_ajax_cgs_download_application_file', array( __CLASS__, 'ajax_download_file' ) );
    }

    public static function ajax_submit() {
        // Honeypot: bots fill this hidden field
        if ( ! empty( $_POST['cgs_hp_email'] ) ) {
            wp_send_json_success( array( 'message' => 'درخواست شما ثبت شد.' ) ); // silent fake success
        }

        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );

        $type_key = sanitize_text_field( $_POST['type_key'] ?? '' );
        if ( ! cgs_get_application_type( $type_key ) ) {
            wp_send_json_error( 'نوع درخواست نامعتبر است.' );
        }

        $fields = CGS_Form_Builder::get_fields( $type_key );
        if ( empty( $fields ) ) {
            wp_send_json_error( 'فرمی برای این بخش تعریف نشده است.' );
        }

        // Collect & validate data
        $data = array();
        $files_to_process = array();

        foreach ( $fields as $field ) {
            $key = $field['field_key'];

            if ( $field['field_type'] === 'file' ) {
                if ( ! empty( $_FILES[ $key ] ) && $_FILES[ $key ]['error'] === UPLOAD_ERR_OK ) {
                    $files_to_process[ $key ] = $_FILES[ $key ];
                } elseif ( $field['is_required'] ) {
                    wp_send_json_error( 'فیلد «' . $field['label'] . '» الزامی است.' );
                }
                continue;
            }

            $value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';

            if ( $field['is_required'] && empty( $value ) ) {
                wp_send_json_error( 'فیلد «' . $field['label'] . '» الزامی است.' );
            }

            // Basic sanitization by type
            switch ( $field['field_type'] ) {
                case 'email':
                    $value = sanitize_email( $value );
                    break;
                case 'tel':
                    $value = cgs_sanitize_phone( $value );
                    break;
                case 'number':
                    $value = floatval( $value );
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field( $value );
                    break;
                default:
                    $value = sanitize_text_field( $value );
            }

            $data[ $key ] = $value;
        }

        // Validate national_id and sheba if present
        if ( ! empty( $data['national_id'] ) && ! self::validate_national_id( $data['national_id'] ) ) {
            wp_send_json_error( 'کد ملی واردشده معتبر نیست.' );
        }
        foreach ( array( 'sheba', 'check_sheba' ) as $sk ) {
            if ( ! empty( $data[ $sk ] ) && ! self::validate_sheba( $data[ $sk ] ) ) {
                wp_send_json_error( 'شماره شبا معتبر نیست.' );
            }
        }
        if ( ! empty( $data['bank_card'] ) ) {
            $card = preg_replace( '/\D/', '', $data['bank_card'] );
            if ( strlen( $card ) !== 16 ) {
                wp_send_json_error( 'شماره کارت باید ۱۶ رقم باشد.' );
            }
        }

        // Create application
        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );

        $code = cgs_generate_code( strtoupper( substr( $type_key, 0, 3 ) ) );

        $insert = array(
            'code'       => $code,
            'type_key'   => $type_key,
            'status'     => 'pending',
            'mobile'     => $data['mobile'] ?? '',
            'email'      => $data['email'] ?? '',
            'full_name'  => $data['full_name'] ?? '',
            'city'       => $data['city'] ?? '',
            'province'   => $data['province'] ?? '',
            'created_at' => current_time( 'mysql' ),
        );

        $wpdb->insert( $table, $insert );
        $app_id = $wpdb->insert_id;

        if ( ! $app_id ) {
            wp_send_json_error( 'خطا در ثبت درخواست. لطفاً دوباره تلاش کنید.' );
        }

        // Save meta
        $meta_table = CGS_Database::get_table( 'application_meta' );
        foreach ( $data as $key => $value ) {
            $wpdb->insert( $meta_table, array(
                'application_id' => $app_id,
                'meta_key'       => $key,
                'meta_value'     => $value,
            ) );
        }

        // Handle file uploads
        if ( ! empty( $files_to_process ) ) {
            $upload_dir = CGS_Security::get_upload_dir();
            $files_table = CGS_Database::get_table( 'files' );

            foreach ( $files_to_process as $field_key => $file ) {
                // Get allowed types and max size from field definition
                $allowed = array( 'jpg', 'jpeg', 'png', 'pdf', 'webp' );
                $max_kb = 2048;
                foreach ( $fields as $fdef ) {
                    if ( $fdef['field_key'] === $field_key && ! empty( $fdef['validation'] ) ) {
                        $vd = json_decode( $fdef['validation'], true );
                        if ( ! empty( $vd['file_types'] ) ) $allowed = $vd['file_types'];
                        if ( ! empty( $vd['file_max_kb'] ) ) $max_kb = (int) $vd['file_max_kb'];
                        break;
                    }
                }
                $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
                if ( ! in_array( $ext, $allowed, true ) ) {
                    wp_send_json_error( 'فرمت فایل «' . $field_key . '» مجاز نیست.' );
                }
                if ( $file['size'] > $max_kb * 1024 ) {
                    wp_send_json_error( 'حجم فایل «' . $field_key . '» بیشتر از حد مجاز است.' );
                }
                // بررسی نوع واقعی محتوا (magic bytes) — دفاع در عمق در برابر فایل با پسوند جعلی
                $ext_to_mime = array(
                    'jpg'  => array( 'image/jpeg' ),
                    'jpeg' => array( 'image/jpeg' ),
                    'png'  => array( 'image/png' ),
                    'webp' => array( 'image/webp' ),
                    'pdf'  => array( 'application/pdf' ),
                );
                if ( isset( $ext_to_mime[ $ext ] ) && function_exists( 'finfo_open' ) ) {
                    $finfo = finfo_open( FILEINFO_MIME_TYPE );
                    $real_mime = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : false;
                    if ( $finfo ) { finfo_close( $finfo ); }
                    if ( $real_mime && ! in_array( $real_mime, $ext_to_mime[ $ext ], true ) ) {
                        wp_send_json_error( 'محتوای فایل «' . $field_key . '» با فرمت اعلام‌شده همخوانی ندارد.' );
                    }
                }

                $new_name = $app_id . '_' . $field_key . '_' . time() . '.' . $ext;
                $target   = $upload_dir . '/' . $new_name;

                if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
                    $wpdb->insert( $files_table, array(
                        'application_id' => $app_id,
                        'field_key'      => $field_key,
                        'file_name'      => sanitize_file_name( $file['name'] ),
                        'file_path'      => $new_name,
                        'file_type'      => $file['type'],
                        'file_size'      => $file['size'],
                    ) );
                }
            }
        }

        do_action( 'cgs_application_submitted', $app_id, $type_key, $data );

        if ( class_exists( 'CGS_Database' ) ) { CGS_Database::invalidate_app_counts(); }
        wp_send_json_success( array(
            'message' => 'درخواست شما با موفقیت ثبت شد. کد پیگیری: ' . $code,
            'code'    => $code,
            'id'      => $app_id,
        ) );
    }

    public static function ajax_update_status() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $app_id = absint( $_POST['application_id'] ?? 0 );
        $status = sanitize_text_field( $_POST['status'] ?? '' );

        $allowed = array( 'pending', 'approved', 'rejected', 'review' );
        if ( ! $app_id || ! in_array( $status, $allowed, true ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }

        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        $old_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $table WHERE id = %d", $app_id ) );

        $updated = $wpdb->update(
            $table,
            array(
                'status'      => $status,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $app_id )
        );

        if ( false === $updated ) {
            wp_send_json_error( 'خطا در به‌روزرسانی' );
        }

        // رفع باگ: این رویداد قبلاً هرگز فراخوانی نمی‌شد، بنابراین همگام‌سازی خودکار CRM
        // (class-crm.php::on_application_status) هیچ‌گاه اجرا نمی‌شد.
        do_action( 'cgs_application_status_changed', $app_id, $old_status, $status );

        // On approve → create user if not exists + send SMS
        if ( $status === 'approved' ) {
            self::handle_approval( $app_id );
        }

        if ( $status === 'rejected' ) {
            do_action( 'cgs_application_rejected', $app_id );
        }

        wp_send_json_success( 'وضعیت با موفقیت تغییر کرد.' );
    }

    private static function handle_approval( $app_id ) {
        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        $app   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $app_id ) );

        if ( ! $app ) {
            return;
        }

        // Create user if mobile exists and no user yet
        if ( empty( $app->user_id ) && ! empty( $app->mobile ) ) {
            $username = 'cg_' . $app->mobile;
            $email    = ! empty( $app->email ) ? $app->email : $app->mobile . '@city-ghest.local';

            if ( ! username_exists( $username ) && ! email_exists( $email ) ) {
                $password = wp_generate_password( 12, true );
                $user_id  = wp_create_user( $username, $password, $email );

                if ( ! is_wp_error( $user_id ) ) {
                    $user = new WP_User( $user_id );
                    $user->set_role( 'cg_member' );

                    // Update display name
                    wp_update_user( array(
                        'ID'           => $user_id,
                        'display_name' => $app->full_name ?: $username,
                        'first_name'   => $app->full_name,
                    ) );

                    // Link to application
                    $wpdb->update( $table, array( 'user_id' => $user_id ), array( 'id' => $app_id ) );

                    // Store password temporarily for SMS (or send reset link)
                    update_user_meta( $user_id, 'cgs_temp_pass', $password );
                    update_user_meta( $user_id, 'cgs_application_id', $app_id );

                    do_action( 'cgs_member_created', $user_id, $app_id, $password );
                }
            }
        }

        do_action( 'cgs_application_approved', $app_id );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = CGS_Database::get_table( 'applications' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
    }

    public static function get_meta( $application_id ) {
        global $wpdb;
        $table = CGS_Database::get_table( 'application_meta' );
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $table WHERE application_id = %d",
            $application_id
        ), ARRAY_A );

        $meta = array();
        foreach ( $rows as $row ) {
            $meta[ $row['meta_key'] ] = $row['meta_value'];
        }
        return $meta;
    }

    /**
     * جزئیات کامل یک درخواست برای نمایش در پنل ادمین (فیلدهای فرم + فایل‌های ضمیمه)
     * رفع باگ: قبلاً هیچ endpoint ای برای دیدن form_data/فایل‌های آپلودی وجود نداشت.
     */
    public static function ajax_get_detail() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }

        $app_id = absint( $_POST['application_id'] ?? 0 );
        if ( ! $app_id ) {
            wp_send_json_error( 'شناسه نامعتبر' );
        }

        $app = self::get( $app_id );
        if ( ! $app ) {
            wp_send_json_error( 'درخواست یافت نشد.' );
        }

        $meta   = self::get_meta( $app_id );
        $fields = class_exists( 'CGS_Form_Builder' ) ? CGS_Form_Builder::get_fields( $app->type_key, false ) : array();
        $labels = array();
        foreach ( $fields as $f ) {
            $labels[ $f['field_key'] ] = $f['label'] ?? $f['field_key'];
        }

        $meta_out = array();
        foreach ( $meta as $key => $value ) {
            $meta_out[] = array(
                'key'   => $key,
                'label' => $labels[ $key ] ?? $key,
                'value' => $value,
            );
        }

        global $wpdb;
        $files_table = CGS_Database::get_table( 'files' );
        $files = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, field_key, file_name, file_type, file_size, uploaded_at FROM $files_table WHERE application_id = %d ORDER BY id ASC",
            $app_id
        ), ARRAY_A );

        $files_out = array();
        foreach ( (array) $files as $f ) {
            $files_out[] = array(
                'id'          => (int) $f['id'],
                'label'       => $labels[ $f['field_key'] ] ?? $f['field_key'],
                'file_name'   => $f['file_name'],
                'file_type'   => $f['file_type'],
                'file_size'   => (int) $f['file_size'],
                'uploaded_at' => $f['uploaded_at'],
                'download_url'=> add_query_arg( array(
                    'action'         => 'cgs_download_application_file',
                    'file_id'        => (int) $f['id'],
                    'application_id' => $app_id,
                    'nonce'          => wp_create_nonce( 'cgs_admin_nonce' ),
                ), admin_url( 'admin-ajax.php' ) ),
            );
        }

        wp_send_json_success( array(
            'application' => array(
                'id'         => (int) $app->id,
                'code'       => $app->code,
                'type_key'   => $app->type_key,
                'status'     => $app->status,
                'full_name'  => $app->full_name,
                'mobile'     => $app->mobile,
                'email'      => $app->email,
                'city'       => $app->city,
                'province'   => $app->province,
                'created_at' => $app->created_at,
            ),
            'meta'  => $meta_out,
            'files' => $files_out,
        ) );
    }

    /**
     * دانلود ایمن یک فایل ضمیمهٔ درخواست — فقط با دسترسی مدیریتی.
     * مسیر فایل همیشه از روی رکورد دیتابیس (نه ورودی مستقیم کاربر) ساخته می‌شود
     * تا مسیر عبور از دایرکتوری (path traversal) ممکن نباشد.
     */
    public static function ajax_download_file() {
        if ( ! check_ajax_referer( 'cgs_admin_nonce', 'nonce', false ) ) {
            wp_die( 'درخواست نامعتبر.', 'خطا', array( 'response' => 403 ) );
        }
        if ( ! current_user_can( 'cgs_manage_applications' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز.', 'خطا', array( 'response' => 403 ) );
        }

        $file_id = absint( $_GET['file_id'] ?? 0 );
        $app_id  = absint( $_GET['application_id'] ?? 0 );
        if ( ! $file_id || ! $app_id ) {
            wp_die( 'درخواست نامعتبر.', 'خطا', array( 'response' => 400 ) );
        }

        global $wpdb;
        $files_table = CGS_Database::get_table( 'files' );
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $files_table WHERE id = %d AND application_id = %d",
            $file_id,
            $app_id
        ), ARRAY_A );

        if ( ! $row ) {
            wp_die( 'فایل یافت نشد.', 'خطا', array( 'response' => 404 ) );
        }

        $upload_dir = CGS_Security::get_upload_dir();
        // basename() جلوگیری می‌کند از استفاده از ../ حتی اگر رکورد دیتابیس دستکاری شود
        $path = $upload_dir . '/' . basename( $row['file_path'] );
        $real_upload_dir = realpath( $upload_dir );
        $real_path = realpath( $path );
        if ( ! $real_path || ! $real_upload_dir || strpos( $real_path, $real_upload_dir ) !== 0 || ! is_file( $real_path ) ) {
            wp_die( 'فایل روی سرور موجود نیست.', 'خطا', array( 'response' => 404 ) );
        }

        $mime = ! empty( $row['file_type'] ) ? $row['file_type'] : 'application/octet-stream';
        nocache_headers();
        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: inline; filename="' . rawurlencode( sanitize_file_name( $row['file_name'] ) ) . '"' );
        header( 'Content-Length: ' . filesize( $real_path ) );
        header( 'X-Content-Type-Options: nosniff' );
        readfile( $real_path );
        exit;
    }
}
