<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * درگاه‌های پرداخت بانکی و واسطه ایرانی
 *
 * واسطه: زرین‌پال، آیدی‌پی، نکست‌پی، پی‌آیر، زیبال، پی‌پینگ
 * بانکی: ملت، سامان، ملی (سداد)، پارسیان، پاسارگاد
 */
class CGS_Payment {

    const OPTION_KEY = 'cgs_payment';

    public static function init() {
        add_action( 'wp_ajax_cgs_pay_request', array( __CLASS__, 'ajax_request' ) );
        add_action( 'wp_ajax_nopriv_cgs_pay_request', array( __CLASS__, 'ajax_request' ) );
        add_action( 'wp_ajax_cgs_pay_verify', array( __CLASS__, 'ajax_verify' ) );
        add_action( 'wp_ajax_nopriv_cgs_pay_verify', array( __CLASS__, 'ajax_verify' ) );
        add_action( 'init', array( __CLASS__, 'handle_callback' ) );
    }

    
    public static function log_debug( $message, $context = array() ) {
        $logs = get_option( 'cgs_payment_debug_log', array() );
        if ( ! is_array( $logs ) ) {
            $logs = array();
        }
        array_unshift( $logs, array(
            'time'    => current_time( 'mysql' ),
            'message' => is_string( $message ) ? $message : wp_json_encode( $message ),
            'context' => $context,
        ) );
        $logs = array_slice( $logs, 0, 50 );
        update_option( 'cgs_payment_debug_log', $logs, false );
    }

    public static function get_debug_log() {
        $logs = get_option( 'cgs_payment_debug_log', array() );
        return is_array( $logs ) ? $logs : array();
    }

    public static function gateways_list() {
        return array(
            'none'      => array( 'label' => '— بدون درگاه —', 'type' => 'none' ),
            'zarinpal'  => array( 'label' => 'زرین‌پال (ZarinPal)', 'type' => 'aggregator', 'sandbox' => true ),
            'idpay'     => array( 'label' => 'آیدی‌پی (IDPay)', 'type' => 'aggregator', 'sandbox' => true ),
            'nextpay'   => array( 'label' => 'نکست‌پی (NextPay)', 'type' => 'aggregator', 'sandbox' => false ),
            'payir'     => array( 'label' => 'پی‌آیر (Pay.ir)', 'type' => 'aggregator', 'sandbox' => true ),
            'zibal'     => array( 'label' => 'زیبال (Zibal)', 'type' => 'aggregator', 'sandbox' => true ),
            'payping'   => array( 'label' => 'پی‌پینگ (PayPing)', 'type' => 'aggregator', 'sandbox' => false ),
            'mellat'    => array( 'label' => 'بانک ملت (بهپرداخت)', 'type' => 'bank', 'sandbox' => false ),
            'saman'     => array( 'label' => 'بانک سامان (SEP)', 'type' => 'bank', 'sandbox' => false ),
            'sadad'     => array( 'label' => 'بانک ملی (سداد)', 'type' => 'bank', 'sandbox' => false ),
            'parsian'   => array( 'label' => 'بانک پارسیان (PEC)', 'type' => 'bank', 'sandbox' => false ),
            'pasargad'  => array( 'label' => 'بانک پاسارگاد', 'type' => 'bank', 'sandbox' => false ),
        );
    }

    public static function get_settings() {
        $defaults = array(
            'gateway'           => 'none',
            'sandbox'           => 1,
            'merchant_id'       => '',
            'api_key'           => '',
            'terminal_id'       => '',
            'username'          => '',
            'password'          => '',
            'callback_secret'   => wp_generate_password( 24, false ),
            'currency'          => 'IRR', // IRR | IRT (toman for some gateways)
            'description'       => 'پرداخت شهر قسط',
        );
        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function save_settings( $data ) {
        $s = self::get_settings();
        $s['gateway']     = sanitize_key( $data['gateway'] ?? $s['gateway'] );
        $s['sandbox']     = ! empty( $data['sandbox'] ) ? 1 : 0;
        $s['merchant_id'] = sanitize_text_field( $data['merchant_id'] ?? '' );
        $s['api_key']     = sanitize_text_field( $data['api_key'] ?? '' );
        $s['terminal_id'] = sanitize_text_field( $data['terminal_id'] ?? '' );
        $s['username']    = sanitize_text_field( $data['username'] ?? '' );
        $s['password']    = sanitize_text_field( $data['password'] ?? '' );
        $s['currency']    = in_array( $data['currency'] ?? '', array( 'IRR', 'IRT' ), true ) ? $data['currency'] : 'IRR';
        $s['description'] = sanitize_text_field( $data['description'] ?? $s['description'] );
        if ( empty( $s['callback_secret'] ) ) {
            $s['callback_secret'] = wp_generate_password( 24, false );
        }
        update_option( self::OPTION_KEY, $s );
        return $s;
    }

    public static function callback_url() {
        return add_query_arg( array( 'cgs_pay' => '1' ), home_url( '/' ) );
    }

    /**
     * شروع پرداخت
     *
     * @param int    $amount  مبلغ به ریال
     * @param array  $meta    order_id, user_id, purpose, mobile, email
     * @return array|\WP_Error { redirect_url, authority, track_id }
     */
    public static function request( $amount, $meta = array() ) {
        $s = self::get_settings();
        $gw = $s['gateway'];
        if ( $gw === 'none' || ! isset( self::gateways_list()[ $gw ] ) ) {
            return new WP_Error( 'no_gateway', 'درگاه پرداخت انتخاب نشده است.' );
        }
        $amount = absint( $amount );
        if ( $amount < 1000 ) {
            return new WP_Error( 'amount', 'مبلغ نامعتبر است.' );
        }

        $order_id = sanitize_text_field( $meta['order_id'] ?? ( 'CGS-' . time() . '-' . wp_rand( 100, 999 ) ) );
        $callback = self::callback_url();

        // ذخیره تراکنش معلق
        $tx = array(
            'order_id'   => $order_id,
            'amount'     => $amount,
            'gateway'    => $gw,
            'status'     => 'pending',
            'meta'       => $meta,
            'created_at' => current_time( 'mysql' ),
        );
        self::store_tx( $order_id, $tx );

        switch ( $gw ) {
            case 'zarinpal':
                return self::zarinpal_request( $amount, $order_id, $callback, $s, $meta );
            case 'idpay':
                return self::idpay_request( $amount, $order_id, $callback, $s, $meta );
            case 'nextpay':
                return self::nextpay_request( $amount, $order_id, $callback, $s, $meta );
            case 'payir':
                return self::payir_request( $amount, $order_id, $callback, $s, $meta );
            case 'zibal':
                return self::zibal_request( $amount, $order_id, $callback, $s, $meta );
            case 'payping':
                return self::payping_request( $amount, $order_id, $callback, $s, $meta );
            case 'mellat':
                return self::mellat_request( $amount, $order_id, $callback, $s, $meta );
            case 'saman':
                return self::saman_request( $amount, $order_id, $callback, $s, $meta );
            case 'sadad':
                return self::sadad_request( $amount, $order_id, $callback, $s, $meta );
            case 'parsian':
                return self::parsian_request( $amount, $order_id, $callback, $s, $meta );
            case 'pasargad':
                return self::pasargad_request( $amount, $order_id, $callback, $s, $meta );
            default:
                return new WP_Error( 'gateway', 'درگاه پشتیبانی نمی‌شود.' );
        }
    }

    public static function verify( $order_id, $params = array() ) {
        $tx = self::get_tx( $order_id );
        if ( ! $tx ) {
            return new WP_Error( 'tx', 'تراکنش یافت نشد.' );
        }
        if ( ( $tx['status'] ?? '' ) === 'paid' ) {
            return array( 'already' => true, 'ref_id' => $tx['ref_id'] ?? '' );
        }
        $s = self::get_settings();
        $gw = $tx['gateway'] ?? $s['gateway'];
        switch ( $gw ) {
            case 'zarinpal':
                return self::zarinpal_verify( $tx, $params, $s );
            case 'idpay':
                return self::idpay_verify( $tx, $params, $s );
            case 'nextpay':
                return self::nextpay_verify( $tx, $params, $s );
            case 'payir':
                return self::payir_verify( $tx, $params, $s );
            case 'zibal':
                return self::zibal_verify( $tx, $params, $s );
            case 'payping':
                return self::payping_verify( $tx, $params, $s );
            case 'mellat':
                return self::mellat_verify( $tx, $params, $s );
            case 'saman':
                return self::saman_verify( $tx, $params, $s );
            case 'sadad':
                return self::sadad_verify( $tx, $params, $s );
            case 'parsian':
                return self::parsian_verify( $tx, $params, $s );
            case 'pasargad':
                return self::pasargad_verify( $tx, $params, $s );
            default:
                return new WP_Error( 'gateway', 'درگاه نامعتبر.' );
        }
    }

    /* ========== Storage ========== */
    private static function store_tx( $order_id, $data ) {
        $all = get_option( 'cgs_payment_tx', array() );
        $all[ $order_id ] = $data;
        // keep last 500
        if ( count( $all ) > 500 ) {
            $all = array_slice( $all, -500, null, true );
        }
        update_option( 'cgs_payment_tx', $all, false );
    }

    private static function get_tx( $order_id ) {
        $all = get_option( 'cgs_payment_tx', array() );
        return isset( $all[ $order_id ] ) ? $all[ $order_id ] : null;
    }

    private static function update_tx( $order_id, $patch ) {
        $tx = self::get_tx( $order_id );
        if ( ! $tx ) {
            return;
        }
        self::store_tx( $order_id, array_merge( $tx, $patch ) );
    }

    private static function http_json( $url, $body, $headers = array() ) {
        $args = array(
            'timeout' => 30,
            'headers' => array_merge( array( 'Content-Type' => 'application/json' ), $headers ),
            'body'    => wp_json_encode( $body ),
        );
        $res = wp_remote_post( $url, $args );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $code = wp_remote_retrieve_response_code( $res );
        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        return array( 'code' => $code, 'data' => $data );
    }

    /* ========== ZarinPal ========== */
    private static function zarinpal_request( $amount, $order_id, $callback, $s, $meta ) {
        $merchant = $s['merchant_id'] ?: $s['api_key'];
        if ( ! $merchant ) {
            return new WP_Error( 'config', 'مرچنت‌آیدی زرین‌پال خالی است.' );
        }
        // amount: IRR; ZarinPal accepts Rials
        $url = ! empty( $s['sandbox'] )
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
            : 'https://api.zarinpal.com/pg/v4/payment/request.json';
        $body = array(
            'merchant_id'  => $merchant,
            'amount'       => $amount,
            'callback_url' => add_query_arg( 'order_id', $order_id, $callback ),
            'description'  => $s['description'] . ' #' . $order_id,
            'metadata'     => array(
                'mobile' => $meta['mobile'] ?? '',
                'email'  => $meta['email'] ?? '',
                'order_id' => $order_id,
            ),
        );
        $res = self::http_json( $url, $body );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $auth = $res['data']['data']['authority'] ?? '';
        $code = $res['data']['data']['code'] ?? ( $res['data']['errors']['code'] ?? 0 );
        if ( ! $auth || (int) $code !== 100 ) {
            $msg = $res['data']['errors']['message'] ?? 'خطای زرین‌پال';
            return new WP_Error( 'zarinpal', $msg );
        }
        self::update_tx( $order_id, array( 'authority' => $auth ) );
        $pay_url = ! empty( $s['sandbox'] )
            ? 'https://sandbox.zarinpal.com/pg/StartPay/' . $auth
            : 'https://www.zarinpal.com/pg/StartPay/' . $auth;
        return array( 'redirect_url' => $pay_url, 'authority' => $auth, 'order_id' => $order_id );
    }

    private static function zarinpal_verify( $tx, $params, $s ) {
        $merchant = $s['merchant_id'] ?: $s['api_key'];
        $auth = $params['Authority'] ?? $params['authority'] ?? ( $tx['authority'] ?? '' );
        $status = $params['Status'] ?? $params['status'] ?? '';
        if ( strtoupper( $status ) !== 'OK' ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'failed' ) );
            return new WP_Error( 'cancelled', 'پرداخت لغو شد.' );
        }
        $url = ! empty( $s['sandbox'] )
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json'
            : 'https://api.zarinpal.com/pg/v4/payment/verify.json';
        $res = self::http_json( $url, array(
            'merchant_id' => $merchant,
            'amount'      => (int) $tx['amount'],
            'authority'   => $auth,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $code = $res['data']['data']['code'] ?? 0;
        $ref  = $res['data']['data']['ref_id'] ?? '';
        if ( in_array( (int) $code, array( 100, 101 ), true ) ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید پرداخت ناموفق بود.' );
    }

    /* ========== IDPay ========== */
    private static function idpay_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['api_key'] ) ) {
            return new WP_Error( 'config', 'API Key آیدی‌پی خالی است.' );
        }
        $headers = array( 'X-API-KEY' => $s['api_key'] );
        if ( ! empty( $s['sandbox'] ) ) {
            $headers['X-SANDBOX'] = '1';
        }
        $res = self::http_json( 'https://api.idpay.ir/v1.1/payment', array(
            'order_id' => $order_id,
            'amount'   => $amount,
            'callback' => add_query_arg( 'order_id', $order_id, $callback ),
            'name'     => $meta['name'] ?? '',
            'phone'    => $meta['mobile'] ?? '',
            'mail'     => $meta['email'] ?? '',
            'desc'     => $s['description'],
        ), $headers );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $link = $res['data']['link'] ?? '';
        $id   = $res['data']['id'] ?? '';
        if ( ! $link ) {
            return new WP_Error( 'idpay', $res['data']['error_message'] ?? 'خطای IDPay' );
        }
        self::update_tx( $order_id, array( 'authority' => $id ) );
        return array( 'redirect_url' => $link, 'authority' => $id, 'order_id' => $order_id );
    }

    private static function idpay_verify( $tx, $params, $s ) {
        $headers = array( 'X-API-KEY' => $s['api_key'] );
        if ( ! empty( $s['sandbox'] ) ) {
            $headers['X-SANDBOX'] = '1';
        }
        $id = $params['id'] ?? ( $tx['authority'] ?? '' );
        $res = self::http_json( 'https://api.idpay.ir/v1.1/payment/verify', array(
            'id'       => $id,
            'order_id' => $tx['order_id'],
        ), $headers );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $status = (int) ( $res['data']['status'] ?? 0 );
        $track  = $res['data']['track_id'] ?? ( $res['data']['payment']['track_id'] ?? '' );
        if ( in_array( $status, array( 100, 101 ), true ) ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $track ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $track );
            return array( 'ref_id' => $track, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید IDPay ناموفق.' );
    }

    /* ========== NextPay ========== */
    private static function nextpay_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['api_key'] ) ) {
            return new WP_Error( 'config', 'کلید نکست‌پی خالی است.' );
        }
        // NextPay amount in toman often — convert if IRR
        $amt = ( $s['currency'] === 'IRT' ) ? intval( $amount / 10 ) : $amount;
        $res = self::http_json( 'https://nextpay.org/nx/gateway/token', array(
            'api_key'      => $s['api_key'],
            'amount'       => $amt,
            'order_id'     => $order_id,
            'callback_uri' => add_query_arg( 'order_id', $order_id, $callback ),
            'customer_phone' => $meta['mobile'] ?? '',
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $code = (int) ( $res['data']['code'] ?? -1 );
        $trans = $res['data']['trans_id'] ?? '';
        if ( $code !== 0 || ! $trans ) {
            return new WP_Error( 'nextpay', 'خطای نکست‌پی کد ' . $code );
        }
        self::update_tx( $order_id, array( 'authority' => $trans ) );
        return array(
            'redirect_url' => 'https://nextpay.org/nx/gateway/payment/' . $trans,
            'authority'    => $trans,
            'order_id'     => $order_id,
        );
    }

    private static function nextpay_verify( $tx, $params, $s ) {
        $trans = $params['trans_id'] ?? ( $tx['authority'] ?? '' );
        $amt = ( $s['currency'] === 'IRT' ) ? intval( $tx['amount'] / 10 ) : (int) $tx['amount'];
        $res = self::http_json( 'https://nextpay.org/nx/gateway/verify', array(
            'api_key'  => $s['api_key'],
            'trans_id' => $trans,
            'amount'   => $amt,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $code = (int) ( $res['data']['code'] ?? -1 );
        if ( $code === 0 ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $trans ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $trans );
            return array( 'ref_id' => $trans, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید نکست‌پی ناموفق.' );
    }

    /* ========== Pay.ir ========== */
    private static function payir_request( $amount, $order_id, $callback, $s, $meta ) {
        $api = $s['api_key'] ?: ( ! empty( $s['sandbox'] ) ? 'test' : '' );
        if ( ! $api ) {
            return new WP_Error( 'config', 'API Key پی‌آیر خالی است.' );
        }
        $res = self::http_json( 'https://pay.ir/pg/send', array(
            'api'          => $api,
            'amount'       => $amount,
            'redirect'     => add_query_arg( 'order_id', $order_id, $callback ),
            'factorNumber' => $order_id,
            'mobile'       => $meta['mobile'] ?? '',
            'description'  => $s['description'],
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $token = $res['data']['token'] ?? '';
        if ( (int) ( $res['data']['status'] ?? 0 ) !== 1 || ! $token ) {
            return new WP_Error( 'payir', $res['data']['errorMessage'] ?? 'خطای Pay.ir' );
        }
        self::update_tx( $order_id, array( 'authority' => $token ) );
        return array( 'redirect_url' => 'https://pay.ir/pg/' . $token, 'authority' => $token, 'order_id' => $order_id );
    }

    private static function payir_verify( $tx, $params, $s ) {
        $api = $s['api_key'] ?: ( ! empty( $s['sandbox'] ) ? 'test' : '' );
        $token = $params['token'] ?? ( $tx['authority'] ?? '' );
        $res = self::http_json( 'https://pay.ir/pg/verify', array(
            'api'   => $api,
            'token' => $token,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        if ( (int) ( $res['data']['status'] ?? 0 ) === 1 ) {
            $ref = $res['data']['transId'] ?? $token;
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید Pay.ir ناموفق.' );
    }

    /* ========== Zibal ========== */
    private static function zibal_request( $amount, $order_id, $callback, $s, $meta ) {
        $merchant = $s['merchant_id'] ?: ( ! empty( $s['sandbox'] ) ? 'zibal' : $s['api_key'] );
        $res = self::http_json( 'https://gateway.zibal.ir/v1/request', array(
            'merchant'    => $merchant,
            'amount'      => $amount,
            'callbackUrl' => add_query_arg( 'order_id', $order_id, $callback ),
            'orderId'     => $order_id,
            'mobile'      => $meta['mobile'] ?? '',
            'description' => $s['description'],
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $track = $res['data']['trackId'] ?? '';
        if ( (int) ( $res['data']['result'] ?? 0 ) !== 100 || ! $track ) {
            return new WP_Error( 'zibal', $res['data']['message'] ?? 'خطای زیبال' );
        }
        self::update_tx( $order_id, array( 'authority' => (string) $track ) );
        return array(
            'redirect_url' => 'https://gateway.zibal.ir/start/' . $track,
            'authority'    => (string) $track,
            'order_id'     => $order_id,
        );
    }

    private static function zibal_verify( $tx, $params, $s ) {
        $merchant = $s['merchant_id'] ?: ( ! empty( $s['sandbox'] ) ? 'zibal' : $s['api_key'] );
        $track = $params['trackId'] ?? ( $tx['authority'] ?? '' );
        $res = self::http_json( 'https://gateway.zibal.ir/v1/verify', array(
            'merchant' => $merchant,
            'trackId'  => (int) $track,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $result = (int) ( $res['data']['result'] ?? 0 );
        if ( in_array( $result, array( 100, 201 ), true ) ) {
            $ref = $res['data']['refNumber'] ?? $track;
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید زیبال ناموفق.' );
    }

    /* ========== PayPing ========== */
    private static function payping_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['api_key'] ) ) {
            return new WP_Error( 'config', 'توکن پی‌پینگ خالی است.' );
        }
        // PayPing amount often in toman
        $amt = ( $s['currency'] === 'IRT' ) ? intval( $amount / 10 ) : intval( $amount / 10 );
        $res = self::http_json( 'https://api.payping.ir/v2/pay', array(
            'amount'      => $amt,
            'returnUrl'   => add_query_arg( 'order_id', $order_id, $callback ),
            'clientRefId' => $order_id,
            'description' => $s['description'],
            'payerIdentity' => $meta['mobile'] ?? '',
        ), array( 'Authorization' => 'Bearer ' . $s['api_key'] ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $code = $res['data']['code'] ?? '';
        if ( ! $code ) {
            return new WP_Error( 'payping', 'خطای پی‌پینگ' );
        }
        self::update_tx( $order_id, array( 'authority' => $code ) );
        return array(
            'redirect_url' => 'https://api.payping.ir/v2/pay/gotoipg/' . $code,
            'authority'    => $code,
            'order_id'     => $order_id,
        );
    }

    private static function payping_verify( $tx, $params, $s ) {
        $ref = $params['refid'] ?? $params['refId'] ?? '';
        $amt = intval( $tx['amount'] / 10 );
        $res = self::http_json( 'https://api.payping.ir/v2/pay/verify', array(
            'amount' => $amt,
            'refId'  => $ref,
        ), array( 'Authorization' => 'Bearer ' . $s['api_key'] ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        // 200 = success typically
        if ( ( $res['code'] ?? 0 ) === 200 || ! empty( $res['data'] ) ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید پی‌پینگ ناموفق.' );
    }

    /* ========== Bank Mellat (Behpardakht) — SOAP structure ========== */
    private static function mellat_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['terminal_id'] ) || empty( $s['username'] ) || empty( $s['password'] ) ) {
            return new WP_Error( 'config', 'ترمینال/نام کاربری/رمز ملت را کامل کنید.' );
        }
        // SOAP would be used in production; structured payload for documentation
        if ( ! class_exists( 'SoapClient' ) ) {
            return new WP_Error( 'soap', 'افزونه SOAP روی سرور فعال نیست (برای ملت لازم است).' );
        }
        try {
            $client = new SoapClient( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl', array( 'encoding' => 'UTF-8', 'connection_timeout' => 20 ) );
            $date = date( 'Ymd' );
            $time = date( 'His' );
            $params = array(
                'terminalId'    => $s['terminal_id'],
                'userName'      => $s['username'],
                'userPassword'  => $s['password'],
                'orderId'       => preg_replace( '/\D/', '', substr( $order_id, -8 ) ) ?: time(),
                'amount'        => $amount,
                'localDate'     => $date,
                'localTime'     => $time,
                'additionalData'=> $order_id,
                'callBackUrl'   => add_query_arg( 'order_id', $order_id, $callback ),
                'payerId'       => '0',
            );
            $result = $client->bpPayRequest( $params );
            $resStr = is_object( $result ) ? ( $result->return ?? '' ) : (string) $result;
            $parts = explode( ',', $resStr );
            if ( ( $parts[0] ?? '' ) !== '0' || empty( $parts[1] ) ) {
                return new WP_Error( 'mellat', 'خطای ملت: ' . $resStr );
            }
            $ref = $parts[1];
            self::update_tx( $order_id, array( 'authority' => $ref ) );
            // Bank redirect is form POST to shaparak
            return array(
                'redirect_url' => 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                'authority'    => $ref,
                'order_id'     => $order_id,
                'method'       => 'POST',
                'form_fields'  => array( 'RefId' => $ref ),
            );
        } catch ( Exception $e ) {
            return new WP_Error( 'mellat', $e->getMessage() );
        }
    }

    private static function mellat_verify( $tx, $params, $s ) {
        if ( ! class_exists( 'SoapClient' ) ) {
            return new WP_Error( 'soap', 'SOAP فعال نیست.' );
        }
        try {
            $client = new SoapClient( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' );
            $sale_order = $params['SaleOrderId'] ?? '';
            $sale_ref   = $params['SaleReferenceId'] ?? '';
            $result = $client->bpVerifyRequest( array(
                'terminalId'      => $s['terminal_id'],
                'userName'        => $s['username'],
                'userPassword'    => $s['password'],
                'orderId'         => $sale_order,
                'saleOrderId'     => $sale_order,
                'saleReferenceId' => $sale_ref,
            ) );
            $ret = is_object( $result ) ? ( $result->return ?? '' ) : (string) $result;
            if ( (string) $ret === '0' ) {
                $client->bpSettleRequest( array(
                    'terminalId'      => $s['terminal_id'],
                    'userName'        => $s['username'],
                    'userPassword'    => $s['password'],
                    'orderId'         => $sale_order,
                    'saleOrderId'     => $sale_order,
                    'saleReferenceId' => $sale_ref,
                ) );
                self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $sale_ref ) );
                do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $sale_ref );
                return array( 'ref_id' => $sale_ref, 'order_id' => $tx['order_id'] );
            }
            return new WP_Error( 'verify', 'تأیید ملت: ' . $ret );
        } catch ( Exception $e ) {
            return new WP_Error( 'mellat', $e->getMessage() );
        }
    }

    /* ========== Saman SEP ========== */
    private static function saman_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['terminal_id'] ) ) {
            return new WP_Error( 'config', 'Terminal ID سامان خالی است.' );
        }
        $res = self::http_json( 'https://sep.shaparak.ir/onlinepg/onlinepg', array(
            'action'      => 'token',
            'TerminalId'  => $s['terminal_id'],
            'Amount'      => $amount,
            'ResNum'      => $order_id,
            'RedirectUrl' => add_query_arg( 'order_id', $order_id, $callback ),
            'CellNumber'  => $meta['mobile'] ?? '',
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $token = $res['data']['token'] ?? '';
        if ( ! $token ) {
            return new WP_Error( 'saman', $res['data']['errorDesc'] ?? 'خطای سامان' );
        }
        self::update_tx( $order_id, array( 'authority' => $token ) );
        return array(
            'redirect_url' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
            'authority'    => $token,
            'order_id'     => $order_id,
            'method'       => 'POST',
            'form_fields'  => array( 'Token' => $token, 'GetMethod' => 'false' ),
        );
    }

    private static function saman_verify( $tx, $params, $s ) {
        $ref = $params['RefNum'] ?? $params['refNum'] ?? '';
        $res = self::http_json( 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', array(
            'RefNum'         => $ref,
            'TerminalNumber' => $s['terminal_id'],
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $ok = ( $res['data']['ResultCode'] ?? $res['data']['Success'] ?? false );
        if ( $ok === 0 || $ok === true || $ok === '0' ) {
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید سامان ناموفق.' );
    }

    /* ========== Sadad (Melli) ========== */
    private static function sadad_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['terminal_id'] ) || empty( $s['merchant_id'] ) || empty( $s['api_key'] ) ) {
            return new WP_Error( 'config', 'ترمینال/مرچنت/کلید سداد را کامل کنید.' );
        }
        // Sign with TerminalKey (simplified — production uses AES/sign per Sadad docs)
        $sign_data = self::sadad_sign( $s['terminal_id'] . ';' . $order_id . ';' . $amount, $s['api_key'] );
        $res = self::http_json( 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest', array(
            'TerminalId'    => $s['terminal_id'],
            'MerchantId'    => $s['merchant_id'],
            'Amount'        => $amount,
            'OrderId'       => $order_id,
            'LocalDateTime' => date( 'Y/m/d H:i:s' ),
            'ReturnUrl'     => add_query_arg( 'order_id', $order_id, $callback ),
            'SignData'      => $sign_data,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        $token = $res['data']['Token'] ?? '';
        if ( (int) ( $res['data']['ResCode'] ?? -1 ) !== 0 || ! $token ) {
            return new WP_Error( 'sadad', $res['data']['Description'] ?? 'خطای سداد' );
        }
        self::update_tx( $order_id, array( 'authority' => $token ) );
        return array(
            'redirect_url' => 'https://sadad.shaparak.ir/VPG/Purchase?Token=' . urlencode( $token ),
            'authority'    => $token,
            'order_id'     => $order_id,
        );
    }

    private static function sadad_sign( $data, $key ) {
        // Base64 key AES encrypt — simplified placeholder; refine with exact Sadad algorithm on go-live
        if ( function_exists( 'openssl_encrypt' ) ) {
            $key_bin = base64_decode( $key );
            if ( $key_bin ) {
                $enc = openssl_encrypt( $data, 'AES-128-CBC', $key_bin, OPENSSL_RAW_DATA, str_repeat( "\0", 16 ) );
                if ( $enc ) {
                    return base64_encode( $enc );
                }
            }
        }
        return base64_encode( hash_hmac( 'sha256', $data, $key, true ) );
    }

    private static function sadad_verify( $tx, $params, $s ) {
        $token = $params['token'] ?? $params['Token'] ?? ( $tx['authority'] ?? '' );
        $sign  = self::sadad_sign( $token, $s['api_key'] );
        $res = self::http_json( 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', array(
            'Token'    => $token,
            'SignData' => $sign,
        ) );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        if ( (int) ( $res['data']['ResCode'] ?? -1 ) === 0 ) {
            $ref = $res['data']['RetrivalRefNo'] ?? $res['data']['SystemTraceNo'] ?? $token;
            self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $ref ) );
            do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $ref );
            return array( 'ref_id' => $ref, 'order_id' => $tx['order_id'] );
        }
        return new WP_Error( 'verify', 'تأیید سداد ناموفق.' );
    }

    /* ========== Parsian ========== */
    private static function parsian_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['pin'] ) && empty( $s['api_key'] ) ) {
            return new WP_Error( 'config', 'LoginAccount (PIN) پارسیان را وارد کنید (در فیلد API Key).' );
        }
        if ( ! class_exists( 'SoapClient' ) ) {
            return new WP_Error( 'soap', 'SOAP برای پارسیان لازم است.' );
        }
        try {
            $client = new SoapClient( 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL' );
            $result = $client->SalePaymentRequest( array(
                'requestData' => array(
                    'LoginAccount'  => $s['api_key'],
                    'Amount'        => $amount,
                    'OrderId'       => (int) preg_replace( '/\D/', '', substr( md5( $order_id ), 0, 8 ) ),
                    'CallBackUrl'   => add_query_arg( 'order_id', $order_id, $callback ),
                    'AdditionalData'=> $order_id,
                ),
            ) );
            $status = $result->SalePaymentRequestResult->Status ?? -1;
            $token  = $result->SalePaymentRequestResult->Token ?? '';
            if ( (int) $status !== 0 || ! $token ) {
                return new WP_Error( 'parsian', 'خطای پارسیان Status=' . $status );
            }
            self::update_tx( $order_id, array( 'authority' => (string) $token ) );
            return array(
                'redirect_url' => 'https://pec.shaparak.ir/NewIPG/?Token=' . $token,
                'authority'    => (string) $token,
                'order_id'     => $order_id,
            );
        } catch ( Exception $e ) {
            return new WP_Error( 'parsian', $e->getMessage() );
        }
    }

    private static function parsian_verify( $tx, $params, $s ) {
        if ( ! class_exists( 'SoapClient' ) ) {
            return new WP_Error( 'soap', 'SOAP لازم است.' );
        }
        try {
            $token = $params['Token'] ?? ( $tx['authority'] ?? '' );
            $client = new SoapClient( 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL' );
            $result = $client->ConfirmPayment( array(
                'requestData' => array(
                    'LoginAccount' => $s['api_key'],
                    'Token'        => (int) $token,
                ),
            ) );
            $status = $result->ConfirmPaymentResult->Status ?? -1;
            $rrn    = $result->ConfirmPaymentResult->RRN ?? $token;
            if ( (int) $status === 0 ) {
                self::update_tx( $tx['order_id'], array( 'status' => 'paid', 'ref_id' => $rrn ) );
                do_action( 'cgs_payment_paid', $tx['order_id'], $tx, $rrn );
                return array( 'ref_id' => $rrn, 'order_id' => $tx['order_id'] );
            }
            return new WP_Error( 'verify', 'تأیید پارسیان: ' . $status );
        } catch ( Exception $e ) {
            return new WP_Error( 'parsian', $e->getMessage() );
        }
    }

    /* ========== Pasargad ========== */
    private static function pasargad_request( $amount, $order_id, $callback, $s, $meta ) {
        if ( empty( $s['merchant_id'] ) || empty( $s['terminal_id'] ) ) {
            return new WP_Error( 'config', 'کد پذیرنده و ترمینال پاسارگاد را وارد کنید.' );
        }
        // Pasargad uses certificate signing — store certificate path in api_key field as path or PEM
        return new WP_Error(
            'pasargad_cert',
            'درگاه پاسارگاد نیازمند گواهی دیجیتال (Certificate) است. فایل گواهی را در سرور قرار دهید و مسیر را در تنظیمات API Key بنویسید. پیاده‌سازی کامل پس از دریافت گواهی از بانک فعال می‌شود.'
        );
    }

    private static function pasargad_verify( $tx, $params, $s ) {
        return new WP_Error( 'pasargad_cert', 'تأیید پاسارگاد پس از پیکربندی گواهی.' );
    }

    /* ========== HTTP handlers ========== */
    public static function handle_callback() {
        if ( empty( $_GET['cgs_pay'] ) ) {
            return;
        }
        $order_id = sanitize_text_field( $_GET['order_id'] ?? $_POST['order_id'] ?? '' );
        if ( ! $order_id ) {
            wp_die( 'سفارش نامعتبر', 'پرداخت', array( 'response' => 400 ) );
        }
        $params = array_merge( $_GET, $_POST );
        $result = self::verify( $order_id, $params );
        $redirect = home_url( '/' );
        if ( is_wp_error( $result ) ) {
            $redirect = add_query_arg( array(
                'cgs_pay_status' => 'failed',
                'msg'            => rawurlencode( $result->get_error_message() ),
            ), $redirect );
        } else {
            $redirect = add_query_arg( array(
                'cgs_pay_status' => 'ok',
                'ref'            => rawurlencode( $result['ref_id'] ?? '' ),
                'order'          => rawurlencode( $order_id ),
            ), $redirect );
        }
        wp_safe_redirect( $redirect );
        exit;
    }

    public static function ajax_request() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        $amount  = absint( $_POST['amount'] ?? 0 );
        $purpose = sanitize_text_field( $_POST['purpose'] ?? 'general' );
        $meta   = array(
            'order_id' => sanitize_text_field( $_POST['order_id'] ?? '' ),
            'mobile'   => sanitize_text_field( $_POST['mobile'] ?? '' ),
            'email'    => sanitize_email( $_POST['email'] ?? '' ),
            'purpose'  => $purpose,
            'user_id'  => get_current_user_id(),
        );

        /**
         * هشدار امنیتی مهم: مبلغ پرداخت مستقیماً از ورودی کاربر (client) خوانده می‌شود.
         * اگر «purpose» به یک قیمت ثابت/محاسبه‌شده در سرور (مثلاً کارمزد پلن یا هزینهٔ
         * اعتبارسنجی) مرتبط است، پیش از اتصال این درگاه به هر عملیات حساس (فعال‌سازی
         * پلن، تسویه، و غیره) باید مبلغ را دوباره از منبع معتبر سرور محاسبه/تأیید کرد،
         * نه اینکه به مقدار ارسالی از مرورگر اعتماد شود. این فیلتر برای همین منظور
         * اضافه شده تا ماژول‌های دیگر (پلن‌ها، تسویه) بتوانند مبلغ را وتو/جایگزین کنند.
         */
        $verified_amount = apply_filters( 'cgs_payment_verified_amount', $amount, $purpose, $meta );
        if ( is_wp_error( $verified_amount ) ) {
            wp_send_json_error( array( 'message' => $verified_amount->get_error_message() ) );
        }
        $amount = absint( $verified_amount );

        $min = (int) apply_filters( 'cgs_payment_min_amount', 1000 );
        $max = (int) apply_filters( 'cgs_payment_max_amount', 500000000 ); // سقف پیش‌فرض احتیاطی: ۵۰ میلیون تومان
        if ( $amount < $min || $amount > $max ) {
            wp_send_json_error( array( 'message' => 'مبلغ درخواستی خارج از محدودهٔ مجاز است.' ) );
        }

        $result = self::request( $amount, $meta );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        wp_send_json_success( $result );
    }

    public static function ajax_verify() {
        check_ajax_referer( 'cgs_frontend_nonce', 'nonce' );
        $order_id = sanitize_text_field( $_POST['order_id'] ?? '' );
        $result = self::verify( $order_id, $_POST );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        wp_send_json_success( $result );
    }
}
