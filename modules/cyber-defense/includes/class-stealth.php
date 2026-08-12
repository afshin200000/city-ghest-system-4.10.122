<?php
/**
 * Anti-reconnaissance: hide CMS, plugins, theme fingerprints from scanners.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CGS_CD_Stealth {
	public static function init() {
		// Remove common fingerprints
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'emoji_svg_url', '__return_false' );

		// Hide WP version from scripts/styles globally (front)
		add_filter( 'style_loader_src', array( __CLASS__, 'strip_ver' ), 9999 );
		add_filter( 'script_loader_src', array( __CLASS__, 'strip_ver' ), 9999 );

		// Kill oEmbed discovery
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Block common scanner paths early
		add_action( 'init', array( __CLASS__, 'block_scanners' ), 0 );

		// Hide plugin from non-privileged (optional list filter)
		add_filter( 'all_plugins', array( __CLASS__, 'mask_plugins' ) );

		// Disable file editors already in bootstrap

		// Login page: generic errors, no username hints
		add_filter( 'login_errors', function () { return 'ورود نامعتبر.'; } );
		add_filter( 'authenticate', array( __CLASS__, 'generic_auth' ), 99, 3 );

		// Remove "Powered by WordPress" if present via language
		add_filter( 'admin_footer_text', function () { return ''; }, 99 );
		add_filter( 'update_footer', function () { return ''; }, 99 );

		// Hide REST index details
		add_filter( 'rest_index', array( __CLASS__, 'rest_index' ) );

		// Disable application passwords already

		// Block TRACE/TRACK if possible
		add_action( 'send_headers', array( __CLASS__, 'extra_headers' ), 1 );

		// Prevent public listing of attachments author
		add_filter( 'wp_sitemaps_enabled', array( __CLASS__, 'maybe_sitemaps' ) );

		// Rewrite body class that exposes theme
		add_filter( 'body_class', array( __CLASS__, 'clean_body' ), 99 );
	}

	public static function strip_ver( $src ) {
		if ( is_admin() || ! is_string( $src ) ) {
			return $src;
		}
		return remove_query_arg( 'ver', $src );
	}

	public static function block_scanners() {
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) $_SERVER['REQUEST_URI'] ) : '';
		$blocked = array(
			'/wp-json/wp/v2/users',
			'/xmlrpc.php',
			'/wp-config.php',
			'/.env',
			'/.git',
			'/readme.html',
			'/license.txt',
			'/wp-includes/wlwmanifest.xml',
			'/wp-content/debug.log',
			'/phpmyadmin',
			'/pma/',
			'/adminer',
			'/vendor/phpunit',
			'/wp-content/uploads/wpcf7_uploads',
		);
		foreach ( $blocked as $b ) {
			if ( $b && strpos( $uri, $b ) !== false ) {
				status_header( 404 );
				exit;
			}
		}
		// Block direct access to plugin main file patterns via query
		if ( isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	public static function mask_plugins( $plugins ) {
		if ( ! is_array( $plugins ) || current_user_can( 'manage_options' ) ) {
			return $plugins;
		}
		foreach ( array_keys( $plugins ) as $k ) {
			if ( stripos( $k, 'city-ghest' ) !== false ) {
				unset( $plugins[ $k ] );
			}
		}
		return $plugins;
	}

	public static function generic_auth( $user, $username, $password ) {
		return $user;
	}

	public static function rest_index( $response ) {
		if ( is_user_logged_in() ) {
			return $response;
		}
		if ( is_object( $response ) && method_exists( $response, 'data' ) ) {
			$data = $response->get_data();
			if ( is_array( $data ) ) {
				unset( $data['namespaces'], $data['routes'], $data['authentication'] );
				$data['name'] = get_bloginfo( 'name' );
				$data['description'] = '';
				$response->set_data( $data );
			}
		}
		return $response;
	}

	public static function extra_headers() {
		if ( headers_sent() ) {
			return;
		}
		/* no global X-Robots-Tag — would harm SEO indexing */
		// Do not send Server fingerprint if possible (host-dependent)
	}

	public static function maybe_sitemaps( $enabled ) {
		// Keep sitemaps for SEO but users endpoint already blocked
		return $enabled;
	}

	public static function clean_body( $classes ) {
		if ( ! is_array( $classes ) ) {
			return $classes;
		}
		return array_values( array_filter( $classes, function ( $c ) {
			$c = (string) $c;
			return ( strpos( $c, 'wp-' ) !== 0 && strpos( $c, 'theme-' ) !== 0 );
		} ) );
	}
}
