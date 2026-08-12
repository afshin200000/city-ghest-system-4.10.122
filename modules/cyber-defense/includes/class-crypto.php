<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_Crypto {
	public static function key() {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' );
		return hash( 'sha256', $material . '|cgs-cd-v1', true );
	}
	public static function encrypt( $plaintext ) {
		$iv = random_bytes( 12 ); $tag = '';
		$ct = openssl_encrypt( (string) $plaintext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
		return $ct === false ? '' : base64_encode( $iv . $tag . $ct );
	}
	public static function decrypt( $payload ) {
		$raw = base64_decode( (string) $payload, true );
		if ( $raw === false || strlen( $raw ) < 28 ) { return ''; }
		$iv = substr( $raw, 0, 12 ); $tag = substr( $raw, 12, 16 ); $ct = substr( $raw, 28 );
		$pt = openssl_decrypt( $ct, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag );
		return $pt === false ? '' : $pt;
	}
}
