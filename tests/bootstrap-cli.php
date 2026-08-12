<?php
/**
 * Minimal WP stubs for CLI unit tests.
 */
define( 'ABSPATH', __DIR__ . '/../' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'CGS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

if ( ! function_exists( 'wp_cache_get' ) ) {
    $GLOBALS['cgs_test_cache'] = array();
    function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
        $k = $group . ':' . $key;
        if ( array_key_exists( $k, $GLOBALS['cgs_test_cache'] ) ) {
            $found = true;
            return $GLOBALS['cgs_test_cache'][ $k ];
        }
        $found = false;
        return false;
    }
    function wp_cache_set( $key, $val, $group = '', $ttl = 0 ) {
        $GLOBALS['cgs_test_cache'][ $group . ':' . $key ] = $val;
        return true;
    }
    function wp_cache_delete( $key, $group = '' ) {
        unset( $GLOBALS['cgs_test_cache'][ $group . ':' . $key ] );
        return true;
    }
    function get_transient( $k ) { return false; }
    function set_transient( $k, $v, $t = 0 ) { return true; }
    function delete_transient( $k ) { return true; }
}

require_once CGS_PLUGIN_DIR . 'includes/class-cache.php';
require_once CGS_PLUGIN_DIR . 'includes/class-validation.php';
require_once CGS_PLUGIN_DIR . 'includes/class-modules.php';
require_once CGS_PLUGIN_DIR . 'includes/class-conditional-logic.php';
require_once CGS_PLUGIN_DIR . 'includes/data/locations.php';

require_once CGS_PLUGIN_DIR . 'includes/class-installment-calculator.php';

require_once CGS_PLUGIN_DIR . 'includes/class-settlement.php';

require_once CGS_PLUGIN_DIR . 'includes/class-credit-risk.php';

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = array() ) {
        if ( is_object( $args ) ) $args = get_object_vars( $args );
        if ( ! is_array( $args ) ) $args = array();
        return array_merge( $defaults, $args );
    }
}
if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
    function wp_using_ext_object_cache() { return false; }
}
if ( ! function_exists( 'absint' ) ) {
    function absint( $n ) { return abs( (int) $n ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $k, $d = false ) { return $d; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $k, $v, $a = true ) { return true; }
}
