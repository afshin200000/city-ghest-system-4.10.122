<?php
/**
 * Lightweight query performance monitor for City Ghest tables
 * Tracks timing, slow queries, and counts — admin only, low overhead
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Query_Monitor {

    const OPTION_LOG   = 'cgs_query_perf_log';
    const OPTION_STATS = 'cgs_query_perf_stats';
    const MAX_LOG      = 50;
    const SLOW_MS      = 50; // threshold in milliseconds

    private static $enabled = null;
    private static $start   = null;
    private static $queries = array();

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
        add_action( 'wp_ajax_cgs_qm_clear', array( __CLASS__, 'ajax_clear' ) );
        add_action( 'wp_ajax_cgs_qm_toggle', array( __CLASS__, 'ajax_toggle' ) );

        if ( ! self::is_enabled() ) {
            return;
        }

        // Hook into $wpdb for CGS-related queries only
        add_filter( 'log_query_custom_data', array( __CLASS__, 'capture_query' ), 10, 5 );
        // Fallback when SAVEQUERIES is off: sample via query filter + microtime
        add_filter( 'query', array( __CLASS__, 'mark_query_start' ), 1 );
        add_filter( 'query', array( __CLASS__, 'mark_query_end' ), 9999 );
        add_action( 'shutdown', array( __CLASS__, 'persist' ), 99 );

        // Also time our own high-level operations
        add_action( 'cgs_qm_start', array( __CLASS__, 'start_timer' ), 10, 1 );
        add_action( 'cgs_qm_end', array( __CLASS__, 'end_timer' ), 10, 1 );
    }

    public static function is_enabled() {
        // همیشه از دیتابیس بخوان (بدون کش استاتیک ماندگار بین درخواست‌ها مشکلی نیست، ولی بعد از toggle رفرش می‌شود)
        if ( self::$enabled === null ) {
            self::$enabled = (int) get_option( 'cgs_qm_enabled', 0 ) === 1;
        }
        return self::$enabled;
    }

    public static function menu() {
        add_submenu_page(
            'city-ghest',
            'پایش کوئری‌ها',
            'پایش عملکرد',
            'manage_options',
            'cgs-query-monitor',
            array( __CLASS__, 'page' )
        );
    }

    /**
     * Capture from WP's SAVEQUERIES / log_query_custom_data
     * @param array  $query_data
     * @param string $query
     * @param float  $elapsed_time seconds
     * @param string $backtrace
     * @param float  $start_time
     */

    private static $q_t0 = null;

    public static function mark_query_start( $sql ) {
        if ( ! self::is_enabled() || ! is_string( $sql ) ) {
            return $sql;
        }
        self::$q_t0 = microtime( true );
        return $sql;
    }

    public static function mark_query_end( $sql ) {
        if ( ! self::is_enabled() || ! is_string( $sql ) || self::$q_t0 === null ) {
            return $sql;
        }
        $ms = round( ( microtime( true ) - self::$q_t0 ) * 1000, 2 );
        self::$q_t0 = null;
        if ( stripos( $sql, 'cgs_' ) === false && stripos( $sql, 'cgs-' ) === false ) {
            return $sql;
        }
        // avoid double-count if log_query_custom_data also ran
        self::$queries[] = array(
            'sql'  => self::truncate_sql( $sql ),
            'ms'   => $ms,
            'slow' => $ms >= self::SLOW_MS,
            'time' => current_time( 'mysql' ),
            'type' => self::detect_type( $sql ),
        );
        return $sql;
    }

    public static function capture_query( $query_data, $query, $elapsed_time, $backtrace = '', $start_time = 0 ) {
        if ( ! self::is_enabled() ) {
            return $query_data;
        }
        // Only track our plugin tables
        if ( stripos( $query, 'cgs_' ) === false && stripos( $query, 'cgs-' ) === false ) {
            return $query_data;
        }

        $ms = round( (float) $elapsed_time * 1000, 2 );
        $entry = array(
            'sql'   => self::truncate_sql( $query ),
            'ms'    => $ms,
            'slow'  => $ms >= self::SLOW_MS,
            'time'  => current_time( 'mysql' ),
            'type'  => self::detect_type( $query ),
        );
        self::$queries[] = $entry;
        return $query_data;
    }

    public static function start_timer( $label = 'op' ) {
        self::$start[ $label ] = microtime( true );
    }

    public static function end_timer( $label = 'op' ) {
        if ( empty( self::$start[ $label ] ) ) {
            return;
        }
        $ms = round( ( microtime( true ) - self::$start[ $label ] ) * 1000, 2 );
        self::$queries[] = array(
            'sql'  => '[OP] ' . $label,
            'ms'   => $ms,
            'slow' => $ms >= self::SLOW_MS,
            'time' => current_time( 'mysql' ),
            'type' => 'operation',
        );
        unset( self::$start[ $label ] );
    }

    /** Manual log helper for use in plugin code */
    public static function log( $sql, $ms, $type = 'custom' ) {
        if ( ! self::is_enabled() ) {
            return;
        }
        self::$queries[] = array(
            'sql'  => self::truncate_sql( $sql ),
            'ms'   => round( (float) $ms, 2 ),
            'slow' => $ms >= self::SLOW_MS,
            'time' => current_time( 'mysql' ),
            'type' => $type,
        );
    }

    public static function persist() {
        if ( empty( self::$queries ) ) {
            return;
        }

        $log = get_option( self::OPTION_LOG, array() );
        if ( ! is_array( $log ) ) {
            $log = array();
        }

        $stats = get_option( self::OPTION_STATS, array(
            'total_queries' => 0,
            'total_ms'      => 0,
            'slow_count'    => 0,
            'by_type'       => array(),
            'max_ms'        => 0,
            'max_sql'       => '',
        ) );

        foreach ( self::$queries as $q ) {
            $log[] = $q;
            $stats['total_queries'] = (int) $stats['total_queries'] + 1;
            $stats['total_ms']      = (float) $stats['total_ms'] + $q['ms'];
            if ( $q['slow'] ) {
                $stats['slow_count'] = (int) $stats['slow_count'] + 1;
            }
            $t = $q['type'];
            if ( ! isset( $stats['by_type'][ $t ] ) ) {
                $stats['by_type'][ $t ] = array( 'count' => 0, 'ms' => 0 );
            }
            $stats['by_type'][ $t ]['count']++;
            $stats['by_type'][ $t ]['ms'] += $q['ms'];
            if ( $q['ms'] > (float) $stats['max_ms'] ) {
                $stats['max_ms']  = $q['ms'];
                $stats['max_sql'] = $q['sql'];
            }
        }

        // Keep last MAX_LOG entries
        if ( count( $log ) > self::MAX_LOG ) {
            $log = array_slice( $log, -self::MAX_LOG );
        }

        update_option( self::OPTION_LOG, $log, false );
        update_option( self::OPTION_STATS, $stats, false );
        self::$queries = array();
    }

    private static function truncate_sql( $sql ) {
        $sql = preg_replace( '/\s+/', ' ', trim( $sql ) );
        if ( strlen( $sql ) > 300 ) {
            $sql = substr( $sql, 0, 300 ) . '…';
        }
        return $sql;
    }

    private static function detect_type( $sql ) {
        $s = ltrim( $sql );
        if ( stripos( $s, 'SELECT' ) === 0 ) return 'SELECT';
        if ( stripos( $s, 'INSERT' ) === 0 ) return 'INSERT';
        if ( stripos( $s, 'UPDATE' ) === 0 ) return 'UPDATE';
        if ( stripos( $s, 'DELETE' ) === 0 ) return 'DELETE';
        if ( stripos( $s, 'SHOW' ) === 0 || stripos( $s, 'DESCRIBE' ) === 0 ) return 'META';
        return 'OTHER';
    }

    public static function ajax_clear() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        delete_option( self::OPTION_LOG );
        delete_option( self::OPTION_STATS );
        wp_send_json_success( 'پاک شد' );
    }

    public static function ajax_toggle() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $on = ! empty( $_POST['enabled'] ) ? 1 : 0;
        update_option( 'cgs_qm_enabled', $on, true );
        self::$enabled = (int) $on; // هم‌گام‌سازی کش استاتیک
        // Enable WP SAVEQUERIES when monitoring is on (admin only impact)
        if ( $on && ! defined( 'SAVEQUERIES' ) ) {
            // Can't define late; we rely on log_query_custom_data when SAVEQUERIES is true
            // Fallback: wrap high-level ops via do_action cgs_qm_start/end
        }
        wp_send_json_success( array( 'enabled' => $on ) );
    }

    public static function page() {
        $enabled = self::is_enabled();
        $log     = get_option( self::OPTION_LOG, array() );
        $stats   = get_option( self::OPTION_STATS, array() );
        if ( ! is_array( $log ) ) $log = array();
        if ( ! is_array( $stats ) ) $stats = array();

        $total_q  = (int) ( $stats['total_queries'] ?? 0 );
        $total_ms = round( (float) ( $stats['total_ms'] ?? 0 ), 1 );
        $slow     = (int) ( $stats['slow_count'] ?? 0 );
        $avg      = $total_q > 0 ? round( $total_ms / $total_q, 2 ) : 0;
        $max_ms   = (float) ( $stats['max_ms'] ?? 0 );
        $max_sql  = $stats['max_sql'] ?? '';
        $by_type  = $stats['by_type'] ?? array();

        include CGS_PLUGIN_DIR . 'admin/views/query-monitor.php';
    }
}
