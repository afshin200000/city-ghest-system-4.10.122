<?php
/**
 * CGS Menu REST API — Phase C complete
 * Namespace: cgs/v1
 * Auth: WordPress cookie / Application Passwords + manage_options for writes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Menu_REST {

	const NS = 'cgs/v1';
	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/menus',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'list_menus' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_menu' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_menu' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( __CLASS__, 'save_menu' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'delete_menu' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<id>[a-zA-Z0-9_-]+)/render',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'render_menu' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<id>[a-zA-Z0-9_-]+)/revisions',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_revisions' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<id>[a-zA-Z0-9_-]+)/revisions/(?P<index>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'restore_revision' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'health' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	public static function health() {
		return rest_ensure_response(
			array(
				'ok'      => true,
				'module'  => 'menu-builder',
				'version' => class_exists( 'CGS_Menu_Builder' ) ? CGS_Menu_Builder::VERSION : '0',
				'rest'    => self::NS,
			)
		);
	}

	public static function list_menus( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_list', 120, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		if ( ! class_exists( 'CGS_Menu_Builder' ) ) {
			return new WP_Error( 'unavailable', 'Menu builder not loaded', array( 'status' => 503 ) );
		}
		$menus = CGS_Menu_Builder::get_all();
		$out   = array();
		foreach ( $menus as $id => $m ) {
			$out[] = array(
				'id'          => $id,
				'title'       => isset( $m['title'] ) ? $m['title'] : $id,
				'layout'      => isset( $m['layout'] ) ? $m['layout'] : 'horizontal',
				'_version'    => isset( $m['_version'] ) ? intval( $m['_version'] ) : 0,
				'_updated_at' => isset( $m['_updated_at'] ) ? intval( $m['_updated_at'] ) : 0,
			);
		}
		return rest_ensure_response( array( 'menus' => $out ) );
	}

	public static function get_menu( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_get', 120, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		$id = sanitize_key( $request['id'] );
		$m  = class_exists( 'CGS_Menu_Builder' ) ? CGS_Menu_Builder::get_one( $id ) : null;
		if ( ! $m ) {
			return new WP_Error( 'not_found', 'Menu not found', array( 'status' => 404 ) );
		}
		return rest_ensure_response( $m );
	}

	public static function save_menu( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_save', 40, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		if ( ! class_exists( 'CGS_Menu_Builder' ) ) {
			return new WP_Error( 'unavailable', 'Menu builder not loaded', array( 'status' => 503 ) );
		}
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) {
			$data = $request->get_params();
		}
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid', 'Invalid body', array( 'status' => 400 ) );
		}
		if ( empty( $data['id'] ) && $request->get_param( 'id' ) ) {
			$data['id'] = $request->get_param( 'id' );
		}
		if ( empty( $data['id'] ) ) {
			return new WP_Error( 'invalid', 'id required', array( 'status' => 400 ) );
		}
		$id    = sanitize_key( $data['id'] );
		$menus = CGS_Menu_Builder::get_all();
		$client_ver = isset( $data['_version'] ) ? intval( $data['_version'] ) : 0;
		$server_ver = isset( $menus[ $id ]['_version'] ) ? intval( $menus[ $id ]['_version'] ) : 0;
		if ( $server_ver > 0 && $client_ver > 0 && $client_ver < $server_ver ) {
			return new WP_Error(
				'version_conflict',
				'Stale version',
				array(
					'status'         => 409,
					'server_version' => $server_ver,
					'client_version' => $client_ver,
				)
			);
		}
		$clean = CGS_Menu_Builder::sanitize_menu( $data );
		$clean['id'] = $id;
		$clean['_version'] = $server_ver + 1;
		$clean['_updated_at'] = time();
		$clean['_updated_by'] = get_current_user_id();
		$menus[ $id ] = $clean;
		update_option( CGS_Menu_Builder::OPTION, $menus, false );
		if ( class_exists( 'CGS_Menu_Revisions' ) ) {
			CGS_Menu_Revisions::push( $id, $clean );
		}
		if ( class_exists( 'CGS_Menu_Repository' ) ) {
			CGS_Menu_Repository::upsert_menu( $id, $clean );
			CGS_Menu_Repository::insert_revision( $id, $clean );
		}
		return rest_ensure_response(
			array(
				'id'      => $id,
				'menu'    => $clean,
				'version' => $clean['_version'],
			)
		);
	}

	public static function delete_menu( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_delete', 20, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		$id = sanitize_key( $request['id'] );
		$menus = CGS_Menu_Builder::get_all();
		if ( isset( $menus[ $id ] ) ) {
			unset( $menus[ $id ] );
			update_option( CGS_Menu_Builder::OPTION, $menus, false );
		}
		return rest_ensure_response( array( 'deleted' => $id ) );
	}

	public static function render_menu( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_render', 180, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		$id = sanitize_key( $request['id'] );
		$m  = class_exists( 'CGS_Menu_Builder' ) ? CGS_Menu_Builder::get_one( $id ) : null;
		if ( ! $m ) {
			return new WP_Error( 'not_found', 'Menu not found', array( 'status' => 404 ) );
		}
		ob_start();
		CGS_Menu_Builder::render_menu_html( $m );
		$html = ob_get_clean();
		return rest_ensure_response( array( 'id' => $id, 'html' => $html ) );
	}

	public static function list_revisions( $request ) {
		$id = sanitize_key( $request['id'] );
		if ( ! class_exists( 'CGS_Menu_Revisions' ) ) {
			return rest_ensure_response( array( 'revisions' => array() ) );
		}
		return rest_ensure_response( array( 'id' => $id, 'revisions' => CGS_Menu_Revisions::list_for( $id ) ) );
	}

	public static function restore_revision( $request ) {
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'rest_restore', 10, 60 ) ) {
			return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
		}
		$id    = sanitize_key( $request['id'] );
		$index = intval( $request['index'] );
		if ( ! class_exists( 'CGS_Menu_Revisions' ) || ! class_exists( 'CGS_Menu_Builder' ) ) {
			return new WP_Error( 'unavailable', 'Module missing', array( 'status' => 503 ) );
		}
		$snap = CGS_Menu_Revisions::get_snapshot( $id, $index );
		if ( ! $snap ) {
			return new WP_Error( 'not_found', 'Revision not found', array( 'status' => 404 ) );
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
		CGS_Menu_Revisions::push( $id, $clean );
		if ( class_exists( 'CGS_Menu_Repository' ) ) {
			CGS_Menu_Repository::upsert_menu( $id, $clean );
			CGS_Menu_Repository::insert_revision( $id, $clean );
		}
		return rest_ensure_response( array( 'id' => $id, 'menu' => $clean, 'version' => $clean['_version'] ) );
	}
}
