<?php
/**
 * ماژول: احراز هویت JWT + درگاه API امن + بازیابی رمز
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cgs_module_api_auth_enabled' ) ) {
	function cgs_module_api_auth_enabled() {
		$flags = get_option( 'cgs_module_flags', array() );
		if ( ! is_array( $flags ) ) {
			$flags = array();
		}
		if ( ! array_key_exists( 'api_auth', $flags ) ) {
			return true;
		}
		return ! empty( $flags['api_auth'] );
	}
}

if ( ! cgs_module_api_auth_enabled() ) {
	return;
}

require_once __DIR__ . '/includes/class-jwt.php';
require_once __DIR__ . '/includes/class-api-gateway.php';

CGS_API_Gateway::init();

// Flush rewrite once when module first loads with new rules
add_action( 'admin_init', function () {
	if ( get_option( 'cgs_api_auth_rewrite' ) !== '1.0' ) {
		flush_rewrite_rules( false );
		update_option( 'cgs_api_auth_rewrite', '1.0', false );
	}
} );
