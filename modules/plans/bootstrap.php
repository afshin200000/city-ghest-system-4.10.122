<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! function_exists( 'cgs_module_plans_enabled' ) ) {
    function cgs_module_plans_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) $flags = array();
        if ( ! array_key_exists( 'plans', $flags ) ) return true;
        return ! empty( $flags['plans'] );
    }
}
if ( ! cgs_module_plans_enabled() ) { return; }

/**
 * ماژول مستقل طرح‌ها و روش‌ها
 * modules/plans/
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGS_PLANS_MODULE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'CGS_PLANS_MODULE_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

/**
 * دارایی کمکی: باز کردن قفل دسته‌ها + تازه کردن لیست قالب‌ها
 */
function cgs_plans_module_assets( $hook ) {
    if ( false === strpos( (string) $hook, 'cgs-plans' ) && false === strpos( (string) $hook, 'plans' ) ) {
        // فقط صفحه طرح‌ها
        if ( empty( $_GET['page'] ) || $_GET['page'] !== 'cgs-plans' ) {
            return;
        }
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    if ( $page !== 'cgs-plans' ) {
        return;
    }
    wp_enqueue_script(
        'cgs-plans-module',
        CGS_PLANS_MODULE_URL . 'assets/plans-module.js',
        array( 'jquery', 'cgs-plans-admin' ),
        defined( 'CGS_VERSION' ) ? CGS_VERSION : '1.0',
        true
    );
    wp_add_inline_style( 'cgs-admin', '
#tab-cats input, #tab-cats select, #tab-cats textarea, #tab-cats button { pointer-events:auto!important;z-index:20!important;position:relative!important; }
#plan_design_id { min-height:38px; font-size:13px; }
' );
}
add_action( 'admin_enqueue_scripts', 'cgs_plans_module_assets', 40 );
