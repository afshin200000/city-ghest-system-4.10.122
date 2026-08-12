<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Form_Templates {
    public static function get_all() {
        if ( method_exists( __CLASS__, 'get_templates' ) ) {
            return self::get_templates();
        }
        global $wpdb;
        $table = $wpdb->prefix . 'cgs_form_templates';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return array();
        }
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ) ?: array();
    }


    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'cgs_form_templates';
    }

    public static function init() {
        add_action( 'wp_ajax_cgs_apply_template', array( __CLASS__, 'ajax_apply' ) );
        add_action( 'wp_ajax_cgs_save_template', array( __CLASS__, 'ajax_save_current' ) );
        add_action( 'wp_ajax_cgs_delete_template', array( __CLASS__, 'ajax_delete' ) );
        add_action( 'wp_ajax_cgs_list_templates', array( __CLASS__, 'ajax_list' ) );
        add_action( 'wp_ajax_cgs_template_versions', array( __CLASS__, 'ajax_versions' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_seed' ), 20 );
    }

    public static function ensure_table() {
        global $wpdb;
        $table = self::table();
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table && class_exists( 'CGS_Database' ) ) {
            CGS_Database::create_tables();
        }
        // migrate version columns if missing
        $cols = $wpdb->get_col( "DESCRIBE {$table}", 0 );
        if ( is_array( $cols ) && ! in_array( 'version_num', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD version_num int(11) NOT NULL DEFAULT 1, ADD parent_id bigint(20) unsigned NOT NULL DEFAULT 0, ADD notes varchar(255) DEFAULT ''" );
        }
    }

    public static function maybe_seed() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        self::ensure_table();
        global $wpdb;
        $count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE is_active = 1' );
        if ( $count < 80 ) {
            // پاکسازی پیش‌فرض‌های قدیمی و بارگذاری ۱۰۰ قالب واقعی
            $wpdb->query( "DELETE FROM " . self::table() . " WHERE source = 'builtin'" );
            self::seed_from_json();
        }
    }

    public static function seed_from_json() {
        $file = CGS_PLUGIN_DIR . 'includes/data/form-templates.json';
        if ( ! file_exists( $file ) ) {
            return 0;
        }
        $data = json_decode( file_get_contents( $file ), true );
        if ( ! is_array( $data ) ) {
            return 0;
        }
        $n = 0;
        foreach ( $data as $tpl ) {
            self::insert_template( array(
                'name'            => $tpl['name'] ?? ( 'قالب ' . ( $n + 1 ) ),
                'type_key'        => $tpl['type'] ?? 'representative',
                'source'          => 'builtin',
                'fields_json'     => wp_json_encode( $tpl['fields'] ?? array() ),
                'styles_json'     => wp_json_encode( $tpl['styles'] ?? array() ),
                'step_meta_json'  => wp_json_encode( array() ),
                'columns_default' => (int) ( $tpl['columns'] ?? 2 ),
                'is_active'       => 1,
                'version_num'     => 1,
                'parent_id'       => 0,
            ) );
            $n++;
        }
        // قالب‌های سیستمی ماندگار: خالی / شروع‌کننده / پیش‌فرض
        global $wpdb;
        $system_tpls = array(
            array(
                'name'            => 'قالب خالی (Blank)',
                'type_key'        => 'applicant',
                'source'          => 'builtin',
                'fields_json'     => '[]',
                'styles_json'     => '{}',
                'step_meta_json'  => '{}',
                'columns_default' => 2,
                'notes'           => 'blank',
            ),
            array(
                'name'            => 'قالب شروع‌کننده (Starter)',
                'type_key'        => 'applicant',
                'source'          => 'builtin',
                'fields_json'     => wp_json_encode( array(
                    array( 'field_key' => 'full_name', 'label' => 'نام و نام خانوادگی', 'type' => 'text', 'required' => 1, 'step' => 1, 'width' => 50 ),
                    array( 'field_key' => 'mobile', 'label' => 'موبایل', 'type' => 'tel', 'required' => 1, 'step' => 1, 'width' => 50 ),
                    array( 'field_key' => 'national_code', 'label' => 'کد ملی', 'type' => 'text', 'required' => 1, 'step' => 1, 'width' => 50 ),
                ) ),
                'styles_json'     => '{}',
                'step_meta_json'  => wp_json_encode( array( 1 => array( 'name' => 'اطلاعات پایه', 'icon' => 'user', 'columns' => 2 ) ) ),
                'columns_default' => 2,
                'notes'           => 'starter',
            ),
            array(
                'name'            => 'قالب پیش‌فرض عمومی',
                'type_key'        => 'credit',
                'source'          => 'builtin',
                'fields_json'     => '[]',
                'styles_json'     => '{}',
                'step_meta_json'  => '{}',
                'columns_default' => 2,
                'notes'           => 'default',
            ),
        );
        foreach ( $system_tpls as $st ) {
            $exists = (int) $wpdb->get_var( $wpdb->prepare(
                'SELECT id FROM ' . self::table() . ' WHERE name = %s LIMIT 1',
                $st['name']
            ) );
            if ( ! $exists ) {
                $st['is_active']   = 1;
                $st['version_num'] = 1;
                $st['parent_id']   = 0;
                self::insert_template( $st );
                $n++;
            }
        }
        return $n;
    }

    public static function insert_template( $row ) {
        global $wpdb;
        self::ensure_table();
        $data = array(
            'name'            => sanitize_text_field( $row['name'] ?? '' ),
            'type_key'        => sanitize_key( $row['type_key'] ?? 'representative' ),
            'source'          => in_array( $row['source'] ?? '', array( 'builtin', 'custom' ), true ) ? $row['source'] : 'custom',
            'fields_json'     => isset( $row['fields_json'] ) ? $row['fields_json'] : '[]',
            'styles_json'     => isset( $row['styles_json'] ) ? $row['styles_json'] : '{}',
            'step_meta_json'  => isset( $row['step_meta_json'] ) ? $row['step_meta_json'] : '{}',
            'columns_default' => max( 1, min( 6, (int) ( $row['columns_default'] ?? 2 ) ) ),
            'is_active'       => isset( $row['is_active'] ) ? (int) $row['is_active'] : 1,
            'version_num'     => max( 1, (int) ( $row['version_num'] ?? 1 ) ),
            'parent_id'       => absint( $row['parent_id'] ?? 0 ),
            'notes'           => sanitize_text_field( $row['notes'] ?? '' ),
        );
        $wpdb->insert( self::table(), $data );
        return (int) $wpdb->insert_id;
    }

    /**
     * Optimized list: only id, name, source, type, version — no longtext
     */
    public static function all( $args = array() ) {
        global $wpdb;
        self::ensure_table();
        $table = self::table();
        $where = array( 'is_active = 1' );
        $params = array();
        if ( ! empty( $args['source'] ) ) {
            $where[] = 'source = %s';
            $params[] = $args['source'];
        }
        if ( ! empty( $args['type_key'] ) ) {
            $where[] = 'type_key = %s';
            $params[] = $args['type_key'];
        }
        $sql = 'SELECT id, name, type_key, source, version_num, parent_id, columns_default, updated_at FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY source ASC, name ASC, version_num DESC';
        if ( $params ) {
            $sql = $wpdb->prepare( $sql, $params );
        }
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        return is_array( $rows ) ? $rows : array();
    }

    public static function get( $id ) {
        global $wpdb;
        self::ensure_table();
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', absint( $id ) ), ARRAY_A );
    }

    
    public static function ajax_versions() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $id = absint( $_POST['id'] ?? 0 );
        $row = self::get( $id );
        if ( ! $row ) {
            wp_send_json_error( 'قالب یافت نشد' );
        }
        $root = ! empty( $row['parent_id'] ) ? (int) $row['parent_id'] : (int) $row['id'];
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name, version_num, source, type_key, updated_at, parent_id
             FROM {$table}
             WHERE is_active = 1 AND (id = %d OR parent_id = %d)
             ORDER BY version_num DESC",
            $root, $root
        ), ARRAY_A );
        wp_send_json_success( array( 'root' => $root, 'versions' => $rows ?: array() ) );
    }

    public static function ajax_list() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        wp_send_json_success( array( 'templates' => self::all() ) );
    }

    public static function ajax_save_current() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $type     = sanitize_key( $_POST['type_key'] ?? 'representative' );
        $parent   = absint( $_POST['parent_id'] ?? 0 );
        $as_new_v = ! empty( $_POST['as_new_version'] );
        if ( $name === '' ) {
            wp_send_json_error( 'نام قالب الزامی است.' );
        }

        global $wpdb;
        $ftable = $wpdb->prefix . 'cgs_form_fields';
        $fields = $wpdb->get_results( $wpdb->prepare(
            "SELECT field_key, label, field_type, placeholder, is_required, options, validation, step_number, sort_order, css_class
             FROM {$ftable} WHERE type_key = %s ORDER BY step_number ASC, sort_order ASC",
            $type
        ), ARRAY_A );

        $normalized = array();
        foreach ( (array) $fields as $f ) {
            $val = json_decode( $f['validation'] ?? '', true );
            $normalized[] = array(
                'key'         => $f['field_key'],
                'label'       => $f['label'],
                'type'        => $f['field_type'],
                'required'    => (int) $f['is_required'],
                'step'        => (int) $f['step_number'],
                'width'       => $f['css_class'] ?: '100',
                'maxlen'      => isset( $val['max_length'] ) ? (int) $val['max_length'] : 0,
                'min_age'     => isset( $val['min_age'] ) ? (int) $val['min_age'] : 0,
                'max_age'     => isset( $val['max_age'] ) ? (int) $val['max_age'] : 0,
                'placeholder' => $f['placeholder'] ?? '',
            );
        }
        $styles = class_exists( 'CGS_Form_Styles' ) ? CGS_Form_Styles::get( $type ) : array();
        $step_meta = get_option( 'cgs_step_meta_' . $type, array() );
        $cols = 2;
        if ( is_array( $step_meta ) ) {
            foreach ( $step_meta as $sm ) {
                if ( ! empty( $sm['columns'] ) ) {
                    $cols = (int) $sm['columns'];
                    break;
                }
            }
        }

        $version   = 1;
        $parent_id = 0;
        $update_id = absint( $_POST['template_id'] ?? 0 );

        // JSON نرمال‌شده با اسکیمای یکپارچه v2
        $payload = array(
            'schema'   => 'cgs.form_template.v2',
            'type_key' => $type,
            'fields'   => $normalized,
            'styles'   => $styles,
            'steps'    => is_array( $step_meta ) ? $step_meta : new \stdClass(),
            'columns'  => $cols,
            'updated'  => current_time( 'c' ),
        );

        // --- به‌روزرسانی همان قالب (ویرایش و ذخیره با همان نام) ---
        if ( ! $as_new_v ) {
            // 1) اگر template_id مشخص باشد و سفارشی باشد → UPDATE
            if ( $update_id > 0 ) {
                $existing = self::get( $update_id );
                if ( $existing && ( $existing['source'] ?? '' ) === 'custom' ) {
                    $wpdb->update(
                        self::table(),
                        array(
                            'name'            => $name,
                            'type_key'        => $type,
                            'fields_json'     => wp_json_encode( $normalized ),
                            'styles_json'     => wp_json_encode( $styles ),
                            'step_meta_json'  => wp_json_encode( $step_meta ),
                            'columns_default' => $cols,
                            'notes'           => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                            'updated_at'      => current_time( 'mysql' ),
                        ),
                        array( 'id' => $update_id ),
                        array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
                        array( '%d' )
                    );
                    wp_send_json_success( array(
                        'id'       => $update_id,
                        'version'  => (int) ( $existing['version_num'] ?? 1 ),
                        'updated'  => true,
                        'message'  => 'قالب «' . $name . '» به‌روزرسانی شد.',
                        'payload'  => $payload,
                    ) );
                }
            }
            // 2) اگر قالبی با همین نام و type و source=custom وجود دارد → UPDATE
            $same = $wpdb->get_row( $wpdb->prepare(
                'SELECT id, version_num FROM ' . self::table() . ' WHERE name = %s AND type_key = %s AND source = %s AND is_active = 1 LIMIT 1',
                $name, $type, 'custom'
            ), ARRAY_A );
            if ( $same ) {
                $sid = (int) $same['id'];
                $wpdb->update(
                    self::table(),
                    array(
                        'fields_json'     => wp_json_encode( $normalized ),
                        'styles_json'     => wp_json_encode( $styles ),
                        'step_meta_json'  => wp_json_encode( $step_meta ),
                        'columns_default' => $cols,
                        'notes'           => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                        'updated_at'      => current_time( 'mysql' ),
                    ),
                    array( 'id' => $sid ),
                    array( '%s', '%s', '%s', '%d', '%s', '%s' ),
                    array( '%d' )
                );
                wp_send_json_success( array(
                    'id'      => $sid,
                    'version' => (int) ( $same['version_num'] ?? 1 ),
                    'updated' => true,
                    'message' => 'قالب «' . $name . '» با همان نام به‌روزرسانی شد.',
                    'payload' => $payload,
                ) );
            }
        }

        // --- نسخه جدید از قالب انتخاب‌شده ---
        if ( $as_new_v && $parent > 0 ) {
            $parent_row = self::get( $parent );
            if ( $parent_row ) {
                $root = ! empty( $parent_row['parent_id'] ) ? (int) $parent_row['parent_id'] : (int) $parent_row['id'];
                $parent_id = $root;
                $max_v = (int) $wpdb->get_var( $wpdb->prepare(
                    'SELECT MAX(version_num) FROM ' . self::table() . ' WHERE id = %d OR parent_id = %d',
                    $root, $root
                ) );
                $version = $max_v + 1;
                if ( $name === ( $parent_row['name'] ?? '' ) ) {
                    $name = $parent_row['name'] . ' v' . $version;
                }
            }
        }

        $id = self::insert_template( array(
            'name'            => $name,
            'type_key'        => $type,
            'source'          => 'custom',
            'fields_json'     => wp_json_encode( $normalized ),
            'styles_json'     => wp_json_encode( $styles ),
            'step_meta_json'  => wp_json_encode( $step_meta ),
            'columns_default' => $cols,
            'is_active'       => 1,
            'version_num'     => $version,
            'parent_id'       => $parent_id,
            'notes'           => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ),
        ) );

        if ( ! $id ) {
            wp_send_json_error( 'خطا در ذخیره: ' . $wpdb->last_error );
        }
        wp_send_json_success( array(
            'id'      => $id,
            'version' => $version,
            'updated' => false,
            'message' => 'قالب «' . $name . '» ذخیره شد (نسخه ' . $version . ').',
            'payload' => $payload,
        ) );
    }

    public static function ajax_delete() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $id = absint( $_POST['id'] ?? 0 );
        $row = self::get( $id );
        if ( ! $row ) {
            wp_send_json_error( 'قالب یافت نشد.' );
        }
        global $wpdb;
        if ( ( $row['source'] ?? '' ) === 'builtin' ) {
            $wpdb->update( self::table(), array( 'is_active' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
        } else {
            $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
        }
        wp_send_json_success( array( 'message' => 'قالب حذف شد.' ) );
    }

    public static function ajax_apply() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $id   = absint( $_POST['template_id'] ?? 0 );
        $type = sanitize_key( $_POST['type_key'] ?? 'representative' );
        if ( ! $id ) {
            wp_send_json_error( 'قالب انتخاب نشده است.' );
        }
        $tpl = self::get( $id );
        if ( ! $tpl ) {
            wp_send_json_error( 'قالب در دیتابیس یافت نشد. یک‌بار افزونه را غیرفعال/فعال کنید.' );
        }
        $fields = json_decode( $tpl['fields_json'] ?? '[]', true );
        $styles = json_decode( $tpl['styles_json'] ?? '{}', true );
        $meta   = json_decode( $tpl['step_meta_json'] ?? '{}', true );
        if ( ! is_array( $fields ) ) {
            $fields = array();
        }
        if ( empty( $fields ) ) {
            wp_send_json_error( 'این قالب فیلدی ندارد.' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cgs_form_fields';
        if ( ! empty( $_POST['replace'] ) ) {
            $wpdb->delete( $table, array( 'type_key' => $type ), array( '%s' ) );
        }
        $order = 0;
        foreach ( $fields as $f ) {
            $order++;
            $val = array();
            if ( ! empty( $f['maxlen'] ) ) {
                $val['max_length'] = (int) $f['maxlen'];
            }
            if ( ! empty( $f['min_age'] ) ) {
                $val['min_age'] = (int) $f['min_age'];
            }
            if ( ! empty( $f['max_age'] ) ) {
                $val['max_age'] = (int) $f['max_age'];
            }
            $wpdb->insert(
                $table,
                array(
                    'type_key'    => $type,
                    'field_key'   => sanitize_key( $f['key'] ?? ( 'field_' . $order ) ),
                    'label'       => sanitize_text_field( $f['label'] ?? '' ),
                    'field_type'  => sanitize_key( $f['type'] ?? 'text' ),
                    'placeholder' => sanitize_text_field( $f['placeholder'] ?? '' ),
                    'is_required' => ! empty( $f['required'] ) ? 1 : 0,
                    'options'     => '',
                    'validation'  => $val ? wp_json_encode( $val ) : '',
                    'step_number' => max( 1, (int) ( $f['step'] ?? 1 ) ),
                    'sort_order'  => $order,
                    'css_class'   => sanitize_text_field( $f['width'] ?? '100' ),
                    'is_active'   => 1,
                )
            );
        }
        if ( class_exists( 'CGS_Database' ) ) {
            CGS_Database::invalidate_fields_cache( $type );
        }
        if ( is_array( $styles ) && class_exists( 'CGS_Form_Styles' ) ) {
            $all = get_option( 'cgs_form_styles', array() );
            $all[ $type ] = wp_parse_args( $styles, CGS_Form_Styles::get_defaults() );
            update_option( 'cgs_form_styles', $all, false );
        }
        $cols = (int) ( $tpl['columns_default'] ?? 2 );
        if ( ! is_array( $meta ) || empty( $meta ) ) {
            $meta = array();
            $steps = array_unique( array_map( function ( $f ) {
                return (int) ( $f['step'] ?? 1 );
            }, $fields ) );
            foreach ( $steps as $sn ) {
                $meta[ $sn ] = array( 'name' => 'مرحله ' . $sn, 'columns' => $cols );
            }
        }
        update_option( 'cgs_step_meta_' . $type, $meta, false );
        wp_send_json_success( array(
            'count'   => $order,
            'message' => 'قالب «' . ( $tpl['name'] ?? '' ) . '» با ' . $order . ' فیلد اعمال شد. صفحه رفرش می‌شود.',
            'reload'  => 1,
        ) );
    }
}
