<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Iranian-domain validation (pure logic, unit-testable).
 */
class CGS_Validation {

    public static function init() {
        // Pure utility module — no hooks.
    }

    /**
     * Iranian national ID (کد ملی) checksum.
     */
    public static function national_id( $code ) {
        $code = preg_replace( '/\D/', '', (string) $code );
        if ( strlen( $code ) !== 10 ) {
            return false;
        }
        if ( preg_match( '/^(\d)\1{9}$/', $code ) ) {
            return false;
        }
        $sum = 0;
        for ( $i = 0; $i < 9; $i++ ) {
            $sum += (int) $code[ $i ] * ( 10 - $i );
        }
        $r = $sum % 11;
        $check = (int) $code[9];
        return ( $r < 2 && $check === $r ) || ( $r >= 2 && $check === ( 11 - $r ) );
    }

    /**
     * Iranian mobile: 09xxxxxxxxx
     */
    public static function mobile( $phone ) {
        $phone = preg_replace( '/\D/', '', (string) $phone );
        if ( strpos( $phone, '98' ) === 0 && strlen( $phone ) === 12 ) {
            $phone = '0' . substr( $phone, 2 );
        }
        return (bool) preg_match( '/^09\d{9}$/', $phone );
    }

    /**
     * Landline local number (without area code): 5–8 digits
     */
    public static function landline_local( $num ) {
        $num = preg_replace( '/\D/', '', (string) $num );
        return (bool) preg_match( '/^\d{5,8}$/', $num );
    }

    /**
     * Area code: 2–4 digits, often starts with 0
     */
    public static function area_code( $code ) {
        $code = preg_replace( '/\D/', '', (string) $code );
        return (bool) preg_match( '/^0?\d{2,3}$/', $code );
    }

    /**
     * IBAN/Sheba IR + 24 digits (total 26 chars with IR)
     */
    public static function sheba( $sheba ) {
        $s = strtoupper( preg_replace( '/\s+/', '', (string) $sheba ) );
        if ( strpos( $s, 'IR' ) === 0 ) {
            $s = substr( $s, 2 );
        }
        if ( ! preg_match( '/^\d{24}$/', $s ) ) {
            return false;
        }
        // ISO 13616 mod-97
        $rearranged = $s . '1827'; // IR -> 18 27
        // bcmod alternative for pure PHP without bcmath
        $remainder = 0;
        $len = strlen( $rearranged );
        for ( $i = 0; $i < $len; $i++ ) {
            $remainder = ( $remainder * 10 + (int) $rearranged[ $i ] ) % 97;
        }
        return $remainder === 1;
    }

    /**
     * Bank card 16 digits with Luhn
     */
    public static function bank_card( $card ) {
        $card = preg_replace( '/\D/', '', (string) $card );
        if ( strlen( $card ) !== 16 ) {
            return false;
        }
        $sum = 0;
        for ( $i = 0; $i < 16; $i++ ) {
            $d = (int) $card[ $i ];
            if ( $i % 2 === 0 ) {
                $d *= 2;
                if ( $d > 9 ) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }
        return $sum % 10 === 0;
    }

    /**
     * Postal code Iran: 10 digits
     */
    public static function postal_code( $code ) {
        return (bool) preg_match( '/^\d{10}$/', preg_replace( '/\D/', '', (string) $code ) );
    }

    /**
     * Generic maxlength / charset
     */
    public static function charset( $value, $charset, $max_length = 0 ) {
        $value = (string) $value;
        if ( $max_length > 0 && strlen( $value ) > $max_length ) {
            return false;
        }
        if ( $charset === 'numeric' ) {
            return (bool) preg_match( '/^\d*$/', $value );
        }
        if ( $charset === 'alpha' ) {
            return (bool) preg_match( '/^[\x{0600}-\x{06FF}\s a-zA-Z]*$/u', $value );
        }
        return true;
    }
}
