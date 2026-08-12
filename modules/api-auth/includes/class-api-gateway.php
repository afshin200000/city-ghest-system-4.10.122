<?php
/**
 * Secure API Gateway — JWT auth, rate limit, password reset
 * Routes: /api/v1/secure-gateway/*  (masked; also via rest if rewrite active)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_API_Gateway {

	const NS           = 'cgs-secure/v1';
	const RL_PREFIX    = 'cgs_rl_';
	const RESET_PREFIX = 'cgs_pw_reset_';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'jwt_authenticate' ), 20 );
		// Optional: hide default wp-json discovery noise for our namespace only handled below
		add_filter( 'rest_index', array( __CLASS__, 'filter_rest_index' ) );
		add_action( 'init', array( __CLASS__, 'add_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_gateway' ), 0 );
	}

	public static function add_rewrite() {
		add_rewrite_rule( '^api/v1/secure-gateway/(.+)/?$', 'index.php?cgs_gw=$matches[1]', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'cgs_gw';
		return $vars;
	}

	/**
	 * Proxy custom path to REST under the hood without exposing wp-json in client URL preference
	 */
	public static function maybe_gateway() {
		$path = get_query_var( 'cgs_gw' );
		if ( ! $path ) {
			return;
		}
		// Map to REST request internally
		$rest_path = '/' . self::NS . '/' . ltrim( $path, '/' );
		$request   = new WP_REST_Request( $_SERVER['REQUEST_METHOD'] ?? 'GET', $rest_path );
		// Body
		$raw = file_get_contents( 'php://input' );
		if ( $raw ) {
			$json = json_decode( $raw, true );
			if ( is_array( $json ) ) {
				foreach ( $json as $k => $v ) {
					$request->set_param( $k, $v );
				}
			}
		}
		foreach ( $_GET as $k => $v ) { // phpcs:ignore
			$request->set_param( sanitize_key( $k ), sanitize_text_field( wp_unslash( $v ) ) );
		}
		$response = rest_do_request( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();
		status_header( $status );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
		header( 'Cache-Control: no-store' );
		// Do not leak WP version
		header_remove( 'X-Powered-By' );
		echo wp_json_encode( $data );
		exit;
	}

	public static function filter_rest_index( $response ) {
		// Reduce fingerprint: remove routes list for anonymous if desired
		if ( ! is_user_logged_in() && ! self::bearer_user_id() ) {
			if ( $response instanceof WP_REST_Response ) {
				$data = $response->get_data();
				if ( is_array( $data ) ) {
					unset( $data['routes'], $data['namespaces'] );
					$response->set_data( $data );
				}
			}
		}
		return $response;
	}

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/auth/login',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'login' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/auth/refresh',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'refresh' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/auth/logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'logout' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/auth/me',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'me' ),
				'permission_callback' => array( __CLASS__, 'require_auth' ),
			)
		);
		// Password reset — hardened
		register_rest_route(
			self::NS,
			'/auth/password/request',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'password_request' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/auth/password/confirm',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'password_confirm' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					return array( 'ok' => true, 'ts' => time() );
				},
				'permission_callback' => '__return_true',
			)
		);
	}

	/* ── Rate limiting ── */

	public static function client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip    = trim( $parts[0] );
		}
		return preg_replace( '/[^0-9a-fA-F:.]/', '', $ip );
	}

	/**
	 * @return true|WP_Error
	 */
	public static function rate_limit( $bucket, $max = 10, $window = 300 ) {
		$ip  = self::client_ip();
		$key = self::RL_PREFIX . $bucket . '_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= $max ) {
			return new WP_Error(
				'rate_limited',
				'تعداد درخواست بیش از حد مجاز است. لطفاً بعداً تلاش کنید.',
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $n + 1, $window );
		return true;
	}

	public static function bearer_token() {
		$hdr = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
		if ( $hdr === '' && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$hdr = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
		}
		if ( preg_match( '/Bearer\s+(\S+)/i', $hdr, $m ) ) {
			return $m[1];
		}
		return '';
	}

	public static function bearer_user_id() {
		$token = self::bearer_token();
		if ( $token === '' ) {
			return 0;
		}
		$payload = CGS_JWT::decode( $token );
		if ( is_wp_error( $payload ) ) {
			return 0;
		}
		if ( ( $payload['type'] ?? '' ) !== 'access' ) {
			return 0;
		}
		return (int) ( $payload['sub'] ?? 0 );
	}

	public static function jwt_authenticate( $result ) {
		if ( true === $result || is_wp_error( $result ) ) {
			return $result;
		}
		$uid = self::bearer_user_id();
		if ( $uid > 0 ) {
			wp_set_current_user( $uid );
			return true;
		}
		return $result;
	}

	public static function require_auth() {
		return self::bearer_user_id() > 0 || is_user_logged_in();
	}

	/* ── Auth endpoints ── */

	public static function login( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'login', 8, 600 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		// Honeypot
		if ( $req->get_param( 'website' ) || $req->get_param( 'hp_field' ) ) {
			return new WP_Error( 'bot', 'درخواست رد شد.', array( 'status' => 400 ) );
		}
		$login = sanitize_text_field( (string) $req->get_param( 'login' ) );
		$pass  = (string) $req->get_param( 'password' );
		if ( $login === '' || $pass === '' ) {
			return new WP_Error( 'missing', 'نام کاربری و رمز الزامی است.', array( 'status' => 400 ) );
		}
		$user = wp_authenticate( $login, $pass );
		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'invalid_credentials', 'اطلاعات ورود نادرست است.', array( 'status' => 401 ) );
		}
		return CGS_JWT::issue_for_user( $user->ID );
	}

	public static function refresh( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'refresh', 20, 600 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$token   = (string) ( $req->get_param( 'refresh_token' ) ?: self::bearer_token() );
		$payload = CGS_JWT::decode( $token );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		if ( ( $payload['type'] ?? '' ) !== 'refresh' ) {
			return new WP_Error( 'not_refresh', 'توکن تازه‌سازی نامعتبر است.', array( 'status' => 401 ) );
		}
		// Rotate: revoke old refresh jti
		if ( ! empty( $payload['jti'] ) ) {
			CGS_JWT::revoke( $payload['jti'], (int) ( $payload['exp'] ?? time() + 60 ) );
		}
		return CGS_JWT::issue_for_user( (int) $payload['sub'] );
	}

	public static function logout( WP_REST_Request $req ) {
		$token   = self::bearer_token() ?: (string) $req->get_param( 'access_token' );
		$payload = CGS_JWT::decode( $token );
		if ( ! is_wp_error( $payload ) && ! empty( $payload['jti'] ) ) {
			CGS_JWT::revoke( $payload['jti'], (int) ( $payload['exp'] ?? time() + 3600 ) );
		}
		$rt = (string) $req->get_param( 'refresh_token' );
		if ( $rt ) {
			$rp = CGS_JWT::decode( $rt );
			if ( ! is_wp_error( $rp ) && ! empty( $rp['jti'] ) ) {
				CGS_JWT::revoke( $rp['jti'], (int) ( $rp['exp'] ?? time() + 60 ) );
			}
		}
		return array( 'ok' => true );
	}

	public static function me() {
		$uid = self::bearer_user_id() ?: get_current_user_id();
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return new WP_Error( 'unauthorized', 'احراز هویت نشده.', array( 'status' => 401 ) );
		}
		return array(
			'id'    => (int) $user->ID,
			'name'  => $user->display_name,
			'email' => $user->user_email,
			'roles' => array_values( (array) $user->roles ),
		);
	}

	/* ── Password reset (anti-enumeration, rate-limited, signed token) ── */

	public static function password_request( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'pw_req', 5, 900 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		if ( $req->get_param( 'website' ) || $req->get_param( 'hp_field' ) ) {
			return array( 'ok' => true, 'message' => 'اگر حساب مرتبط وجود داشته باشد، لینک بازیابی ارسال شد.' );
		}
		$login = sanitize_text_field( (string) $req->get_param( 'login' ) );
		// Always same response (no user enumeration)
		$generic = array(
			'ok'      => true,
			'message' => 'اگر حساب مرتبط وجود داشته باشد، لینک بازیابی ارسال شد.',
		);
		if ( $login === '' ) {
			return $generic;
		}
		$user = strpos( $login, '@' ) !== false ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
		if ( ! $user ) {
			return $generic;
		}
		$token = bin2hex( random_bytes( 32 ) );
		$hash  = hash_hmac( 'sha256', $token, CGS_JWT::secret() );
		set_transient(
			self::RESET_PREFIX . $user->ID,
			array(
				'hash' => $hash,
				'ip'   => self::client_ip(),
				'time' => time(),
			),
			HOUR_IN_SECONDS
		);
		$link = add_query_arg(
			array(
				'cgs_reset' => 1,
				'uid'       => $user->ID,
				'token'     => $token,
			),
			home_url( '/' )
		);
		$subject = 'بازیابی رمز عبور — شهر قسط';
		$body    = "درخواست بازیابی رمز دریافت شد.\n\nاگر این درخواست از سمت شما بوده، از لینک زیر استفاده کنید (معتبر تا ۱ ساعت):\n{$link}\n\nاگر شما نبودید، این پیام را نادیده بگیرید.";
		wp_mail( $user->user_email, $subject, $body );
		return $generic;
	}

	public static function password_confirm( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'pw_confirm', 8, 900 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$uid      = absint( $req->get_param( 'uid' ) );
		$token    = sanitize_text_field( (string) $req->get_param( 'token' ) );
		$password = (string) $req->get_param( 'password' );
		if ( $uid < 1 || $token === '' || strlen( $password ) < 8 ) {
			return new WP_Error( 'invalid', 'پارامترها نامعتبر است. رمز حداقل ۸ کاراکتر.', array( 'status' => 400 ) );
		}
		$stored = get_transient( self::RESET_PREFIX . $uid );
		if ( ! is_array( $stored ) || empty( $stored['hash'] ) ) {
			return new WP_Error( 'expired', 'لینک بازیابی منقضی یا نامعتبر است.', array( 'status' => 400 ) );
		}
		$calc = hash_hmac( 'sha256', $token, CGS_JWT::secret() );
		if ( ! hash_equals( $stored['hash'], $calc ) ) {
			return new WP_Error( 'invalid_token', 'توکن نامعتبر است.', array( 'status' => 400 ) );
		}
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return new WP_Error( 'user', 'کاربر یافت نشد.', array( 'status' => 404 ) );
		}
		wp_set_password( $password, $uid );
		delete_transient( self::RESET_PREFIX . $uid );
		// Invalidate sessions
		if ( class_exists( 'WP_Session_Tokens' ) ) {
			$manager = WP_Session_Tokens::get_instance( $uid );
			$manager->destroy_all();
		}
		return array( 'ok' => true, 'message' => 'رمز با موفقیت تغییر کرد.' );
	}
}
