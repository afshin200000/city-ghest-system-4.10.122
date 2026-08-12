<?php
/**
 * CGS Mega Menu Templates — original templates inspired by commercial mega-menu UX patterns.
 * Not a derivative of UberMenu source; architecture idea only (row/column/content blocks).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CGS_Mega_Templates {

	public static function all() {
		return array(
			'digikala' => array(
				'name'   => 'دیجیکالا کامل (سایدبار دسته + ستون + بنر + برند)',
				'layout' => 'mega-sidebar',
				'mega_cols' => 4,
				'description' => 'مگامنوی واقعی دیجیکالا: سرستون ثابت + ستون لینک + بنر + ردیف برند',
			),
			'hero_content' => array(
				'name'   => 'Hero محتوایی (پست + تصویر + لیست)',
				'layout' => 'mega-content',
				'mega_cols' => 4,
				'description' => 'ستون پست، تصویر بزرگ، لیست آیکون‌دار',
			),
			'shop_products' => array(
				'name'   => 'فروشگاهی + اسلایدر محصول',
				'layout' => 'mega-products',
				'mega_cols' => 4,
				'description' => 'لیست لینک + کارت محصول + اسلایدر',
			),
			'brands_grid' => array(
				'name'   => 'گرید برند با لوگو',
				'layout' => 'mega-brands',
				'mega_cols' => 5,
				'description' => 'شبکه لوگو برند با آپلود',
			),
			'news_magazine' => array(
				'name'   => 'خبری / مجله',
				'layout' => 'mega-content',
				'mega_cols' => 4,
				'description' => 'سایدبرچسب + کارت‌های تصویری خبر',
			),
			'tabs_panel' => array(
				'name'   => 'تب‌دار (Uber-style Tabs)',
				'layout' => 'tabs',
				'mega_cols' => 3,
				'description' => 'سایدتب + پنل محتوا',
			),
			'finance_city' => array(
				'name'   => 'شهر قسط — مالی اقساطی',
				'layout' => 'mega-content',
				'mega_cols' => 4,
				'description' => 'طرح‌ها، محاسبه‌گر، اعتبار، پشتیبانی',
			),
			'fashion_dept_mega' => array(
				'name'   => 'مد و دپارتمان (سایدبار + ۴ ستون)',
				'layout' => 'mega-sidebar',
				'mega_cols' => 4,
				'description' => 'شبیه مگامنوی فروشگاهی مد: لیست دپارتمان چپ + ستون کفش/لباس/اکسسوری/ورزش',
			),
			'product_boards_mega' => array(
				'name'   => 'ویترین محصول (کارت + CTA)',
				'layout' => 'mega-products',
				'mega_cols' => 4,
				'description' => 'ردیف کارت محصول + پنل دعوت به اقدام شبیه Boards showcase',
			),
			'hubspot_platform' => array(
				'name'   => 'هاب‌اسپات — پلتفرم کارت‌محور',
				'layout' => 'mega-hub-grid',
				'mega_cols' => 4,
				'description' => 'مگامنوی کارت‌محور شبیه HubSpot: عنوان پلتفرم + گرید کارت امکانات',
			),
			'adidas_mega' => array(
				'name'   => 'آدیداس — ستون تصویری برند',
				'layout' => 'mega-content',
				'mega_cols' => 6,
				'description' => 'مگامنوی برند شبیه Adidas: ۶ ستون با تصویر بالای هر ستون + لیست لینک',
			),
			'woo_shop_mega' => array(
				'name'   => 'فروشگاهی ووکامرس (محصول + نظر + دسته)',
				'layout' => 'mega-products',
				'mega_cols' => 4,
				'description' => 'شبیه مگامنوی فروشگاهی: آخرین محصولات، لیست قیمت، نظرات، دسته‌ها + تصویر',
			),
			'wp_mega_classic' => array(
				'name'   => 'WP Mega کلاسیک (۴ ستون + پست + بنر)',
				'layout' => 'mega-content',
				'mega_cols' => 4,
				'description' => 'شبیه WP Mega Menu: ستون لینک + پست + تگ + بنر تبلیغاتی',
			),
			'hub_cards' => array(
				'name'   => 'هاب کارت‌ها (HubSpot style)',
				'layout' => 'mega-content',
				'mega_cols' => 4,
				'description' => 'کارت‌های آیکون + توضیح + لینک',
			),
		);
	}

	/** Full item trees for admin “اعمال قالب” */
	public static function tree( $id ) {
		$uid = function ( $p ) { return $p . '_' . substr( md5( $p . microtime( true ) ), 0, 6 ); };
		switch ( $id ) {
			case 'digikala':
				/* Real Digikala: sidebar categories switch content panes */
				return array(
					array(
						'id' => 'dk_root', 'label' => 'دسته‌بندی کالاها', 'url' => '#', 'icon' => '☰', 'content_type' => 'link',
						'children' => array(
							array(
								'id' => 'dk_cat_digital', 'label' => 'کالای دیجیتال', 'url' => '#', 'icon' => '📱', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'dk_h1', 'label' => 'لوازم جانبی گوشی', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_h1a', 'label' => 'کیف و کاور گوشی', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h1b', 'label' => 'پاور بانک (شارژ همراه)', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h1c', 'label' => 'پایه نگهدارنده گوشی', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h1d', 'label' => 'محافظ صفحه نمایش گوشی', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h1e', 'label' => 'کابل و مبدل', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_h2', 'label' => 'گوشی موبایل', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_h2a', 'label' => 'سامسونگ', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h2b', 'label' => 'شیائومی', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h2c', 'label' => 'اپل', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h2d', 'label' => 'هوآوی', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h2e', 'label' => 'نوکیا', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_h3', 'label' => 'هدفون و هدست', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_h3a', 'label' => 'هدفون بی‌سیم', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h3b', 'label' => 'هندزفری', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h3c', 'label' => 'اسپیکر بلوتوث', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_h4', 'label' => 'تبلت', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_h4a', 'label' => 'تبلت اندروید', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h4b', 'label' => 'آیپد', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_h4c', 'label' => 'کتابخوان', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_promo', 'label' => 'بنر ویژه', 'content_type' => 'image', 'url' => '#', 'image' => '' ),
									array( 'id' => 'dk_brands', 'label' => 'برندها', 'content_type' => 'row', 'children' => array(
										array( 'id' => 'dk_br1', 'label' => 'SAMSUNG', 'content_type' => 'brand', 'url' => '#' ),
										array( 'id' => 'dk_br2', 'label' => 'HONOR', 'content_type' => 'brand', 'url' => '#' ),
										array( 'id' => 'dk_br3', 'label' => 'HUAWEI', 'content_type' => 'brand', 'url' => '#' ),
									) ),
								),
							),
							array(
								'id' => 'dk_cat_car', 'label' => 'خودرو، ابزار و تجهیزات صنعتی', 'url' => '#', 'icon' => '🚗', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'dk_c1', 'label' => 'لوازم خودرو', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_c1a', 'label' => 'روغن موتور', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_c1b', 'label' => 'لاستیک', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_c1c', 'label' => 'باتری', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_c2', 'label' => 'ابزار برقی', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_c2a', 'label' => 'دریل', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_c2b', 'label' => 'فرز', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
							array(
								'id' => 'dk_cat_fashion', 'label' => 'مد و پوشاک', 'url' => '#', 'icon' => '👕', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'dk_f1', 'label' => 'مردانه', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_f1a', 'label' => 'پیراهن', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_f1b', 'label' => 'شلوار', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'dk_f2', 'label' => 'زنانه', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_f2a', 'label' => 'مانتو', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_f2b', 'label' => 'کیف', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
							array(
								'id' => 'dk_cat_home', 'label' => 'خانه و آشپزخانه', 'url' => '#', 'icon' => '🏠', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'dk_hm1', 'label' => 'لوازم آشپزخانه', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_hm1a', 'label' => 'قابلمه', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_hm1b', 'label' => 'ظروف', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
							array(
								'id' => 'dk_cat_beauty', 'label' => 'زیبایی و سلامت', 'url' => '#', 'icon' => '💄', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'dk_b1', 'label' => 'آرایشی', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'dk_b1a', 'label' => 'کرم', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'dk_b1b', 'label' => 'عطر', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
						),
					),
					array( 'id' => 'dk_home', 'label' => 'صفحه اصلی', 'url' => '#', 'icon' => '🏠', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'dk_super', 'label' => 'سوپرمارکت', 'url' => '#', 'icon' => '🛒', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'dk_offer', 'label' => 'شگفت‌انگیز', 'url' => '#', 'icon' => '🔥', 'badge' => 'SALE', 'content_type' => 'link', 'children' => array() ),
				);

			
			

			
			case 'fashion_dept_mega':
				/* Fashion department mega: sidebar depts + 4 content columns */
				return array(
					array(
						'id' => 'fd_root', 'label' => 'Shop', 'url' => '#', 'icon' => '☰', 'content_type' => 'link',
						'children' => array(
							array(
								'id' => 'fd_side', 'label' => 'All Departments', 'url' => '#', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'fd_shoes', 'label' => 'Shoes', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'fd_sh1', 'label' => 'Classic', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sh2', 'label' => 'Best Sellers', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sh3', 'label' => 'Boots', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sh4', 'label' => 'Sandals', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sh5', 'label' => 'Lifestyle', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'fd_cloth', 'label' => 'Clothing', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'fd_cl1', 'label' => 'Tees', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_cl2', 'label' => 'Hoodies', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_cl3', 'label' => 'Jackets', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_cl4', 'label' => 'Tops', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'fd_acc', 'label' => 'Accessories', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'fd_a1', 'label' => 'Bags', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_a2', 'label' => 'Hats', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_a3', 'label' => 'Socks', 'url' => '#', 'content_type' => 'link' ),
									) ),
									array( 'id' => 'fd_sport', 'label' => 'Sports', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'fd_sp1', 'label' => 'Training', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sp2', 'label' => 'Running', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sp3', 'label' => 'Basketball', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_sp4', 'label' => 'Golf', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
							array(
								'id' => 'fd_best', 'label' => 'Best Sellers', 'url' => '#', 'content_type' => 'link',
								'children' => array(
									array( 'id' => 'fd_bs1', 'label' => 'New Arrivals', 'content_type' => 'heading', 'children' => array(
										array( 'id' => 'fd_bs1a', 'label' => 'This Week', 'url' => '#', 'content_type' => 'link' ),
										array( 'id' => 'fd_bs1b', 'label' => 'Trending', 'url' => '#', 'content_type' => 'link' ),
									) ),
								),
							),
							array( 'id' => 'fd_gift', 'label' => 'Gift Cards', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
							array( 'id' => 'fd_sale', 'label' => 'Sale', 'url' => '#', 'badge' => 'HOT', 'content_type' => 'link', 'children' => array() ),
						),
					),
					array( 'id' => 'fd_home', 'label' => 'Home', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'fd_women', 'label' => 'Women', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'fd_men', 'label' => 'Men', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'fd_kids', 'label' => 'Kids', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'fd_outlet', 'label' => 'Outlet', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);

			case 'product_boards_mega':
				/* Product showcase: live woo cards + CTA panel */
				return array(
					array(
						'id' => 'pb_boards', 'label' => 'Boards', 'url' => '#', 'icon' => '🛹', 'content_type' => 'link',
						'children' => array(
							array( 'id' => 'pb_live', 'label' => 'Featured Boards', 'content_type' => 'woo_products', 'limit' => 4, 'orderby' => 'date', 'children' => array() ),
							array( 'id' => 'pb_card1', 'label' => 'Mohawk', 'content_type' => 'product_card', 'url' => '#', 'price' => '$89', 'image' => '', 'children' => array() ),
							array( 'id' => 'pb_card2', 'label' => 'Dodge', 'content_type' => 'product_card', 'url' => '#', 'price' => '$99', 'image' => '', 'children' => array() ),
							array( 'id' => 'pb_card3', 'label' => 'Bright', 'content_type' => 'product_card', 'url' => '#', 'price' => '$79', 'image' => '', 'children' => array() ),
							array( 'id' => 'pb_card4', 'label' => 'Max Pro', 'content_type' => 'product_card', 'url' => '#', 'price' => '$120', 'image' => '', 'children' => array() ),
							array( 'id' => 'pb_cta', 'label' => 'Start Riding Today', 'content_type' => 'hub_card', 'icon' => '🚀', 'desc' => 'Brand Finder Quiz · Learning to Ride · Complete Boards', 'link_label' => 'View All Boards →', 'url' => '#' ),
						),
					),
					array( 'id' => 'pb_std', 'label' => 'Standard', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'pb_acc', 'label' => 'Accessories', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'pb_comm', 'label' => 'Community', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);


			case 'hubspot_platform':
				return array(
					array(
						'id' => 'hs_products', 'label' => 'Products', 'url' => '#', 'icon' => '◆', 'content_type' => 'link',
						'children' => array(
							array( 'id' => 'hs_head', 'label' => 'The HubSpot Customer Platform', 'content_type' => 'heading', 'desc' => 'All of your marketing, sales, and customer service software on one AI-powered platform.', 'children' => array() ),
							array( 'id' => 'hs_mkt', 'label' => 'Marketing Hub', 'icon' => '📣', 'desc' => 'Marketing automation software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_sales', 'label' => 'Sales Hub', 'icon' => '📞', 'desc' => 'Sales software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_svc', 'label' => 'Service Hub', 'icon' => '💬', 'desc' => 'Customer service software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_cnt', 'label' => 'Content Hub', 'icon' => '📄', 'desc' => 'Content marketing software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_ops', 'label' => 'Operations Hub', 'icon' => '⚙️', 'desc' => 'Operations software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_com', 'label' => 'Commerce Hub', 'icon' => '🛒', 'desc' => 'B2B commerce software', 'link_label' => 'Free and premium plans', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_crm', 'label' => 'Smart CRM', 'icon' => '🧡', 'desc' => 'AI-powered CRM software', 'link_label' => 'Learn more', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_smb', 'label' => 'Small Business Bundle', 'icon' => '🏢', 'desc' => 'Starter edition for startups', 'link_label' => 'Learn more', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_breeze', 'label' => 'Breeze', 'icon' => '✨', 'desc' => 'AI tools powering the platform', 'link_label' => 'See all AI features', 'content_type' => 'hub_card', 'url' => '#' ),
							array( 'id' => 'hs_apps', 'label' => 'App Marketplace', 'icon' => '🧩', 'desc' => 'Connect your favorite apps', 'link_label' => 'See all integrations', 'content_type' => 'hub_card', 'url' => '#' ),
						),
					),
					array( 'id' => 'hs_sol', 'label' => 'Solutions', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'hs_price', 'label' => 'Pricing', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'hs_res', 'label' => 'Resources', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);

			case 'adidas_mega':
				return array(
					array(
						'id' => 'ad_men', 'label' => 'MEN', 'url' => '#', 'content_type' => 'link',
						'children' => array(
							array( 'id' => 'ad_new', 'label' => "WHAT'S NEW?", 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_new_img', 'label' => "What's New", 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_n1', 'label' => 'Impossible is Nothing', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_n2', 'label' => 'New Arrivals', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_n3', 'label' => 'Best Sellers', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_n4', 'label' => 'Release Dates', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_n5', 'label' => 'Stories', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'ad_colab', 'label' => 'COLLABORATIONS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_col_img', 'label' => 'Collaborations', 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_c1', 'label' => 'IVY PARK', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_c2', 'label' => 'Pharrell', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_c3', 'label' => 'Karlie Kloss', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_c4', 'label' => 'Prada', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_c5', 'label' => 'YEEZY', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_c6', 'label' => 'Stella McCartney', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'ad_world', 'label' => 'OUR WORLD', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_w_img', 'label' => 'Our World', 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_w1', 'label' => 'adidas Give Back', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_w2', 'label' => 'Sustainability', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_w3', 'label' => 'Futurecraft', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_w4', 'label' => 'adidas Legacy', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'ad_sports', 'label' => 'SPORTS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_s_img', 'label' => 'Sports', 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_s1', 'label' => 'Baseball', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_s2', 'label' => 'Basketball', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_s3', 'label' => 'Football', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_s4', 'label' => 'Running', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_s5', 'label' => 'Soccer', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_s6', 'label' => 'Tennis', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'ad_orig', 'label' => 'ORIGINALS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_o_img', 'label' => 'Originals', 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_o1', 'label' => 'Superstar', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_o2', 'label' => 'Stan Smith', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_o3', 'label' => 'Samba', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_o4', 'label' => 'Gazelle', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_o5', 'label' => 'Forum', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'ad_coll', 'label' => 'COLLECTIONS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'ad_cl_img', 'label' => 'Collections', 'content_type' => 'image', 'image' => '', 'url' => '#' ),
								array( 'id' => 'ad_cl1', 'label' => 'Adizero', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_cl2', 'label' => 'Copa', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_cl3', 'label' => 'Predator', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_cl4', 'label' => 'Ultraboost', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'ad_cl5', 'label' => 'NMD', 'url' => '#', 'content_type' => 'link' ),
							) ),
						),
					),
					array( 'id' => 'ad_women', 'label' => 'WOMEN', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'ad_kids', 'label' => 'KIDS', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'ad_sale', 'label' => 'SALE', 'url' => '#', 'badge' => 'HOT', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'ad_3s', 'label' => '3 STRIPE LIFE', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);

			case 'woo_shop_mega':
				/* P1: live WooCommerce product cards when WC active */
				return array(
					array(
						'id' => 'woo_shop', 'label' => 'SHOP', 'url' => '#', 'icon' => '🛒', 'badge' => 'SALE', 'content_type' => 'link',
						'children' => array(
							array( 'id' => 'woo_live', 'label' => 'LATEST PRODUCTS', 'content_type' => 'woo_products', 'limit' => 4, 'orderby' => 'date', 'children' => array() ),
							array( 'id' => 'woo_prods', 'label' => 'PRODUCTS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'woo_p1', 'label' => 'Woo Single #2 — $3.00', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'woo_p2', 'label' => 'Woo Album #4 — $9.00', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'woo_p3', 'label' => 'Woo Single #1 — $3.00', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'woo_rev', 'label' => 'RECENT REVIEWS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'woo_r1', 'label' => 'Woo Ninja — Rated 4/5 by Maria', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'woo_r2', 'label' => 'Premium Quality — Rated 5/5 by Maria', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'woo_cat', 'label' => 'PRODUCT CATEGORIES', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'woo_c1', 'label' => 'Exclusive Clothing', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'woo_c2', 'label' => 'Stylish Hoodies', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'woo_c3', 'label' => 'Mega Posters', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'woo_banner', 'label' => 'SHOP HERO IMAGE', 'content_type' => 'image', 'url' => '#', 'image' => '', 'image_id' => 0 ),
						),
					),
					array( 'id' => 'woo_std', 'label' => 'STANDARD', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'woo_mega', 'label' => 'MEGA ITEMS', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'woo_tabs', 'label' => 'TABS', 'url' => '#', 'badge' => 'HOT', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'woo_contact', 'label' => 'CONTACT US', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);

			case 'wp_mega_classic':			
				/* Classic multi-column mega like WP Mega Menu demo:
				 * 4 heading columns + posts card + recent list + tags + ad banner */
				return array(
					array(
						'id' => 'wpm_mega', 'label' => 'MEGA ITEMS', 'url' => '#', 'icon' => '▦', 'content_type' => 'link',
						'children' => array(
							array( 'id' => 'wpm_c1', 'label' => 'LAYOUTS BUILDER', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_c1a', 'label' => 'Advanced Feature', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c1b', 'label' => 'Potential Menus', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c1c', 'label' => 'High-Quality Mega', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'wpm_c2', 'label' => 'EASY CUSTOMIZATION', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_c2a', 'label' => 'Grained Control', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c2b', 'label' => 'Easy Integration', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c2c', 'label' => 'Interactive Elements', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'wpm_c3', 'label' => 'TABBED SUBMENU', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_c3a', 'label' => 'Priority Support', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c3b', 'label' => 'Easy Drag & Drop', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c3c', 'label' => 'Animation Options', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'wpm_c4', 'label' => 'MENU ANIMATION', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_c4a', 'label' => 'Styling Menus', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c4b', 'label' => 'Styling Options', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_c4c', 'label' => 'Advanced Interactive', 'url' => '#', 'content_type' => 'link' ),
							) ),
							/* second row content blocks */
							array( 'id' => 'wpm_posts', 'label' => 'POSTS CAROUSEL', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_pc1', 'label' => 'esk Impor — Milan Fashion', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_pc2', 'label' => 'Summer Collection 2026', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'wpm_recent', 'label' => 'RECENT POSTS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_rp1', 'label' => 'Milan Fashion Week Import', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_rp2', 'label' => 'Eiget Fels Nec: Puru Commo', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_rp3', 'label' => 'Scenes The Victoria Secret', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_lc', 'label' => 'LATEST COMMENTS', 'content_type' => 'heading', 'children' => array(
									array( 'id' => 'wpm_lc1', 'label' => 'Gerhard on Nanya Silhouette', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'wpm_lc2', 'label' => 'Maria on Premium Quality', 'url' => '#', 'content_type' => 'link' ),
								) ),
							) ),
							array( 'id' => 'wpm_tags', 'label' => 'TAG CLOUDS', 'content_type' => 'heading', 'children' => array(
								array( 'id' => 'wpm_t1', 'label' => 'Animations', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t2', 'label' => 'Builder', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t3', 'label' => 'Customize', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t4', 'label' => 'Drag', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t5', 'label' => 'Dropdown', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t6', 'label' => 'Drops', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t7', 'label' => 'Easy', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t8', 'label' => 'Features', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t9', 'label' => 'Mega Menu', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t10', 'label' => 'Options', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t11', 'label' => 'Plugins', 'url' => '#', 'content_type' => 'link' ),
								array( 'id' => 'wpm_t12', 'label' => 'Widgets', 'url' => '#', 'content_type' => 'link' ),
							) ),
							array( 'id' => 'wpm_ad', 'label' => 'IMAGE WIDGET — AD BANNER', 'content_type' => 'image', 'url' => '#', 'image' => '' ),
						),
					),
					array( 'id' => 'wpm_std', 'label' => 'STANDARD', 'url' => '#', 'icon' => '', 'content_type' => 'link', 'children' => array(
						array( 'id' => 'wpm_std1', 'label' => 'Menu Item 1', 'url' => '#', 'content_type' => 'link' ),
						array( 'id' => 'wpm_std2', 'label' => 'Menu Item 2', 'url' => '#', 'content_type' => 'link' ),
					) ),
					array( 'id' => 'wpm_dd', 'label' => 'DROPDOWN', 'url' => '#', 'content_type' => 'link', 'children' => array(
						array( 'id' => 'wpm_dd1', 'label' => 'Dropdown A', 'url' => '#', 'content_type' => 'link' ),
						array( 'id' => 'wpm_dd2', 'label' => 'Dropdown B', 'url' => '#', 'content_type' => 'link' ),
					) ),
					array( 'id' => 'wpm_tabs', 'label' => 'TABS', 'url' => '#', 'badge' => 'HOT', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'wpm_feat', 'label' => 'FEATURES', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'wpm_shop', 'label' => 'SHOP', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
					array( 'id' => 'wpm_contact', 'label' => 'CONTACT US', 'url' => '#', 'content_type' => 'link', 'children' => array() ),
				);

			case 'hero_content':
				return array(
					array(
						'id' => 'hr_root', 'label' => 'Demo', 'url' => '#', 'icon' => '⭐',
						'children' => array(
							array(
								'id' => 'hr_posts', 'label' => 'My blog posts', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'hr_p1', 'label' => 'Not my dog', 'desc' => 'خلاصه کوتاه پست', 'content_type' => 'card', 'image' => '' ),
									array( 'id' => 'hr_p2', 'label' => 'City that never sleeps', 'desc' => 'خلاصه', 'content_type' => 'card', 'image' => '' ),
								),
							),
							array( 'id' => 'hr_img', 'label' => 'Image Heading', 'content_type' => 'image', 'desc' => 'تصویر بزرگ', 'image' => '' ),
							array(
								'id' => 'hr_list', 'label' => 'List Heading', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'hr_l1', 'label' => 'About us', 'icon' => 'ℹ️', 'content_type' => 'link' ),
									array( 'id' => 'hr_l2', 'label' => 'Plugins', 'icon' => '🔌', 'content_type' => 'link' ),
									array( 'id' => 'hr_l3', 'label' => 'Support', 'icon' => '💬', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'hr_post2', 'label' => 'Post Heading', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'hr_x1', 'label' => 'My sweet life', 'content_type' => 'card', 'desc' => 'متن کوتاه' ),
								),
							),
						),
					),
				);

			case 'shop_products':
				return array(
					array(
						'id' => 'sh_root', 'label' => 'SHOP', 'url' => '#', 'icon' => '🛍️',
						'children' => array(
							array(
								'id' => 'sh_arr', 'label' => 'NEW ARRIVAL', 'content_type' => 'heading', 'badge' => 'NEW',
								'children' => array(
									array( 'id' => 'sh_a1', 'label' => 'Casual Tops', 'content_type' => 'link' ),
									array( 'id' => 'sh_a2', 'label' => 'Shirts & Blouses', 'content_type' => 'link' ),
									array( 'id' => 'sh_a3', 'label' => 'Tunics', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'sh_ed', 'label' => 'EDITORIAL', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'sh_e1', 'label' => 'Casual Tops', 'content_type' => 'link' ),
									array( 'id' => 'sh_e2', 'label' => 'Vests', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'sh_slider', 'label' => 'اسلایدر محصول', 'content_type' => 'product_slider',
								'desc' => 'اسلایدر محصولات داخل مگا',
								'products' => array(
									array( 'title' => 'کفش ورزشی', 'price' => '۲٬۸۰۰٬۰۰۰', 'image' => '', 'badge' => 'SALE' ),
									array( 'title' => 'کیف دستی', 'price' => '۱٬۵۰۰٬۰۰۰', 'image' => '' ),
									array( 'title' => 'تی‌شرت', 'price' => '۴۵۰٬۰۰۰', 'image' => '', 'badge' => 'NEW' ),
								),
								'children' => array(),
							),
							array(
								'id' => 'sh_best', 'label' => 'BEST SELLER', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'sh_b1', 'label' => 'Product A', 'content_type' => 'card', 'desc' => '150.00', 'badge' => 'HOT' ),
									array( 'id' => 'sh_b2', 'label' => 'Product B', 'content_type' => 'card', 'desc' => '150.00' ),
								),
							),
						),
					),
				);

			case 'brands_grid':
				return array(
					array(
						'id' => 'br_root', 'label' => 'برندها', 'url' => '#', 'icon' => '🏷️',
						'children' => array(
							array( 'id' => 'br1', 'label' => 'Apple', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br2', 'label' => 'Samsung', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br3', 'label' => 'Sony', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br4', 'label' => 'Microsoft', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br5', 'label' => 'Intel', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br6', 'label' => 'LG', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br7', 'label' => 'Huawei', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
							array( 'id' => 'br8', 'label' => 'Xiaomi', 'content_type' => 'brand', 'image' => '', 'url' => '#' ),
						),
					),
				);

			case 'news_magazine':
				return array(
					array(
						'id' => 'nw_root', 'label' => 'FASHION', 'url' => '#',
						'children' => array(
							array(
								'id' => 'nw_side', 'label' => 'بخش‌ها', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'nw_s1', 'label' => 'All', 'content_type' => 'link' ),
									array( 'id' => 'nw_s2', 'label' => 'New Look', 'content_type' => 'link' ),
									array( 'id' => 'nw_s3', 'label' => 'Street Fashion', 'content_type' => 'link' ),
									array( 'id' => 'nw_s4', 'label' => 'Vogue', 'content_type' => 'link' ),
								),
							),
							array( 'id' => 'nw_c1', 'label' => 'Fashion Outfit Ideas', 'content_type' => 'card', 'desc' => 'August 7, 2019', 'badge' => 'Vogue' ),
							array( 'id' => 'nw_c2', 'label' => 'Style Spy', 'content_type' => 'card', 'desc' => 'August 7, 2019', 'badge' => 'Vogue' ),
							array( 'id' => 'nw_c3', 'label' => 'Gala Night Looks', 'content_type' => 'card', 'desc' => 'August 7, 2019', 'badge' => 'Vogue' ),
						),
					),
				);

			
			case 'finance_city':
				return array(
					array(
						'id' => 'fc_root', 'label' => 'خدمات شهر قسط', 'url' => '#', 'icon' => '💳',
						'children' => array(
							array(
								'id' => 'fc_c1', 'label' => 'طرح‌های اعتباری', 'url' => '#', 'icon' => '📋', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'fc_c1a', 'label' => 'کسر از حقوق', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'fc_c1b', 'label' => 'چک صیادی', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'fc_c1c', 'label' => 'وثیقه ملکی', 'url' => '#', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'fc_c2', 'label' => 'ابزارها', 'url' => '#', 'icon' => '🧮', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'fc_c2a', 'label' => 'محاسبه‌گر اقساط', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'fc_c2b', 'label' => 'استعلام اعتبار', 'url' => '#', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'fc_c3', 'label' => 'فروشگاه‌ها', 'url' => '#', 'icon' => '🏪', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'fc_c3a', 'label' => 'لیست فروشگاه‌های طرف قرارداد', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'fc_c3b', 'label' => 'ثبت‌نام فروشگاه', 'url' => '#', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'fc_c4', 'label' => 'پشتیبانی', 'url' => '#', 'icon' => '🎧', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'fc_c4a', 'label' => 'تماس با ما', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'fc_c4b', 'label' => 'سؤالات متداول', 'url' => '#', 'content_type' => 'link' ),
								),
							),
						),
					),
				);


			case 'tabs_panel':
				return array(
					array(
						'id' => 'tb_root', 'label' => 'محصولات', 'url' => '#', 'icon' => '📑',
						'children' => array(
							array(
								'id' => 'tb_t1', 'label' => 'موبایل', 'url' => '#', 'icon' => '📱', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'tb_t1a', 'label' => 'گوشی هوشمند', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'tb_t1b', 'label' => 'تبلت', 'url' => '#', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'tb_t2', 'label' => 'لپ‌تاپ', 'url' => '#', 'icon' => '💻', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'tb_t2a', 'label' => 'گیمینگ', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'tb_t2b', 'label' => 'اداری', 'url' => '#', 'content_type' => 'link' ),
								),
							),
							array(
								'id' => 'tb_t3', 'label' => 'لوازم جانبی', 'url' => '#', 'icon' => '🎧', 'content_type' => 'heading',
								'children' => array(
									array( 'id' => 'tb_t3a', 'label' => 'هدفون', 'url' => '#', 'content_type' => 'link' ),
									array( 'id' => 'tb_t3b', 'label' => 'شارژر', 'url' => '#', 'content_type' => 'link' ),
								),
							),
						),
					),
				);

			case 'hub_cards':
				return array(
					array(
						'id' => 'hb_root', 'label' => 'Products', 'url' => '#',
						'children' => array(
							array( 'id' => 'hb1', 'label' => 'Marketing Hub', 'content_type' => 'card', 'desc' => 'اتوماسیون بازاریابی', 'icon' => '📈' ),
							array( 'id' => 'hb2', 'label' => 'Sales Hub', 'content_type' => 'card', 'desc' => 'نرم‌افزار فروش', 'icon' => '💼' ),
							array( 'id' => 'hb3', 'label' => 'Service Hub', 'content_type' => 'card', 'desc' => 'پشتیبانی مشتری', 'icon' => '❤️' ),
							array( 'id' => 'hb4', 'label' => 'CMS Hub', 'content_type' => 'card', 'desc' => 'مدیریت محتوا', 'icon' => '📝' ),
						),
					),
				);

			default:
				return array();
		}
	}
}
