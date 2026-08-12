<?php
/**
 * CGS Menu Rate Limit — Phase C
 * Transient-based per-user (or IP) sliding window for AJAX/REST.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Menu_Rate_Limit {

	/**
	 * @param string $action Logical action key.
	 * @param int    $max    Max hits in window.
	 * @param int    $window Window seconds.
	 * @return bool True if allowed.
	 */
	public static function allow( $action, $max = 60, $window = 60 ) {
		$action = sanitize_key( $action );
		$max    = max( 1, intval( $max ) );
		$window = max( 5, intval( $window ) );
		$uid    = get_current_user_id();
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key    = 'cgs_rl_' . md5( $action . '|' . $uid . '|' . $ip );
		$bucket = get_transient( $key );
		if ( ! is_array( $bucket ) ) {
			$bucket = array( 'c' => 0, 't' => time() );
		}
		$now = time();
		if ( ( $now - intval( $bucket['t'] ) ) >= $window ) {
			$bucket = array( 'c' => 0, 't' => $now );
		}
		$bucket['c'] = intval( $bucket['c'] ) + 1;
		set_transient( $key, $bucket, $window );
		return $bucket['c'] <= $max;
	}
}
