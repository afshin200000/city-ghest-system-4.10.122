<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Deactivator {

    public static function deactivate() {
        // Clear scheduled events if any
        wp_clear_scheduled_hook( 'cgs_daily_cleanup' );

        // Flush rewrite rules
        flush_rewrite_rules();

        // We intentionally do NOT delete tables or data on deactivation
        // to prevent accidental data loss. Use uninstall.php for full cleanup.
    }
}
