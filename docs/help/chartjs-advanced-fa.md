# راهنمای پیشرفته Chart.js در شهر قسط

## محل فایل‌ها
| فایل | نقش |
|------|-----|
| `assets/js/chart.umd.min.js` | کتابخانه محلی Chart.js 4.4.1 |
| `modules/charts/bootstrap.php` | ماژول قالب‌بندی + فعال/غیرفعال |
| `admin/views/dashboard.php` | نمودار وضعیت / انواع / روند |
| `admin/views/crm.php` | نمودار قیف فروش CRM |
| `admin/views/settings.php` (تب نمودارها) | تنظیمات ادمین |
| گزینه `cgs_settings[chart_format]` و `cgs_chart_format_v` | ذخیره‌سازی |

## فعال‌سازی
1. تنظیمات → افزونه‌ها → **موتور نمودار داینامیک** روشن
2. تب **نمودارها** → نوع، انیمیشن، راهنما، عنوان
3. فقط از **همان تب** «ذخیره قالب‌بندی نمودار» را بزنید
4. داشبورد یا CRM را باز کنید (رفرش سخت در صورت نیاز)

## انواع پشتیبانی‌شده
- وضعیت: `doughnut` | `pie` | `bar` | `polarArea` | `horizontalBar` (به bar + indexAxis تبدیل می‌شود)
- انواع درخواست: `bar` | `horizontalBar` | `pie` | `doughnut`
- روند: `line` | `bar`
- CRM: از `status_type` قالب‌بندی مشترک استفاده می‌کند

## کلیدهای مهم قالب‌بندی
```
status_type, types_type, trend_type
legend_position, show_legend
animation, anim_duration
cutout, border_width, border_color
bar_radius, line_tension, line_fill, point_radius
show_grid, show_title
title_status, title_types, title_trend, title_crm
font_size, font_family, aspect_ratio
```

## الگوی ایمن در کد
```javascript
function cgsChartSafe(el, cfg) {
  if (!el || typeof Chart === 'undefined') return null;
  var old = Chart.getChart ? Chart.getChart(el) : null;
  if (old) old.destroy();
  try { return new Chart(el, cfg); }
  catch (e) { console.error(e); return null; }
}
```

## عیب‌یابی
| علامت | علت محتمل | کار |
|--------|-----------|-----|
| نمودار خالی | Chart.js لود نشده | کنسول: `typeof Chart` |
| تغییر تنظیمات بی‌اثر | ذخیره از تب دیگر | فقط تب نمودارها ذخیره شود |
| بعد از رفرش برگشت | کش | `cgs_chart_format_v` و Ctrl+F5 |
| CRM ثابت doughnut | نسخه قدیمی | نسخه ۴.۵.۶+ |

## نکات حرفه‌ای
- همیشه Chart محلی باشد (CDN در ایران ممکن است قطع شود)
- قبل از `new Chart` نمونه قبلی را destroy کنید
- چک‌باکس‌های قالب فقط وقتی تب نمودارها باز است ذخیره شوند
- برای داده صفر، حداقل یک نقطه ساختگی نشان ندهید — پیام «داده‌ای نیست» بهتر است
