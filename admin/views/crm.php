<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$contacts = CGS_CRM::get_contacts();
$stages = CGS_CRM::stages();
$external = cgs_get_option( 'crm_external_provider', '' );
// Count by stage
$by_stage = array();
foreach ( $stages as $k => $lab ) $by_stage[ $k ] = 0;
foreach ( $contacts as $c ) {
    $s = $c['stage'] ?? 'lead';
    if ( ! isset( $by_stage[ $s ] ) ) $by_stage[ $s ] = 0;
    $by_stage[ $s ]++;
}
$filter_stage = isset( $_GET['stage'] ) ? sanitize_key( $_GET['stage'] ) : '';
$filter_type  = isset( $_GET['ctype'] ) ? sanitize_key( $_GET['ctype'] ) : '';
?>
<div class="wrap">
    <h1>CRM — مدیریت ارتباط با مشتری</h1>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'crm' ); } ?>
    <p class="description">مخاطبین، قیف فروش، فعالیت‌ها و اتصال به CRM خارجی.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin:16px 0;">
        <?php foreach ( $stages as $k => $lab ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cgs-crm&stage=' . $k ) ); ?>" style="text-decoration:none;color:inherit;">
            <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:14px;text-align:center;<?php echo $filter_stage===$k?'border-color:#1a237e;box-shadow:0 0 0 2px #c5cae9;':''; ?>">
                <div style="font-size:1.5rem;font-weight:800;color:#1a237e;"><?php echo (int)$by_stage[$k]; ?></div>
                <div style="font-size:0.82rem;"><?php echo esc_html( $lab ); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    
    <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;margin-bottom:16px;max-width:420px;">
        <h3 style="margin-top:0;"><?php $cf = function_exists("cgs_get_chart_format") ? cgs_get_chart_format() : array(); echo esc_html( $cf["title_crm"] ?? "نمودار قیف فروش" ); ?></h3>
        <canvas id="cgsCrmChart" height="200"></canvas>
    </div>
    <script>
    jQuery(function($){
        if (typeof Chart === 'undefined') return;
        var labels = <?php echo wp_json_encode( array_values( $stages ) ); ?>;
        var data = <?php echo wp_json_encode( array_values( $by_stage ) ); ?>;
        var colors = <?php echo wp_json_encode( array_values( function_exists('cgs_get_crm_stage_colors') ? cgs_get_crm_stage_colors() : array('#90caf9','#64b5f6','#42a5f5','#1e88e5','#43a047','#e53935') ) ); ?>;
        var cf = <?php echo wp_json_encode( function_exists('cgs_charts_get_format') ? cgs_charts_get_format() : ( function_exists('cgs_get_chart_format') ? cgs_get_chart_format() : array() ) ); ?>;
        var crmType = cf.status_type || 'doughnut';
        if (crmType === 'horizontalBar') crmType = 'bar';
        var crmCfg = {
            type: crmType,
            data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: parseInt(cf.border_width,10)||2, borderColor: cf.border_color || '#fff', borderRadius: parseInt(cf.bar_radius,10)||6 }] },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: parseFloat(cf.aspect_ratio) || 1.2,
                cutout: (crmType === 'doughnut') ? ((cf.cutout || 55) + '%') : undefined,
                animation: (cf.animation === '1' || cf.animation === 1) ? { duration: parseInt(cf.anim_duration,10)||800 } : false,
                indexAxis: (cf.status_type === 'horizontalBar') ? 'y' : 'x',
                plugins: {
                    legend: {
                        display: cf.show_legend !== '0',
                        position: cf.legend_position || 'bottom',
                        rtl: true,
                        labels: { font: { family: cf.font_family || 'Vazirmatn, Tahoma, sans-serif', size: parseInt(cf.font_size,10)||11 } }
                    },
                    title: {
                        display: cf.show_title === '1' || cf.show_title === 1,
                        text: cf.title_crm || 'قیف فروش',
                        font: { family: cf.font_family || 'Vazirmatn, Tahoma, sans-serif', size: (parseInt(cf.font_size,10)||11)+2 }
                    }
                }
            }
        };
        if (crmType === 'bar' || crmType === 'line') {
            crmCfg.options.scales = {
                x: { grid: { display: cf.show_grid !== '0' } },
                y: { beginAtZero: true, grid: { display: cf.show_grid !== '0' }, ticks: { stepSize: 1 } }
            };
        }
        new Chart(document.getElementById('cgsCrmChart'), crmCfg);
    });
    </script>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
        <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h2 style="margin:0;">مخاطبین</h2>
                <button type="button" class="button button-primary" id="cgs-crm-add">+ مخاطب جدید</button>
            </div>
            <table class="widefat striped" id="cgs-crm-table">
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>موبایل</th>
                        <th>نوع</th>
                        <th>مرحله</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $shown = 0;
                foreach ( $contacts as $c ) :
                    if ( $filter_stage && ( $c['stage'] ?? '' ) !== $filter_stage ) continue;
                    if ( $filter_type && ( $c['type'] ?? '' ) !== $filter_type ) continue;
                    $shown++;
                ?>
                    <tr data-id="<?php echo (int)$c['id']; ?>">
                        <td><strong><?php echo esc_html( $c['name'] ); ?></strong>
                            <?php if ( ! empty( $c['notes'] ) ) : ?><br><small style="color:#888;"><?php echo esc_html( wp_trim_words( $c['notes'], 12 ) ); ?></small><?php endif; ?>
                        </td>
                        <td dir="ltr"><?php echo esc_html( $c['mobile'] ); ?></td>
                        <td><?php echo esc_html( $c['type'] ); ?></td>
                        <td>
                            <select class="cgs-crm-stage" data-id="<?php echo (int)$c['id']; ?>">
                                <?php foreach ( $stages as $sk => $sl ) : ?>
                                <option value="<?php echo esc_attr($sk); ?>" <?php selected( $c['stage'] ?? '', $sk ); ?>><?php echo esc_html($sl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="button button-small cgs-crm-note" data-id="<?php echo (int)$c['id']; ?>">یادداشت</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ( ! $shown ) : ?>
                    <tr><td colspan="5" style="text-align:center;color:#888;">مخاطبی یافت نشد.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;margin-bottom:12px;">
                <h3 style="margin-top:0;">افزودن مخاطب</h3>
                <p><input type="text" id="crm-name" placeholder="نام" style="width:100%;"></p>
                <p><input type="text" id="crm-mobile" placeholder="موبایل" style="width:100%;" dir="ltr"></p>
                <p><input type="email" id="crm-email" placeholder="ایمیل" style="width:100%;" dir="ltr"></p>
                <p>
                    <select id="crm-type" style="width:100%;">
                        <option value="applicant">متقاضی اعتبار</option>
                        <option value="representative">نماینده</option>
                        <option value="seller">فروشنده</option>
                        <option value="marketer">بازاریاب</option>
                        <option value="investor">سرمایه‌گذار</option>
                    </select>
                </p>
                <p><select id="crm-stage" style="width:100%;">
                    <?php foreach ( $stages as $sk => $sl ) : ?>
                    <option value="<?php echo esc_attr($sk); ?>"><?php echo esc_html($sl); ?></option>
                    <?php endforeach; ?>
                </select></p>
                <p><textarea id="crm-notes" rows="2" placeholder="یادداشت" style="width:100%;"></textarea></p>
                <p><button type="button" class="button button-primary" id="crm-save-btn">ذخیره مخاطب</button></p>
            </div>
            <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;">
                <h3 style="margin-top:0;">CRM خارجی</h3>
                <p>وضعیت: <strong><?php echo $external ? esc_html( $external ) : 'فقط داخلی'; ?></strong></p>
                <p class="description">از مسیر تنظیمات می‌توانید Webhook، دیدار، شمسی، Bitrix24 یا HubSpot را انتخاب کنید.</p>
                <p>هوک‌ها: <code>cgs_crm_sync_external</code> · <code>cgs_crm_contact_saved</code> · <code>cgs_crm_stage_changed</code></p>
            </div>
        </div>
    </div>
</div>
<script>
jQuery(function($){
    $('#crm-save-btn').on('click', function(){
        $.post(ajaxurl, {
            action: 'cgs_crm_save_contact',
            nonce: '<?php echo wp_create_nonce( "cgs_admin_nonce" ); ?>',
            name: $('#crm-name').val(),
            mobile: $('#crm-mobile').val(),
            email: $('#crm-email').val(),
            type: $('#crm-type').val(),
            stage: $('#crm-stage').val(),
            notes: $('#crm-notes').val()
        }).done(function(res){
            if (res.success) location.reload();
            else alert(res.data || 'خطا');
        });
    });
    $(document).on('change', '.cgs-crm-stage', function(){
        var id = $(this).data('id'), stage = $(this).val();
        $.post(ajaxurl, {
            action: 'cgs_crm_update_stage',
            nonce: '<?php echo wp_create_nonce( "cgs_admin_nonce" ); ?>',
            id: id,
            stage: stage
        });
    });
    $(document).on('click', '.cgs-crm-note', function(){
        var id = $(this).data('id');
        var note = prompt('یادداشت جدید:');
        if (!note) return;
        $.post(ajaxurl, {
            action: 'cgs_crm_add_activity',
            nonce: '<?php echo wp_create_nonce( "cgs_admin_nonce" ); ?>',
            contact_id: id,
            type: 'note',
            content: note
        }).done(function(res){ if(res.success) alert('ثبت شد'); });
    });
});
</script>
