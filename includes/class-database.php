<?php
/**
 * Database layer — optimized indexes, composite keys, object-cache friendly reads
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Database {

    const CACHE_GROUP = 'cgs';
    const SCHEMA_VER  = '1.3.0';

    public static function init() {
        // Auto-migrate indexes when version changes
        $ver = get_option( 'cgs_db_schema_ver', '' );
        if ( $ver !== self::SCHEMA_VER ) {
            self::create_tables();
            self::ensure_indexes();
            update_option( 'cgs_db_schema_ver', self::SCHEMA_VER, false );
        }
    }

    public static function get_table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'cgs_' . $name;
    }

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'cgs_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Form Fields — composite unique + covering indexes
        $sql_fields = "CREATE TABLE {$prefix}form_fields (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type_key varchar(50) NOT NULL,
            field_key varchar(100) NOT NULL,
            label varchar(255) NOT NULL,
            field_type varchar(50) NOT NULL DEFAULT 'text',
            placeholder varchar(255) DEFAULT '',
            options longtext DEFAULT NULL,
            validation longtext DEFAULT NULL,
            is_required tinyint(1) NOT NULL DEFAULT 0,
            step_number tinyint(3) unsigned NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            css_class varchar(100) DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            version_num int(11) NOT NULL DEFAULT 1,
            parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
            notes varchar(255) DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY type_field (type_key, field_key),
            KEY type_active_sort (type_key, is_active, sort_order),
            KEY type_step (type_key, step_number, sort_order)
        ) $charset;";

        // 2. Applications — composite indexes for dashboard filters
        $sql_apps = "CREATE TABLE {$prefix}applications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(30) NOT NULL,
            type_key varchar(50) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            mobile varchar(20) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            full_name varchar(150) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            province varchar(100) DEFAULT NULL,
            admin_note longtext DEFAULT NULL,
            reviewed_by bigint(20) unsigned DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY type_status_date (type_key, status, created_at),
            KEY status_date (status, created_at),
            KEY user_id (user_id),
            KEY mobile (mobile),
            KEY created_at (created_at)
        ) $charset;";

        // 3. Application Meta — unique per app+key for fast upsert
        $sql_meta = "CREATE TABLE {$prefix}application_meta (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            meta_key varchar(100) NOT NULL,
            meta_value longtext DEFAULT NULL,
            PRIMARY KEY (meta_id),
            UNIQUE KEY app_key (application_id, meta_key),
            KEY meta_key (meta_key(50))
        ) $charset;";

        // 4. Chat Messages
        $sql_chat = "CREATE TABLE {$prefix}messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            sender_id bigint(20) unsigned NOT NULL,
            sender_type varchar(20) NOT NULL DEFAULT 'member',
            message longtext NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY app_read_date (application_id, is_read, created_at),
            KEY application_id (application_id)
        ) $charset;";

        // 5. Files
        $sql_files = "CREATE TABLE {$prefix}files (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            field_key varchar(100) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(50) DEFAULT NULL,
            file_size int(11) DEFAULT NULL,
            uploaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY app_field (application_id, field_key)
        ) $charset;";

        // 6. CRM Contacts
        $sql_crm = "CREATE TABLE {$prefix}crm_contacts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            mobile varchar(20) NOT NULL DEFAULT '',
            email varchar(191) NOT NULL DEFAULT '',
            type varchar(50) NOT NULL DEFAULT 'applicant',
            stage varchar(50) NOT NULL DEFAULT 'lead',
            notes text,
            app_id bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY stage_updated (stage, updated_at),
            KEY mobile (mobile),
            KEY type_stage (type, stage),
            KEY app_id (app_id)
        ) $charset;";

        // 7. CRM Activities
        $sql_crm_act = "CREATE TABLE {$prefix}crm_activities (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL DEFAULT 'note',
            content text,
            user_id bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY contact_date (contact_id, created_at)
        ) $charset;";

        
        $sql_tpl = "CREATE TABLE {$prefix}form_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            type_key varchar(50) NOT NULL DEFAULT 'representative',
            source varchar(20) NOT NULL DEFAULT 'custom',
            fields_json longtext NULL,
            styles_json longtext NULL,
            step_meta_json longtext NULL,
            columns_default tinyint(3) unsigned NOT NULL DEFAULT 2,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            version_num int(11) NOT NULL DEFAULT 1,
            parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
            notes varchar(255) DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type_key (type_key),
            KEY source (source)
        ) $charset;";


        // ── جداول نقش‌محور (سرمایه‌گذار / متقاضی / نماینده / ضامن / تامین‌کننده) ──
        $sql_parties = "CREATE TABLE {$prefix}parties (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            role varchar(32) NOT NULL COMMENT 'investor|applicant|agent|guarantor|supplier|shop',
            user_id bigint(20) unsigned DEFAULT NULL,
            national_id varchar(20) DEFAULT NULL,
            full_name varchar(150) NOT NULL DEFAULT '',
            mobile varchar(20) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            meta_json longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY role_status (role, status),
            KEY user_id (user_id),
            KEY national_id (national_id),
            KEY mobile (mobile)
        ) $charset;";

        $sql_party_links = "CREATE TABLE {$prefix}party_links (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parent_party_id bigint(20) unsigned NOT NULL COMMENT 'مثلاً متقاضی',
            child_party_id bigint(20) unsigned NOT NULL COMMENT 'مثلاً ضامن یا نماینده',
            link_type varchar(32) NOT NULL DEFAULT 'guarantor' COMMENT 'guarantor|agent|investor|supplier',
            application_id bigint(20) unsigned DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_type (parent_party_id, link_type),
            KEY child_id (child_party_id),
            KEY application_id (application_id)
        ) $charset;";

        $sql_credits = "CREATE TABLE {$prefix}credit_allocations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            applicant_party_id bigint(20) unsigned DEFAULT NULL,
            investor_party_id bigint(20) unsigned DEFAULT NULL,
            supplier_party_id bigint(20) unsigned DEFAULT NULL,
            plan_id varchar(50) NOT NULL DEFAULT '',
            principal decimal(18,0) NOT NULL DEFAULT 0,
            credit_amount decimal(18,0) NOT NULL DEFAULT 0,
            principal_interest decimal(18,0) NOT NULL DEFAULT 0,
            monthly_installment decimal(18,0) NOT NULL DEFAULT 0,
            months smallint unsigned NOT NULL DEFAULT 0,
            step_months smallint unsigned NOT NULL DEFAULT 1,
            status varchar(20) NOT NULL DEFAULT 'draft',
            calc_json longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY applicant (applicant_party_id),
            KEY investor (investor_party_id),
            KEY plan_status (plan_id, status)
        ) $charset;";

        $sql_installments = "CREATE TABLE {$prefix}installment_schedule (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            allocation_id bigint(20) unsigned NOT NULL,
            installment_no smallint unsigned NOT NULL,
            due_date date DEFAULT NULL,
            amount decimal(18,0) NOT NULL DEFAULT 0,
            paid_amount decimal(18,0) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY alloc_no (allocation_id, installment_no),
            KEY status_due (status, due_date)
        ) $charset;";

        $sql_guarantees = "CREATE TABLE {$prefix}guarantee_instruments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            allocation_id bigint(20) unsigned NOT NULL,
            party_id bigint(20) unsigned DEFAULT NULL COMMENT 'صاحب چک: متقاضی یا ضامن',
            instrument_type varchar(32) NOT NULL DEFAULT 'guarantee_check' COMMENT 'guarantee_check|installment_check',
            owner_role varchar(20) NOT NULL DEFAULT 'applicant' COMMENT 'applicant|guarantor',
            amount decimal(18,0) NOT NULL DEFAULT 0,
            pct_of_pi decimal(8,4) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'required',
            meta_json longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY allocation_id (allocation_id),
            KEY party_id (party_id),
            KEY type_status (instrument_type, status)
        ) $charset;";


        dbDelta( $sql_parties );
        dbDelta( $sql_party_links );
        dbDelta( $sql_credits );
        dbDelta( $sql_installments );
        dbDelta( $sql_guarantees );
        dbDelta( $sql_fields );
        dbDelta( $sql_tpl );
        dbDelta( $sql_apps );
        dbDelta( $sql_meta );
        dbDelta( $sql_chat );
        dbDelta( $sql_files );
        dbDelta( $sql_crm );
        dbDelta( $sql_crm_act );

        self::seed_default_fields();
        self::ensure_indexes();
    }

    /**
     * Add missing indexes on existing installs (safe IF NOT EXISTS via try)
     */
    public static function ensure_indexes() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'cgs_';

        $indexes = array(
            "{$prefix}form_fields" => array(
                'type_field'         => 'UNIQUE KEY type_field (type_key, field_key)',
                'type_active_sort'   => 'KEY type_active_sort (type_key, is_active, sort_order)',
                'type_step'          => 'KEY type_step (type_key, step_number, sort_order)',
            ),
            "{$prefix}applications" => array(
                'type_status_date' => 'KEY type_status_date (type_key, status, created_at)',
                'status_date'      => 'KEY status_date (status, created_at)',
                'created_at'       => 'KEY created_at (created_at)',
            ),
            "{$prefix}application_meta" => array(
                'app_key' => 'UNIQUE KEY app_key (application_id, meta_key)',
            ),
            "{$prefix}messages" => array(
                'app_read_date' => 'KEY app_read_date (application_id, is_read, created_at)',
            ),
            "{$prefix}files" => array(
                'app_field' => 'KEY app_field (application_id, field_key)',
            ),
            "{$prefix}parties" => array(
                'role_status' => 'KEY role_status (role, status)',
                'national_id' => 'KEY national_id (national_id)',
            ),
            "{$prefix}party_links" => array(
                'parent_type' => 'KEY parent_type (parent_party_id, link_type)',
            ),
            "{$prefix}credit_allocations" => array(
                'plan_status' => 'KEY plan_status (plan_id, status)',
                'application_id' => 'KEY application_id (application_id)',
            ),
            "{$prefix}installment_schedule" => array(
                'status_due' => 'KEY status_due (status, due_date)',
            ),
            "{$prefix}guarantee_instruments" => array(
                'type_status' => 'KEY type_status (instrument_type, status)',
            ),
            "{$prefix}crm_contacts" => array(
                'stage_updated' => 'KEY stage_updated (stage, updated_at)',
                'type_stage'    => 'KEY type_stage (type, stage)',
            ),
            "{$prefix}crm_activities" => array(
                'contact_date' => 'KEY contact_date (contact_id, created_at)',
            ),
        );

        foreach ( $indexes as $table => $defs ) {
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
            if ( ! $exists ) {
                continue;
            }
            $existing = $wpdb->get_results( "SHOW INDEX FROM `$table`", ARRAY_A );
            $names = array();
            if ( is_array( $existing ) ) {
                foreach ( $existing as $row ) {
                    $names[ $row['Key_name'] ] = true;
                }
            }
            foreach ( $defs as $name => $ddl ) {
                if ( isset( $names[ $name ] ) ) {
                    continue;
                }
                // Suppress duplicate-key errors on concurrent runs
                $wpdb->query( "ALTER TABLE `$table` ADD $ddl" );
            }
        }
    }

    // ─── Cached readers ───────────────────────────────────────────

    public static function get_fields( $type_key, $active_only = true ) {
        $type_key  = sanitize_key( $type_key );
        $cache_key = 'fields_' . $type_key . ( $active_only ? '_a' : '_all' );
        // 1) object cache
        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached && is_array( $cached ) ) {
            if ( class_exists( 'CGS_Query_Monitor' ) ) {
                CGS_Query_Monitor::log( 'CACHE HIT form_fields type=' . $type_key, 0.01, 'CACHE' );
            }
            return $cached;
        }
        // 2) لایه CGS_Cache (transient)
        if ( class_exists( 'CGS_Cache' ) ) {
            $cached = CGS_Cache::get( $cache_key, null );
            if ( is_array( $cached ) ) {
                wp_cache_set( $cache_key, $cached, self::CACHE_GROUP, 300 );
                if ( class_exists( 'CGS_Query_Monitor' ) ) {
                    CGS_Query_Monitor::log( 'CACHE HIT form_fields (CGS) type=' . $type_key, 0.01, 'CACHE' );
                }
                return $cached;
            }
        }

        $t0 = microtime( true );
        global $wpdb;
        $table = self::get_table( 'form_fields' );
        if ( $active_only ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE type_key = %s AND is_active = 1 ORDER BY step_number ASC, sort_order ASC, id ASC",
                    $type_key
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE type_key = %s ORDER BY step_number ASC, sort_order ASC, id ASC",
                    $type_key
                ),
                ARRAY_A
            );
        }
        if ( ! is_array( $rows ) ) {
            $rows = array();
        }
        wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, 300 );
        if ( class_exists( 'CGS_Cache' ) ) {
            CGS_Cache::set( $cache_key, $rows, 300 );
        }
        if ( class_exists( 'CGS_Query_Monitor' ) ) {
            CGS_Query_Monitor::log( 'SELECT form_fields type=' . $type_key, ( microtime( true ) - $t0 ) * 1000, 'SELECT' );
        }
        return $rows;
    }

    public static function invalidate_fields_cache( $type_key = '' ) {
        $types = array( 'representative', 'seller', 'marketer', 'investor', 'applicant' );
        if ( $type_key ) {
            $types[] = sanitize_key( $type_key );
            $types = array_unique( $types );
        }
        foreach ( $types as $t ) {
            foreach ( array( '_a', '_all' ) as $suf ) {
                $k = 'fields_' . $t . $suf;
                wp_cache_delete( $k, self::CACHE_GROUP );
                if ( class_exists( 'CGS_Cache' ) ) {
                    CGS_Cache::delete( $k );
                }
            }
        }
    }

    public static function count_applications( $args = array() ) {
        $status = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
        $type   = isset( $args['type_key'] ) ? sanitize_key( $args['type_key'] ) : ( isset( $args['type'] ) ? sanitize_key( $args['type'] ) : '' );
        $cache_key = 'app_count_' . $status . '_' . $type;

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            if ( class_exists( 'CGS_Query_Monitor' ) ) {
                CGS_Query_Monitor::log( 'CACHE HIT app_count ' . $cache_key, 0.01, 'CACHE' );
            }
            return (int) $cached;
        }
        if ( class_exists( 'CGS_Cache' ) ) {
            $c2 = CGS_Cache::get( $cache_key, null );
            if ( null !== $c2 && false !== $c2 ) {
                wp_cache_set( $cache_key, (int) $c2, self::CACHE_GROUP, 60 );
                return (int) $c2;
            }
        }

        $t0 = microtime( true );
        global $wpdb;
        $table = self::get_table( 'applications' );

        $where = array( '1=1' );
        $params = array();
        if ( $status ) {
            $where[]  = 'status = %s';
            $params[] = $status;
        }
        if ( $type ) {
            $where[]  = 'type_key = %s';
            $params[] = $type;
        }
        // COUNT با ایندکس type_status_date / status_date — بدون SHOW TABLES اضافه
        $sql = 'SELECT COUNT(*) FROM `' . str_replace( '`', '', $table ) . '` WHERE ' . implode( ' AND ', $where );
        if ( $params ) {
            $count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
        } else {
            $count = (int) $wpdb->get_var( $sql );
        }
        // جدول نباشد → get_var خطا/null → 0
        if ( $wpdb->last_error && false !== stripos( $wpdb->last_error, "doesn't exist" ) ) {
            $count = 0;
        }

        wp_cache_set( $cache_key, $count, self::CACHE_GROUP, 60 );
        if ( class_exists( 'CGS_Cache' ) ) {
            CGS_Cache::set( $cache_key, $count, 60 );
        }
        if ( class_exists( 'CGS_Query_Monitor' ) ) {
            CGS_Query_Monitor::log( 'COUNT applications ' . $cache_key, ( microtime( true ) - $t0 ) * 1000, 'SELECT' );
        }
        return $count;
    }

    public static function invalidate_app_counts() {
        $statuses = array( '', 'pending', 'review', 'approved', 'rejected' );
        $types = array( '', 'representative', 'seller', 'marketer', 'investor', 'applicant' );
        foreach ( $statuses as $s ) {
            foreach ( $types as $t ) {
                $k = 'app_count_' . $s . '_' . $t;
                wp_cache_delete( $k, self::CACHE_GROUP );
                if ( class_exists( 'CGS_Cache' ) ) {
                    CGS_Cache::delete( $k );
                }
            }
        }
    }

    /**
     * Fast meta upsert (uses UNIQUE app_key)
     */
    public static function update_meta( $application_id, $meta_key, $meta_value ) {
        global $wpdb;
        $table = self::get_table( 'application_meta' );
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table (application_id, meta_key, meta_value) VALUES (%d, %s, %s)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
                $application_id,
                $meta_key,
                is_array( $meta_value ) || is_object( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value
            )
        );
    }

    /**
     * OPTIMIZE TABLE for all cgs_* tables (admin tool)
     */
    public static function optimize_tables() {
        global $wpdb;
        // فقط جداول افزونه — بدون تغییر ساختار
        $tables = array( 'form_fields', 'applications', 'application_meta', 'messages', 'files', 'crm_contacts', 'crm_activities', 'form_templates' );
        $results = array();
        foreach ( $tables as $name ) {
            $full = self::get_table( $name );
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) );
            if ( $exists ) {
                // OPTIMIZE + ANALYZE برای آمار کوئری‌پلنر — بدون ALTER
                $wpdb->query( "OPTIMIZE TABLE `{$full}`" );
                $wpdb->query( "ANALYZE TABLE `{$full}`" );
                $results[ $name ] = 'optimized';
            }
        }
        if ( method_exists( __CLASS__, 'ensure_indexes' ) ) {
            self::ensure_indexes();
        }
        if ( method_exists( __CLASS__, 'invalidate_fields_cache' ) ) {
            self::invalidate_fields_cache();
        }
        if ( method_exists( __CLASS__, 'invalidate_app_counts' ) ) {
            self::invalidate_app_counts();
        }
        return $results;
    }

    private static function seed_default_fields() {
        global $wpdb;
        $table = $wpdb->prefix . 'cgs_form_fields';
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        if ( $count > 0 ) {
            return;
        }
        // Minimal seed — form builder can add the rest
        $defaults = array(
            array( 'applicant', 'full_name', 'نام و نام خانوادگی', 'text', 1, 1 ),
            array( 'applicant', 'mobile', 'شماره موبایل', 'tel', 1, 2 ),
            array( 'applicant', 'national_id', 'کد ملی', 'text', 1, 3 ),
            array( 'applicant', 'province', 'استان', 'select', 1, 4 ),
            array( 'applicant', 'city', 'شهر', 'select', 1, 5 ),
        );
        $i = 0;
        foreach ( $defaults as $d ) {
            $wpdb->insert( $table, array(
                'type_key'    => $d[0],
                'field_key'   => $d[1],
                'label'       => $d[2],
                'field_type'  => $d[3],
                'is_required' => 1,
                'step_number' => $d[4],
                'sort_order'  => $d[5],
                'is_active'   => 1,
            ) );
            $i++;
        }
    }
}
