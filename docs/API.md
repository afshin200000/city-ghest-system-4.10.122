# مستندات API افزونه شهر قسط (City Ghest System)

نسخه مستند: 1.0  
پایه: WordPress REST/AJAX — همه درخواست‌های کاربری از `admin-ajax.php` با nonce

---

## ۱. احراز هویت درخواست‌ها

| زمینه | nonce action | پارامتر |
|--------|----------------|---------|
| فرانت (فرم، پرداخت، استعلام) | `cgs_frontend_nonce` | `nonce` |
| ادمین (فرم‌ساز، تنظیمات AJAX) | `cgs_admin_nonce` | `nonce` |

```
POST /wp-admin/admin-ajax.php
Content-Type: application/x-www-form-urlencoded

action=...&nonce=...&...
```

پاسخ استاندارد:
```json
{ "success": true, "data": { } }
{ "success": false, "data": { "message": "..." } }
```

---

## ۲. پرداخت (CGS_Payment)

### ۲.۱ لیست درگاه‌ها

| کلید | نوع | Sandbox |
|------|-----|---------|
| zarinpal | واسطه | بله |
| idpay | واسطه | بله |
| nextpay | واسطه | خیر |
| payir | واسطه | بله (api=test) |
| zibal | واسطه | بله (merchant=zibal) |
| payping | واسطه | خیر |
| mellat | بانک ملت | خیر (SOAP) |
| saman | بانک سامان | خیر |
| sadad | بانک ملی سداد | خیر |
| parsian | بانک پارسیان | خیر (SOAP) |
| pasargad | بانک پاسارگاد | گواهی |

### ۲.۲ شروع پرداخت

`action=cgs_pay_request`

| پارامتر | الزامی | توضیح |
|---------|--------|--------|
| amount | بله | مبلغ به **ریال** |
| order_id | خیر | شناسه سفارش؛ در صورت خالی تولید می‌شود |
| mobile | خیر | موبایل پرداخت‌کننده |
| email | خیر | ایمیل |
| purpose | خیر | مثلاً `credit_check` |

پاسخ موفق:
```json
{
  "success": true,
  "data": {
    "redirect_url": "https://...",
    "authority": "...",
    "order_id": "CGS-...",
    "method": "GET|POST",
    "form_fields": { }
  }
}
```

اگر `method=POST` باشد، باید یک فرم HTML با `form_fields` به `redirect_url` ارسال شود (ملت/سامان).

### ۲.۳ تأیید پرداخت

- **Callback مرورگر:** `/?cgs_pay=1&order_id=...` (+ پارامترهای درگاه)
- **AJAX:** `action=cgs_pay_verify` + `order_id`

هوک وردپرس پس از پرداخت موفق:
```php
do_action( 'cgs_payment_paid', $order_id, $tx_array, $ref_id );
```

### ۲.۴ فراخوانی برنامه‌ای (PHP)

```php
$result = CGS_Payment::request( 150000, array(
    'order_id' => 'CGS-ORDER-1',
    'mobile'   => '09120000000',
    'purpose'  => 'credit_check',
) );
if ( is_wp_error( $result ) ) {
    // خطا
} else {
    // $result['redirect_url']
}

$verify = CGS_Payment::verify( 'CGS-ORDER-1', $_REQUEST );
```

---

## ۳. خودکارسازی استعلام (CGS_Auto_Verify)

همه قابل خاموش/روشن از **تنظیمات → خودکارسازی و API**  
حالت‌ها: `manual` | `demo` | `auto`

| action | ورودی | خروجی موفق (demo) |
|--------|--------|-------------------|
| `cgs_av_national_id` | `national_id` | نام، نام‌خانوادگی، پدر، شناسنامه، محل/تاریخ تولد، محل صدور |
| `cgs_av_postal` | `postal_code` | `address` + پیام همخوانی سند |
| `cgs_av_mobile` | `national_id`, `mobile` | `matched: true/false` |
| `cgs_av_sheba` | شبا در POST | مالکیت، چک در راه، سقف ادمین |
| `cgs_av_credit` | — | رتبه آزمایشی، `fee` |

**نکته امنیتی:** API واقعی ثبت احوال/شاهکار/پست فقط از سرور افزونه (نه مرورگر کاربر) و با کلید ذخیره‌شده در options فراخوانی شود.

---

## ۴. فرم و ثبت درخواست

| action | نقش |
|--------|-----|
| `cgs_submit_application` | ثبت فرم چندمرحله‌ای |
| فیلدها | بر اساس `field_key` در جدول `wp_cgs_form_fields` |

---

## ۵. اتصال اعتبارسنجی + پرداخت

جریان پیشنهادی:

1. کاربر درخواست اعتبار می‌دهد  
2. اگر `av_credit_enabled` و `av_credit_fee > 0` → `cgs_pay_request` با `purpose=credit_check`  
3. پس از `cgs_payment_paid` → فراخوانی API اعتباریتو (nics24) با کلید ادمین  
4. نتیجه با رتبه مجاز طرح مقایسه و وضعیت درخواست به‌روز می‌شود  

```php
add_action( 'cgs_payment_paid', function( $order_id, $tx, $ref ) {
    if ( ( $tx['meta']['purpose'] ?? '' ) === 'credit_check' ) {
        // فراخوانی CGS_Auto_Verify / API اعتباریتو
    }
}, 10, 3 );
```

---

## ۶. تنظیمات ذخیره‌شده

| option | محتوا |
|--------|--------|
| `cgs_payment` | gateway, sandbox, merchant_id, api_key, terminal_id, username, password, currency |
| `cgs_payment_tx` | تراکنش‌های اخیر (حداکثر ۵۰۰) |
| `cgs_form_styles` | ظاهر فرم به‌ازای type_key |
| `cgs_step_meta_{type}` | نام/آیکن/ستون/فایل هر مرحله |

---

## ۷. خطاهای رایج درگاه

| کد/وضعیت | معنی |
|-----------|------|
| no_gateway | درگاه در تنظیمات انتخاب نشده |
| config | کلید/ترمینال ناقص |
| soap | افزونه SOAP سرور برای ملت/پارسیان |
| cancelled | کاربر از درگاه برگشته بدون پرداخت |
| pasargad_cert | گواهی پاسارگاد پیکربندی نشده |

---

## ۸. امنیت

- مبلغ و وضعیت نهایی فقط از **verify سمت سرور** پذیرفته شود؛ به پارامتر GET اعتماد نکنید.  
- کلیدها در `wp_options` — دسترسی فقط `manage_options`.  
- Callback فقط از طریق `handle_callback` و `order_id` معتبر در `cgs_payment_tx`.  
- برای تولید، HTTPS اجباری است.

---

## ۹. توسعه درگاه جدید

1. متدهای `xxx_request` و `xxx_verify` در `CGS_Payment`  
2. ثبت در `gateways_list()`  
3. case در `request()` / `verify()`  
4. فیلدهای تنظیمات در تب درگاه پرداخت  

