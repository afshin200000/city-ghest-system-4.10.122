<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$report = get_option( CGS_SEO_Engine::OPT_REPORT, array() );
$hist   = get_option( CGS_SEO_Engine::OPT_HISTORY, array() );
$s      = CGS_SEO_Engine::settings();
$nonce  = wp_create_nonce( 'cgs_seo_engine' );
$ajax   = admin_url( 'admin-ajax.php' );
?>
<div class="wrap cgs-seo-engine-wrap" dir="rtl" style="max-width:1100px">
  <h1 style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span>موتور سئو هوشمند</span>
    <span style="font-size:12px;font-weight:600;background:#e0e7ff;color:#3730a3;padding:4px 10px;border-radius:999px">SEO Engine</span>
  </h1>
  <p style="color:#475569;line-height:1.7;max-width:900px">
    این ماژول سایت را ممیزی می‌کند، گزارش خودش را نقد می‌کند، پیشنهاد می‌دهد،
    <strong>اصلاحات امن را می‌تواند خودکار اجرا کند</strong>، دوباره امتیاز می‌دهد و
    <strong>سطح رتبه‌بندی داخلی (A تا E)</strong> را نشان می‌دهد.
    رتبه واقعی گوگل فقط از Search Console دیده می‌شود — موتور ادعای دروغین رتبه خریداری‌شده ندارد.
  </p>

  <div style="display:flex;flex-wrap:wrap;gap:10px;margin:16px 0">
    <button type="button" class="button button-primary button-hero" id="cgs-se-run">🔍 اجرای ممیزی + خودترمیمی امن</button>
    <button type="button" class="button button-hero" id="cgs-se-run-only">فقط ممیزی (بدون اعمال)</button>
    <a class="button" href="<?php echo esc_url( home_url( '/cgs-sitemap.xml' ) ); ?>" target="_blank" rel="noopener">نقشه سایت XML</a>
  </div>
  <p id="cgs-se-msg" style="min-height:1.4em;color:#0f766e;font-weight:600"></p>

  <div id="cgs-se-dashboard" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:20px">
    <?php
    $rank = is_array( $report ) ? ( $report['ranking'] ?? array() ) : array();
    $sa = intval( $report['score_after'] ?? ( $report['audit']['score'] ?? 0 ) );
    $sb = intval( $report['score_before'] ?? 0 );
    $level = $rank['level'] ?? '—';
    $label = $rank['label'] ?? 'هنوز اجرا نشده';
    ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;box-shadow:0 4px 14px rgba(15,23,42,.05)">
      <div style="font-size:12px;color:#64748b">امتیاز فعلی</div>
      <div style="font-size:2rem;font-weight:800;color:#1e3a8a"><?php echo (int) $sa; ?><span style="font-size:1rem;color:#94a3b8">/100</span></div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px">
      <div style="font-size:12px;color:#64748b">قبل → بعد (آخرین چرخه)</div>
      <div style="font-size:1.4rem;font-weight:800"><?php echo (int) $sb; ?> → <?php echo (int) $sa; ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#1e3a8a,#4f46e5);color:#fff;border-radius:14px;padding:16px">
      <div style="font-size:12px;opacity:.9">سطح رتبه‌بندی داخلی</div>
      <div style="font-size:2rem;font-weight:900"><?php echo esc_html( $level ); ?></div>
      <div style="font-size:12px;margin-top:4px"><?php echo esc_html( $label ); ?></div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px">
      <div style="font-size:12px;color:#64748b">آخرین اجرا</div>
      <div style="font-weight:700"><?php echo esc_html( $report['time'] ?? '—' ); ?></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px">
      <h2 style="margin-top:0">نقد خود موتور</h2>
      <ul id="cgs-se-critique" style="line-height:1.8;color:#334155">
        <?php foreach ( (array) ( $report['critique'] ?? array( 'هنوز ممیزی اجرا نشده است.' ) ) as $line ) : ?>
          <li><?php echo esc_html( $line ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px">
      <h2 style="margin-top:0">پیشنهادها و اعمال</h2>
      <ul id="cgs-se-suggestions" style="line-height:1.75;padding-right:18px">
        <?php foreach ( (array) ( $report['suggestions'] ?? array() ) as $sg ) : ?>
          <li style="margin-bottom:8px">
            <strong><?php echo esc_html( $sg['title'] ?? '' ); ?></strong>
            <?php if ( ! empty( $sg['auto'] ) ) : ?><span style="background:#dcfce7;color:#166534;font-size:11px;padding:2px 6px;border-radius:6px">خودکار</span><?php endif; ?>
            <div style="font-size:12px;color:#64748b"><?php echo esc_html( $sg['action'] ?? '' ); ?></div>
          </li>
        <?php endforeach; ?>
        <?php if ( empty( $report['suggestions'] ) ) : ?>
          <li style="color:#94a3b8">پس از اجرا اینجا پر می‌شود.</li>
        <?php endif; ?>
      </ul>
      <?php if ( ! empty( $report['applied_safe'] ) ) : ?>
        <p style="font-size:12px;color:#0f766e">اعمال‌شده در آخرین چرخه: <?php echo esc_html( implode( ', ', (array) $report['applied_safe'] ) ); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-top:16px">
    <h2 style="margin-top:0">مسائل شناسایی‌شده</h2>
    <table class="widefat striped" id="cgs-se-issues">
      <thead><tr><th>سطح</th><th>عنوان</th><th>جزئیات</th><th>حوزه</th></tr></thead>
      <tbody>
        <?php foreach ( (array) ( $report['audit']['issues'] ?? array() ) as $iss ) : ?>
          <tr>
            <td><?php echo esc_html( $iss['level'] ?? '' ); ?></td>
            <td><?php echo esc_html( $iss['title'] ?? '' ); ?></td>
            <td><?php echo esc_html( $iss['detail'] ?? '' ); ?></td>
            <td><?php echo esc_html( $iss['area'] ?? '' ); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-top:16px">
    <h2 style="margin-top:0">تنظیمات موتور</h2>
    <form id="cgs-se-settings" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:800px">
      <label><input type="checkbox" name="auto_audit" value="1" <?php checked( ! empty( $s['auto_audit'] ) ); ?>> ممیزی زمان‌بندی‌شده (روزانه)</label>
      <label><input type="checkbox" name="auto_fix_safe" value="1" <?php checked( ! empty( $s['auto_fix_safe'] ) ); ?>> اعمال خودکار اصلاحات امن</label>
      <label style="grid-column:1/-1">توضیح پیش‌فرض سایت (برای خانه و متا)
        <textarea name="site_description" rows="2" class="large-text"><?php echo esc_textarea( $s['site_description'] ); ?></textarea>
      </label>
      <label>نام سازمان (Schema)
        <input type="text" name="org_name" class="regular-text" value="<?php echo esc_attr( $s['org_name'] ); ?>">
      </label>
      <label>robots پیش‌فرض
        <input type="text" name="default_robots" class="regular-text" value="<?php echo esc_attr( $s['default_robots'] ); ?>">
      </label>
      <label style="grid-column:1/-1">کلید IndexNow (اختیاری)
        <input type="text" name="indexnow_key" class="large-text" value="<?php echo esc_attr( $s['indexnow_key'] ); ?>" placeholder="برای اعلام URL به بینگ/یاران IndexNow">
      </label>
      <p style="grid-column:1/-1"><button type="submit" class="button button-primary">ذخیره تنظیمات</button></p>
    </form>
  </div>

  <?php if ( ! empty( $hist ) ) : ?>
  <div style="margin-top:16px">
    <h2>تاریخچه چرخه‌ها</h2>
    <ol style="line-height:1.8">
      <?php foreach ( array_reverse( $hist ) as $h ) : ?>
        <li><?php echo esc_html( ( $h['time'] ?? '' ) . ' — ' . ( $h['before'] ?? '' ) . '→' . ( $h['after'] ?? '' ) . ' [' . ( $h['rank'] ?? '' ) . ']' ); ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
  <?php endif; ?>
</div>
<script>
(function($){
  var nonce = <?php echo wp_json_encode( $nonce ); ?>;
  var ajax = <?php echo wp_json_encode( $ajax ); ?>;
  function run(apply){
    $('#cgs-se-msg').text('در حال اجرا…');
    $.post(ajax, { action: 'cgs_seo_engine_run', nonce: nonce, apply_safe: apply ? 1 : 0 })
      .done(function(res){
        if (res && res.success) {
          $('#cgs-se-msg').text('انجام شد — امتیاز: ' + (res.data.score_after||0) + ' | سطح: ' + ((res.data.ranking&&res.data.ranking.level)||'—'));
          setTimeout(function(){ location.reload(); }, 700);
        } else {
          $('#cgs-se-msg').text('خطا در اجرا');
        }
      }).fail(function(){ $('#cgs-se-msg').text('خطای شبکه'); });
  }
  $('#cgs-se-run').on('click', function(){ run(true); });
  $('#cgs-se-run-only').on('click', function(){ run(false); });
  $('#cgs-se-settings').on('submit', function(e){
    e.preventDefault();
    var data = $(this).serializeArray();
    var payload = { action: 'cgs_seo_engine_save_settings', nonce: nonce };
    data.forEach(function(f){ payload[f.name] = f.value; });
    if (!$('[name=auto_audit]').is(':checked')) payload.auto_audit = 0;
    if (!$('[name=auto_fix_safe]').is(':checked')) payload.auto_fix_safe = 0;
    $.post(ajax, payload).done(function(res){
      $('#cgs-se-msg').text(res && res.success ? 'تنظیمات ذخیره شد' : 'خطا');
    });
  });
})(jQuery);
</script>
