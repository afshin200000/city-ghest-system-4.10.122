<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$company = cgs_get_option( 'company_name', 'شهر قسط' );
$login_id = (int) get_option( 'cgs_page_login_id', 0 );
$dash_id  = (int) get_option( 'cgs_page_dashboard_id', 0 );
$form_id  = (int) get_option( 'cgs_page_forms_id', 0 );
?>
<div class="wrap cgs-setup-wrap">
  <div class="cgs-setup-hero">
    <div class="cgs-setup-badge">نصب موفق</div>
    <h1>به سامانه <?php echo esc_html( $company ); ?> خوش آمدید</h1>
    <p>زیرساخت فرم‌ساز، اعضا، نقشه ایران و قالب‌ها آماده است. این چند گام کوتاه را یک‌بار انجام دهید.</p>
  </div>
  <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'setup', true ); } ?>

  <div class="cgs-setup-grid">
    <div class="cgs-setup-card is-done">
      <div class="cgs-setup-num">۱</div>
      <h3>جداول و تنظیمات</h3>
      <p>جداول اختصاصی، نقش کاربری و تنظیمات پایه ایجاد شدند.</p>
      <span class="cgs-setup-status">انجام شد</span>
    </div>
    <div class="cgs-setup-card <?php echo $login_id && $dash_id ? 'is-done' : ''; ?>">
      <div class="cgs-setup-num">۲</div>
      <h3>صفحات سیستم</h3>
      <p>
        <?php if ( $login_id ) : ?>ورود: <a href="<?php echo esc_url( get_permalink( $login_id ) ); ?>" target="_blank">مشاهده</a><br><?php endif; ?>
        <?php if ( $dash_id ) : ?>داشبورد: <a href="<?php echo esc_url( get_permalink( $dash_id ) ); ?>" target="_blank">مشاهده</a><br><?php endif; ?>
        <?php if ( $form_id ) : ?>فرم: <a href="<?php echo esc_url( get_permalink( $form_id ) ); ?>" target="_blank">مشاهده</a><?php endif; ?>
      </p>
      <span class="cgs-setup-status"><?php echo $login_id ? 'آماده' : 'با فعال‌سازی ساخته می‌شود'; ?></span>
    </div>
    <div class="cgs-setup-card">
      <div class="cgs-setup-num">۳</div>
      <h3>فرم‌ساز و قالب‌ها</h3>
      <p>۱۰۰ قالب آماده در دیتابیس. نوع مخاطب را انتخاب و قالب را اعمال کنید.</p>
      <a class="cgs-setup-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=cgs-form-builder' ) ); ?>">باز کردن فرم‌ساز</a>
    </div>
    <div class="cgs-setup-card">
      <div class="cgs-setup-num">۴</div>
      <h3>تنظیمات و درگاه</h3>
      <p>پیامک، درگاه پرداخت، لوگو و استعلام را از تنظیمات پیکربندی کنید.</p>
      <a class="cgs-setup-btn cgs-setup-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=cgs-settings' ) ); ?>">تنظیمات</a>
    </div>
  </div>

  <div class="cgs-setup-footer">
    <form method="post">
      <?php wp_nonce_field( 'cgs_dismiss_wizard', 'cgs_wizard_nonce' ); ?>
      <button type="submit" name="cgs_dismiss_wizard" value="1" class="cgs-setup-btn">شروع کار — بستن راهنما</button>
    </form>
    <p class="description">هر زمان از منوی «شهر قسط» به فرم‌ساز و تنظیمات دسترسی دارید.</p>
  </div>
</div>
<style>
.cgs-setup-wrap{max-width:980px;margin:20px auto;font-family:Vazirmatn,Tahoma,sans-serif}
.cgs-setup-hero{background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border-radius:20px;padding:28px 32px;box-shadow:0 18px 50px rgba(26,35,126,.28);margin-bottom:22px}
.cgs-setup-badge{display:inline-block;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;margin-bottom:10px}
.cgs-setup-hero h1{margin:0 0 8px;font-size:1.6rem;font-weight:800;color:#fff}
.cgs-setup-hero p{margin:0;opacity:.9;line-height:1.7}
.cgs-setup-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
@media(max-width:782px){.cgs-setup-grid{grid-template-columns:1fr}}
.cgs-setup-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 8px 28px rgba(15,23,42,.05);position:relative}
.cgs-setup-card.is-done{border-color:#bbf7d0;background:linear-gradient(180deg,#f0fdf4,#fff)}
.cgs-setup-num{width:32px;height:32px;border-radius:10px;background:#e8eaf6;color:#1a237e;display:flex;align-items:center;justify-content:center;font-weight:800;margin-bottom:12px}
.cgs-setup-card h3{margin:0 0 8px;font-size:1.05rem;color:#0f172a}
.cgs-setup-card p{margin:0 0 14px;color:#64748b;font-size:13px;line-height:1.7}
.cgs-setup-status{font-size:12px;font-weight:700;color:#15803d}
.cgs-setup-btn{display:inline-flex;align-items:center;padding:10px 16px;border-radius:12px;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff!important;text-decoration:none;font-weight:700;font-size:13px;border:none;cursor:pointer;box-shadow:0 8px 20px rgba(26,35,126,.25)}
.cgs-setup-btn-ghost{background:#fff;color:#1a237e!important;border:1px solid #c5cae9;box-shadow:none}
.cgs-setup-footer{margin-top:22px;padding:18px;background:#fff;border-radius:16px;border:1px solid #e2e8f0;text-align:center}
</style>
