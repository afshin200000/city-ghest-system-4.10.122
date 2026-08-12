<?php
/**
 * Central Menu Field Contract — single source of truth for UI ↔ State ↔ Sanitize ↔ Render
 * Version: 4.10.73
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

return array(
	'version' => 1,
	'fields'  => array(
		// identity
		'id' => array( 'type' => 'key', 'default' => '' ),
		'name' => array( 'type' => 'text', 'default' => 'منوی جدید' ),
		'slug' => array( 'type' => 'key', 'default' => 'main' ),
		'location' => array( 'type' => 'key', 'default' => 'header' ),
		'layout' => array( 'type' => 'key', 'default' => 'horizontal' ),
		// appearance
		'bg_color' => array( 'type' => 'color', 'default' => '#0f172a' ),
		'bg_color2' => array( 'type' => 'color', 'default' => '' ),
		'gradient_dir' => array( 'type' => 'key', 'default' => 'ltr' ),
		'text_color' => array( 'type' => 'color', 'default' => '#f8fafc' ),
		'hover_color' => array( 'type' => 'color', 'default' => '#38bdf8' ),
		'active_color' => array( 'type' => 'color', 'default' => '#22d3ee' ),
		'radius' => array( 'type' => 'int', 'default' => 12, 'min' => 0, 'max' => 48 ),
		'bg_image' => array( 'type' => 'url', 'default' => '' ),
		'bg_opacity' => array( 'type' => 'int', 'default' => 100, 'min' => 0, 'max' => 100 ),
		// behavior
		'effect' => array( 'type' => 'key', 'default' => 'slide' ),
		'speed' => array( 'type' => 'int', 'default' => 280, 'min' => 50, 'max' => 2000 ),
		'sub_open_dir' => array( 'type' => 'key', 'default' => 'bottom' ),
		'sound' => array( 'type' => 'key', 'default' => 'none' ),
		'volume' => array( 'type' => 'int', 'default' => 40, 'min' => 0, 'max' => 100 ),
		'sticky' => array( 'type' => 'bool', 'default' => 0 ),
		'hamburger' => array( 'type' => 'bool', 'default' => 1 ),
		'breakpoint' => array( 'type' => 'int', 'default' => 768, 'min' => 320, 'max' => 1400 ),
		'mega_cols' => array( 'type' => 'int', 'default' => 3, 'min' => 1, 'max' => 8 ),
		'fullwidth_sub' => array( 'type' => 'bool', 'default' => 0 ),
		// logo
		'logo_url' => array( 'type' => 'url', 'default' => '' ),
		'logo_x' => array( 'type' => 'int', 'default' => 0 ),
		'logo_y' => array( 'type' => 'int', 'default' => 0 ),
		'logo_target' => array( 'type' => 'key', 'default' => 'bar' ),
		'logo_col' => array( 'type' => 'int', 'default' => 1, 'min' => 1, 'max' => 8 ),
		// search
		'search_enabled' => array( 'type' => 'bool', 'default' => 0 ),
		'search_place' => array( 'type' => 'key', 'default' => 'bar-end' ),
		'search_x' => array( 'type' => 'int', 'default' => 0 ),
		'search_y' => array( 'type' => 'int', 'default' => 0 ),
		// mobile
		'mobile_sync' => array( 'type' => 'bool', 'default' => 0 ),
		'mobile_endpoint' => array( 'type' => 'text', 'default' => '' ),
		// CTA
		'cta_text' => array( 'type' => 'text', 'default' => '' ),
		'cta_url' => array( 'type' => 'url', 'default' => '' ),
		'cta_style' => array( 'type' => 'key', 'default' => 'glass-capsule' ),
		'cta_color' => array( 'type' => 'color', 'default' => '#e11d48' ),
		'cta_color2' => array( 'type' => 'color', 'default' => '' ),
		'cta_color_mode' => array( 'type' => 'key', 'default' => 'gradient' ),
		'cta_opacity' => array( 'type' => 'int', 'default' => 100, 'min' => 0, 'max' => 100 ),
		'cta_scale' => array( 'type' => 'int', 'default' => 100, 'min' => 40, 'max' => 200 ),
		'cta_light' => array( 'type' => 'key', 'default' => 'tl' ),
		'cta_font' => array( 'type' => 'text', 'default' => '' ),
		'cta_font_size' => array( 'type' => 'int', 'default' => 14, 'min' => 10, 'max' => 32 ),
		'cta_img' => array( 'type' => 'url', 'default' => '' ),
		'cta_icon' => array( 'type' => 'text', 'default' => '' ),
		'cta_emoji' => array( 'type' => 'text', 'default' => '' ),
		'cta_role' => array( 'type' => 'key', 'default' => 'cta_link' ),
		'cta_pos' => array( 'type' => 'key', 'default' => 'end' ),
		'cta_target' => array( 'type' => 'key', 'default' => 'bar' ),
		'cta_x' => array( 'type' => 'int', 'default' => 0 ),
		'cta_y' => array( 'type' => 'int', 'default' => 0 ),
		'cta_col' => array( 'type' => 'int', 'default' => 1, 'min' => 1, 'max' => 8 ),
		'cta_radius' => array( 'type' => 'int', 'default' => 22, 'min' => 0, 'max' => 999 ),
		'cta_size' => array( 'type' => 'key', 'default' => 'md' ),
		// meta
		'items' => array( 'type' => 'array', 'default' => array() ),
		'_version' => array( 'type' => 'int', 'default' => 1 ),
		'updated_at' => array( 'type' => 'int', 'default' => 0 ),
	),
);
