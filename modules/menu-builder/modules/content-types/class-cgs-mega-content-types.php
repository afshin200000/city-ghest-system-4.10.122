<?php
/**
 * CGS Mega Content Types — registry + structural layout chrome
 * v4.10.91: row/column are real containers (children rendered inside), not empty shells.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CGS_Mega_Content_Types {

	/** Registry labels for admin UI */
	public static function types() {
		return array(
			'link'                => 'لینک',
			'heading'             => 'عنوان',
			'image'               => 'تصویر',
			'card'                => 'کارت',
			'brand'               => 'برند / لوگو',
			'product_slider'      => 'اسلایدر محصول (ثابت)',
			'product_slider_live' => 'اسلایدر محصول زنده',
			'woo_products'        => 'محصولات ووکامرس',
			'dynamic_terms'       => 'ترم‌های پویا (دسته/برچسب/تاکسونومی)',
			'widget'              => 'ویجت زنده وردپرس',
			'column'              => 'ستون (گروه‌بندی)',
			'row'                 => 'ردیف',
			'divider'             => 'جداکننده',
		);
	}

	/**
	 * Public contract used by admin localization and golden tests.
	 * Alias of types() — never call a non-existent method from bootstrap.
	 */
	public static function list_types() {
		return self::types();
	}

	/** Structural types that act as layout containers */
	public static function structural_types() {
		return array( 'column', 'row', 'divider' );
	}

	/** Max nesting depth contract (must match sanitize + render_items) */
	public static function max_depth() {
		return 5;
	}

	/**
	 * Whether item is a layout container (row/column).
	 *
	 * @param array $it Item.
	 * @return bool
	 */
	public static function is_container( $it ) {
		$ct = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : '';
		return in_array( $ct, array( 'row', 'column' ), true );
	}

	/**
	 * Column span 1–12 (12-column grid contract).
	 *
	 * @param array $it Item.
	 * @return int
	 */
	public static function column_span( $it ) {
		$w = isset( $it['col'] ) ? intval( $it['col'] ) : 3;
		if ( isset( $it['col_span'] ) ) {
			$w = intval( $it['col_span'] );
		}
		return max( 1, min( 12, $w ) );
	}

	/**
	 * Open structural chrome. Caller must close via close_structural().
	 * Divider is self-closing (returns full HTML, open+close not needed).
	 *
	 * @param array $it Item.
	 * @return string HTML open tag(s) or full divider.
	 */
	public static function open_structural( $it ) {
		$ct = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : '';
		if ( $ct === 'divider' ) {
			return '<hr class="cgs-menu-divider" aria-hidden="true">';
		}
		if ( $ct === 'row' ) {
			$gap = isset( $it['gap'] ) ? sanitize_html_class( $it['gap'] ) : '';
			$cls = 'cgs-mega-row' . ( $gap ? ' cgs-gap-' . $gap : '' );
			return '<div class="' . esc_attr( $cls ) . '" data-ct="row" role="presentation">';
		}
		if ( $ct === 'column' ) {
			$span = self::column_span( $it );
			$cls  = 'cgs-mega-col cgs-col-' . $span;
			if ( ! empty( $it['col_bg'] ) ) {
				$cls .= ' has-bg';
			}
			$style = '';
			if ( ! empty( $it['col_bg'] ) ) {
				$style = ' style="background:' . esc_attr( $it['col_bg'] ) . '"';
			}
			return '<div class="' . esc_attr( $cls ) . '" data-ct="column" data-span="' . esc_attr( (string) $span ) . '"' . $style . ' role="presentation">';
		}
		return '';
	}

	/**
	 * Close structural chrome for row/column.
	 *
	 * @param array $it Item.
	 * @return string
	 */
	public static function close_structural( $it ) {
		$ct = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : '';
		if ( in_array( $ct, array( 'row', 'column' ), true ) ) {
			return '</div>';
		}
		return '';
	}

	/**
	 * Legacy helper — full structural block without children (divider only useful).
	 * Prefer open/close + children render in bootstrap::render_items.
	 *
	 * @param array $it Item.
	 * @return string
	 */
	public static function render_structural( $it ) {
		$ct = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : '';
		if ( $ct === 'divider' ) {
			return self::open_structural( $it );
		}
		// Empty shell discouraged — containers need children via render_items.
		return self::open_structural( $it ) . self::close_structural( $it );
	}
}
