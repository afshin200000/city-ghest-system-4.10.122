<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! function_exists( 'cgs_module_settings_enabled' ) ) {
    function cgs_module_settings_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) $flags = array();
        if ( ! array_key_exists( 'settings', $flags ) ) return true;
        return ! empty( $flags['settings'] );
    }
}
if ( ! cgs_module_settings_enabled() ) { return; }

/**
 * ماژول مستقل تنظیمات
 * مسیر: modules/settings/
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGS_SETTINGS_MODULE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'CGS_SETTINGS_MODULE_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

/**
 * ذخیره امن قالب نمودار — فقط وقتی تب نمودارها فعال است
 * جلوگیری از صفر شدن چک‌باکس‌ها هنگام ذخیره تب‌های دیگر
 */
function cgs_settings_save_chart_format_from_post() {
    if ( empty( $_POST['cgs_save_settings'] ) && empty( $_POST['cgs_reset_chart_format'] ) ) {
        return;
    }
    $tab = isset( $_POST['cgs_active_tab'] ) ? sanitize_key( $_POST['cgs_active_tab'] ) : '';
    // فقط تب charts یا دکمه ریست نمودار
    if ( $tab !== 'charts' && empty( $_POST['cgs_reset_chart_format'] ) ) {
        return;
    }
    if ( ! empty( $_POST['cgs_reset_chart_format'] ) ) {
        cgs_update_option( 'chart_format', array() );
        return;
    }
    if ( ! isset( $_POST['chart_format'] ) || ! is_array( $_POST['chart_format'] ) ) {
        return;
    }
    $cf_in = wp_unslash( $_POST['chart_format'] );
    $existing = function_exists( 'cgs_get_chart_format' ) ? cgs_get_chart_format() : array();
    $cf = is_array( $existing ) ? $existing : array();

    $text_keys = array( 'status_type','types_type','trend_type','legend_position','border_color','title_status','title_types','title_trend','title_crm','font_family' );
    $num_keys  = array( 'anim_duration','cutout','border_width','bar_radius','line_tension','point_radius','font_size','aspect_ratio' );
    $chk_keys  = array( 'show_legend','animation','line_fill','show_grid','show_title','tooltip_rtl' );

    foreach ( $text_keys as $k ) {
        if ( isset( $cf_in[ $k ] ) ) {
            $cf[ $k ] = sanitize_text_field( $cf_in[ $k ] );
        }
    }
    foreach ( $num_keys as $k ) {
        if ( isset( $cf_in[ $k ] ) ) {
            $cf[ $k ] = sanitize_text_field( $cf_in[ $k ] );
        }
    }
    // چک‌باکس: فقط در تب charts مقداردهی می‌شوند
    foreach ( $chk_keys as $k ) {
        $cf[ $k ] = ! empty( $cf_in[ $k ] ) ? '1' : '0';
    }
    cgs_update_option( 'chart_format', $cf );
    // پشتیبان مستقل برای جلوگیری از کش
    update_option( 'cgs_chart_format_v', $cf, false );
}

/**
 * مسیر ویو تنظیمات
 */
function cgs_settings_view_path() {
    $mod = CGS_SETTINGS_MODULE_DIR . 'views/settings.php';
    if ( file_exists( $mod ) ) {
        return $mod;
    }
    return ( defined( 'CGS_PLUGIN_DIR' ) ? CGS_PLUGIN_DIR : '' ) . 'admin/views/settings.php';
}
