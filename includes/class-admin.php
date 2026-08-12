<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Admin {

    public static function init() {
        add_action( 'wp_ajax_cgs_optimize_db', array( __CLASS__, 'ajax_optimize_db' ) );
        add_action( 'wp_ajax_cgs_flush_field_cache', array( __CLASS__, 'ajax_flush_field_cache' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'wp_ajax_cgs_get_cities', array( 'CGS_Frontend', 'ajax_get_cities' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_nocache_headers' ), 1 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'admin_footer_text', array( __CLASS__, 'remove_footer_text' ) );
        add_filter( 'update_footer', '__return_empty_string', 11 );
        add_action( 'wp_ajax_cgs_set_member_guarantee', array( __CLASS__, 'ajax_set_member_guarantee' ) );
        add_action( 'wp_ajax_cgs_update_status', array( 'CGS_Application', 'ajax_update_status' ) );
    }

    public static function register_menus() {
        add_menu_page(
            'شهر قسط',
            'شهر قسط',
            'manage_options',
            'city-ghest',
            array( __CLASS__, 'page_dashboard' ),
            'dashicons-building',
            3
        );

        
        if ( get_option( 'cgs_show_setup_wizard' ) ) {
            add_submenu_page(
                'cgs-dashboard',
                'راه‌اندازی اولیه',
                'راه‌اندازی اولیه',
                'manage_options',
                'cgs-setup',
                array( __CLASS__, 'render_setup_wizard' )
            );
        }
add_submenu_page( 'city-ghest', 'داشبورد', 'داشبورد', 'manage_options', 'city-ghest', array( __CLASS__, 'page_dashboard' ) );
        add_submenu_page( 'city-ghest', 'درخواست‌ها', 'درخواست‌ها', 'manage_options', 'cgs-applications', array( __CLASS__, 'page_applications' ) );
        add_submenu_page( 'city-ghest', 'فرم‌ساز', 'فرم‌ساز داینامیک', 'manage_options', 'cgs-form-builder', array( __CLASS__, 'page_form_builder' ) );
        add_submenu_page( 'city-ghest', 'تنظیمات', 'تنظیمات', 'manage_options', 'cgs-settings', array( __CLASS__, 'page_settings' ) );
        add_submenu_page( 'city-ghest', 'منوساز حرفه‌ای', 'منوساز', 'manage_options', 'cgs-menu-builder', array( __CLASS__, 'page_menu_builder' ) );
    }


    /**
     * هدر ضدکش فقط روی صفحات افزونه — جایگزین نسبی Ctrl+F5 برای HTML ادمین
     */
    public static function maybe_nocache_headers() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }
        $page = sanitize_key( $_GET['page'] );
        $ours = array( 'city-ghest', 'cgs-form-builder', 'cgs-settings', 'cgs-applications', 'cgs-plans', 'cgs-crm', 'cgs-query-monitor', 'cgs-smart-monitor' );
        if ( ! in_array( $page, $ours, true ) && strpos( $page, 'cgs-' ) !== 0 ) {
            return;
        }
        if ( ! headers_sent() ) {
            nocache_headers();
            header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        }
    }

    public static function asset_ver() {
        $salt = get_option( 'cgs_asset_salt', '' );
        return ( defined( 'CGS_VERSION' ) ? CGS_VERSION : '1.0' ) . ( $salt ? '.' . $salt : '' );
    }

    public static function enqueue_assets( $hook ) {
        // منوی کناری همیشه
        wp_enqueue_style( 'cgs-admin-menu', CGS_PLUGIN_URL . 'admin/css/admin-menu.css', array(), self::asset_ver() );

        // فقط صفحات شهر قسط
        $hook = (string) $hook;
        if ( strpos( $hook, 'city-ghest' ) === false && strpos( $hook, 'cgs-' ) === false ) {
            return;
        }

        $is_builder  = ( false !== strpos( $hook, 'form-builder' ) );
        $is_settings = ( false !== strpos( $hook, 'cgs-settings' ) );
        $is_plans    = ( false !== strpos( $hook, 'cgs-plans' ) );
        $is_monitor  = ( false !== strpos( $hook, 'query-monitor' ) || false !== strpos( $hook, 'cgs-query' ) );

        // دارایی‌های مشترک همه صفحات افزونه — رفع شکست ساختاری ۳.۹.۲ (دکمه‌ها)
        wp_enqueue_style( 'cgs-icons', CGS_PLUGIN_URL . 'assets/icons/fontawesome/cgs-icons.css', array(), self::asset_ver() );
        wp_enqueue_style( 'cgs-persian-fonts', CGS_PLUGIN_URL . 'assets/fonts/persian-fonts.css', array(), self::asset_ver() );
        wp_enqueue_style( 'cgs-grid-responsive', CGS_PLUGIN_URL . 'admin/css/cgs-grid-responsive.css', array(), self::asset_ver() );
        wp_enqueue_style( 'cgs-admin', CGS_PLUGIN_URL . 'admin/css/admin.css', array(), self::asset_ver() );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_media();
        // SortableJS محلی — جایگزین jquery-ui-sortable
        wp_enqueue_script(
            'sortablejs',
            CGS_PLUGIN_URL . 'assets/js/Sortable.min.js',
            array(),
            '1.15.6',
            true
        );
        // shopify-draggable temporarily disabled — was breaking admin JS
        if ( false ) wp_enqueue_script(
            'shopify-draggable',
            CGS_PLUGIN_URL . 'assets/js/draggable.bundle.js',
            array(),
            '1.0.0-beta.8',
            true
        );
        // راهنمای سراسری روی همه صفحات شهر قسط
        if ( class_exists( 'CGS_Help' ) ) {
            add_action( 'admin_head', function () {
                if ( class_exists( 'CGS_Help' ) ) {
                    echo CGS_Help::styles();
                }
            }, 5 );
        }

        $cgs_common = array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'cgs_admin_nonce' ),
            'sound_enabled' => (int) ( function_exists( 'cgs_get_option' ) ? cgs_get_option( 'sound_enabled', 1 ) : 1 ),
            'sound_type'    => function_exists( 'cgs_get_option' ) ? cgs_get_option( 'sound_type', 'chime' ) : 'chime',
            'sound_volume'  => (int) ( function_exists( 'cgs_get_option' ) ? cgs_get_option( 'sound_volume', 70 ) : 70 ),
            'dnd_engine'    => function_exists( 'cgs_get_option' ) ? cgs_get_option( 'fb_dnd_engine', 'sortablejs' ) : 'sortablejs',
            'fb_plugins'    => function_exists( 'cgs_get_option' ) ? ( cgs_get_option( 'fb_plugins', array() ) ?: array() ) : array(),
        );

        // Chart.js برای داشبورد / CRM / مانیتور — همیشه محلی
        $need_chart = ( ! $is_builder && ! $is_settings && ! $is_plans );
        if ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'city-ghest', 'cgs-crm', 'cgs-settings' ), true ) ) {
            // داشبورد اصلی یا CRM
            if ( in_array( $_GET['page'], array( 'city-ghest', 'cgs-crm' ), true ) ) {
                $need_chart = true;
            }
        }
        if ( $need_chart ) {
            wp_enqueue_script( 'cgs-chartjs', CGS_PLUGIN_URL . 'assets/js/chart.umd.min.js', array( 'jquery' ), defined('CGS_VERSION') ? CGS_VERSION : '4.4.1', true );
        }
        // اجبار روی داشبورد اصلی
        if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'city-ghest' ) {
            wp_enqueue_script( 'cgs-chartjs', CGS_PLUGIN_URL . 'assets/js/chart.umd.min.js', array( 'jquery' ), defined('CGS_VERSION') ? CGS_VERSION : '4.4.1', true );
        }

        // فرم‌ساز: استایل‌های اختصاصی
        if ( $is_builder ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'cgs-fb-modern', CGS_PLUGIN_URL . 'admin/css/form-builder-modern.css', array(), self::asset_ver() );
            wp_enqueue_style( 'cgs-fb-polish', CGS_PLUGIN_URL . 'admin/css/form-builder-polish.css', array( 'cgs-admin' ), self::asset_ver() );
            wp_enqueue_style( 'cgs-public-preview', CGS_PLUGIN_URL . 'public/css/public.css', array(), self::asset_ver() );
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_script( 'cgs-jalali-dp', CGS_PLUGIN_URL . 'public/js/jalali-datepicker.js', array( 'jquery' ), self::asset_ver(), true );
        }

        // admin.js حاوی هندلرهای مشترک (تغییر وضعیت درخواست‌ها، امضای ضامن، کپی و...) است
        // و باید در همه صفحات افزونه بارگذاری شود، نه فقط فرم‌ساز — رفع باگ: دراپ‌داون
        // «تغییر وضعیت» در صفحات «درخواست‌ها» و «داشبورد» قبلاً فاقد اسکریپت متصل بود.
        $admin_js_deps = array( 'jquery', 'jquery-ui-sortable', 'sortablejs' );
        if ( $is_builder ) {
            $admin_js_deps[] = 'wp-color-picker';
        }
        wp_enqueue_script( 'cgs-admin', CGS_PLUGIN_URL . 'admin/js/admin.js', $admin_js_deps, self::asset_ver(), true );

        $localize_data = $cgs_common;
        if ( $is_builder ) {
            $localize_data['locations']   = function_exists( 'cgs_get_iran_locations' ) ? cgs_get_iran_locations() : array();
            $localize_data['city_coords'] = array();
        }
        wp_localize_script( 'cgs-admin', 'cgsAdmin', $localize_data );

        // تنظیمات: تب‌ها
        if ( $is_settings ) {
            wp_enqueue_script( 'cgs-settings-tabs', CGS_PLUGIN_URL . 'admin/js/settings-tabs.js', array( 'jquery', 'sortablejs' ), self::asset_ver(), true );
            wp_localize_script( 'cgs-settings-tabs', 'cgsAdmin', $localize_data );
        }

        // plans.js توسط CGS_Plans::assets لود می‌شود
        if ( $is_plans ) {
            // sortablejs + media already enqueued
        }
    }

    public static function page_menu_builder() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        try {
            if ( ! class_exists( 'CGS_Menu_Builder', false ) ) {
                $b = CGS_PLUGIN_DIR . 'modules/menu-builder/bootstrap.php';
                if ( is_readable( $b ) ) { require_once $b; }
            }
            if ( class_exists( 'CGS_Menu_Builder' ) && method_exists( 'CGS_Menu_Builder', 'render_admin' ) ) {
                CGS_Menu_Builder::render_admin();
                return;
            }
        } catch ( Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[CGS page_menu_builder] ' . $e->getMessage() );
            }
            echo '<div class="wrap" dir="rtl"><h1>منوساز</h1><div class="notice notice-error"><p>خطای بارگذاری منوساز: '
                . esc_html( $e->getMessage() ) . '</p></div></div>';
            return;
        }
        echo '<div class="wrap" dir="rtl"><h1>منوساز</h1><p>ماژول منوساز بارگذاری نشد.</p></div>';
    }

    public static function page_dashboard() {
        include CGS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function page_applications() {
        if ( isset( $_POST['cgs_save_guarantee_checklist'] ) && check_admin_referer( 'cgs_guarantee_checklist', 'cgs_gc_nonce' ) ) {
            $app_id = absint( $_POST['gc_app_id'] ?? 0 );
            $items  = isset( $_POST['gc_items'] ) ? array_map( 'sanitize_text_field', (array) $_POST['gc_items'] ) : array();
            $data = array(
                'items'       => $items,
                'ca_name'     => sanitize_text_field( $_POST['gc_ca_name'] ?? '' ),
                'cert_expiry' => sanitize_text_field( $_POST['gc_cert_expiry'] ?? '' ),
                'saved_at'    => current_time( 'mysql' ),
                'saved_by'    => get_current_user_id(),
            );
            if ( $app_id ) {
                update_option( 'cgs_guarantee_checklist_' . $app_id, $data );
                echo '<div class="notice notice-success"><p>چک‌لیست تضمین برای درخواست #' . $app_id . ' ذخیره شد.</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>شناسه درخواست را وارد کنید.</p></div>';
            }
        }
        include CGS_PLUGIN_DIR . 'admin/views/applications.php';
    }

    public static function page_form_builder() {
        include CGS_PLUGIN_DIR . 'admin/views/form-builder.php';
    }

    public static function page_settings() {
        // Save settings
        if ( isset( $_POST['cgs_save_settings'] ) && check_admin_referer( 'cgs_settings_nonce', 'cgs_settings_nonce' ) ) {
            try {

            // Explicit checkbox defaults (unchecked fields are absent from POST)
            $checkbox_keys = array(
                'sms_enabled', 'sms_on_approve', 'sms_on_reject', 'sound_enabled',
                'allow_natural_person', 'allow_legal_person',
                'allow_check_guarantee', 'allow_promissory_guarantee', 'allow_bank_guarantee', 'allow_property_guarantee', 'allow_semigov_person', 'allow_gov_person',
                'shahkar_mobile', 'shahkar_name',
                'av_national_id_enabled', 'av_postal_enabled', 'av_mobile_enabled',
                'av_sheba_enabled', 'av_credit_enabled',
            );
            foreach ( $checkbox_keys as $ck ) {
                cgs_update_option( $ck, isset( $_POST[ $ck ] ) ? 1 : 0 );
            }

            $fields = array(
                'date_calendar', 'sms_enabled', 'sms_provider', 'sms_api_key',
                'sms_sender', 'sms_on_approve', 'sms_on_reject', 'company_name',
                'primary_color', 'secondary_color', 'sound_volume', 'sound_type', 'ui_theme', 'number_system', 'sign_providers_list', 'sms_providers_list', 'custom_guarantee_types', 'property_deed_fields', 'property_deed_types', 'digital_sign_provider', 'digital_sign_api_key', 'shahkar_api_key', 'site_icon', 'site_logo', 'crm_external_provider', 'crm_external_url', 'crm_external_key'
            );
            foreach ( $fields as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    cgs_update_option( $field, sanitize_text_field( $_POST[ $field ] ) );
                }
            }
            // Checkboxes
            
            // Colors & labels
            if ( ! empty( $_POST['cgs_reset_colors_labels'] ) ) {
                foreach ( array( 'status_labels', 'status_colors', 'type_labels', 'type_colors', 'crm_stage_labels', 'crm_stage_colors' ) as $opt ) {
                    cgs_update_option( $opt, array() );
                }
            } else {
                if ( isset( $_POST['status_labels'] ) && is_array( $_POST['status_labels'] ) ) {
                    $sl = array();
                    foreach ( $_POST['status_labels'] as $k => $v ) {
                        $sl[ sanitize_key( $k ) ] = sanitize_text_field( $v );
                    }
                    cgs_update_option( 'status_labels', $sl );
                }
                if ( isset( $_POST['status_colors'] ) && is_array( $_POST['status_colors'] ) ) {
                    $sc = array();
                    foreach ( $_POST['status_colors'] as $k => $v ) {
                        $sc[ sanitize_key( $k ) ] = sanitize_hex_color( $v ) ?: $v;
                    }
                    cgs_update_option( 'status_colors', $sc );
                }
                if ( isset( $_POST['type_labels'] ) && is_array( $_POST['type_labels'] ) ) {
                    $tl = array();
                    foreach ( $_POST['type_labels'] as $k => $v ) {
                        $tl[ sanitize_key( $k ) ] = sanitize_text_field( $v );
                    }
                    cgs_update_option( 'type_labels', $tl );
                }
                if ( isset( $_POST['type_colors'] ) && is_array( $_POST['type_colors'] ) ) {
                    $tc = array();
                    foreach ( $_POST['type_colors'] as $v ) {
                        $tc[] = sanitize_hex_color( $v ) ?: $v;
                    }
                    cgs_update_option( 'type_colors', $tc );
                }
                if ( isset( $_POST['crm_stage_labels'] ) && is_array( $_POST['crm_stage_labels'] ) ) {
                    $cl = array();
                    foreach ( $_POST['crm_stage_labels'] as $k => $v ) {
                        $cl[ sanitize_key( $k ) ] = sanitize_text_field( $v );
                    }
                    cgs_update_option( 'crm_stage_labels', $cl );
                }
                if ( isset( $_POST['crm_stage_colors'] ) && is_array( $_POST['crm_stage_colors'] ) ) {
                    $cc = array();
                    foreach ( $_POST['crm_stage_colors'] as $k => $v ) {
                        $cc[ sanitize_key( $k ) ] = sanitize_hex_color( $v ) ?: $v;
                    }
                    cgs_update_option( 'crm_stage_colors', $cc );
                }
            }

            
            // Chart format — فقط وقتی تب نمودارها فعال است (ماژول settings)
            // جلوگیری از صفر شدن چک‌باکس نمودار هنگام ذخیره تب‌های دیگر
            if ( function_exists( 'cgs_settings_save_chart_format_from_post' ) ) {
                cgs_settings_save_chart_format_from_post();
            } elseif ( ! empty( $_POST['cgs_reset_chart_format'] ) ) {
                cgs_update_option( 'chart_format', array() );
            } elseif ( isset( $_POST['chart_format'] ) && is_array( $_POST['chart_format'] )
                && ( ( $_POST['cgs_active_tab'] ?? '' ) === 'charts' ) ) {
                $cf_in = wp_unslash( $_POST['chart_format'] );
                $cf = function_exists( 'cgs_get_chart_format' ) ? cgs_get_chart_format() : array();
                $text_keys = array( 'status_type','types_type','trend_type','legend_position','border_color','title_status','title_types','title_trend','title_crm','font_family' );
                $num_keys  = array( 'anim_duration','cutout','border_width','bar_radius','line_tension','point_radius','font_size','aspect_ratio' );
                $chk_keys  = array( 'show_legend','animation','line_fill','show_grid','show_title','tooltip_rtl' );
                foreach ( $text_keys as $k ) {
                    if ( isset( $cf_in[ $k ] ) ) $cf[ $k ] = sanitize_text_field( $cf_in[ $k ] );
                }
                foreach ( $num_keys as $k ) {
                    if ( isset( $cf_in[ $k ] ) ) $cf[ $k ] = sanitize_text_field( $cf_in[ $k ] );
                }
                foreach ( $chk_keys as $k ) {
                    $cf[ $k ] = ! empty( $cf_in[ $k ] ) ? '1' : '0';
                }
                cgs_update_option( 'chart_format', $cf );
                update_option( 'cgs_chart_format_v', $cf, false );
            }

            
            // Custom B fonts upload
            $font_map = array(
                'cgs_font_bnazanin'      => 'BNazanin.woff2',
                'cgs_font_bnazanin_bold' => 'BNazanin-Bold.woff2',
                'cgs_font_btitr'         => 'BTitrBold.woff2',
            );
            $font_dir = CGS_PLUGIN_DIR . 'assets/fonts/woff2/';
            if ( ! is_dir( $font_dir ) ) {
                wp_mkdir_p( $font_dir );
            }
            foreach ( $font_map as $field => $target ) {
                if ( empty( $_FILES[ $field ]['tmp_name'] ) || ! is_uploaded_file( $_FILES[ $field ]['tmp_name'] ) ) {
                    continue;
                }
                $name = sanitize_file_name( $_FILES[ $field ]['name'] );
                $ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
                if ( ! in_array( $ext, array( 'woff2', 'woff', 'ttf' ), true ) ) {
                    continue;
                }
                // Keep original extension if not woff2
                if ( $ext !== 'woff2' ) {
                    $target = preg_replace( '/\.woff2$/', '.' . $ext, $target );
                }
                $dest = $font_dir . $target;
                if ( move_uploaded_file( $_FILES[ $field ]['tmp_name'], $dest ) ) {
                    // ok
                }
            }

            
            // Jalali advanced settings
            if ( ! empty( $_POST['cgs_reset_jalali'] ) ) {
                cgs_update_option( 'jalali_settings', array() );
            } elseif ( isset( $_POST['jalali_settings'] ) && is_array( $_POST['jalali_settings'] ) ) {
                $jin = $_POST['jalali_settings'];
                $j = array();
                foreach ( array( 'calendar_type','start_year','end_year','format','min_age','max_age','week_start','theme','position' ) as $k ) {
                    if ( isset( $jin[ $k ] ) ) {
                        $j[ $k ] = sanitize_text_field( $jin[ $k ] );
                    }
                }
                foreach ( array( 'show_today_btn','show_clear_btn','close_on_select','default_today','month_dropdown','year_dropdown','locale_numbers','show_holidays','enabled' ) as $k ) {
                    $j[ $k ] = ! empty( $jin[ $k ] ) ? '1' : '0';
                }
                cgs_update_option( 'jalali_settings', $j );
            }

            
            // Auto-verify / automation
            $av_checks = array(
                'av_national_id_enabled', 'av_postal_enabled', 'av_mobile_enabled',
                'av_sheba_enabled', 'av_credit_enabled',
            );
            foreach ( $av_checks as $ack ) {
                cgs_update_option( $ack, isset( $_POST[ $ack ] ) ? 1 : 0 );
            }
            $av_texts = array(
                'av_national_id_mode', 'av_national_id_api_url', 'av_national_id_api_key',
                'av_postal_mode', 'av_postal_notice',
                'av_mobile_mode', 'av_mobile_mismatch_msg',
                'av_sheba_mode', 'av_sheba_on_exceed',
                'av_credit_mode',
            );
            foreach ( $av_texts as $at ) {
                if ( isset( $_POST[ $at ] ) ) {
                    cgs_update_option( $at, sanitize_text_field( wp_unslash( $_POST[ $at ] ) ) );
                }
            }
            foreach ( array( 'av_sheba_max_pending_checks', 'av_sheba_max_pending_amount', 'av_credit_fee' ) as $an ) {
                if ( isset( $_POST[ $an ] ) ) {
                    cgs_update_option( $an, absint( $_POST[ $an ] ) );
                }
            }
            if ( isset( $_POST['custom_audiences'] ) && is_array( $_POST['custom_audiences'] ) ) {
                $aud = array();
                foreach ( $_POST['custom_audiences'] as $row ) {
                    $key   = sanitize_key( $row['key'] ?? '' );
                    $label = sanitize_text_field( $row['label'] ?? '' );
                    // ردیف حذف‌شده از DOM یا خالی ذخیره نشود
                    if ( ! $key || ! $label ) {
                        continue;
                    }
                    $aud[] = array(
                        'key'    => $key,
                        'label'  => $label,
                        'color'  => sanitize_hex_color( $row['color'] ?? '' ) ?: '#1a237e',
                        'icon'   => sanitize_text_field( $row['icon'] ?? 'user' ),
                        'active' => ! empty( $row['active'] ) ? 1 : 0,
                    );
                }
                cgs_update_option( 'custom_audiences', $aud );
            }

            // موتور درگ و افزونه‌های فرم‌ساز
            if ( isset( $_POST['fb_dnd_engine'] ) ) {
                $eng = sanitize_key( $_POST['fb_dnd_engine'] );
                if ( ! in_array( $eng, array( 'sortablejs', 'html5', 'rbd' ), true ) ) {
                    $eng = 'sortablejs';
                }
                cgs_update_option( 'fb_dnd_engine', $eng );
            }
            if ( isset( $_POST['fb_dnd_engine'] ) || isset( $_POST['fb_plugins'] ) ) {
                $fb_plugin_keys = array( 'cond', 'matrix', 'jalali', 'landline', 'sheba', 'signature', 'sound', 'resize', 'dnd' );
                $fb_plugins_save = array();
                foreach ( $fb_plugin_keys as $pk ) {
                    $fb_plugins_save[ $pk ] = ( isset( $_POST['fb_plugins'][ $pk ] ) && $_POST['fb_plugins'][ $pk ] ) ? 1 : 0;
                }
                cgs_update_option( 'fb_plugins', $fb_plugins_save );
            }

            
            
            if ( isset( $_POST['inquiry_providers'] ) && is_array( $_POST['inquiry_providers'] ) ) {
                $ips = array();
                foreach ( $_POST['inquiry_providers'] as $row ) {
                    $ips[] = array(
                        'type'    => sanitize_key( $row['type'] ?? '' ),
                        'label'   => sanitize_text_field( $row['label'] ?? '' ),
                        'api_url' => esc_url_raw( $row['api_url'] ?? '' ),
                        'api_key' => sanitize_text_field( $row['api_key'] ?? '' ),
                        'enabled' => ! empty( $row['enabled'] ) ? 1 : 0,
                    );
                }
                cgs_update_option( 'inquiry_providers', $ips );
            }

            if ( class_exists( 'CGS_Payment' ) && isset( $_POST['cgs_pay_gateway'] ) ) {
                CGS_Payment::save_settings( array(
                    'gateway'     => $_POST['cgs_pay_gateway'] ?? 'none',
                    'sandbox'     => ! empty( $_POST['cgs_pay_sandbox'] ),
                    'merchant_id' => $_POST['cgs_pay_merchant_id'] ?? '',
                    'api_key'     => $_POST['cgs_pay_api_key'] ?? '',
                    'terminal_id' => $_POST['cgs_pay_terminal_id'] ?? '',
                    'username'    => $_POST['cgs_pay_username'] ?? '',
                    'password'    => $_POST['cgs_pay_password'] ?? '',
                    'currency'    => $_POST['cgs_pay_currency'] ?? 'IRR',
                    'description' => $_POST['cgs_pay_description'] ?? '',
                ) );
            }

            cgs_update_option( 'sound_enabled', isset( $_POST['sound_enabled'] ) ? 1 : 0 );
            if ( isset( $_POST['sound_type'] ) ) {
                cgs_update_option( 'sound_type', sanitize_key( $_POST['sound_type'] ) );
            }
            cgs_update_option( 'allow_natural_person', isset( $_POST['allow_natural_person'] ) ? 1 : 0 );
            cgs_update_option( 'allow_legal_person', isset( $_POST['allow_legal_person'] ) ? 1 : 0 );
            cgs_update_option( 'allow_check_guarantee', isset( $_POST['allow_check_guarantee'] ) ? 1 : 0 );
            cgs_update_option( 'allow_promissory_guarantee', isset( $_POST['allow_promissory_guarantee'] ) ? 1 : 0 );
            cgs_update_option( 'shahkar_mobile', isset( $_POST['shahkar_mobile'] ) ? 1 : 0 );
            cgs_update_option( 'shahkar_name', isset( $_POST['shahkar_name'] ) ? 1 : 0 );
            cgs_update_option( 'sms_enabled', isset( $_POST['sms_enabled'] ) ? 1 : 0 );
            cgs_update_option( 'sms_on_approve', isset( $_POST['sms_on_approve'] ) ? 1 : 0 );
            cgs_update_option( 'sms_on_reject', isset( $_POST['sms_on_reject'] ) ? 1 : 0 );

            // Keep user on same tab after save
            $tab = isset( $_POST['cgs_active_tab'] ) ? sanitize_key( $_POST['cgs_active_tab'] ) : 'general';
            
            // Calculator defaults
            foreach ( array( 'cgs_calc_default_principal', 'cgs_calc_default_rate', 'cgs_calc_default_months', 'cgs_calc_default_step', 'cgs_calc_default_method' ) as $cf ) {
                if ( isset( $_POST[ $cf ] ) ) {
                    cgs_update_option( $cf, sanitize_text_field( wp_unslash( $_POST[ $cf ] ) ) );
                }
            }
            // Settlement settings
            $settlement = array(
                'early_discount_percent' => floatval( $_POST['settlement_early_discount_percent'] ?? 50 ),
                'late_penalty_daily'     => floatval( $_POST['settlement_late_penalty_daily'] ?? 0.1 ),
                'grace_days'             => absint( $_POST['settlement_grace_days'] ?? 3 ),
                'min_partial_percent'    => floatval( $_POST['settlement_min_partial_percent'] ?? 10 ),
                'rounding'               => sanitize_key( $_POST['settlement_rounding'] ?? 'round' ),
            );
            update_option( 'cgs_settlement_settings', $settlement, false );
            // Credit risk settings
            $risk = array(
                'weight_credit_rank'    => absint( $_POST['risk_weight_credit_rank'] ?? 35 ),
                'weight_debt'           => absint( $_POST['risk_weight_debt'] ?? 25 ),
                'weight_age'            => absint( $_POST['risk_weight_age'] ?? 10 ),
                'weight_income_ratio'   => absint( $_POST['risk_weight_income_ratio'] ?? 20 ),
                'weight_history'        => absint( $_POST['risk_weight_history'] ?? 10 ),
                'reject_below'          => absint( $_POST['risk_reject_below'] ?? 40 ),
                'manual_below'          => absint( $_POST['risk_manual_below'] ?? 60 ),
                'max_installment_ratio' => floatval( $_POST['risk_max_installment_ratio'] ?? 40 ),
                'min_age'               => absint( $_POST['risk_min_age'] ?? 18 ),
                'max_age'               => absint( $_POST['risk_max_age'] ?? 70 ),
                'auto_reject'           => ! empty( $_POST['risk_auto_reject'] ) ? 1 : 0,
                'auto_approve'          => 0,
            );
            update_option( 'cgs_credit_risk_settings', $risk, false );

            set_transient( 'cgs_settings_saved_redirect', 1, 30 );
            // ذخیره بدون خالی کردن بافرها (علت صفحه سفید)
            $url_args = array(
                'page'    => 'cgs-settings',
                'tab'     => $tab,
                'updated' => '1',
            );
            if ( $tab === 'charts' ) {
                $url_args['cgs_charts_saved'] = '1';
            }
            $url = add_query_arg( $url_args, admin_url( 'admin.php' ) );
            if ( ! empty( $_POST['cgs_reset_ui_theme'] ) ) {
                cgs_update_option( 'ui_theme', 'navy' );
            }
            if ( ! empty( $_POST['cgs_add_custom_theme'] ) ) {
                $ck = sanitize_key( $_POST['custom_theme_key'] ?? '' );
                $cn = sanitize_text_field( $_POST['custom_theme_name'] ?? '' );
                if ( $ck && $cn ) {
                    $customs = cgs_get_option( 'custom_ui_themes', array() );
                    if ( ! is_array( $customs ) ) $customs = array();
                    $customs[ $ck ] = array(
                        'name' => $cn,
                        'c' => array(
                            sanitize_hex_color( $_POST['custom_theme_c1'] ?? '#1a237e' ) ?: '#1a237e',
                            sanitize_hex_color( $_POST['custom_theme_c2'] ?? '#3949ab' ) ?: '#3949ab',
                            sanitize_hex_color( $_POST['custom_theme_c3'] ?? '#c5cae9' ) ?: '#c5cae9',
                            sanitize_hex_color( $_POST['custom_theme_c4'] ?? '#ffd54f' ) ?: '#ffd54f',
                        ),
                    );
                    cgs_update_option( 'custom_ui_themes', $customs );
                    cgs_update_option( 'ui_theme', $ck );
                }
            }
            if ( class_exists( 'CGS_Cache' ) ) {
                if ( method_exists( 'CGS_Cache', 'flush_all' ) ) {
                    CGS_Cache::flush_all();
                } elseif ( method_exists( 'CGS_Cache', 'flush_group_prefix' ) ) {
                    CGS_Cache::flush_group_prefix( '' );
                }
            }
            // تضمین‌های داینامیک (انواع پایه + جزئیات با آیکن و توضیح)
            if ( isset( $_POST['guarantee_types'] ) && is_array( $_POST['guarantee_types'] ) ) {
                $gtypes = array();
                foreach ( $_POST['guarantee_types'] as $row ) {
                    if ( ! is_array( $row ) ) {
                        continue;
                    }
                    $props = array();
                    if ( ! empty( $row['props'] ) && is_array( $row['props'] ) ) {
                        foreach ( $row['props'] as $pr ) {
                            if ( ! is_array( $pr ) ) {
                                continue;
                            }
                            $plabel = sanitize_text_field( $pr['label'] ?? '' );
                            if ( $plabel === '' ) {
                                continue;
                            }
                            $props[] = array(
                                'label'       => $plabel,
                                'type'        => sanitize_key( $pr['type'] ?? 'text' ),
                                'description' => sanitize_textarea_field( $pr['description'] ?? '' ),
                                'icon'        => sanitize_text_field( $pr['icon'] ?? '' ),
                                'enabled'     => ! empty( $pr['enabled'] ) ? 1 : 0,
                            );
                        }
                    }
                    $glabel = sanitize_text_field( $row['label'] ?? '' );
                    if ( $glabel === '' ) {
                        continue;
                    }
                    $gtypes[] = array(
                        'id'          => sanitize_key( $row['id'] ?? '' ) ?: sanitize_key( $glabel ),
                        'label'       => $glabel,
                        'description' => sanitize_textarea_field( $row['description'] ?? '' ),
                        'icon'        => sanitize_text_field( $row['icon'] ?? 'file-text' ),
                        'enabled'     => ! empty( $row['enabled'] ) ? 1 : 0,
                        'props'       => $props,
                    );
                }
                cgs_update_option( 'guarantee_types_dynamic', $gtypes );
            }
            if ( ! headers_sent() ) {
                wp_safe_redirect( $url );
                exit;
            }
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد. <a href="' . esc_url( $url ) . '">ادامه</a></p></div>';
            } catch ( Throwable $e ) {
                echo '<div class="notice notice-error"><p>خطا در ذخیره: ' . esc_html( $e->getMessage() ) . '</p></div>';
            }
        }
        include ( function_exists('cgs_settings_view_path') ? cgs_settings_view_path() : ( CGS_PLUGIN_DIR . 'admin/views/settings.php' ) );
    }
    public static function ajax_set_member_guarantee() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $user_id = absint( $_POST['user_id'] ?? 0 );
        $app_id  = absint( $_POST['application_id'] ?? 0 );
        $allow_check = ! empty( $_POST['allow_check'] ) ? 1 : 0;
        $allow_promissory = ! empty( $_POST['allow_promissory'] ) ? 1 : 0;

        if ( $user_id ) {
            update_user_meta( $user_id, 'cgs_allow_check', $allow_check );
            update_user_meta( $user_id, 'cgs_allow_promissory', $allow_promissory );
        }
        if ( $app_id ) {
            update_option( 'cgs_app_guarantee_' . $app_id, array(
                'allow_check' => $allow_check,
                'allow_promissory' => $allow_promissory,
            ) );
        }
        wp_send_json_success( 'مجوز تضمین ذخیره شد.' );
    }

    public static function ajax_flush_field_cache() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        if ( class_exists( 'CGS_Cache' ) && method_exists( 'CGS_Cache', 'flush_all' ) ) {
            CGS_Cache::flush_all();
        }
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_cgs_%' OR option_name LIKE '%_transient_timeout_cgs_%'" );
        wp_send_json_success( true );
    }

    public static function ajax_optimize_db() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        CGS_Database::ensure_indexes();
        $r = CGS_Database::optimize_tables();
        wp_send_json_success( $r );
    }
    public static function remove_footer_text( $text = '' ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && ( strpos( (string) $screen->id, 'city-ghest' ) !== false || strpos( (string) $screen->id, 'cgs-' ) !== false ) ) {
            return '';
        }
        return $text;
    }

}
