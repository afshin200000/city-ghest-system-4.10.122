<?php
/**
 * ماژول: SEO Engine
 * موتور سئوی پویا با خودارزیابی، پیشنهاد، اعمال امن، و گزارش امتیاز رتبه‌بندی داخلی
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cgs_module_seo_engine_enabled' ) ) {
	function cgs_module_seo_engine_enabled() {
		$flags = get_option( 'cgs_module_flags', array() );
		if ( ! is_array( $flags ) ) {
			$flags = array();
		}
		if ( ! array_key_exists( 'seo-engine', $flags ) && ! array_key_exists( 'seo_engine', $flags ) ) {
			return true;
		}
		if ( array_key_exists( 'seo-engine', $flags ) ) {
			return ! empty( $flags['seo-engine'] );
		}
		return ! empty( $flags['seo_engine'] );
	}
}

if ( ! cgs_module_seo_engine_enabled() ) {
	if ( function_exists( 'cgs_module_log' ) ) {
		cgs_module_log( 'seo-engine', 'disabled', 'SEO Engine خاموش است' );
	}
	return;
}

if ( ! class_exists( 'CGS_SEO_Engine' ) ) {

	class CGS_SEO_Engine {

		const OPT_SETTINGS = 'cgs_seo_engine_settings';
		const OPT_REPORT   = 'cgs_seo_engine_last_report';
		const OPT_HISTORY  = 'cgs_seo_engine_history';
		const OPT_QUEUE    = 'cgs_seo_engine_fix_log';
		const CRON_HOOK    = 'cgs_seo_engine_cron_audit';

		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 85 );
			add_action( 'wp_head', array( __CLASS__, 'output_head' ), 4 );
			add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 2 );
			add_action( 'wp_ajax_cgs_seo_engine_run', array( __CLASS__, 'ajax_run' ) );
			add_action( 'wp_ajax_cgs_seo_engine_apply', array( __CLASS__, 'ajax_apply' ) );
			add_action( 'wp_ajax_cgs_seo_engine_apply_all_safe', array( __CLASS__, 'ajax_apply_all_safe' ) );
			add_action( 'wp_ajax_cgs_seo_engine_save_settings', array( __CLASS__, 'ajax_save_settings' ) );
			add_action( self::CRON_HOOK, array( __CLASS__, 'cron_audit' ) );
			add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
			add_filter( 'robots_txt', array( __CLASS__, 'filter_robots' ), 10, 2 );
			add_action( 'init', array( __CLASS__, 'register_sitemap_rewrite' ), 5 );
			add_action( 'template_redirect', array( __CLASS__, 'serve_sitemap' ), 1 );
		}

		public static function settings() {
			$d = array(
				'auto_audit'       => 1,
				'auto_fix_safe'   => 1,
				'cron_hours'       => 24,
				'indexnow_key'     => '',
				'site_description' => get_bloginfo( 'description' ),
				'org_name'         => get_bloginfo( 'name' ),
				'default_robots'   => 'index,follow',
				'max_posts_scan'   => 80,
			);
			$o = get_option( self::OPT_SETTINGS, array() );
			return wp_parse_args( is_array( $o ) ? $o : array(), $d );
		}

		public static function maybe_schedule() {
			$s = self::settings();
			if ( empty( $s['auto_audit'] ) ) {
				return;
			}
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() + 300, 'daily', self::CRON_HOOK );
			}
		}

		public static function cron_audit() {
			$report = self::run_full_cycle( true );
			update_option( self::OPT_REPORT, $report, false );
		}

		public static function admin_menu() {
			add_submenu_page(
				'city-ghest',
				'موتور سئو هوشمند',
				'موتور سئو (SEO Engine)',
				'manage_options',
				'cgs-seo-engine',
				array( __CLASS__, 'render_admin' )
			);
		}

		public static function render_admin() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$file = dirname( __FILE__ ) . '/views/admin.php';
			if ( is_readable( $file ) ) {
				include $file;
			}
		}

		/** چرخه کامل: ممیزی → نقد خود → پیشنهاد → اعمال امن (اختیاری) → امتیاز مجدد */
		public static function run_full_cycle( $apply_safe = null ) {
			$s = self::settings();
			if ( $apply_safe === null ) {
				$apply_safe = ! empty( $s['auto_fix_safe'] );
			}

			$audit1 = self::audit_site();
			$critique = self::self_critique( $audit1 );
			$suggestions = self::build_suggestions( $audit1, $critique );

			$applied = array();
			if ( $apply_safe ) {
				foreach ( $suggestions as $sg ) {
					if ( ! empty( $sg['auto'] ) && ! empty( $sg['id'] ) ) {
						$r = self::apply_suggestion( $sg['id'], $sg );
						if ( ! empty( $r['ok'] ) ) {
							$applied[] = $sg['id'];
						}
					}
				}
			}

			$audit2 = self::audit_site();
			$rank = self::ranking_report( $audit2 );

			$report = array(
				'time'            => current_time( 'mysql' ),
				'score_before'    => intval( $audit1['score'] ?? 0 ),
				'score_after'     => intval( $audit2['score'] ?? 0 ),
				'audit'           => $audit2,
				'critique'        => $critique,
				'suggestions'     => $suggestions,
				'applied_safe'    => $applied,
				'ranking'         => $rank,
				'engine_version'  => '1.0.0',
				'note_fa'         => 'امتیاز رتبه‌بندی داخلی بر اساس سیگنال‌های فنی/محتوایی است؛ رتبه واقعی گوگل فقط از Search Console قابل مشاهده است.',
			);

			$hist = get_option( self::OPT_HISTORY, array() );
			if ( ! is_array( $hist ) ) {
				$hist = array();
			}
			$hist[] = array(
				'time'  => $report['time'],
				'before'=> $report['score_before'],
				'after' => $report['score_after'],
				'rank'  => $rank['level'] ?? '',
			);
			if ( count( $hist ) > 30 ) {
				$hist = array_slice( $hist, -30 );
			}
			update_option( self::OPT_HISTORY, $hist, false );
			update_option( self::OPT_REPORT, $report, false );
			return $report;
		}

		public static function audit_site() {
			$s = self::settings();
			$issues = array();
			$score  = 100;
			$max    = max( 10, min( 150, intval( $s['max_posts_scan'] ) ) );

			// — سایت —
			$name = get_bloginfo( 'name' );
			$desc = get_bloginfo( 'description' );
			if ( strlen( trim( $name ) ) < 3 ) {
				$score -= 10;
				$issues[] = array( 'id' => 'site_title', 'level' => 'error', 'area' => 'site', 'title' => 'نام سایت خیلی کوتاه', 'detail' => 'Settings → General' );
			}
			if ( strlen( trim( $desc ) ) < 20 ) {
				$score -= 8;
				$issues[] = array( 'id' => 'site_tagline', 'level' => 'warn', 'area' => 'site', 'title' => 'معرفی سایت (Tagline) ضعیف یا خالی', 'detail' => 'حداقل ۲۰ کاراکتر توصیفی' );
			}

			$home = home_url( '/' );
			if ( strpos( $home, 'http://' ) === 0 ) {
				$score -= 5;
				$issues[] = array( 'id' => 'https', 'level' => 'warn', 'area' => 'tech', 'title' => 'سایت روی HTTP است', 'detail' => 'HTTPS برای سئو و اعتماد ضروری است' );
			}

			$permalink = get_option( 'permalink_structure' );
			if ( empty( $permalink ) ) {
				$score -= 12;
				$issues[] = array( 'id' => 'permalinks', 'level' => 'error', 'area' => 'tech', 'title' => 'پیوند یکتا ساده/خالی', 'detail' => 'Settings → Permalinks → Post name' );
			}

			// — محتوا —
			$q = new WP_Query( array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => $max,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			) );
			$empty_title = 0;
			$empty_desc  = 0;
			$thin        = 0;
			$no_image    = 0;
			$dup_titles  = array();
			$checked     = 0;
			foreach ( $q->posts as $p ) {
				$checked++;
				$t = trim( $p->post_title );
				if ( $t === '' ) {
					$empty_title++;
				} else {
					$dup_titles[ $t ] = ( $dup_titles[ $t ] ?? 0 ) + 1;
				}
				$meta_d = get_post_meta( $p->ID, '_cgs_seo_description', true );
				if ( $meta_d === '' || $meta_d === false ) {
					$excerpt = wp_strip_all_tags( $p->post_excerpt ? $p->post_excerpt : $p->post_content );
					if ( mb_strlen( $excerpt ) < 40 ) {
						$empty_desc++;
					}
				}
				$content_len = mb_strlen( wp_strip_all_tags( $p->post_content ) );
				if ( $content_len > 0 && $content_len < 150 ) {
					$thin++;
				}
				if ( ! has_post_thumbnail( $p->ID ) && $p->post_type === 'post' ) {
					$no_image++;
				}
			}
			wp_reset_postdata();

			if ( $empty_title ) {
				$score -= min( 15, $empty_title * 3 );
				$issues[] = array( 'id' => 'empty_titles', 'level' => 'error', 'area' => 'content', 'title' => $empty_title . ' محتوا بدون عنوان', 'detail' => 'عنوان خالی برای سئو مخرب است', 'count' => $empty_title );
			}
			if ( $empty_desc ) {
				$score -= min( 12, $empty_desc * 2 );
				$issues[] = array( 'id' => 'empty_meta_desc', 'level' => 'warn', 'area' => 'content', 'title' => $empty_desc . ' صفحه بدون توضیح متا قابل استخراج', 'detail' => 'توضیح متا از خلاصه ساخته می‌شود', 'count' => $empty_desc, 'auto' => true );
			}
			if ( $thin ) {
				$score -= min( 10, $thin );
				$issues[] = array( 'id' => 'thin_content', 'level' => 'info', 'area' => 'content', 'title' => $thin . ' محتوای نازک (<150 کاراکتر)', 'detail' => 'محتوای کوتاه معمولاً رتبه نمی‌گیرد', 'count' => $thin );
			}
			if ( $no_image ) {
				$score -= min( 6, (int) ceil( $no_image / 3 ) );
				$issues[] = array( 'id' => 'no_thumb', 'level' => 'info', 'area' => 'content', 'title' => $no_image . ' نوشته بدون تصویر شاخص', 'detail' => 'OG و جذابیت نتایج جستجو', 'count' => $no_image );
			}
			foreach ( $dup_titles as $tt => $n ) {
				if ( $n > 1 ) {
					$score -= 4;
					$issues[] = array( 'id' => 'dup_title_' . md5( $tt ), 'level' => 'warn', 'area' => 'content', 'title' => 'عنوان تکراری: ' . mb_substr( $tt, 0, 40 ), 'detail' => $n . ' بار' );
					break;
				}
			}

			// — منو —
			$menu_seo = get_option( 'cgs_menu_seo_last', array() );
			if ( is_array( $menu_seo ) && isset( $menu_seo['score'] ) ) {
				$ms = intval( $menu_seo['score'] );
				if ( $ms < 70 ) {
					$score -= 5;
					$issues[] = array( 'id' => 'menu_seo', 'level' => 'warn', 'area' => 'menu', 'title' => 'امتیاز سئوی منو پایین (' . $ms . ')', 'detail' => 'منوساز → تحلیل سئو' );
				}
			}

			// — نقشه سایت —
			$sitemap_ok = true;
			$score = max( 0, min( 100, $score ) );

			return array(
				'score'     => $score,
				'issues'    => $issues,
				'stats'     => array(
					'posts_scanned' => $checked,
					'empty_desc'    => $empty_desc,
					'thin'          => $thin,
					'no_image'      => $no_image,
				),
				'sitemap_url' => home_url( '/cgs-sitemap.xml' ),
				'time'      => current_time( 'mysql' ),
			);
		}

		/** نقد عملکرد خود موتور نسبت به نتیجه ممیزی */
		public static function self_critique( $audit ) {
			$score = intval( $audit['score'] ?? 0 );
			$lines = array();
			if ( $score >= 90 ) {
				$lines[] = 'وضعیت فنی خوب است؛ تمرکز بعدی باید روی محتوا و لینک‌سازی بیرونی باشد (خارج از موتور خودکار).';
			} elseif ( $score >= 70 ) {
				$lines[] = 'سیگنال‌های فنی قابل قبول‌اند اما چند ضعف محتوایی/متا باقی است که موتور می‌تواند بخشی را خودکار پر کند.';
			} elseif ( $score >= 50 ) {
				$lines[] = 'نقص‌های متوسط شناسایی شد. اعمال پیشنهادهای امن و سپس ممیزی مجدد ضروری است.';
			} else {
				$lines[] = 'وضعیت ضعیف: پیوند یکتا، HTTPS یا عناوین خالی احتمالاً مانع ایندکس مؤثرند.';
			}
			$err = 0;
			$warn = 0;
			foreach ( (array) ( $audit['issues'] ?? array() ) as $i ) {
				if ( ( $i['level'] ?? '' ) === 'error' ) {
					$err++;
				}
				if ( ( $i['level'] ?? '' ) === 'warn' ) {
					$warn++;
				}
			}
			$lines[] = "تعداد خطای جدی: {$err} — هشدار: {$warn}.";
			$lines[] = 'موتور فقط اصلاحات امن (متا، نقشه سایت، robots) را خودکار می‌کند؛ تغییر محتوای اصلی نیاز به تأیید ادمین دارد.';
			return $lines;
		}

		public static function build_suggestions( $audit, $critique ) {
			$sugs = array();
			foreach ( (array) ( $audit['issues'] ?? array() ) as $iss ) {
				$id = $iss['id'] ?? '';
				$auto = false;
				$action = '';
				if ( $id === 'empty_meta_desc' ) {
					$auto = true;
					$action = 'تولید توضیح متا از خلاصه/ابتدای متن برای صفحات بدون متا';
				} elseif ( $id === 'site_tagline' ) {
					$auto = true;
					$action = 'اگر تنظیم موتور توضیح سایت دارد، به‌عنوان پیش‌فرض متای خانه استفاده شود';
				} elseif ( $id === 'permalinks' ) {
					$action = 'از تنظیمات وردپرس پیوند یکتا را روی «نام نوشته» بگذارید (دستی)';
				} elseif ( $id === 'https' ) {
					$action = 'گواهی SSL و آدرس سایت را HTTPS کنید (دستی/هاست)';
				} elseif ( $id === 'menu_seo' ) {
					$action = 'منوساز → تحلیل سئو و رفع لینک‌های #';
				} else {
					$action = $iss['detail'] ?? 'بررسی دستی';
				}
				$sugs[] = array(
					'id'     => $id,
					'title'  => $iss['title'] ?? $id,
					'action' => $action,
					'level'  => $iss['level'] ?? 'info',
					'auto'   => $auto || ! empty( $iss['auto'] ),
					'area'   => $iss['area'] ?? 'general',
				);
			}
			// پیشنهاد دائمی موتور
			$sugs[] = array(
				'id'     => 'regen_sitemap',
				'title'  => 'بازسازی نقشه سایت',
				'action' => 'به‌روزرسانی cgs-sitemap.xml',
				'level'  => 'info',
				'auto'   => true,
				'area'   => 'tech',
			);
			$sugs[] = array(
				'id'     => 'org_schema',
				'title'  => 'اطمینان از Schema سازمان در صفحه اصلی',
				'action' => 'JSON-LD Organization در wp_head',
				'level'  => 'info',
				'auto'   => true,
				'area'   => 'tech',
			);
			return $sugs;
		}

		public static function apply_suggestion( $id, $sg = array() ) {
			$log = get_option( self::OPT_QUEUE, array() );
			if ( ! is_array( $log ) ) {
				$log = array();
			}
			$ok = false;
			$msg = '';

			switch ( $id ) {
				case 'empty_meta_desc':
					$n = self::autofill_meta_descriptions( 40 );
					$ok = true;
					$msg = "متا برای {$n} مورد پر شد";
					break;
				case 'site_tagline':
					$s = self::settings();
					if ( strlen( trim( $s['site_description'] ) ) >= 20 ) {
						$ok = true;
						$msg = 'توضیح پیش‌فرض موتور برای خانه فعال است';
					} else {
						$msg = 'ابتدا در تنظیمات موتور توضیح سایت را کامل کنید';
					}
					break;
				case 'regen_sitemap':
					update_option( 'cgs_seo_engine_sitemap_time', current_time( 'mysql' ), false );
					$ok = true;
					$msg = 'نقشه سایت در درخواست بعدی تازه می‌شود';
					break;
				case 'org_schema':
					update_option( 'cgs_seo_engine_schema_org', 1, false );
					$ok = true;
					$msg = 'Schema سازمان فعال شد';
					break;
				default:
					$msg = 'این مورد نیاز به اقدام دستی دارد';
			}

			$log[] = array( 'id' => $id, 'ok' => $ok, 'msg' => $msg, 'time' => current_time( 'mysql' ) );
			if ( count( $log ) > 50 ) {
				$log = array_slice( $log, -50 );
			}
			update_option( self::OPT_QUEUE, $log, false );
			return array( 'ok' => $ok, 'msg' => $msg );
		}

		public static function autofill_meta_descriptions( $limit = 40 ) {
			$q = new WP_Query( array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_query'     => array(
					'relation' => 'OR',
					array( 'key' => '_cgs_seo_description', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_cgs_seo_description', 'value' => '', 'compare' => '=' ),
				),
			) );
			$n = 0;
			foreach ( $q->posts as $p ) {
				$text = $p->post_excerpt ? $p->post_excerpt : $p->post_content;
				$text = wp_strip_all_tags( $text );
				$text = preg_replace( '/\s+/', ' ', $text );
				$text = mb_substr( trim( $text ), 0, 155 );
				if ( mb_strlen( $text ) >= 40 ) {
					update_post_meta( $p->ID, '_cgs_seo_description', $text );
					$n++;
				}
			}
			wp_reset_postdata();
			return $n;
		}

		/** گزارش سطح رتبه‌بندی داخلی (نه رتبه واقعی گوگل) */
		public static function ranking_report( $audit ) {
			$score = intval( $audit['score'] ?? 0 );
			if ( $score >= 90 ) {
				$level = 'A';
				$label = 'عالی — آماده رقابت فنی';
			} elseif ( $score >= 80 ) {
				$level = 'B';
				$label = 'خوب — بهینه‌سازی جزئی باقی مانده';
			} elseif ( $score >= 65 ) {
				$level = 'C';
				$label = 'متوسط — نیاز به رفع هشدارها';
			} elseif ( $score >= 45 ) {
				$level = 'D';
				$label = 'ضعیف — موانع ایندکس/محتوا';
			} else {
				$level = 'E';
				$label = 'بحرانی — اصلاح فوری فنی';
			}
			return array(
				'score'       => $score,
				'level'       => $level,
				'label'       => $label,
				'percentile'  => $score, // تخمین نسبی داخلی
				'competitors' => 'برای رتبه واقعی کلمات کلیدی از Google Search Console و ابزارهای SERP استفاده کنید.',
			);
		}

		public static function on_save_post( $post_id, $post ) {
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				return;
			}
			if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
				return;
			}
			$desc = get_post_meta( $post_id, '_cgs_seo_description', true );
			if ( $desc === '' || $desc === false ) {
				$text = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
				$text = mb_substr( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ), 0, 155 );
				if ( mb_strlen( $text ) >= 40 ) {
					update_post_meta( $post_id, '_cgs_seo_description', $text );
				}
			}
		}

		public static function output_head() {
			if ( is_admin() ) {
				return;
			}
			$s = self::settings();
			echo "\n<!-- CGS SEO Engine -->\n";
			$desc = '';
			$title_extra = '';
			if ( is_singular() ) {
				$desc = (string) get_post_meta( get_the_ID(), '_cgs_seo_description', true );
				if ( $desc === '' ) {
					$desc = wp_strip_all_tags( get_the_excerpt() );
				}
			} elseif ( is_front_page() ) {
				$desc = $s['site_description'] ?: get_bloginfo( 'description' );
			}
			$desc = mb_substr( preg_replace( '/\s+/', ' ', $desc ), 0, 160 );
			if ( $desc ) {
				echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
			}
			$robots = $s['default_robots'] ?: 'index,follow';
			if ( is_search() || is_404() ) {
				$robots = 'noindex,follow';
			}
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '" />' . "\n";
			if ( is_singular() ) {
				echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '" />' . "\n";
			}
			// Open Graph
			if ( is_singular() ) {
				echo '<meta property="og:type" content="article" />' . "\n";
				echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
				echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
				if ( $desc ) {
					echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
				}
				if ( has_post_thumbnail() ) {
					echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( null, 'large' ) ) . '" />' . "\n";
				}
			}
			if ( is_front_page() || get_option( 'cgs_seo_engine_schema_org' ) ) {
				$org = array(
					'@context' => 'https://schema.org',
					'@type'    => 'Organization',
					'name'     => $s['org_name'] ?: get_bloginfo( 'name' ),
					'url'      => home_url( '/' ),
				);
				echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
			}
			echo '<link rel="sitemap" type="application/xml" href="' . esc_url( home_url( '/cgs-sitemap.xml' ) ) . '" />' . "\n";
			echo "<!-- /CGS SEO Engine -->\n";
		}

		public static function register_sitemap_rewrite() {
			// سرو از template_redirect با query var ساده
		}

		public static function serve_sitemap() {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			if ( strpos( $uri, 'cgs-sitemap.xml' ) === false ) {
				return;
			}
			header( 'Content-Type: application/xml; charset=UTF-8' );
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
			echo '<url><loc>' . esc_url( home_url( '/' ) ) . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>' . "\n";
			$q = new WP_Query( array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'orderby'        => 'modified',
				'fields'         => 'ids',
			) );
			foreach ( $q->posts as $pid ) {
				echo '<url><loc>' . esc_url( get_permalink( $pid ) ) . '</loc><lastmod>' . esc_html( get_post_modified_time( 'c', true, $pid ) ) . '</lastmod></url>' . "\n";
			}
			echo '</urlset>';
			exit;
		}

		public static function filter_robots( $output, $public ) {
			$output .= "\nSitemap: " . home_url( '/cgs-sitemap.xml' ) . "\n";
			return $output;
		}

		public static function ajax_run() {
			check_ajax_referer( 'cgs_seo_engine', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'forbidden' );
			}
			$apply = ! empty( $_POST['apply_safe'] );
			$report = self::run_full_cycle( $apply );
			wp_send_json_success( $report );
		}

		public static function ajax_apply() {
			check_ajax_referer( 'cgs_seo_engine', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'forbidden' );
			}
			$id = sanitize_key( $_POST['fix_id'] ?? '' );
			$r = self::apply_suggestion( $id );
			wp_send_json_success( $r );
		}

		public static function ajax_apply_all_safe() {
			check_ajax_referer( 'cgs_seo_engine', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'forbidden' );
			}
			$report = self::run_full_cycle( true );
			wp_send_json_success( $report );
		}

		public static function ajax_save_settings() {
			check_ajax_referer( 'cgs_seo_engine', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'forbidden' );
			}
			$s = self::settings();
			$s['auto_audit'] = ! empty( $_POST['auto_audit'] ) ? 1 : 0;
			$s['auto_fix_safe'] = ! empty( $_POST['auto_fix_safe'] ) ? 1 : 0;
			$s['site_description'] = sanitize_text_field( wp_unslash( $_POST['site_description'] ?? '' ) );
			$s['org_name'] = sanitize_text_field( wp_unslash( $_POST['org_name'] ?? '' ) );
			$s['default_robots'] = sanitize_text_field( wp_unslash( $_POST['default_robots'] ?? 'index,follow' ) );
			$s['indexnow_key'] = sanitize_text_field( wp_unslash( $_POST['indexnow_key'] ?? '' ) );
			update_option( self::OPT_SETTINGS, $s, false );
			wp_send_json_success( $s );
		}
	}

	CGS_SEO_Engine::init();
}
