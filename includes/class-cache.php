<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central cache layer — Redis/object-cache first, transient fallback.
 * Isolated: failure here must never break page render.
 */
class CGS_Cache {

    const GROUP = 'cgs_v3';
    const DEFAULT_TTL = 300;

    /**
     * آیا object cache خارجی (مثل Redis) فعال است؟
     */
    public static function has_persistent_object_cache() {
        if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
            return true;
        }
        // نشانه‌های رایج افزونه Redis
        if ( defined( 'WP_REDIS_CLIENT' ) || defined( 'WP_REDIS_HOST' ) || defined( 'WP_CACHE_KEY_SALT' ) ) {
            if ( function_exists( 'wp_cache_get' ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * TTL پیشنهادی: با Redis می‌توان کش طولانی‌تری نگه داشت
     */
    public static function preferred_ttl( $ttl = self::DEFAULT_TTL ) {
        $ttl = abs( (int) $ttl );
        if ( $ttl < 1 ) {
            $ttl = self::DEFAULT_TTL;
        }
        if ( self::has_persistent_object_cache() ) {
            return max( $ttl, 120 );
        }
        return $ttl;
    }

    public static function get( $key, $default = null ) {
        try {
            $found = false;
            $val   = wp_cache_get( $key, self::GROUP, false, $found );
            if ( $found ) {
                return $val;
            }
            // فقط وقتی object cache پایدار نیست از transient (جدول options) استفاده شود
            if ( ! self::has_persistent_object_cache() ) {
                $t = get_transient( 'cgs_c_' . md5( $key ) );
                if ( false !== $t ) {
                    wp_cache_set( $key, $t, self::GROUP, self::DEFAULT_TTL );
                    return $t;
                }
            }
        } catch ( Exception $e ) {
            // ignore
        } catch ( Throwable $e ) {
            // ignore
        }
        return $default;
    }

    public static function set( $key, $value, $ttl = self::DEFAULT_TTL ) {
        try {
            $ttl = self::preferred_ttl( $ttl );
            wp_cache_set( $key, $value, self::GROUP, $ttl );
            // با Redis/Memcached از transient صرف‌نظر می‌کنیم تا کوئری options کم شود
            if ( ! self::has_persistent_object_cache() ) {
                set_transient( 'cgs_c_' . md5( $key ), $value, $ttl );
            }
            return true;
        } catch ( Exception $e ) {
            return false;
        } catch ( Throwable $e ) {
            return false;
        }
    }

    public static function delete( $key ) {
        try {
            wp_cache_delete( $key, self::GROUP );
            if ( ! self::has_persistent_object_cache() ) {
                delete_transient( 'cgs_c_' . md5( $key ) );
            }
        } catch ( Exception $e ) {
            // ignore
        } catch ( Throwable $e ) {
            // ignore
        }
    }

    public static function flush_group_prefix( $prefix = '' ) {
        try {
            global $wpdb;
            if ( isset( $wpdb ) && is_object( $wpdb ) && ! self::has_persistent_object_cache() ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                        $wpdb->esc_like( '_transient_cgs_c_' ) . '%',
                        $wpdb->esc_like( '_transient_timeout_cgs_c_' ) . '%'
                    )
                );
            }
            if ( function_exists( 'wp_cache_flush_group' ) ) {
                wp_cache_flush_group( self::GROUP );
            }
        } catch ( Exception $e ) {
            // ignore
        } catch ( Throwable $e ) {
            // ignore
        }
    }

    public static function remember( $key, $ttl, $callback ) {
        $cached = self::get( $key, null );
        if ( null !== $cached ) {
            return $cached;
        }
        $value = call_user_func( $callback );
        self::set( $key, $value, $ttl );
        return $value;
    }

    /**
     * پاک‌سازی کامل کش افزونه (سازگار با فراخوان‌های قبلی flush_all)
     */
    public static function flush_all() {
        self::flush_group_prefix( '' );
        try {
            if ( function_exists( 'wp_cache_flush_group' ) ) {
                wp_cache_flush_group( self::GROUP );
            }
            // کلیدهای رایج فیلد/شمارش
            $types = array( 'representative', 'seller', 'marketer', 'investor', 'applicant' );
            foreach ( $types as $t ) {
                wp_cache_delete( 'fields_' . $t . '_a', 'cgs' );
                wp_cache_delete( 'fields_' . $t . '_all', 'cgs' );
            }
        } catch ( Exception $e ) {
            // ignore
        } catch ( Throwable $e ) {
            // ignore
        }
        return true;
    }
}
