<?php
/**
 * Plugin Name:       City Ghest System
 * Plugin URI:        https://www.city-ghest.com
 * Description:       سیستم جامع جذب نماینده، فروشنده، بازاریاب، سرمایه‌گذار و متقاضی اعتبار — معماری ماژولار
 * Version:           4.10.122
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            City Ghest
 * Author URI:        https://www.city-ghest.com
 * License:           GPL v2 or later
 * Text Domain:       city-ghest
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGS_VERSION', '4.10.122' );
define( 'CGS_PLUGIN_FILE', __FILE__ );
define( 'CGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CGS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-like autoload for CGS_* classes in includes/
 */
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'CGS_' ) !== 0 ) {
        return;
    }
    $relative = strtolower( str_replace( '_', '-', substr( $class, 4 ) ) );
    $file     = CGS_PLUGIN_DIR . 'includes/class-' . $relative . '.php';
    if ( is_readable( $file ) ) {
        require_once $file;
    }
} );

register_activation_hook( __FILE__, function () {
    require_once CGS_PLUGIN_DIR . 'includes/helpers.php';
    require_once CGS_PLUGIN_DIR . 'includes/class-activator.php';
    if ( class_exists( 'CGS_Activator' ) ) {
        CGS_Activator::activate();
    }
} );

register_deactivation_hook( __FILE__, function () {
    require_once CGS_PLUGIN_DIR . 'includes/class-deactivator.php';
    if ( class_exists( 'CGS_Deactivator' ) ) {
        CGS_Deactivator::deactivate();
    }
} );

/**
 * Main plugin container — modular, fault-tolerant.
 */
final class City_Ghest_System {

    /** @var self|null */
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_core();
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 1 );
        add_action( 'init', array( $this, 'boot_modules' ), 5 );
    }

    /**
     * Always-safe dependencies (must not throw).
     */
    private function load_core() {
        $files = array(
            'includes/helpers.php',
            'includes/class-cache.php',
            'includes/class-help.php',
            'includes/class-ajax.php',
            'includes/class-modules.php',
            'includes/data/locations.php',
            'includes/data/guilds.php',
        );
        foreach ( $files as $rel ) {
            $path = CGS_PLUGIN_DIR . $rel;
            if ( is_readable( $path ) ) {
                require_once $path;
            }
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'city-ghest', false, dirname( CGS_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Boot every module in isolation.
     */
    public function boot_modules() {
        if ( class_exists( 'CGS_Modules' ) ) {
            CGS_Modules::boot();
        }
        // ماژول مستقل فرم‌ساز — دارایی پیش‌نمایش جدا؛ تغییر سایر بخش‌ها این را نمی‌شکند
        $fb_boot = CGS_PLUGIN_DIR . 'modules/form-builder/bootstrap.php';
        if ( is_readable( $fb_boot ) ) {
            require_once $fb_boot;
        }
        // Menu Builder early boot — hooks + submodules before admin page render
        $mb_boot = CGS_PLUGIN_DIR . 'modules/menu-builder/bootstrap.php';
        if ( is_readable( $mb_boot ) && ! class_exists( 'CGS_Menu_Builder', false ) ) {
            try {
                require_once $mb_boot;
            } catch ( Throwable $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[CGS menu-builder] ' . $e->getMessage() );
                }
            }
        }
        $pl_boot = CGS_PLUGIN_DIR . 'modules/plans/bootstrap.php';
        $st_boot = CGS_PLUGIN_DIR . 'modules/settings/bootstrap.php';
        if ( file_exists( $st_boot ) ) { require_once $st_boot; }
        $ch_boot = CGS_PLUGIN_DIR . 'modules/charts/bootstrap.php';
        $mod_loader = CGS_PLUGIN_DIR . 'modules/loader.php';
        if ( is_readable( $mod_loader ) ) {
            require_once $mod_loader;
            if ( function_exists( 'cgs_load_folder_modules' ) ) {
                cgs_load_folder_modules();
            }
        }
        if ( file_exists( $ch_boot ) ) { require_once $ch_boot; }
        if ( is_readable( $pl_boot ) ) {
            require_once $pl_boot;
        }
    }
}


/**
 * ارتقا نسخه + پاک‌سازی خودکار کش (بدون نیاز به Ctrl+Shift+R)
 */
function cgs_maybe_upgrade() {
    $stored = get_option( 'cgs_db_version', '0' );
    $current = defined( 'CGS_VERSION' ) ? CGS_VERSION : '0';
    if ( (string) $stored === (string) $current ) {
        return;
    }
    if ( class_exists( 'CGS_Activator' ) && method_exists( 'CGS_Activator', 'flush_all_caches' ) ) {
        CGS_Activator::flush_all_caches();
    } else {
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
        update_option( 'cgs_asset_salt', (string) time(), false );
        flush_rewrite_rules( false );
    }
    update_option( 'cgs_db_version', $current, false );
    update_option( 'cgs_asset_salt', (string) time(), false );
}
add_action( 'admin_init', 'cgs_maybe_upgrade', 1 );
add_action( 'init', 'cgs_maybe_upgrade', 1 );


City_Ghest_System::instance();
