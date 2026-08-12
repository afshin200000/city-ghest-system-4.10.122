<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$type_info = cgs_get_application_type( $type );
$total_steps = count( $steps );
$step_meta = get_option( 'cgs_step_meta_' . $type, array() );

$cgs_field_icons = array(
    'full_name' => 'user', 'first_name' => 'user', 'last_name' => 'user',
    'mobile' => 'mobile', 'phone' => 'phone', 'landline' => 'phone', 'phone_fixed' => 'phone',
    'national_id' => 'id-card', 'id_card' => 'id-card',
    'province' => 'map', 'city' => 'map', 'address' => 'home', 'postal_code' => 'map',
    'birth_date' => 'calendar', 'date' => 'calendar',
    'email' => 'mail', 'website' => 'edit',
    'bank_account' => 'bank', 'bank_card' => 'bank', 'sheba' => 'bank', 'bank_name' => 'bank',
    'account_holder' => 'user', 'card_name' => 'user',
    'company_name' => 'building', 'business_type' => 'building',
    'guarantee_type' => 'shield', 'check_bank' => 'shield', 'promissory' => 'file',
    'id_card_front' => 'camera', 'id_card_back' => 'camera', 'file' => 'file',
    'password' => 'lock', 'person_type' => 'users',
);
if ( ! function_exists( 'cgs_field_icon_html' ) ) {
function cgs_field_icon_html( $key, $field_type = '' ) {
    global $cgs_field_icons;
    $icons = isset( $cgs_field_icons ) ? $cgs_field_icons : array();
    $name = '';
    if ( isset( $icons[ $key ] ) ) {
        $name = $icons[ $key ];
    } else {
        foreach ( $icons as $k => $v ) {
            if ( strpos( $key, $k ) !== false ) { $name = $v; break; }
        }
    }
    if ( ! $name ) {
        if ( $field_type === 'file' ) $name = 'file';
        elseif ( $field_type === 'date' ) $name = 'calendar';
        elseif ( $field_type === 'email' ) $name = 'mail';
        elseif ( $field_type === 'tel' ) $name = 'phone';
        else $name = 'edit';
    }
    return '<span class="cgs-icon cgs-icon-' . esc_attr( $name ) . '" aria-hidden="true"></span> ';
}
}

?>

<script>
window.cgsLocations = <?php echo wp_json_encode( cgs_get_iran_locations() ); ?>;
</script>

<?php
$cgs_styles = class_exists( 'CGS_Form_Styles' ) ? CGS_Form_Styles::get( $type ) : array();
$cgs_btn_style = $cgs_styles['button_style'] ?? 'solid';
$cgs_has_bg = ! empty( $cgs_styles['form_bg_image'] ) ? ' has-bg' : '';
$cgs_logo = cgs_get_option( 'site_logo', '' );
?>
<?php
$cgs_st = class_exists( 'CGS_Form_Styles' ) ? CGS_Form_Styles::get( $type ) : array();
$cgs_label_pos = isset( $cgs_st['label_position'] ) ? $cgs_st['label_position'] : 'beside';
$cgs_wrap_extra = ( $cgs_label_pos === 'beside' ) ? ' cgs-labels-beside' : ' cgs-labels-above';
if ( ! empty( $cgs_st['form_bg_image'] ) ) {
    $cgs_wrap_extra .= ' has-bg';
}
?>
<div class="cgs-form-wrapper cgs-btn-style-<?php echo esc_attr( isset( $cgs_btn_style ) ? $cgs_btn_style : 'solid' ); ?><?php echo esc_attr( $cgs_wrap_extra ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
    <?php $cgs_logo = cgs_get_option( 'site_logo', '' ); if ( $cgs_logo ) : ?>
        <div class="cgs-form-logo" style="text-align:center;margin-bottom:12px;"><img src="<?php echo esc_url( $cgs_logo ); ?>" alt="لوگو" style="max-height:64px;width:auto;"></div>
        <?php endif; ?>
        <div class="cgs-form-header">
        <h2 class="cgs-form-title"><span class="cgs-icon cgs-icon-lg cgs-icon-building"></span> <?php echo esc_html( $type_info['label'] ); ?></h2>
        <p class="cgs-form-subtitle">فرم ثبت‌نام و همکاری با شهر قسط</p>
    </div>

    <!-- Progress Bar -->
    <div class="cgs-progress">
        <div class="cgs-progress-bar">
            <div class="cgs-progress-fill" style="width: <?php echo ( 100 / $total_steps ); ?>%;"></div>
        </div>
        <div class="cgs-progress-steps">
            <?php
            $step_keys = array_keys( $steps );
            $idx = 0;
            foreach ( $step_keys as $sn ) :
                $idx++;
                $sm = isset( $step_meta[ $sn ] ) ? $step_meta[ $sn ] : ( isset( $step_meta[ (string) $sn ] ) ? $step_meta[ (string) $sn ] : ( isset( $step_meta[ (int) $sn ] ) ? $step_meta[ (int) $sn ] : array() ) );
                $sname = ( ! empty( $sm['name'] ) ) ? $sm['name'] : ( 'مرحله ' . $idx );
                $sicon = ! empty( $sm['icon'] ) ? $sm['icon'] : '';
                $sicon_url = ! empty( $sm['icon_url'] ) ? $sm['icon_url'] : '';
            ?>
                <div class="cgs-step-indicator <?php echo $idx === 1 ? 'active' : ''; ?>" data-step="<?php echo (int) $sn; ?>" title="<?php echo esc_attr( $sname ); ?>">
                    <span class="cgs-step-num"><?php echo $idx; ?></span>
                    <span class="cgs-step-label"><?php
                    if ( $sicon_url ) {
                        echo '<img src="' . esc_url( $sicon_url ) . '" alt="" style="width:1em;height:1em;vertical-align:middle;margin-left:4px;"> ';
                    } elseif ( $sicon ) {
                        echo '<span class="cgs-icon cgs-icon-' . esc_attr( $sicon ) . '" style="width:0.9em;height:0.9em;"></span> ';
                    }
                    echo esc_html( $sname );
                ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="cgs-steps-remaining" style="text-align:center;font-size:0.85rem;color:#666;margin-top:8px;">
            مرحله <span class="cgs-current-step-num">1</span> از <?php echo (int) $total_steps; ?>
            — <span class="cgs-remaining-text"><?php echo max(0, $total_steps - 1); ?> مرحله باقی‌مانده</span>
        </div>
    </div>

    <form id="cgs-application-form" class="cgs-multi-step-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="type_key" value="<?php echo esc_attr( $type ); ?>">
        <input type="hidden" name="action" value="cgs_submit_application">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'cgs_frontend_nonce' ); ?>">

        <?php foreach ( $steps as $step_num => $fields ) : ?>
            
<?php if ( $type === 'applicant' && class_exists( 'CGS_Plans' ) ) :
    $active_plans = CGS_Plans::get_active_plans();
    if ( ! empty( $active_plans ) ) : ?>
<div class="cgs-plan-select-wrap" style="margin-bottom:16px;padding:14px;background:#f5f7ff;border-radius:12px;border:1px solid #c5cae9;">
    <label for="cgs-plan-select" style="font-weight:700;display:block;margin-bottom:6px;"><span class="cgs-icon cgs-icon-star"></span> انتخاب طرح اعتباری</label>
    <select id="cgs-plan-select" name="credit_plan_id" style="width:100%;padding:10px;border-radius:8px;">
        <option value="">— انتخاب طرح —</option>
        <?php foreach ( $active_plans as $ap ) : ?>
        <option value="<?php echo esc_attr( $ap['id'] ); ?>" data-fields="<?php echo esc_attr( implode( ',', $ap['field_keys'] ?? array() ) ); ?>">
            <?php echo esc_html( ( $ap['icon_emoji'] ?? '📋' ) . ' ' . $ap['title'] ); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <div id="cgs-plan-info" style="margin-top:8px;font-size:0.88rem;color:#444;"></div>
</div>
<?php endif; endif; ?>

            <div class="cgs-form-step <?php echo $step_num === array_key_first($steps) ? 'active' : ''; ?>" data-step="<?php echo (int) $step_num; ?>">
                <?php
                $sm2 = isset( $step_meta[ $step_num ] ) ? $step_meta[ $step_num ] : ( isset( $step_meta[ (string) $step_num ] ) ? $step_meta[ (string) $step_num ] : array() );
                $simg = isset( $sm2['image'] ) ? $sm2['image'] : '';
                $sname = isset( $sm2['name'] ) ? $sm2['name'] : '';
                if ( $sname ) {
                    echo '<h3 class="cgs-step-title"><span class="cgs-icon cgs-icon-edit"></span> ' . esc_html( $sname ) . '</h3>';
                }
                $sfiles = array();
                if ( ! empty( $sm2['files'] ) && is_array( $sm2['files'] ) ) {
                    $sfiles = $sm2['files'];
                } elseif ( $simg ) {
                    $sfiles[] = array( 'url' => $simg, 'type' => 'image' );
                }
                if ( ! empty( $sfiles ) ) {
                    echo '<div class="cgs-step-guide">';
                    foreach ( $sfiles as $sf ) {
                        $u = esc_url( $sf['url'] ?? '' );
                        $t = $sf['type'] ?? 'image';
                        if ( $t === 'pdf' || substr( $u, -4 ) === '.pdf' ) {
                            echo '<p class="cgs-step-pdf"><a href="' . $u . '" target="_blank" rel="noopener" class="cgs-btn cgs-btn-secondary">📄 مشاهده فایل آموزشی PDF</a></p>';
                        } else {
                            echo '<img src="' . $u . '" alt="راهنمای مرحله" style="max-width:100%;border-radius:10px;margin-bottom:12px;">';
                        }
                    }
                    echo '<p class="cgs-step-guide-caption">فایل‌های راهنما برای تکمیل این مرحله</p></div>';
                }
                ?>
                <?php
                $step_cols = 2;
                $smc = isset( $step_meta[ $step_num ] ) ? $step_meta[ $step_num ] : ( isset( $step_meta[ (string) $step_num ] ) ? $step_meta[ (string) $step_num ] : array() );
                if ( ! empty( $smc['columns'] ) ) $step_cols = max( 1, min( 6, (int) $smc['columns'] ) );
                ?>
                <div class="cgs-step-fields" data-step-cols="<?php echo (int) $step_cols; ?>" style="display:grid;grid-template-columns:repeat(<?php echo (int) $step_cols; ?>,minmax(0,1fr));gap:12px;width:100%;">
                    <?php foreach ( $fields as $field ) :
                        $required = $field['is_required'] ? 'required' : '';
                        $key      = esc_attr( $field['field_key'] );
                        $label    = cgs_field_icon_html( $key, $field['field_type'] ?? '' ) . esc_html( $field['label'] );
                        $placeholder = esc_attr( $field['placeholder'] );
                        $maxlen_attr = '';
                        $charset_attr = '';
                        $val_data = array();
                        if ( ! empty( $field['validation'] ) ) {
                            $val_data = is_array( $field['validation'] ) ? $field['validation'] : ( json_decode( $field['validation'], true ) ?: array() );
                            if ( ! empty( $val_data['max_length'] ) ) {
                                $maxlen_attr = ' maxlength="' . absint( $val_data['max_length'] ) . '" data-maxlen="' . absint( $val_data['max_length'] ) . '" data-maxlength="' . absint( $val_data['max_length'] ) . '"';
                            }
                            if ( ! empty( $val_data['charset'] ) ) {
                                $charset_attr = ' data-charset="' . esc_attr( $val_data['charset'] ) . '"';
                            }
                        }
                        // Defaults for known keys
                        if ( ! $maxlen_attr ) {
                            if ( $key === 'mobile' || strpos( $key, 'mobile' ) !== false ) {
                                $maxlen_attr = ' maxlength="11" data-maxlen="11" data-maxlength="11"';
                                $charset_attr = ' data-charset="numeric"';
                            } elseif ( $key === 'national_id' || strpos( $key, 'national_id' ) !== false ) {
                                $maxlen_attr = ' maxlength="10" data-maxlen="10" data-maxlength="10"';
                                $charset_attr = ' data-charset="numeric"';
                            } elseif ( $key === 'landline' || $key === 'phone_fixed' ) {
                                $maxlen_attr = ' maxlength="8" data-maxlen="8" data-maxlength="8"';
                                $charset_attr = ' data-charset="numeric"';
                            }
                        }
                        if ( ! $charset_attr && in_array( $field['field_type'] ?? '', array( 'tel', 'number' ), true ) ) {
                            $charset_attr = ' data-charset="numeric"';
                        }
                    ?>
                        <?php
                        $fw = !empty($field['css_class']) ? $field['css_class'] : '100';
                        $width_class = 'cgs-w-' . $fw;
                        $gclass = '';
                        $gk = $field['field_key'];
                        if ( in_array( $gk, array( 'guarantor_name', 'guarantor_national_id', 'guarantor_mobile', 'guarantor_relation', 'guarantor_sign_status' ), true )
                            || strpos( $gk, 'guarantor_' ) === 0 ) {
                            $gclass = ' cgs-guarantor-field';
                        }
                        ?>
                        <div class="cgs-field-group cgs-field-<?php echo esc_attr( $field['field_type'] ); ?> <?php echo esc_attr($width_class . $gclass); ?>" data-field-key="<?php echo esc_attr( $gk ); ?>"<?php
                            $__vd = array();
                            if ( ! empty( $field['validation'] ) ) {
                                $__vd = is_array( $field['validation'] ) ? $field['validation'] : ( json_decode( $field['validation'], true ) ?: array() );
                            }
                            if ( ! empty( $__vd['conditions']['enabled'] ) ) {
                                echo ' data-cgs-conditions="' . esc_attr( wp_json_encode( $__vd['conditions'] ) ) . '"';
                            }
                            ?>>
                            <?php
                            $cgs_skip_label = false;
                            if ( ( $field['field_type'] ?? '' ) === 'table' ) {
                                $pl = trim( (string) ( $field['label'] ?? '' ) );
                                if ( $pl === '' || $pl === 'جدول' || false !== strpos( $pl, 'جدول' ) || false !== strpos( $pl, 'ماتریس داده' ) ) {
                                    $cgs_skip_label = true;
                                }
                            }
                            if ( ! $cgs_skip_label ) :
                            ?>
                            <label for="cgs-<?php echo $key; ?>">
                                <?php echo $label; ?>
                                <?php if ( $field['is_required'] ) : ?><span class="cgs-required">*</span><?php endif; ?>
                            </label>
                            <?php endif; ?>

                            <?php
                            switch ( $field['field_type'] ) :
                                case 'textarea':
                                    ?>
                                    <textarea id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?> rows="4"></textarea>
                                    <?php
                                    break;

                                case 'select':
                                    $options = json_decode( $field['options'] ?? '[]', true ) ?: array();
                                    $extra_class = '';
                                    $disabled_opts = array();
                                    if ( $key === 'province' || strpos( $key, 'province' ) !== false ) {
                                        $options = cgs_get_provinces_list();
                                        $extra_class = 'cgs-province';
                                    } elseif ( $key === 'city' || strpos( $key, 'city' ) !== false ) {
                                        $options = array();
                                        $extra_class = 'cgs-city';
                                    } elseif ( $key === 'business_type' || strpos( $key, 'business_type' ) !== false ) {
                                        $options = cgs_get_guild_list();
                                        $extra_class = 'cgs-business-type';
                                    } elseif ( $key === 'guarantee_type' || strpos( $key, 'guarantee_type' ) !== false ) {
                                        $options = array();
                                        $disabled_opts = array();
                                        if ( cgs_get_option( 'allow_check_guarantee', 1 ) ) $options[] = 'چک';
                                        else $disabled_opts[] = 'چک';
                                        if ( cgs_get_option( 'allow_promissory_guarantee', 1 ) ) $options[] = 'سفته';
                                        else $disabled_opts[] = 'سفته';
                                        $extra_class = 'cgs-guarantee-type';
                                    } elseif ( $key === 'guarantee_owner' || strpos( $key, 'guarantee_owner' ) !== false ) {
                                        $options = array( 'خودم', 'شخص دیگر' );
                                        $extra_class = 'cgs-guarantee-owner';
                                    } elseif ( $key === 'guarantor_sign_status' || strpos( $key, 'guarantor_sign_status' ) !== false ) {
                                        $options = array( 'در انتظار احراز هویت', 'لینک امضا ارسال شده', 'امضا شده و تأیید شده', 'رد شده' );
                                        $extra_class = 'cgs-sign-status';
                                    } elseif ( $key === 'person_type' || strpos( $key, 'person_type' ) !== false ) {
                                        $options = array();
                                        if ( cgs_get_option( 'allow_natural_person', 1 ) ) {
                                            $options[] = 'حقیقی';
                                        } else {
                                            $disabled_opts[] = 'حقیقی';
                                        }
                                        if ( cgs_get_option( 'allow_legal_person', 1 ) ) {
                                            $options[] = 'حقوقی';
                                        } else {
                                            $disabled_opts[] = 'حقوقی';
                                        }
                                        // Always show both for visibility, but disable if not allowed
                                        $all_person = array( 'حقیقی', 'حقوقی' );
                                        $extra_class = 'cgs-person-type';
                                    }
                                    ?>
                                    <select id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" class="<?php echo esc_attr($extra_class); ?>" data-role="<?php echo esc_attr($key); ?>" data-field-key="<?php echo esc_attr($key); ?>" <?php echo $required; ?>>
                                        <option value="">انتخاب کنید...</option>
                                        <?php
                                        if ( $key === 'person_type' || strpos( $key, 'person_type' ) !== false ) {
                                            foreach ( array( 'حقیقی', 'حقوقی' ) as $opt ) {
                                                $is_disabled = ! in_array( $opt, $options, true );
                                                $dis_attr = $is_disabled ? ' disabled' : '';
                                                $note = $is_disabled ? ' (غیرفعال)' : '';
                                                echo '<option value="' . esc_attr( $opt ) . '"' . $dis_attr . '>' . esc_html( $opt . $note ) . '</option>';
                                            }
                                        } elseif ( $key === 'guarantee_type' || strpos( $key, 'guarantee_type' ) !== false ) {
                                            foreach ( array( 'چک', 'سفته' ) as $opt ) {
                                                $is_disabled = ! in_array( $opt, $options, true );
                                                $dis_attr = $is_disabled ? ' disabled' : '';
                                                $note = $is_disabled ? ' (غیرفعال)' : '';
                                                echo '<option value="' . esc_attr( $opt ) . '"' . $dis_attr . '>' . esc_html( $opt . $note ) . '</option>';
                                            }
                                        } else {
                                            foreach ( $options as $opt ) :
                                        ?>
                                            <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
                                        <?php endforeach;
                                        }
                                        ?>
                                    </select>
                                    <?php if ( ( $key === 'person_type' || strpos( $key, 'person_type' ) !== false ) && ! empty( $disabled_opts ) ) : ?>
                                        <div class="cgs-field-note">توجه: گزینه<?php echo count($disabled_opts)>1?'‌های':''; ?> <?php echo esc_html( implode( ' و ', $disabled_opts ) ); ?> در حال حاضر غیرفعال است.</div>
                                    <?php endif; ?>
                                    <?php
                                    break;

                                case 'url':
                                    ?>
                                    <input type="url" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder ?: 'https://'; ?>" dir="ltr" style="text-align:left;" <?php echo $required; ?> <?php echo $maxlen_attr; ?> <?php echo $charset_attr; ?>>
                                    <?php
                                    break;


                                case 'divider':
                                    $dtitle = $label ?: $placeholder;
                                    ?>
                                    <div class="cgs-divider-block">
                                        <?php if ( $dtitle ) : ?><div class="cgs-divider-title"><?php echo esc_html( $dtitle ); ?></div><?php endif; ?>
                                        <hr class="cgs-divider-line">
                                    </div>
                                    <?php
                                    break;

                                case 'table':
                                    $vd = array();
                                    if ( ! empty( $field['validation'] ) ) {
                                        $vd = is_array( $field['validation'] ) ? $field['validation'] : ( json_decode( $field['validation'], true ) ?: array() );
                                    }
                                    $tcols = max( 2, min( 12, (int) ( $vd['table_cols'] ?? 3 ) ) );
                                    $trows = max( 1, min( 30, (int) ( $vd['table_rows'] ?? 2 ) ) );
                                    $tmax  = max( $trows, min( 50, (int) ( $vd['table_max_rows'] ?? 10 ) ) );
                                    $tcolor = ! empty( $vd['table_color'] ) ? $vd['table_color'] : '#1a237e';
                                    $tctext = ! empty( $vd['table_color_text'] ) ? $vd['table_color_text'] : '#ffffff';
                                    $thdrs  = ! empty( $vd['table_headers'] ) && is_array( $vd['table_headers'] ) ? $vd['table_headers'] : array();
                                    $cls = 'cgs-dynamic-table';
                                    if ( ! empty( $vd['table_striped'] ) ) $cls .= ' is-striped';
                                    if ( ! empty( $vd['table_bordered'] ) ) $cls .= ' is-bordered';
                                    if ( ! empty( $vd['table_compact'] ) ) $cls .= ' is-compact';
                                    ?>
                                    <div class="cgs-dynamic-table-wrap" data-max-rows="<?php echo (int) $tmax; ?>">
                                        <table class="<?php echo esc_attr( $cls ); ?>" style="--cgs-th-bg:<?php echo esc_attr( $tcolor ); ?>;--cgs-th-fg:<?php echo esc_attr( $tctext ); ?>">
                                            <thead><tr>
                                            <?php for ( $ci = 0; $ci < $tcols; $ci++ ) :
                                                $ht = isset( $thdrs[ $ci ] ) ? $thdrs[ $ci ] : ( 'ستون ' . ( $ci + 1 ) ); ?>
                                                <th style="background:<?php echo esc_attr( $tcolor ); ?>;color:<?php echo esc_attr( $tctext ); ?>;"><?php echo esc_html( $ht ); ?></th>
                                            <?php endfor; ?>
                                            </tr></thead>
                                            <tbody>
                                            <?php for ( $ri = 0; $ri < $trows; $ri++ ) : ?>
                                                <tr>
                                                <?php for ( $ci = 0; $ci < $tcols; $ci++ ) : ?>
                                                    <td><input type="text" name="<?php echo esc_attr( $key ); ?>[<?php echo $ri; ?>][<?php echo $ci; ?>]" class="cgs-input" placeholder="—"></td>
                                                <?php endfor; ?>
                                                </tr>
                                            <?php endfor; ?>
                                            </tbody>
                                            <?php
                                            $formula = $vd['table_formula'] ?? '';
                                            if ( $formula && in_array( $formula, array( 'sum', 'avg', 'count', 'min', 'max' ), true ) ) :
                                                $flabels = array( 'sum' => 'جمع', 'avg' => 'میانگین', 'count' => 'تعداد', 'min' => 'کمینه', 'max' => 'بیشینه' );
                                            ?>
                                            <tfoot><tr>
                                            <?php for ( $ci = 0; $ci < $tcols; $ci++ ) : ?>
                                                <td style="background:#f1f5f9;font-weight:700;text-align:center;"><span data-cgs-agg="<?php echo esc_attr( $formula ); ?>" data-col="<?php echo (int) $ci; ?>">—</span><br><small style="font-weight:500;color:#64748b;"><?php echo esc_html( $flabels[ $formula ] ); ?></small></td>
                                            <?php endfor; ?>
                                            </tr></tfoot>
                                            <?php endif; ?>
                                        </table>
                                        <?php if ( ! isset( $vd['table_addrow'] ) || ! empty( $vd['table_addrow'] ) ) : ?>
                                        <button type="button" class="cgs-btn cgs-btn-secondary cgs-table-add-row">+ افزودن ردیف</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    break;

                                case 'file':

                                    $file_types = array( 'jpg', 'jpeg', 'png', 'pdf', 'webp' );
                                    $file_max_kb = 2048;
                                    if ( ! empty( $field['validation'] ) ) {
                                        $vd = json_decode( $field['validation'], true );
                                        if ( ! empty( $vd['file_types'] ) ) $file_types = $vd['file_types'];
                                        if ( ! empty( $vd['file_max_kb'] ) ) $file_max_kb = (int) $vd['file_max_kb'];
                                    }
                                    $accept = '.' . implode( ',.', $file_types );
                                    $types_label = strtoupper( implode( '، ', $file_types ) );
                                    $size_label = $file_max_kb >= 1024 ? round( $file_max_kb / 1024, 1 ) . ' مگابایت' : $file_max_kb . ' کیلوبایت';
                                    ?>
                                    <div class="cgs-file-upload cgs-upload-row" style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                                        <div style="flex:1;min-width:160px;">
                                        <input type="file" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" class="cgs-file-input"
                                            accept="<?php echo esc_attr( $accept ); ?>"
                                            data-allowed="<?php echo esc_attr( implode( ',', $file_types ) ); ?>"
                                            data-maxkb="<?php echo esc_attr( $file_max_kb ); ?>"
                                            data-preview-target="cgs-prev-<?php echo esc_attr( $key ); ?>"
                                            <?php echo $required; ?>>
                                        <label for="cgs-<?php echo $key; ?>" class="cgs-file-label">
                                            <span class="cgs-file-icon"><span class="cgs-icon cgs-icon-lg cgs-icon-camera"></span></span>
                                            <span class="cgs-file-text">انتخاب فایل / تصویر</span>
                                        </label>
                                        <div class="cgs-file-hint">فرمت‌های مجاز: <?php echo esc_html( $types_label ); ?> — حداکثر حجم: <?php echo esc_html( $size_label ); ?></div>
                                        <div class="cgs-file-selected" style="display:none;margin-top:8px;font-size:0.88rem;color:#1a237e;"></div>
                                        </div>
                                        <div class="cgs-upload-preview" id="cgs-prev-<?php echo esc_attr( $key ); ?>" style="flex:0 0 120px;width:120px;height:120px;border:2px dashed #c5cae9;border-radius:14px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#94a3b8;font-size:11px;text-align:center;padding:8px;">پیش‌نمایش تصویر</div>
                                    </div>
                                    <?php
                                    break;

                                case 'number':
                                    ?>
                                    <input type="text" inputmode="numeric" class="cgs-numeric" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?> <?php echo $maxlen_attr; ?> <?php echo $charset_attr; ?> autocomplete="off">
                                    <?php
                                    break;

                                case 'tel':
                                    if ( $key === 'landline' || $key === 'phone_fixed' || strpos( $key, 'landline' ) !== false || strpos( $key, 'phone_fixed' ) !== false || ( ! empty( $field['label'] ) && ( false !== strpos( $field['label'], 'تلفن ثابت' ) || false !== strpos( $field['label'], 'شماره ثابت' ) || ( ( $field['field_type'] ?? '' ) === 'tel' && false !== strpos( $field['label'], 'ثابت' ) ) ) ) ) {
                                        $ml = $maxlen_attr ? $maxlen_attr : 'maxlength="8" data-maxlen="8" data-maxlength="8"';
                                        // دو کادر کاملاً جدا
                                        ?>
                                    <div class="cgs-two-fields cgs-landline-row" style="display:flex;flex-direction:row;direction:ltr;gap:10px;align-items:flex-end;width:100%;">
                                        <div class="cgs-field-group cgs-area-code-group" style="flex:0 0 88px;">
                                            <label class="cgs-sub-label">کد شهرستان</label>
                                            <input type="text" name="area_code" id="cgs-area-code-field" class="cgs-area-code" data-role="area_code" value="" placeholder="خودکار" readonly maxlength="4" data-charset="numeric">
                                        </div>
                                        <div class="cgs-field-group cgs-landline-num-group">
                                            <label class="cgs-sub-label">شماره تلفن ثابت</label>
                                            <input type="tel" class="cgs-tel cgs-numeric cgs-landline" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder ?: 'شماره بدون کد'; ?>" inputmode="numeric" pattern="[0-9]*" <?php echo $required; ?> <?php echo $ml; ?> data-charset="numeric" data-role="landline" autocomplete="tel-national">
                                        </div>
                                    </div>
                                        <?php
                                    } else {
                                        ?>
                                    <input type="tel" class="cgs-tel cgs-numeric" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder; ?>" inputmode="numeric" <?php echo $maxlen_attr ? $maxlen_attr : 'maxlength="11" data-maxlen="11" data-maxlength="11"'; ?> data-charset="numeric" <?php echo $required; ?> autocomplete="tel">
                                        <?php
                                    }
                                    break;

                                case 'email':
                                    ?>
                                    <input type="email" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?>>
                                    <?php
                                    break;

                                case 'date':
                                    ?>
                                    <input type="text" class="cgs-jalali-date" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="انتخاب تاریخ" <?php echo $required; ?> autocomplete="off" readonly>
                                    <?php
                                    break;

                                default:
                                    $extra_cls = '';
                                    $readonly = '';
                                    if ( $key === 'national_id' || strpos( $key, 'national_id' ) !== false ) $extra_cls = 'cgs-national-id cgs-numeric';
                                    if ( $key === 'sheba' || strpos( $key, 'sheba' ) !== false || $key === 'check_sheba' ) $extra_cls = 'cgs-sheba';
                                    if ( $key === 'bank_card' ) $extra_cls = 'cgs-numeric';
                                    if ( $key === 'area_code' || strpos( $key, 'area_code' ) !== false ) {
                                        $extra_cls = 'cgs-area-code cgs-numeric';
                                        $readonly = 'readonly';
                                        if ( ! $placeholder ) $placeholder = 'با انتخاب استان پر می‌شود';
                                    }
                                    if ( $key === 'landline' || $key === 'phone_fixed' || strpos( $key, 'landline' ) !== false || strpos( $key, 'phone_fixed' ) !== false || ( ! empty( $field['label'] ) && ( false !== strpos( $field['label'], 'تلفن ثابت' ) || false !== strpos( $field['label'], 'شماره ثابت' ) || ( ( $field['field_type'] ?? '' ) === 'tel' && false !== strpos( $field['label'], 'ثابت' ) ) ) ) ) {
                                        $ml = $maxlen_attr ? $maxlen_attr : 'maxlength="8" data-maxlen="8" data-maxlength="8"';
                                        ?>
                                    <div class="cgs-two-fields cgs-landline-row" style="display:flex;flex-direction:row;direction:ltr;gap:10px;align-items:flex-end;width:100%;">
                                        <div class="cgs-field-group cgs-area-code-group" style="flex:0 0 88px;">
                                            <label class="cgs-sub-label">کد شهرستان</label>
                                            <input type="text" name="area_code" class="cgs-area-code" data-role="area_code" value="" placeholder="خودکار" readonly maxlength="4">
                                        </div>
                                        <div class="cgs-field-group cgs-landline-num-group">
                                            <label class="cgs-sub-label">شماره تلفن ثابت</label>
                                            <input type="tel" class="cgs-tel cgs-numeric cgs-landline" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder ?: 'شماره بدون کد'; ?>" inputmode="numeric" <?php echo $required; ?> <?php echo $ml; ?> data-charset="numeric" data-role="landline">
                                        </div>
                                    </div>
                                        <?php
                                    } else {
                                    ?>
                                    <input type="text" class="<?php echo esc_attr( trim($extra_cls) ); ?>" id="cgs-<?php echo $key; ?>" name="<?php echo $key; ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?> <?php echo $maxlen_attr; ?> <?php echo $charset_attr; ?> <?php echo $readonly; ?> data-role="<?php echo esc_attr($key); ?>">
                                    <?php
                                    }
                            endswitch;
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                
            <!-- Honeypot anti-spam (مخفی از کاربر) -->
            <div class="cgs-hp" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden;">
                <label>ایمیل کاری<input type="text" name="cgs_hp_email" value="" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="cgs-step-actions">
                    <?php if ( $step_num > 1 ) : ?>
                        <button type="button" class="cgs-btn cgs-btn-secondary cgs-prev-step"><span class="cgs-icon cgs-icon-edit"></span> مرحله قبل</button>
                    <?php endif; ?>

                    <?php if ( $step_num < $total_steps ) : ?>
                        <button type="button" class="cgs-btn cgs-btn-primary cgs-next-step"><span class="cgs-icon cgs-icon-success"></span> مرحله بعد</button>
                    <?php else : ?>
                        <button type="submit" class="cgs-btn cgs-btn-success cgs-submit-form"><span class="cgs-icon cgs-icon-check"></span> ثبت نهایی درخواست</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>

    
<div class="cgs-digital-sign-trigger" style="margin:16px 0;text-align:center;">
    <button type="button" class="cgs-btn cgs-btn-primary cgs-btn-digital-sign" id="cgs-start-digital-sign">
        ثبت امضای دیجیتال و مشاهده قوانین
    </button>
    <p style="font-size:0.85rem;color:#666;margin-top:8px;">قبل از ثبت امضا، قوانین اعتبار قانونی نمایش داده می‌شود.</p>
</div>


        <div class="cgs-iran-map-wrap" aria-label="نقشه ایران">
            <div class="cgs-map-toolbar">
                <div class="cgs-map-toolbar-title">🗺️ نقشه تعاملی ایران</div>
                <div class="cgs-map-tools">
                    <button type="button" class="cgs-map-zoom-in" title="بزرگ‌نمایی">+</button>
                    <button type="button" class="cgs-map-zoom-out" title="کوچک‌نمایی">−</button>
                    <button type="button" class="cgs-map-toggle-scroll" title="فعال‌سازی زوم با اسکرول">⟳</button>
                    <button type="button" class="cgs-map-reset" title="نمای کل ایران">⌖</button>
                </div>
            </div>
            <div id="cgs-iran-map" role="img" aria-label="نقشه شهر انتخاب‌شده"></div>
            <div class="cgs-map-hud" id="cgs-map-hud"><span class="cgs-map-hud-muted">استان و شهر را انتخاب کنید تا روی نقشه نمایش داده شود</span></div>
        </div>

        <div class="cgs-form-message" style="display:none;"></div>
</div>

<!-- Digital signature legal notice -->
<div id="cgs-sign-legal-overlay" class="cgs-sign-legal-overlay" aria-hidden="true">
    <div class="cgs-sign-legal-card" role="dialog" aria-labelledby="cgs-sign-legal-title">
        <div class="cgs-sign-legal-header">
            <h3 id="cgs-sign-legal-title">اعتبار قانونی امضای دیجیتال</h3>
            <p>قبل از ثبت امضا، لطفاً این نکات قانونی را مطالعه کنید</p>
            <span class="cgs-sign-legal-badge">قانون تجارت الکترونیکی — مصوب ۱۳۸۲</span>
        </div>
        <div class="cgs-sign-legal-body">
            <h4>ماده ۷ قانون تجارت الکترونیکی</h4>
            <p>«هرگاه قانون وجود امضا را لازم بداند، امضای الکترونیکی مکفی است.»</p>

            <h4>امضای الکترونیکی مطمئن</h4>
            <p>طبق مواد ۱۰ و ۱۵ همان قانون، امضای الکترونیکی مطمئن (مبتنی بر گواهی معتبر از مراکز صدور گواهی دارای مجوز) دارای آثار زیر است:</p>
            <ul>
                <li>در حکم سند معتبر و قابل استناد در مراجع قضایی و اداری است.</li>
                <li>نسبت به اصل امضا، انکار و تردید به‌سادگی پذیرفته نمی‌شود (مشابه اسناد رسمی از نظر اماره صحت).</li>
                <li>هویت امضاکننده از طریق گواهی الکترونیکی صادرشده توسط مراکز مجاز قابل اثبات است.</li>
                <li>تغییر سند پس از امضا قابل تشخیص است.</li>
            </ul>

            <div class="cgs-sign-legal-highlight">
                امضای دیجیتال معتبر — در چارچوب قانون تجارت الکترونیکی و گواهی صادرشده از مراکز دارای مجوز شورای سیاست‌گذاری گواهی الکترونیکی — از نظر آثار قانونی هم‌تراز با امضای انجام‌شده در دفترخانه اسناد رسمی برای اسناد الکترونیکی محسوب می‌شود و در مراجع قضایی قابل استناد است.
            </div>

            <h4>تعهد شما</h4>
            <p>با تأیید و ثبت امضای دیجیتال، اعلام می‌دارید که:</p>
            <ul>
                <li>مفاد سند/فرم را خوانده و پذیرفته‌اید.</li>
                <li>امضا با اراده و اختیار خودتان انجام می‌شود.</li>
                <li>از آثار قانونی این امضا آگاه هستید.</li>
            </ul>
        </div>
        <div class="cgs-sign-legal-footer">
            <label class="cgs-sign-legal-check">
                <input type="checkbox" id="cgs-sign-legal-agree">
                <span>قوانین فوق را مطالعه کردم و می‌پذیرم که امضای دیجیتال من طبق قانون تجارت الکترونیکی دارای اعتبار قانونی است.</span>
            </label>
            <div class="cgs-sign-legal-actions">
                <button type="button" class="cgs-btn cgs-btn-cancel-sign" id="cgs-sign-legal-cancel">انصراف</button>
                <button type="button" class="cgs-btn cgs-btn-accept-sign" id="cgs-sign-legal-accept" disabled>ادامه و ثبت امضا</button>
            </div>
        </div>
    </div>
</div>

