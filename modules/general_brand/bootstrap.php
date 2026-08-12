<?php
/**
 * ماژول: عمومی و برند
 * لایه نازک — رفتار قبلی را حفظ می‌کند؛ فقط مرز ماژول و قابلیت فعال/غیرفعال.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cgs_module_general_brand_enabled' ) ) {
    /**
     * @return bool
     */
    function cgs_module_general_brand_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) {
            $flags = array();
        }
        // پیش‌فرض: روشن (تا چیزی نشکند)
        if ( ! array_key_exists( 'general_brand', $flags ) ) {
            return true;
        }
        return ! empty( $flags['general_brand'] );
    }
}

if ( ! cgs_module_general_brand_enabled() ) {
    return;
}


// کلاس هسته از قبل توسط autoload / CGS_Modules بارگذاری می‌شود.
// این فایل فقط مرز ماژول و پرچم فعال‌سازی را تعریف می‌کند.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    // error_log( '[CGS] module general_brand ready' );
}

