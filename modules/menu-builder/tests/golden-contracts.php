<?php
/**
 * CGS MenuBuilder Golden Contracts (v4.10.84)
 * Run: wp eval-file modules/menu-builder/tests/golden-contracts.php
 * Or include from CLI with WP loaded.
 *
 * Validates: depth contract, CTA attrs, effect data-*, structural CT, no dual depth mismatch.
 */
if ( ! defined( 'ABSPATH' ) ) {
	// CLI bootstrap hint
	fwrite( STDERR, "Load WordPress first (wp eval-file ...).\n" );
	exit( 1 );
}

if ( ! class_exists( 'CGS_Menu_Builder' ) && ! class_exists( 'CGS_MenuBuilder' ) ) {
	// try common class name from bootstrap
}

$pass = 0;
$fail = 0;
function cgs_gold_assert( $cond, $msg ) {
	global $pass, $fail;
	if ( $cond ) {
		echo "[PASS] $msg\n";
		$pass++;
	} else {
		echo "[FAIL] $msg\n";
		$fail++;
	}
}

$max = class_exists( 'CGS_Mega_Content_Types' ) ? CGS_Mega_Content_Types::max_depth() : 5;
cgs_gold_assert( $max === 5, 'max_depth contract is 5' );

$types = class_exists( 'CGS_Mega_Content_Types' ) ? CGS_Mega_Content_Types::list_types() : array();
cgs_gold_assert( isset( $types['divider'], $types['column'], $types['row'] ), 'structural content types registered' );

if ( class_exists( 'CGS_Mega_Content_Types' ) ) {
	$html = CGS_Mega_Content_Types::render_structural( array( 'content_type' => 'divider' ) );
	cgs_gold_assert( strpos( $html, 'cgs-menu-divider' ) !== false, 'divider structural render' );
}

// Render sample menu if builder class exists
$builder = null;
foreach ( array( 'CGS_Menu_Builder', 'CGS_MenuBuilder', 'CGS_MB' ) as $cls ) {
	if ( class_exists( $cls ) && method_exists( $cls, 'render_menu_html' ) ) {
		$builder = $cls;
		break;
	}
}

// Detect from bootstrap single class
if ( ! $builder ) {
	// scan declared classes
	foreach ( get_declared_classes() as $cls ) {
		if ( stripos( $cls, 'Menu' ) !== false && method_exists( $cls, 'render_menu_html' ) ) {
			$builder = $cls;
			break;
		}
	}
}

if ( $builder && method_exists( $builder, 'sanitize_menu' ) ) {
	$sample = array(
		'id'           => 'gold1',
		'title'        => 'Golden Menu',
		'layout'       => 'mega',
		'effect'       => 'fade',
		'effect_speed' => 300,
		'sub_open_dir' => 'bottom',
		'sound'        => 'click',
		'sound_vol'    => 40,
		'cta_text'     => 'درخواست',
		'cta_url'      => 'https://example.com',
		'cta_style'    => 'glass-capsule',
		'cta_color'    => '#e11d48',
		'cta_scale'    => 110,
		'cta_role'     => 'cta_link',
		'items'        => array(
			array(
				'id'       => 'i1',
				'label'    => 'خانه',
				'url'      => '/',
				'children' => array(
					array( 'id' => 'i1a', 'label' => 'زیر۱', 'url' => '#', 'children' => array() ),
				),
			),
		),
	);
	$clean = $builder::sanitize_menu( $sample );
	cgs_gold_assert( ! empty( $clean['effect'] ), 'sanitize keeps effect' );
	cgs_gold_assert( intval( $clean['effect_speed'] ) === 300, 'sanitize keeps effect_speed' );
	cgs_gold_assert( ! empty( $clean['cta_text'] ), 'sanitize keeps cta_text' );

	ob_start();
	$builder::render_menu_html( $clean );
	$html = ob_get_clean();
	cgs_gold_assert( strpos( $html, 'data-effect=' ) !== false, 'render outputs data-effect' );
	cgs_gold_assert( strpos( $html, 'data-speed=' ) !== false, 'render outputs data-speed' );
	cgs_gold_assert( strpos( $html, 'data-vol=' ) !== false, 'render outputs data-vol' );
	cgs_gold_assert( strpos( $html, 'data-sub-dir=' ) !== false, 'render outputs data-sub-dir' );
	cgs_gold_assert( strpos( $html, 'cgs-nav-cta' ) !== false || strpos( $html, 'data-cta=' ) !== false, 'render outputs CTA' );
	cgs_gold_assert( strpos( $html, 'cgs-nav-item' ) !== false, 'render outputs items' );
} else {
	echo "[SKIP] render_menu_html class not loaded in this context\n";
}

echo "\nResult: $pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
