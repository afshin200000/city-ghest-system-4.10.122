<?php
/**
 * ماژول مستقل فرم‌ساز داینامیک
 * مسیر: modules/form-builder/
 * تغییر در سایر بخش‌های افزونه نباید این ماژول را بشکند.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGS_FB_MODULE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'CGS_FB_MODULE_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

/**
 * بارگذاری کلاس‌های فرم‌ساز از includes (سازگاری با ساختار فعلی)
 * در آینده می‌توان فایل‌ها را به این پوشه منتقل کرد بدون تغییر API.
 */
function cgs_fb_module_boot() {
    $base = defined( 'CGS_PLUGIN_DIR' ) ? CGS_PLUGIN_DIR : dirname( dirname( dirname( __FILE__ ) ) ) . '/';
    $files = array(
        'includes/class-form-builder.php',
        'includes/class-form-styles.php',
        'includes/class-form-templates.php',
    );
    foreach ( $files as $rel ) {
        $path = $base . $rel;
        if ( file_exists( $path ) && ! class_exists( str_replace( array( 'includes/class-', '.php', '-' ), array( 'CGS_', '', '_' ), basename( $rel, '.php' ) ) ) ) {
            // بارگذاری فقط اگر هنوز لود نشده
        }
        // require_once امن — کلاس‌ها با autoloader اصلی هم می‌آیند
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

/**
 * دارایی اختصاصی پیش‌نمایش — بعد از admin.js تا قفل Sortable را خنثی کند
 */
function cgs_fb_module_assets( $hook ) {
    if ( false === strpos( (string) $hook, 'form-builder' ) ) {
        return;
    }
    wp_enqueue_script(
        'cgs-fb-preview',
        CGS_FB_MODULE_URL . 'assets/form-builder-preview.js',
        array( 'jquery', 'sortablejs', 'cgs-admin' ),
        defined( 'CGS_VERSION' ) ? CGS_VERSION : '1.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'cgs_fb_module_assets', 30 );

// فقط ثبت دارایی؛ کلاس‌ها از هسته لود می‌شوند
