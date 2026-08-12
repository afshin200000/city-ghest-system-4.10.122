<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper functions for City Ghest System
 */

/**
 * Get plugin option with default
 */
/**
 * بارگذاری یک‌باره تنظیمات در هر درخواست (کاهش کوئری get_option)
 */
function cgs_get_settings_all( $force_reload = false ) {
    static $opts = null;
    if ( $force_reload ) {
        $opts = null;
    }
    if ( null !== $opts ) {
        return $opts;
    }
    $loaded = get_option( 'cgs_settings', array() );
    $opts = is_array( $loaded ) ? $loaded : array();
    return $opts;
}

/**
 * Get plugin option with default
 */
function cgs_get_option( $key, $default = '' ) {
    $options = cgs_get_settings_all();
    return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
}

/**
 * Update a single plugin option
 */
function cgs_update_option( $key, $value ) {
    $options = cgs_get_settings_all();
    $options[ $key ] = $value;
    $ok = update_option( 'cgs_settings', $options, false );
    // همگام‌سازی کش استاتیک با مقدار ذخیره‌شده
    cgs_get_settings_all( true );
    return $ok;
}

/**
 * Application types (modular - can be extended later)
 */
function cgs_get_application_types() {
    $types = array(
        'representative' => array(
            'label'       => 'نماینده',
            'label_plural'=> 'نمایندگان',
            'icon'        => 'dashicons-location-alt',
            'color'       => '#1e88e5',
        ),
        'seller' => array(
            'label'       => 'فروشنده / ارائه‌دهنده کالا و خدمات',
            'label_plural'=> 'فروشندگان و ارائه‌دهندگان',
            'icon'        => 'dashicons-store',
            'color'       => '#43a047',
        ),
        'marketer' => array(
            'label'       => 'بازاریاب',
            'label_plural'=> 'بازاریابان',
            'icon'        => 'dashicons-megaphone',
            'color'       => '#fb8c00',
        ),
        'investor' => array(
            'label'       => 'سرمایه‌گذار',
            'label_plural'=> 'سرمایه‌گذاران',
            'icon'        => 'dashicons-chart-line',
            'color'       => '#8e24aa',
        ),
        'credit' => array(
            'label'       => 'متقاضی اعتبار',
            'label_plural'=> 'متقاضیان اعتبار',
            'icon'        => 'dashicons-money-alt',
            'color'       => '#e53935',
        ),
    );

    // مخاطبین سفارشی — بدون تکرار برچسب یا کلیدهای سیستمی
    $custom = function_exists( 'cgs_get_option' ) ? cgs_get_option( 'custom_audiences', array() ) : array();
    $used_labels = array();
    foreach ( $types as $t ) {
        $used_labels[ mb_strtolower( trim( $t['label'] ) ) ] = true;
    }
    $reserved = array_keys( $types );
    // alias قدیمی
    if ( isset( $types['credit'] ) && ! isset( $types['applicant'] ) ) {
        // فقط credit نگه داشته می‌شود
    }
    if ( is_array( $custom ) ) {
        foreach ( $custom as $row ) {
            if ( empty( $row['active'] ) ) {
                continue;
            }
            $key = sanitize_key( $row['key'] ?? '' );
            $label = sanitize_text_field( $row['label'] ?? '' );
            if ( ! $key || ! $label ) {
                continue;
            }
            // جلوگیری از تکرار متقاضی اعتبار و کلیدهای سیستمی
            if ( in_array( $key, $reserved, true ) || $key === 'applicant' ) {
                continue;
            }
            $lk = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $label ) ) : strtolower( trim( $label ) );
            if ( isset( $used_labels[ $lk ] ) ) {
                continue;
            }
            $types[ $key ] = array(
                'label'        => $label,
                'label_plural' => $label,
                'icon'         => sanitize_key( $row['icon'] ?? 'user' ),
                'color'        => sanitize_hex_color( $row['color'] ?? '#1a237e' ) ?: '#1a237e',
            );
            $used_labels[ $lk ] = true;
        }
    }
    // حذف alias تکراری applicant اگر credit هست
    if ( isset( $types['credit'], $types['applicant'] ) ) {
        unset( $types['applicant'] );
    }
    return apply_filters( 'cgs_application_types', $types );
}

/**
 * Get single application type
 */
function cgs_get_application_type( $type ) {
    $types = cgs_get_application_types();
    return isset( $types[ $type ] ) ? $types[ $type ] : null;
}

/**
 * Status labels
 */
function cgs_get_statuses() {
    return array(
        'pending'  => array( 'label' => 'در انتظار بررسی', 'color' => '#f9a825' ),
        'approved' => array( 'label' => 'تأیید شده',     'color' => '#2e7d32' ),
        'rejected' => array( 'label' => 'رد شده',        'color' => '#c62828' ),
        'review'   => array( 'label' => 'در حال بررسی',  'color' => '#0277bd' ),
    );
}

/**
 * Sanitize phone number (Iranian format)
 */
function cgs_sanitize_phone( $phone ) {
    $phone = preg_replace( '/[^0-9]/', '', $phone );
    if ( strpos( $phone, '98' ) === 0 ) {
        $phone = '0' . substr( $phone, 2 );
    }
    return $phone;
}

/**
 * Check if current user is a CG member
 */
function cgs_is_member( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }
    return in_array( 'cg_member', (array) $user->roles, true );
}

/**
 * Redirect helper (safe)
 */
function cgs_redirect( $url ) {
    wp_safe_redirect( $url );
    exit;
}

/**
 * Get member dashboard URL
 */
function cgs_get_dashboard_url() {
    $page_id = cgs_get_option( 'dashboard_page_id' );
    if ( $page_id ) {
        return get_permalink( $page_id );
    }
    return home_url( '/dashboard/' );
}

/**
 * Get login page URL (custom)
 */
function cgs_get_login_url() {
    $page_id = cgs_get_option( 'login_page_id' );
    if ( $page_id ) {
        return get_permalink( $page_id );
    }
    return home_url( '/login/' );
}

/**
 * Simple Jalali date converter (lightweight)
 * Returns formatted date based on admin setting
 */
function cgs_format_date( $timestamp = null, $format = null ) {
    if ( ! $timestamp ) {
        $timestamp = current_time( 'timestamp' );
    }
    if ( is_string( $timestamp ) && ! is_numeric( $timestamp ) ) {
        $timestamp = strtotime( $timestamp );
    }

    $calendar = cgs_get_option( 'date_calendar', 'jalali' );

    if ( $calendar === 'gregorian' ) {
        $format = $format ?: 'Y/m/d H:i';
        return date_i18n( $format, $timestamp );
    }

    // Jalali
    require_once CGS_PLUGIN_DIR . 'includes/lib/jalali.php';
    $format = $format ?: 'Y/m/d H:i';
    return cgs_jdate( $format, $timestamp );
}

/**
 * Generate unique application code
 */
function cgs_generate_code( $prefix = 'CG' ) {
    return $prefix . '-' . strtoupper( wp_generate_password( 8, false, false ) );
}

/**
 * Status labels (customizable)
 */
function cgs_get_status_labels() {
    $defaults = array(
        'pending'  => 'در انتظار بررسی',
        'review'   => 'در حال بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    );
    $custom = cgs_get_option( 'status_labels', array() );
    if ( ! is_array( $custom ) ) $custom = array();
    return wp_parse_args( $custom, $defaults );
}

/**
 * Application type labels (customizable)
 */
function cgs_get_type_labels() {
    // منبع حقیقت: انواع فعال (شامل مخاطبین سفارشی فعال)
    $types = function_exists( 'cgs_get_application_types' ) ? cgs_get_application_types() : array();
    $labels = array();
    foreach ( (array) $types as $k => $info ) {
        if ( is_array( $info ) ) {
            $labels[ $k ] = $info['label_plural'] ?? ( $info['label'] ?? $k );
        } else {
            $labels[ $k ] = $info;
        }
    }
    // برچسب‌های دستی فقط برای کلیدهای موجود (نه اضافه کردن حذف‌شده‌ها)
    $custom = cgs_get_option( 'type_labels', array() );
    if ( is_array( $custom ) ) {
        foreach ( $custom as $k => $v ) {
            if ( isset( $labels[ $k ] ) && $v !== '' ) {
                $labels[ $k ] = $v;
            }
        }
    }
    if ( empty( $labels ) ) {
        $labels = array(
            'representative' => 'نمایندگان',
            'seller'         => 'فروشندگان',
            'marketer'       => 'بازاریابان',
            'investor'       => 'سرمایه‌گذاران',
            'credit'         => 'متقاضیان اعتبار',
        );
    }
    return $labels;
}

/**
 * Status chart colors
 */
function cgs_get_status_colors() {
    $defaults = array(
        'pending'  => '#ffc107',
        'review'   => '#2196f3',
        'approved' => '#4caf50',
        'rejected' => '#f44336',
    );
    $custom = cgs_get_option( 'status_colors', array() );
    if ( ! is_array( $custom ) ) $custom = array();
    return wp_parse_args( $custom, $defaults );
}

/**
 * Type chart colors (ordered list)
 */
function cgs_get_type_colors() {
    $defaults = array( '#1a237e', '#3949ab', '#5c6bc0', '#7986cb', '#9fa8da' );
    $custom = cgs_get_option( 'type_colors', array() );
    if ( ! is_array( $custom ) || count( $custom ) < 5 ) {
        return $defaults;
    }
    return array_values( $custom );
}

/**
 * CRM stage labels
 */
function cgs_get_crm_stage_labels() {
    $defaults = array(
        'lead'      => 'سرنخ',
        'contacted' => 'تماس گرفته‌شده',
        'qualified' => 'واجد شرایط',
        'proposal'  => 'پیشنهاد / قرارداد',
        'won'       => 'موفق',
        'lost'      => 'از دست‌رفته',
    );
    $custom = cgs_get_option( 'crm_stage_labels', array() );
    if ( ! is_array( $custom ) ) $custom = array();
    return wp_parse_args( $custom, $defaults );
}

/**
 * CRM stage colors
 */
function cgs_get_crm_stage_colors() {
    $defaults = array(
        'lead'      => '#90caf9',
        'contacted' => '#64b5f6',
        'qualified' => '#42a5f5',
        'proposal'  => '#1e88e5',
        'won'       => '#43a047',
        'lost'      => '#e53935',
    );
    $custom = cgs_get_option( 'crm_stage_colors', array() );
    if ( ! is_array( $custom ) ) $custom = array();
    return wp_parse_args( $custom, $defaults );
}

/**
 * Advanced chart formatting options
 */
function cgs_get_chart_format() {
    if ( function_exists( 'cgs_get_settings_all' ) ) { cgs_get_settings_all( true ); }
    $defaults = array(
        'status_type'     => 'doughnut',   // doughnut | pie | bar | polarArea
        'types_type'      => 'bar',        // bar | horizontalBar | pie | doughnut
        'trend_type'      => 'line',       // line | bar
        'legend_position' => 'bottom',     // top | bottom | left | right
        'show_legend'     => '1',
        'animation'       => '1',
        'anim_duration'   => '800',
        'cutout'          => '55',         // doughnut hole %
        'border_width'    => '2',
        'border_color'    => '#ffffff',
        'bar_radius'      => '6',
        'line_tension'    => '0.35',
        'line_fill'       => '1',
        'point_radius'    => '3',
        'show_grid'       => '1',
        'show_title'      => '1',
        'title_status'    => 'وضعیت درخواست‌ها',
        'title_types'     => 'انواع درخواست',
        'title_trend'     => 'روند ۱۴ روز اخیر',
        'title_crm'       => 'قیف فروش CRM',
        'font_size'       => '11',
        'font_family'     => 'Vazirmatn, Tahoma, sans-serif',
        'aspect_ratio'    => '1.2',
        'tooltip_rtl'     => '1',
    );
    $custom = cgs_get_option( 'chart_format', array() );
    if ( ! is_array( $custom ) ) {
        $custom = array();
    }
    // پشتیبان مستقیم از options جدول اگر کش خالی بود
    if ( empty( $custom ) ) {
        $all = get_option( 'cgs_settings', array() );
        if ( is_array( $all ) && ! empty( $all['chart_format'] ) && is_array( $all['chart_format'] ) ) {
            $custom = $all['chart_format'];
        }
    }
    $bak = get_option( 'cgs_chart_format_v', array() );
    if ( is_array( $bak ) && ! empty( $bak ) ) {
        $custom = wp_parse_args( $bak, is_array( $custom ) ? $custom : array() );
    }
    return wp_parse_args( $custom, $defaults );
}

/**
 * Advanced Jalali calendar settings
 */
function cgs_get_jalali_settings() {
    $defaults = array(
        'enabled'           => '1',
        'calendar_type'     => 'jalali',      // jalali | gregorian | both
        'start_year'        => '1320',
        'end_year'          => '1410',
        'default_today'     => '0',
        'show_today_btn'    => '1',
        'show_clear_btn'    => '1',
        'close_on_select'   => '1',
        'format'            => 'YYYY/MM/DD',  // display format
        'min_age'           => '',            // e.g. 18 for birth date
        'max_age'           => '',
        'week_start'        => '6',           // 6 = Saturday (Iran)
        'show_holidays'     => '0',
        'locale_numbers'    => '0',           // Persian digits
        'month_dropdown'    => '1',
        'year_dropdown'     => '1',
        'theme'             => 'default',     // default | dark | gold
        'position'          => 'auto',        // auto | bottom | top
    );
    $custom = cgs_get_option( 'jalali_settings', array() );
    if ( ! is_array( $custom ) ) {
        $custom = array();
    }
    return wp_parse_args( $custom, $defaults );
}
