<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_License {
	const OPT = 'cgs_cd_domain_anchor';
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'check' ), 2 );
	}
	public static function fingerprint() {
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'cgs';
		return hash_hmac( 'sha256', $host . '|' . ( defined( 'DB_NAME' ) ? DB_NAME : '' ), $salt );
	}
	public static function ensure_anchor() {
		$stored = get_option( self::OPT, '' );
		if ( $stored === '' ) {
			update_option( self::OPT, self::fingerprint(), false );
			return true;
		}
		return hash_equals( (string) $stored, self::fingerprint() );
	}
	public static function check() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		self::ensure_anchor();
	}
}
