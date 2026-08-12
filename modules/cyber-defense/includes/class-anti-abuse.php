<?php
/**
 * Anti-phishing / anti-spam / anti-scrape extras — isolated, no impact on admin UI modules.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CGS_CD_Anti_Abuse {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'block_fake_login' ), 0 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ), 99 );
		add_action( 'wp_login_failed', array( __CLASS__, 'log_fail' ) );
		add_filter( 'xmlrpc_enabled', '__return_false', 99 );
		// Honeypot header for bots that mirror forms
		add_action( 'login_form', array( __CLASS__, 'login_honeypot' ) );
		add_filter( 'authenticate', array( __CLASS__, 'check_honeypot' ), 20, 3 );
		// Disable user enumeration via REST already in API harden
		add_filter( 'comment_flood_filter', array( __CLASS__, 'comment_flood' ), 10, 3 );
	}

	public static function block_fake_login() {
		if ( is_admin() ) { return; }
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) $_SERVER['REQUEST_URI'] ) : '';
		$fakes = array( 'wp-login.php.bak', 'wp-signin', 'wp-admin.php', 'login.html', 'admin.html' );
		foreach ( $fakes as $f ) {
			if ( strpos( $uri, $f ) !== false ) {
				status_header( 404 );
				exit;
			}
		}
	}

	public static function mail_from( $from ) {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $host && is_string( $from ) && strpos( $from, '@' ) !== false ) {
			// keep site domain alignment to reduce spoof flags
			return $from;
		}
		return $from;
	}

	public static function log_fail( $username ) {
		$key = 'cgs_cd_fail_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . strtolower( (string) $username ) );
		$n = (int) get_transient( $key );
		set_transient( $key, $n + 1, 900 );
	}

	public static function login_honeypot() {
		echo '<p style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true"><label>website<input type="text" name="cgs_hp_url" value="" tabindex="-1" autocomplete="off"></label></p>';
	}

	public static function check_honeypot( $user, $username, $password ) {
		if ( ! empty( $_POST['cgs_hp_url'] ) ) {
			return new WP_Error( 'denied', 'دسترسی مجاز نیست.' );
		}
		return $user;
	}

	public static function comment_flood( $block, $time_last, $time_new ) {
		if ( ( $time_new - $time_last ) < 20 ) {
			return true;
		}
		return $block;
	}
}
