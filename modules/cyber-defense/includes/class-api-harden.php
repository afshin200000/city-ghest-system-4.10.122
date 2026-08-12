<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_API_Harden {
	public static function init() {
		add_filter( 'xmlrpc_enabled', '__return_false', 99 );
		add_filter( 'xmlrpc_methods', '__return_empty_array', 99 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'rest_gate' ), 20 );
		add_filter( 'rest_endpoints', array( __CLASS__, 'filter_endpoints' ), 99 );
		add_action( 'init', array( __CLASS__, 'block_author_enum' ), 1 );
	}
	public static function rest_gate( $result ) {
		if ( true === $result || is_wp_error( $result ) ) { return $result; }
		if ( is_user_logged_in() ) { return $result; }
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
		if ( preg_match( '#/wp/v2/users#', $route ) ) {
			return new WP_Error( 'rest_disabled', 'Unauthorized', array( 'status' => 401 ) );
		}
		$opts = get_option( 'cgs_cd_opts', array() );
		if ( ! empty( $opts['rest_strict'] ) && strpos( $route, '/cgs-secure/' ) === false && strpos( $route, '/city-ghest' ) === false ) {
			// allow public theme routes; block sensitive
			if ( preg_match( '#/wp/v2/(users|settings)#', $route ) ) {
				return new WP_Error( 'rest_login_required', 'Auth required', array( 'status' => 401 ) );
			}
		}
		return $result;
	}
	public static function filter_endpoints( $endpoints ) {
		if ( is_user_logged_in() || ! is_array( $endpoints ) ) { return $endpoints; }
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( strpos( $route, '/wp/v2/users' ) === 0 ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
	public static function block_author_enum() {
		if ( ! is_admin() && isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
}
