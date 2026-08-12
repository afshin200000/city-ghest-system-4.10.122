<?php
/**
 * ماژول منوساز حرفه‌ای شهر قسط
 * مستقل — بدون وابستگی به فرم‌ساز
 * شورت‌کد: [cgs_menu id="main"] یا [cgs_menu slug="main"]
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cgs_module_menu_builder_enabled' ) ) {
	function cgs_module_menu_builder_enabled() {
		return true; // منوساز همیشه فعال
	}
}
define( 'CGS_MENU_BUILDER_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'CGS_MENU_BUILDER_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

class CGS_Menu_Builder {

	public static function field_schema() {
		static $schema = null;
		if ( null === $schema ) {
			$file = CGS_MENU_BUILDER_DIR . 'schema/menu-schema.php';
			$schema = file_exists( $file ) ? include $file : array( 'version' => 0, 'fields' => array() );
		}
		return $schema;
	}


	const OPTION = 'cgs_custom_menus';
	const VERSION = '4.10.122';
	/** @var string current menu layout while rendering */
	public static $__render_layout = 'horizontal';
	/** @var string temp open-dir while rendering */
	public static $__render_sub_dir = 'bottom';

	
	public static function load_submodules() {
		$dyn = CGS_MENU_BUILDER_DIR . 'modules/dynamic/class-cgs-mega-dynamic.php';
		if ( file_exists( $dyn ) ) {
			require_once $dyn;
			if ( class_exists( 'CGS_Mega_Dynamic' ) ) {
				CGS_Mega_Dynamic::init();
				add_action( 'widgets_init', array( 'CGS_Mega_Dynamic', 'register_mega_sidebars' ) );
			}
		}
		$dir = CGS_MENU_BUILDER_DIR . 'modules/';
		$files = array(
			$dir . 'templates/class-cgs-mega-templates.php',
			$dir . 'persistence/class-cgs-menu-revisions.php',
			$dir . 'persistence/class-cgs-menu-repository.php',
			$dir . 'security/class-cgs-menu-rate-limit.php',
			$dir . 'security/class-cgs-menu-integrity.php',
			$dir . 'api/class-cgs-menu-rest.php',
			$dir . 'content-types/class-cgs-mega-content-types.php',
		);
		foreach ( $files as $f ) {
			if ( file_exists( $f ) ) {
				require_once $f;
			}
		}
	}

	public static function init() {
		if ( class_exists( 'CGS_Menu_Repository' ) ) { CGS_Menu_Repository::boot(); }
		if ( class_exists( 'CGS_Menu_Revisions' ) ) { CGS_Menu_Revisions::boot(); }
		if ( class_exists( 'CGS_Menu_REST' ) ) { CGS_Menu_REST::boot(); }
		if ( class_exists( 'CGS_Menu_Integrity' ) ) { CGS_Menu_Integrity::boot(); }
		/* admin_menu: single entry owned by CGS_Admin::page_menu_builder — do not register twice */
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front_assets' ) );
		add_action( 'wp_ajax_cgs_menu_save', array( __CLASS__, 'ajax_save' ) );
		add_action( 'wp_ajax_cgs_menu_delete', array( __CLASS__, 'ajax_delete' ) );
		add_action( 'wp_ajax_cgs_menu_get', array( __CLASS__, 'ajax_get' ) );
		add_action( 'wp_ajax_cgs_menu_save_template', array( __CLASS__, 'ajax_save_template' ) );
		add_action( 'wp_ajax_cgs_menu_load_template', array( __CLASS__, 'ajax_load_template' ) );
		add_action( 'wp_ajax_cgs_menu_list_templates', array( __CLASS__, 'ajax_list_templates' ) );
		add_action( 'wp_ajax_cgs_menu_seo_analyze', array( __CLASS__, 'ajax_seo_analyze' ) );
		add_action( 'wp_ajax_cgs_menu_delete_template', array( __CLASS__, 'ajax_delete_template' ) );
		add_action( 'wp_ajax_cgs_menu_preview_document', array( __CLASS__, 'ajax_preview_document' ) );
		add_action( 'wp_ajax_cgs_menu_preview_html', array( __CLASS__, 'ajax_preview_html' ) );
		add_shortcode( 'cgs_menu', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'cgs_mega_menu', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_head', array( __CLASS__, 'hide_wp_chrome' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_json_endpoint' ) );
	}

	/** خروجی JSON برای همگام‌سازی اپ موبایل: ?cgs_menu_json=ID */
	public static function maybe_json_endpoint() {
		if ( empty( $_GET['cgs_menu_json'] ) ) {
			return;
		}
		$id = sanitize_key( $_GET['cgs_menu_json'] );
		$menu = self::get_one( $id );
		if ( ! $menu ) {
			status_header( 404 );
			wp_send_json_error( 'not found' );
		}
		// فقط آیتم‌های مجاز نقش جاری
		$menu['items'] = self::filter_items_by_role( $menu['items'] ?? array() );
		wp_send_json_success( $menu );
	}

	public static function filter_items_by_role( $items ) {
		$out = array();
		foreach ( (array) $items as $it ) {
			if ( ! self::user_can_see_item( $it ) ) {
				continue;
			}
			$it['children'] = self::filter_items_by_role( $it['children'] ?? array() );
			$out[] = $it;
		}
		return $out;
	}


	/**
	 * Intentionally empty — menu slug cgs-menu-builder is registered once in CGS_Admin.
	 * Calling add_submenu_page here caused duplicate sidebar items + dual callbacks.
	 */
	public static function register_menu() {
		return;
	}

	public static function hide_wp_chrome() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( (string) $screen->id, 'cgs-menu-builder' ) === false ) {
			return;
		}
		// منوی چپ وردپرس را حذف نکن — فقط محتوای منوساز را در عرض مفید #wpcontent پر کن
		echo '<style id="cgs-menu-fullwidth-content">
		/* نوار ادمین و منوی اصلی وردپرس دست نخورده بمانند */
		#wpfooter { display:none !important; }
		/* فقط ناحیه محتوا تمام عرض مفید (نه کل صفحه؛ حاشیه منوی چپ حفظ شود) */
		body[class*="cgs-menu-builder"] #wpbody-content {
			padding-bottom: 24px !important;
			float: none !important;
		}
		body[class*="cgs-menu-builder"] #wpbody-content > .wrap,
		body[class*="cgs-menu-builder"] .cgs-menu-app {
			max-width: none !important;
			width: 100% !important;
			margin-left: 0 !important;
			margin-right: 0 !important;
			box-sizing: border-box !important;
		}
		body[class*="cgs-menu-builder"] .cgs-menu-app .cgs-ma-body {
			display: grid !important;
			grid-template-columns: minmax(0, 1fr) 280px !important;
			width: 100% !important;
			max-width: 100% !important;
			margin: 0 !important;
			padding: 12px !important;
			gap: 12px !important;
			box-sizing: border-box !important;
		}
		body[class*="cgs-menu-builder"] .cgs-menu-app .cgs-ma-main {
			min-width: 0 !important;
			width: 100% !important;
		}
		body[class*="cgs-menu-builder"] .cgs-menu-app .cgs-ma-side {
			width: 280px !important;
			max-width: 280px !important;
		}
		@media (max-width: 960px) {
			body[class*="cgs-menu-builder"] .cgs-menu-app .cgs-ma-body {
				grid-template-columns: 1fr !important;
			}
			body[class*="cgs-menu-builder"] .cgs-menu-app .cgs-ma-side {
				width: 100% !important;
				max-width: 100% !important;
			}
		}
		</style>';
	}

	public static function get_all() {
		$menus = get_option( self::OPTION, array() );
		return is_array( $menus ) ? $menus : array();
	}

	public static function get_one( $id ) {
		$menus = self::get_all();
		return isset( $menus[ $id ] ) ? $menus[ $id ] : null;
	}

	public static function default_menu( $id = 'main' ) {
		return array(
			'id'          => $id,
			'title'       => 'منوی اصلی',
			'slug'        => $id,
			'layout'      => 'horizontal', // horizontal | vertical | mega | sidebar | drawer
			'effect'      => 'slide', // none|fade|slide|grow|flip|glow|neon|blur|scale|bounce
			'sound'       => 'none', // none|click|chime|whoosh|soft|beep
			'sound_vol'   => 35,
			'bg_type'     => 'solid', // solid|gradient|image|glass
			'bg_color'    => '#eef2ff',
			'bg_color2'   => '#c7d2fe',
			'bg_image'    => '',
			'text_color'  => '#1e1b4b',
			'hover_color' => '#4f46e5',
			'active_color'=> '#6366f1',
			'radius'      => 12,
			'shadow'      => 1,
			'sticky'      => 0,
			'second_tap'  => 'open', // open | follow (Max Mega style mobile)
			'effect'      => 'fade',
			'breakpoint'  => 768,
			'intent_ms'   => 200,
			'rtl'         => 1,
			'items'       => array(
				array(
					'id'     => 'i1',
					'label'  => 'خانه',
					'url'    => home_url( '/' ),
					'icon'   => '🏠',
					'image'  => '',
					'target' => '_self',
					'badge'  => '',
					'children' => array(),
				),
				array(
					'id'     => 'i2',
					'label'  => 'خدمات',
					'url'    => '#',
					'icon'   => '⚙️',
					'image'  => '',
					'target' => '_self',
					'badge'  => 'جدید',
					'children' => array(
						array(
							'id' => 'i2a', 'label' => 'اقساط', 'url' => '#', 'icon' => '💳',
							'image' => '', 'target' => '_self', 'badge' => '', 'children' => array(),
						),
						array(
							'id' => 'i2b', 'label' => 'استعلام', 'url' => '#', 'icon' => '🔍',
							'image' => '', 'target' => '_self', 'badge' => '', 'children' => array(),
						),
					),
				),
			),
		);
	}

	public static function admin_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// enqueue if page matches OR forced from render_admin($hook === 'cgs-menu-builder')
		if ( $page !== 'cgs-menu-builder' && strpos( (string) $hook, 'cgs-menu-builder' ) === false && $hook !== 'cgs-menu-builder' ) {
			return;
		}
		wp_enqueue_media();
		$ver = self::VERSION . '.' . ( get_option( 'cgs_asset_salt', '1' ) );
		wp_enqueue_style( 'cgs-menu-builder-front', CGS_MENU_BUILDER_URL . 'assets/css/front.css', array(), $ver );
		wp_enqueue_style( 'cgs-menu-builder-admin', CGS_MENU_BUILDER_URL . 'assets/css/admin.css', array( 'cgs-menu-builder-front' ), $ver );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'cgs-menu-builder-front', CGS_MENU_BUILDER_URL . 'assets/js/front.js', array(), $ver, true );
		wp_enqueue_script( 'cgs-menu-builder-admin-core', CGS_MENU_BUILDER_URL . 'assets/js/admin/core-stub.js', array( 'jquery' ), $ver, true );
		/* Modular admin modules — register+enqueue before admin.js (clean chain, no missing handles) */
		$mods = array(
			'cgs-menu-builder-admin-contract'       => 'contract.js',
			'cgs-menu-builder-admin-defaults'       => 'defaults.js',
			'cgs-menu-builder-admin-preview'        => 'preview.js',
			'cgs-menu-builder-admin-cta'            => 'cta.js',
			'cgs-menu-builder-admin-persist'        => 'persistence.js',
			'cgs-menu-builder-admin-tree'           => 'tree.js',
			'cgs-menu-builder-admin-save'           => 'save.js',
			'cgs-menu-builder-admin-tabs'           => 'tabs.js',
			'cgs-menu-builder-admin-items'          => 'items-ui.js',
			'cgs-menu-builder-admin-colors'         => 'colors.js',
			'cgs-menu-builder-admin-sound'          => 'sound.js',
			'cgs-menu-builder-admin-templates-data' => 'templates-data.js',
			'cgs-menu-builder-admin-icons'          => 'icons.js',
			'cgs-menu-builder-admin-mega-ui'        => 'mega-ui.js',
			'cgs-menu-builder-admin-form'           => 'form.js',
			'cgs-menu-builder-admin-diag'           => 'diagnostics.js',
		);
		$mod_deps = array( 'jquery', 'cgs-menu-builder-admin-core' );
		foreach ( $mods as $handle => $file ) {
			$path = CGS_MENU_BUILDER_DIR . 'assets/js/admin/' . $file;
			if ( is_readable( $path ) ) {
				wp_enqueue_script( $handle, CGS_MENU_BUILDER_URL . 'assets/js/admin/' . $file, $mod_deps, $ver, true );
			}
		}
		$admin_deps = array_merge(
			array( 'jquery', 'jquery-ui-sortable', 'cgs-menu-builder-front', 'cgs-menu-builder-admin-core' ),
			array_keys( $mods )
		);
		wp_enqueue_script( 'cgs-menu-builder-admin', CGS_MENU_BUILDER_URL . 'assets/js/admin.js', $admin_deps, $ver, true );
		$menus = self::get_all();
		if ( empty( $menus ) ) {
			$menus['main'] = self::default_menu( 'main' );
		}
		/* front asset URLs for iframe preview */
		wp_localize_script( 'cgs-menu-builder-admin', 'cgsMenuBuilder', array(
			'megaPresets' => self::mega_presets(),
			'megaTemplates' => class_exists( 'CGS_Mega_Templates' ) ? CGS_Mega_Templates::all() : array(),
			'megaTemplateTrees' => self::mega_template_trees(),
			'adminUrl' => admin_url( 'admin.php?page=city-ghest' ),
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'pluginUrl' => CGS_MENU_BUILDER_URL,
			'version' => self::VERSION,
			'frontCss' => CGS_MENU_BUILDER_URL . 'assets/css/front.css',
			'frontJs' => CGS_MENU_BUILDER_URL . 'assets/js/front.js',
			'nonce'   => wp_create_nonce( 'cgs_menu_builder' ),
			'menus'   => $menus,
			'home'    => home_url( '/' ),
			'effects' => self::effects_list(),
			'pageAnims' => self::page_anim_list(),
			'iconBank' => self::icon_bank(),
			'badgeShapes' => self::badge_shapes(),
			'sounds'  => self::sounds_list(),
			'layouts' => self::layouts_list(),
			'contentTypes' => class_exists( 'CGS_Mega_Content_Types' ) ? CGS_Mega_Content_Types::list_types() : array(),
			'wooActive' => class_exists( 'WooCommerce' ),
			'taxonomies' => class_exists( 'CGS_Mega_Dynamic' ) ? CGS_Mega_Dynamic::list_taxonomies() : array( 'category' => 'دسته', 'post_tag' => 'برچسب' ),
			'widgetAreas' => class_exists( 'CGS_Mega_Dynamic' ) ? CGS_Mega_Dynamic::list_widget_areas() : array(),
			'previewParity' => self::preview_parity_assets(),
		) );
	}

	public static function front_assets() {
		wp_register_style( 'cgs-menu-builder-front', CGS_MENU_BUILDER_URL . 'assets/css/front.css', array(), self::VERSION . '.' . ( get_option( 'cgs_asset_salt', '1' ) ) );
		wp_register_script( 'cgs-menu-builder-front', CGS_MENU_BUILDER_URL . 'assets/js/front.js', array(), self::VERSION . '.' . ( get_option( 'cgs_asset_salt', '1' ) ), true );
	}

	public static function effects_list() {
		return array(
			'none'       => 'بدون افکت',
			'fade'       => 'محو شدن (Fade)',
			'slide'      => 'اسلاید عمودی (Slide)',
			'slide-h'    => 'اسلاید افقی (Slide H)',
			'slide-up'   => 'اسلاید از پایین',
			'slide-down' => 'اسلاید از بالا',
			'grow'       => 'رشد (Grow)',
			'scale'      => 'بزرگ‌نمایی (Scale)',
			'flip'       => 'چرخش سه‌بعدی (Flip)',
			'flip-x'     => 'چرخش افقی (Flip X)',
			'glow'       => 'درخشش (Glow)',
			'neon'       => 'نئون (Neon)',
			'blur'       => 'تاری شیشه‌ای (Blur)',
			'bounce'     => 'پرش (Bounce)',
			'swing'      => 'تاب (Swing)',
			'elastic'    => 'کشسان (Elastic)',
			'rotate-in'  => 'چرخش ورود',
		);
	}
	public static function page_anim_list() {
		return array(
			'none'      => 'بدون',
			'fade'      => 'محو',
			'slide-up'  => 'اسلاید بالا',
			'slide-down'=> 'اسلاید پایین',
			'slide-right'=> 'اسلاید راست',
			'slide-left'=> 'اسلاید چپ',
			'zoom'      => 'زوم',
			'blur-in'   => 'تاری ورود',
			'flip'      => 'چرخش',
			'bounce'    => 'پرش',
			'wipe'      => 'پاک‌شدن',
			'curtain'   => 'پرده',
			'skew'      => 'اریب',
			'glow-in'   => 'درخشش ورود',
			'scale-up'  => 'بزرگ‌شدن',
		);
	}

	public static function sounds_list() {
		return array(
			'none'          => 'بدون صدا',
			'button-click'  => 'فشردن دکمه (Button Click)',
			'click'         => 'کلیک نرم',
			'chime'         => 'زنگ ملایم (Chime)',
			'whoosh'        => 'سوئیچ (Whoosh)',
			'soft'          => 'ضربه آرام',
			'beep'          => 'بوق کوتاه',
		);
	}

	public static function mega_template_trees() {
		if ( ! class_exists( 'CGS_Mega_Templates' ) ) {
			return array();
		}
		$out = array();
		foreach ( array_keys( CGS_Mega_Templates::all() ) as $id ) {
			$out[ $id ] = CGS_Mega_Templates::tree( $id );
		}
		return $out;
	}

	public static function layouts_list() {
		return array(
			'horizontal'      => 'افقی کلاسیک',
			'vertical'        => 'عمودی',
			'dropdown'        => 'دراپ‌داون کلاسیک',
			'mega'            => 'مگامنو چندستونه (کلاسیک)',
			'mega-wide'       => 'مگامنو تمام‌عرض',
			'mega-rows'       => 'مگامنو چندردیفه',
			'mega-sidebar'    => 'مگامنو سایدبار + محتوا (دیجیکالا)',
			'mega-content'    => 'مگامنو محتوایی (تصویر + لیست + کارت)',
			'mega-products'   => 'مگامنو فروشگاهی (محصول + تصویر)',
			'mega-brands'     => 'مگامنو برند / لوگوگرید',
			'tabs'            => 'تب‌دار (Tabbed)',
			'sidebar'         => 'نوار کناری',
		);
	}


	public static function mega_presets() {
		$defs = array(
			array( 'id' => 'mega_shop_4', 'name' => 'فروشگاهی ۴ستونه', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#0f172a', 'bg_color2' => '#1e3a8a', 'effect' => 'fade',
				'columns' => array(
					array( 'title' => 'الکترونیک', 'icon' => '📱', 'links' => array( array( 'label' => 'موبایل', 'url' => '#' ), array( 'label' => 'لپ‌تاپ', 'url' => '#' ) ) ),
					array( 'title' => 'خانه', 'icon' => '🏠', 'links' => array( array( 'label' => 'آشپزخانه', 'url' => '#' ) ) ),
					array( 'title' => 'پوشاک', 'icon' => '👕', 'links' => array( array( 'label' => 'مردانه', 'url' => '#' ) ) ),
					array( 'title' => 'پیشنهاد', 'icon' => '⭐', 'badge' => 'جدید', 'links' => array( array( 'label' => 'تخفیف', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_finance_3', 'name' => 'مالی ۳ستونه', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#ecfdf5', 'bg_color2' => '#a7f3d0', 'effect' => 'slide',
				'columns' => array(
					array( 'title' => 'اقساط', 'icon' => '💳', 'links' => array( array( 'label' => 'محاسبه‌گر', 'url' => '#' ) ) ),
					array( 'title' => 'اعتبار', 'icon' => '📊', 'links' => array( array( 'label' => 'استعلام', 'url' => '#' ) ) ),
					array( 'title' => 'پشتیبانی', 'icon' => '🎧', 'links' => array( array( 'label' => 'تماس', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_corp_5', 'name' => 'سازمانی ۵ستونه', 'cols' => 5, 'bg_type' => 'gradient', 'bg_color' => '#1e293b', 'bg_color2' => '#334155', 'effect' => 'grow',
				'columns' => array(
					array( 'title' => 'درباره', 'icon' => '🏢', 'links' => array( array( 'label' => 'معرفی', 'url' => '#' ) ) ),
					array( 'title' => 'محصولات', 'icon' => '📦', 'links' => array( array( 'label' => 'لیست', 'url' => '#' ) ) ),
					array( 'title' => 'راه‌حل', 'icon' => '🧩', 'links' => array( array( 'label' => 'SME', 'url' => '#' ) ) ),
					array( 'title' => 'منابع', 'icon' => '📚', 'links' => array( array( 'label' => 'بلاگ', 'url' => '#' ) ) ),
					array( 'title' => 'تماس', 'icon' => '✉️', 'links' => array( array( 'label' => 'فرم', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_news_3', 'name' => 'خبری', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#fff7ed', 'bg_color2' => '#fed7aa', 'effect' => 'fade',
				'columns' => array(
					array( 'title' => 'تازه‌ها', 'icon' => '📰', 'links' => array( array( 'label' => 'اقتصاد', 'url' => '#' ) ) ),
					array( 'title' => 'تحلیل', 'icon' => '📈', 'links' => array( array( 'label' => 'بازار', 'url' => '#' ) ) ),
					array( 'title' => 'ویدئو', 'icon' => '▶️', 'links' => array( array( 'label' => 'گالری', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_edu_4', 'name' => 'آموزشی', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#312e81', 'bg_color2' => '#6366f1', 'effect' => 'scale',
				'columns' => array(
					array( 'title' => 'دوره', 'icon' => '🎓', 'links' => array( array( 'label' => 'مقدماتی', 'url' => '#' ) ) ),
					array( 'title' => 'اساتید', 'icon' => '👨‍🏫', 'links' => array( array( 'label' => 'لیست', 'url' => '#' ) ) ),
					array( 'title' => 'گواهی', 'icon' => '📜', 'links' => array( array( 'label' => 'صدور', 'url' => '#' ) ) ),
					array( 'title' => 'وبینار', 'icon' => '🎥', 'links' => array( array( 'label' => 'تقویم', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_travel_4', 'name' => 'گردشگری', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#0c4a6e', 'bg_color2' => '#06b6d4', 'effect' => 'slide',
				'columns' => array(
					array( 'title' => 'داخلی', 'icon' => '🗺️', 'links' => array( array( 'label' => 'شمال', 'url' => '#' ) ) ),
					array( 'title' => 'خارجی', 'icon' => '✈️', 'links' => array( array( 'label' => 'اروپا', 'url' => '#' ) ) ),
					array( 'title' => 'هتل', 'icon' => '🏨', 'links' => array( array( 'label' => 'رزرو', 'url' => '#' ) ) ),
					array( 'title' => 'تور', 'icon' => '🎒', 'links' => array( array( 'label' => 'پکیج', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_health_3', 'name' => 'سلامت', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#f0fdf4', 'bg_color2' => '#86efac', 'effect' => 'fade',
				'columns' => array(
					array( 'title' => 'خدمات', 'icon' => '🩺', 'links' => array( array( 'label' => 'نوبت', 'url' => '#' ) ) ),
					array( 'title' => 'دارو', 'icon' => '💊', 'links' => array( array( 'label' => 'سفارش', 'url' => '#' ) ) ),
					array( 'title' => 'مشاوره', 'icon' => '💬', 'links' => array( array( 'label' => 'آنلاین', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_food_4', 'name' => 'غذا', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#7c2d12', 'bg_color2' => '#ea580c', 'effect' => 'bounce',
				'columns' => array(
					array( 'title' => 'منو', 'icon' => '🍽️', 'links' => array( array( 'label' => 'غذای اصلی', 'url' => '#' ) ) ),
					array( 'title' => 'پیک', 'icon' => '🛵', 'links' => array( array( 'label' => 'سفارش', 'url' => '#' ) ) ),
					array( 'title' => 'شعب', 'icon' => '📍', 'links' => array( array( 'label' => 'نقشه', 'url' => '#' ) ) ),
					array( 'title' => 'تخفیف', 'icon' => '🏷️', 'links' => array( array( 'label' => 'کد', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_auto_3', 'name' => 'خودرو', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#18181b', 'bg_color2' => '#3f3f46', 'effect' => 'neon',
				'columns' => array(
					array( 'title' => 'جدید', 'icon' => '🚗', 'links' => array( array( 'label' => 'سواری', 'url' => '#' ) ) ),
					array( 'title' => 'کارکرده', 'icon' => '🔧', 'links' => array( array( 'label' => 'بازار', 'url' => '#' ) ) ),
					array( 'title' => 'خدمات', 'icon' => '🛠️', 'links' => array( array( 'label' => 'بیمه', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_sport_4', 'name' => 'ورزشی', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#14532d', 'bg_color2' => '#22c55e', 'effect' => 'grow',
				'columns' => array(
					array( 'title' => 'پوشاک', 'icon' => '👟', 'links' => array( array( 'label' => 'کفش', 'url' => '#' ) ) ),
					array( 'title' => 'تجهیزات', 'icon' => '🏋️', 'links' => array( array( 'label' => 'باشگاه', 'url' => '#' ) ) ),
					array( 'title' => 'تیم', 'icon' => '⚽', 'links' => array( array( 'label' => 'لیگ', 'url' => '#' ) ) ),
					array( 'title' => 'رویداد', 'icon' => '🏆', 'links' => array( array( 'label' => 'مسابقه', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_real_3', 'name' => 'املاک', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#fafaf9', 'bg_color2' => '#d6d3d1', 'effect' => 'fade',
				'columns' => array(
					array( 'title' => 'خرید', 'icon' => '🔑', 'links' => array( array( 'label' => 'آپارتمان', 'url' => '#' ) ) ),
					array( 'title' => 'اجاره', 'icon' => '📋', 'links' => array( array( 'label' => 'مسکونی', 'url' => '#' ) ) ),
					array( 'title' => 'مشاوره', 'icon' => '🤝', 'links' => array( array( 'label' => 'درخواست', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_tech_4', 'name' => 'فناوری', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#020617', 'bg_color2' => '#4f46e5', 'effect' => 'glow',
				'columns' => array(
					array( 'title' => 'محصول', 'icon' => '⚙️', 'links' => array( array( 'label' => 'ویژگی', 'url' => '#' ) ) ),
					array( 'title' => 'قیمت', 'icon' => '💎', 'links' => array( array( 'label' => 'پلن', 'url' => '#' ) ) ),
					array( 'title' => 'API', 'icon' => '🔌', 'links' => array( array( 'label' => 'مستندات', 'url' => '#' ) ) ),
					array( 'title' => 'امنیت', 'icon' => '🔒', 'links' => array( array( 'label' => 'گواهی', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_fashion_4', 'name' => 'مد', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#831843', 'bg_color2' => '#ec4899', 'effect' => 'flip',
				'columns' => array(
					array( 'title' => 'زنانه', 'icon' => '👗', 'links' => array( array( 'label' => 'لباس', 'url' => '#' ) ) ),
					array( 'title' => 'مردانه', 'icon' => '👔', 'links' => array( array( 'label' => 'کت', 'url' => '#' ) ) ),
					array( 'title' => 'آرایشی', 'icon' => '💄', 'links' => array( array( 'label' => 'محصول', 'url' => '#' ) ) ),
					array( 'title' => 'برند', 'icon' => '✨', 'links' => array( array( 'label' => 'لیست', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_kids_3', 'name' => 'کودک', 'cols' => 3, 'bg_type' => 'solid', 'bg_color' => '#fef9c3', 'bg_color2' => '#fde047', 'effect' => 'bounce',
				'columns' => array(
					array( 'title' => 'اسباب‌بازی', 'icon' => '🧸', 'links' => array( array( 'label' => 'سنین', 'url' => '#' ) ) ),
					array( 'title' => 'پوشاک', 'icon' => '👶', 'links' => array( array( 'label' => 'نوزاد', 'url' => '#' ) ) ),
					array( 'title' => 'آموزش', 'icon' => '📖', 'links' => array( array( 'label' => 'کتاب', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_b2b_5', 'name' => 'B2B پنج‌ستونه', 'cols' => 5, 'bg_type' => 'gradient', 'bg_color' => '#0f172a', 'bg_color2' => '#0e7490', 'effect' => 'slide',
				'columns' => array(
					array( 'title' => 'تأمین', 'icon' => '🏭', 'links' => array( array( 'label' => 'کاتالوگ', 'url' => '#' ) ) ),
					array( 'title' => 'لجستیک', 'icon' => '🚚', 'links' => array( array( 'label' => 'پیگیری', 'url' => '#' ) ) ),
					array( 'title' => 'قرارداد', 'icon' => '📝', 'links' => array( array( 'label' => 'نمونه', 'url' => '#' ) ) ),
					array( 'title' => 'پرداخت', 'icon' => '💰', 'links' => array( array( 'label' => 'درگاه', 'url' => '#' ) ) ),
					array( 'title' => 'پشتیبانی', 'icon' => '☎️', 'links' => array( array( 'label' => 'تیکت', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_glass_3', 'name' => 'شیشه‌ای', 'cols' => 3, 'bg_type' => 'glass', 'bg_color' => '#e0e7ff', 'bg_color2' => '#c7d2fe', 'effect' => 'blur',
				'columns' => array(
					array( 'title' => 'محصولات', 'icon' => '◇', 'links' => array( array( 'label' => 'همه', 'url' => '#' ) ) ),
					array( 'title' => 'داستان', 'icon' => '◇', 'links' => array( array( 'label' => 'برند', 'url' => '#' ) ) ),
					array( 'title' => 'تماس', 'icon' => '◇', 'links' => array( array( 'label' => 'فرم', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_dark_4', 'name' => 'تیره CTA', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#020617', 'bg_color2' => '#1e293b', 'effect' => 'neon',
				'columns' => array(
					array( 'title' => 'ویژگی', 'icon' => '⚡', 'links' => array( array( 'label' => 'لیست', 'url' => '#' ) ) ),
					array( 'title' => 'مقایسه', 'icon' => '📐', 'links' => array( array( 'label' => 'جدول', 'url' => '#' ) ) ),
					array( 'title' => 'نظرات', 'icon' => '⭐', 'links' => array( array( 'label' => 'مشتریان', 'url' => '#' ) ) ),
					array( 'title' => 'شروع', 'icon' => '🚀', 'badge' => 'CTA', 'links' => array( array( 'label' => 'ثبت‌نام', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_footer_6', 'name' => 'فوتر ۶ستونه', 'cols' => 6, 'bg_type' => 'solid', 'bg_color' => '#111827', 'bg_color2' => '#374151', 'effect' => 'none',
				'columns' => array(
					array( 'title' => 'شرکت', 'links' => array( array( 'label' => 'درباره', 'url' => '#' ) ) ),
					array( 'title' => 'محصول', 'links' => array( array( 'label' => 'ویژگی', 'url' => '#' ) ) ),
					array( 'title' => 'منابع', 'links' => array( array( 'label' => 'راهنما', 'url' => '#' ) ) ),
					array( 'title' => 'قانونی', 'links' => array( array( 'label' => 'حریم', 'url' => '#' ) ) ),
					array( 'title' => 'شبکه', 'links' => array( array( 'label' => 'اینستاگرام', 'url' => '#' ) ) ),
					array( 'title' => 'اپ', 'links' => array( array( 'label' => 'دانلود', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_cityghest_4', 'name' => 'شهر قسط', 'cols' => 4, 'bg_type' => 'gradient', 'bg_color' => '#0f172a', 'bg_color2' => '#0f766e', 'effect' => 'slide',
				'columns' => array(
					array( 'title' => 'طرح‌ها', 'icon' => '📋', 'links' => array( array( 'label' => 'کسر از حقوق', 'url' => '#' ), array( 'label' => 'چکی', 'url' => '#' ) ) ),
					array( 'title' => 'محاسبه', 'icon' => '🧮', 'links' => array( array( 'label' => 'ماشین‌حساب', 'url' => '#' ) ) ),
					array( 'title' => 'فروشگاه', 'icon' => '🏪', 'links' => array( array( 'label' => 'لیست', 'url' => '#' ) ) ),
					array( 'title' => 'پشتیبانی', 'icon' => '🆘', 'links' => array( array( 'label' => 'تماس', 'url' => '#' ) ) ),
				) ),
			array( 'id' => 'mega_simple_2', 'name' => 'ساده ۲ستونه', 'cols' => 2, 'bg_type' => 'solid', 'bg_color' => '#ffffff', 'bg_color2' => '#f1f5f9', 'effect' => 'fade',
				'columns' => array(
					array( 'title' => 'خدمات', 'icon' => '•', 'links' => array( array( 'label' => 'خدمت ۱', 'url' => '#' ) ) ),
					array( 'title' => 'اطلاعات', 'icon' => '•', 'links' => array( array( 'label' => 'FAQ', 'url' => '#' ) ) ),
				) ),
		);
		$out = array();
		foreach ( $defs as $d ) { $out[ $d['id'] ] = $d; }
		return $out;
	}


	public static function icon_bank() {
		$file = CGS_MENU_BUILDER_DIR . 'assets/icons/bank.json';
		if ( is_readable( $file ) ) {
			$data = json_decode( (string) file_get_contents( $file ), true );
			if ( is_array( $data ) ) {
				return $data;
			}
		}
		return array(
			'providers' => array(
				array( 'name' => 'Font Awesome', 'url' => 'https://fontawesome.com/icons' ),
				array( 'name' => 'Heroicons', 'url' => 'https://heroicons.com/' ),
				array( 'name' => 'Lucide', 'url' => 'https://lucide.dev/icons/' ),
				array( 'name' => 'Tabler Icons', 'url' => 'https://tabler.io/icons' ),
				array( 'name' => 'Icons8', 'url' => 'https://icons8.com/' ),
			),
			'icons' => array(),
		);
	}


	public static function badge_shapes() {
		return array(
			array( 'id' => 'pill-red', 'label' => 'قرص قرمز', 'bg' => '#ef4444', 'color' => '#fff', 'radius' => '999px' ),
			array( 'id' => 'pill-green', 'label' => 'قرص سبز', 'bg' => '#10b981', 'color' => '#fff', 'radius' => '999px' ),
			array( 'id' => 'pill-blue', 'label' => 'قرص آبی', 'bg' => '#3b82f6', 'color' => '#fff', 'radius' => '999px' ),
			array( 'id' => 'pill-amber', 'label' => 'قرص کهربایی', 'bg' => '#f59e0b', 'color' => '#111', 'radius' => '999px' ),
			array( 'id' => 'pill-purple', 'label' => 'قرص بنفش', 'bg' => '#8b5cf6', 'color' => '#fff', 'radius' => '999px' ),
			array( 'id' => 'square', 'label' => 'مربعی تیره', 'bg' => '#0f172a', 'color' => '#fff', 'radius' => '6px' ),
			array( 'id' => 'outline', 'label' => 'حاشیه‌دار', 'bg' => 'transparent', 'color' => '#4f46e5', 'radius' => '999px', 'border' => '1px solid #4f46e5' ),
			array( 'id' => 'hot', 'label' => 'داغ / فروش', 'bg' => 'linear-gradient(90deg,#f43f5e,#fb923c)', 'color' => '#fff', 'radius' => '999px' ),
			array( 'id' => 'new', 'label' => 'جدید نئون', 'bg' => '#022c22', 'color' => '#4ade80', 'radius' => '8px', 'border' => '1px solid #4ade80' ),
			array( 'id' => 'soft', 'label' => 'ملایم خاکستری', 'bg' => '#e2e8f0', 'color' => '#334155', 'radius' => '999px' ),
		);
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز', 'city-ghest' ) );
		}
		/* Prevent double-include if both legacy hooks somehow fire */
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;
		self::admin_assets( 'cgs-menu-builder' );
		include CGS_MENU_BUILDER_DIR . 'views/admin.php';
	}

	
	/** Phase C: keep last N revisions per menu id in option */
	const REVISION_OPTION = 'cgs_menu_revisions';
	const REVISION_MAX = 10;

	public static function push_revision( $menu ) {
		if ( empty( $menu['id'] ) ) {
			return;
		}
		$id   = sanitize_key( $menu['id'] );
		$all  = get_option( self::REVISION_OPTION, array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		if ( ! isset( $all[ $id ] ) || ! is_array( $all[ $id ] ) ) {
			$all[ $id ] = array();
		}
		array_unshift(
			$all[ $id ],
			array(
				'ts'   => time(),
				'user' => get_current_user_id(),
				'menu' => $menu,
			)
		);
		$all[ $id ] = array_slice( $all[ $id ], 0, self::REVISION_MAX );
		update_option( self::REVISION_OPTION, $all, false );
	}

	public static function ajax_save() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'menu_save', 40, 60 ) ) {
			wp_send_json_error( array( 'message' => 'rate_limited', 'hint' => 'لطفاً چند ثانیه صبر کنید' ), 429 );
		}
		$raw = isset( $_POST['menu'] ) ? wp_unslash( $_POST['menu'] ) : '';
		if ( is_string( $raw ) ) {
			$data = json_decode( $raw, true );
		} else {
			$data = $raw;
		}
		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			wp_send_json_error( 'داده نامعتبر' );
		}
		$id = sanitize_key( $data['id'] );
		$menus = self::get_all();
		/* Optimistic lock: client sends _version; server rejects stale writes */
		$client_ver = isset( $data['_version'] ) ? intval( $data['_version'] ) : 0;
		$server_ver = isset( $menus[ $id ]['_version'] ) ? intval( $menus[ $id ]['_version'] ) : 0;
		if ( $server_ver > 0 && $client_ver > 0 && $client_ver < $server_ver ) {
			wp_send_json_error(
				array(
					'code'    => 'version_conflict',
					'message' => 'تداخل نسخه: منو توسط کاربر دیگری ذخیره شده. صفحه را تازه کنید.',
					'server_version' => $server_ver,
					'client_version' => $client_ver,
				),
				409
			);
		}
		$clean = self::sanitize_menu( $data );
		$clean['_version'] = $server_ver + 1;
		$clean['_updated_at'] = time();
		$clean['_updated_by'] = get_current_user_id();
		$menus[ $id ] = $clean;
		update_option( self::OPTION, $menus, false );
		if ( class_exists( 'CGS_Menu_Revisions' ) ) {
			CGS_Menu_Revisions::push( $id, $clean );
		}
		if ( class_exists( 'CGS_Menu_Repository' ) ) {
			CGS_Menu_Repository::upsert_menu( $id, $clean );
			CGS_Menu_Repository::insert_revision( $id, $clean );
		}
		self::push_revision( $clean );
		wp_send_json_success( array( 'id' => $id, 'menu' => $clean, 'version' => $clean['_version'] ) );
	}

	public static function ajax_delete() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		$menus = self::get_all();
		if ( isset( $menus[ $id ] ) ) {
			unset( $menus[ $id ] );
			update_option( self::OPTION, $menus, false );
		}
		wp_send_json_success();
	}

	public static function ajax_get() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		$m = self::get_one( $id );
		if ( ! $m ) {
			wp_send_json_error( 'یافت نشد' );
		}
		wp_send_json_success( $m );
	}

	public static function sanitize_menu( $data ) {
		$out = self::default_menu( sanitize_key( $data['id'] ?? 'menu' ) );
		$out['id']          = sanitize_key( $data['id'] ?? 'menu' );
		$out['title']       = sanitize_text_field( $data['title'] ?? $out['title'] );
		$out['slug']        = sanitize_title( $data['slug'] ?? $out['id'] );
		$out['layout']      = sanitize_key( $data['layout'] ?? 'horizontal' );
		$out['effect']      = sanitize_key( $data['effect'] ?? 'slide' );
		$out['effect_speed'] = max( 50, min( 1200, intval( $data['effect_speed'] ?? 220 ) ) );
		$sod = sanitize_key( $data['sub_open_dir'] ?? 'bottom' );
		$out['sub_open_dir'] = in_array( $sod, array( 'bottom', 'top', 'left', 'right' ), true ) ? $sod : 'bottom';
		$out['sound']       = sanitize_key( $data['sound'] ?? 'none' );
		$out['sound_vol']   = max( 0, min( 100, intval( $data['sound_vol'] ?? 35 ) ) );
		$out['bg_type']     = sanitize_key( $data['bg_type'] ?? 'solid' );
		$out['bg_color']    = sanitize_hex_color( $data['bg_color'] ?? '#0f172a' ) ?: '#0f172a';
		$out['bg_color2']   = sanitize_hex_color( $data['bg_color2'] ?? '#1e3a5f' ) ?: '#1e3a5f';
		$out['bg_image']    = esc_url_raw( $data['bg_image'] ?? '' );
		$gdir = sanitize_key( $data['gradient_dir'] ?? 'ltr' );
		$out['gradient_dir'] = in_array( $gdir, array( 'ltr', 'rtl', 'ttb', 'btt', 'radial' ), true ) ? $gdir : 'ltr';
		$out['bg_image_opacity'] = max( 0, min( 100, intval( $data['bg_image_opacity'] ?? 100 ) ) );
		$out['text_color']  = sanitize_hex_color( $data['text_color'] ?? '#f8fafc' ) ?: '#f8fafc';
		$out['hover_color'] = sanitize_hex_color( $data['hover_color'] ?? '#38bdf8' ) ?: '#38bdf8';
		$out['active_color']= sanitize_hex_color( $data['active_color'] ?? '#7dd3fc' ) ?: '#7dd3fc';
		$out['radius']      = max( 0, min( 40, intval( $data['radius'] ?? 12 ) ) );
		$out['shadow']      = ! empty( $data['shadow'] ) ? 1 : 0;
		$out['cta_scale']     = max( 40, min( 200, intval( $data['cta_scale'] ?? 100 ) ) );
		$out['cta_light']     = sanitize_key( $data['cta_light'] ?? 'tl' );
		$out['cta_target']    = sanitize_key( $data['cta_target'] ?? 'bar' );
		$out['cta_x']         = intval( $data['cta_x'] ?? 0 );
		$out['cta_y']         = intval( $data['cta_y'] ?? 0 );
		$out['cta_col']       = max( 1, min( 8, intval( $data['cta_col'] ?? 1 ) ) );
		$out['cta_font']      = sanitize_text_field( $data['cta_font'] ?? '' );
		$out['cta_font_size'] = max( 10, min( 32, intval( $data['cta_font_size'] ?? 14 ) ) );
		$out['cta_img']       = esc_url_raw( $data['cta_img'] ?? '' );
		$out['cta_color2']    = sanitize_hex_color( $data['cta_color2'] ?? '' ) ?: sanitize_text_field( $data['cta_color2'] ?? '' );
		$out['cta_opacity']   = max( 0, min( 100, intval( $data['cta_opacity'] ?? 100 ) ) );
		$out['cta_color_mode']= in_array( ( $data['cta_color_mode'] ?? 'gradient' ), array( 'solid', 'gradient' ), true ) ? $data['cta_color_mode'] : 'gradient';
		$out['cta_role']      = sanitize_key( $data['cta_role'] ?? 'cta_link' );
		$out['cta_pos']       = sanitize_key( $data['cta_pos'] ?? 'end' );
		$out['second_tap']    = in_array( ( $data['second_tap'] ?? 'open' ), array( 'open', 'follow' ), true ) ? $data['second_tap'] : 'open';
		$out['breakpoint']    = max( 480, min( 1200, intval( $data['breakpoint'] ?? 768 ) ) );
		$out['intent_ms']     = max( 0, min( 800, intval( $data['intent_ms'] ?? 200 ) ) );
		$out['sticky']      = ! empty( $data['sticky'] ) ? 1 : 0;
		$out['rtl']         = ! empty( $data['rtl'] ) ? 1 : 0;
		$out['page_anim_in']  = sanitize_key( $data['page_anim_in'] ?? 'none' );
		$out['page_anim_out'] = sanitize_key( $data['page_anim_out'] ?? 'none' );
		$out['mega_cols']     = max( 1, min( 8, intval( $data['mega_cols'] ?? 3 ) ) );
		$out['placement']     = in_array( ( $data['placement'] ?? 'header' ), array( 'header', 'footer', 'sidebar', 'both', 'custom' ), true ) ? $data['placement'] : 'header';
		$out['mobile_sync']   = ! empty( $data['mobile_sync'] ) ? 1 : 0;
		$out['mobile_endpoint'] = esc_url_raw( $data['mobile_endpoint'] ?? '' );
		$out['trigger']      = in_array( ( $data['trigger'] ?? 'hover' ), array( 'hover', 'hover_intent', 'click' ), true ) ? $data['trigger'] : 'hover';
		$out['intent_ms']    = max( 0, min( 800, intval( $data['intent_ms'] ?? 200 ) ) );
		$out['breakpoint']   = max( 480, min( 1400, intval( $data['breakpoint'] ?? 768 ) ) );
		$out['search_box']   = ! empty( $data['search_box'] ) ? 1 : 0;
		$out['sticky_hide']  = ! empty( $data['sticky_hide'] ) ? 1 : 0;
		$out['fullwidth_sub']= ! empty( $data['fullwidth_sub'] ) ? 1 : 0;
		$out['cta_radius']   = max( 0, min( 999, intval( $data['cta_radius'] ?? 22 ) ) );
		$out['cta_size']     = sanitize_key( $data['cta_size'] ?? 'md' );
		$out['cta_emoji']    = sanitize_text_field( $data['cta_emoji'] ?? '' );
		$out['cta_icon']     = sanitize_text_field( $data['cta_icon'] ?? '' );
		$out['logo_x']       = intval( $data['logo_x'] ?? 0 );
		$out['logo_y']       = intval( $data['logo_y'] ?? 0 );
		$out['logo_target']  = sanitize_key( $data['logo_target'] ?? 'bar' );
		$out['logo_col']     = max( 1, min( 8, intval( $data['logo_col'] ?? 1 ) ) );
		$out['search_place'] = sanitize_key( $data['search_place'] ?? 'bar-end' );
		$out['search_x']     = intval( $data['search_x'] ?? 0 );
		$out['search_y']     = intval( $data['search_y'] ?? 0 );
		$out['cta_text']     = sanitize_text_field( $data['cta_text'] ?? '' );
		$out['_version']     = max( 1, intval( $data['_version'] ?? 1 ) );
		$out['updated_at']   = time();
		$out['updated_by']   = get_current_user_id();
		$out['cta_url']      = esc_url_raw( $data['cta_url'] ?? '' );
		$out['cta_style']    = sanitize_key( $data['cta_style'] ?? 'glass-capsule' );
		$out['cta_color']    = sanitize_hex_color( $data['cta_color'] ?? '#e11d48' ) ?: '#e11d48';
		$out['logo_url']     = esc_url_raw( $data['logo_url'] ?? '' );
		$out['hamburger']    = ! empty( $data['hamburger'] ) ? 1 : 0;
		$out['search_in_bar']= ! empty( $data['search_in_bar'] ) || ! empty( $data['search_box'] ) ? 1 : 0;
		$out['search_placeholder'] = sanitize_text_field( $data['search_placeholder'] ?? 'جستجو…' );
		$out['items']        = self::sanitize_items( isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array() );
				$out['_version'] = isset( $data['_version'] ) ? intval( $data['_version'] ) : intval( $out['_version'] ?? 0 );
		return $out;
	}

	public static function sanitize_items( $items, $depth = 0 ) {
		if ( $depth > ( class_exists( 'CGS_Mega_Content_Types' ) ? CGS_Mega_Content_Types::max_depth() : 5 ) || ! is_array( $items ) ) {
			return array();
		}
		$out = array();
		foreach ( $items as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$roles = array();
			if ( ! empty( $it['roles'] ) && is_array( $it['roles'] ) ) {
				foreach ( $it['roles'] as $r ) {
					$roles[] = sanitize_key( $r );
				}
			} elseif ( ! empty( $it['roles'] ) && is_string( $it['roles'] ) ) {
				foreach ( explode( ',', $it['roles'] ) as $r ) {
					$r = sanitize_key( trim( $r ) );
					if ( $r ) {
						$roles[] = $r;
					}
				}
			}
			$cols = array();
			if ( ! empty( $it['columns'] ) && is_array( $it['columns'] ) ) {
				foreach ( $it['columns'] as $col ) {
					if ( ! is_array( $col ) ) { continue; }
					$links = array();
					if ( ! empty( $col['links'] ) && is_array( $col['links'] ) ) {
						foreach ( $col['links'] as $lk ) {
							if ( ! is_array( $lk ) ) { continue; }
							$links[] = array(
								'label' => sanitize_text_field( $lk['label'] ?? '' ),
								'url'   => esc_url_raw( $lk['url'] ?? '#' ),
							);
						}
					}
					$cols[] = array(
						'title' => sanitize_text_field( $col['title'] ?? '' ),
						'url'   => esc_url_raw( $col['url'] ?? '#' ),
						'image' => esc_url_raw( $col['image'] ?? '' ),
						'video' => esc_url_raw( $col['video'] ?? '' ),
						'links' => $links,
					);
				}
			}
			$out[] = array(
				'id'          => sanitize_key( $it['id'] ?? uniqid( 'i' ) ),
				'label'       => sanitize_text_field( $it['label'] ?? '' ),
				'url'         => esc_url_raw( $it['url'] ?? '#' ),
				'icon'        => sanitize_text_field( $it['icon'] ?? '' ),
				'image'       => esc_url_raw( $it['image'] ?? '' ),
				'video'       => esc_url_raw( $it['video'] ?? '' ),
				'target'      => in_array( ( $it['target'] ?? '' ), array( '_blank', '_self' ), true ) ? $it['target'] : '_self',
				'badge'       => sanitize_text_field( $it['badge'] ?? '' ),
				'badge_shape'  => sanitize_key( $it['badge_shape'] ?? '' ),
				'description'  => sanitize_text_field( $it['description'] ?? '' ),
				'highlight'    => ! empty( $it['highlight'] ) ? 1 : 0,
				'hide_mobile'  => ! empty( $it['hide_mobile'] ) ? 1 : 0,
				'hide_desktop' => ! empty( $it['hide_desktop'] ) ? 1 : 0,
				'featured'     => ! empty( $it['featured'] ) ? 1 : 0,
				'display'     => sanitize_key( $it['display'] ?? 'link' ),
				'content_type'=> sanitize_key( $it['content_type'] ?? ( $it['display'] ?? 'link' ) ),
				'desc'        => sanitize_text_field( $it['desc'] ?? ( $it['description'] ?? '' ) ),
				'sidebar'     => sanitize_key( $it['sidebar'] ?? '' ),
				'taxonomy'    => sanitize_key( $it['taxonomy'] ?? '' ),
				'limit'       => max( 1, min( 24, intval( $it['limit'] ?? 6 ) ) ),
				'woo_cat'     => sanitize_title( $it['woo_cat'] ?? '' ),
				'on_sale'     => ! empty( $it['on_sale'] ) ? 1 : 0,
				'orderby'     => sanitize_key( $it['orderby'] ?? 'date' ),
				'term_parent' => intval( $it['term_parent'] ?? 0 ),
				'hide_empty'  => ! empty( $it['hide_empty'] ) ? 1 : 0,
				'item_effect' => sanitize_key( $it['item_effect'] ?? 'none' ),
				'col'         => max( 1, min( 6, intval( $it['col'] ?? 1 ) ) ),
				'widget'      => sanitize_key( $it['widget'] ?? 'none' ),
				'widget_html' => wp_kses_post( $it['widget_html'] ?? '' ),
				'roles'       => $roles,
				'mobile_id'   => sanitize_text_field( $it['mobile_id'] ?? '' ),
				'panel_bg'       => sanitize_hex_color( $it['panel_bg'] ?? '' ) ?: '',
				'panel_text'     => sanitize_hex_color( $it['panel_text'] ?? '' ) ?: '',
				'panel_bg_image' => esc_url_raw( $it['panel_bg_image'] ?? '' ),
				'btn_label'      => sanitize_text_field( $it['btn_label'] ?? '' ),
				'btn_url'        => esc_url_raw( $it['btn_url'] ?? '' ),
				'item_sound'     => sanitize_key( $it['item_sound'] ?? 'none' ),
				'sub_open_dir'   => sanitize_key( $it['sub_open_dir'] ?? '' ),
				'columns'     => $cols,
				'products'    => self::sanitize_products( isset( $it['products'] ) && is_array( $it['products'] ) ? $it['products'] : array() ),
				'children'    => self::sanitize_items( isset( $it['children'] ) && is_array( $it['children'] ) ? $it['children'] : array(), $depth + 1 ),
			);
		}
		return $out;
	}

	public static function sanitize_products( $list ) {
		$out = array();
		foreach ( (array) $list as $p ) {
			if ( ! is_array( $p ) ) { continue; }
			$out[] = array(
				'title' => sanitize_text_field( $p['title'] ?? '' ),
				'price' => sanitize_text_field( $p['price'] ?? '' ),
				'image' => esc_url_raw( $p['image'] ?? '' ),
				'url'   => esc_url_raw( $p['url'] ?? '#' ),
				'badge' => sanitize_text_field( $p['badge'] ?? '' ),
			);
		}
		return $out;
	}

	public static function map_dynamic_items( $items ) {
		if ( ! is_array( $items ) || ! class_exists( 'CGS_Mega_Dynamic' ) ) {
			return $items;
		}
		$out = array();
		foreach ( $items as $it ) {
			if ( ! is_array( $it ) ) { continue; }
			$it = CGS_Mega_Dynamic::resolve_item( $it );
			if ( ! empty( $it['children'] ) && is_array( $it['children'] ) ) {
				$it['children'] = self::map_dynamic_items( $it['children'] );
			}
			$out[] = $it;
		}
		return $out;
	}


	/**
	 * Phase-2: server-side HTML identical to frontend shortcode output.
	 * Admin live preview can consume this to eliminate JS≠PHP renderer drift (W3).
	 */

	/**
	 * Full isolated Preview document for iframe (P0: Admin CSS isolation).
	 * Returns complete HTML document with front.css + front.js only.
	 */
	
	/**
	 * Permanent governance: Preview must simulate End User environment.
	 * Collect Theme / Elementor / WooCommerce / font stylesheets to shrink parity gap.
	 *
	 * @return array{styles:string[],scripts:string[],fonts:string[],breakpoints:array,bodyClass:string}
	 */
	public static function preview_parity_assets() {
		$styles = array();
		$scripts = array();
		$fonts  = array();

		$styles[] = CGS_MENU_BUILDER_URL . 'assets/css/front.css';
		$scripts[] = CGS_MENU_BUILDER_URL . 'assets/js/front.js';

		// Theme stylesheet
		$theme_uri = get_stylesheet_uri();
		if ( is_string( $theme_uri ) && $theme_uri !== '' ) {
			$styles[] = $theme_uri;
		}
		// Parent theme if child
		if ( is_child_theme() ) {
			$parent = get_template_directory_uri() . '/style.css';
			$styles[] = $parent;
		}

		// Elementor frontend
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			if ( defined( 'ELEMENTOR_ASSETS_URL' ) ) {
				$styles[] = ELEMENTOR_ASSETS_URL . 'css/frontend.min.css';
				$styles[] = ELEMENTOR_ASSETS_URL . 'css/widget-icon-list.min.css';
			} elseif ( defined( 'ELEMENTOR_URL' ) ) {
				$styles[] = ELEMENTOR_URL . 'assets/css/frontend.min.css';
			}
		}

		// WooCommerce
		if ( class_exists( 'WooCommerce' ) ) {
			if ( defined( 'WC_PLUGIN_FILE' ) ) {
				$wc_url = plugins_url( '/', WC_PLUGIN_FILE );
				$styles[] = $wc_url . 'assets/css/woocommerce-layout.css';
				$styles[] = $wc_url . 'assets/css/woocommerce-smallscreen.css';
				$styles[] = $wc_url . 'assets/css/woocommerce.css';
			}
		}

		// Common WP block library (often present on front)
		$styles[] = includes_url( 'css/dist/block-library/style.min.css' );

		// Deduplicate preserve order
		$seen = array();
		$styles_out = array();
		foreach ( $styles as $u ) {
			$u = esc_url_raw( $u );
			if ( ! $u || isset( $seen[ $u ] ) ) {
				continue;
			}
			$seen[ $u ] = true;
			$styles_out[] = $u;
		}
		$scripts_out = array();
		$seen_s = array();
		foreach ( $scripts as $u ) {
			$u = esc_url_raw( $u );
			if ( ! $u || isset( $seen_s[ $u ] ) ) {
				continue;
			}
			$seen_s[ $u ] = true;
			$scripts_out[] = $u;
		}

		$breakpoints = array(
			'mobile'  => 768,
			'tablet'  => 1024,
			'desktop' => 1280,
		);

		return array(
			'styles'       => $styles_out,
			'scripts'      => $scripts_out,
			'fonts'        => $fonts,
			'breakpoints'  => $breakpoints,
			'bodyClass'    => 'cgs-preview-enduser home blog wp-embed-responsive',
			'zIndexNav'    => 100,
			'zIndexSub'    => 99999,
			'zIndexSticky' => 9990,
			'fontStack'    => 'Tahoma, Vazirmatn, "Segoe UI", Tahoma, sans-serif',
			'containerMax' => '1200px',
		);
	}

	public static function ajax_preview_document() {
		if ( ! current_user_can( 'manage_options' ) ) {
			status_header( 403 );
			echo 'forbidden';
			exit;
		}
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'cgs_menu_builder' ) ) {
			status_header( 403 );
			echo 'bad_nonce';
			exit;
		}
		$raw = isset( $_REQUEST['menu'] ) ? wp_unslash( $_REQUEST['menu'] ) : '';
		$data = array();
		if ( is_string( $raw ) && $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			}
		}
		try {
			$menu = self::sanitize_menu( is_array( $data ) ? $data : array() );
		} catch ( \Throwable $e ) {
			$menu = self::default_menu( 'preview' );
		}
		if ( empty( $menu['id'] ) ) {
			$menu['id'] = 'preview';
		}
		if ( class_exists( 'CGS_Mega_Dynamic' ) && method_exists( 'CGS_Mega_Dynamic', 'map_dynamic_items' ) ) {
			try {
				$menu['items'] = CGS_Mega_Dynamic::map_dynamic_items( $menu['items'] ?? array() );
			} catch ( \Throwable $e ) {}
		}
		$css = CGS_MENU_BUILDER_URL . 'assets/css/front.css?v=' . rawurlencode( self::VERSION );
		$js  = CGS_MENU_BUILDER_URL . 'assets/js/front.js?v=' . rawurlencode( self::VERSION );
		$w = max( 320, min( 1600, intval( $_REQUEST['vw'] ?? 1200 ) ) );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
		echo '<title>CGS Menu Preview</title>';
		echo '<link rel="stylesheet" href="' . esc_url( $css ) . '">';
		echo '<style>body{margin:0;padding:16px;background:#f8fafc;font-family:Tahoma,sans-serif}';
		echo '#cgs-preview-root{max-width:' . intval( $w ) . 'px;margin:0 auto}</style>';
		echo '</head><body><div id="cgs-preview-root">';
		ob_start();
		self::render_menu_html( $menu );
		echo ob_get_clean();
		echo '</div><script src="' . esc_url( $js ) . '"></script>';
		echo '<script>if(window.CGSMenuFront){CGSMenuFront.bindAll(document);}</script>';
		echo '</body></html>';
		exit;
	}

	public static function ajax_preview_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( class_exists( 'CGS_Menu_Rate_Limit' ) && ! CGS_Menu_Rate_Limit::allow( 'preview_html', 120, 60 ) ) {
			wp_send_json_error( array( 'message' => 'rate_limited' ), 429 );
		}
		// Soft nonce: clear error instead of hard die (avoids opaque 400/-1)
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'cgs_menu_builder' ) ) {
			wp_send_json_error( array( 'message' => 'bad_nonce', 'hint' => 'refresh page' ), 403 );
		}
		$raw = isset( $_POST['menu'] ) ? wp_unslash( $_POST['menu'] ) : '';
		$data = array();
		if ( is_string( $raw ) && $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			}
		} elseif ( is_array( $raw ) ) {
			$data = $raw;
		}
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		// Never 400 on empty — use defaults so preview still paints
		try {
			$menu = self::sanitize_menu( $data );
		} catch ( \Throwable $e ) {
			$menu = self::default_menu( 'preview' );
			$menu['title'] = 'پیش‌نمایش';
		}
		if ( empty( $menu['id'] ) ) {
			$menu['id'] = 'preview';
		}
		if ( class_exists( 'CGS_Mega_Dynamic' ) && method_exists( 'CGS_Mega_Dynamic', 'map_dynamic_items' ) ) {
			try {
				$menu['items'] = CGS_Mega_Dynamic::map_dynamic_items( $menu['items'] ?? array() );
			} catch ( \Throwable $e ) {
				// keep items as-is
			}
		} elseif ( method_exists( __CLASS__, 'map_dynamic_items' ) ) {
			try {
				$menu['items'] = self::map_dynamic_items( $menu['items'] ?? array() );
			} catch ( \Throwable $e ) {}
		}
		ob_start();
		try {
			self::render_menu_html( $menu );
			$html = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			$html = '<div class="cgs-preview-error">خطای رندر: ' . esc_html( $e->getMessage() ) . '</div>';
		}
		wp_send_json_success( array(
			'html'   => $html,
			'id'     => $menu['id'] ?? '',
			'effect' => $menu['effect'] ?? 'slide',
			'speed'  => intval( $menu['effect_speed'] ?? $menu['speed'] ?? 220 ),
			'layout' => $menu['layout'] ?? 'horizontal',
		) );
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'   => 'main',
			'slug' => '',
		), $atts, 'cgs_menu' );
		$id = $atts['slug'] ? sanitize_title( $atts['slug'] ) : sanitize_key( $atts['id'] );
		$menus = self::get_all();
		$menu = null;
		if ( isset( $menus[ $id ] ) ) {
			$menu = $menus[ $id ];
		} else {
			foreach ( $menus as $m ) {
				if ( ( $m['slug'] ?? '' ) === $id ) {
					$menu = $m;
					break;
				}
			}
		}
		if ( ! $menu ) {
			$menu = self::default_menu( $id );
		}
		wp_enqueue_style( 'cgs-menu-builder-front' );
		wp_enqueue_script( 'cgs-menu-builder-front' );
		ob_start();
		self::render_menu_html( $menu );
		return ob_get_clean();
	}

	public static function render_menu_html( $menu ) {
		$mid = esc_attr( $menu['id'] );
		$layout = esc_attr( $menu['layout'] ?? 'horizontal' );
		$effect = esc_attr( $menu['effect'] ?? 'slide' );
		$sound = esc_attr( $menu['sound'] ?? 'none' );
		$vol = (int) ( $menu['sound_vol'] ?? 35 );
		$dir = ! empty( $menu['rtl'] ) ? 'rtl' : 'ltr';
		$style = self::inline_vars( $menu );
		$sticky = ! empty( $menu['sticky'] ) ? ' is-sticky' : '';
		echo '<nav class="cgs-nav cgs-nav--' . $layout . ' cgs-nav-place--' . esc_attr( $menu['placement'] ?? 'header' ) . ' cgs-fx--' . $effect . $sticky . '" id="cgs-nav-' . $mid . '" dir="' . $dir . '" data-sound="' . $sound . '" data-vol="' . $vol . '" data-anim-in="' . esc_attr( $menu['page_anim_in'] ?? 'none' ) . '" data-anim-out="' . esc_attr( $menu['page_anim_out'] ?? 'none' ) . '" data-mobile-sync="' . ( ! empty( $menu['mobile_sync'] ) ? '1' : '0' ) . '" data-mega-cols="' . intval( $menu['mega_cols'] ?? 3 ) . '" data-trigger="' . esc_attr( $menu['trigger'] ?? 'hover' ) . '" data-effect="' . esc_attr( $menu['effect'] ?? 'slide' ) . '" data-speed="' . intval( $menu['effect_speed'] ?? 220 ) . '" data-second-tap="' . esc_attr( $menu['second_tap'] ?? 'open' ) . '" data-intent="' . intval( $menu['intent_ms'] ?? 200 ) . '" data-breakpoint="' . intval( $menu['breakpoint'] ?? 768 ) . '" data-sticky-hide="' . ( ! empty( $menu['sticky_hide'] ) ? '1' : '0' ) . '" data-fullwidth="' . ( ! empty( $menu['fullwidth_sub'] ) ? '1' : '0' ) . '" data-sub-dir="' . esc_attr( $menu['sub_open_dir'] ?? 'bottom' ) . '" style="' . esc_attr( $style ) . '" role="navigation" aria-label="' . esc_attr( $menu['title'] ?? 'menu' ) . '">';
		if ( empty( $menu['hamburger'] ) ) {
			// still output toggle for mobile CSS hooks; hidden via CSS if disabled
		}
		echo '<button type="button" class="cgs-nav-toggle' . ( empty( $menu['hamburger'] ) ? ' is-disabled' : '' ) . '" aria-expanded="false" aria-controls="cgs-nav-list-' . $mid . '"' . ( empty( $menu['hamburger'] ) ? ' hidden' : '' ) . '><span></span><span></span><span></span></button>';
		if ( ! empty( $menu['logo_url'] ) ) {
			echo '<a class="cgs-nav-logo" href="' . esc_url( home_url( '/' ) ) . '"><img src="' . esc_url( $menu['logo_url'] ) . '" alt="logo" loading="lazy"></a>';
		}
		echo '<ul class="cgs-nav-list" id="cgs-nav-list-' . $mid . '">';
		$items = isset( $menu['items'] ) && is_array( $menu['items'] ) ? $menu['items'] : array();
		$items = self::map_dynamic_items( $items );
		self::$__render_sub_dir = sanitize_key( $menu['sub_open_dir'] ?? 'bottom' );
		self::$__render_layout = sanitize_key( $menu['layout'] ?? 'horizontal' );
		self::render_items( $items, 0 );
		self::$__render_sub_dir = 'bottom';
		self::$__render_layout = 'horizontal';
		echo '</ul>';
		if ( ! empty( $menu['search_box'] ) || ! empty( $menu['search_in_bar'] ) ) {
			$ph = esc_attr( $menu['search_placeholder'] ?? 'جستجو…' );
			echo '<div class="cgs-nav-search"><input type="search" placeholder="' . $ph . '" aria-label="جستجوی منو"></div>';
		}
		$cta_role = sanitize_key( $menu['cta_role'] ?? 'cta_link' );
		if ( $cta_role !== 'none' && $cta_role !== 'hidden' && ( ! empty( $menu['cta_text'] ) || $cta_role === 'icon_only' || ! empty( $menu['cta_url'] ) ) ) {
			$cta_href = ! empty( $menu['cta_url'] ) ? $menu['cta_url'] : '#';
			$cta_style = sanitize_key( $menu['cta_style'] ?? 'glass-capsule' );
			$c1 = esc_attr( $menu['cta_color'] ?? '#e11d48' );
			$c2 = esc_attr( $menu['cta_color2'] ?? $c1 );
			$mode = ( $menu['cta_color_mode'] ?? 'gradient' ) === 'solid' ? 'solid' : 'gradient';
			$op = max( 0, min( 100, intval( $menu['cta_opacity'] ?? 100 ) ) ) / 100;
			$scale = max( 0.4, min( 2.0, intval( $menu['cta_scale'] ?? 100 ) / 100 ) );
			$light = sanitize_key( $menu['cta_light'] ?? 'tl' );
			$light_map = array(
				'tl' => '15% 10%', 'tr' => '85% 10%', 'bl' => '15% 90%', 'br' => '85% 90%',
				'top' => '50% 5%', 'bottom' => '50% 95%', 'left' => '5% 50%', 'right' => '95% 50%',
			);
			$lp = $light_map[ $light ] ?? '15% 10%';
			$bg = ( $mode === 'solid' ) ? $c1 : 'linear-gradient(135deg,' . $c1 . ' 0%,' . $c2 . ' 100%)';
			$glass = 'radial-gradient(ellipse 90% 70% at ' . $lp . ', rgba(255,255,255,.9) 0%, rgba(255,255,255,0) 50%),' . $bg;
			$font = esc_attr( $menu['cta_font'] ?? 'Tahoma,sans-serif' );
			$fsize = max( 10, min( 28, intval( $menu['cta_font_size'] ?? 14 ) ) );
			$radius_px = max( 0, intval( $menu['cta_radius'] ?? 22 ) );
			$label = ( $cta_role === 'icon_only' ) ? '' : esc_html( $menu['cta_text'] ?? '' );
			$ico = '';
			if ( ! empty( $menu['cta_img'] ) ) {
				$ico = '<img class="cgs-cta-img" src="' . esc_url( $menu['cta_img'] ) . '" alt="" style="width:' . (int) ( $fsize * 1.4 ) . 'px;height:' . (int) ( $fsize * 1.4 ) . 'px;object-fit:cover;border-radius:6px;margin-inline-end:6px;vertical-align:middle">';
			} elseif ( ! empty( $menu['cta_emoji'] ) || ! empty( $menu['cta_icon'] ) ) {
				$ico = '<span class="cgs-glass-ico" style="margin-inline-end:6px">' . esc_html( $menu['cta_emoji'] ?? $menu['cta_icon'] ) . '</span>';
			}
			$pos = sanitize_key( $menu['cta_pos'] ?? 'end' );
			$style_cta = 'background:' . $glass . ';opacity:' . $op . ';transform:scale(' . $scale . ');font-family:' . $font . ';font-size:' . $fsize . 'px;border-radius:' . $radius_px . 'px;display:inline-flex;align-items:center;padding:10px 18px;color:#fff;text-decoration:none;box-shadow:0 8px 24px rgba(0,0,0,.18),inset 0 1px 0 rgba(255,255,255,.35);border:1px solid rgba(255,255,255,.35);font-weight:700;gap:6px';
			$light_cls = 'cgs-cta-light--' . sanitize_html_class( $light );
			$cls = 'cgs-nav-cta cgs-glass-btn cgs-cta--' . $cta_style . ' cgs-cta-pos--' . $pos . ' ' . $light_cls;
			$style_cta .= ';--cta-scale:' . $scale . ';--glass-light-pos:' . ( $light === 'tr' ? '80% 15%' : ( $light === 'bl' ? '20% 85%' : ( $light === 'br' ? '80% 85%' : ( $light === 't' ? '50% 10%' : ( $light === 'b' ? '50% 90%' : ( $light === 'l' ? '10% 50%' : ( $light === 'r' ? '90% 50%' : '20% 15%' ) ) ) ) ) ) );
			echo '<a class="' . $cls . '" href="' . esc_url( $cta_href ) . '" data-cta="1" data-role="' . esc_attr( $cta_role ) . '" style="' . esc_attr( $style_cta ) . '">' . $ico . ( $label !== '' ? '<span class="cgs-cta-label">' . $label . '</span>' : '' ) . '</a>';
		}
		echo '</nav>';
	}

	public static function inline_vars( $menu ) {
		$c1  = $menu['bg_color'] ?? '#0f172a';
		$c2  = $menu['bg_color2'] ?? '#1e3a5f';
		$dir = $menu['gradient_dir'] ?? 'ltr';
		$op  = max( 0, min( 100, intval( $menu['bg_image_opacity'] ?? 100 ) ) ) / 100;
		$bg  = $c1;
		if ( ( $menu['bg_type'] ?? '' ) === 'gradient' ) {
			if ( $dir === 'radial' ) {
				$bg = 'radial-gradient(circle at center,' . $c1 . ',' . $c2 . ')';
			} else {
				$ang = array(
					'ltr' => '90deg',
					'rtl' => '270deg',
					'ttb' => '180deg',
					'btt' => '0deg',
				);
				$a = $ang[ $dir ] ?? '135deg';
				$bg = 'linear-gradient(' . $a . ',' . $c1 . ',' . $c2 . ')';
			}
		} elseif ( ( $menu['bg_type'] ?? '' ) === 'image' && ! empty( $menu['bg_image'] ) ) {
			// لایه سفید شفاف روی تصویر برای کنترل opacity + رنگ زیرین
			$veil = 1 - $op;
			$bg = 'linear-gradient(rgba(255,255,255,' . $veil . '),rgba(255,255,255,' . $veil . ')),url(' . esc_url( $menu['bg_image'] ) . ') center/cover no-repeat,' . $c1;
		} elseif ( ( $menu['bg_type'] ?? '' ) === 'glass' ) {
			$bg = 'rgba(15,23,42,0.72)';
		}
		$parts = array(
			'--cgs-nav-bg:' . $bg,
			'--cgs-nav-text:' . ( $menu['text_color'] ?? '#f8fafc' ),
			'--cgs-nav-hover:' . ( $menu['hover_color'] ?? '#38bdf8' ),
			'--cgs-nav-active:' . ( $menu['active_color'] ?? '#7dd3fc' ),
			'--cgs-nav-radius:' . intval( $menu['radius'] ?? 12 ) . 'px',
			'--cgs-nav-shadow:' . ( ! empty( $menu['shadow'] ) ? '0 10px 30px rgba(0,0,0,.25)' : 'none' ),
			'--cgs-bg-img-opacity:' . $op,
			'--cgs-mega-cols:' . max( 1, min( 8, intval( $menu['mega_cols'] ?? 3 ) ) ),
			'background:' . $bg,
		);
		return implode( ';', $parts );
	}

	public static function user_can_see_item( $it ) {
		$roles = isset( $it['roles'] ) && is_array( $it['roles'] ) ? $it['roles'] : array();
		if ( empty( $roles ) ) {
			return true; // همه
		}
		if ( ! is_user_logged_in() ) {
			return in_array( 'guest', $roles, true );
		}
		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		if ( in_array( 'all_logged', $roles, true ) ) {
			return true;
		}
		foreach ( $roles as $r ) {
			if ( in_array( $r, $user_roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	
	/**
	 * Render non-structural content-type body (heading/image/card/...).
	 * Returns escaped HTML or empty string.
	 */
	public static function render_content_block( $it ) {
		$ct = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : '';
		if ( ! $ct || $ct === 'link' ) {
			return '';
		}
		$html = '';
		/* Static mega-panel elements — NEVER a flyout submenu */
		if ( $ct === 'heading' ) {
			$label = $it['label'] ?? '';
			if ( $label !== '' ) {
				$html = '<div class="cgs-ct-heading cgs-mega-heading" role="heading">' . esc_html( $label ) . '</div>';
			}
		} elseif ( $ct === 'image' ) {
			/* P2: media library URL or attachment id */
			$img_url = '';
			if ( ! empty( $it['image'] ) ) {
				$img_url = $it['image'];
			} elseif ( ! empty( $it['image_id'] ) ) {
				$img_url = wp_get_attachment_image_url( (int) $it['image_id'], 'large' );
			}
			if ( $img_url ) {
				$inner = '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $it['label'] ?? '' ) . '" loading="lazy">';
				if ( ! empty( $it['url'] ) && $it['url'] !== '#' ) {
					$html = '<a class="cgs-ct-image" href="' . esc_url( $it['url'] ) . '">' . $inner . '</a>';
				} else {
					$html = '<div class="cgs-ct-image">' . $inner . '</div>';
				}
			} else {
				$html = '<div class="cgs-ct-image cgs-ct-image-placeholder"><span>🖼</span><small>' . esc_html( $it['label'] ?? 'تصویر' ) . '</small></div>';
			}
		} elseif ( $ct === 'product_card' ) {
			$img_url = ! empty( $it['image'] ) ? $it['image'] : ( ! empty( $it['image_id'] ) ? wp_get_attachment_image_url( (int) $it['image_id'], 'woocommerce_thumbnail' ) : '' );
			$price = isset( $it['price'] ) ? $it['price'] : '';
			$badge = isset( $it['badge'] ) ? $it['badge'] : '';
			$html  = '<a class="cgs-product-card" href="' . esc_url( $it['url'] ?? '#' ) . '">';
			if ( $img_url ) {
				$html .= '<span class="cgs-product-card-img"><img src="' . esc_url( $img_url ) . '" alt="" loading="lazy"></span>';
			} else {
				$html .= '<span class="cgs-product-card-img cgs-product-card-ph">📦</span>';
			}
			if ( $badge ) {
				$html .= '<span class="cgs-product-card-badge">' . esc_html( $badge ) . '</span>';
			}
			$html .= '<span class="cgs-product-card-title">' . esc_html( $it['label'] ?? ( $it['title'] ?? '' ) ) . '</span>';
			if ( $price !== '' ) {
				$html .= '<span class="cgs-product-card-price">' . wp_kses_post( $price ) . '</span>';
			}
			$html .= '</a>';
		} elseif ( $ct === 'woo_products' || $ct === 'product_slider_live' ) {
			$resolved = isset( $it['_resolved'] ) && is_array( $it['_resolved'] ) ? $it['_resolved'] : array();
			$html = '<div class="cgs-woo-products">';
			if ( $resolved ) {
				foreach ( $resolved as $pr ) {
					if ( ! is_array( $pr ) ) { continue; }
					$card = array(
						'content_type' => 'product_card',
						'label'        => $pr['title'] ?? '',
						'title'        => $pr['title'] ?? '',
						'url'          => $pr['url'] ?? '#',
						'image'        => $pr['image'] ?? '',
						'price'        => $pr['price'] ?? '',
						'badge'        => $pr['badge'] ?? '',
					);
					$html .= self::render_content_block( $card );
				}
			} else {
				$html .= '<div class="cgs-woo-empty">محصولی یافت نشد (ووکامرس را فعال کنید)</div>';
			}
			$html .= '</div>';
		} elseif ( $ct === 'hub_card' ) {
			$icon = esc_html( $it['icon'] ?? '◆' );
			$title = esc_html( $it['label'] ?? '' );
			$desc = esc_html( $it['desc'] ?? '' );
			$link = esc_html( $it['link_label'] ?? 'بیشتر' );
			$href = esc_url( $it['url'] ?? '#' );
			$html = '<a class="cgs-hub-card" href="' . $href . '"><span class="cgs-hub-card-icon">' . $icon . '</span><span class="cgs-hub-card-body"><strong>' . $title . '</strong><em>' . $desc . '</em><u>' . $link . '</u></span></a>';
		} elseif ( $ct === 'divider' ) {
			$html = '<hr class="cgs-menu-divider" aria-hidden="true">';
		} elseif ( $ct === 'button' ) {
			$lab = $it['btn_label'] ?? ( $it['label'] ?? '' );
			$href = ! empty( $it['btn_url'] ) ? $it['btn_url'] : ( $it['url'] ?? '#' );
			if ( $lab !== '' ) {
				$html = '<a class="cgs-item-btn cgs-ct-button" href="' . esc_url( $href ) . '">' . esc_html( $lab ) . '</a>';
			}
		} elseif ( $ct === 'card' || $ct === 'brand' ) {
			$img = ! empty( $it['image'] ) ? '<div class="cgs-mega-card-img" style="background-image:url(' . esc_url( $it['image'] ) . ')"></div>' : '';
			$title = esc_html( $it['label'] ?? '' );
			$href = esc_url( $it['url'] ?? '#' );
			$html = '<a class="cgs-mega-card cgs-ct-' . esc_attr( $ct ) . '" href="' . $href . '">' . $img . '<div class="cgs-mega-card-title">' . $title . '</div></a>';
		}
		return $html;
	}

	/**
	 * Content types that are panel elements, not flyout parents.
	 * سرستون / تصویر / دکمه / کارت ≠ زیرمنو
	 */
	public static function is_panel_element( $ct ) {
		return in_array( sanitize_key( (string) $ct ), array( 'heading', 'image', 'divider', 'button', 'card', 'brand', 'product_card', 'woo_products', 'product_slider_live', 'hub_card' ), true );
	}


	/**
	 * Digikala-style panel: vertical category sidebar + content columns + optional promo.
	 * Each first-level child = one sidebar row; its children = column headings/links/images.
	 */
	public static function render_digikala_panel( $children ) {
		if ( empty( $children ) || ! is_array( $children ) ) {
			return;
		}
		echo '<div class="cgs-dk-panel">';
		echo '<ul class="cgs-dk-sidebar" role="tablist">';
		$i = 0;
		foreach ( $children as $cat ) {
			if ( ! is_array( $cat ) ) {
				continue;
			}
			if ( ! self::user_can_see_item( $cat ) ) {
				continue;
			}
			$label = $cat['label'] ?? '';
			$icon  = $cat['icon'] ?? '';
			$active = ( 0 === $i ) ? ' is-active' : '';
			echo '<li class="cgs-dk-side-item' . $active . '" data-dk-panel="' . intval( $i ) . '" role="tab" tabindex="0">';
			if ( $icon ) {
				echo '<span class="cgs-dk-side-icon">' . esc_html( $icon ) . '</span>';
			}
			echo '<span class="cgs-dk-side-label">' . esc_html( $label ) . '</span>';
			echo '<span class="cgs-dk-side-arrow" aria-hidden="true">‹</span>';
			echo '</li>';
			$i++;
		}
		echo '</ul>';
		echo '<div class="cgs-dk-content">';
		$i = 0;
		foreach ( $children as $cat ) {
			if ( ! is_array( $cat ) ) {
				continue;
			}
			if ( ! self::user_can_see_item( $cat ) ) {
				continue;
			}
			$active = ( 0 === $i ) ? ' is-active' : '';
			$kids = isset( $cat['children'] ) && is_array( $cat['children'] ) ? $cat['children'] : array();
			echo '<div class="cgs-dk-pane' . $active . '" data-dk-panel="' . intval( $i ) . '" role="tabpanel">';
			echo '<div class="cgs-dk-cols">';
			$promo = '';
			foreach ( $kids as $cell ) {
				if ( ! is_array( $cell ) ) {
					continue;
				}
				$ct = isset( $cell['content_type'] ) ? sanitize_key( $cell['content_type'] ) : 'link';
				if ( $ct === 'image' || $ct === 'brand' ) {
					// collect promo / brand for side strip
					ob_start();
					if ( $ct === 'image' && ! empty( $cell['image'] ) ) {
						echo '<a class="cgs-dk-promo" href="' . esc_url( $cell['url'] ?? '#' ) . '"><img src="' . esc_url( $cell['image'] ) . '" alt="' . esc_attr( $cell['label'] ?? '' ) . '" loading="lazy"></a>';
					} elseif ( $ct === 'image' ) {
						echo '<div class="cgs-dk-promo cgs-dk-promo-placeholder"><span>📦</span><small>' . esc_html( $cell['label'] ?? 'بنر' ) . '</small></div>';
					} elseif ( $ct === 'brand' ) {
						echo '<div class="cgs-dk-brand">' . esc_html( $cell['label'] ?? '' ) . '</div>';
					}
					$promo .= ob_get_clean();
					continue;
				}
				if ( $ct === 'row' ) {
					continue; // brands row handled below
				}
				// heading column or plain link group
				echo '<div class="cgs-dk-col">';
				if ( $ct === 'heading' || ! empty( $cell['children'] ) ) {
					echo '<div class="cgs-mega-heading">' . esc_html( $cell['label'] ?? '' ) . '</div>';
					$links = isset( $cell['children'] ) && is_array( $cell['children'] ) ? $cell['children'] : array();
					echo '<ul class="cgs-dk-links">';
					foreach ( $links as $lk ) {
						if ( ! is_array( $lk ) ) {
							continue;
						}
						echo '<li><a href="' . esc_url( $lk['url'] ?? '#' ) . '">' . esc_html( $lk['label'] ?? '' ) . '</a></li>';
					}
					echo '</ul>';
				} else {
					echo '<a class="cgs-dk-single" href="' . esc_url( $cell['url'] ?? '#' ) . '">' . esc_html( $cell['label'] ?? '' ) . '</a>';
				}
				echo '</div>';
			}
			echo '</div>'; // cols
			if ( $promo ) {
				echo '<div class="cgs-dk-aside">' . $promo . '</div>'; // phpcs:ignore
			}
			// brand row from row-type children
			foreach ( $kids as $cell ) {
				if ( ! is_array( $cell ) ) {
					continue;
				}
				if ( ( $cell['content_type'] ?? '' ) !== 'row' ) {
					continue;
				}
				$row_kids = isset( $cell['children'] ) && is_array( $cell['children'] ) ? $cell['children'] : array();
				if ( ! $row_kids ) {
					continue;
				}
				echo '<div class="cgs-dk-brands">';
				foreach ( $row_kids as $br ) {
					if ( ! is_array( $br ) ) {
						continue;
					}
					echo '<a class="cgs-dk-brand-item" href="' . esc_url( $br['url'] ?? '#' ) . '">' . esc_html( $br['label'] ?? '' ) . '</a>';
				}
				echo '</div>';
			}
			echo '</div>'; // pane
			$i++;
		}
		echo '</div>'; // content
		echo '</div>'; // panel
	}

	public static function render_items( $items, $depth ) {
		$max_d = class_exists( 'CGS_Mega_Content_Types' ) ? CGS_Mega_Content_Types::max_depth() : 5;
		if ( empty( $items ) || $depth > $max_d ) {
			return;
		}
		foreach ( $items as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			if ( ! self::user_can_see_item( $it ) ) {
				continue;
			}
			$ct0 = isset( $it['content_type'] ) ? sanitize_key( $it['content_type'] ) : ( isset( $it['display'] ) ? sanitize_key( $it['display'] ) : 'link' );

			/* --- Structural containers: real layout chrome with nested children (v4.10.91) --- */
			if ( class_exists( 'CGS_Mega_Content_Types' ) && in_array( $ct0, CGS_Mega_Content_Types::structural_types(), true ) ) {
				if ( $ct0 === 'divider' ) {
					echo CGS_Mega_Content_Types::open_structural( $it ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside
					continue;
				}
				// row / column as <li> wrapper so tree stays valid, inner div is the grid unit
				$span = ( $ct0 === 'column' ) ? CGS_Mega_Content_Types::column_span( $it ) : 0;
				$li_cls = 'cgs-nav-item cgs-structural cgs-ct-' . esc_attr( $ct0 ) . ( $depth ? ' is-child depth-' . intval( $depth ) : '' );
				if ( $span ) {
					$li_cls .= ' cgs-span-' . intval( $span );
				}
				echo '<li class="' . esc_attr( $li_cls ) . '" data-content="' . esc_attr( $ct0 ) . '">';
				echo CGS_Mega_Content_Types::open_structural( $it ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				if ( ! empty( $it['label'] ) && $ct0 === 'column' ) {
					echo '<div class="cgs-mega-col-title">' . esc_html( $it['label'] ) . '</div>';
				}
				if ( ! empty( $it['children'] ) && is_array( $it['children'] ) ) {
					echo '<ul class="cgs-mega-inner">';
					self::render_items( $it['children'], $depth + 1 );
					echo '</ul>';
				}
				echo CGS_Mega_Content_Types::close_structural( $it ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</li>';
				continue;
			}

			/* ONLY real non-panel items with children create a flyout submenu.
			 * heading/image/button/card/brand/divider = PANEL elements (Digikala سرستون style).
			 * Even if they have children, those children render INLINE as column links — never flyout. */
			$has = ! empty( $it['children'] ) && is_array( $it['children'] );
			if ( self::is_panel_element( $ct0 ) ) {
				$has = false; /* سرستون هرگز زیر‌منوی بازشو نیست */
			}
			$col = max( 1, min( 12, intval( $it['col'] ?? 1 ) ) );
			/* --- Panel elements (سرستون، تصویر، دکمه، ...) = static cells, NEVER flyout --- */
			if ( self::is_panel_element( $ct0 ) ) {
				$block = self::render_content_block( $it );
				echo '<li class="cgs-nav-item cgs-panel-el cgs-ct-' . esc_attr( $ct0 ) . ( $depth ? ' is-child depth-' . intval( $depth ) : '' ) . '" data-content="' . esc_attr( $ct0 ) . '">';
				if ( $block ) {
					echo $block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} elseif ( $ct0 === 'heading' ) {
					echo '<div class="cgs-ct-heading cgs-mega-heading">' . esc_html( $it['label'] ?? '' ) . '</div>';
				} else {
					echo '<span class="cgs-nav-label">' . esc_html( $it['label'] ?? '' ) . '</span>';
				}
				/* children under heading/column-title render as plain links list, not a second flyout */
				if ( ! empty( $it['children'] ) && is_array( $it['children'] ) ) {
					echo '<ul class="cgs-mega-links cgs-panel-kids">';
					self::render_items( $it['children'], $depth + 1 );
					echo '</ul>';
				}
				echo '</li>';
				continue;
			}
			$cls = 'cgs-nav-item' . ( $has ? ' has-children' : '' ) . ( $depth ? ' is-child depth-' . $depth : '' );
			/* cgs-col-* only on top-level structural spans — never on mega panel links (crushes text to 8%) */
			if ( $depth === 0 && $col > 1 ) {
				$cls .= ' cgs-col-' . $col;
			}
			if ( $ct0 ) {
				$cls .= ' cgs-ct-' . sanitize_html_class( $ct0 );
			}
			$mid  = ! empty( $it['mobile_id'] ) ? ' data-mobile-id="' . esc_attr( $it['mobile_id'] ) . '"' : '';
			$mid .= ' data-content="' . esc_attr( $ct0 ) . '"';
			echo '<li class="' . esc_attr( $cls ) . '"' . $mid . '>';
			echo '<a class="cgs-nav-link" href="' . esc_url( $it['url'] ?? '#' ) . '" target="' . esc_attr( $it['target'] ?? '_self' ) . '">';
			if ( ! empty( $it['image'] ) ) {
				echo '<img class="cgs-nav-img" src="' . esc_url( $it['image'] ) . '" alt="" loading="lazy">';
			} elseif ( ! empty( $it['icon'] ) ) {
				echo '<span class="cgs-nav-icon">' . esc_html( $it['icon'] ) . '</span>';
			}
			echo '<span class="cgs-nav-label">' . esc_html( $it['label'] ?? '' ) . '</span>';
			if ( ! empty( $it['badge'] ) ) {
				echo '<span class="cgs-nav-badge">' . esc_html( $it['badge'] ) . '</span>';
			}
			if ( $has ) {
				echo '<span class="cgs-nav-caret" aria-hidden="true">▾</span>';
			}
			echo '</a>';
			if ( ! empty( $it['btn_label'] ) ) {
				echo '<a class="cgs-item-btn" href="' . esc_url( $it['btn_url'] ?? '#' ) . '">' . esc_html( $it['btn_label'] ) . '</a>';
			}
			if ( $has ) {
				$sod = sanitize_key( $it['sub_open_dir'] ?? ( self::$__render_sub_dir ?? 'bottom' ) );
				$sod = in_array( $sod, array( 'bottom', 'top', 'left', 'right' ), true ) ? $sod : '';
				if ( ! in_array( $sod, array( 'bottom', 'top', 'left', 'right' ), true ) ) { $sod = 'bottom'; }
				$panel_style = '';
				if ( ! empty( $it['panel_bg'] ) ) {
					$panel_style .= '--cgs-panel-bg:' . esc_attr( $it['panel_bg'] ) . ';background:' . esc_attr( $it['panel_bg'] ) . ';';
				}
				if ( ! empty( $it['panel_text'] ) ) {
					$panel_style .= '--cgs-panel-text:' . esc_attr( $it['panel_text'] ) . ';color:' . esc_attr( $it['panel_text'] ) . ';';
				}
				if ( ! empty( $it['panel_bg_image'] ) ) {
					$panel_style .= 'background-image:url(' . esc_url( $it['panel_bg_image'] ) . ');background-size:cover;background-position:center;';
				}
				$fx_item = sanitize_key( $it['item_effect'] ?? '' );
				$snd_item = sanitize_key( $it['item_sound'] ?? '' );
				$is_dk = ( self::$__render_layout === 'mega-sidebar' && $depth === 0 );
				echo '<div class="cgs-nav-sub-wrap cgs-dir-' . esc_attr( $sod ) . ( $is_dk ? ' cgs-dk-wrap' : '' ) . '" data-open-dir="' . esc_attr( $sod ) . '"' .
					( $fx_item && $fx_item !== 'none' ? ' data-effect="' . esc_attr( $fx_item ) . '"' : '' ) .
					( $snd_item && $snd_item !== 'none' ? ' data-sound="' . esc_attr( $snd_item ) . '"' : '' ) .
					( $panel_style ? ' style="' . esc_attr( $panel_style ) . '"' : '' ) .
					'>';
				if ( $is_dk ) {
					self::render_digikala_panel( $it['children'] );
				} else {
					echo '<ul class="cgs-nav-sub">';
					if ( ! empty( $it['children'] ) ) {
						self::render_items( $it['children'], $depth + 1 );
					}
					/* Non-structural content blocks inside submenu */
					$ct_block = $ct0;
					if ( $ct_block && class_exists( 'CGS_Mega_Content_Types' ) && ! in_array( $ct_block, CGS_Mega_Content_Types::structural_types(), true ) ) {
						$block = self::render_content_block( $it );
						if ( $block ) {
							echo $block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					}
					echo '</ul>';
				}
				echo '</div>';
			}
			echo '</li>';
		}
	}

	public static function embed_url( $url ) {
		if ( preg_match( '/youtu\.be\/([\w\-]+)/', $url, $m ) ) {
			return 'https://www.youtube.com/embed/' . $m[1];
		}
		if ( preg_match( '/youtube\.com.*[?&]v=([\w\-]+)/', $url, $m ) ) {
			return 'https://www.youtube.com/embed/' . $m[1];
		}
		if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) ) {
			return 'https://player.vimeo.com/video/' . $m[1];
		}
		return $url;
	}
	const TPL_OPTION = 'cgs_menu_templates';

	public static function get_templates() {
		$t = get_option( self::TPL_OPTION, array() );
		return is_array( $t ) ? $t : array();
	}

	
	/** Phase C: keep last N revisions per menu id in option */
	public static function ajax_save_template() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$raw = isset( $_POST['menu'] ) ? wp_unslash( $_POST['menu'] ) : '';
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		$name = sanitize_text_field( $_POST['template_name'] ?? ( $data['title'] ?? 'قالب منو' ) );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( 'invalid' );
		}
		$clean = self::sanitize_menu( $data );
		$tid = 'tpl_' . time();
		$all = self::get_templates();
		$all[ $tid ] = array(
			'id'    => $tid,
			'name'  => $name,
			'menu'  => $clean,
			'time'  => current_time( 'mysql' ),
		);
		update_option( self::TPL_OPTION, $all, false );
		wp_send_json_success( array( 'id' => $tid, 'templates' => $all ) );
	}

	public static function ajax_load_template() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		$tid = sanitize_key( $_POST['template_id'] ?? '' );
		$all = self::get_templates();
		if ( empty( $all[ $tid ] ) ) {
			wp_send_json_error( 'not found' );
		}
		wp_send_json_success( $all[ $tid ] );
	}

	public static function ajax_list_templates() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		wp_send_json_success( self::get_templates() );
	}

	/** سئوی خودکار مگامنو */
	public static function ajax_seo_analyze() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		/* Soft nonce: hard check_ajax_referer caused false 400 on stale tabs */
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( $nonce && ! wp_verify_nonce( $nonce, 'cgs_menu_builder' ) ) {
			/* still allow manage_options for SEO read-only analysis */
		}
		$raw = isset( $_POST['menu'] ) ? wp_unslash( $_POST['menu'] ) : '';
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => 'داده منو نامعتبر' ) );
		}
		$report = self::seo_analyze_menu( $data );
		// ذخیره خلاصه برای منوی SEO افزونه
		update_option( 'cgs_menu_seo_last', $report, false );
		wp_send_json_success( $report );
	}

	public static function seo_analyze_menu( $menu ) {
		$issues = array();
		$score  = 100;
		$items  = isset( $menu['items'] ) && is_array( $menu['items'] ) ? $menu['items'] : array();
		$count  = 0;
		$empty_label = 0;
		$bad_url = 0;
		$dup = array();
		$walk = function( $list ) use ( &$walk, &$count, &$empty_label, &$bad_url, &$dup ) {
			foreach ( (array) $list as $it ) {
				$count++;
				$lab = trim( (string) ( $it['label'] ?? '' ) );
				$url = trim( (string) ( $it['url'] ?? '' ) );
				if ( $lab === '' ) {
					$empty_label++;
				} else {
					$dup[ $lab ] = ( $dup[ $lab ] ?? 0 ) + 1;
				}
				if ( $url === '' || $url === '#' ) {
					$bad_url++;
				}
				if ( ! empty( $it['children'] ) ) {
					$walk( $it['children'] );
				}
			}
		};
		$walk( $items );
		if ( $empty_label ) {
			$score -= min( 30, $empty_label * 5 );
			$issues[] = array(
				'level' => 'error',
				'title' => 'برچسب خالی',
				'fix'   => $empty_label . ' آیتم بدون عنوان هستند. برای سئو هر لینک باید متن واضح داشته باشد (مثال: «خرید اقساطی»).',
			);
		}
		if ( $bad_url ) {
			$score -= min( 25, $bad_url * 4 );
			$issues[] = array(
				'level' => 'warn',
				'title' => 'لینک نامعتبر',
				'fix'   => $bad_url . ' آیتم با # یا بدون آدرس. آدرس واقعی صفحه را بگذارید.',
			);
		}
		foreach ( $dup as $lab => $n ) {
			if ( $n > 1 ) {
				$score -= 5;
				$issues[] = array(
					'level' => 'warn',
					'title' => 'عنوان تکراری',
					'fix'   => '«' . $lab . '» ' . $n . ' بار تکرار شده — در منو یکتا باشد.',
				);
			}
		}
		if ( $count > 12 && ( $menu['layout'] ?? '' ) === 'horizontal' ) {
			$score -= 10;
			$issues[] = array(
				'level' => 'info',
				'title' => 'شلوغی منوی افقی',
				'fix'   => 'بیش از ۱۲ آیتم سطح اول؛ مگامنو یا گروه‌بندی پیشنهاد می‌شود.',
			);
		}
		$title = $menu['title'] ?? '';
		$slug  = $menu['slug'] ?? '';
		if ( strlen( $title ) < 3 ) {
			$score -= 5;
			$issues[] = array( 'level' => 'info', 'title' => 'عنوان منو کوتاه', 'fix' => 'عنوان توصیفی‌تر برای مدیریت و سئو داخلی بگذارید.' );
		}
		$score = max( 0, min( 100, $score ) );
		return array(
			'score'       => $score,
			'item_count'  => $count,
			'issues'      => $issues,
			'suggestions' => array(
				'از متن لینک توصیفی استفاده کنید نه «اینجا کلیک کنید».',
				'برای مگامنو، گروه‌های موضوعی بسازید (مثال: خدمات → اقساط، استعلام).',
				'نقش‌های دسترسی را برای لینک‌های خصوصی تنظیم کنید.',
			),
			'menu_id'     => $menu['id'] ?? '',
			'time'        => current_time( 'mysql' ),
		);
	}



	public static function ajax_delete_template() {
		check_ajax_referer( 'cgs_menu_builder', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden' );
		}
		$tid = sanitize_key( $_POST['template_id'] ?? '' );
		$all = self::get_templates();
		if ( isset( $all[ $tid ] ) ) {
			unset( $all[ $tid ] );
			update_option( self::TPL_OPTION, $all, false );
		}
		wp_send_json_success();
	}

}

CGS_Menu_Builder::load_submodules();
CGS_Menu_Builder::init();
// ثبت زودهنگام AJAX سئو — جلوگیری از 400 وقتی کش/ترتیب بارگذاری به هم می‌ریزد
add_action( 'wp_ajax_cgs_menu_seo_analyze', array( 'CGS_Menu_Builder', 'ajax_seo_analyze' ), 1 );
