<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="cgs-menu-app" class="cgs-menu-app" dir="rtl">
  <div id="cgs-ma-diag" class="cgs-ma-diag">وضعیت موتور منوساز: در حال بارگذاری…</div>

  <header class="cgs-ma-topbar">
    <div class="cgs-ma-brand">
      <span class="cgs-ma-logo">☰</span>
      <div>
        <strong>منوساز حرفه‌ای شهر قسط</strong>
        <span class="cgs-ma-ver">v<?php echo esc_html( defined( 'CGS_VERSION' ) ? CGS_VERSION : '4.10.122' ); ?></span>
      </div>
    </div>
    <div class="cgs-ma-top-actions">
      <label class="cgs-ma-menu-pick">منوی فعال
        <select id="cgs-ma-menu-select" class="cgs-ma-menu-select"><option value="">— بارگذاری —</option></select>
      </label>
      <code id="cgs-ma-shortcode" class="cgs-ma-sc-inline">[cgs_menu id="main"]</code>
      <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-primary" id="cgs-ma-save">💾 ذخیره</button>
      <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-teal" id="cgs-ma-new">＋ جدید</button>
      <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-amber" id="cgs-ma-seo">🔍 سئو</button>
      <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-violet" id="cgs-ma-fx-demo">✨ افکت</button>
      <button type="button" class="cgs-ma-btn cgs-3d" id="cgs-ma-copy-sc">📋 شورت‌کد</button>
      <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-ghost" id="cgs-ma-back-wp">بازگشت</button>
    </div>
  </header>

  <div style="display:flex;justify-content:flex-end;margin:6px 0">
    <button type="button" id="cgs-ma-diag-btn" class="button" style="font-weight:700">🩺 خودآزمایی زنجیره منوساز</button>
  </div>
  <nav class="cgs-ma-tabs" id="cgs-ma-tabs">
    <button type="button" class="cgs-ma-tab is-active" data-tab="settings">🎛️ تنظیمات عمومی منوها</button>
    <button type="button" class="cgs-ma-tab" data-tab="editor">🧩 آیتم‌ها و ساختار</button>
    <button type="button" class="cgs-ma-tab" data-tab="live">🖥️ پیش‌نمایش زنده</button>
    <button type="button" class="cgs-ma-tab" data-tab="help">📚 راهنمای جامع</button>
    <!-- v4.10.122: dynamic woo/terms/widget -->
  </nav>

  <div class="cgs-ma-toolsbar" id="cgs-ma-toolsbar">
    <label>قالب ذخیره‌شده
      <select id="cgs-ma-tpl-select"><option value="">— انتخاب —</option></select>
    </label>
    <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-teal" id="cgs-ma-load-tpl">بارگذاری</button>
    <button type="button" class="cgs-ma-btn sm cgs-3d" id="cgs-ma-save-tpl">ذخیره قالب</button>
    <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-danger" id="cgs-ma-del-tpl">حذف</button>
    <span class="cgs-ma-tools-sep"></span>
    <div class="cgs-ma-tpl-bar" id="cgs-ma-mega-templates">
      <span class="cgs-ma-bar cgs-ma-bar--navy" style="display:inline-block;padding:4px 10px;border-radius:8px;font-size:11px">قالب‌های آماده مگا</span>
      <select id="cgs-ma-ready-tpl" style="max-width:220px">
        <option value="">— انتخاب قالب —</option>
        <option value="digikala">دیجیکالا (سایدبار+ستون)</option>
        <option value="hero_content">Hero محتوایی</option>
        <option value="shop_products">فروشگاهی + اسلایدر محصول</option>
        <option value="brands_grid">گرید برند + لوگو</option>
        <option value="mega_2x2">فیکسچر مگا ۲×۲ (پذیرش)</option>
        <option value="news_magazine">خبری / مجله</option>
        <option value="finance_city">شهر قسط — مالی اقساطی</option>
        <option value="hub_cards">هاب کارت‌ها</option>
        <option value="tabs_panel">تب‌دار (Uber-style tabs)</option>
        <option value="wp_mega_classic">WP Mega کلاسیک (۴ ستون + پست + بنر)</option>
        <option value="woo_shop_mega">فروشگاهی ووکامرس (محصول + نظر + دسته)</option>
        <option value="adidas_mega">آدیداس (Adidas) — ستون تصویری برند</option>
        <option value="hubspot_platform">هاب‌اسپات (HubSpot) — پلتفرم کارت‌محور</option>
        <option value="fashion_dept_mega">مد و دپارتمان (سایدبار + ۴ ستون)</option>
        <option value="product_boards_mega">ویترین محصول (کارت + CTA)</option>
        <option value="mega_shop_4">Preset: فروشگاهی ۴ستونه</option>
        <option value="mega_finance_3">Preset: مالی ۳ستونه</option>
        <option value="mega_cityghest_4">Preset: شهر قسط ۴ستونه</option>
        <option value="mega_corp_5">Preset: سازمانی ۵ستونه</option>
      </select>
      <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-teal" id="cgs-ma-ready-tpl-apply">اعمال قالب آماده</button>
    </div>
  </div>

  <div class="cgs-ma-body cgs-ma-body--editor" id="cgs-ma-body">
    <main class="cgs-ma-main" id="cgs-ma-panel-editor">
      <div class="cgs-ma-editor-top">
        <div id="cgs-ma-revisions" class="cgs-ma-revisions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:8px 0;padding:8px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
          <strong style="font-size:12px">تاریخچه نسخه‌ها:</strong>
          <button type="button" class="button button-small" id="cgs-ma-rev-refresh">بارگذاری</button>
          <select id="cgs-ma-rev-list" style="max-width:280px;min-height:28px;font-size:12px"><option value="">— پس از ذخیره —</option></select>
          <button type="button" class="button button-small" id="cgs-ma-rev-restore">بازگردانی</button>
          <span id="cgs-ma-rev-msg" style="font-size:11px;color:#64748b"></span>
          <button type="button" class="button button-small" id="cgs-ma-integrity" title="بررسی سلامت ماژول">سلامت ماژول</button>
          <span id="cgs-ma-integrity-msg" style="font-size:11px;color:#64748b"></span>
        </div><div id="cgs-ma-seo-box" class="cgs-ma-seo-inline" hidden><strong>سئو:</strong> <span id="cgs-ma-seo-result"></span></div></div>
<details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--emerald">⑥ آیتم‌ها و زیرمنوها</span></summary>
        <div class="cgs-ma-pad">
          <div class="cgs-ma-items-head">
            <button type="button" class="cgs-ma-btn cgs-3d cgs-3d-primary" id="cgs-ma-add-item">＋ آیتم جدید</button>
          </div>
          <ul id="cgs-ma-items" class="cgs-ma-items"></ul>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--amber">⑦ بانک ایموجی (ساده و ۳D)</span></summary>
        <div class="cgs-ma-pad">
          <p class="cgs-hint">بانک ایموجی ساده و سبک ۳D. روی هر مورد کلیک کنید تا در فیلد «آیکن» آیتم بازشده قرار گیرد. اگر آیتمی باز نیست، در کلیپبورد کپی می‌شود.</p>
          <div id="cgs-ma-emoji-grid" class="cgs-ma-emoji-grid"></div>
          <div class="cgs-ma-icon-providers">
            <a href="https://openmoji.org/" target="_blank" rel="noopener">OpenMoji</a> ·
            <a href="https://unicode.org/emoji/charts/full-emoji-list.html" target="_blank" rel="noopener">Unicode Emoji</a>
          </div>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--slate">⑧ بانک آیکن</span></summary>
        <div class="cgs-ma-pad">
          <div id="cgs-ma-icon-tabs" class="cgs-ma-icon-tabs">
            <button type="button" class="cgs-ma-tab is-active" data-type="all">همه</button>
            <button type="button" class="cgs-ma-tab" data-type="static">ثابت</button>
            <button type="button" class="cgs-ma-tab" data-type="animated">انیمیشنی</button>
            <button type="button" class="cgs-ma-tab" data-type="font">فونت‌آیکن</button>
            <button type="button" class="cgs-ma-tab" data-type="graphic">گرافیکی</button>
          </div>
          <div id="cgs-ma-icon-picked" class="cgs-hint"></div>
          <div id="cgs-ma-icon-grid" class="cgs-ma-icon-grid"></div>
          <div id="cgs-ma-icon-providers" class="cgs-ma-icon-providers"></div>
        </div>
      </details>
    
    </main>

    <section class="cgs-ma-main cgs-ma-settings-panel" id="cgs-ma-panel-settings" hidden>
    <!-- cgs-maxmega-fields -->
    <div class="cgs-ma-card" style="margin-bottom:12px">
      <div class="cgs-ma-card-h" style="background:linear-gradient(90deg,#0f172a,#1e3a5f);color:#fff;padding:8px 12px;border-radius:8px 8px 0 0">⚙️ رفتار موبایل و چسبان (الهام Max Mega)</div>
      <div style="padding:12px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
        <label class="cgs-f">تأخیر Hover Intent (ms)<br><input type="number" id="m-intent-ms" min="0" max="800" value="200" style="max-width:100px"></label>
        <label class="cgs-f">Breakpoint موبایل (px)<br><input type="number" id="m-breakpoint" min="480" max="1200" value="768" style="max-width:100px"></label>
        <label class="cgs-f">ضربه دوم موبایل<br><select id="m-second-tap" style="max-width:140px"><option value="open">باز/بسته</option><option value="follow">دنبال لینک</option></select></label>
        <label class="cgs-f"><input type="checkbox" id="m-sticky"> منوی چسبان (Sticky)</label>
        <label class="cgs-f"><input type="checkbox" id="m-sticky-hide"> مخفی هنگام اسکرول پایین</label>
      </div>
    </div>

      <div class="cgs-ma-settings-note">تنظیمات عمومی منوی فعال — پس از تغییر، از نوار بالا «ذخیره» را بزنید و در تب پیش‌نمایش نتیجه را ببینید.</div>
      <details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--navy">① شناسه، محل و چیدمان</span></summary>
        <div class="cgs-ma-grid cgs-ma-pad">
          <label>شناسه منو <input type="text" id="m-id" placeholder="main"></label>
          <label>عنوان <input type="text" id="m-title" placeholder="منوی اصلی"></label>
          <label>نامک / اسلاگ <input type="text" id="m-slug" placeholder="main"></label>
          <label>محل قرارگیری
            <select id="m-placement">
              <option value="header">هدر (Header)</option>
              <option value="footer">فوتر (Footer)</option>
              <option value="sidebar">سایدبار</option>
            </select>
          </label>
          <label>چیدمان (Layout) <select id="m-layout"></select></label>
          <p class="cgs-hint cgs-ma-span2">برای مگامنوی فروشگاهی/دیجیکالا: چیدمان «سایدبار + محتوا» یا «محتوایی» را انتخاب کنید. در هر آیتم فرزند، «نوع محتوا در مگا» را روی عنوان ستون، تصویر یا کارت بگذارید.</p>
          <label>تعداد ستون مگا <input type="number" id="m-mega-cols" min="1" max="8" value="3"></label>
          <label class="cgs-ma-span2">لوگوی منو
            <div class="cgs-ma-control-row">
              <input type="url" id="m-logo-url" class="cgs-ma-grow" placeholder="آدرس تصویر لوگو">
              <button type="button" class="cgs-ma-btn sm cgs-3d cgs-browse" data-target="#m-logo-url" data-kind="image">📂 انتخاب</button>
            </div>
          </label>
          <label>لوگو — هدف
            <select id="m-logo-target">
              <option value="bar">نوار اصلی منو</option>
              <option value="submenu">داخل زیرمنو</option>
              <option value="column">ستون مگا</option>
            </select>
          </label>
          <label>لوگو — شماره ستون مگا <input type="number" id="m-logo-col" min="1" max="8" value="1"></label>
          <label>لوگو — X (px از راست) <input type="number" id="m-logo-x" min="0" max="2000" value="8"></label>
          <label>لوگو — Y (px از بالا) <input type="number" id="m-logo-y" min="0" max="800" value="4"></label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-hamburger" checked> منوی همبرگری موبایل (فعال)</label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-search-in-bar" class="cgs-search-enable"> فیلد جستجو در نوار منوی اصلی</label>
          <label>متن placeholder جستجو <input type="text" id="m-search-placeholder" value="جستجو…"></label>
          <label>محل جستجو
            <select id="m-search-place">
              <option value="bar-end">انتهای نوار</option>
              <option value="bar-start">ابتدای نوار</option>
              <option value="after-logo">بعد از لوگو</option>
              <option value="coords">مختصات دستی (px)</option>
            </select>
          </label>
          <label>جستجو — فاصله از راست (px) <input type="number" id="m-search-x" min="0" max="2000" value="12"></label>
          <label>جستجو — فاصله از بالا (px) <input type="number" id="m-search-y" min="0" max="800" value="8"></label>
        </div>
      </details>

      <details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--teal">② رنگ‌ها و پس‌زمینه</span></summary>
        <div class="cgs-ma-grid cgs-ma-pad">
          <label>نوع پس‌زمینه
            <select id="m-bg-type">
              <option value="solid">ساده</option>
              <option value="gradient" selected>گرادیان</option>
              <option value="image">تصویر</option>
              <option value="glass">شیشه‌ای</option>
            </select>
          </label>
          <label>جهت گرادیان
            <select id="m-gradient-dir">
              <option value="ltr">چپ ← راست</option>
              <option value="rtl">راست ← چپ</option>
              <option value="ttb">بالا ↓ پایین</option>
              <option value="btt">پایین ↑ بالا</option>
              <option value="radial">دایره‌ای</option>
            </select>
          </label>
          <label class="cgs-ma-color-lab">رنگ پس‌زمینه
            <span class="cgs-ma-color-pair"><input type="color" id="m-bg" value="#0f172a"><input type="text" id="m-bg-hex" class="cgs-ma-hex" value="#0f172a" maxlength="7"></span>
          </label>
          <label class="cgs-ma-color-lab">رنگ دوم گرادیان
            <span class="cgs-ma-color-pair"><input type="color" id="m-bg2" value="#1e3a8a"><input type="text" id="m-bg2-hex" class="cgs-ma-hex" value="#1e3a8a" maxlength="7"></span>
          </label>
          <label class="cgs-ma-color-lab">رنگ متن
            <span class="cgs-ma-color-pair"><input type="color" id="m-text" value="#f8fafc"><input type="text" id="m-text-hex" class="cgs-ma-hex" value="#f8fafc" maxlength="7"></span>
          </label>
          <label class="cgs-ma-color-lab">رنگ هاور
            <span class="cgs-ma-color-pair"><input type="color" id="m-hover" value="#38bdf8"><input type="text" id="m-hover-hex" class="cgs-ma-hex" value="#38bdf8" maxlength="7"></span>
          </label>
          <label class="cgs-ma-color-lab">رنگ فعال
            <span class="cgs-ma-color-pair"><input type="color" id="m-active" value="#6366f1"><input type="text" id="m-active-hex" class="cgs-ma-hex" value="#6366f1" maxlength="7"></span>
          </label>
          <label>انحنای گوشه (px) <input type="number" id="m-radius" min="0" max="40" value="12"></label>
          <label class="cgs-ma-span2">تصویر پس‌زمینه
            <div class="cgs-ma-control-row">
              <input type="url" id="m-bg-img" class="cgs-ma-grow" placeholder="URL یا از مخزن">
              <button type="button" class="cgs-ma-btn sm cgs-3d" id="m-bg-browse" class="cgs-ma-btn sm cgs-3d cgs-browse" data-target="#m-bg-image" data-kind="image">📂 فایل</button>
            </div>
          </label>
          <label class="cgs-ma-span2">شفافیت تصویر
            <div class="cgs-ma-vol-row">
              <input type="range" id="m-bg-img-opacity" min="0" max="100" value="100">
              <span id="m-bg-img-opacity-val">100%</span>
            </div>
          </label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-shadow" checked> سایه</label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-sticky"> چسبان (Sticky)</label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-rtl" checked> راست‌چین (RTL)</label>
        </div>
      </details>

      <details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--violet">③ افکت، جهت، سرعت و صدا</span></summary>
        <div class="cgs-ma-grid cgs-ma-pad">
          <label>افکت زیرمنو <select id="m-effect"></select></label>
          <label>جهت باز شدن زیرمنو (نه گرادیان)
            <select id="m-sub-dir">
              <option value="bottom">از بالا به پایین (زیر آیتم)</option>
              <option value="top">از پایین به بالا (بالای آیتم)</option>
              <option value="left">از راست به چپ</option>
              <option value="right">از چپ به راست</option>
            </select>
          </label>
          <label>سرعت باز شدن (ms) <span id="m-effect-speed-val" class="cgs-vol-pct">220 ms</span>
<input type="number" id="m-effect-speed" min="50" max="1200" step="10" value="220"></label>
          <label>صدا <select id="m-sound"></select></label>
          <label>بلندی صدا
            <div class="cgs-ma-vol-row">
              <input type="range" id="m-sound-vol" min="0" max="100" value="35">
              <span id="m-sound-vol-val">35</span>
              <button type="button" class="cgs-ma-btn sm cgs-3d" id="m-sound-preview">تست</button>
            </div>
          </label>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--cyan">④ انیمیشن صفحه، مگامنو، موبایل</span></summary>
        <div class="cgs-ma-grid cgs-ma-pad">
          <label>انیمیشن ورود <select id="m-page-anim-in"></select></label>
          <label>انیمیشن خروج <select id="m-page-anim-out"></select></label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-mobile-sync"> همگام اپ موبایل</label>
          <label>آدرس JSON سفارشی <input type="text" id="m-mobile-endpoint" placeholder="اختیاری"></label>
          <label>JSON اپ (خروجی)
            <code id="cgs-ma-json-url" class="cgs-ma-code-inline">?cgs_menu_json=main</code>
          </label>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--rose">⑤ رفتار منو و CTA</span></summary>
        <div class="cgs-ma-grid cgs-ma-pad">
          <label>تریگر
            <select id="m-trigger">
              <option value="hover">هاور</option>
              <option value="click">کلیک</option>
            </select>
          </label>
          <label>تأخیر Intent (ms) <input type="number" id="m-intent-ms" min="0" max="1000" value="280"></label>
          <label>Breakpoint (px) <input type="number" id="m-breakpoint" min="320" max="1200" value="768"></label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-search-box" class="cgs-search-enable"> فعال‌سازی فیلد جستجو (همگام با نوار اصلی)</label>
          <label class="cgs-hint cgs-ma-span2">هر دو تیک جستجو به هم وصل‌اند؛ روشن کردن یکی هر دو را فعال می‌کند.</label>
          <label>استایل سرشاخه‌ها
            <select id="m-root-style">
              <option value="link">لینک معمولی</option>
              <option value="glass">دکمه شیشه‌ای ۳D (کپسولی)</option>
              <option value="pill">کپسولی مات</option>
            </select>
          </label>
          <label class="cgs-ma-color-lab">رنگ شیشه سرشاخه
            <span class="cgs-ma-color-pair"><input type="color" id="m-root-glass-color" value="#e11d48"><input type="text" id="m-root-glass-hex" class="cgs-ma-hex" value="#e11d48" maxlength="7"></span>
          </label>
          <label>اندازه سرشاخه شیشه‌ای
            <select id="m-root-glass-size"><option value="sm">کوچک</option><option value="md" selected>متوسط</option><option value="lg">بزرگ</option></select>
          </label>
          <label>شعاع گوشه سرشاخه (px) <input type="number" id="m-root-glass-radius" min="0" max="40" value="22"></label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-fullwidth-sub"> زیرمنوی تمام‌عرض</label>
          <label class="cgs-ma-check"><input type="checkbox" id="m-sticky-hide"> مخفی با اسکرول پایین</label>
          <div class="cgs-ma-span-all cgs-cta-box">
            <div class="cgs-ma-bar cgs-ma-bar--rose" style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
              <span>دکمه و قالب‌های ظاهر (۳D / کپسولی / تخت / ساده)</span>
              <button type="button" id="cgs-cta-clear" class="cgs-cta-clear">🗑 حذف دکمه از منو</button>
            </div>
            <div id="cgs-cta-sample" class="cgs-cta-sample" aria-label="نمونه زنده دکمه"></div>
            <p class="cgs-hint">نمونه بالا با تغییر فونت، رنگ، گرادیان، نور، اندازه و تصویر هم‌زمان به‌روز می‌شود.</p>
            <p class="cgs-hint" style="margin:6px 0 10px">هر دسته قالب را از دراپ‌داون مربوط انتخاب کنید. نقش دکمه (لیبل منو، ارجاع، فقط آیکن) و شفافیت و رنگ را جدا تنظیم کنید. نتیجه در تب پیش‌نمایش دیده می‌شود.</p>

            <div class="cgs-ma-grid cgs-ma-pad" style="padding:0">
              <label>دکمه‌های ۳D (کره / حلقه / برجسته)
                <select id="m-btn-cat-3d">
                  <option value="">— انتخاب از این دسته —</option>
                  <option value="glass-sphere|#ff6f00">کره شیشه‌ای نارنجی</option>
                  <option value="glass-sphere|#29b6f6">کره شیشه‌ای آبی</option>
                  <option value="glass-sphere|#e53935">کره شیشه‌ای قرمز</option>
                  <option value="glass-sphere|#43a047">کره شیشه‌ای سبز</option>
                  <option value="ring|#7cb342">حلقه‌دار سبز</option>
                  <option value="ring|#1e88e5">حلقه‌دار آبی</option>
                  <option value="glass-sphere|#7b1fa2">کره بنفش</option>
                  <option value="bevel|#546e7a">برجسته فلزی</option>
                </select>
              </label>
              <label>دکمه‌های کپسولی شیشه‌ای
                <select id="m-btn-cat-capsule">
                  <option value="">— انتخاب از این دسته —</option>
                  <option value="glass-capsule|#00bcd4">کپسول فیروزه‌ای</option>
                  <option value="glass-capsule|#7b1fa2">کپسول بنفش</option>
                  <option value="glass-capsule|#e53935">کپسول قرمز</option>
                  <option value="glass-capsule|#43a047">کپسول سبز</option>
                  <option value="glass-capsule|#1e88e5">کپسول آبی</option>
                  <option value="glass-capsule|#fb8c00">کپسول نارنجی</option>
                  <option value="glass-capsule|#212121">کپسول مشکی</option>
                  <option value="glass-capsule|#9e9e9e">کپسول نقره‌ای</option>
                  <option value="glass-capsule|#c0ca33">کپسول لیمویی</option>
                  <option value="glass-capsule|#8e24aa">کپسول ارغوانی</option>
                </select>
              </label>
              <label>دکمه‌های تخت / Minimal
                <select id="m-btn-cat-flat">
                  <option value="">— انتخاب از این دسته —</option>
                  <option value="flat|#e11d48">تخت قرمز</option>
                  <option value="flat|#2563eb">تخت آبی</option>
                  <option value="flat|#059669">تخت سبز</option>
                  <option value="flat|#0f172a">تخت تیره</option>
                  <option value="flat|#f59e0b">تخت کهربایی</option>
                  <option value="outline|#e11d48">حاشیه‌دار قرمز</option>
                  <option value="outline|#2563eb">حاشیه‌دار آبی</option>
                </select>
              </label>
              <label>دکمه‌های ساده / مستطیل
                <select id="m-btn-cat-simple">
                  <option value="">— انتخاب از این دسته —</option>
                  <option value="glass-rect|#3949ab">مستطیل براق نیلی</option>
                  <option value="glass-rect|#00897b">مستطیل براق فیروزه</option>
                  <option value="soft|#6366f1">نرم گرد بنفش</option>
                  <option value="soft|#ec4899">نرم گرد صورتی</option>
                  <option value="chip|#0ea5e9">چیپ آبی</option>
                  <option value="chip|#22c55e">چیپ سبز</option>
                </select>
              </label>

              <label>نقش / کارکرد دکمه
                <select id="m-cta-role">
                  <option value="none">بدون دکمه (مخفی)</option>
                  <option value="cta_link">دکمه ارجاع (با لینک مقصد)</option>
                  <option value="menu_label">لیبل ظاهر منو / سرشاخه</option>
                  <option value="submenu_label">لیبل زیرمنو</option>
                  <option value="icon_only">فقط آیکن / ایموجی (بدون متن)</option>
                  <option value="decorative">تزئینی (بدون لینک)</option>
                </select>
              </label>
              <label>محل در نوار منو
                <select id="m-cta-pos">
                  <option value="end">انتهای نوار</option>
                  <option value="start">ابتدای نوار</option>
                  <option value="after-logo">بعد از لوگو</option>
                  <option value="before-search">قبل از جستجو</option>
                  <option value="inline_items">بین آیتم‌های منو</option>
                  <option value="coords">مختصات دستی</option>
                  <option value="submenu_col">ستون زیرمنو / مگا</option>
                </select>
              </label>
              <label>هدف دکمه
                <select id="m-cta-target">
                  <option value="bar">نوار اصلی</option>
                  <option value="menu">سرشاخه منو</option>
                  <option value="submenu">زیرمنو</option>
                  <option value="column">ستون مگا</option>
                </select>
              </label>
              <label>شماره ستون مگا <input type="number" id="m-cta-col" min="1" max="8" value="1"></label>
              <label>CTA — X (px از راست) <input type="number" id="m-cta-x" min="0" max="2000" value="16"></label>
              <label>CTA — Y (px از بالا) <input type="number" id="m-cta-y" min="0" max="800" value="6"></label>

              <label>متن روی دکمه <input type="text" id="m-cta-text" value="درخواست اعتبار" placeholder="متن دکمه"></label>
              <label>لینک مقصد (برای نقش ارجاع)
                <div class="cgs-ma-control-row">
                  <input type="url" id="m-cta-url" class="cgs-ma-grow" placeholder="https://">
                  <button type="button" class="cgs-ma-btn sm cgs-3d cgs-browse" data-target="#m-cta-url" data-kind="image">📂</button>
                </div>
              </label>

              <label>حالت رنگ
                <select id="m-cta-color-mode">
                  <option value="solid">تک‌رنگ</option>
                  <option value="gradient" selected>گرادیان دو رنگ</option>
                </select>
              </label>
              <label class="cgs-ma-color-lab">رنگ اصلی
                <span class="cgs-ma-color-pair"><input type="color" id="m-cta-color" value="#00bcd4"><input type="text" id="m-cta-color-hex" class="cgs-ma-hex" value="#00bcd4" maxlength="7"></span>
              </label>
              <label class="cgs-ma-color-lab">رنگ دوم (گرادیان)
                <span class="cgs-ma-color-pair"><input type="color" id="m-cta-color2" value="#0097a7"><input type="text" id="m-cta-color2-hex" class="cgs-ma-hex" value="#0097a7" maxlength="7"></span>
              </label>
              <label class="cgs-ma-span2">شفافیت (٪)
                <div class="cgs-ma-vol-row">
                  <span id="m-cta-opacity-val" class="cgs-vol-pct">100%</span>
                  <input type="range" id="m-cta-opacity" min="0" max="100" value="100">
                </div>
              </label>
              <label>جهت تابش نور
                <select id="m-cta-light">
                  <option value="tl">گوشه چپ بالا</option>
                  <option value="tr">گوشه راست بالا</option>
                  <option value="bl">گوشه چپ پایین</option>
                  <option value="br">گوشه راست پایین</option>
                  <option value="top">از بالا</option>
                  <option value="bottom">از پایین</option>
                  <option value="left">از چپ</option>
                  <option value="right">از راست</option>
                </select>
              </label>

              <label>فونت متن
                <select id="m-cta-font">
                  <option value="inherit">پیش‌فرض قالب</option>
                  <option value="Tahoma,sans-serif">Tahoma</option>
                  <option value="Vazirmatn,Tahoma,sans-serif">وزیر</option>
                  <option value="IranSans,Tahoma,sans-serif">ایران‌سنس</option>
                  <option value="Georgia,serif">Georgia</option>
                </select>
              </label>
              <label>اندازه فونت (px) <input type="number" id="m-cta-font-size" min="10" max="28" value="14"></label>
              <label class="cgs-ma-span2">اندازه دکمه (٪)
                <div class="cgs-ma-vol-row">
                  <span id="m-cta-scale-val" class="cgs-vol-pct">100%</span>
                  <input type="range" id="m-cta-scale" min="50" max="160" value="100">
                </div>
              </label>
              <input type="hidden" id="m-cta-size" value="md">
              <label>زوایای اطراف (px) <input type="number" id="m-cta-radius" min="0" max="40" value="22"></label>

              <label>ایموجی روی دکمه
                <div class="cgs-ma-control-row">
                  <select id="m-cta-emoji" class="cgs-ma-grow">
                    <option value="">— بدون —</option>
                    <option>🔥</option><option>✨</option><option>🚀</option><option>⭐</option><option>❤️</option>
                    <option>✅</option><option>🎁</option><option>📞</option><option>🛒</option><option>💎</option>
                    <option>🏆</option><option>⚡</option><option>🌟</option><option>🎯</option><option>📱</option>
                  </select>
                  <input type="text" id="m-cta-icon" placeholder="دستی" style="max-width:70px">
                </div>
              </label>
              <label>تصویر روی دکمه
                <div class="cgs-ma-control-row">
                  <input type="url" id="m-cta-img" class="cgs-ma-grow" placeholder="URL">
                  <button type="button" class="cgs-ma-btn sm cgs-3d cgs-browse" data-target="#m-cta-img" data-kind="image">📂</button>
                </div>
              </label>

              <input type="hidden" id="m-cta-style" value="glass-capsule">
              <div class="cgs-ma-span-all" style="margin-top:6px">
                <span class="cgs-hint">پیش‌نمایش لحظه‌ای قالب:</span>
                <div id="cgs-cta-live-swatch" class="cgs-cta-live-swatch"><span class="cgs-glass-btn is-sm" style="--glass-c:#00bcd4">درخواست اعتبار</span></div>
              </div>
            </div>
          </div>
        </div>
      </details>

      
    </section>



    <section class="cgs-ma-live-panel" id="cgs-ma-panel-live" hidden>
      <div class="cgs-ma-live-toolbar">
        <div class="cgs-ma-dev-switch" role="group" aria-label="اندازه دستگاه">
          <button type="button" class="cgs-ma-dev is-active" data-dev="desktop">🖥️ دسکتاپ ۱۴۴۰</button>
          <button type="button" class="cgs-ma-dev" data-dev="tablet">📱 تبلت ۷۶۸</button>
          <button type="button" class="cgs-ma-dev" data-dev="mobile">📱 موبایل ۳۹۰</button>
        </div>
        <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-violet" id="cgs-ma-fx-demo-live">✨ تست افکت</button>
        <div class="cgs-ma-monitor-drop">
          <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-navy" id="cgs-ma-refresh-server-preview">🔄 رندر واقعی فرانت</button>
          <button type="button" class="cgs-ma-btn sm cgs-3d cgs-3d-navy" id="cgs-ma-run-monitor">🩺 پایش پیش‌نمایش ▾</button>
          <div class="cgs-ma-monitor-panel" id="cgs-ma-monitor" hidden>
            <div class="cgs-ma-monitor-head">نتیجه پایش</div>
            <div class="cgs-ma-monitor-meta" id="cgs-ma-monitor-meta">—</div>
            <div id="cgs-ma-monitor-list" class="cgs-ma-monitor-list"></div>
            <button type="button" class="cgs-ma-btn sm cgs-3d" id="cgs-ma-copy-monitor">کپی گزارش</button>
          </div>
        </div>
        <span class="cgs-ma-live-hint">فقط پیش‌نمایش واقعی · بدون پنل ویرایش</span>
      </div>
      <div class="cgs-ma-live-canvas">
        <div id="cgs-ma-preview-stage" class="cgs-ma-preview-stage is-desktop" data-frame="desktop">
          <div class="cgs-ma-device-frame">
            <div class="cgs-ma-fake-chrome"><span></span><span></span><span></span><em id="cgs-ma-frame-label">دسکتاپ ۱۴۴۰×۹۰۰</em></div>
            <div id="cgs-ma-preview" class="cgs-ma-preview-root"></div>
          </div>
        </div>
      </div>
    </section>
    <section class="cgs-ma-help-panel" id="cgs-ma-panel-help" hidden>
      <div class="cgs-ma-help-hero" style="background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#fff;padding:16px;border-radius:12px;margin-bottom:12px">
        <h2 style="margin:0 0 8px;font-size:18px">📘 راهنمای قدم‌به‌قدم منوساز شهر قسط</h2>
        <p style="margin:0;opacity:.9;font-size:13px">این راهنما مسیر ساخت یک مگامنوی فروشگاهی واقعی را از صفر تا پیش‌نمایش نشان می‌دهد. هر مرحله را اجرا کنید و نتیجه را در تب «پیش‌نمایش زنده» ببینید.</p>
      </div>
      <details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--sky">نمای بصری جریان کار (وایر فریم)</span></summary>
        <div class="cgs-ma-pad cgs-help-visual" style="font-size:13px">
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
            <div style="border:1px solid #cbd5e1;border-radius:10px;padding:10px;background:#f8fafc">
              <div style="font-weight:800;margin-bottom:6px">۱) انتخاب قالب</div>
              <div style="background:#0f172a;color:#fff;border-radius:8px;padding:8px;font-size:11px;text-align:center">[ قالب دیجیکالا ▼ ] اعمال</div>
            </div>
            <div style="border:1px solid #cbd5e1;border-radius:10px;padding:10px;background:#f8fafc">
              <div style="font-weight:800;margin-bottom:6px">۲) درخت آیتم</div>
              <div style="font-size:11px;line-height:1.6">☰ دسته‌بندی<br>&nbsp;&nbsp;↳ موبایل<br>&nbsp;&nbsp;↳ لپ‌تاپ<br>☰ پیشنهادها</div>
            </div>
            <div style="border:1px solid #cbd5e1;border-radius:10px;padding:10px;background:#f8fafc">
              <div style="font-weight:800;margin-bottom:6px">۳) پیش‌نمایش مگا</div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;background:#0f172a;color:#e2e8f0;padding:8px;border-radius:8px;font-size:10px">
                <div>ستون۱<br>لینک<br>لینک</div>
                <div>ستون۲<br>لینک<br>لینک</div>
                <div>بنر<br>CTA</div>
              </div>
            </div>
            <div style="border:1px solid #cbd5e1;border-radius:10px;padding:10px;background:#f8fafc">
              <div style="font-weight:800;margin-bottom:6px">۴) ذخیره و شورت‌کد</div>
              <code dir="ltr" style="font-size:11px">[cgs_menu id="main"]</code>
            </div>
          </div>
        </div>
      </details>


      <details class="cgs-ma-acc" open>
        <summary><span class="cgs-ma-bar cgs-ma-bar--sky">گام ۱ — منوی جدید یا انتخاب منو</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ol>
            <li>از بالای صفحه روی <strong>منوی جدید</strong> بزنید یا از فهرست یک منوی موجود را انتخاب کنید.</li>
            <li>در تنظیمات عمومی: <strong>عنوان</strong>، <strong>شناسه</strong> و <strong>چیدمان</strong> را مشخص کنید.</li>
            <li>برای مگامنو فروشگاهی، چیدمان را روی یکی از گزینه‌های <em>مگامنو چندستونه / دیجیکالا / فروشگاهی</em> بگذارید.</li>
          </ol>
          <p style="background:#f0f9ff;border:1px solid #bae6fd;padding:8px;border-radius:8px">✓ انتظار: عنوان منو در لیست سمت بالا دیده می‌شود.</p>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--emerald">گام ۲ — اعمال قالب آماده (سریع‌ترین مسیر)</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ol>
            <li>از نوار بالا، فهرست <strong>قالب آماده</strong> را باز کنید.</li>
            <li>یکی از این‌ها را انتخاب کنید: <em>دیجیکالا</em>، <em>فروشگاهی + اسلایدر</em>، <em>گرید برند</em>، <em>Hero محتوایی</em>.</li>
            <li>دکمه <strong>اعمال قالب آماده</strong> را بزنید.</li>
            <li>به تب <strong>پیش‌نمایش زنده</strong> بروید و روی آیتم ریشه هاور کنید تا ستون‌ها باز شوند.</li>
          </ol>
          <p style="background:#ecfdf5;border:1px solid #a7f3d0;padding:8px;border-radius:8px">✓ انتظار: آیتم‌ها در «آیتم‌ها و زیرمنوها» پر می‌شوند و پیش‌نمایش مگای چندستونه نشان می‌دهد.</p>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--amber">گام ۳ — ساخت دستی درخت و ستون</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ol>
            <li>دکمه <strong>＋ آیتم جدید</strong> را بزنید.</li>
            <li>با دکمه‌های <strong>↳</strong> آیتم را زیرمنو کنید؛ با <strong>↑ ↓</strong> جابه‌جا کنید؛ با <strong>🗑</strong> حذف کنید.</li>
            <li>روی خلاصه آیتم کلیک کنید تا بدنه باز شود؛ عنوان، URL و نوع محتوا را تنظیم کنید.</li>
            <li>نوع محتوا را برای گروه‌بندی روی <em>ستون (column)</em> یا <em>ردیف (row)</em> بگذارید و زیرآیتم‌های لینک را داخل آن بسازید.</li>
          </ol>
          <p style="background:#fffbeb;border:1px solid #fde68a;padding:8px;border-radius:8px">✓ انتظار: ساختار درختی با تورفتگی سطح‌ها؛ در پیش‌نمایش ستون‌ها کنار هم.</p>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--violet">گام ۴ — ظاهر، رنگ، افکت</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ol>
            <li>در تنظیمات عمومی: نوع پس‌زمینه (ساده / گرادیان / تصویر) را انتخاب کنید.</li>
            <li>رنگ پس‌زمینه، متن، هاور و فعال را از پالت تغییر دهید — باید بلافاصله در پیش‌نمایش منعکس شود.</li>
            <li>افکت زیرمنو و سرعت باز شدن را تنظیم کنید و در پیش‌نمایش هاور کنید.</li>
            <li>تعداد ستون مگا را با فیلد <strong>تعداد ستون مگا</strong> هم‌خوان با قالب نگه دارید.</li>
          </ol>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--rose">گام ۵ — ذخیره و استفاده در سایت</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ol>
            <li>دکمه <strong>ذخیره</strong> را بزنید تا نسخه روی سرور ثبت شود.</li>
            <li>در برگه یا ویجت، شورت‌کد را قرار دهید: <code dir="ltr">[cgs_menu id="شناسه-منو"]</code></li>
            <li>خروجی فرانت باید همان ساختار پیش‌نمایش باشد (مسیر Renderer واحد).</li>
          </ol>
          <p style="background:#fef2f2;border:1px solid #fecaca;padding:8px;border-radius:8px">اگر پیش‌نمایش خالی است: ابتدا قالب اعمال کنید یا چند آیتم بسازید، سپس سخت‌رفرش ادمین.</p>
        </div>
      </details>

      <details class="cgs-ma-acc">
        <summary><span class="cgs-ma-bar cgs-ma-bar--slate">عیب‌یابی سریع</span></summary>
        <div class="cgs-ma-pad" style="font-size:13px;line-height:1.8">
          <ul>
            <li><strong>عناصر فقط دکور:</strong> Ctrl+Shift+R — کش JS قدیمی.</li>
            <li><strong>حذف آیتم کار نمی‌کند:</strong> از دکمه 🗑 روی خلاصه آیتم استفاده کنید.</li>
            <li><strong>رنگ اعمال نمی‌شود:</strong> پس از تغییر رنگ، ذخیره و پیش‌نمایش را تازه کنید.</li>
            <li><strong>مگا ستون‌بندی ندارد:</strong> چیدمان را روی mega / mega-sidebar بگذارید و تعداد ستون را تنظیم کنید.</li>
          </ul>
        </div>
      </details>
    </section>



  </div>
  <div id="cgs-ma-toast" class="cgs-ma-toast" hidden></div>
</div>
