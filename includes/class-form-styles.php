<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Form_Styles {

    public static function init() {
        add_action( 'wp_ajax_cgs_save_form_styles', array( __CLASS__, 'ajax_save_styles' ) );
        add_action( 'wp_ajax_cgs_copy_form_styles', array( __CLASS__, 'ajax_copy_styles' ) );
    }

    public static function get_defaults() {
        return array(
            'label_font'       => 'Vazirmatn',
            'label_size'       => '15',
            'label_weight'     => '600',
            'label_style'      => 'normal',
            'label_decoration' => 'none',
            'label_align'      => 'right',
            'input_font'       => 'Vazirmatn',
            'input_size'       => '15',
            'input_weight'     => '400',
            'input_style'      => 'normal',
            'input_decoration' => 'none',
            'input_align'      => 'right',
            'primary_color'    => '#1a237e',
            'button_color'     => '#1a237e',
            'color_label'      => '#1a1a2e',
            'color_required'   => '#c62828',
            'color_input'      => '#1a1a2e',
            'color_border'     => '#e0e4ec',
            'color_bg'         => '#ffffff',
            'color_button'     => '#1a237e',
            'button_radius'    => '10',
            'button_style'     => 'glass',
            'btn_next_text'    => 'مرحله بعد',
            'btn_prev_text'    => 'مرحله قبل',
            'btn_submit_text'  => 'ثبت نهایی درخواست',
            'form_bg_image'    => '',
            'form_bg_opacity'  => '85',
            'form_bg_effect'   => 'none',
            'label_position'   => 'beside',
            'form_columns'     => '2',
            'field_gap'        => '14',
            'label_width'      => '32',
            'shadow_form'      => 'soft',
            'shadow_field'     => 'none',
            'shadow_btn'       => 'medium',
            'btn_hover'        => 'lift',
            'btn_sound'        => '1',
            'sound_type'       => 'chime',
            'sound_volume'     => '40',
            'btn_align'        => 'space-between',
            'form_title_text'  => '',
            'form_title_font'  => 'Vazirmatn',
            'form_title_size'  => '20',
            'form_title_color' => '#1a237e',
            'form_title_border'=> '#c5cae9',
            'form_title_bw'    => '0',
            'form_title_shadow'=> 'none',
            'form_title_anim'  => 'none',
            'form_title_icon'  => '',
            'form_title_icon_size' => '24',
            'title_position'   => 'top',
            'btn_position'     => 'bottom',
            'btn_position_scope' => 'all',
            'btn_template'     => 'default',
            'btn_size'         => 'md',
            'btn_fullwidth'    => '0',
            'btn_anim'         => 'none',
            'btn_mt'           => '12',
            'btn_mb'           => '0',
            'btn_mx'           => '0',
            'btn_gap'          => '8',
            'btn_ml'           => '0',
            'btn_mr'           => '0',
            'btn_color'        => '#1a237e',
            'btn_font'         => 'Vazirmatn',
            'btn_font_size'    => '14',
        );
    }

    public static function get( $type_key = 'global' ) {
        $all = get_option( 'cgs_form_styles', array() );
        $defaults = self::get_defaults();
        if ( isset( $all[ $type_key ] ) ) {
            return wp_parse_args( $all[ $type_key ], $defaults );
        }
        return $defaults;
    }

    public static function ajax_save_styles() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_forms' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $type_key = sanitize_text_field( $_POST['type_key'] ?? 'global' );
        $styles   = isset( $_POST['styles'] ) ? (array) $_POST['styles'] : array();
        $clean    = array();
        $allowed  = array_keys( self::get_defaults() );
        foreach ( $allowed as $key ) {
            if ( isset( $styles[ $key ] ) ) {
                $clean[ $key ] = sanitize_text_field( wp_unslash( $styles[ $key ] ) );
            }
        }
        $all = get_option( 'cgs_form_styles', array() );
        $all[ $type_key ] = wp_parse_args( $clean, self::get_defaults() );
        update_option( 'cgs_form_styles', $all );
        $log = array(
            'type_key' => $type_key,
            'time'     => current_time( 'mysql' ),
            'user'     => get_current_user_id(),
        );
        update_option( 'cgs_form_styles_last_save', $log, false );
        wp_send_json_success( array( 'styles' => $all[ $type_key ], 'last_save' => $log ) );
    }


    /**
     * کپی ظاهر از یک نوع درخواست به نوع دیگر
     */
    public static function ajax_copy_styles() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'cgs_manage_forms' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $from = sanitize_key( $_POST['from_type'] ?? '' );
        $to   = sanitize_key( $_POST['to_type'] ?? '' );
        if ( ! $from || ! $to ) {
            wp_send_json_error( 'نوع مبدأ و مقصد الزامی است' );
        }
        if ( $from === $to ) {
            wp_send_json_error( 'مبدأ و مقصد یکسان است' );
        }
        $all = get_option( 'cgs_form_styles', array() );
        if ( ! is_array( $all ) ) {
            $all = array();
        }
        $src = isset( $all[ $from ] ) ? $all[ $from ] : self::get_defaults();
        $all[ $to ] = wp_parse_args( (array) $src, self::get_defaults() );
        update_option( 'cgs_form_styles', $all );
        update_option( 'cgs_form_styles_last_save', array(
            'type_key' => $to,
            'time'     => current_time( 'mysql' ),
            'user'     => get_current_user_id(),
            'copied_from' => $from,
        ), false );
        wp_send_json_success( array( 'styles' => $all[ $to ], 'message' => 'ظاهر کپی شد' ) );
    }

    public static function get_css( $type_key = 'global' ) {
        $s         = self::get( $type_key );
        $radius    = isset( $s['button_radius'] ) ? (int) $s['button_radius'] : 10;
        $btn_style = isset( $s['button_style'] ) ? $s['button_style'] : 'glass';
        $bg_img    = isset( $s['form_bg_image'] ) ? $s['form_bg_image'] : '';
        $bg_op     = isset( $s['form_bg_opacity'] ) ? (int) $s['form_bg_opacity'] : 85;
        $label_pos = isset( $s['label_position'] ) ? $s['label_position'] : 'beside';
        $cols      = isset( $s['form_columns'] ) ? max( 1, min( 6, (int) $s['form_columns'] ) ) : 2;
        $gap       = isset( $s['field_gap'] ) ? (int) $s['field_gap'] : 14;
        $label_w   = isset( $s['label_width'] ) ? (int) $s['label_width'] : 32;
        $sf        = isset( $s['shadow_form'] ) ? $s['shadow_form'] : 'soft';
        $sfield    = isset( $s['shadow_field'] ) ? $s['shadow_field'] : 'none';
        $sbtn      = isset( $s['shadow_btn'] ) ? $s['shadow_btn'] : 'medium';
        $bhover    = isset( $s['btn_hover'] ) ? $s['btn_hover'] : 'lift';
        $balign    = isset( $s['btn_align'] ) ? $s['btn_align'] : 'space-between';
        $cbtn      = isset( $s['color_button'] ) ? $s['color_button'] : '#1a237e';

        $shadows = array(
            'none'   => 'none',
            'soft'   => '0 4px 20px rgba(0,0,0,0.08)',
            'medium' => '0 8px 28px rgba(0,0,0,0.12)',
            'strong' => '0 12px 40px rgba(0,0,0,0.18)',
            'glow'   => '0 8px 32px rgba(26,35,126,0.28)',
        );

        $css = "
        .cgs-form-wrapper[data-type='{$type_key}'] {
            box-shadow: " . ( $shadows[ $sf ] ?? $shadows['soft'] ) . " !important;
            border-radius: 16px;
            padding: 22px 24px;
        }
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group > label,
        .cgs-form-wrapper .cgs-field-group > label {
            font-family: {$s['label_font']}, Tahoma, sans-serif !important;
            font-size: {$s['label_size']}px !important;
            font-weight: {$s['label_weight']} !important;
            font-style: {$s['label_style']} !important;
            text-decoration: {$s['label_decoration']} !important;
            text-align: {$s['label_align']} !important;
            color: {$s['color_label']} !important;
        }
        .cgs-form-wrapper .cgs-required { color: {$s['color_required']} !important; }
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group input,
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group select,
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group textarea {
            font-family: {$s['input_font']}, Tahoma, sans-serif !important;
            font-size: {$s['input_size']}px !important;
            font-weight: {$s['input_weight']} !important;
            font-style: {$s['input_style']} !important;
            text-decoration: {$s['input_decoration']} !important;
            text-align: {$s['input_align']} !important;
            color: {$s['color_input']} !important;
            border-color: {$s['color_border']} !important;
            background-color: {$s['color_bg']} !important;
            border-radius: 10px !important;
        }
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-step-fields {
            display: grid !important;
            grid-template-columns: repeat({$cols}, 1fr) !important;
            gap: {$gap}px !important;
        }
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn {
            border-radius: {$radius}px !important;
            box-shadow: " . ( $shadows[ $sbtn ] ?? $shadows['medium'] ) . " !important;
            transition: all 0.25s ease !important;
        }
        .cgs-form-wrapper[data-type='{$type_key}'] .cgs-step-actions {
            display: flex !important;
            justify-content: {$balign} !important;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        ";

        if ( $sfield !== 'none' ) {
            $css .= ".cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group input,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group select,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group textarea {
                box-shadow: " . ( $shadows[ $sfield ] ?? 'none' ) . " !important;
            }";
        }

        // لیبل کنار / بالا — برای فرم عمومی و پیش‌نمایش
        if ( $label_pos === 'beside' ) {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group,
            .cgs-form-wrapper.cgs-labels-beside .cgs-field-group,
            #cgs-live-preview.cgs-labels-beside .cgs-field-group {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 12px !important;
            }
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group > label,
            .cgs-form-wrapper.cgs-labels-beside .cgs-field-group > label,
            #cgs-live-preview.cgs-labels-beside .cgs-field-label {
                flex: 0 0 {$label_w}% !important;
                margin: 0 !important;
            }
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group > .cgs-field-control,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group > *:not(label) {
                flex: 1 1 auto !important;
                min-width: 0 !important;
            }
            ";
        } else {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group,
            .cgs-form-wrapper.cgs-labels-above .cgs-field-group,
            #cgs-live-preview.cgs-labels-above .cgs-field-group {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-field-group > label,
            .cgs-form-wrapper.cgs-labels-above .cgs-field-group > label,
            #cgs-live-preview.cgs-labels-above .cgs-field-label {
                display: block !important;
                margin: 0 0 6px !important;
                flex: none !important;
            }
            ";
        }

        if ( $btn_style === 'glass' ) {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-primary,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-success,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn {
                background: rgba(255,255,255,0.28) !important;
                backdrop-filter: blur(14px) saturate(1.5) !important;
                -webkit-backdrop-filter: blur(14px) saturate(1.5) !important;
                border: 1.5px solid rgba(255,255,255,0.55) !important;
                color: {$cbtn} !important;
            }
            ";
        } elseif ( $btn_style === 'outline' ) {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-primary,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-success {
                background: transparent !important;
                border: 2px solid {$cbtn} !important;
                color: {$cbtn} !important;
            }
            ";
        } elseif ( $btn_style === 'gradient' ) {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-primary,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-success {
                background: linear-gradient(135deg, {$cbtn}, #3949ab) !important;
                color: #fff !important;
                border: none !important;
            }
            ";
        } else {
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-primary,
            .cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn-success {
                background: {$cbtn} !important;
                color: #fff !important;
            }
            ";
        }

        if ( $bhover === 'lift' ) {
            $css .= ".cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn:hover { transform: translateY(-3px) !important; }";
        } elseif ( $bhover === 'scale' ) {
            $css .= ".cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn:hover { transform: scale(1.04) !important; }";
        } elseif ( $bhover === 'glow' ) {
            $css .= ".cgs-form-wrapper[data-type='{$type_key}'] .cgs-btn:hover { box-shadow: 0 0 22px rgba(26,35,126,0.45) !important; }";
        }

        if ( $bg_img ) {
            $op = max( 0, min( 100, $bg_op ) ) / 100;
            $css .= "
            .cgs-form-wrapper[data-type='{$type_key}'] {
                background-image: url('{$bg_img}') !important;
                background-size: cover !important;
                background-position: center !important;
            }
            .cgs-form-wrapper[data-type='{$type_key}'].has-bg::before {
                content: '';
                position: absolute; inset: 0;
                background: rgba(255,255,255,{$op}) !important;
                border-radius: inherit;
                z-index: 0;
            }
            ";
        }

        $css .= "
        .cgs-two-fields {
            display: grid !important;
            grid-template-columns: 120px 1fr !important;
            gap: 12px !important;
            width: 100% !important;
        }
        .cgs-two-fields .cgs-field-group { display: block !important; margin: 0 !important; }
        .cgs-two-fields .cgs-sub-label {
            display: block !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            margin-bottom: 4px !important;
        }
        ";

        return $css;
    }
}
