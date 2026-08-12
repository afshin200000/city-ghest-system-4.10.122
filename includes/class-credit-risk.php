<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * مدیریت ریسک اعتباری هوشمند (قوانین قابل تنظیم + امتیازدهی)
 * امتیاز ۰–۱۰۰؛ آستانه‌های رد / بررسی دستی / تایید
 */
class CGS_Credit_Risk {

    public static function init() {
        add_action( 'wp_ajax_cgs_credit_score_preview', array( __CLASS__, 'ajax_preview' ) );
    }

    public static function get_settings() {
        $defaults = array(
            'weight_credit_rank'   => 35,  // رتبه اعتباری استعلام
            'weight_debt'          => 25,  // بدهی سررسیدشده
            'weight_age'           => 10,
            'weight_income_ratio'  => 20,  // نسبت قسط به درآمد
            'weight_history'       => 10,  // سابقه داخل سیستم
            'reject_below'         => 40,
            'manual_below'         => 60,
            'max_installment_ratio'=> 40,  // حداکثر قسط/درآمد ٪
            'min_age'              => 18,
            'max_age'              => 70,
            'auto_reject'          => 1,
            'auto_approve'         => 0,
        );
        $saved = get_option( 'cgs_credit_risk_settings', array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    /**
     * @param array $input keys: credit_rank (1-5 or A-E), has_overdue_debt (bool),
     *   age, monthly_income, installment_amount, internal_score (0-100 optional)
     */
    public static function score( array $input, $settings = null ) {
        $s = $settings ?: self::get_settings();
        $details = array();
        $total_w = max( 1, (int) $s['weight_credit_rank'] + (int) $s['weight_debt'] + (int) $s['weight_age'] + (int) $s['weight_income_ratio'] + (int) $s['weight_history'] );

        // رتبه اعتباری: 1 بهترین … 5 بدترین یا A=1
        $rank = $input['credit_rank'] ?? 3;
        if ( is_string( $rank ) ) {
            $map = array( 'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5 );
            $rank = $map[ strtoupper( $rank ) ] ?? 3;
        }
        $rank = max( 1, min( 5, (int) $rank ) );
        $rank_score = ( 6 - $rank ) * 20; // 1→100, 5→20
        $details['credit_rank'] = $rank_score;

        $debt_score = ! empty( $input['has_overdue_debt'] ) ? 0 : 100;
        $details['debt'] = $debt_score;

        $age = isset( $input['age'] ) ? (int) $input['age'] : 30;
        $min_a = (int) $s['min_age'];
        $max_a = (int) $s['max_age'];
        if ( $age < $min_a || $age > $max_a ) {
            $age_score = 0;
        } else {
            // اوج در میانه بازه
            $mid = ( $min_a + $max_a ) / 2;
            $span = max( 1, ( $max_a - $min_a ) / 2 );
            $age_score = max( 0, 100 - abs( $age - $mid ) / $span * 40 );
        }
        $details['age'] = round( $age_score, 1 );

        $income = max( 0, (float) ( $input['monthly_income'] ?? 0 ) );
        $inst   = max( 0, (float) ( $input['installment_amount'] ?? 0 ) );
        $ratio  = $income > 0 ? ( $inst / $income ) * 100 : 100;
        $max_ratio = max( 1, (float) $s['max_installment_ratio'] );
        if ( $ratio <= $max_ratio * 0.5 ) {
            $income_score = 100;
        } elseif ( $ratio >= $max_ratio ) {
            $income_score = 0;
        } else {
            $income_score = 100 * ( 1 - ( $ratio - $max_ratio * 0.5 ) / ( $max_ratio * 0.5 ) );
        }
        $details['income_ratio'] = round( $income_score, 1 );
        $details['installment_to_income_pct'] = round( $ratio, 1 );

        $hist = isset( $input['internal_score'] ) ? max( 0, min( 100, (float) $input['internal_score'] ) ) : 70;
        $details['history'] = $hist;

        $weighted = (
            $rank_score * (int) $s['weight_credit_rank'] +
            $debt_score * (int) $s['weight_debt'] +
            $age_score * (int) $s['weight_age'] +
            $income_score * (int) $s['weight_income_ratio'] +
            $hist * (int) $s['weight_history']
        ) / $total_w;

        $final = (int) round( $weighted );
        $decision = 'approve';
        if ( $final < (int) $s['reject_below'] ) {
            $decision = 'reject';
        } elseif ( $final < (int) $s['manual_below'] ) {
            $decision = 'manual';
        }

        if ( ! empty( $input['has_overdue_debt'] ) && ! empty( $s['auto_reject'] ) ) {
            $decision = 'reject';
        }

        $labels = array(
            'approve' => 'تایید خودکار',
            'manual'  => 'بررسی دستی ادمین',
            'reject'  => 'رد',
        );

        return array(
            'score'    => $final,
            'decision' => $decision,
            'label'    => $labels[ $decision ],
            'details'  => $details,
            'message'  => 'امتیاز ریسک اعتباری محاسبه شد',
        );
    }

    public static function ajax_preview() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی' );
        }
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        $input = array(
            'credit_rank'         => sanitize_text_field( $_POST['credit_rank'] ?? '3' ),
            'has_overdue_debt'    => ! empty( $_POST['has_overdue_debt'] ),
            'age'                 => intval( $_POST['age'] ?? 30 ),
            'monthly_income'      => floatval( $_POST['monthly_income'] ?? 0 ),
            'installment_amount'  => floatval( $_POST['installment_amount'] ?? 0 ),
            'internal_score'      => floatval( $_POST['internal_score'] ?? 70 ),
        );
        wp_send_json_success( self::score( $input ) );
    }
}
