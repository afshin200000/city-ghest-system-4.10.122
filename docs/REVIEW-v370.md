# بازبینی تجاری v3.7.0

## مقایسه با فرم‌سازهای جهانی (نمونه الگوهای اقتباس‌شده)
Gravity Forms, WPForms, Formidable, Ninja Forms, Typeform, Jotform, Fluent Forms, HappyForms, MetForm, everest-forms,
Bit Form, weForms, Contact Form 7 patterns, Quform, ws-form, Forminator, Kali Forms, ARForms, Visual Form Builder, Super Forms

### اقتباس و محل پیاده‌سازی
| الگو | منبع | محل در شهر قسط |
|------|------|----------------|
| Multi-step + progress | GF, Typeform | form-multi-step + نوار مراحل |
| Conditional logic | GF, Formidable | CGS_Conditional_Logic |
| Style designer live | Fluent, MetForm | CGS_Form_Styles + پیش‌نمایش |
| Template library | WPForms | CGS_Form_Templates |
| File upload preview | Jotform | cgs-upload-preview |
| Column layout per page | GF page breaks + columns | cgsApplyStepColumns واحد |
| Validation Iranian domain | — | CGS_Validation |
| Confirmation / success | Typeform | پیام موفقیت + صدا |

## معماری
ماژول ایزوله (CGS_Modules) · کش (CGS_Cache) · درگاه AJAX (CGS_Ajax)

## چرخه بازبینی
حذف handlerهای تکراری ستون · اتصال st-form-columns · حذف CSS مرده نقشه · a11y · تست واحد سبز
