<?php
/**
 * CGS Menu Repository — Phase C complete
 * Dual-write: WordPress options (primary runtime) + optional dedicated tables.
 * Tables enable scale/query without breaking existing option readers.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Menu_Repository {

	const DB_VERSION = 1;
	const DB_OPT     = 'cgs_menu_db_version';

	public static function boot() {
		self::maybe_install();
		self::sync_from_options();
	}

	/**
	 * One-time soft sync: mirror options into tables (never deletes options).
	 * Runtime continues to read from options — zero regression.
	 */
	public static function sync_from_options() {
		if ( get_option( 'cgs_menu_db_synced', 0 ) ) {
			return;
		}
		if ( ! class_exists( 'CGS_Menu_Builder' ) ) {
			return;
		}
		$menus = CGS_Menu_Builder::get_all();
		if ( ! is_array( $menus ) || ! $menus ) {
			update_option( 'cgs_menu_db_synced', 1, false );
			return;
		}
		foreach ( $menus as $key => $menu ) {
			if ( is_array( $menu ) ) {
				self::upsert_menu( $key, $menu );
			}
		}
		update_option( 'cgs_menu_db_synced', 1, false );
	}

	public static function table_menus() {
		global $wpdb;
		return $wpdb->prefix . 'cgs_menus';
	}

	public static function table_revisions() {
		global $wpdb;
		return $wpdb->prefix . 'cgs_menu_revisions';
	}

	public static function maybe_install() {
		$ver = intval( get_option( self::DB_OPT, 0 ) );
		if ( $ver >= self::DB_VERSION ) {
			return;
		}
		self::install();
		update_option( self::DB_OPT, self::DB_VERSION, false );
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$t1 = self::table_menus();
		$t2 = self::table_revisions();
		$sql1 = "CREATE TABLE {$t1} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_key varchar(64) NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			layout varchar(32) NOT NULL DEFAULT 'horizontal',
			version int unsigned NOT NULL DEFAULT 0,
			payload longtext NOT NULL,
			updated_at datetime NOT NULL,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY menu_key (menu_key),
			KEY layout (layout),
			KEY updated_at (updated_at)
		) {$charset};";
		$sql2 = "CREATE TABLE {$t2} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_key varchar(64) NOT NULL,
			version int unsigned NOT NULL DEFAULT 0,
			title varchar(255) NOT NULL DEFAULT '',
			payload longtext NOT NULL,
			saved_at datetime NOT NULL,
			saved_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY menu_key_version (menu_key, version),
			KEY saved_at (saved_at)
		) {$charset};";
		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	/**
	 * Dual-write one menu after options update.
	 *
	 * @param string $menu_key Menu id.
	 * @param array  $menu     Sanitized menu.
	 */
	public static function upsert_menu( $menu_key, $menu ) {
		global $wpdb;
		$menu_key = sanitize_key( $menu_key );
		if ( ! $menu_key || ! is_array( $menu ) ) {
			return;
		}
		self::maybe_install();
		$table = self::table_menus();
		// Table may fail on restricted hosts — soft fail.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return;
		}
		$payload = wp_json_encode( $menu );
		if ( ! $payload ) {
			return;
		}
		$row = array(
			'menu_key'   => $menu_key,
			'title'      => isset( $menu['title'] ) ? sanitize_text_field( $menu['title'] ) : $menu_key,
			'layout'     => isset( $menu['layout'] ) ? sanitize_key( $menu['layout'] ) : 'horizontal',
			'version'    => isset( $menu['_version'] ) ? intval( $menu['_version'] ) : 0,
			'payload'    => $payload,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => get_current_user_id(),
		);
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE menu_key = %s", $menu_key ) );
		if ( $found ) {
			$wpdb->update( $table, $row, array( 'menu_key' => $menu_key ), array( '%s', '%s', '%s', '%d', '%s', '%s', '%d' ), array( '%s' ) );
		} else {
			$wpdb->insert( $table, $row, array( '%s', '%s', '%s', '%d', '%s', '%s', '%d' ) );
		}
	}

	/**
	 * Insert revision row (table mirror of option revisions).
	 *
	 * @param string $menu_key Menu id.
	 * @param array  $menu     Snapshot.
	 */
	public static function insert_revision( $menu_key, $menu ) {
		global $wpdb;
		$menu_key = sanitize_key( $menu_key );
		if ( ! $menu_key || ! is_array( $menu ) ) {
			return;
		}
		self::maybe_install();
		$table = self::table_revisions();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return;
		}
		$payload = wp_json_encode( $menu );
		if ( ! $payload ) {
			return;
		}
		$wpdb->insert(
			$table,
			array(
				'menu_key' => $menu_key,
				'version'  => isset( $menu['_version'] ) ? intval( $menu['_version'] ) : 0,
				'title'    => isset( $menu['title'] ) ? sanitize_text_field( $menu['title'] ) : $menu_key,
				'payload'  => $payload,
				'saved_at' => current_time( 'mysql' ),
				'saved_by' => get_current_user_id(),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d' )
		);
		// Keep table bounded: delete older than 50 per key
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE menu_key = %s ORDER BY id DESC", $menu_key ) );
		if ( is_array( $ids ) && count( $ids ) > 50 ) {
			$drop = array_map( 'intval', array_slice( $ids, 50 ) );
			if ( $drop ) {
				$in = implode( ',', $drop );
				$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ints only
			}
		}
	}
}
