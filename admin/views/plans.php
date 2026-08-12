
<style id="cgs-emergency-unlock-plans">
.cgs-admin-wrap input, .cgs-admin-wrap select, .cgs-admin-wrap textarea, .cgs-admin-wrap button,
#cgs-categories-editor input, #cgs-categories-editor select, #cgs-categories-editor textarea, #cgs-categories-editor button,
#tab-cats input, #tab-cats select, #tab-cats textarea, #tab-cats button {
  pointer-events: auto !important;
  opacity: 1 !important;
  z-index: 60 !important;
  user-select: text !important;
}
</style>

<style id="cgs-cats-unlock">
#tab-cats, #tab-cats * { pointer-events: auto !important; }
#cgs-categories-editor input,
#cgs-categories-editor select,
#cgs-categories-editor textarea,
#cgs-categories-editor button {
  pointer-events: auto !important;
  user-select: text !important;
  -webkit-user-select: text !important;
  position: relative !important;
  z-index: 20 !important;
  opacity: 1 !important;
  background: #fff !important;
}
.cgs-cat-handle, .cgs-opt-handle { cursor: grab !important; }
</style>
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$plans = CGS_Plans::get_plans();
$categories = CGS_Plans::get_categories();
$styles = CGS_Plans::get_styles();
$detail_types = CGS_Plans::detail_types();
?>

<style id="cgs-plans-layout">
.cgs-plans-wrap { max-width: 1100px; overflow-x: hidden; box-sizing: border-box; }
.cgs-plans-wrap * { box-sizing: border-box; }
.cgs-plans-wrap .cgs-plans-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
  gap: 16px;
  align-items: start;
}
@media (max-width: 960px) {
  .cgs-plans-wrap .cgs-plans-grid { grid-template-columns: 1fr; }
}
.cgs-plans-wrap input[type="text"],
.cgs-plans-wrap input[type="number"],
.cgs-plans-wrap textarea,
.cgs-plans-wrap select {
  max-width: 100%;
  width: 100%;
}
.cgs-plans-wrap .form-table { width: 100%; table-layout: fixed; }
.cgs-plans-wrap .form-table th { width: 140px; }
.cgs-plans-wrap #cgs-durations-list { max-width: 100%; }
.cgs-plan-preview-card, #cgs-plan-preview-card {
  border: 1px solid #c5cae9;
  border-radius: 16px;
  padding: 18px;
  background: #fff;
  box-shadow: 0 8px 28px rgba(26,35,126,0.1);
}
.cgs-pc-period {
  background: linear-gradient(135deg, #eef2ff, #f8fafc);
  border: 1px solid #c5cae9;
  border-radius: 10px;
  padding: 10px 12px;
  margin: 8px 0;
}
.cgs-pc-steps { margin: 6px 0 0 14px; padding: 0; font-size: 12.5px; }
.cgs-pc-steps li { margin: 3px 0; }
.cgs-plan-card-public {
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 20px;
  background: #fff;
  box-shadow: 0 6px 24px rgba(15,23,42,0.08);
  margin-bottom: 14px;
}
</style>


<style id="cgs-plans-cat-grid">
#cgs-categories-editor .cgs-cat-block{max-width:100%;}
#cgs-categories-editor .cgs-opt-item{
  display:grid !important;
  grid-template-columns: 28px minmax(100px,1.2fr) minmax(110px,1fr) minmax(100px,1fr) 32px !important;
  gap:8px !important;
  align-items:center !important;
  padding:8px 10px !important;
}
#cgs-categories-editor .cgs-opt-item input[type="text"],
#cgs-categories-editor .cgs-opt-item select,
#cgs-categories-editor .cgs-opt-item input[type="number"],
#cgs-categories-editor .cgs-opt-item textarea{
  max-width:100% !important;
  width:100% !important;
  box-sizing:border-box !important;
  font-size:12.5px !important;
  padding:5px 8px !important;
  height:auto !important;
  min-height:32px !important;
}
#cgs-categories-editor .cgs-cat-title{max-width:280px !important;font-size:13px !important;}
@media(max-width:900px){
  #cgs-categories-editor .cgs-opt-item{grid-template-columns:1fr !important;}
}
</style>

<div class="wrap cgs-admin-wrap cgs-plans-wrap">
    <h1>طرح‌ها و روش‌های فروش اقساطی</h1>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'plans.main' ); } ?>

    <h2 class="nav-tab-wrapper cgs-plans-tabs">
        <a href="#tab-plans" class="nav-tab nav-tab-active" data-tab="plans">مدیریت طرح‌ها</a>
        <a href="#tab-cats" class="nav-tab" data-tab="cats">دسته‌بندی‌ها و گزینه‌ها</a>
        <a href="#tab-style" class="nav-tab" data-tab="style">ظاهر و پیش‌نمایش</a>
    </h2>

    <!-- TAB: Categories -->
    <div id="tab-cats" class="cgs-tab-panel" style="display:none;margin-top:16px;">
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'plans.categories' ); } ?>
<style>
.cgs-cat-block{
  background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin-bottom:14px;
  box-shadow:0 4px 18px rgba(15,23,42,.07);transition:box-shadow .2s;
}
.cgs-cat-block:hover{box-shadow:0 8px 28px rgba(26,35,126,.12);}
.cgs-cat-head{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #eef2ff;}
.cgs-cat-title{flex:1;min-width:160px;max-width:320px;padding:6px 10px!important;border-radius:8px!important;border:1px solid #cbd5e1!important;font-weight:700;color:#1a237e;}
.cgs-opt-sortable{margin:0;padding:0;list-style:none;}
.cgs-opt-item{
  display:grid;grid-template-columns:24px minmax(120px,1.2fr) minmax(100px,1fr) minmax(100px,1fr) auto;
  gap:8px;align-items:center;padding:8px 10px;margin:6px 0;background:#f8fafc;border:1px solid #e2e8f0;
  border-radius:10px;
}
.cgs-opt-item input,.cgs-opt-item select{max-width:100%;padding:5px 8px!important;border-radius:7px!important;border:1px solid #cbd5e1!important;font-size:12.5px;}
.cgs-opt-item .cgs-opt-handle{cursor:grab;color:#94a3b8;}
@media(max-width:800px){.cgs-opt-item{grid-template-columns:1fr;}}
.cgs-plan-sec-box{
  background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;margin:12px 0;
  box-shadow:0 2px 12px rgba(15,23,42,.05);
}
.cgs-plan-sec-box h3{margin:0 0 10px;font-size:13.5px;color:#1a237e;border-bottom:1px solid #eef2ff;padding-bottom:6px;}
.cgs-badge-vip{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#78350f;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;}
.cgs-badge-star{color:#f59e0b;font-size:16px;}
.cgs-badge-off{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:6px;font-size:11px;}
.cgs-badge-on{background:#dcfce7;color:#166534;padding:2px 8px;border-radius:6px;font-size:11px;}
</style>
<details class="cgs-help" open>
<summary>راهنما: دسته‌بندی‌ها و گزینه‌ها</summary>
<div class="cgs-help-body">
<p>هر <strong>دسته</strong> یک گروه ویژگی طرح است (مثلاً مشمولین، شرایط سنی، رتبه اعتباری).</p>
<ol>
<li>با «+ دسته جدید» یک باکس بسازید و عنوان را بنویسید.</li>
<li>داخل دسته «+ گزینه» بزنید (مثلاً بازنشستگان، ۱۸ تا ۶۵ سال).</li>
<li>نوع جزئیات گزینه را انتخاب کنید (بازه عددی، متن، فهرست…).</li>
<li>در پایان «ذخیره همه دسته‌ها» را بزنید.</li>
</ol>
<div class="cgs-help-tip">💡 این دسته‌ها بعداً هنگام تعریف هر طرح به‌صورت چک‌باکس ظاهر می‌شوند.</div>
</div>
</details>
        <p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <button type="button" class="cgs-btn-admin cgs-btn-admin-primary" id="cgs-add-category">+ دسته جدید</button>
            <button type="button" class="cgs-btn-admin cgs-btn-admin-success" id="cgs-save-categories">💾 ذخیره همه دسته‌ها</button>
            <button type="button" class="button" id="cgs-reset-categories">ریست به پیش‌فرض</button>
            <span id="cgs-cats-msg"></span>
        </p>
        <div id="cgs-categories-editor" class="cgs-cats-sortable">
            <?php foreach ( $categories as $cat ) : ?>
            <div class="cgs-cat-block" data-id="<?php echo esc_attr( $cat['id'] ); ?>">
                <div class="cgs-cat-head">
                    <span class="dashicons dashicons-menu cgs-cat-handle"></span>
                    <input type="text" class="cgs-cat-title" value="<?php echo esc_attr( $cat['title'] ); ?>" placeholder="عنوان دسته">
                    <button type="button" class="button button-small cgs-add-option">+ گزینه</button>
                    <button type="button" class="button-link cgs-del-cat" style="color:#c00;">حذف دسته</button>
                </div>
                <ul class="cgs-opt-sortable">
                    <?php
                    $opts = $cat['options'] ?? array();
                    usort( $opts, function( $a, $b ) { return ( $a['sort'] ?? 0 ) - ( $b['sort'] ?? 0 ); } );
                    foreach ( $opts as $opt ) :
                        $dt = $opt['detail_type'] ?? 'none';
                    ?>
                    <li class="cgs-opt-item" data-id="<?php echo esc_attr( $opt['id'] ); ?>">
                        <span class="dashicons dashicons-menu cgs-opt-handle"></span>
                        <input type="text" class="cgs-opt-label" value="<?php echo esc_attr( $opt['label'] ); ?>" placeholder="عنوان گزینه" style="min-width:140px;flex:1;">
                        <select class="cgs-opt-dtype">
                            <?php foreach ( $detail_types as $k => $lab ) : ?>
                            <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $dt, $k ); ?>><?php echo esc_html( $lab ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="cgs-opt-detail-wrap">
                            <?php if ( $dt === 'age_range' || $dt === 'number_range' ) :
                                $parts = explode( '-', $opt['detail'] ?? '18-70' );
                            ?>
                                <input type="number" class="cgs-opt-min" value="<?php echo esc_attr( $parts[0] ?? '' ); ?>" placeholder="حداقل" style="width:70px;">
                                <span>تا</span>
                                <input type="number" class="cgs-opt-max" value="<?php echo esc_attr( $parts[1] ?? '' ); ?>" placeholder="حداکثر" style="width:70px;">
                            <?php elseif ( $dt === 'list' ) : ?>
                                <textarea class="cgs-opt-detail" rows="2" placeholder="هر خط یک مقدار" style="width:160px;"><?php echo esc_textarea( $opt['detail'] ?? '' ); ?></textarea>
                            <?php elseif ( $dt !== 'none' ) : ?>
                                <input type="text" class="cgs-opt-detail" value="<?php echo esc_attr( $opt['detail'] ?? '' ); ?>" placeholder="جزئیات" style="width:120px;">
                            <?php endif; ?>
                        </span>
                        <button type="button" class="button-link cgs-del-opt" style="color:#c00;">×</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB: Styles -->
    <div id="tab-style" class="cgs-tab-panel" style="display:none;margin-top:16px;">
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'plans.appearance' ); } ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;">
                <h2 style="margin-top:0;">سفارشی‌سازی ظاهر کارت طرح</h2>
                <div class="cgs-style-row"><label>رنگ پس‌زمینه کارت</label><input type="color" id="ps-card-bg" value="<?php echo esc_attr( $styles['card_bg'] ); ?>"></div>
                <div class="cgs-style-row"><label>رنگ حاشیه</label><input type="color" id="ps-card-border" value="<?php echo esc_attr( $styles['card_border'] ); ?>"></div>
                <div class="cgs-style-row"><label>رنگ عنوان</label><input type="color" id="ps-title-color" value="<?php echo esc_attr( $styles['title_color'] ); ?>"></div>
                <div class="cgs-style-row"><label>اندازه عنوان</label><input type="number" id="ps-title-size" value="<?php echo esc_attr( $styles['title_size'] ); ?>" style="width:70px;"> px</div>
                <div class="cgs-style-row"><label>رنگ متن</label><input type="color" id="ps-text-color" value="<?php echo esc_attr( $styles['text_color'] ); ?>"></div>
                <div class="cgs-style-row"><label>رنگ تاکید</label><input type="color" id="ps-accent" value="<?php echo esc_attr( $styles['accent'] ); ?>"></div>
                <div class="cgs-style-row"><label>گردی گوشه</label><input type="number" id="ps-radius" value="<?php echo esc_attr( $styles['radius'] ); ?>" style="width:70px;"> px</div>
                <div class="cgs-style-row"><label>نمایش آیکن</label><label><input type="checkbox" id="ps-show-icon" <?php checked( $styles['show_icon'], '1' ); ?>> بله</label></div>
                <div class="cgs-style-row"><label>متن دکمه</label><input type="text" id="ps-btn-text" value="<?php echo esc_attr( $styles['btn_text'] ); ?>"></div>
                <div class="cgs-style-row"><label>رنگ دکمه</label><input type="color" id="ps-btn-bg" value="<?php echo esc_attr( $styles['btn_bg'] ); ?>"></div>
                <div class="cgs-style-row"><label>سبک دکمه</label>
                    <select id="ps-btn-style"><option value="solid" <?php selected($styles['btn_style'],'solid'); ?>>ساده</option><option value="glass" <?php selected($styles['btn_style'],'glass'); ?>>شیشه‌ای (Glass)</option></select>
                </div>
                <div class="cgs-style-row"><label>سایه کارت</label>
                    <select id="ps-card-shadow">
                        <option value="none" <?php selected($styles['card_shadow']??'','none'); ?>>بدون سایه</option>
                        <option value="0 2px 8px rgba(15,23,42,0.06)" <?php selected($styles['card_shadow']??'','0 2px 8px rgba(15,23,42,0.06)'); ?>>کم</option>
                        <option value="0 6px 24px rgba(15,23,42,0.08)" <?php selected($styles['card_shadow']??'0 6px 24px rgba(15,23,42,0.08)','0 6px 24px rgba(15,23,42,0.08)'); ?>>متوسط</option>
                        <option value="0 12px 40px rgba(15,23,42,0.14)" <?php selected($styles['card_shadow']??'','0 12px 40px rgba(15,23,42,0.14)'); ?>>زیاد</option>
                    </select>
                </div>
                <div class="cgs-style-row"><label>دکمه شیشه‌ای</label><label><input type="checkbox" id="ps-glass-btn" <?php checked(($styles['glass_btn']??'0'),'1'); ?>> فعال</label></div>
                <div class="cgs-style-row"><label>صدای دکمه</label><label><input type="checkbox" id="ps-btn-sound" <?php checked(($styles['btn_sound']??'0'),'1'); ?>> فعال</label></div>
                <div class="cgs-style-row"><label>رنگ نشان VIP</label><input type="color" id="ps-vip-badge" value="<?php echo esc_attr($styles['vip_badge_color']??'#fbbf24'); ?>"></div>
                <div class="cgs-style-row"><label>تعداد ستاره</label><input type="number" id="ps-star-count" min="1" max="10" value="<?php echo esc_attr($styles['star_count']??'5'); ?>" style="width:70px;"></div>
                <div class="cgs-style-row"><label>رنگ هر ستاره</label>
                    <span id="ps-star-colors">
                    <?php
                    $sc = explode(',', $styles['star_colors'] ?? '#f59e0b,#f59e0b,#f59e0b,#f59e0b,#f59e0b');
                    $sn = max(1, min(10, (int)($styles['star_count']??5)));
                    for ($si=0; $si<$sn; $si++) {
                        $col = $sc[$si] ?? '#f59e0b';
                        echo '<input type="color" class="ps-star-color" value="'.esc_attr($col).'" title="ستاره '.($si+1).'" style="width:28px;height:28px;padding:0;border:none;margin:0 2px;">';
                    }
                    ?>
                    </span>
                </div>
                <div class="cgs-style-row"><label>هایلایت ویژه</label><label><input type="checkbox" id="ps-featured-glow" <?php checked(($styles['featured_glow']??'0'),'1'); ?>> درخشش طرح‌های ویژه</label></div>
                <div class="cgs-style-row"><label>رنگ هایلایت</label><input type="color" id="ps-featured-color" value="<?php echo esc_attr($styles['featured_color']??'#4338ca'); ?>"></div>

                <div class="cgs-design-presets" style="margin:14px 0;padding:12px;background:#f8fafc;border:1px solid #c5cae9;border-radius:12px;">
                    <h3 style="margin:0 0 8px;font-size:13px;color:#1a237e;">📚 بانک قالب‌های ظاهر (قابل استفاده مجدد)</h3>
                    <p class="description" style="margin:0 0 8px;font-size:12px;">ظاهر فعلی را با نام ذخیره کنید و بعداً روی هر طرح اعمال کنید.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <select id="cgs-design-select" style="min-width:180px;max-width:100%;">
                            <option value="">— انتخاب قالب ظاهر —</option>
                            <?php
                            $designs = isset( $designs ) && is_array( $designs ) ? $designs : array();
                            foreach ( $designs as $did => $drow ) :
                                $dn = is_array( $drow ) ? ( $drow['name'] ?? $did ) : $did;
                            ?>
                            <option value="<?php echo esc_attr( is_array($drow) ? ($drow['id']??$did) : $did ); ?>"><?php echo esc_html( $dn ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button" id="cgs-design-apply">اعمال روی پیش‌نمایش</button>
                        <button type="button" class="button" id="cgs-design-delete" style="color:#b91c1c;">حذف قالب</button>
                        <span style="width:1px;height:24px;background:#e2e8f0;"></span>
                        <input type="text" id="cgs-design-name" placeholder="نام قالب ظاهر جدید" style="min-width:140px;">
                        <button type="button" class="button button-primary" id="cgs-design-save">💾 ذخیره به‌عنوان قالب</button>
                        <span id="cgs-design-msg" style="font-size:12px;"></span>
                    </div>
                </div>
                <p style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="button button-primary" id="cgs-save-plan-styles">💾 ذخیره ظاهر</button>
                    <button type="button" class="button" id="cgs-reset-plan-styles">ریست ظاهر</button>
                    <span id="cgs-style-plan-msg"></span>
                </p>
            </div>
            <div class="cgs-panel" style="background:#f8f9fc;border:1px solid #ccd0d4;border-radius:10px;padding:16px;">
                <h2 style="margin-top:0;">👁 پیش‌نمایش ظاهر</h2>
                <div id="cgs-style-preview-card" class="cgs-plan-card-preview">
                    <div class="cgs-pc-icon">📋</div>
                    <div class="cgs-pc-title">نمونه نام طرح</div>
                    <div class="cgs-pc-desc">توضیح کوتاه طرح برای نمایش به متقاضی</div>
                    <div class="cgs-pc-facility" style="display:none;font-size:12.5px;color:#475569;margin:8px 0;padding:8px 10px;background:#f1f5f9;border-radius:8px;"></div>
                    <div class="cgs-pc-durs"><div>۱۲ ماه · سود ۴٫۸٪ · هر ماه یک‌بار</div></div>
                    <button type="button" class="cgs-pc-btn">انتخاب این طرح</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: Plans -->
    <div id="tab-plans" class="cgs-tab-panel" style="margin-top:16px;">
        <div style="display:grid;grid-template-columns:260px 1fr 320px;gap:14px;">
            <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong>فهرست طرح‌ها</strong>
                    <button type="button" class="button button-primary button-small" id="cgs-new-plan">+ جدید</button>
                </div>
                <div id="cgs-plans-list">
                    <?php if ( empty( $plans ) ) : ?>
                        <p style="color:#888;font-size:0.85rem;">هنوز طرحی نیست.</p>
                    <?php else :
                        uasort( $plans, function( $a, $b ) { return ( $a['sort_order'] ?? 0 ) - ( $b['sort_order'] ?? 0 ); } );
                        foreach ( $plans as $p ) :
                            $n = count( $p['durations'] ?? array() );
                            $status = ! empty( $p['active'] ) ? 'فعال' : 'غیرفعال'; // badges below
                            $ic = ! empty( $p['icon'] ) ? '<img src="'.esc_url($p['icon']).'" style="width:24px;height:24px;object-fit:contain;">' : '<span>'.esc_html($p['icon_emoji'] ?? '📋').'</span>';
                    ?>
                    <div class="cgs-plan-item" data-id="<?php echo esc_attr( $p['id'] ); ?>">
                        <?php echo $ic; ?>
                        <div style="flex:1;min-width:0;">
                            <strong><?php echo esc_html( $p['title'] ); ?></strong>
                            <small><?php
                                if ( ! empty( $p['vip'] ) ) echo '<span class="cgs-badge-vip">VIP</span> ';
                                if ( ! empty( $p['featured'] ) ) echo '<span class="cgs-badge-star">★</span> ';
                                echo ! empty( $p['active'] ) ? '<span class="cgs-badge-on">فعال</span>' : '<span class="cgs-badge-off">غیرفعال</span>';
                                echo ( ( $p['status'] ?? 'published' ) === 'draft' ) ? ' <span class="cgs-badge-off" style="background:#fef3c7;color:#92400e;">پیش‌نویس</span>' : ' <span class="cgs-badge-on" style="background:#dbeafe;color:#1e40af;">منتشرشده</span>';
                            ?> · <?php echo (int)$n; ?> دوره</small>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:14px;" id="cgs-plan-editor">
                <h2 style="margin-top:0;font-size:1.05rem;" id="cgs-plan-editor-title">طرح جدید</h2>
                <form id="cgs-plan-form">
                    <input type="hidden" id="plan_id" value="">
                    <div style="display:grid;grid-template-columns:1fr 70px;gap:8px;">
                        <div><label><strong>نام طرح *</strong></label><input type="text" id="plan_title" style="width:100%;" placeholder="مثلاً طرح بازنشستگان"></div>
                        <div><label><strong>ترتیب</strong></label><input type="number" id="plan_sort" value="0" style="width:100%;"></div>
                    </div>
                    <p style="margin-top:6px;"><label><strong>توضیحات</strong></label><textarea id="plan_desc" rows="2" style="width:100%;"></textarea></p>
                    <div style="display:grid;grid-template-columns:60px 1fr auto;gap:6px;align-items:end;margin-bottom:8px;">
                        <div><label>ایموجی</label><input type="text" id="plan_icon_emoji" value="📋" style="width:100%;text-align:center;font-size:1.2rem;"></div>
                        <div><label>تصویر آیکن</label><input type="url" id="plan_icon" style="width:100%;" dir="ltr"></div>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="button" id="cgs-pick-icon">رسانه</button>
                            <label><input type="checkbox" id="plan_active" checked> ✅ فعال</label>
                            <label><input type="checkbox" id="plan_featured"> ⭐ ویژه / ستاره‌دار</label>
                            <label><input type="checkbox" id="plan_vip"> 👑 VIP</label>
                        </div>
                    </div>
                    <div id="cgs-plan-design-box" style="margin:12px 0;padding:14px;background:#eef2ff;border:2px solid #3949ab;border-radius:12px;clear:both;width:100%;box-sizing:border-box;">
                        <label for="plan_design_id" style="display:block;font-weight:800;color:#1a237e;margin-bottom:8px;font-size:13px;">🎨 قالب ظاهر این طرح</label>
                        <select id="plan_design_id" name="plan_design_id" style="display:block;width:100%;min-height:40px;padding:10px 12px;border-radius:8px;border:1px solid #3949ab;font-size:14px;background:#fff;">
                            <option value="">— پیش‌فرض سراسری (تب ظاهر) —</option>
                            <?php
                            $__designs = isset( $designs ) && is_array( $designs ) ? $designs : array();
                            if ( empty( $__designs ) ) {
                                echo '<option value="" disabled>هنوز قالبی ذخیره نشده — بروید به تب «ظاهر و پیش‌نمایش»</option>';
                            }
                            foreach ( $__designs as $__did => $__d ) :
                                $__dn = is_array( $__d ) ? ( $__d['name'] ?? $__did ) : $__did;
                                $__id = is_array( $__d ) ? ( $__d['id'] ?? $__did ) : $__did;
                            ?>
                            <option value="<?php echo esc_attr( $__id ); ?>"><?php echo esc_html( $__dn ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="cgs-design-link-msg" style="margin:8px 0 0;font-size:12px;font-weight:600;"></p>
                        <details class="cgs-help" style="margin-top:10px;background:#fff;border-radius:8px;padding:8px 10px;border:1px solid #c5cae9;">
                            <summary style="cursor:pointer;font-weight:700;color:#1a237e;">❓ راهنما: اتصال قالب ظاهر به طرح</summary>
                            <ol style="margin:8px 0 0;padding-right:18px;font-size:12px;line-height:1.7;color:#334155;">
                                <li>به تب <strong>ظاهر و پیش‌نمایش</strong> بروید.</li>
                                <li>رنگ، سایه، دکمه شیشه‌ای، VIP و… را تنظیم کنید.</li>
                                <li>نام بدهید و <strong>ذخیره به‌عنوان قالب</strong> را بزنید.</li>
                                <li>به تب <strong>طرح‌ها</strong> برگردید، طرح را باز کنید.</li>
                                <li>از لیست بالا قالب را انتخاب کنید — پیش‌نمایش سمت چپ باید عوض شود.</li>
                                <li>حتماً <strong>ذخیره طرح</strong> را بزنید تا اتصال ماندگار شود.</li>
                            </ol>
                        </details>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                        <div><label><strong>حداقل مبلغ (ریال)</strong></label><input type="number" id="plan_min" value="0" style="width:100%;"></div>
                        <div><label><strong>حداکثر مبلغ (ریال)</strong></label><input type="number" id="plan_max" value="0" style="width:100%;"></div>
                    </div>

                    <h3 class="cgs-plan-sec">مدت‌های بازپرداخت اقساط (با سود جداگانه)</h3>
                    <p class="description">مثلاً ۶ ماه با سود ۵٫۷٪ و ۱۲ ماه با سود ۴٫۸٪ — برای هر کدام فاصله اقساط جدا.</p>
                    <div id="cgs-durations-list-help"></div>
<details class="cgs-help" open><summary>راهنما: دوره بازپرداخت و گام‌های سود</summary><div class="cgs-help-body">
<p>هر <strong>دوره</strong> (مثلاً ۶ یا ۱۲ ماه) می‌تواند چند <strong>گام پرداخت</strong> داشته باشد (هر ۱ ماه، هر ۲ ماه، …) و هر گام ضریب سود جدا دارد.</p>
<ol>
<li>دکمه «+ دوره» را بزنید و تعداد ماه را وارد کنید.</li>
<li>داخل همان دوره «+ گام» را بزنید و فاصله پرداخت + درصد سود را تنظیم کنید.</li>
<li>مثال: دوره ۶ ماهه → گام ماهانه ۶٪ و گام دوماهه ۶٫۲٪</li>
</ol>
</div></details>
<div id="cgs-durations-list"></div>
                    <button type="button" class="button" id="cgs-add-duration">+ افزودن مدت بازپرداخت</button>

                    <h3 class="cgs-plan-sec">مهلت استفاده از اعتبار تصویب‌شده</h3>
                    <p class="description">پس از تأیید، متقاضی تا چند ماه فرصت دارد از اعتبار استفاده کند (متفاوت از مدت بازپرداخت اقساط).</p>
                    <input type="number" id="plan_facility_use" value="12" min="1" style="width:100px;"> <span>ماه</span>

                    <div style="display:flex;gap:20px;margin-top:12px;">
                        <label><input type="checkbox" id="plan_prepayment"> پیش‌پرداخت دارد</label>
                        <label><input type="checkbox" id="plan_guarantor"> نیاز به ضامن دارد</label>

                        <div class="cgs-plan-sec-box" style="margin-top:10px;">
                          <h3>📋 وضعیت انتشار و اتصال قالب فرم</h3>
                          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                              <label for="plan_status" style="font-weight:600;font-size:12px;">وضعیت طرح</label>
                              <select id="plan_status" style="width:100%;min-height:38px;">
                                <option value="draft">پیش‌نویس</option>
                                <option value="published">منتشرشده</option>
                              </select>
                            </div>
                            <div>
                              <label for="plan_form_template_id" style="font-weight:600;font-size:12px;">قالب فرم‌ساز (اختیاری)</label>
                              <select id="plan_form_template_id" style="width:100%;min-height:38px;">
                                <option value="">— بدون اتصال —</option>
                                <?php
                                if ( class_exists( 'CGS_Form_Templates' ) && method_exists( 'CGS_Form_Templates', 'get_all' ) ) {
                                  $tpls = CGS_Form_Templates::get_all();
                                  if ( is_array( $tpls ) ) {
                                    foreach ( $tpls as $tpl ) {
                                      $tid = is_array($tpl) ? ( $tpl['id'] ?? $tpl['ID'] ?? '' ) : '';
                                      $tname = is_array($tpl) ? ( $tpl['name'] ?? $tpl['title'] ?? $tid ) : '';
                                      if ( $tid ) echo '<option value="'.esc_attr($tid).'">'.esc_html($tname).'</option>';
                                    }
                                  }
                                }
                                ?>
                              </select>
                            </div>
                          </div>
                          <div style="margin-top:10px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
                            <label style="font-weight:700;font-size:12px;color:#92400e;">قاعده ساده مبلغ</label>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:6px;">
                              <span style="font-size:12px;">اگر مبلغ درخواستی بیشتر از</span>
                              <input type="number" id="plan_rule_amount_gt" min="0" step="1000000" value="0" style="width:140px;" placeholder="۰ = خاموش">
                              <span style="font-size:12px;">ریال باشد ←</span>
                              <label style="font-size:12px;"><input type="checkbox" id="plan_rule_force_guarantor"> الزام ضامن</label>
                            </div>
                            <p style="margin:6px 0 0;font-size:11px;color:#78716c;">۰ یعنی قاعده غیرفعال است. این تنظیم همراه طرح ذخیره می‌شود.</p>
                          </div>
                        </div>

                    </div>

                    <div id="cgs-plan-categories-select">
                        <?php foreach ( $categories as $cat ) : ?>
                        <h3 class="cgs-plan-sec"><?php echo esc_html( $cat['title'] ); ?></h3>
                        <div class="cgs-check-grid" data-cat="<?php echo esc_attr( $cat['id'] ); ?>">
                            <?php foreach ( $cat['options'] ?? array() as $opt ) :
                                $dt = $opt['detail_type'] ?? 'none';
                                $detail_hint = '';
                                if ( $dt === 'age_range' || $dt === 'number_range' ) $detail_hint = 'بازه: ' . ( $opt['detail'] ?? '' );
                                elseif ( $dt === 'list' ) $detail_hint = 'مقادیر: ' . str_replace( "\n", '، ', $opt['detail'] ?? '' );
                                elseif ( $dt !== 'none' && ! empty( $opt['detail'] ) ) $detail_hint = $opt['detail'];
                            ?>
                            <label class="cgs-opt-select-row">
                                <input type="checkbox" class="plan-sel-opt" data-cat="<?php echo esc_attr( $cat['id'] ); ?>" value="<?php echo esc_attr( $opt['id'] ); ?>">
                                <span><?php echo esc_html( $opt['label'] ); ?></span>
                                <?php if ( $detail_hint ) : ?><small style="color:#666;display:block;"><?php echo esc_html( $detail_hint ); ?></small><?php endif; ?>
                                <?php if ( $dt === 'age_range' || $dt === 'number_range' ) : ?>
                                <span class="cgs-plan-detail-edit" style="display:none;margin-top:4px;">
                                    <input type="number" class="plan-det-min" placeholder="حداقل" style="width:60px;"> –
                                    <input type="number" class="plan-det-max" placeholder="حداکثر" style="width:60px;">
                                </span>
                                <?php elseif ( $dt === 'list' ) : ?>
                                <span class="cgs-plan-detail-edit" style="display:none;margin-top:4px;">
                                    <textarea class="plan-det-list" rows="2" placeholder="رتبه‌های مجاز (هر خط)" style="width:100%;font-size:0.8rem;"></textarea>
                                </span>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cgs-plan-sec-box">
                    <h3>کلید فیلدهای فرم مرتبط</h3>
                    <details class="cgs-help" open><summary>این قسمت چیست؟</summary><div class="cgs-help-body">
                    <p><strong>کلید فیلد</strong> همان شناسه انگلیسی فیلد در فرم‌ساز است (مثلاً <code>full_name</code>، <code>mobile</code>، <code>national_id</code>).</p>
                    <p><strong>کاربرد:</strong> وقتی متقاضی این طرح را انتخاب می‌کند، فقط فیلدهایی که کلیدشان اینجا نوشته شده در فرم نمایش داده می‌شوند. بقیه فیلدها مخفی می‌مانند.</p>
                    <ol>
                    <li>به فرم‌ساز بروید و کلید هر فیلد را از لیست فیلدها ببینید.</li>
                    <li>کلیدها را با کاما جدا کنید: <code>full_name,mobile,national_id</code></li>
                    <li>اگر خالی بگذارید، همه فیلدهای آن مخاطب نمایش داده می‌شوند.</li>
                    </ol>
                    <div class="cgs-help-tip">💡 برای طرح بازنشستگان می‌توانید فقط فیلدهای لازم همان طرح را لیست کنید تا فرم کوتاه‌تر شود.</div>
                    </div></details>
                    <input type="text" id="plan_field_keys" style="width:100%;" dir="ltr" placeholder="full_name,mobile,national_id">
                    </div>

                    <p style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="button button-primary">ذخیره طرح</button>
                        <button type="button" class="button" id="cgs-delete-plan" style="display:none;color:#b71c1c;">حذف</button>
                        <button type="button" class="button" id="cgs-reset-plan-form">ریست فرم</button>
                        <span id="cgs-plan-msg"></span>
                    </p>
                </form>
            </div>

            <!-- Live preview -->
            <div class="cgs-panel" style="background:#f8f9fc;border:1px solid #ccd0d4;border-radius:10px;padding:12px;position:sticky;top:32px;max-height:92vh;overflow-y:auto;">
                <h2 style="margin:0 0 8px;font-size:0.95rem;color:#1a237e;">👁 پیش‌نمایش کامل طرح</h2>
                <div id="cgs-plan-preview-card" class="cgs-plan-card-preview">
                    <div class="cgs-pc-icon">📋</div>
                    <div class="cgs-pc-badges" style="margin-bottom:6px;"></div><div class="cgs-pc-title">نام طرح</div>
                    <div class="cgs-pc-desc">توضیحات</div>
                    <div class="cgs-pc-section"><strong>بازپرداخت:</strong><div class="cgs-pc-facility" style="display:none;font-size:12.5px;color:#475569;margin:8px 0;padding:8px 10px;background:#f1f5f9;border-radius:8px;"></div>
                    <div class="cgs-pc-durs"></div></div>
                    <div class="cgs-pc-section cgs-pc-extra"></div>
                    <div class="cgs-pc-meta"></div>
                    <button type="button" class="cgs-pc-btn">انتخاب این طرح</button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.cgs-plan-sec{margin:16px 0 8px;border-bottom:2px solid #e8eaf6;padding-bottom:5px;font-size:0.92rem}
.cgs-check-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.cgs-opt-select-row{display:block;padding:8px;background:#f5f7ff;border-radius:8px;font-size:0.85rem}
.cgs-plan-item{display:flex;gap:8px;align-items:center;padding:8px;border:1px solid #e8eaf0;border-radius:8px;margin-bottom:6px;cursor:pointer}
.cgs-plan-item strong{display:block;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cgs-plan-item small{color:#666;font-size:0.75rem}
.cgs-plan-item:hover,.cgs-plan-item.active{background:#eef0ff;border-color:#9fa8da}
.cgs-duration-row{display:grid;grid-template-columns:1fr 1fr 1.3fr auto;gap:6px;align-items:end;margin-bottom:6px;padding:8px;background:#eef0ff;border-radius:8px}
.cgs-duration-row label{font-size:0.72rem;display:block;margin-bottom:2px;color:#555}
.cgs-cat-block{background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:12px;margin-bottom:12px}
.cgs-cat-head{display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap}
.cgs-cat-title{flex:1;min-width:160px;font-weight:700}
.cgs-opt-item{display:flex;align-items:flex-start;gap:6px;padding:8px 0;border-bottom:1px solid #f0f0f0;flex-wrap:wrap}
.cgs-opt-sortable,.cgs-cats-sortable{list-style:none;margin:0;padding:0}
.cgs-plan-card-preview{background:#fff;border:1px solid #c5cae9;border-radius:14px;padding:16px;text-align:center;box-shadow:0 6px 20px rgba(26,35,126,.08)}
.cgs-pc-icon{font-size:2rem;margin-bottom:6px}
.cgs-pc-icon img{width:48px;height:48px;object-fit:contain}
.cgs-pc-title{font-size:1.1rem;font-weight:800;color:#1a237e;margin-bottom:4px}
.cgs-pc-desc{font-size:0.85rem;color:#555;margin-bottom:10px;line-height:1.5}
.cgs-pc-section{text-align:right;font-size:0.8rem;margin-bottom:8px}
.cgs-pc-durs div{background:#e8eaf6;border-radius:6px;padding:4px 8px;margin:3px 0}
.cgs-pc-meta{font-size:0.78rem;color:#666;margin:8px 0;text-align:right}
.cgs-pc-btn{display:inline-block;padding:8px 16px;border:none;border-radius:8px;background:#1a237e;color:#fff;font-weight:700;cursor:pointer;margin-top:6px}
.cgs-style-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.cgs-style-row label{min-width:140px;font-size:0.88rem}
</style>
