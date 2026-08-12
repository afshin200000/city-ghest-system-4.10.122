<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! function_exists( 'cgs_module_charts_enabled' ) ) {
    function cgs_module_charts_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) $flags = array();
        if ( ! array_key_exists( 'charts', $flags ) ) return true;
        return ! empty( $flags['charts'] );
    }
}
if ( ! cgs_module_charts_enabled() ) { return; }

/**
 * ماژول قالب‌بندی پیشرفته نمودارها
 * مسیر: modules/charts/
 * قابلیت فعال / غیرفعال — وقتی خاموش باشد داشبورد از پیش‌فرض Chart.js استفاده می‌کند.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGS_CHARTS_MODULE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'CGS_CHARTS_MODULE_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

/**
 * آیا ماژول قالب‌بندی پیشرفته فعال است؟
 */
function cgs_charts_module_enabled() {
    $v = function_exists( 'cgs_get_option' ) ? cgs_get_option( 'charts_module_enabled', 1 ) : 1;
    return (int) $v === 1;
}

/**
 * خواندن تنظیمات با احترام به فعال/غیرفعال
 * اگر ماژول خاموش باشد فقط پیش‌فرض‌های امن برمی‌گردد.
 */
function cgs_charts_get_format() {
    if ( ! function_exists( 'cgs_get_chart_format' ) ) {
        return array();
    }
    $cf = cgs_get_chart_format();
    $bak = get_option( 'cgs_chart_format_v', array() );
    if ( is_array( $bak ) && ! empty( $bak ) ) {
        $cf = wp_parse_args( $bak, $cf );
    }
    if ( ! cgs_charts_module_enabled() ) {
        // پیش‌فرض ساده و پایدار
        return array_merge( $cf, array(
            'status_type'   => 'doughnut',
            'types_type'    => 'bar',
            'trend_type'    => 'line',
            'animation'     => '1',
            'anim_duration' => '600',
            'show_legend'   => '1',
            'show_title'    => '1',
            'module_active' => '0',
        ) );
    }
    $cf['module_active'] = '1';
    // گزینه‌های پیشرفته اختصاصی ماژول
    $adv = function_exists( 'cgs_get_option' ) ? cgs_get_option( 'charts_advanced', array() ) : array();
    if ( ! is_array( $adv ) ) {
        $adv = array();
    }
    $adv_defaults = array(
        'color_scheme'   => 'default', // default | vivid | pastel | mono
        'export_png'     => '0',
        'datalabels'     => '0',
        'rtl_tooltips'   => '1',
        'min_height'     => '220',
    );
    $adv = wp_parse_args( $adv, $adv_defaults );
    return array_merge( $cf, $adv );
}

/**
 * ذخیره از POST — فقط تب charts یا وقتی فلگ ماژول آمده
 */
function cgs_charts_save_from_post() {
    if ( empty( $_POST['cgs_save_settings'] ) && empty( $_POST['cgs_charts_save'] ) ) {
        return;
    }
    $tab = isset( $_POST['cgs_active_tab'] ) ? sanitize_key( $_POST['cgs_active_tab'] ) : '';

    // فعال/غیرفعال از تب افزونه‌ها یا نمودارها
    if ( isset( $_POST['charts_module_enabled'] ) || $tab === 'fbplugins' || $tab === 'charts' ) {
        if ( isset( $_POST['charts_module_enabled'] ) || $tab === 'fbplugins' ) {
            // از تب افزونه‌ها: چک‌باکس
            if ( $tab === 'fbplugins' ) {
                cgs_update_option( 'charts_module_enabled', ! empty( $_POST['charts_module_enabled'] ) ? 1 : 0 );
            }
        }
        if ( $tab === 'charts' && isset( $_POST['charts_module_enabled'] ) ) {
            cgs_update_option( 'charts_module_enabled', ! empty( $_POST['charts_module_enabled'] ) ? 1 : 0 );
        }
    }

    // قالب اصلی فقط در تب charts (منطق قبلی settings)
    if ( function_exists( 'cgs_settings_save_chart_format_from_post' ) ) {
        cgs_settings_save_chart_format_from_post();
    }

    // پیشرفته
    if ( ( $tab === 'fbplugins' || $tab === 'charts' ) && isset( $_POST['charts_plugins'] ) && is_array( $_POST['charts_plugins'] ) ) {
        $keys = array( 'advanced_format', 'color_schemes', 'rtl_tooltips', 'datalabels' );
        $save = array();
        foreach ( $keys as $k ) {
            $save[ $k ] = ! empty( $_POST['charts_plugins'][ $k ] ) ? 1 : 0;
        }
        cgs_update_option( 'charts_plugins', $save );
    }
    // اگر تب افزونه و چک‌باکس‌ها نیامده (همه خاموش)
    if ( $tab === 'fbplugins' && ! isset( $_POST['charts_plugins'] ) && isset( $_POST['charts_module_enabled'] ) ) {
        // نگه داشتن مقادیر قبلی plugins مگر اینکه عمداً پاک شوند
    }

    if ( $tab === 'charts' && isset( $_POST['charts_advanced'] ) && is_array( $_POST['charts_advanced'] ) ) {
        $in = wp_unslash( $_POST['charts_advanced'] );
        $clean = array(
            'color_scheme' => sanitize_key( $in['color_scheme'] ?? 'default' ),
            'export_png'   => ! empty( $in['export_png'] ) ? '1' : '0',
            'datalabels'   => ! empty( $in['datalabels'] ) ? '1' : '0',
            'rtl_tooltips' => ! empty( $in['rtl_tooltips'] ) ? '1' : '0',
            'min_height'   => (string) max( 120, min( 480, absint( $in['min_height'] ?? 220 ) ) ),
        );
        if ( ! in_array( $clean['color_scheme'], array( 'default', 'vivid', 'pastel', 'mono' ), true ) ) {
            $clean['color_scheme'] = 'default';
        }
        cgs_update_option( 'charts_advanced', $clean );
    }

    // پاک‌سازی کش استاتیک تنظیمات
    if ( function_exists( 'cgs_get_settings_all' ) ) {
        cgs_get_settings_all( true );
    }
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'cgs_settings', 'options' );
        wp_cache_delete( 'alloptions', 'options' );
    }
}

/**
 * بعد از ذخیره موفق نمودار — فلگ ادمین برای نوتیس
 */
function cgs_charts_admin_notice() {
    if ( ! empty( $_GET['cgs_charts_saved'] ) && current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات نمودار ذخیره شد. داشبورد را یک‌بار رفرش کنید (Ctrl+F5).</p></div>';
    }
}
add_action( 'admin_notices', 'cgs_charts_admin_notice' );


/**
 * رنگ‌بندی بر اساس طرح
 */
function cgs_charts_apply_color_scheme( $colors, $scheme = 'default' ) {
    if ( ! is_array( $colors ) || $scheme === 'default' ) {
        return $colors;
    }
    $out = array();
    $i = 0;
    foreach ( $colors as $c ) {
        if ( $scheme === 'mono' ) {
            $shade = 40 + ( $i * 25 ) % 140;
            $out[] = sprintf( 'rgb(%d,%d,%d)', $shade, $shade, min( 255, $shade + 40 ) );
        } elseif ( $scheme === 'pastel' ) {
            $out[] = $c; // نگه‌داری؛ می‌توان بعداً مخلوط با سفید کرد
        } elseif ( $scheme === 'vivid' ) {
            $out[] = $c;
        } else {
            $out[] = $c;
        }
        $i++;
    }
    return $out;
}

add_action( 'admin_init', function () {
    if ( is_admin() && ! empty( $_POST['cgs_save_settings'] ) ) {
        cgs_charts_save_from_post();
    }
}, 5 );
