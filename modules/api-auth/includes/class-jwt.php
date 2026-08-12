<?php
/**
 * JWT HS256 — pure PHP, no external dependency
 * Claims: sub, iat, exp, jti, roles, type (access|refresh)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_JWT {

	const OPT_SECRET = 'cgs_jwt_secret';
	const OPT_ISSUER = 'cgs_jwt_issuer';

	public static function secret() {
		$s = get_option( self::OPT_SECRET, '' );
		if ( ! is_string( $s ) || strlen( $s ) < 32 ) {
			$s = bin2hex( random_bytes( 32 ) );
			update_option( self::OPT_SECRET, $s, false );
		}
		return $s;
	}

	public static function issuer() {
		$iss = get_option( self::OPT_ISSUER, '' );
		if ( $iss === '' ) {
			$iss = 'city-ghest';
			update_option( self::OPT_ISSUER, $iss, false );
		}
		return $iss;
	}

	private static function b64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( $data ) {
		$remainder = strlen( $data ) % 4;
		if ( $remainder ) {
			$data .= str_repeat( '=', 4 - $remainder );
		}
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	/**
	 * @param array $payload
	 * @param int   $ttl_seconds
	 * @return string
	 */
	public static function encode( array $payload, $ttl_seconds = 3600 ) {
		$header = array( 'alg' => 'HS256', 'typ' => 'JWT' );
		$now    = time();
		$payload = array_merge(
			array(
				'iss' => self::issuer(),
				'iat' => $now,
				'nbf' => $now,
				'exp' => $now + max( 60, (int) $ttl_seconds ),
				'jti' => bin2hex( random_bytes( 16 ) ),
			),
			$payload
		);
		$segments = array(
			self::b64url_encode( wp_json_encode( $header ) ),
			self::b64url_encode( wp_json_encode( $payload ) ),
		);
		$signing  = implode( '.', $segments );
		$sig      = hash_hmac( 'sha256', $signing, self::secret(), true );
		$segments[] = self::b64url_encode( $sig );
		return implode( '.', $segments );
	}

	/**
	 * @param string $jwt
	 * @return array|WP_Error payload
	 */
	public static function decode( $jwt ) {
		$jwt = is_string( $jwt ) ? trim( $jwt ) : '';
		$parts = explode( '.', $jwt );
		if ( count( $parts ) !== 3 ) {
			return new WP_Error( 'jwt_malformed', 'توکن نامعتبر است.', array( 'status' => 401 ) );
		}
		list( $h64, $p64, $s64 ) = $parts;
		$header  = json_decode( self::b64url_decode( $h64 ), true );
		$payload = json_decode( self::b64url_decode( $p64 ), true );
		if ( ! is_array( $header ) || ( $header['alg'] ?? '' ) !== 'HS256' ) {
			return new WP_Error( 'jwt_alg', 'الگوریتم پشتیبانی نمی‌شود.', array( 'status' => 401 ) );
		}
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'jwt_payload', 'بار توکن نامعتبر است.', array( 'status' => 401 ) );
		}
		$expected = self::b64url_encode( hash_hmac( 'sha256', $h64 . '.' . $p64, self::secret(), true ) );
		if ( ! hash_equals( $expected, $s64 ) ) {
			return new WP_Error( 'jwt_sig', 'امضای توکن معتبر نیست.', array( 'status' => 401 ) );
		}
		$now = time();
		if ( isset( $payload['nbf'] ) && (int) $payload['nbf'] > $now + 30 ) {
			return new WP_Error( 'jwt_nbf', 'توکن هنوز فعال نشده.', array( 'status' => 401 ) );
		}
		if ( isset( $payload['exp'] ) && (int) $payload['exp'] < $now ) {
			return new WP_Error( 'jwt_exp', 'توکن منقضی شده است.', array( 'status' => 401 ) );
		}
		if ( isset( $payload['iss'] ) && $payload['iss'] !== self::issuer() ) {
			return new WP_Error( 'jwt_iss', 'صادرکننده نامعتبر.', array( 'status' => 401 ) );
		}
		// blacklist jti
		if ( ! empty( $payload['jti'] ) && self::is_revoked( $payload['jti'] ) ) {
			return new WP_Error( 'jwt_revoked', 'توکن باطل شده است.', array( 'status' => 401 ) );
		}
		return $payload;
	}

	public static function revoke( $jti, $exp ) {
		$jti = sanitize_text_field( (string) $jti );
		if ( $jti === '' ) {
			return;
		}
		$key = 'cgs_jwt_bl_' . md5( $jti );
		$ttl = max( 60, (int) $exp - time() );
		set_transient( $key, 1, $ttl );
	}

	public static function is_revoked( $jti ) {
		return (bool) get_transient( 'cgs_jwt_bl_' . md5( (string) $jti ) );
	}

	/**
	 * Issue access + refresh pair for user
	 */
	public static function issue_for_user( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return new WP_Error( 'user', 'کاربر یافت نشد.', array( 'status' => 404 ) );
		}
		$roles  = array_values( (array) $user->roles );
		$access = self::encode(
			array(
				'sub'   => (int) $user->ID,
				'type'  => 'access',
				'roles' => $roles,
				'name'  => $user->display_name,
			),
			(int) apply_filters( 'cgs_jwt_access_ttl', 3600 )
		);
		$refresh = self::encode(
			array(
				'sub'  => (int) $user->ID,
				'type' => 'refresh',
			),
			(int) apply_filters( 'cgs_jwt_refresh_ttl', 1209600 ) // 14 days
		);
		return array(
			'token_type'    => 'Bearer',
			'access_token'  => $access,
			'refresh_token' => $refresh,
			'expires_in'    => (int) apply_filters( 'cgs_jwt_access_ttl', 3600 ),
			'user'          => array(
				'id'    => (int) $user->ID,
				'name'  => $user->display_name,
				'email' => $user->user_email,
				'roles' => $roles,
			),
		);
	}
}
