<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1>پایش عملکرد کوئری‌ها (City Ghest)</h1>

<div id="cgs-smart-monitor-embed" class="cgs-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:12px;padding:16px;margin-bottom:20px;" dir="rtl">
  <h2 style="margin-top:0;color:#1a237e;">🧠 پایش هوشمند ماژول‌ها</h2>
  <p style="font-size:13px;color:#475569;line-height:1.7;">
    <strong>تفاوت:</strong>
    «پایش عملکرد» = سرعت و کوئری‌های دیتابیس افزونه.
    «پایش هوشمند» = سلامت بارگذاری ماژول‌ها، فایل Chart.js، و تنظیمات خراب — با راه‌حل فارسی.
  </p>
  <p>
    <button type="button" class="button button-primary" id="cgs-mon-run">اجرای پایش هوشمند اکنون</button>
    <span id="cgs-mon-msg" style="margin-right:10px;"></span>
  </p>
  <?php
  $cgs_mon_report = get_option( 'cgs_monitor_last_report', array() );
  $cgs_mon_boot = get_option( 'cgs_module_last_boot', array() );
  $cgs_mon_issues = is_array( $cgs_mon_report ) ? ( $cgs_mon_report['issues'] ?? array() ) : array();
  if ( ! empty( $cgs_mon_boot['time'] ) ) {
    echo '<p style="font-size:12px;color:#64748b;">آخرین بوت ماژول: <code>' . esc_html( $cgs_mon_boot['time'] ) . '</code> — تعداد: ' . (int)( $cgs_mon_boot['count'] ?? 0 ) . '</p>';
  }
  echo '<div id="cgs-mon-results">';
  if ( empty( $cgs_mon_issues ) ) {
    echo '<p style="color:#64748b;">هنوز پایشی اجرا نشده. دکمه را بزنید.</p>';
  } else {
    foreach ( $cgs_mon_issues as $iss ) {
      $c = ( $iss['severity'] ?? '' ) === 'error' ? '#fee2e2' : ( ( $iss['severity'] ?? '' ) === 'warning' ? '#fef3c7' : '#dcfce7' );
      echo '<div style="background:' . esc_attr( $c ) . ';border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;margin-bottom:8px;">';
      echo '<strong>' . esc_html( $iss['title'] ?? '' ) . '</strong> <span style="font-size:11px;color:#64748b;">[' . esc_html( $iss['module'] ?? '' ) . ']</span>';
      echo '<div style="font-size:13px;margin-top:4px;">' . esc_html( $iss['detail'] ?? '' ) . '</div>';
      echo '<div style="font-size:13px;margin-top:4px;"><strong>راه‌حل:</strong> ' . esc_html( $iss['solution'] ?? '' ) . '</div>';
      if ( ! empty( $iss['fix_code'] ) && $iss['fix_code'] !== 'none' ) {
        echo '<p style="margin:8px 0 0;"><button type="button" class="button cgs-mon-fix" data-fix="' . esc_attr( $iss['fix_code'] ) . '">اصلاح امن پس از تأیید</button></p>';
      }
      echo '</div>';
    }
  }
  echo '</div>';
  ?>
  <script>
  jQuery(function($){
    var nonce = '<?php echo esc_js( wp_create_nonce( 'cgs_admin_nonce' ) ); ?>';
    $('#cgs-mon-run').on('click', function(){
      var $b=$(this).prop('disabled',true);
      $('#cgs-mon-msg').text('در حال بررسی...');
      $.post(ajaxurl,{action:'cgs_monitor_run',nonce:nonce}).done(function(res){
        if(res&&res.success) location.reload();
        else $('#cgs-mon-msg').text('خطا در پایش');
      }).fail(function(xhr){ var t=(xhr&&xhr.responseText)?String(xhr.responseText).slice(0,80):''; $('#cgs-mon-msg').text('خطای ارتباط ('+(xhr&&xhr.status?xhr.status:'?')+') '+t); }).always(function(){ $b.prop('disabled',false); });
    });
    $(document).on('click','.cgs-mon-fix',function(){
      var code=$(this).data('fix');
      if(!confirm('این اصلاح فقط اقدامات از پیش‌مجاز را انجام می‌دهد. ادامه؟')) return;
      $.post(ajaxurl,{action:'cgs_monitor_apply_fix',nonce:nonce,fix_code:code,confirm:'yes'}).done(function(res){
        alert((res&&res.data)?res.data:'انجام شد');
        if(res&&res.success) location.reload();
      });
    });
  });
  </script>
</div>
<hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0;">
<h2 style="color:#1a237e;">⏱ پایش عملکرد (کوئری و سرعت)</h2>

    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'monitor', true ); } ?>
    <p class="description">
        فقط کوئری‌های مربوط به جداول <code>cgs_*</code> ثبت می‌شوند.
        آستانه کندی: <strong><?php echo (int) CGS_Query_Monitor::SLOW_MS; ?> میلی‌ثانیه</strong>.
        برای کاهش بار در پروداکشن، پایش را فقط هنگام دیباگ روشن کنید.
    </p>

    <div style="display:flex;gap:12px;align-items:center;margin:16px 0;flex-wrap:wrap;background:#fff;border:1px solid #c3c4c7;border-radius:12px;padding:14px 16px;">
        <label style="font-weight:700;display:flex;align-items:center;gap:8px;margin:0;">
            <input type="checkbox" id="cgs-qm-toggle" <?php checked( $enabled ); ?> style="width:18px;height:18px;">
            فعال‌سازی پایش
        </label>
        <button type="button" id="cgs-qm-save" class="cgs-btn-admin cgs-btn-admin-success" style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,#2e7d32,#43a047);color:#fff;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(46,125,50,.3);">
            <span style="font-size:16px;">💾</span> ذخیره تغییرات پایش
        </button>
        <button type="button" class="button" id="cgs-qm-clear">پاک کردن لاگ و آمار</button>
        <span id="cgs-qm-msg" style="font-weight:600;"></span>
    </div>

    <?php if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) : ?>
    <div class="notice notice-warning" style="padding:10px 14px;">
        برای ثبت خودکار کوئری‌های وردپرس، در <code>wp-config.php</code> این خط را اضافه کنید:
        <code>define('SAVEQUERIES', true);</code>
        (فقط در محیط توسعه توصیه می‌شود). عملیات‌های سطح‌بالای افزونه حتی بدون آن هم قابل زمان‌گیری هستند.
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin:20px 0;">
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;color:#1a237e;"><?php echo $total_q; ?></div>
            <div style="font-size:0.85rem;">کل کوئری‌ها</div>
        </div>
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;"><?php echo $total_ms; ?></div>
            <div style="font-size:0.85rem;">مجموع زمان (ms)</div>
        </div>
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;"><?php echo $avg; ?></div>
            <div style="font-size:0.85rem;">میانگین (ms)</div>
        </div>
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;color:<?php echo $slow ? '#c62828' : '#2e7d32'; ?>;"><?php echo $slow; ?></div>
            <div style="font-size:0.85rem;">کوئری کند</div>
        </div>
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;color:#e65100;"><?php echo $max_ms; ?></div>
            <div style="font-size:0.85rem;">کندترین (ms)</div>
        </div>
    </div>

    
    <?php
    // === مشاور هوشمند پایش (تحلیل خودکار + راهکار فارسی) ===
    $advice = array();
    if ( ! $enabled ) {
        $advice[] = array(
            'level' => 'info',
            'title' => 'پایش خاموش است',
            'body'  => 'برای تشخیص کندی واقعی، پایش را فقط در محیط توسعه روشن کنید. در پروداکشن روشن گذاشتن مداوم باعث سربار می‌شود.',
            'fix'   => 'چک‌باکس «فعال‌سازی پایش» را موقتاً روشن کنید، چند صفحه فرم و داشبورد را باز کنید، سپس همین صفحه را رفرش کنید.',
        );
    }
    if ( $slow > 0 ) {
        $advice[] = array(
            'level' => 'danger',
            'title' => $slow . ' کوئری کند شناسایی شد',
            'body'  => 'کوئری‌های بالای آستانه (معمولاً بدون ایندکس یا SELECT *) صفحه را سنگین می‌کنند.',
            'fix'   => 'گام ۱: در همین صفحه جدول «کندترین کوئری» را باز کنید و متن SQL را بخوانید. گام ۲: اگر نام جدول cgs_form_fields دیدید → منوی شهر قسط → فرم‌ساز → دکمه ذخیره فیلدها را یک‌بار بزنید تا کش تازه شود. گام ۳: شهر قسط → تنظیمات → تب «سیستم و دیتابیس» → دکمه بهینه‌سازی دیتابیس را اجرا کنید. گام ۴: پایش را خاموش و دوباره روشن کنید و یک صفحه فرم را باز کنید؛ اگر عدد «کوئری کند» صفر شد مشکل برطرف است.',
        );
    }
    if ( $avg > 15 ) {
        $advice[] = array(
            'level' => 'warn',
            'title' => 'میانگین زمان کوئری بالاست (' . $avg . ' ms)',
            'body'  => 'میانگین بالا یعنی یا هاست ضعیف است یا کوئری‌های تکراری بدون کش زده می‌شود.',
            'fix'   => 'گام ۱: از هاست بپرسید آیا Redis یا Memcached دارید. گام ۲: اگر بله، افزونه «Redis Object Cache» را نصب و Enable بزنید. گام ۳: دوباره همین صفحه پایش را رفرش کنید؛ میانگین ms باید پایین بیاید. اگر Redis ندارید: در تنظیمات → سیستم، بهینه‌سازی دیتابیس را اجرا کنید و تعداد افزونه‌های فعال غیرضروری را کم کنید.',
        );
    }
    if ( $total_q > 400 ) {
        $advice[] = array(
            'level' => 'warn',
            'title' => 'تعداد کوئری در جلسه خیلی زیاد است (' . $total_q . ')',
            'body'  => 'در پیشخوان وردپرس با چند افزونه فعال، ۲۰۰–۳۰۰ کوئری غیرعادی نیست. فقط اعداد خیلی بالا (مثلاً بالای ۴۰۰) یا همراه با کوئری کند merit بررسی دارد.',
            'fix'   => 'گام ۱: پایش را خاموش کنید اگر فقط برای تست روشن کرده بودید. گام ۲: اگر همزمان کوئری کند (قرمز) دارید، SQL همان ردیف را بررسی کنید. گام ۳: افزونه‌های غیرضروری را موقتاً غیرفعال و دوباره پایش کنید. گام ۴: در فرم‌ساز از get_fields_by_step یکجا استفاده می‌شود؛ نیازی به تغییر فوری نیست مگر کندی واقعی حس شود.',
        );
    } elseif ( $total_q > 80 && $total_q <= 400 ) {
        $advice[] = array(
            'level' => 'info',
            'title' => 'تعداد کوئری جلسه: ' . $total_q . ' (در محدوده معمول ادمین)',
            'body'  => 'این عدد هشدار بحرانی نیست. پیشخوان وردپرس + افزونه‌ها معمولاً بیش از ۸۰ کوئری می‌زنند.',
            'fix'   => 'اگر سایت کند نیست، اقدامی لازم نیست. برای کاهش: Redis Object Cache و غیرفعال کردن افزونه‌های بلااستفاده.',
        );
    }
    if ( $enabled && $total_q === 0 ) {
        $advice[] = array(
            'level' => 'info',
            'title' => 'هنوز داده‌ای ثبت نشده',
            'body'  => 'پایش روشن است ولی لاگ خالی است.',
            'fix'   => 'یک‌بار صفحه فرم‌ساز یا فرم عمومی را باز کنید. اگر SAVEQUERIES در wp-config فعال نیست، فقط عملیات سطح‌بالای افزونه زمان‌گیری می‌شوند.',
        );
    }
    // تشخیص‌های ساختاری نسخه ۴
    $advice[] = array(
        'level' => 'info',
        'title' => 'وضعیت فعلی پایش',
        'body'  => 'این بخش فقط راهنمای عملکرد است، نه خطای قطعی. نسخه افزونه را از منوی افزونه‌ها ببینید.',
        'fix'   => 'نمودار خالی: داشبورد را تازه کنید. تب تنظیمات خالی: تب عمومی را باز کنید. جزئیات ماژول‌ها در بخش پایش هوشمند همین صفحه است.',
    );
    if ( empty( $advice ) && $enabled ) {
        $advice[] = array(
            'level' => 'ok',
            'title' => 'وضعیت مطلوب',
            'body'  => 'در بازه ثبت‌شده کوئری بحرانی دیده نشد.',
            'fix'   => 'گام ۱: تیک «فعال‌سازی پایش» را بردارید و ذخیره کنید. گام ۲: قبل از به‌روزرسانی بزرگ فقط روی staging پایش را روشن کنید و مقایسه کنید.',
        );
    }
    $colors = array(
        'danger' => '#c62828',
        'warn'   => '#e65100',
        'info'   => '#1565c0',
        'ok'     => '#2e7d32',
    );
    ?>
    <div class="cgs-advisor" style="margin:20px 0;padding:18px 20px;background:linear-gradient(135deg,#f8fafc,#eef2ff);border:1px solid #c5cae9;border-radius:14px;">
        <h2 style="margin:0 0 12px;color:#1a237e;font-size:1.15rem;">🤖 مشاور هوشمند پایش شهر قسط</h2>
        <p style="margin:0 0 14px;color:#475569;font-size:13px;">تحلیل خودکار آمار همین صفحه + راهکار عملی به فارسی (مانند دستیار فنی).</p>
        <?php foreach ( $advice as $ad ) :
            $c = $colors[ $ad['level'] ] ?? '#334155';
        ?>
        <div style="background:#fff;border-right:4px solid <?php echo esc_attr( $c ); ?>;border-radius:10px;padding:12px 14px;margin-bottom:10px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <strong style="color:<?php echo esc_attr( $c ); ?>;"><?php echo esc_html( $ad['title'] ); ?></strong>
            <p style="margin:6px 0;color:#334155;"><?php echo esc_html( $ad['body'] ); ?></p>
            <p style="margin:0;font-size:13px;background:#f1f5f9;padding:8px 10px;border-radius:8px;"><strong>راهکار:</strong> <?php echo esc_html( $ad['fix'] ); ?></p>
        </div>
        <?php endforeach; ?>
    </div>


    <?php if ( $max_sql ) : ?>
    <p><strong>کندترین کوئری:</strong> <code style="word-break:break-all;"><?php echo esc_html( $max_sql ); ?></code></p>
    <?php endif; ?>

    <?php if ( ! empty( $by_type ) ) : ?>
    <h2>بر اساس نوع</h2>
    <table class="widefat striped" style="max-width:480px;">
        <thead><tr><th>نوع</th><th>تعداد</th><th>مجموع ms</th><th>میانگین</th></tr></thead>
        <tbody>
        <?php foreach ( $by_type as $t => $d ) :
            $cnt = (int) $d['count'];
            $ms  = round( (float) $d['ms'], 1 );
            $a   = $cnt ? round( $ms / $cnt, 2 ) : 0;
        ?>
            <tr>
                <td><?php echo esc_html( $t ); ?></td>
                <td><?php echo $cnt; ?></td>
                <td><?php echo $ms; ?></td>
                <td><?php echo $a; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    
    <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;margin:16px 0;max-width:520px;">
        <h3 style="margin-top:0;">نمودار زمان بر اساس نوع کوئری</h3>
        <canvas id="cgsQmChart" height="180"></canvas>
    </div>
    <script>
    jQuery(function($){
        if (typeof Chart === 'undefined') return;
        var byType = <?php echo wp_json_encode( $by_type ); ?>;
        var labels = [], msData = [], countData = [];
        for (var k in byType) {
            if (!byType.hasOwnProperty(k)) continue;
            labels.push(k);
            msData.push(Math.round(byType[k].ms * 10) / 10);
            countData.push(byType[k].count);
        }
        if (!labels.length) return;
        new Chart(document.getElementById('cgsQmChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'مجموع ms', data: msData, backgroundColor: '#1a237e', borderRadius: 6 },
                    { label: 'تعداد', data: countData, backgroundColor: '#ffc107', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', rtl: true } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
    </script>

    <h2>آخرین کوئری‌ها (حداکثر <?php echo (int) CGS_Query_Monitor::MAX_LOG; ?>)</h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th style="width:70px;">زمان (ms)</th>
                <th style="width:80px;">نوع</th>
                <th>SQL / عملیات</th>
                <th style="width:140px;">زمان ثبت</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $log ) ) : ?>
            <tr><td colspan="4" style="text-align:center;color:#888;">هنوز داده‌ای ثبت نشده. پایش را روشن کنید و چند صفحه افزونه را باز کنید.</td></tr>
        <?php else :
            $log = array_reverse( $log );
            foreach ( $log as $row ) :
                $slow_row = ! empty( $row['slow'] );
        ?>
            <tr style="<?php echo $slow_row ? 'background:#ffebee;' : ''; ?>">
                <td><strong style="color:<?php echo $slow_row ? '#c62828' : '#333'; ?>;"><?php echo esc_html( $row['ms'] ?? 0 ); ?></strong></td>
                <td><?php echo esc_html( $row['type'] ?? '' ); ?></td>
                <td><code style="font-size:0.8rem;word-break:break-all;"><?php echo esc_html( $row['sql'] ?? '' ); ?></code></td>
                <td><?php echo esc_html( $row['time'] ?? '' ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<script>
jQuery(function($){
    var nonce = '<?php echo wp_create_nonce( "cgs_admin_nonce" ); ?>';
    function cgsQmSave(){
        var on = $('#cgs-qm-toggle').is(':checked') ? 1 : 0;
        $('#cgs-qm-msg').text('در حال ذخیره...').css('color','#666');
        $('#cgs-qm-save').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'cgs_qm_toggle',
            nonce: nonce,
            enabled: on
        }).done(function(res){
            if (res.success) {
                $('#cgs-qm-msg').text('✓ ذخیره شد — وضعیت: ' + (on ? 'فعال' : 'غیرفعال')).css('color','green');
                setTimeout(function(){ location.reload(); }, 700);
            } else {
                $('#cgs-qm-msg').text(res.data || 'خطا در ذخیره').css('color','red');
            }
        }).fail(function(){
            $('#cgs-qm-msg').text('خطای ارتباط با سرور').css('color','red');
        }).always(function(){
            $('#cgs-qm-save').prop('disabled', false);
        });
    }
    $('#cgs-qm-save').on('click', function(e){ e.preventDefault(); cgsQmSave(); });
    $('#cgs-qm-toggle').on('change', function(){
        $('#cgs-qm-msg').text('تغییر کرد — روی «ذخیره تغییرات پایش» کلیک کنید').css('color','#b45309');
    });
    $('#cgs-qm-clear').on('click', function(){
        if (!confirm('لاگ و آمار پاک شود؟')) return;
        $.post(ajaxurl, { action: 'cgs_qm_clear', nonce: nonce })
            .done(function(res){ if (res.success) location.reload(); });
    });
});
</script>
