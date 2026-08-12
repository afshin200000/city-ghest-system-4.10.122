<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lightweight Jalali (Persian) date functions
 * Based on common algorithms - no external dependency
 */

function cgs_gregorian_to_jalali( $gy, $gm, $gd ) {
    $g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
    $gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
    $days  = 355666 + ( 365 * $gy ) + (int)( ( $gy2 + 3 ) / 4 ) - (int)( ( $gy2 + 99 ) / 100 ) + (int)( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
    $jy    = -1595 + ( 33 * (int)( $days / 12053 ) );
    $days %= 12053;
    $jy   += 4 * (int)( $days / 1461 );
    $days %= 1461;
    if ( $days > 365 ) {
        $jy   += (int)( ( $days - 1 ) / 365 );
        $days  = ( $days - 1 ) % 365;
    }
    if ( $days < 186 ) {
        $jm = 1 + (int)( $days / 31 );
        $jd = 1 + ( $days % 31 );
    } else {
        $jm = 7 + (int)( ( $days - 186 ) / 30 );
        $jd = 1 + ( ( $days - 186 ) % 30 );
    }
    return array( $jy, $jm, $jd );
}

function cgs_jdate( $format, $timestamp = null, $timezone = null ) {
    if ( null === $timestamp ) {
        $timestamp = current_time( 'timestamp' );
    }

    $date = getdate( $timestamp );
    list( $jy, $jm, $jd ) = cgs_gregorian_to_jalali( $date['year'], $date['mon'], $date['mday'] );

    $months = array(
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    );

    $replacements = array(
        'Y' => $jy,
        'y' => substr( $jy, -2 ),
        'm' => sprintf( '%02d', $jm ),
        'n' => $jm,
        'd' => sprintf( '%02d', $jd ),
        'j' => $jd,
        'F' => $months[ $jm ],
        'H' => sprintf( '%02d', $date['hours'] ),
        'i' => sprintf( '%02d', $date['minutes'] ),
        's' => sprintf( '%02d', $date['seconds'] ),
    );

    $result = $format;
    foreach ( $replacements as $key => $value ) {
        $result = str_replace( $key, $value, $result );
    }

    return $result;
}
