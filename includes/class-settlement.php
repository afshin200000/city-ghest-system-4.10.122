<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * الگوریتم‌های پیشرفته تسویه اقساط
 * - تسویه عادی طبق جدول
 * - تسویه زودهنگام با تخفیف سود باقی‌مانده
 * - تسویه جزئی (partial)
 * - جریمه دیرکرد (دیرکرد روزانه)
 */
class CGS_Settlement {

    public static function init() {
        add_action( 'wp_ajax_cgs_settlement_preview', array( __CLASS__, 'ajax_preview' ) );
    }

    public static function get_settings() {
        $defaults = array(
            'early_discount_percent' => 50,   // درصد بخشودگی سود باقی‌مانده در تسویه زودهنگام
            'late_penalty_daily'     => 0.1,  // درصد جریمه روزانه روی قسط معوق
            'grace_days'             => 3,    // مهلت بخشودگی دیرکرد (روز)
            'min_partial_percent'    => 10,   // حداقل درصد تسویه جزئی
            'rounding'               => 'round', // round|floor|ceil
        );
        $saved = get_option( 'cgs_settlement_settings', array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    /**
     * ساخت جدول اقساط از خروجی محاسبه‌گر
     */
    public static function build_schedule( $principal, $rate, $months, $step = 1, $method = 'flat' ) {
        if ( ! class_exists( 'CGS_Installment_Calculator' ) ) {
            return array();
        }
        $calc = CGS_Installment_Calculator::calculate( $principal, $rate, $months, $step, $method );
        return $calc;
    }

    /**
     * تسویه زودهنگام: اصل باقی‌مانده + بخشی از سود باقی‌مانده
     *
     * @param array $schedule از calculate()['schedule']
     * @param int   $paid_count تعداد اقساط پرداخت‌شده
     * @param float $principal
     * @param float $total_profit
     */
    public static function early_payoff( $principal, $total_profit, $payments_count, $paid_count, $settings = null ) {
        $settings = $settings ?: self::get_settings();
        $paid_count = max( 0, min( (int) $paid_count, (int) $payments_count ) );
        $remaining_payments = max( 0, $payments_count - $paid_count );
        if ( $remaining_payments <= 0 ) {
            return array(
                'type'            => 'early',
                'remaining_principal' => 0,
                'remaining_profit'=> 0,
                'discount'        => 0,
                'payable'         => 0,
                'message'         => 'بدهی تسویه شده است',
            );
        }
        // تخصیص خطی اصل و سود
        $principal_per = $principal / max( 1, $payments_count );
        $profit_per    = $total_profit / max( 1, $payments_count );
        $remaining_principal = $principal_per * $remaining_payments;
        $remaining_profit    = $profit_per * $remaining_payments;
        $discount_rate = max( 0, min( 100, (float) $settings['early_discount_percent'] ) ) / 100;
        $discount = $remaining_profit * $discount_rate;
        $payable  = $remaining_principal + ( $remaining_profit - $discount );
        $payable  = self::round_amount( $payable, $settings['rounding'] );
        return array(
            'type'                => 'early',
            'remaining_principal' => self::round_amount( $remaining_principal, $settings['rounding'] ),
            'remaining_profit'    => self::round_amount( $remaining_profit, $settings['rounding'] ),
            'discount'            => self::round_amount( $discount, $settings['rounding'] ),
            'payable'             => $payable,
            'remaining_payments'  => $remaining_payments,
            'message'             => 'تسویه زودهنگام با بخشودگی سود',
        );
    }

    /**
     * جریمه دیرکرد برای یک قسط
     */
    public static function late_penalty( $installment_amount, $days_late, $settings = null ) {
        $settings = $settings ?: self::get_settings();
        $days_late = max( 0, (int) $days_late );
        $grace = max( 0, (int) $settings['grace_days'] );
        $effective = max( 0, $days_late - $grace );
        $daily = max( 0, (float) $settings['late_penalty_daily'] ) / 100;
        $penalty = $installment_amount * $daily * $effective;
        return array(
            'days_late'   => $days_late,
            'grace_days'  => $grace,
            'effective_days' => $effective,
            'penalty'     => self::round_amount( $penalty, $settings['rounding'] ),
            'total_due'   => self::round_amount( $installment_amount + $penalty, $settings['rounding'] ),
        );
    }

    /**
     * تسویه جزئی: حداقل درصد از مانده
     */
    public static function partial_pay( $remaining_total, $amount, $settings = null ) {
        $settings = $settings ?: self::get_settings();
        $remaining_total = max( 0, (float) $remaining_total );
        $amount = max( 0, (float) $amount );
        $min_pct = max( 0, (float) $settings['min_partial_percent'] );
        $min_amount = $remaining_total * ( $min_pct / 100 );
        if ( $amount < $min_amount && $amount < $remaining_total ) {
            return array(
                'ok'      => false,
                'message' => 'مبلغ کمتر از حداقل تسویه جزئی است',
                'min'     => self::round_amount( $min_amount, $settings['rounding'] ),
            );
        }
        $applied = min( $amount, $remaining_total );
        return array(
            'ok'        => true,
            'applied'   => self::round_amount( $applied, $settings['rounding'] ),
            'remaining' => self::round_amount( $remaining_total - $applied, $settings['rounding'] ),
            'message'   => 'تسویه جزئی ثبت شد',
        );
    }

    private static function round_amount( $n, $mode = 'round' ) {
        if ( $mode === 'floor' ) {
            return (int) floor( $n );
        }
        if ( $mode === 'ceil' ) {
            return (int) ceil( $n );
        }
        return (int) round( $n );
    }

    public static function ajax_preview() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی' );
        }
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        $principal = floatval( $_POST['principal'] ?? 0 );
        $rate      = floatval( $_POST['rate'] ?? 0 );
        $months    = intval( $_POST['months'] ?? 12 );
        $paid      = intval( $_POST['paid_count'] ?? 0 );
        $calc = self::build_schedule( $principal, $rate, $months );
        $early = self::early_payoff( $calc['principal'], $calc['profit'], $calc['payments'], $paid );
        wp_send_json_success( array( 'calc' => $calc, 'early' => $early ) );
    }
}
