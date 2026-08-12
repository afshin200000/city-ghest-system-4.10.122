<?php
/**
 * ماژول: امنیت
 * لایه نازک — رفتار قبلی را حفظ می‌کند؛ فقط مرز ماژول و قابلیت فعال/غیرفعال.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cgs_module_security_enabled' ) ) {
    /**
     * @return bool
     */
    function cgs_module_security_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) {
            $flags = array();
        }
        // پیش‌فرض: روشن (تا چیزی نشکند)
        if ( ! array_key_exists( 'security', $flags ) ) {
            return true;
        }
        return ! empty( $flags['security'] );
    }
}

if ( ! cgs_module_security_enabled() ) {
    return;
}


// کلاس هسته از قبل توسط autoload / CGS_Modules بارگذاری می‌شود.
// این فایل فقط مرز ماژول و پرچم فعال‌سازی را تعریف می‌کند.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    // error_log( '[CGS] module security ready' );
}

