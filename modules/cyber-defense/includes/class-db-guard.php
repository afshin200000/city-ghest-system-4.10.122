<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class CGS_CD_DB_Guard {
	public static function init() {
		add_filter( 'query', array( __CLASS__, 'watch' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'block_export_tools' ), 1 );
	}
	public static function watch( $sql ) {
		if ( ! is_string( $sql ) ) { return $sql; }
		$u = strtoupper( ltrim( $sql ) );
		// Only block clearly malicious patterns — not normal WP SHOW
		if ( preg_match( '/\b(INTO\s+OUTFILE|INTO\s+DUMPFILE|LOAD_FILE\s*\(|BENCHMARK\s*\(|SLEEP\s*\()/i', $sql ) ) {
			return 'SELECT 1 WHERE 0 /* cgs_cd_blocked */';
		}
		// Bulk dump heuristic for non-admin CLI-less context
		if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) && preg_match( '/\bUNION\s+SELECT\b/i', $sql ) && substr_count( $u, 'INFORMATION_SCHEMA' ) > 0 ) {
			return 'SELECT 1 WHERE 0 /* cgs_cd_blocked */';
		}
		return $sql;
	}
	public static function block_export_tools() {
		if ( ! current_user_can( 'export' ) ) {
			return;
		}
		// Soft: leave export for admins; rate is handled elsewhere
	}
}
