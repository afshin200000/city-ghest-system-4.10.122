<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ماژول خودکارسازی احراز هویت و استعلام
 * APIهای رسمی نیاز به قرارداد با ارائه‌دهنده دارند — اینجا اسکلت + حالت آزمایشی
 */
class CGS_Auto_Verify {

    public static function init() {
        add_action( 'wp_ajax_cgs_av_national_id', array( __CLASS__, 'ajax_national_id' ) );
        add_action( 'wp_ajax_nopriv_cgs_av_national_id', array( __CLASS__, 'ajax_national_id' ) );
        add_action( 'wp_ajax_cgs_av_postal', array( __CLASS__, 'ajax_postal' ) );
        add_action( 'wp_ajax_nopriv_cgs_av_postal', array( __CLASS__, 'ajax_postal' ) );
        add_action( 'wp_ajax_cgs_av_mobile', array( __CLASS__, 'ajax_mobile' ) );
        add_action( 'wp_ajax_nopriv_cgs_av_mobile', array( __CLASS__, 'ajax_mobile' ) );
        add_action( 'wp_ajax_cgs_av_sheba', array( __CLASS__, 'ajax_sheba' ) );
        add_action( 'wp_ajax_nopriv_cgs_av_sheba', array( __CLASS__, 'ajax_sheba' ) );
        add_action( 'wp_ajax_cgs_av_credit', array( __CLASS__, 'ajax_credit' ) );
        add_action( 'wp_ajax_nopriv_cgs_av_credit', array( __CLASS__, 'ajax_credit' ) );
    }

    private static function enabled( $key ) {
        return (int) cgs_get_option( 'av_' . $key . '_enabled', 0 ) === 1;
    }

    private static function mode( $key ) {
        return cgs_get_option( 'av_' . $key . '_mode', 'manual' ); // manual | auto | demo
    }

    public static function ajax_national_id() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        if ( ! self::enabled( 'national_id' ) ) {
            wp_send_json_error( array( 'code' => 'disabled', 'message' => 'استعلام کد ملی غیرفعال است.' ) );
        }
        $nid = preg_replace( '/\D/', '', sanitize_text_field( $_POST['national_id'] ?? '' ) );
        if ( strlen( $nid ) !== 10 ) {
            wp_send_json_error( array( 'message' => 'کد ملی نامعتبر است.' ) );
        }
        $mode = self::mode( 'national_id' );
        if ( $mode === 'demo' ) {
            wp_send_json_success( array(
                'demo' => true,
                'data' => array(
                    'first_name'   => 'نمونه',
                    'last_name'    => 'آزمایشی',
                    'father_name'  => 'محمد',
                    'id_number'    => '12345',
                    'birth_place'  => 'تهران',
                    'birth_date'   => '1370/01/15',
                    'id_issue'     => 'تهران',
                ),
                'message' => 'حالت آزمایشی — داده واقعی از ثبت احوال نیست. API واقعی را در تنظیمات وصل کنید.',
            ) );
        }
        // Real API placeholder
        $api_url = cgs_get_option( 'av_national_id_api_url', '' );
        $api_key = cgs_get_option( 'av_national_id_api_key', '' );
        if ( ! $api_url || ! $api_key ) {
            wp_send_json_error( array(
                'code' => 'no_api',
                'message' => 'آدرس و کلید API ثبت احوال در تنظیمات تعریف نشده. برای قرارداد با ارائه‌دهندگان مجاز (مثل شاهکار/ثبت احوال از طریق شرکت‌های دارای مجوز) اقدام کنید.',
            ) );
        }
        wp_send_json_error( array( 'message' => 'اتصال API واقعی پس از دریافت کلید فعال می‌شود.' ) );
    }

    public static function ajax_postal() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        if ( ! self::enabled( 'postal' ) ) {
            wp_send_json_error( array( 'message' => 'استعلام کد پستی غیرفعال است.' ) );
        }
        $pc = preg_replace( '/\D/', '', sanitize_text_field( $_POST['postal_code'] ?? '' ) );
        if ( strlen( $pc ) !== 10 ) {
            wp_send_json_error( array( 'message' => 'کد پستی باید ۱۰ رقم باشد.' ) );
        }
        if ( self::mode( 'postal' ) === 'demo' ) {
            wp_send_json_success( array(
                'demo' => true,
                'data' => array( 'address' => 'استان تهران، خیابان نمونه، پلاک ۱۲ (داده آزمایشی)' ),
                'notice' => cgs_get_option( 'av_postal_notice', 'کد پستی باید با قولنامه/سند مالکیت همخوانی داشته باشد.' ),
            ) );
        }
        wp_send_json_error( array( 'message' => 'API پست کشور را در تنظیمات پیکربندی کنید.' ) );
    }

    public static function ajax_mobile() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        if ( ! self::enabled( 'mobile' ) ) {
            wp_send_json_error( array( 'message' => 'بررسی مالکیت موبایل غیرفعال است.' ) );
        }
        $nid = preg_replace( '/\D/', '', sanitize_text_field( $_POST['national_id'] ?? '' ) );
        $mobile = preg_replace( '/\D/', '', sanitize_text_field( $_POST['mobile'] ?? '' ) );
        if ( self::mode( 'mobile' ) === 'demo' ) {
            // Demo: accept if mobile starts with 09 and nid length 10
            $ok = ( strlen( $nid ) === 10 && preg_match( '/^09\d{9}$/', $mobile ) );
            if ( $ok ) {
                wp_send_json_success( array( 'matched' => true, 'message' => 'تأیید آزمایشی مالکیت (حالت دمو).' ) );
            }
            wp_send_json_error( array( 'matched' => false, 'message' => cgs_get_option( 'av_mobile_mismatch_msg', 'شماره موبایل با کد ملی همخوانی ندارد. اصلاح کنید.' ) ) );
        }
        wp_send_json_error( array( 'message' => 'API شاهکار/اپراتور را پیکربندی کنید.' ) );
    }

    public static function ajax_sheba() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        if ( ! self::enabled( 'sheba' ) ) {
            wp_send_json_error( array( 'message' => 'استعلام شبا غیرفعال است.' ) );
        }
        $max_checks = (int) cgs_get_option( 'av_sheba_max_pending_checks', 3 );
        $max_amount = (int) cgs_get_option( 'av_sheba_max_pending_amount', 500000000 );
        $on_exceed  = cgs_get_option( 'av_sheba_on_exceed', 'admin' ); // auto_reject | admin
        if ( self::mode( 'sheba' ) === 'demo' ) {
            wp_send_json_success( array(
                'demo' => true,
                'owner_match' => true,
                'pending_checks' => 0,
                'pending_amount' => 0,
                'limits' => array( 'max_checks' => $max_checks, 'max_amount' => $max_amount ),
                'on_exceed' => $on_exceed,
                'message' => 'استعلام آزمایشی — حساب سالم فرض شد.',
            ) );
        }
        wp_send_json_error( array( 'message' => 'API بانکی/چک را پیکربندی کنید.' ) );
    }

    public static function ajax_credit() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        if ( ! self::enabled( 'credit' ) ) {
            wp_send_json_error( array( 'message' => 'اعتبارسنجی غیرفعال است.' ) );
        }
        $fee = (int) cgs_get_option( 'av_credit_fee', 0 );
        if ( self::mode( 'credit' ) === 'demo' ) {
            wp_send_json_success( array(
                'demo' => true,
                'score' => 'B',
                'has_overdue' => false,
                'fee' => $fee,
                'message' => 'رتبه آزمایشی B — اتصال واقعی به etebarito.nics24.ir پس از قرارداد.',
            ) );
        }
        wp_send_json_error( array( 'message' => 'درگاه پرداخت و API اعتباریتو را در تنظیمات کامل کنید.' ) );
    }
}
