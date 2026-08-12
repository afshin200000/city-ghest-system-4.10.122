<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<style id="cgs-settings-layout-fix">
/* رفع قطعی صفحه سفید: ارتفاع واقعی برای بدنه پیشخوان */
body.wp-admin #wpbody {
  height: auto !important;
  min-height: 100% !important;
  position: relative !important;
}
body.wp-admin #wpbody-content {
  float: none !important;
  width: 100% !important;
  max-width: 100% !important;
  overflow: visible !important;
  clear: both !important;
  position: relative !important;
  min-height: 400px !important;
  height: auto !important;
  padding-bottom: 40px !important;
}
body.wp-admin #wpbody-content:after {
  content: "" !important;
  display: table !important;
  clear: both !important;
}
.wrap.cgs-admin-wrap {
  display: block !important;
  float: none !important;
  clear: both !important;
  width: 100% !important;
  max-width: 100% !important;
  margin: 10px 0 40px !important;
  padding: 0 12px 40px 0 !important;
  box-sizing: border-box !important;
  position: relative !important;
  overflow: visible !important;
  min-height: 520px !important;
  height: auto !important;
  visibility: visible !important;
  opacity: 1 !important;
}
#cgs-settings-form {
  display: block !important;
  float: none !important;
  clear: both !important;
  min-height: 400px !important;
  height: auto !important;
  overflow: visible !important;
  visibility: visible !important;
  opacity: 1 !important;
}
.wrap.cgs-admin-wrap .form-table th { width: 160px; max-width: 28%; }
.wrap.cgs-admin-wrap .form-table td { word-break: break-word; }
.wrap.cgs-admin-wrap .nav-tab-wrapper { white-space: normal !important; }
.cgs-tab-save.sticky-save { position: static !important; }
/* ready-state rules removed — isolation CSS handles panels */
</style>

<style id="cgs-tab-isolation">
/* تب‌های تنظیمات — ساده و قابل‌اعتماد */
.cgs-settings-panel { display: none; }
.cgs-settings-panel.is-open {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  min-height: 200px;
  padding: 16px 20px 28px;
  background: #fff;
  border: 1px solid #c3c4c7;
  border-top: none;
  position: relative !important;
  left: auto !important;
  height: auto !important;
  overflow: visible !important;
}
.wrap.cgs-admin-wrap,
#cgs-settings-form {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}
.cgs-settings-tabs { display: flex !important; flex-wrap: wrap; gap: 4px; margin: 12px 0 0 !important; }
/* نوار تب همیشه دیده شود */
.nav-tab-wrapper.cgs-settings-tabs { display: flex !important; visibility: visible !important; }
</style>



<style id="cgs-settings-cols">
.cgs-set-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;align-items:start;}
@media(max-width:900px){.cgs-set-grid{grid-template-columns:1fr;}}
.cgs-set-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 2px 12px rgba(15,23,42,.05);}
.cgs-set-card h3,.cgs-set-card h2{margin:0 0 10px;font-size:14px;color:#1a237e;border-bottom:1px solid #eef2ff;padding-bottom:6px;}
.cgs-set-card .form-table{margin:0;}
.cgs-set-card .form-table th{width:120px;padding:6px 8px;font-size:12.5px;}
.cgs-set-card .form-table td{padding:6px 8px;}
.cgs-set-card input.regular-text,.cgs-set-card select{max-width:100%;}
.cgs-themes-box{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;}
.cgs-theme-card{border:2px solid #e2e8f0;border-radius:12px;padding:10px;cursor:pointer;background:#fff;text-align:center;transition:.2s;}
.cgs-theme-card:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.08);}
.cgs-theme-card.is-active{border-color:#4338ca;box-shadow:0 0 0 3px rgba(67,56,202,.2);}
.cgs-theme-swatches{display:flex;height:22px;border-radius:6px;overflow:hidden;margin-bottom:6px;}
.cgs-theme-swatches span{flex:1;}
.cgs-theme-name{font-size:11.5px;font-weight:700;color:#334155;}
.cgs-link-box{background:linear-gradient(135deg,#eef2ff,#f8fafc);border:1px solid #c5cae9;border-radius:10px;padding:10px 12px;margin-top:8px;font-size:12.5px;}
.cgs-link-box a{color:#3730a3;font-weight:600;}
</style>

<script>try{document.body.classList.add('cgs-settings-ready');}catch(e){}</script>
<?php if ( ! empty( $_GET['updated'] ) || ! empty( $_GET['cgs_charts_saved'] ) ) : ?>
<script>setTimeout(function(){ if (window.cgsHardReload) cgsHardReload(100); else { var u=location.href.replace(/([?&])_cgs_r=\d+/,'$1').replace(/[?&]$/,''); location.replace(u+(u.indexOf('?')>=0?'&':'?')+'_cgs_r='+Date.now()); } }, 200);</script>
<?php endif; ?>
<div class="wrap cgs-admin-wrap" style="display:block!important;visibility:visible!important;opacity:1!important;min-height:520px;">
    <h1>تنظیمات شهر قسط</h1>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.main' ); } ?>

<?php if ( ! empty( $_GET['updated'] ) ) : ?>
<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت در دیتابیس ذخیره شد.</p></div>
<?php endif; ?>



<script>
jQuery(function($){
    function showTab(t){
    if (!t) t = 'general';
    $('.cgs-settings-tab').removeClass('nav-tab-active');
    $('.cgs-settings-tab[data-tab="'+t+'"]').addClass('nav-tab-active');
    $('.cgs-settings-panel').removeClass('is-open').each(function(){
      this.style.display = 'none';
    });
    var p = document.getElementById('cgs-tab-' + t);
    if (p) {
      p.classList.add('is-open');
      p.style.display = 'block';
      p.style.visibility = 'visible';
      p.style.opacity = '1';
      p.style.height = 'auto';
      p.style.position = 'relative';
      p.style.left = 'auto';
    } else {
      var g = document.getElementById('cgs-tab-general');
      if (g) { g.classList.add('is-open'); g.style.display = 'block'; }
    }
    $('#cgs_active_tab').val(t);
  }
  $(document).on('click', '.cgs-settings-tab', function(e){
    e.preventDefault();
    showTab($(this).data('tab'));
  });
  // تب از URL یا hidden field
  var urlTab = (function(){
    try {
      var m = location.search.match(/[?&]tab=([a-z0-9_\-]+)/i);
      return m ? m[1] : '';
    } catch(e){ return ''; }
  })();
  var start = urlTab || $('#cgs_active_tab').val() || 'general';
  showTab(start);
  // تضمین: ارتفاع بدنه بعد از رسم
  requestAnimationFrame(function(){
    document.body.classList.add('cgs-settings-ready');
    var p = document.querySelector('.cgs-settings-panel.is-open');
    if (p) { void p.offsetHeight; }
  });
});
</script>

<style>
.cgs-settings-tabs { margin: 12px 0 16px; }
/* panels controlled by cgs-tab-isolation */
.cgs-settings-tabs { display:flex; flex-wrap:wrap; gap:4px; }
.cgs-btn-admin { display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:none;border-radius:10px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.12);transition:.2s; }
.cgs-btn-admin-success { background:linear-gradient(135deg,#2e7d32,#43a047);color:#fff; }
.cgs-btn-admin-success:hover { transform:translateY(-2px);box-shadow:0 6px 20px rgba(46,125,50,.35); }
.cgs-btn-lg { font-size:15px;padding:12px 24px; }
.cgs-tab-save { margin:16px 0;padding:12px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0; }

</style>
<nav class="nav-tab-wrapper cgs-settings-tabs" style="margin-bottom:0;">
  <a href="#" class="nav-tab nav-tab-active cgs-settings-tab" data-tab="general">عمومی و برند</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="sms">پیامک</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="sign">امضا دیجیتال</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="guarantee">تضامین</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="audience">مخاطبین</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="inquiry">استعلام و API</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="payment">درگاه پرداخت</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="sound">صدا</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="appearance">ظاهر و فونت</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="jalali">تقویم شمسی</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="crm">CRM</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="fbplugins">افزونه‌های فرم‌ساز</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="calculator">محاسبه‌گر اقساط</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="settlement">تسویه</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="risk">ریسک اعتباری</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="charts">نمودارها</a>
  <a href="#" class="nav-tab cgs-settings-tab" data-tab="system">سیستم و دیتابیس</a>
</nav>

<form method="post" enctype="multipart/form-data" id="cgs-settings-form">
<?php wp_nonce_field( 'cgs_settings_nonce', 'cgs_settings_nonce' ); ?>
<input type="hidden" name="cgs_active_tab" id="cgs_active_tab" value="general">

<div id="cgs-tab-general" class="cgs-settings-panel is-open" style="min-height:280px;padding:16px 20px 28px;background:#fff;border:1px solid #c3c4c7;border-top:none;">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.general' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
        
<details class="cgs-help"><summary>راهنما: تنظیمات عمومی و لوگو</summary><div class="cgs-help-body">
<p>نام شرکت، لوگو و پذیرش حقیقی/حقوقی را اینجا تنظیم کنید. بعد از تغییر حتماً ذخیره را بزنید.</p>
<ol><li>لوگو را با دکمه Browse از رسانه وردپرس انتخاب کنید.</li>
<li>تیک حقیقی/حقوقی را برای فعال یا محدود کردن گزینه‌ها تنظیم کنید.</li></ol>
</div></details>
        <table class="form-table">
            <tr>
                <th>نوع اعداد</th>
                <td>
                    <label style="margin-left:16px;"><input type="radio" name="number_system" value="fa" <?php checked( cgs_get_option( 'number_system', 'fa' ), 'fa' ); ?>> اعداد پارسی (۰۱۲۳…)</label>
                    <label><input type="radio" name="number_system" value="en" <?php checked( cgs_get_option( 'number_system', 'fa' ), 'en' ); ?>> اعداد انگلیسی (0123…)</label>
                    <p class="description">در فرم‌ها و پیش‌نمایش اعمال می‌شود.</p>
                </td>
            </tr>
        </table>

<table class="form-table">
            <tr>
                <th>نام مجموعه</th>
                <td><input type="text" name="company_name" value="<?php echo esc_attr( cgs_get_option( 'company_name', 'شهر قسط' ) ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>لوگوی شهر قسط</th>
                <td>
                    <?php $logo = cgs_get_option( 'site_logo', '' ); ?>
                    <?php if ( $logo ) : ?><p><img src="<?php echo esc_url($logo); ?>" alt="" style="max-height:60px;"></p><?php endif; ?>
                    <input type="url" name="site_logo" id="cgs-site-logo" value="<?php echo esc_attr($logo); ?>" class="regular-text" dir="ltr" placeholder="URL لوگو">
                    <button type="button" class="button cgs-media-upload" data-target="cgs-site-logo" data-title="انتخاب لوگوی شهر قسط">انتخاب از رسانه / Browse</button>
                    <p class="description">فرمت مجاز: PNG، JPG، WEBP، SVG — حداکثر حجم پیشنهادی ۲ مگابایت. نسبت مربعی یا افقی مناسب سربرگ.</p>
                    <div class="cgs-logo-preview" style="margin-top:8px;"><?php if($logo): ?><img src="<?php echo esc_url($logo); ?>" alt="" style="max-height:64px;max-width:200px;"><?php endif; ?></div>
                    <p class="description">یا از رسانه وردپرس آدرس تصویر را وارد کنید. در سربرگ فرم‌ها استفاده می‌شود.</p>
                </td>
            </tr>

            <tr>
                <th>نوع تقویم</th>
                <td>
                    <select name="date_calendar">
                        <option value="jalali" <?php selected( cgs_get_option( 'date_calendar' ), 'jalali' ); ?>>هجری شمسی</option>
                        <option value="gregorian" <?php selected( cgs_get_option( 'date_calendar' ), 'gregorian' ); ?>>میلادی</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>رنگ اصلی</th>
                <td><input type="color" name="primary_color" value="<?php echo esc_attr( cgs_get_option( 'primary_color', '#1a237e' ) ); ?>"></td>
            </tr>
            <tr>
                <th>رنگ ثانویه</th>
                <td><input type="color" name="secondary_color" value="<?php echo esc_attr( cgs_get_option( 'secondary_color', '#ffc107' ) ); ?>"></td>
            </tr>
        </table>
        <h2>لوگوی سایت و شهر قسط</h2>
        <table class="form-table">
            <tr>
                <th>آدرس لوگوی اصلی</th>
                <td>
                    <input type="url" name="site_logo" id="cgs-site-logo-2" value="<?php echo esc_attr( cgs_get_option( 'site_logo', '' ) ); ?>" class="regular-text" dir="ltr" placeholder="https://...">
                    <button type="button" class="button cgs-media-upload" data-target="cgs-site-logo-2" data-title="انتخاب لوگو">انتخاب از رسانه / Browse</button>
                    <p class="description">فرمت: PNG، JPG، WEBP، SVG — حداکثر ۲ مگابایت. برای سربرگ فرم و قرارداد.</p>
                </td>
            </tr>
            <tr>
                <th>آیکون کوچک (favicon / آیکن)</th>
                <td>
                    <input type="url" name="site_icon" id="cgs-site-icon" value="<?php echo esc_attr( cgs_get_option( 'site_icon', '' ) ); ?>" class="regular-text" dir="ltr" placeholder="https://.../favicon.png">
                    <button type="button" class="button cgs-media-upload" data-target="cgs-site-icon" data-title="انتخاب آیکن">Browse / رسانه</button>
                    <div class="cgs-link-box">
                        <strong>منابع آیکن رایگان:</strong>
                        <a href="https://icons8.com/" target="_blank" rel="noopener">Icons8</a> ·
                        <a href="https://www.flaticon.com/" target="_blank" rel="noopener">Flaticon</a> ·
                        <a href="https://favicon.io/" target="_blank" rel="noopener">Favicon.io</a> ·
                        <a href="https://www.iconfinder.com/" target="_blank" rel="noopener">Iconfinder</a>
                    </div>
                </td>
            </tr>
        </table>

        <div class="cgs-set-card" style="margin-top:14px;">
        <h2>پذیرش اشخاص</h2>
        <table class="form-table">
            <tr>
                <th>اشخاص حقیقی</th>
                <td>
                    <label>
                        <input type="checkbox" name="allow_natural_person" value="1" <?php checked( cgs_get_option( 'allow_natural_person', 1 ), 1 ); ?>>
                        امکان انتخاب «حقیقی» در فرم‌ها فعال باشد
                    </label>
                    <p class="description">اگر غیرفعال شود، گزینه حقیقی دیده می‌شود ولی قابل انتخاب نیست.</p>
                </td>
            </tr>
            <tr>
                <th>اشخاص حقوقی</th>
                <td>
                    <label>
                        <input type="checkbox" name="allow_legal_person" value="1" <?php checked( cgs_get_option( 'allow_legal_person', 1 ), 1 ); ?>>
                        امکان انتخاب «حقوقی» در فرم‌ها فعال باشد
                    </label>
                    <p class="description">اگر غیرفعال شود، گزینه حقوقی دیده می‌شود ولی قابل انتخاب نیست.</p>
                </td>
            </tr>
            <tr>
                <th>دولتی</th>
                <td>
                    <label><input type="checkbox" name="allow_gov_person" value="1" <?php checked( cgs_get_option( 'allow_gov_person', 1 ), 1 ); ?>> امکان انتخاب «دولتی» فعال باشد</label>
                </td>
            </tr>
            <tr>
                <th>نیمه‌دولتی</th>
                <td>
                    <label><input type="checkbox" name="allow_semigov_person" value="1" <?php checked( cgs_get_option( 'allow_semigov_person', 1 ), 1 ); ?>> امکان انتخاب «نیمه‌دولتی» فعال باشد</label>
                </td>
            </tr>
        </table>
        </div><!-- /پذیرش اشخاص card -->

</div><!-- /general -->

<div id="cgs-tab-sms" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.sms' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help"><summary>راهنما: پیامک</summary><div class="cgs-help-body">
<p>برای ارسال پیامک هنگام تأیید یا رد درخواست، کلید API سرویس‌دهنده (مثل کاوه‌نگار) را وارد کنید.</p>
<ol>
<li>ارائه‌دهنده را انتخاب کنید.</li>
<li>API Key و شماره فرستنده را وارد کنید.</li>
<li>تیک‌های «ارسال هنگام تأیید/رد» را فعال کنید.</li>
</ol>
<div class="cgs-help-tip">💡 بدون کلید معتبر، پیامک ارسال نمی‌شود؛ فقط وضعیت درخواست ثبت می‌گردد.</div>
</div></details>
<div class="cgs-set-card" style="margin:12px 0;">
  <h3>چند ارائه‌دهنده پیامک</h3>
  <p class="description">هر خط: نام|نمایش(1/0)|فعال(1/0)|API Key|فرستنده — مثال: کاوه‌نگار|1|1|xxxx|1000</p>
  <textarea name="sms_providers_list" rows="4" class="large-text" dir="ltr" placeholder="kavenegar|1|1|APIKEY|1000"><?php echo esc_textarea( cgs_get_option( 'sms_providers_list', '' ) ); ?></textarea>
</div>


<h2>تنظیمات پیامک</h2>
        <table class="form-table">
            <tr>
                <th>فعال‌سازی پیامک</th>
                <td>
                    <label>
                        <input type="checkbox" name="sms_enabled" value="1" <?php checked( cgs_get_option( 'sms_enabled' ), 1 ); ?>>
                        ارسال پیامک فعال باشد
                    </label>
                </td>
            </tr>
            <tr>
                <th>درگاه پیامک</th>
                <td>
                    <select name="sms_provider">
                        <option value="">انتخاب کنید</option>
                        <option value="kavenegar" <?php selected( cgs_get_option( 'sms_provider' ), 'kavenegar' ); ?>>کاوه نگار</option>
                        <option value="melipayamak" <?php selected( cgs_get_option( 'sms_provider' ), 'melipayamak' ); ?>>ملی پیامک</option>
                        <option value="farapayamak" <?php selected( cgs_get_option( 'sms_provider' ), 'farapayamak' ); ?>>فناوران پیامک (FaraPayamak)</option>
                        <option value="ippanel" <?php selected( cgs_get_option( 'sms_provider' ), 'ippanel' ); ?>>IPPanel / ایده پردازان</option>
                        <option value="smsir" <?php selected( cgs_get_option( 'sms_provider' ), 'smsir' ); ?>>SMS.ir</option>
                        <option value="niksms" <?php selected( cgs_get_option( 'sms_provider' ), 'niksms' ); ?>>نیک‌اس‌ام‌اس</option>
                        <option value="payamresan" <?php selected( cgs_get_option( 'sms_provider' ), 'payamresan' ); ?>>پیام‌رسان</option>
                        <option value="ghasedak" <?php selected( cgs_get_option( 'sms_provider' ), 'ghasedak' ); ?>>قاصدک</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>API Key</th>
                <td><input type="text" name="sms_api_key" value="<?php echo esc_attr( cgs_get_option( 'sms_api_key' ) ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>شماره فرستنده</th>
                <td><input type="text" name="sms_sender" value="<?php echo esc_attr( cgs_get_option( 'sms_sender' ) ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>ارسال هنگام تأیید</th>
                <td><label><input type="checkbox" name="sms_on_approve" value="1" <?php checked( cgs_get_option( 'sms_on_approve' ), 1 ); ?>> بله</label></td>
            </tr>
            <tr>
                <th>ارسال هنگام رد</th>
                <td><label><input type="checkbox" name="sms_on_reject" value="1" <?php checked( cgs_get_option( 'sms_on_reject' ), 1 ); ?>> بله</label></td>
            </tr>
        </table>
</div><!-- /sms -->

<div id="cgs-tab-sign" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.sign' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<div class="cgs-set-card" style="margin:12px 0;">
  <h3>چند ارائه‌دهنده امضای دیجیتال</h3>
  <p class="description">هر خط: نام|نمایش(1/0)|فعال(1/0)|API Key — کاربر می‌تواند از بین موارد نمایش‌داده‌شده انتخاب کند.</p>
  <textarea name="sign_providers_list" rows="4" class="large-text" dir="ltr" placeholder="emzame|1|1|KEY"><?php echo esc_textarea( cgs_get_option( 'sign_providers_list', '' ) ); ?></textarea>
</div>

<h2>امضای دیجیتال صاحب سند (ضامن)</h2>
        <table class="form-table">
            <tr>
                <th>ارائه‌دهنده</th>
                <td>
                    <select name="digital_sign_provider">
                        <option value="" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), '' ); ?>>انتخاب نشده (فقط ثبت وضعیت دستی)</option>
                        <option value="manual" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'manual' ); ?>>دستی توسط ادمین</option>
                        <option value="gica" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'gica' ); ?>>GICA (مرکز میانی عام)</option>
                        <option value="parssign" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'parssign' ); ?>>پارس‌ساین (Pars Sign)</option>
                        <option value="raahbar" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'raahbar' ); ?>>فناوران اعتماد راهبر</option>
                        <option value="smarttrust" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'smarttrust' ); ?>>اعتماد هوشمند (Smart Trust)</option>
                        <option value="emzame" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'emzame' ); ?>>امضامی (Emzame)</option>
                        <option value="govahit" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'govahit' ); ?>>گواهیت</option>
                        <option value="custom_api" <?php selected( cgs_get_option( 'digital_sign_provider', '' ), 'custom_api' ); ?>>API سفارشی</option>
                    </select>
                    <p class="description">اتصال واقعی به سامانه‌های احراز هویت و امضای دیجیتال رسمی ایران نیازمند قرارداد با مرکز صدور گواهی (CA) معتبر است. تا آن زمان می‌توانید وضعیت را دستی ثبت کنید.</p>
                </td>
            </tr>
            <tr>
                <th>کلید API (اختیاری)</th>
                <td>
                    <input type="text" name="digital_sign_api_key" value="<?php echo esc_attr( cgs_get_option( 'digital_sign_api_key', '' ) ); ?>" class="regular-text" dir="ltr">
                </td>
            </tr>
        </table>

</div><!-- /sign -->

<div id="cgs-tab-guarantee" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.guarantee', true ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help" open><summary>راهنما: تضامین داینامیک</summary><div class="cgs-help-body">
<p>انواع پایه تضمین را می‌توانید <strong>افزودن / حذف / ویرایش / فعال‌سازی / جابجا</strong> کنید. برای هر نوع، مجموعه جزئیات (خصوصیات) با فیلد توضیح و آیکن جداگانه تعریف می‌شود.</p>
<ol>
<li>روی «+ افزودن نوع تضمین» کلیک کنید و نام، آیکن و توضیح را وارد کنید.</li>
<li>با «+ خصوصیت» فیلدهای جزئیات را بسازید (نام، نوع، توضیح، آیکن).</li>
<li>با دستگیره ☰ ترتیب را تغییر دهید. در پایان «ذخیره» را بزنید.</li>
</ol>
</div></details>
<?php
$cgs_icon_opts = array(
    'file-text' => '📄 سند',
    'check'     => '✅ چک',
    'edit'      => '✏️ سفته',
    'home'      => '🏠 ملک',
    'bank'      => '🏦 بانک',
    'shield'    => '🛡️ ضمانت',
    'user'      => '👤 شخص',
    'building'  => '🏢 شرکت',
    'star'      => '⭐ ویژه',
    'money'     => '💰 مبلغ',
    'calendar'  => '📅 تاریخ',
    'image'     => '🖼️ تصویر',
    'list'      => '📋 لیست',
    'key'       => '🔑 کلید',
    'lock'      => '🔒 قفل',
);
$gtypes = cgs_get_option( 'guarantee_types_dynamic', array() );
if ( empty( $gtypes ) || ! is_array( $gtypes ) ) {
    $gtypes = array(
        array(
            'id' => 'check', 'label' => 'چک', 'enabled' => 1, 'icon' => 'check', 'description' => 'چک صیادی / تضمینی',
            'props' => array(
                array( 'label' => 'شماره صیاد', 'type' => 'text', 'description' => 'شناسه ۱۶ رقمی صیاد', 'icon' => 'list', 'enabled' => 1 ),
                array( 'label' => 'تاریخ چک', 'type' => 'date', 'description' => '', 'icon' => 'calendar', 'enabled' => 1 ),
                array( 'label' => 'مبلغ', 'type' => 'number', 'description' => 'به ریال', 'icon' => 'money', 'enabled' => 1 ),
                array( 'label' => 'تصویر چک', 'type' => 'file', 'description' => '', 'icon' => 'image', 'enabled' => 1 ),
            ),
        ),
        array(
            'id' => 'promissory', 'label' => 'سفته', 'enabled' => 1, 'icon' => 'edit', 'description' => 'سفته تضمینی',
            'props' => array(
                array( 'label' => 'شماره سفته', 'type' => 'text', 'description' => '', 'icon' => 'list', 'enabled' => 1 ),
                array( 'label' => 'مبلغ', 'type' => 'number', 'description' => '', 'icon' => 'money', 'enabled' => 1 ),
                array( 'label' => 'تعداد برگ', 'type' => 'number', 'description' => '', 'icon' => 'file-text', 'enabled' => 1 ),
            ),
        ),
        array(
            'id' => 'property', 'label' => 'سند ملکی', 'enabled' => 0, 'icon' => 'home', 'description' => 'وثیقه ملکی',
            'props' => array(
                array( 'label' => 'نوع سند', 'type' => 'text', 'description' => '', 'icon' => 'file-text', 'enabled' => 1 ),
                array( 'label' => 'پلاک ثبتی', 'type' => 'text', 'description' => '', 'icon' => 'list', 'enabled' => 1 ),
                array( 'label' => 'آدرس ملک', 'type' => 'text', 'description' => '', 'icon' => 'home', 'enabled' => 1 ),
                array( 'label' => 'مساحت (متر)', 'type' => 'number', 'description' => '', 'icon' => 'list', 'enabled' => 1 ),
            ),
        ),
        array(
            'id' => 'bank', 'label' => 'ضمانت‌نامه بانکی', 'enabled' => 0, 'icon' => 'bank', 'description' => 'ضمانت‌نامه صادره از بانک',
            'props' => array(
                array( 'label' => 'نام بانک', 'type' => 'text', 'description' => '', 'icon' => 'bank', 'enabled' => 1 ),
                array( 'label' => 'شماره ضمانت‌نامه', 'type' => 'text', 'description' => '', 'icon' => 'list', 'enabled' => 1 ),
                array( 'label' => 'مبلغ', 'type' => 'number', 'description' => '', 'icon' => 'money', 'enabled' => 1 ),
            ),
        ),
    );
}
$cgs_icon_select = function( $name, $selected ) use ( $cgs_icon_opts ) {
    $h = '<select name="' . esc_attr( $name ) . '" class="cgs-icon-select" style="max-width:140px;">';
    foreach ( $cgs_icon_opts as $k => $lbl ) {
        $h .= '<option value="' . esc_attr( $k ) . '"' . selected( $selected, $k, false ) . '>' . esc_html( $lbl ) . '</option>';
    }
    $h .= '</select>';
    return $h;
};
?>
<style>
.cgs-gtype{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;margin-bottom:14px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.cgs-gtype-head{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #eef2ff}
.cgs-gtype-head input[type=text]{flex:1;min-width:120px}
.cgs-gtype-desc{width:100%;margin:4px 0 8px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;resize:vertical;min-height:36px}
.cgs-gprops{list-style:none;margin:0;padding:0}
.cgs-gprop{display:grid;grid-template-columns:24px minmax(100px,1.2fr) minmax(90px,.8fr) minmax(100px,1fr) minmax(90px,.7fr) auto auto;gap:6px;align-items:center;padding:8px 10px;margin:4px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px}
.cgs-gprop input,.cgs-gprop select{max-width:100%;padding:4px 8px!important;font-size:12.5px}
.cgs-ghandle{cursor:grab;color:#94a3b8;font-size:16px}
@media(max-width:900px){.cgs-gprop{grid-template-columns:1fr}}
</style>
<div id="cgs-guarantee-types">
<?php foreach ( $gtypes as $gi => $gt ) :
    $gid = esc_attr( $gt['id'] ?? ( 'g' . $gi ) );
    $glabel = esc_attr( $gt['label'] ?? '' );
    $gdesc = esc_textarea( $gt['description'] ?? '' );
    $gicon = $gt['icon'] ?? 'file-text';
    $gen = ! empty( $gt['enabled'] );
    $props = isset( $gt['props'] ) && is_array( $gt['props'] ) ? $gt['props'] : array();
?>
  <div class="cgs-gtype" data-index="<?php echo (int) $gi; ?>">
    <div class="cgs-gtype-head">
      <span class="cgs-ghandle cgs-gtype-handle" title="جابجایی">☰</span>
      <input type="hidden" name="guarantee_types[<?php echo (int) $gi; ?>][id]" value="<?php echo $gid; ?>">
      <input type="text" name="guarantee_types[<?php echo (int) $gi; ?>][label]" value="<?php echo $glabel; ?>" placeholder="نام نوع تضمین" class="regular-text">
      <?php echo $cgs_icon_select( 'guarantee_types[' . (int) $gi . '][icon]', $gicon ); ?>
      <label><input type="checkbox" name="guarantee_types[<?php echo (int) $gi; ?>][enabled]" value="1" <?php checked( $gen, true ); ?>> فعال</label>
      <button type="button" class="button cgs-gtype-add-prop">+ خصوصیت</button>
      <button type="button" class="button-link-delete cgs-gtype-remove" style="color:#b91c1c;">حذف نوع</button>
    </div>
    <textarea name="guarantee_types[<?php echo (int) $gi; ?>][description]" class="cgs-gtype-desc" placeholder="توضیح کوتاه این نوع تضمین..."><?php echo $gdesc; ?></textarea>
    <ul class="cgs-gprops">
    <?php foreach ( $props as $pi => $pr ) :
        $pen = ! isset( $pr['enabled'] ) || ! empty( $pr['enabled'] );
    ?>
      <li class="cgs-gprop">
        <span class="cgs-ghandle cgs-gprop-handle">☰</span>
        <input type="text" name="guarantee_types[<?php echo (int) $gi; ?>][props][<?php echo (int) $pi; ?>][label]" value="<?php echo esc_attr( $pr['label'] ?? '' ); ?>" placeholder="نام خصوصیت">
        <select name="guarantee_types[<?php echo (int) $gi; ?>][props][<?php echo (int) $pi; ?>][type]">
          <?php
          $pt = $pr['type'] ?? 'text';
          foreach ( array( 'text' => 'متن', 'number' => 'عدد', 'date' => 'تاریخ', 'file' => 'فایل', 'textarea' => 'چندخطی' ) as $tk => $tl ) {
              echo '<option value="' . esc_attr( $tk ) . '"' . selected( $pt, $tk, false ) . '>' . esc_html( $tl ) . '</option>';
          }
          ?>
        </select>
        <input type="text" name="guarantee_types[<?php echo (int) $gi; ?>][props][<?php echo (int) $pi; ?>][description]" value="<?php echo esc_attr( $pr['description'] ?? '' ); ?>" placeholder="توضیح">
        <?php echo $cgs_icon_select( 'guarantee_types[' . (int) $gi . '][props][' . (int) $pi . '][icon]', $pr['icon'] ?? 'list' ); ?>
        <label style="white-space:nowrap;font-size:12px;"><input type="checkbox" name="guarantee_types[<?php echo (int) $gi; ?>][props][<?php echo (int) $pi; ?>][enabled]" value="1" <?php checked( $pen, true ); ?>> فعال</label>
        <button type="button" class="button-link-delete cgs-gprop-remove" style="color:#b91c1c;">حذف</button>
      </li>
    <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
</div>
<p style="margin-top:10px;">
  <button type="button" class="button button-primary" id="cgs-gtype-add">+ افزودن نوع تضمین</button>
</p>
<script>
jQuery(function($){
  var iconOpts = <?php echo wp_json_encode( $cgs_icon_opts ); ?>;
  function iconSelectHtml(name, selected){
    var h = '<select name="'+name+'" class="cgs-icon-select" style="max-width:140px;">';
    $.each(iconOpts, function(k,lbl){
      h += '<option value="'+k+'"'+(k===selected?' selected':'')+'>'+lbl+'</option>';
    });
    return h+'</select>';
  }
  function reindexGuarantee(){
    $('#cgs-guarantee-types .cgs-gtype').each(function(gi){
      $(this).attr('data-index', gi);
      $(this).find('[name]').each(function(){
        var n = $(this).attr('name');
        if (!n) return;
        n = n.replace(/guarantee_types\[\d+\]/, 'guarantee_types['+gi+']');
        $(this).attr('name', n);
      });
      $(this).find('.cgs-gprop').each(function(pi){
        $(this).find('[name]').each(function(){
          var n = $(this).attr('name');
          if (!n) return;
          n = n.replace(/\[props\]\[\d+\]/, '[props]['+pi+']');
          $(this).attr('name', n);
        });
      });
    });
  }
  if ($.fn.sortable) {
    $('#cgs-guarantee-types').sortable({ handle: '.cgs-gtype-handle', update: reindexGuarantee });
    $('.cgs-gprops').sortable({ handle: '.cgs-gprop-handle', update: reindexGuarantee });
  }
  $('#cgs-gtype-add').on('click', function(){
    var gi = $('#cgs-guarantee-types .cgs-gtype').length;
    var html = '<div class="cgs-gtype" data-index="'+gi+'">'+
      '<div class="cgs-gtype-head">'+
      '<span class="cgs-ghandle cgs-gtype-handle">☰</span>'+
      '<input type="hidden" name="guarantee_types['+gi+'][id]" value="g'+gi+'">'+
      '<input type="text" name="guarantee_types['+gi+'][label]" value="" placeholder="نام نوع تضمین" class="regular-text">'+
      iconSelectHtml('guarantee_types['+gi+'][icon]','file-text')+
      '<label><input type="checkbox" name="guarantee_types['+gi+'][enabled]" value="1" checked> فعال</label>'+
      '<button type="button" class="button cgs-gtype-add-prop">+ خصوصیت</button>'+
      '<button type="button" class="button-link-delete cgs-gtype-remove" style="color:#b91c1c;">حذف نوع</button>'+
      '</div>'+
      '<textarea name="guarantee_types['+gi+'][description]" class="cgs-gtype-desc" placeholder="توضیح کوتاه این نوع تضمین..."></textarea>'+
      '<ul class="cgs-gprops"></ul></div>';
    $('#cgs-guarantee-types').append(html);
    if ($.fn.sortable) {
      $('#cgs-guarantee-types .cgs-gprops').last().sortable({ handle: '.cgs-gprop-handle', update: reindexGuarantee });
    }
  });
  $(document).on('click', '.cgs-gtype-remove', function(){
    if (!confirm('این نوع تضمین حذف شود؟')) return;
    $(this).closest('.cgs-gtype').remove();
    reindexGuarantee();
  });
  $(document).on('click', '.cgs-gtype-add-prop', function(){
    var $box = $(this).closest('.cgs-gtype');
    var gi = $box.index();
    var pi = $box.find('.cgs-gprop').length;
    var html = '<li class="cgs-gprop">'+
      '<span class="cgs-ghandle cgs-gprop-handle">☰</span>'+
      '<input type="text" name="guarantee_types['+gi+'][props]['+pi+'][label]" placeholder="نام خصوصیت">'+
      '<select name="guarantee_types['+gi+'][props]['+pi+'][type]">'+
      '<option value="text">متن</option><option value="number">عدد</option><option value="date">تاریخ</option>'+
      '<option value="file">فایل</option><option value="textarea">چندخطی</option></select>'+
      '<input type="text" name="guarantee_types['+gi+'][props]['+pi+'][description]" placeholder="توضیح">'+
      iconSelectHtml('guarantee_types['+gi+'][props]['+pi+'][icon]','list')+
      '<label style="white-space:nowrap;font-size:12px;"><input type="checkbox" name="guarantee_types['+gi+'][props]['+pi+'][enabled]" value="1" checked> فعال</label>'+
      '<button type="button" class="button-link-delete cgs-gprop-remove" style="color:#b91c1c;">حذف</button></li>';
    $box.find('.cgs-gprops').append(html);
  });
  $(document).on('click', '.cgs-gprop-remove', function(){
    $(this).closest('.cgs-gprop').remove();
    reindexGuarantee();
  });
});
</script>
</div><!-- /guarantee -->

<div id="cgs-tab-audience" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.audience' ); } ?>
<style>
#cgs-tab-audience .form-table th{width:140px;padding:8px 10px;}
#cgs-tab-audience .form-table td{padding:8px 10px;}
#cgs-tab-audience input.regular-text{max-width:220px;}
#cgs-tab-audience table{max-width:100%;}
#cgs-tab-audience .cgs-aud-table{display:block;overflow-x:auto;max-width:100%;}
.wrap.cgs-admin-wrap{max-width:100%;overflow:visible;}
.cgs-settings-panel{max-width:100%;box-sizing:border-box;}
</style>

<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
  <h2>تعریف مخاطبین (تب‌های فرم)</h2>
  <p class="description">افزودن، حذف و جابجایی مخاطبین. برچسب فارسی همان نام تب در فرم‌ساز است.</p>
  <table class="widefat striped" id="cgs-audience-table">
    <thead><tr><th style="width:30px;">☰</th><th>کلید (en)</th><th>برچسب فارسی</th><th>رنگ</th><th>آیکن</th><th>فعال</th><th></th></tr></thead>
    <tbody id="cgs-audience-tbody">
    <?php
    $audiences = cgs_get_option( 'custom_audiences', array() );
    if ( empty( $audiences ) ) {
        $audiences = array(
            array( 'key' => 'representative', 'label' => 'نماینده', 'color' => '#1a237e', 'icon' => 'user', 'active' => 1 ),
            array( 'key' => 'seller', 'label' => 'فروشنده', 'color' => '#0d47a1', 'icon' => 'building', 'active' => 1 ),
            array( 'key' => 'marketer', 'label' => 'بازاریاب', 'color' => '#00695c', 'icon' => 'users', 'active' => 1 ),
            array( 'key' => 'investor', 'label' => 'سرمایه‌گذار', 'color' => '#e65100', 'icon' => 'money', 'active' => 1 ),
            array( 'key' => 'applicant', 'label' => 'متقاضی اعتبار', 'color' => '#6a1b9a', 'icon' => 'star', 'active' => 1 ),
        );
    }
    foreach ( $audiences as $i => $a ) :
    ?>
      <tr class="cgs-aud-row">
        <td class="cgs-aud-handle" style="cursor:move;">☰</td>
        <td><input type="text" name="custom_audiences[<?php echo (int)$i; ?>][key]" value="<?php echo esc_attr($a['key']??''); ?>" class="regular-text" dir="ltr"></td>
        <td><input type="text" name="custom_audiences[<?php echo (int)$i; ?>][label]" value="<?php echo esc_attr($a['label']??''); ?>" class="regular-text"></td>
        <td><input type="color" name="custom_audiences[<?php echo (int)$i; ?>][color]" value="<?php echo esc_attr($a['color']??'#1a237e'); ?>"></td>
        <td><input type="text" name="custom_audiences[<?php echo (int)$i; ?>][icon]" value="<?php echo esc_attr($a['icon']??'user'); ?>" style="width:90px;"></td>
        <td><label><input type="checkbox" name="custom_audiences[<?php echo (int)$i; ?>][active]" value="1" <?php checked( !empty($a['active']), true ); ?>> فعال</label></td>
        <td><button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-aud-remove" style="padding:4px 8px;font-size:11px;">حذف</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:10px;">
    <button type="button" id="cgs-aud-add" class="cgs-btn-admin cgs-btn-admin-primary">+ افزودن مخاطب</button>
  </p>
  <script>
  jQuery(function($){
    var idx = $('#cgs-audience-tbody tr').length;
    $('#cgs-aud-add').on('click', function(){
      var i = idx++;
      var row = '<tr class="cgs-aud-row"><td class="cgs-aud-handle" style="cursor:move;">☰</td>';
      row += '<td><input type="text" name="custom_audiences['+i+'][key]" value="custom_'+i+'" class="regular-text" dir="ltr"></td>';
      row += '<td><input type="text" name="custom_audiences['+i+'][label]" value="مخاطب جدید" class="regular-text"></td>';
      row += '<td><input type="color" name="custom_audiences['+i+'][color]" value="#1a237e"></td>';
      row += '<td><input type="text" name="custom_audiences['+i+'][icon]" value="user" style="width:90px;"></td>';
      row += '<td><label><input type="checkbox" name="custom_audiences['+i+'][active]" value="1" checked> فعال</label></td>';
      row += '<td><button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-aud-remove" style="padding:4px 8px;font-size:11px;">حذف</button></td></tr>';
      $('#cgs-audience-tbody').append(row);
    });
    $(document).on('click', '.cgs-aud-remove', function(){
      if ($('#cgs-audience-tbody tr').length <= 1) { alert('حداقل یک مخاطب لازم است'); return; }
      $(this).closest('tr').remove();
    });
  });
  </script>

  <h3>برچسب انواع درخواست (متناظر با مخاطبین)</h3>
  <p class="description">این برچسب‌ها از فهرست مخاطبین بالا ساخته می‌شوند. پس از ذخیره مخاطبین، در داشبورد و آمار استفاده می‌شوند.</p>
  <table class="form-table">
    <?php
    $type_labels = cgs_get_option( 'type_labels', array() );
    foreach ( $audiences as $a ) :
        $k = $a['key'] ?? '';
        if ( ! $k ) continue;
        $lab = $type_labels[ $k ] ?? ( $a['label'] ?? $k );
    ?>
    <tr>
      <th><?php echo esc_html( $k ); ?></th>
      <td><input type="text" name="type_labels[<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($lab); ?>" class="regular-text"></td>
    </tr>
    <?php endforeach; ?>
  </table>

</div><!-- /audience -->

<div id="cgs-tab-inquiry" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.inquiry' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help"><summary>راهنما: استعلام و API</summary><div class="cgs-help-body">
<p>اینجا همه تنظیمات مربوط به استعلام کد ملی، موبایل، شبا و ارائه‌دهندگان API قرار دارد.</p>
<ol>
<li>برای هر استعلام حالت دستی / آزمایشی / خودکار را انتخاب کنید.</li>
<li>در حالت خودکار باید URL و کلید API قرارداد رسمی داشته باشید.</li>
<li>با «+ افزودن استعلام» ارائه‌دهنده جدید اضافه کنید.</li>
</ol>
<div class="cgs-help-tip">⚠️ این بخش فقط در همین تب دیده می‌شود؛ اگر جایی دیگر دیدید صفحه را سخت‌رفرش کنید (Ctrl+Shift+R).</div>
</div></details>

        <h2>استعلام کد ملی (شاهکار / ثبت احوال)</h2>
        <table class="form-table">
            <tr>
                <th>بررسی مالکیت موبایل</th>
                <td>
                    <label><input type="checkbox" name="shahkar_mobile" value="1" <?php checked( cgs_get_option( 'shahkar_mobile', 0 ), 1 ); ?>> فعال (نیاز به API ارائه‌دهنده)</label>
                    <p class="description">بدون کلید API، فقط هشدار نمایشی ثبت می‌شود. برای استعلام واقعی باید با اپراتور یا سرویس شاهکار قرارداد داشته باشید.</p>
                </td>
            </tr>
            <tr>
                <th>بررسی نام با کد ملی</th>
                <td>
                    <label><input type="checkbox" name="shahkar_name" value="1" <?php checked( cgs_get_option( 'shahkar_name', 0 ), 1 ); ?>> فعال (نیاز به API ثبت احوال)</label>
                </td>
            </tr>
            <tr>
                <th>کلید API استعلام</th>
                <td><input type="text" name="shahkar_api_key" value="<?php echo esc_attr( cgs_get_option( 'shahkar_api_key', '' ) ); ?>" class="regular-text" dir="ltr"></td>
            </tr>
        </table>

  <h2>خودکارسازی استعلام‌ها</h2>
  <p class="description"><strong>مهم:</strong> APIهای ثبت احوال، پست، شاهکار موبایل، چک برگشتی و اعتباریتو نیاز به قرارداد رسمی و کلید دارند. تا آن زمان می‌توانید حالت <em>آزمایشی (Demo)</em> را روشن کنید.</p>
  <table class="form-table">
    <tr><th>کد ملی ← ثبت احوال</th><td>
      <label><input type="checkbox" name="av_national_id_enabled" value="1" <?php checked(cgs_get_option('av_national_id_enabled',0),1); ?>> فعال</label>
      <select name="av_national_id_mode">
        <option value="manual" <?php selected(cgs_get_option('av_national_id_mode','manual'),'manual'); ?>>دستی</option>
        <option value="demo" <?php selected(cgs_get_option('av_national_id_mode'),'demo'); ?>>آزمایشی</option>
        <option value="auto" <?php selected(cgs_get_option('av_national_id_mode'),'auto'); ?>>خودکار (API)</option>
      </select>
      <p>API URL: <input type="url" name="av_national_id_api_url" value="<?php echo esc_attr(cgs_get_option('av_national_id_api_url')); ?>" class="regular-text" dir="ltr"></p>
      <p>API Key: <input type="text" name="av_national_id_api_key" value="<?php echo esc_attr(cgs_get_option('av_national_id_api_key')); ?>" class="regular-text" dir="ltr"></p>
    </td></tr>
    <tr><th>کد پستی ← پست</th><td>
      <label><input type="checkbox" name="av_postal_enabled" value="1" <?php checked(cgs_get_option('av_postal_enabled',0),1); ?>> فعال</label>
      <select name="av_postal_mode">
        <option value="manual" <?php selected(cgs_get_option('av_postal_mode','manual'),'manual'); ?>>دستی</option>
        <option value="demo" <?php selected(cgs_get_option('av_postal_mode'),'demo'); ?>>آزمایشی</option>
        <option value="auto" <?php selected(cgs_get_option('av_postal_mode'),'auto'); ?>>خودکار</option>
      </select>
      <p>پیام همخوانی با سند: <input type="text" name="av_postal_notice" value="<?php echo esc_attr(cgs_get_option('av_postal_notice','کد پستی باید با قولنامه/سند مالکیت همخوانی داشته باشد.')); ?>" class="large-text"></p>
    </td></tr>
    <tr><th>موبایل ↔ کد ملی</th><td>
      <label><input type="checkbox" name="av_mobile_enabled" value="1" <?php checked(cgs_get_option('av_mobile_enabled',0),1); ?>> فعال</label>
      <select name="av_mobile_mode">
        <option value="manual" <?php selected(cgs_get_option('av_mobile_mode','manual'),'manual'); ?>>دستی</option>
        <option value="demo" <?php selected(cgs_get_option('av_mobile_mode'),'demo'); ?>>آزمایشی</option>
        <option value="auto" <?php selected(cgs_get_option('av_mobile_mode'),'auto'); ?>>خودکار</option>
      </select>
    </td></tr>
    <tr><th>شبا / حساب بانکی</th><td>
      <label><input type="checkbox" name="av_sheba_enabled" value="1" <?php checked(cgs_get_option('av_sheba_enabled',0),1); ?>> فعال</label>
      <select name="av_sheba_mode">
        <option value="manual" <?php selected(cgs_get_option('av_sheba_mode','manual'),'manual'); ?>>دستی</option>
        <option value="demo" <?php selected(cgs_get_option('av_sheba_mode'),'demo'); ?>>آزمایشی</option>
        <option value="auto" <?php selected(cgs_get_option('av_sheba_mode'),'auto'); ?>>خودکار</option>
      </select>
      <p>حداکثر چک در راه: <input type="number" name="av_sheba_max_pending_checks" value="<?php echo esc_attr(cgs_get_option('av_sheba_max_pending_checks',3)); ?>" style="width:80px;"></p>
      <p>حداکثر مبلغ چک در راه (ریال): <input type="number" name="av_sheba_max_pending_amount" value="<?php echo esc_attr(cgs_get_option('av_sheba_max_pending_amount',500000000)); ?>" class="regular-text"></p>
      <p>در صورت تجاوز:
        <select name="av_sheba_on_exceed">
          <option value="admin" <?php selected(cgs_get_option('av_sheba_on_exceed','admin'),'admin'); ?>>تصمیم ادمین</option>
          <option value="auto_reject" <?php selected(cgs_get_option('av_sheba_on_exceed'),'auto_reject'); ?>>رد خودکار</option>
        </select>
      </p>
    </td></tr>
    <tr><th>اعتبارسنجی (etebarito)</th><td>
      <label><input type="checkbox" name="av_credit_enabled" value="1" <?php checked(cgs_get_option('av_credit_enabled',0),1); ?>> فعال</label>
      <select name="av_credit_mode">
        <option value="manual" <?php selected(cgs_get_option('av_credit_mode','manual'),'manual'); ?>>دستی</option>
        <option value="demo" <?php selected(cgs_get_option('av_credit_mode'),'demo'); ?>>آزمایشی</option>
        <option value="auto" <?php selected(cgs_get_option('av_credit_mode'),'auto'); ?>>خودکار</option>
      </select>
      <p>مبلغ دریافتی از متقاضی (ریال): <input type="number" name="av_credit_fee" value="<?php echo esc_attr(cgs_get_option('av_credit_fee',0)); ?>" class="regular-text"></p>
      <p class="description">درگاه پرداخت و تسویه با nics24 پس از قرارداد رسمی.</p>
    </td></tr>
  </table>

  <h3>ارائه‌دهندگان استعلام (قابل افزودن)</h3>
  <p class="description">از لیست نوع استعلام را انتخاب کنید تا باکس تنظیمات آن اضافه شود.</p>
  <p>
    <select id="cgs-inq-type">
      <option value="national_id">کد ملی / ثبت احوال</option>
      <option value="postal">کد پستی / پست</option>
      <option value="mobile">مالکیت موبایل / شاهکار</option>
      <option value="sheba">شبا و چک برگشتی</option>
      <option value="credit">اعتبارسنجی (etebarito)</option>
      <option value="company">استعلام شرکت / شناسه ملی</option>
      <option value="plate">پلاک خودرو</option>
    </select>
    <button type="button" id="cgs-inq-add" class="cgs-btn-admin cgs-btn-admin-primary">+ افزودن استعلام</button>
  </p>
  <div id="cgs-inquiry-providers">
    <?php
    $providers = cgs_get_option( 'inquiry_providers', array() );
    if ( ! is_array( $providers ) ) $providers = array();
    foreach ( $providers as $pi => $pr ) :
    ?>
    <div class="cgs-inq-box" style="border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:10px;background:#fafbff;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <strong><?php echo esc_html( $pr['type'] ?? '' ); ?></strong>
        <button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-inq-remove" style="font-size:11px;padding:4px 8px;">حذف</button>
      </div>
      <input type="hidden" name="inquiry_providers[<?php echo (int)$pi; ?>][type]" value="<?php echo esc_attr($pr['type']??''); ?>">
      <p>نام نمایشی <input type="text" name="inquiry_providers[<?php echo (int)$pi; ?>][label]" value="<?php echo esc_attr($pr['label']??''); ?>" class="regular-text"></p>
      <p>API URL <input type="url" name="inquiry_providers[<?php echo (int)$pi; ?>][api_url]" value="<?php echo esc_attr($pr['api_url']??''); ?>" class="regular-text" dir="ltr"></p>
      <p>API Key <input type="text" name="inquiry_providers[<?php echo (int)$pi; ?>][api_key]" value="<?php echo esc_attr($pr['api_key']??''); ?>" class="regular-text" dir="ltr"></p>
      <p><label><input type="checkbox" name="inquiry_providers[<?php echo (int)$pi; ?>][enabled]" value="1" <?php checked(!empty($pr['enabled'])); ?>> فعال</label></p>
    </div>
    <?php endforeach; ?>
  </div>
  <script>
  jQuery(function($){
    var iq = $('#cgs-inquiry-providers .cgs-inq-box').length;
    $('#cgs-inq-add').on('click', function(){
      var t = $('#cgs-inq-type').val();
      var lab = $('#cgs-inq-type option:selected').text();
      var i = iq++;
      var html = '<div class="cgs-inq-box" style="border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:10px;background:#fafbff;">';
      html += '<div style="display:flex;justify-content:space-between;"><strong>'+lab+'</strong>';
      html += '<button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-inq-remove" style="font-size:11px;padding:4px 8px;">حذف</button></div>';
      html += '<input type="hidden" name="inquiry_providers['+i+'][type]" value="'+t+'">';
      html += '<p>نام نمایشی <input type="text" name="inquiry_providers['+i+'][label]" value="'+lab+'" class="regular-text"></p>';
      html += '<p>API URL <input type="url" name="inquiry_providers['+i+'][api_url]" value="" class="regular-text" dir="ltr"></p>';
      html += '<p>API Key <input type="text" name="inquiry_providers['+i+'][api_key]" value="" class="regular-text" dir="ltr"></p>';
      html += '<p><label><input type="checkbox" name="inquiry_providers['+i+'][enabled]" value="1" checked> فعال</label></p></div>';
      $('#cgs-inquiry-providers').append(html);
    });
    $(document).on('click', '.cgs-inq-remove', function(){ $(this).closest('.cgs-inq-box').remove(); });
  });
  </script>

</div><!-- /inquiry -->

<div id="cgs-tab-payment" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.payment' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
  <h2>درگاه‌های پرداخت ایرانی</h2>
  <p class="description">واسطه (زرین‌پال، آیدی‌پی، …) یا درگاه مستقیم بانک. پس از انتخاب، فیلدهای مربوط را پر کنید. آدرس بازگشت خودکار: <code dir="ltr"><?php echo esc_html( class_exists('CGS_Payment') ? CGS_Payment::callback_url() : '' ); ?></code></p>
  <?php $pay = class_exists('CGS_Payment') ? CGS_Payment::get_settings() : array(); $gws = class_exists('CGS_Payment') ? CGS_Payment::gateways_list() : array(); ?>
  <table class="form-table">
    <tr>
      <th>درگاه فعال</th>
      <td>
        <select name="cgs_pay_gateway" id="cgs_pay_gateway" class="regular-text">
          <?php foreach ( $gws as $gk => $gi ) : ?>
            <option value="<?php echo esc_attr($gk); ?>" <?php selected( $pay['gateway'] ?? '', $gk ); ?>>
              <?php echo esc_html( $gi['label'] ); ?> (<?php echo $gi['type']==='bank' ? 'بانکی' : ( $gi['type']==='aggregator' ? 'واسطه' : '—' ); ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>
    <tr>
      <th>حالت آزمایشی (Sandbox)</th>
      <td><label><input type="checkbox" name="cgs_pay_sandbox" value="1" <?php checked( !empty($pay['sandbox']), true ); ?>> فعال (برای زرین‌پال، آیدی‌پی، پی‌آیر، زیبال)</label></td>
    </tr>
    <tr>
      <th>Merchant ID / پذیرنده</th>
      <td><input type="text" name="cgs_pay_merchant_id" value="<?php echo esc_attr($pay['merchant_id']??''); ?>" class="regular-text" dir="ltr"></td>
    </tr>
    <tr>
      <th>API Key / توکن / PIN</th>
      <td><input type="text" name="cgs_pay_api_key" value="<?php echo esc_attr($pay['api_key']??''); ?>" class="regular-text" dir="ltr" autocomplete="off"></td>
    </tr>
    <tr>
      <th>Terminal ID</th>
      <td><input type="text" name="cgs_pay_terminal_id" value="<?php echo esc_attr($pay['terminal_id']??''); ?>" class="regular-text" dir="ltr">
      <p class="description">ملت، سامان، سداد، پاسارگاد</p></td>
    </tr>
    <tr>
      <th>نام کاربری درگاه</th>
      <td><input type="text" name="cgs_pay_username" value="<?php echo esc_attr($pay['username']??''); ?>" class="regular-text" dir="ltr"></td>
    </tr>
    <tr>
      <th>رمز عبور درگاه</th>
      <td><input type="password" name="cgs_pay_password" value="<?php echo esc_attr($pay['password']??''); ?>" class="regular-text" dir="ltr" autocomplete="new-password"></td>
    </tr>
    <tr>
      <th>واحد مبلغ ارسالی</th>
      <td>
        <select name="cgs_pay_currency">
          <option value="IRR" <?php selected(($pay['currency']??'IRR'),'IRR'); ?>>ریال (IRR)</option>
          <option value="IRT" <?php selected(($pay['currency']??''),'IRT'); ?>>تومان (برای نکست‌پی/پی‌پینگ)</option>
        </select>
      </td>
    </tr>
    <tr>
      <th>توضیح پیش‌فرض تراکنش</th>
      <td><input type="text" name="cgs_pay_description" value="<?php echo esc_attr($pay['description']??'پرداخت شهر قسط'); ?>" class="large-text"></td>
    </tr>
  </table>
  <h3>راهنمای سریع فیلدها</h3>
  <ul style="line-height:1.8;">
    <li><strong>زرین‌پال:</strong> Merchant ID</li>
    <li><strong>آیدی‌پی / نکست‌پی / پی‌آیر / پی‌پینگ:</strong> API Key</li>
    <li><strong>زیبال:</strong> Merchant (در حالت تست: zibal)</li>
    <li><strong>ملت:</strong> Terminal + Username + Password (نیاز به SOAP)</li>
    <li><strong>سامان:</strong> TerminalId</li>
    <li><strong>سداد (ملی):</strong> Terminal + MerchantId + TerminalKey در API Key</li>
    <li><strong>پارسیان:</strong> LoginAccount در API Key (SOAP)</li>
    <li><strong>پاسارگاد:</strong> گواهی دیجیتال — پس از دریافت از بانک</li>
  </ul>
</div><!-- /payment -->

<div id="cgs-tab-sound" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.sound' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help"><summary>راهنما: صدا</summary><div class="cgs-help-body">
<p>نوع صدای موفقیت فرم و بلندی آن را انتخاب کنید.</p>
<ol>
<li>تیک پخش صدا را روشن کنید.</li>
<li>یکی از ۲۰ نوع صدا را از لیست انتخاب کنید.</li>
<li>با دکمه «پیش‌نمایش» گوش دهید، سپس ذخیره کنید.</li>
</ol>
</div></details>

  <h2>دسترسی‌پذیری و صدا</h2>
        <?php
        $cgs_sound_types = array(
            'chime'      => 'زنگ ملایم (Chime)',
            'bell'       => 'زنگوله',
            'success'    => 'موفقیت کوتاه',
            'sparkle'    => 'درخشش',
            'coin'       => 'سکه',
            'pop'        => 'پاپ',
            'ding'       => 'دینگ',
            'double'     => 'دوبل دینگ',
            'rising'     => 'صعودی',
            'falling'    => 'نزولی',
            'soft_piano' => 'پیانوی نرم',
            'marimba'    => 'مارimba',
            'notify'     => 'اعلان',
            'click'      => 'کلیک نرم',
            'whoosh'     => 'ووش',
            'levelup'    => 'ارتقا سطح',
            'fanfare'    => 'کوتاه جشن',
            'glass'      => 'شیشه',
            'bubble'     => 'حباب',
            'harp'       => 'چنگ',
        );
        $cur_sound = cgs_get_option( 'sound_type', 'chime' );
        ?>
        <table class="form-table">
            <tr>
                <th>صدای موفقیت فرم</th>
                <td>
                    <label>
                        <input type="checkbox" name="sound_enabled" value="1" <?php checked( cgs_get_option( 'sound_enabled', 1 ), 1 ); ?>>
                        پخش صدا پس از ثبت موفق فرم و روی دکمه‌ها
                    </label>
                </td>
            </tr>
            <tr>
                <th>نوع صدا</th>
                <td>
                    <select name="sound_type" id="cgs-sound-type" class="regular-text">
                        <?php foreach ( $cgs_sound_types as $sk => $sl ) : ?>
                            <option value="<?php echo esc_attr( $sk ); ?>" <?php selected( $cur_sound, $sk ); ?>><?php echo esc_html( $sl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="cgs-sound-preview" class="button" style="margin-right:8px;">▶ پیش‌نمایش صدا</button>
                    <p class="description">۲۰ نوع صدا — قبل از ذخیره با دکمه پیش‌نمایش گوش دهید.</p>
                </td>
            </tr>
            <tr>
                <th>بلندی صدا</th>
                <td>
                    <input type="range" name="sound_volume" id="cgs-sound-volume" min="0" max="100" title="بلندی تا ۱۰۰٪" value="<?php echo esc_attr( cgs_get_option( 'sound_volume', 75 ) ); ?>" style="width:200px;vertical-align:middle;">
                    <span id="cgs-sound-vol-label"><?php echo esc_html( cgs_get_option( 'sound_volume', 40 ) ); ?>٪</span>
                </td>
            </tr>
        </table>
<script>
jQuery(function($){
  var patterns = {
    chime: [[523,0.15],[784,0.15],[1046,0.25]],
    bell: [[880,0.25],[440,0.35]],
    success: [[392,0.12],[523,0.12],[659,0.12],[784,0.28]],
    sparkle: [[1500,0.05],[1800,0.05],[2100,0.05],[2400,0.12]],
    coin: [[1200,0.06],[1600,0.18]],
    pop: [[180,0.08],[90,0.12]],
    ding: [[1100,0.28]],
    double: [[660,0.12],[0,0.08],[660,0.15]],
    rising: [[300,0.1],[450,0.1],[600,0.1],[800,0.18]],
    falling: [[900,0.1],[600,0.1],[350,0.2]],
    soft_piano: [[261,0.2],[329,0.2],[392,0.25]],
    marimba: [[350,0.15],[466,0.15],[587,0.2]],
    notify: [[800,0.12],[1000,0.2]],
    click: [[200,0.05]],
    whoosh: [[150,0.2],[80,0.2]],
    levelup: [[330,0.1],[415,0.1],[523,0.1],[659,0.1],[880,0.22]],
    fanfare: [[392,0.12],[523,0.12],[659,0.12],[784,0.12],[1046,0.3]],
    glass: [[1800,0.1],[2200,0.15]],
    bubble: [[500,0.08],[350,0.1],[220,0.14]],
    harp: [[196,0.12],[247,0.12],[294,0.12],[370,0.18]]
  };
  function playPattern(key, vol){
    try {
      var ctx = window.cgsSoundCtx || (window.cgsSoundCtx = new (window.AudioContext||window.webkitAudioContext)());
      var seq = patterns[key] || patterns.chime;
      var t0 = ctx.currentTime;
      var gMaster = Math.max(0.05, Math.min(1, (vol||70)/100)) * 0.95;
      seq.forEach(function(note, i){
        var o = ctx.createOscillator();
        var g = ctx.createGain();
        o.type = (key==='whoosh'||key==='bubble') ? 'triangle' : (key==='click'?'square':'sine');
        o.frequency.value = note[0];
        g.gain.value = gMaster;
        o.connect(g); g.connect(ctx.destination);
        var start = t0 + i * 0.12;
        o.start(start);
        g.gain.exponentialRampToValueAtTime(0.001, start + note[1]);
        o.stop(start + note[1] + 0.02);
      });
    } catch(e){ alert('مرورگر پخش صدا را پشتیبانی نمی‌کند'); }
  }
  $('#cgs-sound-preview').on('click', function(e){
    e.preventDefault();
    playPattern($('#cgs-sound-type').val(), parseInt($('#cgs-sound-volume').val(),10)||40);
  });
  $('#cgs-sound-volume').on('input', function(){
    $('#cgs-sound-vol-label').text($(this).val()+'٪');
  });
});
</script>


<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره همه تنظیمات</button>
</p>

</div><!-- /sound -->

<div id="cgs-tab-appearance" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.appearance' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>

<details class="cgs-help" open>
  <summary>راهنما: تم‌های رنگی و ظاهر</summary>
  <div class="cgs-help-body">
    <p>از کارت‌های رنگی زیر یک <strong>تم آماده</strong> انتخاب کنید تا رنگ اصلی، دکمه‌ها و حس کلی پنل یک‌دست شود.</p>
    <ol>
      <li>روی کارت تم کلیک کنید تا انتخاب شود (حاشیه آبی).</li>
      <li>دکمه <strong>ذخیره تنظیمات این بخش</strong> را بزنید.</li>
      <li>در صورت نیاز رنگ‌های دستی پایین‌تر را هم تنظیم کنید.</li>
    </ol>
    <div class="cgs-help-tip">💡 تم فقط ظاهر مدیریت و فرم‌ها را هماهنگ می‌کند؛ داده‌های کاربران عوض نمی‌شود.</div>
  </div>
</details>

<h2>تم‌های رنگی آماده</h2>
<div class="cgs-themes-box" id="cgs-themes-box">
<?php
$cgs_themes = array(
  'navy'     => array( 'name' => 'نیلی کلاسیک', 'c' => array('#1a237e','#3949ab','#c5cae9','#ffd54f') ),
  'indigo'   => array( 'name' => 'نیلی ملایم', 'c' => array('#312e81','#6366f1','#e0e7ff','#a5b4fc') ),
  'emerald'  => array( 'name' => 'زمردی', 'c' => array('#064e3b','#10b981','#d1fae5','#6ee7b7') ),
  'teal'     => array( 'name' => 'فیروزه‌ای', 'c' => array('#134e4a','#14b8a6','#ccfbf1','#5eead4') ),
  'sky'      => array( 'name' => 'آسمانی', 'c' => array('#0c4a6e','#0ea5e9','#e0f2fe','#7dd3fc') ),
  'ocean'    => array( 'name' => 'اقیانوسی', 'c' => array('#1e3a5f','#3b82f6','#dbeafe','#93c5fd') ),
  'violet'   => array( 'name' => 'بنفش', 'c' => array('#4c1d95','#8b5cf6','#ede9fe','#c4b5fd') ),
  'royal'    => array( 'name' => 'شاهانه', 'c' => array('#581c87','#a855f7','#f3e8ff','#e9d5ff') ),
  'rose'     => array( 'name' => 'رز', 'c' => array('#9f1239','#f43f5e','#ffe4e6','#fda4af') ),
  'blush'    => array( 'name' => 'صورتی ملایم', 'c' => array('#9d174d','#ec4899','#fce7f3','#f9a8d4') ),
  'sunset'   => array( 'name' => 'غروب', 'c' => array('#9a3412','#f97316','#ffedd5','#fdba74') ),
  'amber'    => array( 'name' => 'کهربایی', 'c' => array('#92400e','#f59e0b','#fef3c7','#fcd34d') ),
  'forest'   => array( 'name' => 'جنگلی', 'c' => array('#14532d','#22c55e','#dcfce7','#86efac') ),
  'lime'     => array( 'name' => 'سبز لیمویی', 'c' => array('#3f6212','#84cc16','#ecfccb','#bef264') ),
  'graphite' => array( 'name' => 'گرافیت', 'c' => array('#1e293b','#64748b','#f1f5f9','#94a3b8') ),
  'slate'    => array( 'name' => 'سنگی', 'c' => array('#0f172a','#475569','#e2e8f0','#cbd5e1') ),
  'copper'   => array( 'name' => 'مسی', 'c' => array('#7c2d12','#c2410c','#ffedd5','#fb923c') ),
  'mint'     => array( 'name' => 'نعنایی', 'c' => array('#115e59','#2dd4bf','#f0fdfa','#99f6e4') ),
  'lavender' => array( 'name' => 'اسطوخودوس', 'c' => array('#5b21b6','#a78bfa','#f5f3ff','#ddd6fe') ),
  'sand'     => array( 'name' => 'شنی', 'c' => array('#78350f','#d97706','#fffbeb','#fde68a') ),
);
$custom_themes = cgs_get_option( 'custom_ui_themes', array() );
if ( is_array( $custom_themes ) ) {
  foreach ( $custom_themes as $ck => $cv ) {
    if ( ! empty( $cv['name'] ) && ! empty( $cv['c'] ) && is_array( $cv['c'] ) ) {
      $cgs_themes[ sanitize_key( $ck ) ] = array( 'name' => $cv['name'], 'c' => $cv['c'] );
    }
  }
}
$cur_theme = cgs_get_option( 'ui_theme', 'navy' );
foreach ( $cgs_themes as $tk => $tv ) :
  $active = ( $cur_theme === $tk ) ? ' is-active' : '';
?>
  <label class="cgs-theme-card<?php echo $active; ?>">
    <input type="radio" name="ui_theme" value="<?php echo esc_attr($tk); ?>" <?php checked( $cur_theme, $tk ); ?> style="display:none;">
    <div class="cgs-theme-swatches">
      <?php foreach ( $tv['c'] as $col ) : ?>
        <span style="background:<?php echo esc_attr($col); ?>"></span>
      <?php endforeach; ?>
    </div>
    <div class="cgs-theme-name"><?php echo esc_html( $tv['name'] ); ?></div>
  </label>
<?php endforeach; ?>
</div>
<script>
jQuery(function($){
  $('#cgs-themes-box').on('click', '.cgs-theme-card', function(){
    $('#cgs-themes-box .cgs-theme-card').removeClass('is-active');
    $(this).addClass('is-active');
    $(this).find('input[type=radio]').prop('checked', true);
  });
});
</script>
<p style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
  <button type="submit" name="cgs_reset_ui_theme" value="1" class="button">ریست تم به پیش‌فرض (نیلی)</button>
</p>
<div class="cgs-set-card" style="margin-top:12px;">
  <h3>تم سفارشی</h3>
  <p class="description">نام + چهار رنگ (اصلی، ثانویه، روشن، تاکید). پس از ذخیره در لیست تم‌ها می‌ماند.</p>
  <div class="cgs-set-grid">
    <p><label>نام تم</label><input type="text" name="custom_theme_name" class="regular-text" placeholder="مثلاً برند من"></p>
    <p><label>کلید انگلیسی</label><input type="text" name="custom_theme_key" class="regular-text" dir="ltr" placeholder="mybrand"></p>
  </div>
  <p style="display:flex;flex-wrap:wrap;gap:10px;">
    <label>رنگ1 <input type="color" name="custom_theme_c1" value="#1a237e"></label>
    <label>رنگ2 <input type="color" name="custom_theme_c2" value="#3949ab"></label>
    <label>رنگ3 <input type="color" name="custom_theme_c3" value="#c5cae9"></label>
    <label>رنگ4 <input type="color" name="custom_theme_c4" value="#ffd54f"></label>
  </p>
  <button type="submit" name="cgs_add_custom_theme" value="1" class="button button-primary">افزودن تم سفارشی</button>
</div>

        <h2>سفارشی‌سازی رنگ‌ها و برچسب‌ها</h2>
        <p class="description">برچسب‌ها و رنگ‌های داشبورد، نمودارها و CRM را از اینجا تغییر دهید. برای ریست، فیلد را خالی بگذارید یا دکمه ریست را بزنید.</p>

        <h3>برچسب وضعیت درخواست‌ها</h3>
        <table class="form-table">
            <?php
            $status_labels = cgs_get_status_labels();
            $status_keys = array( 'pending' => 'pending', 'review' => 'review', 'approved' => 'approved', 'rejected' => 'rejected' );
            foreach ( $status_labels as $sk => $sl ) :
            ?>
            <tr>
                <th><code><?php echo esc_html( $sk ); ?></code></th>
                <td><input type="text" name="status_labels[<?php echo esc_attr( $sk ); ?>]" value="<?php echo esc_attr( $sl ); ?>" class="regular-text"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>رنگ وضعیت‌ها (نمودار و کارت‌ها)</h3>
        <table class="form-table">
            <?php
            $status_colors = cgs_get_status_colors();
            foreach ( $status_colors as $sk => $sc ) :
            ?>
            <tr>
                <th><?php echo esc_html( $status_labels[ $sk ] ?? $sk ); ?></th>
                <td><input type="color" name="status_colors[<?php echo esc_attr( $sk ); ?>]" value="<?php echo esc_attr( $sc ); ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>برچسب انواع درخواست</h3>
        <table class="form-table">
            <?php
            $type_labels = cgs_get_type_labels();
            foreach ( $type_labels as $tk => $tl ) :
            ?>
            <tr>
                <th><code><?php echo esc_html( $tk ); ?></code></th>
                <td><input type="text" name="type_labels[<?php echo esc_attr( $tk ); ?>]" value="<?php echo esc_attr( $tl ); ?>" class="regular-text"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>رنگ انواع درخواست (نمودار میله‌ای)</h3>
        <table class="form-table">
            <?php
            $type_colors = cgs_get_type_colors();
            $type_keys = array_keys( $type_labels );
            foreach ( $type_keys as $i => $tk ) :
                $col = $type_colors[ $i ] ?? '#1a237e';
            ?>
            <tr>
                <th><?php echo esc_html( $type_labels[ $tk ] ?? $tk ); ?></th>
                <td><input type="color" name="type_colors[]" value="<?php echo esc_attr( $col ); ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>برچسب مراحل CRM</h3>
        <table class="form-table">
            <?php
            $crm_labels = cgs_get_crm_stage_labels();
            foreach ( $crm_labels as $ck => $cl ) :
            ?>
            <tr>
                <th><code><?php echo esc_html( $ck ); ?></code></th>
                <td><input type="text" name="crm_stage_labels[<?php echo esc_attr( $ck ); ?>]" value="<?php echo esc_attr( $cl ); ?>" class="regular-text"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>رنگ مراحل CRM</h3>
        <table class="form-table">
            <?php
            $crm_colors = cgs_get_crm_stage_colors();
            foreach ( $crm_colors as $ck => $cc ) :
            ?>
            <tr>
                <th><?php echo esc_html( $crm_labels[ $ck ] ?? $ck ); ?></th>
                <td><input type="color" name="crm_stage_colors[<?php echo esc_attr( $ck ); ?>]" value="<?php echo esc_attr( $cc ); ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p>
            <button type="submit" class="button button-primary" name="cgs_save_settings" value="1">ذخیره رنگ‌ها و برچسب‌ها</button>
            <button type="submit" class="button" name="cgs_reset_colors_labels" value="1" onclick="return confirm('برچسب‌ها و رنگ‌ها به پیش‌فرض برگردند؟');">ریست رنگ‌ها و برچسب‌ها</button>
        </p>

        
        
        <h2>آپلود فونت‌های گروه B (نازنین / تیتر)</h2>
        <p class="description">فایل‌های <code>woff2</code> یا <code>woff</code> را آپلود کنید تا برای همه بازدیدکنندگان سایت لود شوند. نام پیشنهادی: BNazanin.woff2 ، BNazanin-Bold.woff2 ، BTitrBold.woff2</p>
        <table class="form-table">
            <tr>
                <th>B Nazanin عادی</th>
                <td><input type="file" name="cgs_font_bnazanin" accept=".woff2,.woff,.ttf"></td>
            </tr>
            <tr>
                <th>B Nazanin Bold</th>
                <td><input type="file" name="cgs_font_bnazanin_bold" accept=".woff2,.woff,.ttf"></td>
            </tr>
            <tr>
                <th>B Titr Bold</th>
                <td><input type="file" name="cgs_font_btitr" accept=".woff2,.woff,.ttf"></td>
            </tr>
        </table>
        <p class="description">پس از آپلود، در فرم‌ساز می‌توانید «بی نازنین» و «بی تیتر» را انتخاب کنید.</p>

        
</div><!-- /appearance -->

<div id="cgs-tab-jalali" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.jalali' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
        <h2>تنظیمات پیشرفته تقویم شمسی</h2>
        <?php $js = function_exists('cgs_get_jalali_settings') ? cgs_get_jalali_settings() : array(); ?>
        <table class="form-table">
            <tr>
                <th>نوع تقویم پیش‌فرض</th>
                <td>
                    <select name="jalali_settings[calendar_type]">
                        <option value="jalali" <?php selected($js['calendar_type']??'','jalali'); ?>>فقط شمسی (جلالی)</option>
                        <option value="gregorian" <?php selected($js['calendar_type']??'','gregorian'); ?>>فقط میلادی</option>
                        <option value="both" <?php selected($js['calendar_type']??'','both'); ?>>هر دو (قابلیت سوییچ)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>بازه سال</th>
                <td>
                    از <input type="number" name="jalali_settings[start_year]" value="<?php echo esc_attr($js['start_year']??'1320'); ?>" style="width:90px;" min="1300" max="1450">
                    تا <input type="number" name="jalali_settings[end_year]" value="<?php echo esc_attr($js['end_year']??'1410'); ?>" style="width:90px;" min="1300" max="1450">
                    <p class="description">سال‌های قابل انتخاب در دراپ‌داون</p>
                </td>
            </tr>
            <tr>
                <th>قالب نمایش تاریخ</th>
                <td>
                    <select name="jalali_settings[format]">
                        <?php foreach (array('YYYY/MM/DD'=>'۱۴۰۳/۰۱/۱۵','YYYY-MM-DD'=>'۱۴۰۳-۰۱-۱۵','DD/MM/YYYY'=>'۱۵/۰۱/۱۴۰۳') as $fk=>$fl): ?>
                        <option value="<?php echo esc_attr($fk); ?>" <?php selected($js['format']??'',$fk); ?>><?php echo esc_html($fl); ?> (<?php echo esc_html($fk); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>دکمه‌ها و رفتار</th>
                <td>
                    <label><input type="checkbox" name="jalali_settings[show_today_btn]" value="1" <?php checked(($js['show_today_btn']??'1'),'1'); ?>> دکمه «امروز»</label><br>
                    <label><input type="checkbox" name="jalali_settings[show_clear_btn]" value="1" <?php checked(($js['show_clear_btn']??'1'),'1'); ?>> دکمه «پاک کردن»</label><br>
                    <label><input type="checkbox" name="jalali_settings[close_on_select]" value="1" <?php checked(($js['close_on_select']??'1'),'1'); ?>> بستن خودکار پس از انتخاب</label><br>
                    <label><input type="checkbox" name="jalali_settings[default_today]" value="1" <?php checked(($js['default_today']??'0'),'1'); ?>> مقدار پیش‌فرض = امروز</label><br>
                    <label><input type="checkbox" name="jalali_settings[month_dropdown]" value="1" <?php checked(($js['month_dropdown']??'1'),'1'); ?>> انتخاب سریع ماه</label><br>
                    <label><input type="checkbox" name="jalali_settings[year_dropdown]" value="1" <?php checked(($js['year_dropdown']??'1'),'1'); ?>> انتخاب سریع سال</label><br>
                    <label><input type="checkbox" name="jalali_settings[locale_numbers]" value="1" <?php checked(($js['locale_numbers']??'0'),'1'); ?>> اعداد فارسی (۱۲۳)</label>
                </td>
            </tr>
            <tr>
                <th>محدودیت سن (فیلد تولد)</th>
                <td>
                    حداقل سن: <input type="number" name="jalali_settings[min_age]" value="<?php echo esc_attr($js['min_age']??''); ?>" style="width:70px;" min="0" max="120" placeholder="مثلاً ۱۸">
                    حداکثر سن: <input type="number" name="jalali_settings[max_age]" value="<?php echo esc_attr($js['max_age']??''); ?>" style="width:70px;" min="0" max="120" placeholder="مثلاً ۷۰">
                    <p class="description">خالی = بدون محدودیت. برای فیلدهای تاریخ تولد اعمال می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th>شروع هفته</th>
                <td>
                    <select name="jalali_settings[week_start]">
                        <option value="6" <?php selected($js['week_start']??'','6'); ?>>شنبه (ایران)</option>
                        <option value="0" <?php selected($js['week_start']??'','0'); ?>>یکشنبه</option>
                        <option value="1" <?php selected($js['week_start']??'','1'); ?>>دوشنبه</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>تم ظاهری تقویم</th>
                <td>
                    <select name="jalali_settings[theme]">
                        <option value="default" <?php selected($js['theme']??'','default'); ?>>پیش‌فرض (بنفش شهر قسط)</option>
                        <option value="gold" <?php selected($js['theme']??'','gold'); ?>>طلایی</option>
                        <option value="dark" <?php selected($js['theme']??'','dark'); ?>>تیره</option>
                        <option value="green" <?php selected($js['theme']??'','green'); ?>>سبز</option>
                    </select>
                    &nbsp; موقعیت:
                    <select name="jalali_settings[position]">
                        <option value="auto" <?php selected($js['position']??'','auto'); ?>>خودکار</option>
                        <option value="bottom" <?php selected($js['position']??'','bottom'); ?>>زیر فیلد</option>
                        <option value="top" <?php selected($js['position']??'','top'); ?>>بالای فیلد</option>
                    </select>
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-primary" name="cgs_save_settings" value="1">ذخیره تنظیمات تقویم</button>
            <button type="submit" class="button" name="cgs_reset_jalali" value="1" onclick="return confirm('تنظیمات تقویم به پیش‌فرض برگردد؟');">ریست تقویم شمسی</button>
        </p>

</div><!-- /jalali -->

<div id="cgs-tab-crm" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.crm' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
        <h2>CRM خارجی</h2>


        <table class="form-table">
            <tr>
                <th>ارائه‌دهنده</th>
                <td>
                    <select name="crm_external_provider">
                        <option value="" <?php selected( cgs_get_option( 'crm_external_provider', '' ), '' ); ?>>فقط CRM داخلی</option>
                        <option value="webhook" <?php selected( cgs_get_option( 'crm_external_provider', '' ), 'webhook' ); ?>>Webhook سفارشی</option>
                        <option value="didar" <?php selected( cgs_get_option( 'crm_external_provider', '' ), 'didar' ); ?>>دیدار</option>
                        <option value="shamsi" <?php selected( cgs_get_option( 'crm_external_provider', '' ), 'shamsi' ); ?>>شمسی</option>
                        <option value="bitrix24" <?php selected( cgs_get_option( 'crm_external_provider', '' ), 'bitrix24' ); ?>>Bitrix24</option>
                        <option value="hubspot" <?php selected( cgs_get_option( 'crm_external_provider', '' ), 'hubspot' ); ?>>HubSpot</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>آدرس Webhook / API</th>
                <td><input type="url" name="crm_external_url" value="<?php echo esc_attr( cgs_get_option( 'crm_external_url', '' ) ); ?>" class="regular-text" dir="ltr"></td>
            </tr>
            <tr>
                <th>کلید API</th>
                <td><input type="text" name="crm_external_key" value="<?php echo esc_attr( cgs_get_option( 'crm_external_key', '' ) ); ?>" class="regular-text" dir="ltr"></td>
            </tr>
        </table>

</div><!-- /crm -->



<div id="cgs-tab-fbplugins" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.fbplugins' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>

<details class="cgs-help" open style="margin:12px 0 16px;border:1px solid #c5cae9;border-radius:12px;padding:10px 14px;background:#f8fafc;">
  <summary style="cursor:pointer;font-weight:700;color:#1a237e;">📘 آموزش تنظیمات پیشرفته افزونه‌ها</summary>
  <div class="cgs-help-body" style="font-size:13px;line-height:1.75;margin-top:8px;">
    <ol style="padding-right:18px;margin:0;">
      <li><strong>موتور نمودار داینامیک</strong> را روشن کنید؛ بدون آن تنظیمات تب «نمودارها» روی داشبورد اعمال نمی‌شود.</li>
      <li>زیرمجموعه‌ها (قالب‌بندی، طرح رنگ، RTL، برچسب عدد) را به نیاز فعال کنید.</li>
      <li>به تب <strong>نمودارها</strong> بروید و نوع/انیمیشن/عنوان را تنظیم کنید.</li>
      <li>حتماً از <strong>همان تب نمودارها</strong> دکمه «ذخیره قالب‌بندی نمودار» را بزنید.</li>
      <li>داشبورد را با <kbd>Ctrl+F5</kbd> رفرش کنید تا Chart.js با تنظیمات جدید رسم شود.</li>
      <li>افزونه‌های فرم‌ساز (شرطی، شبا، درگ…) فقط روی فرم‌ساز اثر دارند و مستقل از نمودارند.</li>
    </ol>
  </div>
</details>

<h2 style="margin:0 0 8px;color:#1a237e;">🧩 افزونه‌ها و قابلیت‌های فرم‌ساز</h2>
<p class="description">هر قابلیت را جداگانه فعال/غیرفعال کنید. موتور درگ‌اند‌دراپ را هم از اینجا انتخاب کنید.</p>

<h3 style="margin:18px 0 10px;color:#312e81;">① موتور درگ‌اند‌دراپ</h3>
<?php
$dnd = cgs_get_option( 'fb_dnd_engine', 'sortablejs' );
?>
<table class="form-table">
  <tr>
    <th>کتابخانه درگ</th>
    <td>
      <label style="display:block;margin-bottom:8px;">
        <input type="radio" name="fb_dnd_engine" value="sortablejs" <?php checked( $dnd, 'sortablejs' ); ?>>
        <strong>SortableJS</strong> — پیشنهادی برای وردپرس (سبک، پایدار، handle-only)
      </label>
      <label style="display:block;margin-bottom:8px;">
        <input type="radio" name="fb_dnd_engine" value="html5" <?php checked( $dnd, 'html5' ); ?>>
        <strong>HTML5 Drag & Drop</strong> — بومی مرورگر
      </label>
      <label style="display:block;margin-bottom:8px;opacity:.85;">
        <input type="radio" name="fb_dnd_engine" value="rbd" <?php checked( $dnd, 'rbd' ); ?>>
        <strong>React Beautiful DnD</strong> — حالت سازگار (روی SortableJS با API مشابه؛ رندر کامل React نیاز به بازنویسی SPA دارد)
      </label>
      <p class="description">در پیش‌نمایش فرم‌ساز: درگ فقط از دستگیره ⋮⋮ — ورودی فیلد و تغییر عرض همزمان فعال می‌مانند.</p>
    </td>
  </tr>
</table>


<h3 style="margin:18px 0 10px;color:#312e81;">①ب — افزونه‌های نمودار داینامیک</h3>
<?php
$charts_on = (int) cgs_get_option( 'charts_module_enabled', 1 );
$cplug = cgs_get_option( 'charts_plugins', array() );
if ( ! is_array( $cplug ) ) { $cplug = array(); }
$chart_plugin_defs = array(
  'advanced_format' => array( 'قالب‌بندی پیشرفته', 'نوع نمودار، انیمیشن، راهنما، عنوان و فونت' ),
  'color_schemes'   => array( 'طرح رنگ داینامیک', 'پیش‌فرض / پررنگ / پاستلی / تک‌رنگ' ),
  'rtl_tooltips'    => array( 'راهنمای RTL', 'تولتیپ راست‌چین برای فارسی' ),
  'datalabels'      => array( 'برچسب عدد روی نمودار', 'نمایش مقدار روی میله/برش (آزمایشی)' ),
);
?>
<div id="cgs-charts-plugins-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:16px;">
  <label style="display:block;border:2px solid <?php echo $charts_on ? '#86efac' : '#e2e8f0'; ?>;border-radius:12px;padding:12px;background:<?php echo $charts_on ? '#f0fdf4' : '#f8fafc'; ?>;">
    <span style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
      <strong style="color:#1a237e;">📊 موتور نمودار داینامیک</strong>
      <input type="checkbox" name="charts_module_enabled" value="1" <?php checked( $charts_on, 1 ); ?>>
    </span>
    <span style="font-size:12px;color:#64748b;display:block;margin-top:6px;line-height:1.5;">کلید اصلی: روشن = تب نمودارها روی داشبورد اعمال می‌شود.</span>
    <?php if ( $charts_on ) : ?><span style="display:inline-block;margin-top:8px;font-size:11px;background:#bbf7d0;color:#166534;padding:2px 8px;border-radius:999px;font-weight:700;">● فعال</span><?php endif; ?>
  </label>
  <?php foreach ( $chart_plugin_defs as $ck => $cinfo ) :
    $con = array_key_exists( $ck, $cplug ) ? ! empty( $cplug[ $ck ] ) : true;
  ?>
  <label style="display:block;border:1px solid #e2e8f0;border-radius:12px;padding:12px;background:#fff;">
    <span style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
      <strong style="color:#1a237e;font-size:13px;"><?php echo esc_html( $cinfo[0] ); ?></strong>
      <input type="checkbox" name="charts_plugins[<?php echo esc_attr( $ck ); ?>]" value="1" <?php checked( $con ); ?> <?php disabled( ! $charts_on ); ?>>
    </span>
    <span style="font-size:12px;color:#64748b;line-height:1.5;display:block;margin-top:4px;"><?php echo esc_html( $cinfo[1] ); ?></span>
  </label>
  <?php endforeach; ?>
</div>
<h3 style="margin:18px 0 10px;color:#312e81;">② قابلیت‌های فرم‌ساز</h3>
<?php
$fb_plugins = cgs_get_option( 'fb_plugins', array() );
if ( ! is_array( $fb_plugins ) ) { $fb_plugins = array(); }
$plugin_defs = array(
  'cond'      => array( 'منطق شرطی فیلدها', 'نمایش/مخفی بر اساس مقدار فیلد دیگر' ),
  'matrix'    => array( 'ماتریس داده', 'جدول پویا با جمع و میانگین' ),
  'jalali'    => array( 'تقویم شمسی', 'انتخاب تاریخ جلالی' ),
  'landline'  => array( 'تلفن ثابت + کد', 'کد شهرستان خودکار' ),
  'sheba'     => array( 'اعتبارسنجی شبا', 'بررسی ارقام شبا' ),
  'signature' => array( 'امضای دیجیتال', 'بوم امضا در فرم' ),
  'sound'     => array( 'افکت صدا', 'صدای پس از ثبت' ),
  'resize'    => array( 'تغییر عرض فیلد', 'کشیدن لبه در پیش‌نمایش' ),
  'dnd'       => array( 'درگ‌اند‌دراپ فیلدها', 'جابجایی باکس‌ها در پیش‌نمایش' ),
);
?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;">
<?php foreach ( $plugin_defs as $pkey => $pinfo ) :
  $on = array_key_exists( $pkey, $fb_plugins ) ? ! empty( $fb_plugins[ $pkey ] ) : true;
?>
  <label style="display:block;border:1px solid #e2e8f0;border-radius:12px;padding:12px;background:#f8fafc;cursor:pointer;">
    <span style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px;">
      <strong style="color:#1a237e;font-size:13px;"><?php echo esc_html( $pinfo[0] ); ?></strong>
      <input type="checkbox" name="fb_plugins[<?php echo esc_attr( $pkey ); ?>]" value="1" <?php checked( $on ); ?>>
    </span>
    <span style="font-size:12px;color:#64748b;line-height:1.5;"><?php echo esc_html( $pinfo[1] ); ?></span>
  </label>
<?php endforeach; ?>
</div>

<details class="cgs-help" style="margin-top:16px;" open>
  <summary style="cursor:pointer;font-weight:700;color:#1a237e;">راهنما: این تب چیست؟</summary>
  <div class="cgs-help-body" style="font-size:13px;line-height:1.7;">
    <p>این بخش فقط کنترل افزونه‌ها و موتور درگ فرم‌ساز است — با تب «عمومی و برند» فرق دارد.</p>
    <ol>
      <li>موتور درگ را انتخاب کنید (پیش‌فرض SortableJS).</li>
      <li>قابلیت‌های لازم را تیک بزنید.</li>
      <li>ذخیره تنظیمات این بخش را بزنید.</li>
      <li>به فرم‌ساز بروید و پیش‌نمایش تمام‌عرض پایین صفحه را تست کنید.</li>
    </ol>
  </div>
</details>
</div>

<div id="cgs-tab-calculator" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.calculator' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات محاسبه‌گر</button>
</p>
<h2>تنظیمات محاسبه‌گر اقساط</h2>
<p class="description">شورت‌کد: <code>[cgs_installment_calculator]</code> — این مقادیر پیش‌فرض فرم محاسبه‌گر هستند.</p>
<table class="form-table">
<tr><th>مبلغ پیش‌فرض (ریال)</th><td><input type="text" name="cgs_calc_default_principal" value="<?php echo esc_attr( cgs_get_option( 'cgs_calc_default_principal', '100000000' ) ); ?>" class="regular-text" dir="ltr"></td></tr>
<tr><th>نرخ سود پیش‌فرض (٪)</th><td><input type="number" step="0.1" name="cgs_calc_default_rate" value="<?php echo esc_attr( cgs_get_option( 'cgs_calc_default_rate', '4.8' ) ); ?>" class="small-text"></td></tr>
<tr><th>مدت پیش‌فرض (ماه)</th><td><input type="number" name="cgs_calc_default_months" value="<?php echo esc_attr( cgs_get_option( 'cgs_calc_default_months', '12' ) ); ?>" class="small-text"></td></tr>
<tr><th>فاصله اقساط پیش‌فرض</th><td>
<select name="cgs_calc_default_step">
<?php $st = cgs_get_option( 'cgs_calc_default_step', '1' ); ?>
<option value="1" <?php selected( $st, '1' ); ?>>ماهانه</option>
<option value="2" <?php selected( $st, '2' ); ?>>دوماهه</option>
<option value="3" <?php selected( $st, '3' ); ?>>سه‌ماهه</option>
<option value="6" <?php selected( $st, '6' ); ?>>شش‌ماهه</option>
</select>
</td></tr>
<tr><th>روش محاسبه پیش‌فرض</th><td>
<select name="cgs_calc_default_method">
<?php $mt = cgs_get_option( 'cgs_calc_default_method', 'flat' ); ?>
<option value="flat" <?php selected( $mt, 'flat' ); ?>>سود ثابت روی اصل</option>
<option value="reducing" <?php selected( $mt, 'reducing' ); ?>>کاهش‌یابنده</option>
</select>
</td></tr>
</table>
</div><!-- /calculator -->

<div id="cgs-tab-settlement" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.settlement' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات تسویه</button>
</p>
<h2>الگوریتم‌های تسویه</h2>
<?php $ss = class_exists( 'CGS_Settlement' ) ? CGS_Settlement::get_settings() : array(); ?>
<table class="form-table">
<tr><th>بخشودگی سود در تسویه زودهنگام (٪)</th><td><input type="number" step="1" min="0" max="100" name="settlement_early_discount_percent" value="<?php echo esc_attr( $ss['early_discount_percent'] ?? 50 ); ?>" class="small-text"></td></tr>
<tr><th>جریمه دیرکرد روزانه (٪ از قسط)</th><td><input type="number" step="0.01" min="0" name="settlement_late_penalty_daily" value="<?php echo esc_attr( $ss['late_penalty_daily'] ?? 0.1 ); ?>" class="small-text"></td></tr>
<tr><th>مهلت بخشودگی دیرکرد (روز)</th><td><input type="number" min="0" name="settlement_grace_days" value="<?php echo esc_attr( $ss['grace_days'] ?? 3 ); ?>" class="small-text"></td></tr>
<tr><th>حداقل تسویه جزئی (٪ از مانده)</th><td><input type="number" min="0" max="100" name="settlement_min_partial_percent" value="<?php echo esc_attr( $ss['min_partial_percent'] ?? 10 ); ?>" class="small-text"></td></tr>
<tr><th>گرد کردن مبلغ</th><td>
<select name="settlement_rounding">
<?php $rg = $ss['rounding'] ?? 'round'; ?>
<option value="round" <?php selected( $rg, 'round' ); ?>>گرد معمولی</option>
<option value="floor" <?php selected( $rg, 'floor' ); ?>>به پایین</option>
<option value="ceil" <?php selected( $rg, 'ceil' ); ?>>به بالا</option>
</select>
</td></tr>
</table>
</div><!-- /settlement -->

<div id="cgs-tab-risk" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.risk' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات ریسک</button>
</p>
<h2>مدیریت ریسک اعتباری هوشمند</h2>
<?php $rs = class_exists( 'CGS_Credit_Risk' ) ? CGS_Credit_Risk::get_settings() : array(); ?>
<table class="form-table">
<tr><th>وزن رتبه اعتباری</th><td><input type="number" name="risk_weight_credit_rank" value="<?php echo esc_attr( $rs['weight_credit_rank'] ?? 35 ); ?>" class="small-text"></td></tr>
<tr><th>وزن بدهی معوق</th><td><input type="number" name="risk_weight_debt" value="<?php echo esc_attr( $rs['weight_debt'] ?? 25 ); ?>" class="small-text"></td></tr>
<tr><th>وزن سن</th><td><input type="number" name="risk_weight_age" value="<?php echo esc_attr( $rs['weight_age'] ?? 10 ); ?>" class="small-text"></td></tr>
<tr><th>وزن نسبت قسط به درآمد</th><td><input type="number" name="risk_weight_income_ratio" value="<?php echo esc_attr( $rs['weight_income_ratio'] ?? 20 ); ?>" class="small-text"></td></tr>
<tr><th>وزن سابقه داخلی</th><td><input type="number" name="risk_weight_history" value="<?php echo esc_attr( $rs['weight_history'] ?? 10 ); ?>" class="small-text"></td></tr>
<tr><th>رد زیر امتیاز</th><td><input type="number" name="risk_reject_below" value="<?php echo esc_attr( $rs['reject_below'] ?? 40 ); ?>" class="small-text"></td></tr>
<tr><th>بررسی دستی زیر امتیاز</th><td><input type="number" name="risk_manual_below" value="<?php echo esc_attr( $rs['manual_below'] ?? 60 ); ?>" class="small-text"></td></tr>
<tr><th>حداکثر قسط/درآمد (٪)</th><td><input type="number" name="risk_max_installment_ratio" value="<?php echo esc_attr( $rs['max_installment_ratio'] ?? 40 ); ?>" class="small-text"></td></tr>
<tr><th>حداقل سن</th><td><input type="number" name="risk_min_age" value="<?php echo esc_attr( $rs['min_age'] ?? 18 ); ?>" class="small-text"></td></tr>
<tr><th>حداکثر سن</th><td><input type="number" name="risk_max_age" value="<?php echo esc_attr( $rs['max_age'] ?? 70 ); ?>" class="small-text"></td></tr>
<tr><th>رد خودکار در صورت بدهی معوق</th><td><label><input type="checkbox" name="risk_auto_reject" value="1" <?php checked( ! empty( $rs['auto_reject'] ) ); ?>> فعال</label></td></tr>
</table>
</div><!-- /risk -->


<div id="cgs-tab-system" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.system' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help"><summary>راهنما: سیستم و دیتابیس</summary><div class="cgs-help-body">
<p>بهینه‌سازی جداول افزونه سرعت پیشخوان و فرم‌ها را بهتر می‌کند.</p>
<ol>
<li>دکمه «اجرای بهینه‌سازی جداول» را بزنید.</li>
<li>پیام موفقیت را ببینید.</li>
<li>در صورت کندی مداوم، با هاست درباره Redis صحبت کنید.</li>
</ol>
</div></details>

        <h2>بهینه‌سازی دیتابیس</h2>
        <p class="description">ایندکس‌های ترکیبی، کش فیلدها و OPTIMIZE TABLE برای سرعت بالاتر.</p>
        <p>
            <button type="button" class="button" id="cgs-optimize-db">اجرای بهینه‌سازی جداول</button>
            <span id="cgs-optimize-msg"></span>
        </p>

        <div class="cgs-plan-sec-box" style="margin-top:20px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 2px 12px rgba(15,23,42,.05);">
          <h2 style="margin-top:0;font-size:1.05rem;color:#1a237e;">مدیریت داده‌های پویای ماتریس (جداول فرم)</h2>
          <details class="cgs-help" open><summary>راهنما</summary><div class="cgs-help-body">
          <p>فیلدهای نوع «ماتریس داده» ساختار ستون/ردیف را در دیتابیس فیلدهای فرم نگه می‌دارند. از اینجا می‌توانید خروجی بگیرید یا کش را پاک کنید.</p>
          </div></details>
          <p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <a class="cgs-btn-admin cgs-btn-admin-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=cgs-data-manager' ) ); ?>">📦 رفتن به مدیریت داده (CSV)</a>
            <button type="button" class="button" id="cgs-flush-table-cache">پاک‌سازی کش فیلدها و ماتریس‌ها</button>
            <span id="cgs-flush-msg"></span>
          </p>
          <p class="description">خروجی CSV فیلدها شامل تنظیمات ماتریس (ستون، ردیف، فرمول) نیز می‌شود.</p>
        </div>
        <script>
        jQuery(function($){
          $('#cgs-flush-table-cache').on('click', function(){
            var $b=$(this).prop('disabled',true);
            $('#cgs-flush-msg').text('...');
            $.post(ajaxurl,{action:'cgs_flush_field_cache',nonce:'<?php echo wp_create_nonce("cgs_admin_nonce"); ?>'})
              .done(function(r){ $('#cgs-flush-msg').text(r.success?'کش پاک شد ✓':(r.data||'خطا')).css('color',r.success?'green':'red'); })
              .always(function(){ $b.prop('disabled',false); });
          });
        });
        </script>
        <script>
        jQuery(function($){
            $('#cgs-optimize-db').on('click', function(){
                var $btn = $(this).prop('disabled', true);
                $('#cgs-optimize-msg').text('در حال اجرا...');
                $.post(ajaxurl, { action: 'cgs_optimize_db', nonce: '<?php echo wp_create_nonce("cgs_admin_nonce"); ?>' })
                    .done(function(res){
                        $('#cgs-optimize-msg').text(res.success ? 'انجام شد ✓' : (res.data||'خطا')).css('color', res.success?'green':'red');
                    })
                    .always(function(){ $btn.prop('disabled', false); });
            });
        });
        </script>

        
        </div><!-- /system -->
<div id="cgs-tab-charts" class="cgs-settings-panel">
<?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'settings.charts' ); } ?>
<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>
<details class="cgs-help"><summary>راهنما: نمودارها</summary><div class="cgs-help-body">
<p>نوع و ظاهر نمودارهای داشبورد و CRM را اینجا تنظیم کنید. این بخش از دیتابیس جداست.</p>
<ol><li>نوع هر نمودار را انتخاب کنید.</li><li>ذخیره را بزنید.</li><li>داشبورد را باز کنید و نتیجه را ببینید.</li></ol>
</div></details>
<h2>قالب‌بندی پیشرفته نمودارها</h2>
<p class="description" style="color:#b45309;">برای اعمال تغییرات نمودار، حتماً از <strong>همین تب</strong> دکمه ذخیره را بزنید (ذخیره تب‌های دیگر تنظیمات نمودار را عوض نمی‌کند).</p>

        <p class="description">نوع نمودار، انیمیشن، راهنما، ضخامت حاشیه، فونت و عناوین را تنظیم کنید. پس از ذخیره در داشبورد و CRM اعمال می‌شود.</p>
        <?php $cf = cgs_get_chart_format(); ?>
        <table class="form-table">
            <tr>
                <th>نوع نمودار وضعیت</th>
                <td>
                    <select name="chart_format[status_type]">
                        <?php foreach ( array('doughnut'=>'حلقه‌ای (Doughnut)','pie'=>'دایره‌ای (Pie)','bar'=>'میله‌ای','polarArea'=>'قطبی') as $k=>$l ) : ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($cf['status_type'],$k); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>نوع نمودار انواع درخواست</th>
                <td>
                    <select name="chart_format[types_type]">
                        <?php foreach ( array('bar'=>'میله‌ای عمودی','horizontalBar'=>'میله‌ای افقی','doughnut'=>'حلقه‌ای','pie'=>'دایره‌ای') as $k=>$l ) : ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($cf['types_type'],$k); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>نوع نمودار روند</th>
                <td>
                    <select name="chart_format[trend_type]">
                        <option value="line" <?php selected($cf['trend_type'],'line'); ?>>خطی</option>
                        <option value="bar" <?php selected($cf['trend_type'],'bar'); ?>>میله‌ای</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>موقعیت راهنما (Legend)</th>
                <td>
                    <select name="chart_format[legend_position]">
                        <?php foreach ( array('bottom'=>'پایین','top'=>'بالا','left'=>'چپ','right'=>'راست') as $k=>$l ) : ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($cf['legend_position'],$k); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                    &nbsp; <label><input type="checkbox" name="chart_format[show_legend]" value="1" <?php checked($cf['show_legend'],'1'); ?>> نمایش راهنما</label>
                </td>
            </tr>
            <tr>
                <th>انیمیشن</th>
                <td>
                    <label><input type="checkbox" name="chart_format[animation]" value="1" <?php checked($cf['animation'],'1'); ?>> فعال</label>
                    &nbsp; مدت (ms): <input type="number" name="chart_format[anim_duration]" value="<?php echo esc_attr($cf['anim_duration']); ?>" style="width:80px;" min="0" max="3000">
                </td>
            </tr>
            <tr>
                <th>حفره حلقه (Cutout %)</th>
                <td><input type="number" name="chart_format[cutout]" value="<?php echo esc_attr($cf['cutout']); ?>" min="0" max="90" style="width:80px;"> — فقط برای Doughnut</td>
            </tr>
            <tr>
                <th>حاشیه قطعات</th>
                <td>
                    ضخامت: <input type="number" name="chart_format[border_width]" value="<?php echo esc_attr($cf['border_width']); ?>" min="0" max="10" style="width:60px;">
                    رنگ: <input type="color" name="chart_format[border_color]" value="<?php echo esc_attr($cf['border_color']); ?>">
                </td>
            </tr>
            <tr>
                <th>میله / خط</th>
                <td>
                    گردی میله: <input type="number" name="chart_format[bar_radius]" value="<?php echo esc_attr($cf['bar_radius']); ?>" min="0" max="20" style="width:60px;">
                    انحنای خط: <input type="number" name="chart_format[line_tension]" value="<?php echo esc_attr($cf['line_tension']); ?>" min="0" max="1" step="0.05" style="width:70px;">
                    &nbsp; <label><input type="checkbox" name="chart_format[line_fill]" value="1" <?php checked($cf['line_fill'],'1'); ?>> پر کردن زیر خط</label>
                    &nbsp; شعاع نقطه: <input type="number" name="chart_format[point_radius]" value="<?php echo esc_attr($cf['point_radius']); ?>" min="0" max="12" style="width:60px;">
                </td>
            </tr>
            <tr>
                <th>شبکه و عنوان</th>
                <td>
                    <label><input type="checkbox" name="chart_format[show_grid]" value="1" <?php checked($cf['show_grid'],'1'); ?>> نمایش خطوط شبکه</label>
                    &nbsp; <label><input type="checkbox" name="chart_format[show_title]" value="1" <?php checked($cf['show_title'],'1'); ?>> نمایش عنوان روی نمودار</label>
                </td>
            </tr>
            <tr>
                <th>عناوین نمودارها</th>
                <td>
                    <input type="text" name="chart_format[title_status]" value="<?php echo esc_attr($cf['title_status']); ?>" placeholder="وضعیت" style="width:30%;">
                    <input type="text" name="chart_format[title_types]" value="<?php echo esc_attr($cf['title_types']); ?>" placeholder="انواع" style="width:30%;">
                    <input type="text" name="chart_format[title_trend]" value="<?php echo esc_attr($cf['title_trend']); ?>" placeholder="روند" style="width:30%;">
                    <br><input type="text" name="chart_format[title_crm]" value="<?php echo esc_attr($cf['title_crm']); ?>" placeholder="CRM" style="width:40%;margin-top:6px;">
                </td>
            </tr>
            <tr>
                <th>فونت و نسبت</th>
                <td>
                    اندازه فونت: <input type="number" name="chart_format[font_size]" value="<?php echo esc_attr($cf['font_size']); ?>" min="9" max="18" style="width:60px;">
                    خانواده: <input type="text" name="chart_format[font_family]" value="<?php echo esc_attr($cf['font_family']); ?>" style="width:220px;" dir="ltr">
                    نسبت ابعاد: <input type="number" name="chart_format[aspect_ratio]" value="<?php echo esc_attr($cf['aspect_ratio']); ?>" min="0.5" max="3" step="0.1" style="width:70px;">
                </td>
            </tr>
        </table>
        <p>
            
<?php
$charts_on = (int) cgs_get_option( 'charts_module_enabled', 1 );
$cadv = cgs_get_option( 'charts_advanced', array() );
if ( ! is_array( $cadv ) ) $cadv = array();
$cadv = wp_parse_args( $cadv, array( 'color_scheme'=>'default','export_png'=>'0','datalabels'=>'0','rtl_tooltips'=>'1','min_height'=>'220' ) );
?>
<table class="form-table" style="margin-top:16px;">
  <tr>
    <th>وضعیت ماژول</th>
    <td>
      <label><input type="checkbox" name="charts_module_enabled" value="1" <?php checked( $charts_on, 1 ); ?>> قالب‌بندی پیشرفته فعال باشد</label>
      <p class="description">اگر خاموش باشد، داشبورد از تنظیمات ساده پیش‌فرض استفاده می‌کند.</p>
    </td>
  </tr>
  <tr>
    <th>طرح رنگ</th>
    <td>
      <select name="charts_advanced[color_scheme]">
        <option value="default" <?php selected( $cadv['color_scheme'], 'default' ); ?>>پیش‌فرض (رنگ‌های وضعیت/نوع)</option>
        <option value="vivid" <?php selected( $cadv['color_scheme'], 'vivid' ); ?>>پررنگ</option>
        <option value="pastel" <?php selected( $cadv['color_scheme'], 'pastel' ); ?>>پاستلی</option>
        <option value="mono" <?php selected( $cadv['color_scheme'], 'mono' ); ?>>تک‌رنگ آبی</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>ارتفاع حداقل نمودار</th>
    <td><input type="number" name="charts_advanced[min_height]" value="<?php echo esc_attr( $cadv['min_height'] ); ?>" min="120" max="480" style="width:80px;"> px</td>
  </tr>
  <tr>
    <th>گزینه‌های کمکی</th>
    <td>
      <label><input type="checkbox" name="charts_advanced[rtl_tooltips]" value="1" <?php checked( $cadv['rtl_tooltips'], '1' ); ?>> راهنمای RTL</label>
      &nbsp;
      <label><input type="checkbox" name="charts_advanced[datalabels]" value="1" <?php checked( $cadv['datalabels'], '1' ); ?>> نمایش عدد روی نمودار (آزمایشی)</label>
    </td>
  </tr>
</table>

<input type="hidden" name="cgs_active_tab" value="charts" id="cgs_force_charts_tab">
<button type="submit" class="button button-primary" name="cgs_save_settings" value="1" onclick="var el=document.getElementById('cgs_active_tab'); if(el) el.value='charts';">ذخیره قالب‌بندی نمودار</button>
            <button type="submit" class="button" name="cgs_reset_chart_format" value="1" onclick="return confirm('قالب‌بندی نمودار به پیش‌فرض برگردد؟');">ریست قالب‌بندی نمودار</button>
        </p>


</div><!-- /charts -->


<p class="cgs-tab-save sticky-save">
  <button type="submit" name="cgs_save_settings" value="1" class="cgs-btn-admin cgs-btn-admin-success cgs-btn-lg">💾 ذخیره تنظیمات این بخش</button>
</p>


<script>
jQuery(function($){
  $(document).on('click', '.cgs-media-upload', function(e){
    e.preventDefault();
    var target = $(this).data('target');
    var title = $(this).data('title') || 'انتخاب تصویر';
    var frame = wp.media({ title: title, button: { text: 'استفاده از این تصویر' }, multiple: false, library: { type: 'image' } });
    frame.on('select', function(){
      var url = frame.state().get('selection').first().toJSON().url;
      $('#'+target).val(url).trigger('change');
      $('#'+target).closest('td').find('.cgs-logo-preview').html('<img src="'+url+'" style="max-height:64px;max-width:200px;" alt="">');
    });
    frame.open();
  });
});
</script>
</form>
