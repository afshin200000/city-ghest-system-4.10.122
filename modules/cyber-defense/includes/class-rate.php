<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_Rate {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'guard' ), 0 );
		add_filter( 'authenticate', array( __CLASS__, 'login_throttle' ), 30, 3 );
	}
	public static function ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip = trim( $parts[0] );
		}
		return preg_replace( '/[^0-9a-fA-F:.]/', '', $ip );
	}
	public static function guard() {
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = get_option( 'cgs_cd_opts', array() );
		if ( isset( $opts['rate_front'] ) && empty( $opts['rate_front'] ) ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $uri, 'admin-ajax.php' ) !== false ) { return; }
		if ( preg_match( '/\.(sql|bak|env|git|svn|tar|zip)(\?|$)/i', $uri ) ) {
			status_header( 403 ); exit;
		}
		$key = 'cgs_cd_rl_' . md5( self::ip() );
		$n = (int) get_transient( $key );
		if ( $n > 150 ) {
			status_header( 429 );
			header( 'Retry-After: 60' );
			exit( 'Too Many Requests' );
		}
		set_transient( $key, $n + 1, 60 );
	}
	public static function login_throttle( $user, $username, $password ) {
		if ( empty( $username ) ) { return $user; }
		$key = 'cgs_cd_login_' . md5( self::ip() . '|' . strtolower( $username ) );
		$n = (int) get_transient( $key );
		if ( $n >= 8 ) {
			return new WP_Error( 'rate_limit', 'تلاش ورود بیش از حد.' );
		}
		set_transient( $key, $n + 1, 600 );
		return $user;
	}
}
