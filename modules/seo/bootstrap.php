<?php
/**
 * ماژول: سئو ساز
 * لایه نازک — رفتار قبلی را حفظ می‌کند؛ فقط مرز ماژول و قابلیت فعال/غیرفعال.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cgs_module_seo_enabled' ) ) {
    /**
     * @return bool
     */
    function cgs_module_seo_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( ! is_array( $flags ) ) {
            $flags = array();
        }
        // پیش‌فرض: روشن (تا چیزی نشکند)
        if ( ! array_key_exists( 'seo', $flags ) ) {
            return true;
        }
        return ! empty( $flags['seo'] );
    }
}

if ( ! cgs_module_seo_enabled() ) {
    return;
}


/**
 * ماژول سئو — سبک و بدون تداخل با فرم‌ساز
 */
if ( ! class_exists( 'CGS_SEO' ) ) {
    class CGS_SEO {

        public static function menu_seo_page() {
            add_submenu_page(
                'city-ghest',
                'سئوی منو',
                'سئوی منو',
                'manage_options',
                'cgs-menu-seo',
                array( __CLASS__, 'render_menu_seo' )
            );
        }
        public static function render_menu_seo() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            $report = get_option( 'cgs_menu_seo_last', array() );
            echo '<div class="wrap" dir="rtl"><h1>سئوی مگامنو / منوساز</h1>';
            echo '<p>آخرین تحلیل از منوساز حرفه‌ای. برای اجرای دوباره: شهر قسط → منوساز → تحلیل سئو.</p>';
            if ( empty( $report ) || ! is_array( $report ) ) {
                echo '<div class="notice notice-info"><p>هنوز تحلیلی ثبت نشده است.</p></div></div>';
                return;
            }
            $score = intval( $report['score'] ?? 0 );
            echo '<p style="font-size:1.4rem;font-weight:800;">امتیاز: ' . $score . ' / 100</p>';
            echo '<p>تعداد آیتم: ' . intval( $report['item_count'] ?? 0 ) . ' — زمان: ' . esc_html( $report['time'] ?? '' ) . '</p>';
            echo '<ul>';
            foreach ( (array) ( $report['issues'] ?? array() ) as $iss ) {
                echo '<li><strong>' . esc_html( $iss['title'] ?? '' ) . '</strong> — ' . esc_html( $iss['fix'] ?? '' ) . '</li>';
            }
            echo '</ul>';
            if ( ! empty( $report['suggestions'] ) ) {
                echo '<h2>پیشنهادها</h2><ul>';
                foreach ( $report['suggestions'] as $sg ) {
                    echo '<li>' . esc_html( $sg ) . '</li>';
                }
                echo '</ul>';
            }
            echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=cgs-menu-builder' ) ) . '">باز کردن منوساز</a></p>';
            echo '</div>';
        }

        public static function init() {
            add_action( 'wp_head', array( __CLASS__, 'output_meta' ), 5 );
            add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ), 20 );
            add_action( 'admin_menu', array( __CLASS__, 'menu_seo_page' ), 80 );
        }
        public static function get_opts() {
            $o = function_exists( 'cgs_get_option' ) ? cgs_get_option( 'seo', array() ) : array();
            return is_array( $o ) ? $o : array();
        }
        public static function output_meta() {
            if ( is_admin() ) return;
            $o = self::get_opts();
            if ( empty( $o['enabled'] ) ) return;
            if ( ! empty( $o['description'] ) ) {
                echo '<meta name="description" content="' . esc_attr( $o['description'] ) . '" />' . "\n";
            }
            if ( ! empty( $o['robots'] ) ) {
                echo '<meta name="robots" content="' . esc_attr( $o['robots'] ) . '" />' . "\n";
            }
        }
        public static function title_parts( $parts ) {
            $o = self::get_opts();
            if ( ! empty( $o['enabled'] ) && ! empty( $o['title_suffix'] ) && ! is_admin() ) {
                $parts['site'] = ( $parts['site'] ?? get_bloginfo( 'name' ) ) . ' ' . $o['title_suffix'];
            }
            return $parts;
        }
    }
}
if ( class_exists( 'CGS_SEO' ) && method_exists( 'CGS_SEO', 'init' ) ) {
    CGS_SEO::init();
}

