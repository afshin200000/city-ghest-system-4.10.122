<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_Headers {
	public static function init() {
		add_action( 'send_headers', array( __CLASS__, 'send' ), 0 );
		add_filter( 'wp_headers', array( __CLASS__, 'filter_wp' ), 999 );
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		$opts = self::opts();
		if ( ! empty( $opts['cloak_ver'] ) ) {
			add_filter( 'style_loader_src', array( __CLASS__, 'strip_ver' ), 999 );
			add_filter( 'script_loader_src', array( __CLASS__, 'strip_ver' ), 999 );
		}
	}
	public static function opts() {
		$o = get_option( 'cgs_cd_opts', array() );
		return wp_parse_args( is_array( $o ) ? $o : array(), array( 'hsts' => 1, 'csp' => 1, 'cloak_ver' => 1 ) );
	}
	public static function send() {
		if ( headers_sent() ) { return; }
		header_remove( 'X-Powered-By' );
		header_remove( 'X-Pingback' );
		// Admin/AJAX: do not apply strict CSP (breaks media, SEO tools, previews)
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			return;
		}
		$opts = self::opts();
		if ( ! empty( $opts['hsts'] ) && is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
		}
		header( 'X-Frame-Options: DENY' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
		if ( ! empty( $opts['csp'] ) ) {
			// Allow Google/Bing crawlers + common SEO assets; still block framing
			$csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https: blob:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
			header( 'Content-Security-Policy: ' . $csp );
		}
		// X-Robots-Tag must NOT globally noindex — that kills SEO
	}
	public static function filter_wp( $headers ) {
		unset( $headers['X-Pingback'], $headers['X-Powered-By'] );
		return $headers;
	}
	public static function strip_ver( $src ) {
		if ( is_admin() || ! is_string( $src ) ) { return $src; }
		return remove_query_arg( 'ver', $src );
	}
}
