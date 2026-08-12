<?php
/**
 * Read-only integrity probe for Menu Builder (Phase A/B/C seal).
 * Never mutates data or hooks business logic.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Menu_Integrity {

	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_action( 'wp_ajax_cgs_menu_integrity', array( __CLASS__, 'ajax_probe' ) );
	}

	public static function probe() {
		$checks = array();

		$checks['class_builder'] = class_exists( 'CGS_Menu_Builder' );
		$checks['class_repository'] = class_exists( 'CGS_Menu_Repository' );
		$checks['class_revisions'] = class_exists( 'CGS_Menu_Revisions' );
		$checks['class_rest'] = class_exists( 'CGS_Menu_REST' );
		$checks['class_rate_limit'] = class_exists( 'CGS_Menu_Rate_Limit' );
		$checks['class_templates'] = class_exists( 'CGS_Mega_Templates' );

		$checks['method_render'] = $checks['class_builder'] && method_exists( 'CGS_Menu_Builder', 'render_menu_html' );
		$checks['method_sanitize'] = $checks['class_builder'] && method_exists( 'CGS_Menu_Builder', 'sanitize_menu' );
		$checks['method_get_all'] = $checks['class_builder'] && method_exists( 'CGS_Menu_Builder', 'get_all' );

		$files = array(
			'bootstrap' => 'bootstrap.php',
			'admin_js' => 'assets/js/admin.js',
			'front_js' => 'assets/js/front.js',
			'preview_js' => 'assets/js/admin/preview.js',
			'items_ui' => 'assets/js/admin/items-ui.js',
			'front_css' => 'assets/css/front.css',
			'admin_css' => 'assets/css/admin.css',
			'admin_view' => 'views/admin.php',
		);
		foreach ( $files as $k => $rel ) {
			$checks[ 'file_' . $k ] = file_exists( CGS_MENU_BUILDER_DIR . $rel );
		}

		$score = 0;
		$total = count( $checks );
		foreach ( $checks as $ok ) {
			if ( $ok ) {
				$score++;
			}
		}

		return array(
			'ok'       => $score === $total,
			'score'    => $score,
			'total'    => $total,
			'percent'  => $total ? round( ( $score / $total ) * 100 ) : 0,
			'checks'   => $checks,
			'version'  => class_exists( 'CGS_Menu_Builder' ) ? CGS_Menu_Builder::VERSION : '0',
			'phase'    => 'A+B+C',
		);
	}

	public static function ajax_probe() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		wp_send_json_success( self::probe() );
	}
}
