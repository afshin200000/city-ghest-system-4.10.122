<?php
/**
 * CGS Menu Revisions — Phase C
 * Stores last N snapshots per menu id in a dedicated option (not a patch of core OPTION).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Menu_Revisions {

	const OPTION = 'cgs_menu_revisions_v1';
	const MAX_PER_MENU = 20;

	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) { return; }
		self::$booted = true;
		add_action( 'wp_ajax_cgs_menu_revisions_list', array( __CLASS__, 'ajax_list' ) );
		add_action( 'wp_ajax_cgs_menu_revision_restore', array( __CLASS__, 'ajax_restore' ) );
	}

	/**
	 * Push snapshot after successful save.
	 *
	 * @param string $menu_id Menu id.
	 * @param array  $menu    Sanitized menu.
	 */
	public static function push( $menu_id, $menu ) {
		$menu_id = sanitize_key( $menu_id );
		if ( ! $menu_id || ! is_array( $menu ) ) {
			return;
		}
		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		if ( ! isset( $all[ $menu_id ] ) || ! is_array( $all[ $menu_id ] ) ) {
			$all[ $menu_id ] = array();
		}
		$snap = array(
			'id'         => $menu_id,
			'version'    => isset( $menu['_version'] ) ? intval( $menu['_version'] ) : 0,
			'saved_at'   => time(),
			'saved_by'   => get_current_user_id(),
			'title'      => isset( $menu['title'] ) ? sanitize_text_field( $menu['title'] ) : $menu_id,
			'snapshot'   => $menu,
		);
		array_unshift( $all[ $menu_id ], $snap );
		$all[ $menu_id ] = array_slice( $all[ $menu_id ], 0, self::MAX_PER_MENU );
		update_option( self::OPTION, $all, false );
		if ( class_exists( 'CGS_Menu_Repository' ) ) {
			CGS_Menu_Repository::insert_revision( $menu_id, $menu );
		}
	}

	/**
	 * @param string $menu_id Menu id.
	 * @return array
	 */
	public static function list_for( $menu_id ) {
		$menu_id = sanitize_key( $menu_id );
		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) || empty( $all[ $menu_id ] ) ) {
			return array();
		}
		$out = array();
		foreach ( $all[ $menu_id ] as $i => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out[] = array(
				'index'    => $i,
				'version'  => isset( $row['version'] ) ? intval( $row['version'] ) : 0,
				'saved_at' => isset( $row['saved_at'] ) ? intval( $row['saved_at'] ) : 0,
				'saved_by' => isset( $row['saved_by'] ) ? intval( $row['saved_by'] ) : 0,
				'title'    => isset( $row['title'] ) ? $row['title'] : $menu_id,
			);
		}
		return $out;
	}

	/**
	 * @param string $menu_id Menu id.
	 * @param int    $index   Snapshot index.
	 * @return array|null Full menu snapshot.
	 */
	public static function get_snapshot( $menu_id, $index ) {
		$menu_id = sanitize_key( $menu_id );
		$index   = intval( $index );
		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) || empty( $all[ $menu_id ][ $index ]['snapshot'] ) ) {
			return null;
		}
		$snap = $all[ $menu_id ][ $index ]['snapshot'];
		return is_array( $snap ) ? $snap : null;
	}

	public static function ajax_list() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'revisions_list', 30, 60 ) ) {
			wp_send_json_error( array( 'message' => 'rate_limited' ), 429 );
		}
		$id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		wp_send_json_success( array( 'id' => $id, 'revisions' => self::list_for( $id ) ) );
	}

	public static function ajax_restore() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'revision_restore', 10, 60 ) ) {
			wp_send_json_error( array( 'message' => 'rate_limited' ), 429 );
		}
		$id    = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : -1;
		$snap  = self::get_snapshot( $id, $index );
		if ( ! $snap ) {
			wp_send_json_error( array( 'message' => 'revision_not_found' ) );
		}
		if ( ! class_exists( 'CGS_Menu_Builder' ) ) {
			wp_send_json_error( array( 'message' => 'builder_missing' ) );
		}
		$menus = CGS_Menu_Builder::get_all();
		$server_ver = isset( $menus[ $id ]['_version'] ) ? intval( $menus[ $id ]['_version'] ) : 0;
		$clean = CGS_Menu_Builder::sanitize_menu( $snap );
		$clean['id'] = $id;
		$clean['_version'] = $server_ver + 1;
		$clean['_updated_at'] = time();
		$clean['_updated_by'] = get_current_user_id();
		$clean['_restored_from'] = $index;
		$menus[ $id ] = $clean;
		update_option( CGS_Menu_Builder::OPTION, $menus, false );
		self::push( $id, $clean );
		wp_send_json_success( array( 'id' => $id, 'menu' => $clean, 'version' => $clean['_version'] ) );
	}
}
