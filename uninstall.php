<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Optional full cleanup - currently commented for safety
// global $wpdb;
// $tables = array( 'cgs_form_fields', 'cgs_applications', 'cgs_application_meta', 'cgs_messages', 'cgs_files' );
// foreach ( $tables as $table ) {
//     $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
// }
// delete_option( 'cgs_settings' );
// delete_option( 'cgs_version' );
