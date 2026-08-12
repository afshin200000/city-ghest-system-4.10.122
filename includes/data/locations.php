<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cgs_get_iran_locations() {
    if ( class_exists( 'CGS_Cache' ) ) {
        $hit = CGS_Cache::get( 'iran_locations' );
        if ( is_array( $hit ) ) {
            return $hit;
        }
    }
    static $data = null;
    if ( $data === null ) {
        $file = CGS_PLUGIN_DIR . 'includes/data/iran-locations.json';
        if ( file_exists( $file ) ) {
            $data = json_decode( file_get_contents( $file ), true );
        } else {
            $data = array();
        }
    }
    if ( class_exists( 'CGS_Cache' ) && is_array( $data ) ) {
        CGS_Cache::set( 'iran_locations', $data, HOUR_IN_SECONDS );
    }
    return $data;
}

function cgs_get_provinces_list() {
    return array_keys( cgs_get_iran_locations() );
}

function cgs_get_cities_by_province( $province ) {
    $all = cgs_get_iran_locations();
    if ( ! isset( $all[ $province ]['cities'] ) ) {
        return array();
    }
    $cities = $all[ $province ]['cities'];
    $names = array();
    foreach ( $cities as $c ) {
        if ( is_array( $c ) ) {
            $names[] = $c['name'] ?? '';
        } else {
            $names[] = $c;
        }
    }
    return array_filter( $names );
}

function cgs_get_area_code( $province, $city = '' ) {
    $all = cgs_get_iran_locations();
    if ( ! isset( $all[ $province ] ) ) {
        return '';
    }
    $prov = $all[ $province ];
    $fallback = isset( $prov['code'] ) ? $prov['code'] : '';
    if ( $city && ! empty( $prov['cities'] ) ) {
        foreach ( $prov['cities'] as $c ) {
            if ( is_array( $c ) && isset( $c['name'] ) && $c['name'] === $city ) {
                return ! empty( $c['code'] ) ? $c['code'] : $fallback;
            }
        }
    }
    return $fallback;
}


function cgs_get_city_coords() {
    static $coords = null;
    if ( $coords !== null ) return $coords;
    $file = CGS_PLUGIN_DIR . 'includes/data/iran-city-coords.json';
    if ( file_exists( $file ) ) {
        $coords = json_decode( file_get_contents( $file ), true );
        if ( ! is_array( $coords ) ) $coords = array();
    } else {
        $coords = array();
    }
    return $coords;
}
