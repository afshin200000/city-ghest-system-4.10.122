<?php
/**
 * CGS Mega Dynamic Sources — WooCommerce products, taxonomy terms, live widgets.
 * Original implementation inspired by commercial mega-menu patterns (not a copy).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CGS_Mega_Dynamic {

	public static function init() {
		add_action( 'wp_ajax_cgs_menu_dynamic_query', array( __CLASS__, 'ajax_query' ) );
		add_action( 'wp_ajax_cgs_menu_list_widgets', array( __CLASS__, 'ajax_list_widgets' ) );
		add_action( 'wp_ajax_cgs_menu_list_taxonomies', array( __CLASS__, 'ajax_list_taxonomies' ) );
	}

	/** Registered sidebars/widgets for mega injection */
	public static function list_widget_areas() {
		global $wp_registered_sidebars;
		$out = array();
		if ( is_array( $wp_registered_sidebars ) ) {
			foreach ( $wp_registered_sidebars as $id => $sb ) {
				$out[ $id ] = isset( $sb['name'] ) ? $sb['name'] : $id;
			}
		}
		// Always offer a dedicated mega area name (may be empty until registered)
		$out['cgs-mega-widget-1'] = 'ناحیه ویجت مگا ۱ (CGS)';
		$out['cgs-mega-widget-2'] = 'ناحیه ویجت مگا ۲ (CGS)';
		return $out;
	}

	public static function register_mega_sidebars() {
		register_sidebar( array(
			'name'          => 'CGS مگا منو — ناحیه ۱',
			'id'            => 'cgs-mega-widget-1',
			'description'   => 'ویجت‌های این ناحیه در آیتم‌های نوع widget مگامنو نمایش داده می‌شوند.',
			'before_widget' => '<div class="cgs-mega-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<div class="cgs-mega-widget-title">',
			'after_title'   => '</div>',
		) );
		register_sidebar( array(
			'name'          => 'CGS مگا منو — ناحیه ۲',
			'id'            => 'cgs-mega-widget-2',
			'description'   => 'ویجت‌های این ناحیه در آیتم‌های نوع widget مگامنو نمایش داده می‌شوند.',
			'before_widget' => '<div class="cgs-mega-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<div class="cgs-mega-widget-title">',
			'after_title'   => '</div>',
		) );
	}

	public static function list_taxonomies() {
		$taxes = get_taxonomies( array( 'public' => true ), 'objects' );
		$out = array();
		foreach ( $taxes as $tx ) {
			$out[ $tx->name ] = $tx->labels->name . ' (' . $tx->name . ')';
		}
		if ( empty( $out ) ) {
			$out['category'] = 'دسته‌بندی نوشته';
			$out['post_tag'] = 'برچسب';
		}
		return $out;
	}

	/** Query products / terms for preview & front */
	public static function query( $args ) {
		$type = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : '';
		$limit = isset( $args['limit'] ) ? max( 1, min( 24, (int) $args['limit'] ) ) : 6;
		$out = array();

		if ( $type === 'woo_products' || $type === 'product_slider' ) {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return array( 'ok' => true, 'items' => array(), 'notice' => 'WooCommerce فعال نیست' );
			}
			$q = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => isset( $args['orderby'] ) ? sanitize_key( $args['orderby'] ) : 'date',
				'order'          => 'DESC',
			);
			if ( ! empty( $args['category'] ) ) {
				$q['tax_query'] = array( array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_title( $args['category'] ),
				) );
			}
			if ( ! empty( $args['on_sale'] ) ) {
				$q['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
			}
			$posts = get_posts( $q );
			foreach ( $posts as $p ) {
				$prod = wc_get_product( $p->ID );
				if ( ! $prod ) { continue; }
				$img = get_the_post_thumbnail_url( $p->ID, 'woocommerce_thumbnail' );
				$out[] = array(
					'id'    => $p->ID,
					'title' => get_the_title( $p->ID ),
					'url'   => get_permalink( $p->ID ),
					'price' => wp_strip_all_tags( $prod->get_price_html() ),
					'image' => $img ? $img : '',
					'badge' => $prod->is_on_sale() ? 'SALE' : '',
				);
			}
			return array( 'ok' => true, 'items' => $out );
		}

		if ( $type === 'terms' || $type === 'dynamic_terms' ) {
			$tax = isset( $args['taxonomy'] ) ? sanitize_key( $args['taxonomy'] ) : 'category';
			$parent = isset( $args['parent'] ) ? (int) $args['parent'] : 0;
			$hide_empty = ! empty( $args['hide_empty'] );
			$terms = get_terms( array(
				'taxonomy'   => $tax,
				'hide_empty' => $hide_empty,
				'number'     => $limit,
				'parent'     => $parent,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );
			if ( is_wp_error( $terms ) ) {
				return array( 'ok' => false, 'items' => array(), 'error' => $terms->get_error_message() );
			}
			foreach ( $terms as $t ) {
				$out[] = array(
					'id'    => $t->term_id,
					'title' => $t->name,
					'url'   => get_term_link( $t ),
					'count' => (int) $t->count,
					'slug'  => $t->slug,
				);
			}
			return array( 'ok' => true, 'items' => $out );
		}

		if ( $type === 'widget' ) {
			$area = isset( $args['sidebar'] ) ? sanitize_key( $args['sidebar'] ) : 'cgs-mega-widget-1';
			ob_start();
			if ( is_active_sidebar( $area ) ) {
				dynamic_sidebar( $area );
			} else {
				echo '<div class="cgs-mega-widget-empty">ویجتی در این ناحیه نیست — از نمایش > ابزارک‌ها اضافه کنید.</div>';
			}
			$html = ob_get_clean();
			return array( 'ok' => true, 'html' => $html );
		}

		return array( 'ok' => false, 'items' => array(), 'error' => 'unknown type' );
	}

	public static function ajax_query() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$args = array(
			'type'      => isset( $_POST['dtype'] ) ? sanitize_key( wp_unslash( $_POST['dtype'] ) ) : '',
			'limit'     => isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 6,
			'taxonomy'  => isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : 'category',
			'parent'    => isset( $_POST['parent'] ) ? (int) $_POST['parent'] : 0,
			'category'  => isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '',
			'orderby'   => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'date',
			'on_sale'   => ! empty( $_POST['on_sale'] ),
			'hide_empty'=> ! empty( $_POST['hide_empty'] ),
			'sidebar'   => isset( $_POST['sidebar'] ) ? sanitize_key( wp_unslash( $_POST['sidebar'] ) ) : 'cgs-mega-widget-1',
		);
		wp_send_json_success( self::query( $args ) );
	}

	public static function ajax_list_widgets() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		wp_send_json_success( array( 'areas' => self::list_widget_areas() ) );
	}

	public static function ajax_list_taxonomies() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		wp_send_json_success( array( 'taxonomies' => self::list_taxonomies() ) );
	}

	/** Resolve dynamic item on front render */
	public static function resolve_item( $it ) {
		$ct = isset( $it['content_type'] ) ? $it['content_type'] : ( isset( $it['display'] ) ? $it['display'] : 'link' );
		if ( $ct === 'woo_products' || $ct === 'product_slider_live' ) {
			$res = self::query( array(
				'type'     => 'woo_products',
				'limit'    => isset( $it['limit'] ) ? (int) $it['limit'] : 6,
				'category' => isset( $it['woo_cat'] ) ? $it['woo_cat'] : '',
				'orderby'  => isset( $it['orderby'] ) ? $it['orderby'] : 'date',
				'on_sale'  => ! empty( $it['on_sale'] ),
			) );
			$it['_resolved'] = isset( $res['items'] ) ? $res['items'] : array();
		} elseif ( $ct === 'dynamic_terms' ) {
			$res = self::query( array(
				'type'       => 'terms',
				'taxonomy'   => isset( $it['taxonomy'] ) ? $it['taxonomy'] : 'category',
				'limit'      => isset( $it['limit'] ) ? (int) $it['limit'] : 12,
				'parent'     => isset( $it['term_parent'] ) ? (int) $it['term_parent'] : 0,
				'hide_empty' => ! empty( $it['hide_empty'] ),
			) );
			$it['_resolved'] = isset( $res['items'] ) ? $res['items'] : array();
		} elseif ( $ct === 'widget' ) {
			$res = self::query( array(
				'type'    => 'widget',
				'sidebar' => isset( $it['sidebar'] ) ? $it['sidebar'] : 'cgs-mega-widget-1',
			) );
			$it['_widget_html'] = isset( $res['html'] ) ? $res['html'] : '';
		}
		return $it;
	}
}
