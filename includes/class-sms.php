<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_SMS {

    public static function init() {
        add_action( 'cgs_application_approved', array( __CLASS__, 'on_approve' ) );
        add_action( 'cgs_application_rejected', array( __CLASS__, 'on_reject' ) );
        add_action( 'cgs_member_created', array( __CLASS__, 'on_member_created' ), 10, 3 );
    }

    public static function on_approve( $app_id ) {
        if ( ! cgs_get_option( 'sms_enabled' ) || ! cgs_get_option( 'sms_on_approve' ) ) {
            return;
        }
        $app = CGS_Application::get( $app_id );
        if ( ! $app || empty( $app->mobile ) ) {
            return;
        }

        $message = sprintf(
            'کاربر گرامی، درخواست شما با کد %s در شهر قسط تأیید شد. برای ورود به پنل به سایت مراجعه کنید.',
            $app->code
        );

        self::send( $app->mobile, $message );
    }

    public static function on_reject( $app_id ) {
        if ( ! cgs_get_option( 'sms_enabled' ) || ! cgs_get_option( 'sms_on_reject' ) ) {
            return;
        }
        $app = CGS_Application::get( $app_id );
        if ( ! $app || empty( $app->mobile ) ) {
            return;
        }

        $message = sprintf(
            'متأسفانه درخواست شما با کد %s در شهر قسط مورد تأیید قرار نگرفت.',
            $app->code
        );

        self::send( $app->mobile, $message );
    }

    public static function on_member_created( $user_id, $app_id, $password ) {
        if ( ! cgs_get_option( 'sms_enabled' ) ) {
            return;
        }
        $app = CGS_Application::get( $app_id );
        if ( ! $app || empty( $app->mobile ) ) {
            return;
        }

        $login_url = cgs_get_login_url();
        $message   = sprintf(
            "حساب کاربری شما در شهر قسط ایجاد شد.\nنام کاربری: %s\nرمز عبور: %s\nورود: %s",
            'cg_' . $app->mobile,
            $password,
            $login_url
        );

        self::send( $app->mobile, $message );
    }

    /**
     * Main send method - modular providers
     */
    public static function send( $mobile, $message ) {
        $provider = cgs_get_option( 'sms_provider' );
        $api_key  = cgs_get_option( 'sms_api_key' );
        $sender   = cgs_get_option( 'sms_sender' );

        if ( empty( $provider ) || empty( $api_key ) ) {
            return new WP_Error( 'sms_not_configured', 'درگاه پیامک تنظیم نشده است.' );
        }

        $mobile = cgs_sanitize_phone( $mobile );

        // Provider switch - easy to extend
        switch ( $provider ) {
            case 'kavenegar':
                return self::send_kavenegar( $mobile, $message, $api_key, $sender );
            case 'melipayamak':
                return self::send_melipayamak( $mobile, $message, $api_key, $sender );
            case 'ghasedak':
                return self::send_ghasedak( $mobile, $message, $api_key, $sender );
            default:
                // Generic webhook / custom
                return apply_filters( 'cgs_sms_send', false, $mobile, $message, $provider, $api_key, $sender );
        }
    }

    private static function send_kavenegar( $mobile, $message, $api_key, $sender ) {
        $url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json";
        $response = wp_remote_post( $url, array(
            'body' => array(
                'sender'     => $sender,
                'receptor'   => $mobile,
                'message'    => $message,
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
        return true;
    }

    private static function send_melipayamak( $mobile, $message, $api_key, $sender ) {
        // Placeholder - implement with actual Melipayamak API when needed
        return apply_filters( 'cgs_sms_melipayamak', false, $mobile, $message, $api_key, $sender );
    }

    private static function send_ghasedak( $mobile, $message, $api_key, $sender ) {
        // Placeholder
        return apply_filters( 'cgs_sms_ghasedak', false, $mobile, $message, $api_key, $sender );
    }
}
