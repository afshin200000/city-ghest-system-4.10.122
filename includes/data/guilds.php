<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cgs_get_guild_list() {
    static $list = null;
    if ( $list === null ) {
        $file = CGS_PLUGIN_DIR . 'includes/data/iran-guilds.json';
        $list = file_exists( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
        if ( ! is_array( $list ) ) $list = array();
    }
    return $list;
}
