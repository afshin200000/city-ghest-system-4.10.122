# City Ghest System v3.1.0 — معماری ماژولار

## اصل طراحی
هر ماژول در `CGS_Modules` با try/catch جدا بارگذاری می‌شود.
خرابی یک ماژول بقیه را متوقف نمی‌کند (نوتیس ادمین در صورت خطا).

## هسته
- `CGS_Cache` — object cache + transient
- `CGS_Ajax` — دروازه امنیتی یکپارچه AJAX
- `CGS_Modules` — رجیستری و boot ایزوله

## ماژول‌ها
roles, security, database, form_builder, form_styles, form_templates,
application, sms, digital_sign, plans, crm, query_monitor, chat, member,
auto_verify, payment, admin (فقط ادمین), frontend

## بهینه‌سازی دارایی
- Leaflet / نقشه / جلالی / sortable فقط در صفحه فرم‌ساز
- Chart.js فقط داشبورد و CRM
- locations JSON فقط در فرم‌ساز localize می‌شود

## کش
- فیلدهای فرم (۵ دقیقه)
- استان/شهر ایران (۱ ساعت)
- شمارش درخواست‌ها (۶۰ ثانیه)

## امنیت
- Nonce روی تنظیمات و AJAX
- Capability manage_options
- Sanitize ورودی‌ها
