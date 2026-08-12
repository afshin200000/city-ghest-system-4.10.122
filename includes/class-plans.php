<?php
/**
 * Fully dynamic Plans module
 * - Custom categories (admin creates)
 * - Options with detail types (age range, credit ranks, text, number...)
 * - Multiple repayment durations per plan
 * - Appearance styles + live preview
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Plans {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'wp_ajax_cgs_save_plan', array( __CLASS__, 'ajax_save_plan' ) );
        add_action( 'wp_ajax_cgs_delete_plan', array( __CLASS__, 'ajax_delete_plan' ) );
        add_action( 'wp_ajax_cgs_get_plan', array( __CLASS__, 'ajax_get_plan' ) );
        add_action( 'wp_ajax_cgs_save_plan_categories', array( __CLASS__, 'ajax_save_categories' ) );
        add_action( 'wp_ajax_cgs_save_plan_styles', array( __CLASS__, 'ajax_save_styles' ) );
        add_action( 'wp_ajax_cgs_save_plan_design', array( __CLASS__, 'ajax_save_design' ) );
        add_action( 'wp_ajax_cgs_delete_plan_design', array( __CLASS__, 'ajax_delete_design' ) );
        add_action( 'wp_ajax_cgs_list_plan_designs', array( __CLASS__, 'ajax_list_designs' ) );
        add_shortcode( 'cgs_plans', array( __CLASS__, 'shortcode_cards' ) );
    }

    public static function menu() {
        // manage_options تا در همه نصب‌ها منو و دکمه‌ها کار کنند (قابلیت اختصاصی ممکن است به ادمین اضافه نشده باشد)
        add_submenu_page( 'city-ghest', 'طرح‌ها و روش‌ها', 'طرح‌ها و روش‌ها', 'manage_options', 'cgs-plans', array( __CLASS__, 'page' ) );
    }

    public static function assets( $hook ) {
        if ( strpos( $hook, 'cgs-plans' ) === false ) {
            return;
        }
        wp_enqueue_style( 'cgs-admin' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_media();
        // SortableJS (نه jQuery UI) — محلی برای پایداری
        wp_enqueue_script(
            'sortablejs',
            CGS_PLUGIN_URL . 'assets/js/Sortable.min.js',
            array(),
            '1.15.6',
            true
        );
        // draggable disabled
        // wp_enqueue_script('shopify-draggable', ...);
        $ver = ( defined( 'CGS_VERSION' ) ? CGS_VERSION : '1' ) . '.' . get_option( 'cgs_asset_salt', '' );
        wp_enqueue_script(
            'cgs-plans-admin',
            CGS_PLUGIN_URL . 'admin/js/plans.js',
            array( 'jquery', 'sortablejs' ),
            $ver,
            true
        );
        wp_localize_script( 'cgs-plans-admin', 'cgsPlans', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'cgs_admin_nonce' ),
            'categories'   => self::get_categories(),
            'styles'       => self::get_styles(),
            'detail_types' => self::detail_types(),
            'designs'      => self::get_designs(),
        ) );
    }

    /** Detail field types for options */
    public static function detail_types() {
        return array(
            'none'        => 'بدون جزئیات (فقط عنوان)',
            'text'        => 'متن آزاد',
            'number'      => 'عدد',
            'age_range'   => 'بازه سنی (حداقل / حداکثر)',
            'number_range'=> 'بازه عددی (حداقل / حداکثر)',
            'list'        => 'فهرست مقادیر (هر خط یکی)',
            'percent'     => 'درصد',
        );
    }

    public static function default_categories() {
        return array(
            array(
                'id' => 'cat_beneficiaries',
                'title' => 'مشمولین دریافت اعتبار',
                'sort' => 1,
                'options' => array(
                    array( 'id' => 'b1', 'label' => 'عموم مردم', 'detail_type' => 'none', 'detail' => '', 'sort' => 1 ),
                    array( 'id' => 'b2', 'label' => 'پرسنل شاغل نیروهای مسلح', 'detail_type' => 'none', 'detail' => '', 'sort' => 2 ),
                    array( 'id' => 'b3', 'label' => 'بازنشستگان نیروهای مسلح', 'detail_type' => 'none', 'detail' => '', 'sort' => 3 ),
                    array( 'id' => 'b4', 'label' => 'بازنشستگان تأمین اجتماعی', 'detail_type' => 'none', 'detail' => '', 'sort' => 4 ),
                    array( 'id' => 'b5', 'label' => 'کارمندان دولت', 'detail_type' => 'none', 'detail' => '', 'sort' => 5 ),
                ),
            ),
            array(
                'id' => 'cat_instruments',
                'title' => 'وسیله پرداخت / تضمین',
                'sort' => 2,
                'options' => array(
                    array( 'id' => 'pi1', 'label' => 'چک', 'detail_type' => 'none', 'detail' => '', 'sort' => 1 ),
                    array( 'id' => 'pi2', 'label' => 'سفته', 'detail_type' => 'none', 'detail' => '', 'sort' => 2 ),
                    array( 'id' => 'pi3', 'label' => 'کسر از حقوق', 'detail_type' => 'none', 'detail' => '', 'sort' => 3 ),
                    array( 'id' => 'pi4', 'label' => 'سند ملکی', 'detail_type' => 'none', 'detail' => '', 'sort' => 4 ),
                    array( 'id' => 'pi5', 'label' => 'ضمانت‌نامه بانکی', 'detail_type' => 'none', 'detail' => '', 'sort' => 5 ),
                ),
            ),
            array(
                'id' => 'cat_conditions',
                'title' => 'شرایط تخصیص اعتبار',
                'sort' => 3,
                'options' => array(
                    array( 'id' => 'cc1', 'label' => 'رتبه اعتباری', 'detail_type' => 'list', 'detail' => "A\nB\nC", 'sort' => 1 ),
                    array( 'id' => 'cc2', 'label' => 'شرایط سنی', 'detail_type' => 'age_range', 'detail' => '18-70', 'sort' => 2 ),
                    array( 'id' => 'cc3', 'label' => 'مدارک درآمدی', 'detail_type' => 'none', 'detail' => '', 'sort' => 3 ),
                    array( 'id' => 'cc4', 'label' => 'نداشتن چک برگشتی', 'detail_type' => 'none', 'detail' => '', 'sort' => 4 ),
                ),
            ),
            array(
                'id' => 'cat_documents',
                'title' => 'مدارک مورد نیاز',
                'sort' => 4,
                'options' => array(
                    array( 'id' => 'd1', 'label' => 'کارت ملی', 'detail_type' => 'none', 'detail' => '', 'sort' => 1 ),
                    array( 'id' => 'd2', 'label' => 'شناسنامه', 'detail_type' => 'none', 'detail' => '', 'sort' => 2 ),
                    array( 'id' => 'd3', 'label' => 'فیش حقوقی', 'detail_type' => 'none', 'detail' => '', 'sort' => 3 ),
                    array( 'id' => 'd4', 'label' => 'گردش حساب', 'detail_type' => 'none', 'detail' => '', 'sort' => 4 ),
                ),
            ),
            array(
                'id' => 'cat_pay_methods',
                'title' => 'روش پرداخت اقساط',
                'sort' => 5,
                'options' => array(
                    array( 'id' => 'im1', 'label' => 'تأمین موجودی چک', 'detail_type' => 'none', 'detail' => '', 'sort' => 1 ),
                    array( 'id' => 'im2', 'label' => 'واریز به حساب تأمین‌کننده اعتبار', 'detail_type' => 'none', 'detail' => '', 'sort' => 2 ),
                    array( 'id' => 'im3', 'label' => 'واریز به حساب مشتری و کسر خودکار', 'detail_type' => 'none', 'detail' => '', 'sort' => 3 ),
                    array( 'id' => 'im4', 'label' => 'کسر از حقوق', 'detail_type' => 'none', 'detail' => '', 'sort' => 4 ),
                ),
            ),
            array(
                'id' => 'cat_installment_steps',
                'title' => 'فاصله گام‌های پرداخت اقساط',
                'sort' => 6,
                'options' => array(
                    array( 'id' => 's1', 'label' => 'هر ماه یک‌بار', 'detail_type' => 'number', 'detail' => '1', 'sort' => 1 ),
                    array( 'id' => 's2', 'label' => 'هر ۲ ماه یک‌بار', 'detail_type' => 'number', 'detail' => '2', 'sort' => 2 ),
                    array( 'id' => 's3', 'label' => 'هر ۳ ماه یک‌بار', 'detail_type' => 'number', 'detail' => '3', 'sort' => 3 ),
                    array( 'id' => 's6', 'label' => 'هر ۶ ماه یک‌بار', 'detail_type' => 'number', 'detail' => '6', 'sort' => 4 ),
                ),
            ),
        );
    }

    public static function get_categories() {
        $cats = get_option( 'cgs_plan_categories', null );
        if ( ! is_array( $cats ) || empty( $cats ) ) {
            $cats = self::default_categories();
            update_option( 'cgs_plan_categories', $cats, false );
        }
        usort( $cats, function( $a, $b ) { return ( $a['sort'] ?? 0 ) - ( $b['sort'] ?? 0 ); } );
        return $cats;
    }

    public static function default_styles() {
        return array(
            'card_bg'         => '#ffffff',
            'card_border'     => '#c5cae9',
            'title_color'     => '#1a237e',
            'title_size'      => '18',
            'text_color'      => '#333333',
            'accent'          => '#1a237e',
            'radius'          => '14',
            'show_icon'       => '1',
            'btn_text'        => 'انتخاب این طرح',
            'btn_bg'          => '#1a237e',
            'btn_style'       => 'solid',
            'card_shadow'     => '0 6px 24px rgba(15,23,42,0.08)',
            'glass_btn'       => '0',
            'btn_sound'       => '0',
            'vip_badge_color' => '#fbbf24',
            'star_count'      => '5',
            'star_colors'     => '#f59e0b,#f59e0b,#f59e0b,#f59e0b,#f59e0b',
            'featured_glow'   => '0',
            'featured_color'  => '#4338ca',
        );
    }

    public static function get_styles() {
        return wp_parse_args( get_option( 'cgs_plan_styles', array() ), self::default_styles() );
    }

    public static function get_plans() {
        $p = get_option( 'cgs_credit_plans', array() );
        return is_array( $p ) ? $p : array();
    }

    public static function get_plan( $id ) {
        $plans = self::get_plans();
        return isset( $plans[ $id ] ) ? $plans[ $id ] : null;
    }

    public static function page() {
        $plans = self::get_plans();
        $categories = self::get_categories();
        $styles = self::get_styles();
        $detail_types = self::detail_types();
        $designs = self::get_designs();
        include CGS_PLUGIN_DIR . 'admin/views/plans.php';
    }

    public static function ajax_save_categories() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $cats = isset( $_POST['categories'] ) ? $_POST['categories'] : array();
        if ( is_string( $cats ) ) {
            $cats = json_decode( wp_unslash( $cats ), true );
        }
        if ( ! is_array( $cats ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }
        if ( empty( $cats ) ) {
            $clean = self::default_categories();
            update_option( 'cgs_plan_categories', $clean, false );
            wp_send_json_success( array( 'categories' => $clean ) );
        }
        $clean = array();
        $csort = 1;
        foreach ( $cats as $cat ) {
            $cid = sanitize_key( $cat['id'] ?? '' );
            if ( ! $cid ) $cid = 'cat_' . time() . '_' . wp_rand( 10, 99 );
            $opts = array();
            $osort = 1;
            foreach ( (array) ( $cat['options'] ?? array() ) as $opt ) {
                $oid = sanitize_key( $opt['id'] ?? '' );
                if ( ! $oid ) $oid = 'opt_' . time() . '_' . wp_rand( 100, 999 );
                $opts[] = array(
                    'id'          => $oid,
                    'label'       => sanitize_text_field( $opt['label'] ?? '' ),
                    'detail_type' => sanitize_key( $opt['detail_type'] ?? 'none' ),
                    'detail'      => sanitize_textarea_field( $opt['detail'] ?? '' ),
                    'sort'        => $osort++,
                );
            }
            $clean[] = array(
                'id'      => $cid,
                'title'   => sanitize_text_field( $cat['title'] ?? 'دسته جدید' ),
                'sort'    => $csort++,
                'options' => $opts,
            );
        }
        update_option( 'cgs_plan_categories', $clean, false );
        wp_send_json_success( array( 'categories' => $clean ) );
    }

    public static function ajax_save_styles() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $raw = isset( $_POST['styles'] ) ? $_POST['styles'] : array();
        if ( is_string( $raw ) ) {
            $raw = json_decode( wp_unslash( $raw ), true );
        }
        $styles = wp_parse_args( is_array( $raw ) ? $raw : array(), self::default_styles() );
        foreach ( $styles as $k => $v ) {
            $styles[ $k ] = sanitize_text_field( $v );
        }
        update_option( 'cgs_plan_styles', $styles, false );
        wp_send_json_success( array( 'message' => 'ذخیره شد', 'styles' => $styles ) );
    }

    public static function ajax_save_plan() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $raw = isset( $_POST['plan'] ) ? $_POST['plan'] : array();
        if ( is_string( $raw ) ) {
            $raw = json_decode( wp_unslash( $raw ), true );
        }
        if ( ! is_array( $raw ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }

        $id = sanitize_key( $raw['id'] ?? '' );
        if ( ! $id ) $id = 'plan_' . time() . '_' . wp_rand( 100, 999 );

        $durations = array();
        foreach ( (array) ( $raw['durations'] ?? array() ) as $d ) {
            $steps = array();
            if ( ! empty( $d['steps'] ) && is_array( $d['steps'] ) ) {
                foreach ( $d['steps'] as $s ) {
                    $steps[] = array(
                        'interval'   => sanitize_text_field( $s['interval'] ?? '1' ),
                        'step_label' => sanitize_text_field( $s['step_label'] ?? '' ),
                        'rate'       => sanitize_text_field( $s['rate'] ?? '' ),
                    );
                }
            }
            // سازگاری با داده قدیم
            if ( empty( $steps ) && ( isset( $d['rate'] ) || isset( $d['step_interval'] ) ) ) {
                $steps[] = array(
                    'interval'   => sanitize_text_field( $d['step_interval'] ?? '1' ),
                    'step_label' => sanitize_text_field( $d['step_label'] ?? '' ),
                    'rate'       => sanitize_text_field( $d['rate'] ?? '' ),
                );
            }
            $durations[] = array(
                'months'        => absint( $d['months'] ?? 0 ),
                'steps'         => $steps,
                'rate'          => sanitize_text_field( $steps[0]['rate'] ?? ( $d['rate'] ?? '' ) ),
                'step_interval' => sanitize_text_field( $steps[0]['interval'] ?? ( $d['step_interval'] ?? '1' ) ),
                'step_label'    => sanitize_text_field( $steps[0]['step_label'] ?? ( $d['step_label'] ?? '' ) ),
            );
        }

        // selected options: { cat_id: { opt_id: { selected:1, detail_override:'' } } }
        $selected = array();
        if ( ! empty( $raw['selected'] ) && is_array( $raw['selected'] ) ) {
            foreach ( $raw['selected'] as $cat_id => $opts ) {
                $cat_id = sanitize_key( $cat_id );
                $selected[ $cat_id ] = array();
                foreach ( (array) $opts as $oid => $odata ) {
                    if ( is_array( $odata ) ) {
                        $selected[ $cat_id ][ sanitize_key( $oid ) ] = array(
                            'on'     => ! empty( $odata['on'] ) ? 1 : 0,
                            'detail' => sanitize_textarea_field( $odata['detail'] ?? '' ),
                        );
                    } elseif ( $odata ) {
                        $selected[ $cat_id ][ sanitize_key( $oid ) ] = array( 'on' => 1, 'detail' => '' );
                    }
                }
            }
        }

        $plan = array(
            'id'                 => $id,
            'title'              => sanitize_text_field( $raw['title'] ?? '' ),
            'description'        => sanitize_textarea_field( $raw['description'] ?? '' ),
            'icon'               => esc_url_raw( $raw['icon'] ?? '' ),
            'icon_emoji'         => sanitize_text_field( $raw['icon_emoji'] ?? '📋' ),
            'active'             => ! empty( $raw['active'] ) ? 1 : 0,
            'featured'           => ! empty( $raw['featured'] ) ? 1 : 0,
            'vip'                => ! empty( $raw['vip'] ) ? 1 : 0,
            'design_id'          => sanitize_key( $raw['design_id'] ?? '' ),
            'sort_order'         => absint( $raw['sort_order'] ?? 0 ),
            'min_amount'         => absint( $raw['min_amount'] ?? 0 ),
            'max_amount'         => absint( $raw['max_amount'] ?? 0 ),
            'durations'          => $durations,
            // facility_use_months = how long approved credit can be used (NOT repayment term)
            'facility_use_months'=> absint( $raw['facility_use_months'] ?? 12 ),
            'prepayment'         => ! empty( $raw['prepayment'] ) ? 1 : 0,
            'guarantor_required' => ! empty( $raw['guarantor_required'] ) ? 1 : 0,
            'selected'           => $selected,
            'field_keys'         => array_filter( array_map( 'sanitize_key', (array) ( $raw['field_keys'] ?? array() ) ) ),
            'custom_notes'       => sanitize_textarea_field( $raw['custom_notes'] ?? '' ),
            'status'             => in_array( ( $raw['status'] ?? 'draft' ), array( 'draft', 'published' ), true ) ? $raw['status'] : 'draft',
            'form_template_id'   => sanitize_key( $raw['form_template_id'] ?? '' ),
            'rule_amount_gt'     => absint( $raw['rule_amount_gt'] ?? 0 ),
            'rule_force_guarantor'=> ! empty( $raw['rule_force_guarantor'] ) ? 1 : 0,
            'updated_at'         => current_time( 'mysql' ),
        );

        if ( empty( $plan['title'] ) ) wp_send_json_error( 'عنوان طرح الزامی است.' );

        $plans = self::get_plans();
        $plans[ $id ] = $plan;
        update_option( 'cgs_credit_plans', $plans, false );
        wp_send_json_success( array( 'id' => $id, 'plan' => $plan ) );
    }

    public static function ajax_delete_plan() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $id = sanitize_key( $_POST['id'] ?? '' );
        $plans = self::get_plans();
        unset( $plans[ $id ] );
        update_option( 'cgs_credit_plans', $plans, false );
        wp_send_json_success();
    }

    public static function ajax_get_plan() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $plan = self::get_plan( sanitize_key( $_POST['id'] ?? '' ) );
        if ( ! $plan ) wp_send_json_error( 'یافت نشد' );
        wp_send_json_success( $plan );
    }

    public static function get_designs() {
        $d = get_option( 'cgs_plan_design_presets', array() );
        return is_array( $d ) ? $d : array();
    }

    public static function ajax_list_designs() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        wp_send_json_success( array( 'designs' => self::get_designs() ) );
    }

    public static function ajax_save_design() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( $name === '' ) {
            wp_send_json_error( 'نام قالب ظاهر الزامی است.' );
        }
        $raw = isset( $_POST['styles'] ) ? $_POST['styles'] : array();
        if ( is_string( $raw ) ) {
            $raw = json_decode( wp_unslash( $raw ), true );
        }
        $styles = wp_parse_args( is_array( $raw ) ? $raw : array(), self::default_styles() );
        foreach ( $styles as $k => $v ) {
            $styles[ $k ] = sanitize_text_field( $v );
        }
        $id = sanitize_key( $_POST['id'] ?? '' );
        if ( ! $id ) {
            $id = 'design_' . time() . '_' . wp_rand( 100, 999 );
        }
        $all = self::get_designs();
        $all[ $id ] = array(
            'id'         => $id,
            'name'       => $name,
            'styles'     => $styles,
            'updated_at' => current_time( 'mysql' ),
        );
        update_option( 'cgs_plan_design_presets', $all, false );
        wp_send_json_success( array(
            'id'      => $id,
            'designs' => $all,
            'message' => 'قالب ظاهر «' . $name . '» ذخیره شد.',
        ) );
    }

    public static function ajax_delete_design() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_settings' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $id  = sanitize_key( $_POST['id'] ?? '' );
        $all = self::get_designs();
        unset( $all[ $id ] );
        update_option( 'cgs_plan_design_presets', $all, false );
        wp_send_json_success( array( 'designs' => $all, 'message' => 'حذف شد' ) );
    }

    public static function shortcode_cards( $atts = array() ) {
        $plans  = self::get_plans();
        $styles = self::get_styles();
        $designs = self::get_designs();
        $shadow = ( $styles['card_shadow'] ?? '' ) === 'none' ? 'none' : ( $styles['card_shadow'] ?? '0 6px 24px rgba(15,23,42,0.08)' );
        $radius = absint( $styles['radius'] ?? 14 );
        $html   = '<div class="cgs-plans-public" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">';
        foreach ( $plans as $plan ) {
            if ( empty( $plan['active'] ) ) {
                continue;
            }
            $title  = esc_html( $plan['title'] ?? '' );
            $desc   = esc_html( $plan['description'] ?? '' );
            $emoji  = esc_html( $plan['icon_emoji'] ?? '📋' );
            $is_vip = ! empty( $plan['vip'] );
            $is_feat = ! empty( $plan['featured'] );
            // قالب ظاهر اختصاصی طرح
            $st = $styles;
            $did = $plan['design_id'] ?? '';
            if ( $did && ! empty( $designs[ $did ]['styles'] ) && is_array( $designs[ $did ]['styles'] ) ) {
                $st = wp_parse_args( $designs[ $did ]['styles'], $styles );
            }
            $shadow = ( ( $st['card_shadow'] ?? '' ) === 'none' ) ? 'none' : ( $st['card_shadow'] ?? $shadow );
            $radius = absint( $st['radius'] ?? $radius );
            $card_shadow = $shadow;
            $border = esc_attr( $st['card_border'] ?? '#e2e8f0' );
            if ( $is_feat && ( $st['featured_glow'] ?? '0' ) === '1' ) {
                $fc = esc_attr( $st['featured_color'] ?? '#4338ca' );
                $card_shadow = ( $shadow === 'none' ? '' : $shadow . ',' ) . '0 0 0 3px ' . $fc . '55,0 0 28px ' . $fc . '66';
                $border = $fc;
            }
            $html .= '<div class="cgs-plan-card-public" style="border-radius:' . $radius . 'px;border:1px solid ' . $border . ';padding:20px;background:' . esc_attr( $st['card_bg'] ?? '#fff' ) . ';box-shadow:' . esc_attr( $card_shadow ) . ';color:' . esc_attr( $st['text_color'] ?? '#333' ) . ';">';
            if ( ( $st['show_icon'] ?? '1' ) !== '0' ) {
                if ( ! empty( $plan['icon'] ) ) {
                    $html .= '<div style="margin-bottom:8px;"><img src="' . esc_url( $plan['icon'] ) . '" alt="" style="max-height:40px;"></div>';
                } else {
                    $html .= '<div style="font-size:28px;margin-bottom:8px;">' . $emoji . '</div>';
                }
            }
            $vip_c = esc_attr( $st['vip_badge_color'] ?? '#fbbf24' );
            $badges = '';
            if ( $is_vip ) {
                $badges .= '<span style="background:' . $vip_c . ';color:#78350f;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;margin-left:4px;">VIP</span>';
            }
            if ( $is_feat ) {
                $star_n = max( 1, min( 10, absint( $st['star_count'] ?? 5 ) ) );
                $cols   = array_map( 'trim', explode( ',', $st['star_colors'] ?? '#f59e0b' ) );
                for ( $i = 0; $i < $star_n; $i++ ) {
                    $c = esc_attr( $cols[ $i ] ?? ( $cols[0] ?? '#f59e0b' ) );
                    $badges .= '<span style="color:' . $c . ';font-size:16px;">★</span>';
                }
            }
            if ( $badges ) {
                $html .= '<div style="margin-bottom:6px;">' . $badges . '</div>';
            }
            $html .= '<h3 style="margin:0 0 8px;color:' . esc_attr( $st['title_color'] ?? '#1a237e' ) . ';font-size:' . absint( $st['title_size'] ?? 18 ) . 'px;">' . $title . '</h3>';
            $html .= '<p style="color:' . esc_attr( $st['text_color'] ?? '#475569' ) . ';font-size:13px;line-height:1.7;">' . $desc . '</p>';
            if ( ! empty( $plan['durations'] ) ) {
                foreach ( $plan['durations'] as $d ) {
                    $html .= '<div class="cgs-pc-period" style="background:linear-gradient(135deg,#eef2ff,#f8fafc);border:1px solid #c5cae9;border-radius:10px;padding:10px 12px;margin:8px 0;">';
                    $html .= '<strong>' . absint( $d['months'] ) . ' ماه</strong>';
                    if ( ! empty( $d['steps'] ) && is_array( $d['steps'] ) ) {
                        $html .= '<ul style="margin:6px 0 0 14px;font-size:12.5px;">';
                        foreach ( $d['steps'] as $s ) {
                            $lab  = esc_html( $s['step_label'] ?? ( 'هر ' . ( $s['interval'] ?? '' ) . ' ماه' ) );
                            $rate = esc_html( $s['rate'] ?? '—' );
                            $html .= '<li>' . $lab . ' — سود <b>' . $rate . '٪</b></li>';
                        }
                        $html .= '</ul>';
                    } else {
                        $html .= ' · سود ' . esc_html( $d['rate'] ?? '—' ) . '٪';
                    }
                    $html .= '</div>';
                }
            }
            $glass = ( ( $st['glass_btn'] ?? '0' ) === '1' ) || ( ( $st['btn_style'] ?? '' ) === 'glass' );
            $btn_bg = $glass ? 'rgba(255,255,255,0.35)' : esc_attr( $st['btn_bg'] ?? '#1a237e' );
            $btn_color = $glass ? esc_attr( $st['accent'] ?? '#1a237e' ) : '#fff';
            $btn_border = $glass ? '1.5px solid rgba(255,255,255,0.55)' : 'none';
            $html .= '<button type="button" class="cgs-plan-select-btn" style="margin-top:10px;width:100%;background:' . $btn_bg . ';color:' . $btn_color . ';border:' . $btn_border . ';border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;">' . esc_html( $st['btn_text'] ?? 'انتخاب این طرح' ) . '</button>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

}
