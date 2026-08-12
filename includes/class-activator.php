<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Activator {

    /**
     * پاک‌سازی کامل کش‌ها — هنگام نصب/فعال‌سازی و ارتقا نسخه
     * تا نیاز به Ctrl+Shift+R نباشد.
     */
    public static function flush_all_caches() {
        // Object cache
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
        // CGS cache
        if ( class_exists( 'CGS_Cache' ) ) {
            if ( method_exists( 'CGS_Cache', 'flush_all' ) ) {
                CGS_Cache::flush_all();
            } elseif ( method_exists( 'CGS_Cache', 'flush_group_prefix' ) ) {
                CGS_Cache::flush_group_prefix( 'cgs' );
            }
        }
        // Transients related to plugin
        global $wpdb;
        if ( isset( $wpdb ) ) {
            $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cgs_%' OR option_name LIKE '_transient_timeout_cgs_%'" );
            $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_cgs_%' OR option_name LIKE '_site_transient_timeout_cgs_%'" );
        }
        // Rewrite
        flush_rewrite_rules( false );
        // Bust browser assets: bump asset salt
        update_option( 'cgs_asset_salt', (string) time(), false );
        // Clear opcache if available
        if ( function_exists( 'opcache_reset' ) ) {
            @opcache_reset();
        }
    }


    public static function activate() {
        if ( ! class_exists( 'CGS_Database' ) ) {
            require_once CGS_PLUGIN_DIR . 'includes/class-database.php';
        }
        CGS_Database::create_tables();

        // Default settings (only fill missing keys)
        $defaults = array(
            'company_name'     => 'شهر قسط',
            'date_calendar'    => 'jalali',
            'primary_color'    => '#1a237e',
            'secondary_color'  => '#f5b942',
            'sound_enabled'    => 1,
            'sound_volume'     => 40,
            'sms_enabled'      => 0,
            'allow_natural_person' => 1,
            'allow_legal_person'   => 1,
        );
        $opts = get_option( 'cgs_settings', array() );
        if ( ! is_array( $opts ) ) {
            $opts = array();
        }
        $opts = array_merge( $defaults, $opts );
        update_option( 'cgs_settings', $opts, false );

        // Pages
        self::ensure_pages();

        // Roles
        if ( class_exists( 'CGS_Roles' ) ) {
            CGS_Roles::create_roles();
        } elseif ( file_exists( CGS_PLUGIN_DIR . 'includes/class-roles.php' ) ) {
            require_once CGS_PLUGIN_DIR . 'includes/class-roles.php';
            if ( class_exists( 'CGS_Roles' ) ) {
                CGS_Roles::create_roles();
            }
        }

        // Seed templates
        if ( class_exists( 'CGS_Form_Templates' ) ) {
            CGS_Form_Templates::ensure_table();
            CGS_Form_Templates::maybe_seed();
        }

        update_option( 'cgs_db_version', defined( 'CGS_VERSION' ) ? CGS_VERSION : '1.0' );
        update_option( 'cgs_show_setup_wizard', 1, false );
        self::flush_all_caches();
    }

    private static function ensure_pages() {
        $pages = array(
            'cgs_page_login'     => array(
                'title'   => 'ورود اعضا — شهر قسط',
                'content' => '[cgs_login]',
                'option'  => 'cgs_page_login_id',
            ),
            'cgs_page_dashboard' => array(
                'title'   => 'داشبورد اعضا — شهر قسط',
                'content' => '[cgs_dashboard]',
                'option'  => 'cgs_page_dashboard_id',
            ),
            'cgs_page_forms'     => array(
                'title'   => 'ثبت‌نام و درخواست — شهر قسط',
                'content' => '[cgs_form type="applicant"]',
                'option'  => 'cgs_page_forms_id',
            ),
        );
        foreach ( $pages as $key => $p ) {
            $existing = (int) get_option( $p['option'], 0 );
            if ( $existing && get_post( $existing ) ) {
                continue;
            }
            $id = wp_insert_post( array(
                'post_title'   => $p['title'],
                'post_content' => $p['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id() ?: 1,
            ) );
            if ( $id && ! is_wp_error( $id ) ) {
                update_option( $p['option'], (int) $id, false );
            }
        }
    }
}
