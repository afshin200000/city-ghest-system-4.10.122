<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<div class="wrap cgs-ic-admin" dir="rtl" style="font-family:Vazirmatn,Tahoma,sans-serif;font-weight:600">
<div id="cgs-ic-modal" class="cgs-ic-modal" hidden>
	<div class="cgs-ic-modal-backdrop"></div>
	<div class="cgs-ic-modal-dialog" role="dialog" aria-modal="true">
		<div class="cgs-ic-modal-head">
			<strong id="cgs-ic-modal-title">نتیجه محاسبه</strong>
			<button type="button" class="cgs-ic-modal-close" aria-label="بستن">×</button>
		</div>
		<div class="cgs-ic-modal-body" id="cgs-ic-modal-body"></div>
		<div class="cgs-ic-modal-foot">
			<button type="button" class="button cgs-ic-modal-close">بستن</button>
		</div>
	</div>
</div>

	<h1>محاسبه‌گر اقساط — موتور چندفرمولی</h1>
	<p class="description">روش «تامین اجتماعی» مطابق جدول اکسل پیاده شده است. روش‌های دیگر و فرمول‌های جدید از تب طرح‌ها قابل انتخاب‌اند. شورت‌کد: <code>[cgs_installment_calculator]</code></p>

	<div class="cgs-ic-tabs">
		<button type="button" class="cgs-ic-tab is-active" data-tab="calc">ماشین‌حساب</button>
		<button type="button" class="cgs-ic-tab" data-tab="discover">کشف ضریب تامین‌کننده</button>
		<button type="button" class="cgs-ic-tab" data-tab="preview">پیش‌نمایش متقاضی</button>
		<button type="button" class="cgs-ic-tab" data-tab="leasing">مدیریت طرح‌های لیزینگ</button>
		<button type="button" class="cgs-ic-tab" data-tab="plans">طرح‌ها و ضرایب پیشرفته</button>
		<button type="button" class="cgs-ic-tab" data-tab="sensitivity">تحلیل حساسیت نرخ</button>
		<button type="button" class="cgs-ic-tab" data-tab="compare">جدول مقایسه‌ای</button>
		<button type="button" class="cgs-ic-tab" data-tab="help">راهنما و منطق اکسل</button>
	</div>

	<div class="cgs-ic-panel is-active" id="cgs-ic-panel-calc">
		<div class="cgs-ic-card" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between">
			<div>
				<strong style="display:block;margin-bottom:6px">نمایش اعداد</strong>
				<label style="margin-left:14px;font-weight:600"><input type="radio" name="ic-num-format" value="fa" checked> فارسی — جداکننده /</label>
				<label style="margin-left:14px;font-weight:600"><input type="radio" name="ic-num-format" value="en"> انگلیسی — جداکننده ,</label>
			</div>
			<span style="font-size:12px;color:#64748b">فیلدهای مبلغ ابتدا خالی‌اند؛ با خروج از فیلد جداکننده اعمال می‌شود.</span>
		</div>
		<div class="cgs-ic-card">
			<h2>محاسبه</h2>

			<div class="cgs-ic-box cgs-ic-box-main">
				<div class="cgs-ic-box-title">انتخاب طرح و مبلغ</div>
				<div class="cgs-ic-form-grid">
					<label>طرح<select id="ic-plan"></select></label>
					<label>اصل مبلغ / قیمت نقدی (ریال)<input type="text" id="ic-principal" value="" inputmode="numeric" autocomplete="off"></label>
					<label>مدت (ماه)<select id="ic-months"></select></label>
					<label>گام بازپرداخت<select id="ic-step"></select></label>
					<label style="display:none">روش محاسبه<select id="ic-method">
						<?php foreach ( $methods as $k => $lab ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lab ); ?></option>
						<?php endforeach; ?>
					</select></label>
					<label>ضریب طرح = ضریب سود نهایی مشتری ٪<input type="number" step="0.01" id="ic-coef" value="" readonly style="background:#f1f5f9" title="از تب طرح‌ها — بر اساس مدت"></label>
				</div>
			</div>

			<div class="cgs-ic-box cgs-ic-box-salary" id="ic-box-salary" style="display:none">
				<div class="cgs-ic-box-title">کسر از حقوق / فیش</div>
				<div class="cgs-ic-form-grid">
					<label>حقوق خالص ماهانه (فیش)<input type="text" id="ic-net-salary" value="" inputmode="numeric"></label>
					<label>سازمان کسر از حقوق<select id="ic-salary-org"></select></label>
					<label>وضعیت شغلی<select id="ic-emp-status"></select></label>
					<label>ضریب سود ماهانه تامین اولیه (٪)<input type="number" step="0.0001" id="ic-primary" value="" readonly style="background:#f1f5f9"></label>
					<label>ضریب کسر ثانویه (٪ از اصل)<input type="number" step="0.01" id="ic-supplier" value="" readonly style="background:#f1f5f9"></label>
					<label>ضریب سود شهر قسط (٪)<input type="number" step="0.01" id="ic-city" value="" readonly style="background:#f1f5f9"></label>
				</div>
				<div id="ic-payslip-ceiling-box" style="display:none;margin-top:8px;padding:10px;background:#ecfdf5;border-radius:10px;font-weight:700;color:#065f46"></div>
			</div>

			<div class="cgs-ic-box cgs-ic-box-manisa" id="ic-box-manisa" style="display:none">
				<div class="cgs-ic-box-title">مانسیا / زنجیره تامین (بر اساس مدت انتخاب‌شده)</div>
				<div class="cgs-ic-form-grid">
					<label>هزینه زیرساخت دیجیتال — کسر اولیه (٪ از اصل)<input type="number" step="0.01" id="ic-infra-pct" value="" readonly style="background:#f1f5f9" title="برای هر مدت (۱۲، ۱۸، …) جداگانه از طرح"></label>
					<label>هزینه زیرساخت (ریال)<input type="number" id="ic-digital-auto" value="" readonly style="background:#f1f5f9"></label>
					<label>کارمزد تامین‌کننده ثانویه (٪ از واریزی اولیه)<input type="number" step="0.01" id="ic-sec-chain-ro" value="" readonly style="background:#f1f5f9"></label>
					<label>کارمزد شهر قسط (٪ از واریزی ثانویه)<input type="number" step="0.01" id="ic-city-chain-ro" value="" readonly style="background:#f1f5f9"></label>
				</div>
			</div>

			<div class="cgs-ic-box cgs-ic-box-agent" id="ic-box-agent">
				<div class="cgs-ic-box-title">نماینده (اختیاری)</div>
				<div class="cgs-ic-form-grid">
					<label>سهم نماینده %<input type="number" step="0.01" id="ic-agent" value=""></label>
					<label>محل کسر سهم نماینده
						<select id="ic-agent-mode">
							<option value="from_city">از سهم شهر قسط</option>
							<option value="from_credit">از اعتبار مشتری</option>
						</select>
					</label>
				</div>
			</div>

			<label style="display:none">هزینه زیرساخت قدیمی<input type="number" id="ic-digital" value=""></label>
			<p style="margin-top:14px"><button type="button" class="button button-primary button-hero" id="ic-run">محاسبه</button></p>
			<div id="ic-result" class="cgs-ic-result" hidden></div>
			<div id="ic-bank-all" class="cgs-ic-card" style="display:none;margin-top:12px">
				<h3 style="margin:0 0 8px">مقایسه سود بانکی و این طرح‌ها (همه طرح‌های فعال)</h3>
				<p style="font-size:12px;color:#64748b;margin:0 0 10px">با مبلغ و مدت فعلی ماشین‌حساب: قسط و جمع هر طرح در کنار وام بانکی قسط‌ثابت. وام بانکی فقط مرجع مقایسه است و در محاسبه لیزینگ دخالت ندارد.</p>
				<div style="overflow:auto">
				<table class="widefat striped" id="ic-bank-all-table" style="min-width:760px">
					<thead><tr>
						<th>طرح</th><th>روش</th><th>مدت</th>
						<th>قسط طرح</th><th>جمع طرح</th>
						<th>قسط بانکی</th><th>جمع بانکی</th>
						<th>اختلاف قسط</th><th>اختلاف جمع</th>
						<th>ضریب ماهانه طرح ٪</th>
					</tr></thead>
					<tbody></tbody>
				</table>
				</div>
			</div>
			<div id="ic-chart-wrap" class="cgs-ic-card" style="display:none;margin-top:12px">
				<h3 style="margin-top:0">نمودار زمان‌بندی اقساط لیزینگ</h3>
				<canvas id="ic-chart" height="200"></canvas>
			</div>
		</div>
	</div>

	<div class="cgs-ic-panel" id="cgs-ic-panel-discover" hidden>
		<div class="cgs-ic-card">
			<h2>کشف ضریب سود تامین‌کننده</h2>
			<p>اصل مبلغ درخواستی مشتری، مبلغ واریزی تامین‌کننده به شهر قسط، و مدت را وارد کنید. در صورت دانستن جمع اصل‌وفرع (یا قسط×تعداد)، نرخ ماهانه اولیه هم استخراج می‌شود.</p>
			<div class="cgs-ic-form-grid">
				<label>اصل مبلغ درخواستی مشتری<input type="text" id="dc-principal" value="" inputmode="numeric"></label>
				<label>واریزی تامین‌کننده به حساب شهر قسط<input type="text" id="dc-deposit" value="2910000000"></label>
				<label>مدت (ماه)<input type="number" id="dc-months" value="6"></label>
				<label>جمع اصل‌وفرع / بازپرداخت کل (اختیاری)<input type="text" id="dc-total" value="3630000000" placeholder="اگر معلوم است"></label>
			</div>
			<p><button type="button" class="button button-primary" id="dc-run">استخراج ضریب</button></p>
			<div id="dc-result" class="cgs-ic-result" hidden></div>
		</div>
	</div>

	<div class="cgs-ic-panel" id="cgs-ic-panel-preview" hidden>
		<div class="cgs-ic-card">
			<div class="cgs-ic-phone">
				<div class="cgs-ic-phone-bar">پیش‌نمایش متقاضی</div>
				<div class="cgs-ic-phone-body"><?php echo do_shortcode( '[cgs_installment_calculator]' ); ?></div>
			</div>
		</div>
	</div>

	
	<div class="cgs-ic-panel" id="cgs-ic-panel-leasing" hidden>
		<div class="cgs-ic-card">
			<h2>مدیریت طرح‌های لیزینگ</h2>
			<p>طرح‌های مبتنی بر لیزینگ (مانسیا، رازی/دایانا، چک صیادی و طرح‌های مشابه) را اینجا ببینید و ویرایش سریع کنید. ذخیره نهایی از همان دکمه ذخیره طرح‌ها در تب «طرح‌ها و ضرایب» است؛ این تب فیلتر و تمرکز روی لیزینگ است.</p>
			<div id="ic-leasing-list"></div>
			<p><button type="button" class="button" id="ic-leasing-add">+ طرح لیزینگ جدید</button>
			<button type="button" class="button button-primary" id="ic-leasing-goto-save">رفتن به ذخیره کامل طرح‌ها</button></p>
		</div>
	</div>
	<div class="cgs-ic-panel" id="cgs-ic-panel-plans" hidden>
		<div class="cgs-ic-card">
			<div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap">
				<h2 style="margin:0">طرح‌ها</h2>
				<button type="button" class="button button-primary" id="ic-add-plan">+ طرح</button>
			</div>
			<div id="ic-plans-list"></div>
			<p><button type="button" class="button button-primary button-hero" id="ic-save-plans">ذخیره طرح‌ها</button> <span id="ic-save-msg"></span></p>
		</div>
	</div>

	
	<div class="cgs-ic-panel" id="cgs-ic-panel-sensitivity" hidden>
		<div class="cgs-ic-card">
			<h2>تحلیل حساسیت نرخ / جمع‌نرخ / جمع‌نرخ</h2>
			<p>با تغییر ±۲٪ و ±۵٪ نسبت به نرخ پایه، اثر روی قسط، اعتبار و نرخ مؤثر دیده می‌شود.</p>
			<p><button type="button" class="button button-primary" id="ic-sensitivity">اجرای تحلیل روی مقادیر ماشین‌حساب</button></p>
			<table class="widefat striped" id="ic-sens-table" style="display:none">
				<thead><tr><th>تغییر نرخ</th><th>قسط دوره‌ای</th><th>اعتبار مشتری</th><th>جمع بازپرداخت</th><th>نرخ مؤثر سالانه</th></tr></thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<div class="cgs-ic-panel" id="cgs-ic-panel-compare" hidden>
		<div class="cgs-ic-card">
			<h2>جدول مقایسه‌ای طرح‌ها و مدت‌ها</h2>
			<label>مبلغ مبنا (ریال) <input type="text" id="ic-compare-principal" value="" style="max-width:220px"></label>
			<p><button type="button" class="button button-primary" id="ic-compare">ساخت جدول مقایسه</button></p>
			<div style="overflow:auto">
			<table class="widefat striped" id="ic-compare-table" style="display:none;min-width:800px">
				<thead><tr><th>طرح</th><th>روش</th><th>مدت</th><th>گام</th><th>قسط</th><th>اعتبار</th><th>جمع بازپرداخت</th><th>چک ضمانت</th><th>نرخ مؤثر</th><th>ریسک</th><th>ضامن</th></tr></thead>
				<tbody></tbody>
			</table>
			</div>
		</div>
	</div>
	<div class="cgs-ic-panel" id="cgs-ic-panel-help" hidden>
		<div class="cgs-ic-card cgs-ic-help">
			<h2>سه روش اکسل در موتور</h2>
			<div class="cgs-ic-help-block">
				<h3>۱) تامین اجتماعی</h3>
				<p>اصل‌وفرع = نقدی×(۱+نرخ‌ماهانه×مدت) — کارمزد ثانویه٪ و سهم شهر قسط٪ از نقدی.</p>
			</div>
			<div class="cgs-ic-help-block">
				<h3>۲) مانسیا (کالای دیجیتال)</h3>
				<p>اصل‌وفرع = نقدی×جمع‌نرخ؛ کارمزد اولیه ماهانه×مدت؛ سپس ۱۳.۲٪ از باقیمانده نصف ثانویه / نصف شهر قسط؛ چک تضمین ۱۵۰٪.</p>
			</div>
			<div class="cgs-ic-help-block">
				<h3>۳) رازی/دایانا (فروش چکی)</h3>
				<p>پیش‌پرداخت + جمع‌نرخ؛ سهم تامین‌کننده + ارزش افزوده؛ سهم شهر قسط؛ <b>همیشه ضامن و چک ضمانت الزامی</b>؛ کف و سقف توسط ادمین.</p>
			</div>
			<h2>جزئیات تامین (روش اول)</h2>
			<div class="cgs-ic-help-block">
				<h3>فرمول‌های استخراج‌شده از نمونه ۱۰ مهر ۱۴۰۳</h3>
				<div class="cgs-ic-formula" dir="ltr">
principal_interest = cash × (1 + primary_monthly_rate × months)<br>
مثال ۶ماه: 3e9 × (1 + 0.035×6) = 3,630,000,000<br>
installment = principal_interest ÷ months<br>
secondary_fee = cash × 3%<br>
supplier_deposit = cash − secondary_fee<br>
city_share = cash × 7%<br>
credit_to_customer = cash − secondary_fee − city_share<br>
monthly_coef = ((principal_interest / credit) − 1) × 100 ÷ months<br>
ratio = principal_interest / credit
				</div>
			</div>
			<div class="cgs-ic-help-block">
				<h3>سهم نماینده</h3>
				<ul>
					<li><b>از سهم شهر قسط:</b> مبلغ نماینده از سود شهر قسط کم می‌شود؛ اعتبار مشتری تغییر نمی‌کند.</li>
					<li><b>از اعتبار مشتری:</b> مبلغ نماینده از قدرت خرید / اعتبار تخصیص‌یافته کسر می‌شود.</li>
				</ul>
			</div>
			<div class="cgs-ic-help-block">
				<h3>افزودن فرمول جدید</h3>
				<p>در کد، متدهای <code>formula_*</code> و فهرست <code>methods_list()</code> گسترش‌پذیرند. در طرح، فیلد «روش محاسبه» همان کلید را انتخاب می‌کند. خروجی همه روش‌ها از <code>enrich_result</code> عبور می‌کند (ضامن، نرخ مؤثر، ریسک).</p>
			</div>
			<div class="cgs-ic-help-block">
				<h3>اشتباه رایج سهم شهر قسط</h3>
				<p>سهم شهر قسط و کارمزد ثانویه <b>فقط یک‌بار</b> و بر اساس <b>اصل مبلغ درخواستی</b> محاسبه می‌شوند — نه روی مبلغ پس از کسر ثانویه. مثال اکسل: اصل ۳/۰۰۰/۰۰۰/۰۰۰، ثانویه ۹۰/۰۰۰/۰۰۰، سهم شهر ۲۱۰/۰۰۰/۰۰۰ (نه ۲۰۳/۷۰۰/۰۰۰ که ۷٪ واریزی است).</p>
				<p>فرمول اکسل شما: <code>x = H/S</code> سپس <code>(x×100 − 100)/E</code><br>
				H=اصل‌وفرع ، <b>S=اعتبار تخصیص‌یافته (مبلغ، نه ضریب)</b> ، E=مدت<br>
				مثال ۶ماه: x=۱.۳۴۴۴… → ضریب ماهانه برای ۶ ماه: <b>۵.۷۴۰۷۴۰۷۴۰۷٪</b> دقیق؛ گردشده ۲ رقم: <b>۵.۷۴٪</b>؛ گرد صحیح: <b>۶٪</b>.
				این عدد نرخ بهره بانکی نیست و از فرمول ((اصل‌وفرع÷اعتبار)−۱)×۱۰۰÷مدت به‌دست می‌آید. جمع کسورات یک‌باره = ۹۰/۰۰۰/۰۰۰ + ۲۱۰/۰۰۰/۰۰۰ = <b>۳۰۰/۰۰۰/۰۰۰</b>. از فرمول ((اصل‌وفرع÷اعتبار)−۱)×۱۰۰÷مدت.</p>
			</div>
			<div class="cgs-ic-help-block">
				<h3>کف، سقف و ضامن</h3>
				<ul>
					<li><b>کف مبلغ:</b> همیشه توسط ادمین برای طرح تعیین می‌شود.</li>
					<li><b>سقف:</b> منبع می‌تواند ادمین، تامین‌کننده (طرح چک) یا فیش حقوقی (کسر از حقوق — فرمول بعداً) باشد.</li>
					<li><b>آستانه ضامن:</b> تا این مبلغ ضامن لازم نیست؛ بالاتر الزامی است (در همه مدت‌های همان طرح).</li>
				</ul>
			</div>
			<div class="cgs-ic-help-block">
				<h3>نرخ اسمی در برابر مؤثر</h3>
				<p><b>اسمی:</b> نرخی که در قرارداد/ضریب اعلام می‌شود (مثلاً ۰.۰۳۵ ماهانه × ۱۲). <b>مؤثر:</b> از نسبت واقعی «جمع بازپرداخت به اصل» در طول مدت، سالانه‌سازی می‌شود. اختلاف آن‌ها نشان می‌دهد هزینه واقعی برای مشتری چقدر با برچسب اسمی فرق دارد.</p>
			</div>
			<div class="cgs-ic-help-block">
				<h3>ریسک نکول اقساط</h3>
				<p>امتیاز ۰–۱۰۰ از روی مدت، فاصله گام، نسبت بازپرداخت به اعتبار، عبور از آستانه ضامن و نرخ مؤثر ساخته می‌شود. سطح کم / متوسط / بالا فقط هشدار مدیریتی است، نه رد خودکار.</p>
			</div>
		</div>
	</div>
</div>
<style>
.cgs-ic-admin{max-width:1100px}
.cgs-ic-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}
.cgs-ic-tab{border:1px solid #c7d2fe;background:#eef2ff;color:#1e3a8a;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer}
.cgs-ic-tab.is-active{background:#1e3a8a;color:#fff}
.cgs-ic-panel[hidden]{display:none!important}
.cgs-ic-panel:not([hidden]){display:block!important}
.cgs-ic-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-bottom:14px}
.cgs-ic-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cgs-ic-form-grid label{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700;color:#475569}
.cgs-ic-form-grid input,.cgs-ic-form-grid select{padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-weight:400}
.cgs-ic-result[hidden]{display:none!important}
.cgs-ic-result{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:14px}
.cgs-ic-result .box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 14px 12px;position:relative;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.cgs-ic-result .box::before{content:'';position:absolute;top:0;right:0;left:0;height:4px;background:#cbd5e1}
.cgs-ic-result .box span{display:block;font-size:11px;color:#64748b;margin-bottom:6px;font-weight:600}
.cgs-ic-result .box strong{font-size:1.05rem;color:#0f172a;font-variant-numeric:tabular-nums}
.cgs-ic-result .box.hi-credit::before{background:linear-gradient(90deg,#059669,#34d399)}
.cgs-ic-result .box.hi-credit{background:#ecfdf5;border-color:#a7f3d0}
.cgs-ic-result .box.hi-pi::before{background:linear-gradient(90deg,#2563eb,#60a5fa)}
.cgs-ic-result .box.hi-pi{background:#eff6ff;border-color:#bfdbfe}
.cgs-ic-result .box.hi-s::before{background:linear-gradient(90deg,#d97706,#fbbf24)}
.cgs-ic-result .box.hi-s{background:#fffbeb;border-color:#fde68a}
.cgs-ic-result .box.hi-t::before{background:linear-gradient(90deg,#7c3aed,#a78bfa)}
.cgs-ic-result .box.hi-t{background:#f5f3ff;border-color:#ddd6fe}
.cgs-ic-result .box.hi-inst::before{background:linear-gradient(90deg,#dc2626,#fb7185)}
.cgs-ic-result .box.hi-inst{background:#fef2f2;border-color:#fecaca}
.cgs-ic-result .box.hi-ded::before{background:linear-gradient(90deg,#475569,#94a3b8)}
.cgs-ic-sec-title{grid-column:1/-1;margin:8px 0 0;padding:8px 12px;border-radius:10px;font-weight:800;font-size:13px;color:#fff}
.cgs-ic-sec-title.s1{background:linear-gradient(90deg,#1e3a8a,#3b82f6)}
.cgs-ic-sec-title.s2{background:linear-gradient(90deg,#065f46,#10b981)}
.cgs-ic-sec-title.s3{background:linear-gradient(90deg,#9a3412,#f59e0b)}
.cgs-ic-sec-title.s4{background:linear-gradient(90deg,#4c1d95,#8b5cf6)}
.cgs-ic-compare{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px}
.cgs-ic-compare .col{border-radius:14px;padding:14px;border:1px solid #e2e8f0}
.cgs-ic-compare .col.lease{background:#f0fdf4;border-color:#86efac}
.cgs-ic-compare .col.bank{background:#f8fafc;border-color:#cbd5e1}
.cgs-ic-compare h4{margin:0 0 10px;font-size:14px}
.cgs-ic-compare table{width:100%;border-collapse:collapse;font-size:12px}
.cgs-ic-compare td{padding:6px 4px;border-bottom:1px solid #e2e8f0}
.cgs-ic-compare td:last-child{text-align:left;font-weight:700}
#ic-chart-wrap h3{margin:0 0 6px}
#ic-chart-wrap .hint{font-size:12px;color:#64748b;margin:0 0 10px}
.cgs-ic-plan-card{border:2px solid #e2e8f0;border-radius:12px;padding:12px;margin-top:12px;background:#fafbff}
.cgs-ic-durs{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.cgs-ic-durs th,.cgs-ic-durs td{border:1px solid #e2e8f0;padding:6px;text-align:center}
.cgs-ic-durs input{width:100%;max-width:88px;padding:4px;border:1px solid #cbd5e1;border-radius:6px}
.cgs-ic-phone{max-width:420px;margin:0 auto;border:3px solid #1e293b;border-radius:28px;overflow:hidden}
.cgs-ic-phone-bar{background:#1e293b;color:#fff;text-align:center;padding:10px;font-weight:700}
.cgs-ic-phone-body{padding:14px;max-height:620px;overflow:auto}
.cgs-ic-formula{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;font-size:12px;line-height:1.7}
.cgs-ic-help-block{margin:12px 0;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0}
.cgs-ic-flags{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;font-size:12px}
@media(max-width:700px){.cgs-ic-form-grid{grid-template-columns:1fr}}

.cgs-ic-modal[hidden]{display:none!important}
.cgs-ic-modal{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px}
.cgs-ic-modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px)}
.cgs-ic-modal-dialog{position:relative;background:#fff;border-radius:18px;width:min(920px,96vw);max-height:90vh;overflow:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);border:1px solid #e2e8f0}
.cgs-ic-modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(90deg,#1e3a8a,#3b82f6);color:#fff;border-radius:18px 18px 0 0}
.cgs-ic-modal-head strong{font-size:16px}
.cgs-ic-modal-close{background:rgba(255,255,255,.2);border:0;color:#fff;width:36px;height:36px;border-radius:10px;font-size:22px;cursor:pointer;line-height:1}
.cgs-ic-modal-body{padding:16px 18px}
.cgs-ic-modal-foot{padding:12px 18px 16px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px}
.cgs-ic-modal .cgs-ic-result{margin-top:0}


.cgs-ic-box{border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin:12px 0;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.cgs-ic-box-title{font-weight:800;font-size:13px;color:#1e3a8a;margin:0 0 10px;padding-bottom:8px;border-bottom:2px solid #dbeafe}
.cgs-ic-box-manisa .cgs-ic-box-title{color:#7c2d12;border-color:#fed7aa}
.cgs-ic-box-salary .cgs-ic-box-title{color:#065f46;border-color:#a7f3d0}
.cgs-ic-box-agent .cgs-ic-box-title{color:#5b21b6;border-color:#ddd6fe}
.cgs-ic-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px 14px}

/* ic-ui-fix */
#ic-plan, #ic-months, #ic-step, #ic-method, #ic-agent-mode, #ic-salary-org, #ic-emp-status {
  pointer-events: auto !important; opacity: 1 !important; background: #fff !important;
}
#ic-run, .button-hero { pointer-events: auto !important; opacity: 1 !important; cursor: pointer !important; }
.cgs-ic-box select, .cgs-ic-box input { min-height: 36px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function($){
	window.cgsSalaryOrgs=<?php echo wp_json_encode( class_exists('CGS_Installment_Calculator') ? CGS_Installment_Calculator::salary_organizations() : array('tamin'=>'تامین اجتماعی','armed'=>'نیروهای مسلح') ); ?>;
	window.cgsEmpStatus=<?php echo wp_json_encode( class_exists('CGS_Installment_Calculator') ? CGS_Installment_Calculator::employment_statuses() : array('employed'=>'شاغل','retired'=>'بازنشسته','pensioner'=>'مستمری‌بگیر') ); ?>;
	var planCategories=<?php echo wp_json_encode( class_exists('CGS_Installment_Calculator') ? CGS_Installment_Calculator::plan_categories() : array() ); ?>;
	var methodsByCategory=<?php echo wp_json_encode( array(
		'salary_auto' => array('tamin_social','flat_coef','flat_principal_fee'),
		'check' => array('manisa_digital','flat_coef','razi_leasing'),
		'self_deposit' => array('flat_coef','flat_principal_fee','bank'),
		'supplier_deposit' => array('flat_coef','flat_principal_fee','bank','razi_leasing'),
	) ); ?>;
	var ajax=<?php echo wp_json_encode( $ajax ); ?>, nonceAdmin=<?php echo wp_json_encode( $nonce_admin ); ?>, nonceCalc=<?php echo wp_json_encode( $nonce_calc ); ?>;
	var plans=<?php echo wp_json_encode( array_values( $plans ) ); ?>;
	var fieldLabels=<?php echo wp_json_encode( $field_labels ); ?>;
	var methods=<?php echo wp_json_encode( $methods ); ?>;
	var chartInst=null;
	function numMode(){ return ($('input[name="ic-num-format"]:checked').val()||'fa'); }
	function fmt(n){
				n = Number(n||0);
				if (!isFinite(n)) return '—';
				var neg = n < 0; n = Math.abs(Math.round(n));
				var s = String(n);
				var sep = numMode()==='en' ? ',' : '/';
				s = s.replace(/\B(?=(\d{3})+(?!\d))/g, sep);
				if (numMode()==='fa') {
					var en='0123456789', fa='۰۱۲۳۴۵۶۷۸۹';
					s = s.replace(/[0-9]/g, function(d){ return fa[en.indexOf(d)]; });
				}
				return (neg?'-':'') + s;
			}
	function onlyNum(v){
				v = String(v||'');
				var map={'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
				v = v.replace(/[۰-۹٠-٩]/g, function(c){ return map[c]||c; });
				return v.replace(/[^\d.]/g,'');
			}
	function formatMoneyInput($el){
				var raw = onlyNum($el.val());
				if(!raw){ $el.val(''); return; }
				var n = Number(raw); if(!isFinite(n)){ $el.val(''); return; }
				$el.val(fmt(n));
			}
	function bindMoneyInputs(){
				var sels = '#ic-principal,#dc-principal,#ic-compare-principal,#ic-digital';
				$(document).on('blur', sels, function(){ formatMoneyInput($(this)); });
				$(document).on('focus', sels, function(){
					var v = onlyNum($(this).val());
					$(this).val(v||'');
				});
				$('input[name="ic-num-format"]').on('change', function(){
					['#ic-principal','#dc-principal','#ic-compare-principal'].forEach(function(s){
						var $e=$(s); if(onlyNum($e.val())) formatMoneyInput($e);
					});
					if($('#ic-result').is(':visible') && onlyNum($('#ic-principal').val())) $('#ic-run').trigger('click');
				});
			}
	bindMoneyInputs();

	function icFillSalarySelects(){
		var $o=$('#ic-salary-org').empty();
		Object.keys(window.cgsSalaryOrgs||{}).forEach(function(k){
			$o.append($('<option>').val(k).text(cgsSalaryOrgs[k]));
		});
		var $s=$('#ic-emp-status').empty();
		Object.keys(window.cgsEmpStatus||{}).forEach(function(k){
			$s.append($('<option>').val(k).text(cgsEmpStatus[k]));
		});
	}
	icFillSalarySelects();


	function icPlayWrong(){
		try {
			var ctx = window.__cgsAudioCtx || (window.__cgsAudioCtx = new (window.AudioContext||window.webkitAudioContext)());
			var o = ctx.createOscillator(); var g = ctx.createGain();
			o.type='square'; o.frequency.value=180;
			g.gain.value=0.08;
			o.connect(g); g.connect(ctx.destination);
			o.start();
			setTimeout(function(){ o.frequency.value=120; }, 120);
			setTimeout(function(){ o.stop(); }, 280);
		} catch(e) {}
	}
	function icCheckFloorCeiling(){
		var p = onlyNum($('#ic-principal').val())||0;
		var $opt = $('#ic-plan option:selected');
		var floor = parseFloat($opt.attr('data-floor')||0)||0;
		var ceil = parseFloat($opt.attr('data-ceiling')||0)||0;
		var msg = '';
		if(floor>0 && p>0 && p<floor){
			msg = '⚠ مبلغ از کف اعتبار کمتر است. کف مجاز: '+fmt(floor)+' ریال | سقف: '+(ceil?fmt(ceil):'—')+' ریال';
		} else if(ceil>0 && p>ceil){
			msg = '⚠ مبلغ از سقف اعتبار بیشتر است. سقف مجاز: '+fmt(ceil)+' ریال | کف: '+(floor?fmt(floor):'—')+' ریال';
		}
		var $w = $('#ic-amount-warn');
		if(!$w.length){
			$w = $('<div id="ic-amount-warn" style="display:none;margin:10px 0;padding:12px 14px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-weight:700"></div>');
			$('#ic-principal').closest('label').after($w);
		}
		if(msg){ $w.html(msg).show(); icPlayWrong(); return false; }
		$w.hide().empty(); return true;
	}


	function icUpdateDigitalRial(){
		var p = onlyNum($('#ic-principal').val()) || 0;
		var pct = parseFloat($('#ic-infra-pct').val())||parseFloat($('#ic-supplier').val())||0;
		var rial = Math.round(p * pct / 100);
		$('#ic-digital-auto').val(rial);
		$('#ic-digital').val(rial);
		// plans cards
		$('.cgs-ic-plan-card').each(function(){
			var $c=$(this);
			var sp=parseFloat($c.find('.ic-sup').val())||0;
			// for plan card we don't have principal context — leave 0 or use data
			$c.find('.ic-dig-auto').val('');
		});
	}
	$(document).on('input change', '#ic-principal, #ic-supplier', function(){ icUpdateDigitalRial(); icCheckFloorCeiling(); });
	$(document).on('input change', '.ic-sup', function(){
		// show hint only; rial needs principal at calc time
		$(this).closest('.cgs-ic-plan-card').find('.ic-dig-auto').attr('placeholder','در ماشین‌حساب با اصل محاسبه می‌شود');
	});


	function icOpenModal(title, html){
		$('#cgs-ic-modal-title').text(title||'نتیجه');
		$('#cgs-ic-modal-body').html(html);
		$('#cgs-ic-modal').removeAttr('hidden');
	}
	function icCloseModal(){ $('#cgs-ic-modal').attr('hidden', true); }
	$(document).on('click', '.cgs-ic-modal-close, .cgs-ic-modal-backdrop', icCloseModal);
	$(document).on('keydown', function(e){ if(e.key==='Escape') icCloseModal(); });

	function icBox(label, value, cls){
		return '<div class="box '+(cls||'')+'"><span>'+label+'</span><strong>'+(value==null||value===''?'—':value)+'</strong></div>';
	}
	function icPct(x, dig){
		if(x==null||x==='') return '—';
		return Number(x).toFixed(dig==null?7:dig);
	}
	function icRenderPayslip(d){
		if(!d.payslip_info) return '';
		var p=d.payslip_info;
		var h='<div class="cgs-ic-result" style="margin-top:12px"><div class="cgs-ic-sec-title s2">سقف قدرت خرید (فیش حقوقی)</div>';
		h+=icBox('حقوق خالص', fmt(p.net_salary));
		h+=icBox('درصد قابل کسر', (p.payslip_pct||0)+'٪');
		h+=icBox('حداکثر قسط ماهانه', fmt(p.max_monthly), 'hi-inst');
		h+=icBox('سقف قدرت خرید / اصل', fmt(p.max_principal), 'hi-credit');
		h+='</div>';
		return h;
	}
	function icRenderKeyResults(d){
		var h = '<div class="cgs-ic-result">';
		h += '<div class="cgs-ic-sec-title s1">نتایج کلیدی</div>';
		h += icBox('اصل مبلغ', fmt(d.principal), 'hi-pi');
		h += icBox('اعتبار تخصیص‌یافته (S)', fmt(d.purchasing_power||d.excel_S), 'hi-credit');
		h += icBox('اصل و فرع نهایی (H)', fmt(d.principal_interest||d.total_repay||d.excel_H), 'hi-pi');
		h += icBox('قسط ماهانه', fmt(d.period_installment||d.monthly_installment||d.installment), 'hi-inst');
		h += icBox('ضریب طرح ٪', icPct(d.plan_coef!=null?d.plan_coef:d.final_coef), 'hi-s');
		h += icBox('مدت (ماه)', String(d.months||'—'), 'hi-ded');
		h += '</div>';
		return h;
	}
	function icRenderDeductions(d){
		var h = '<div class="cgs-ic-result" style="margin-top:12px">';
		h += '<div class="cgs-ic-sec-title s2">کسورات یک‌باره</div>';
		h += icBox('کارمزد ثانویه', fmt(d.supplier_cut||0));
		h += icBox('سهم شهر قسط', fmt(d.city_share_gross||d.city_cut||0));
		h += icBox('جمع کسورات', fmt(d.total_deductions||0), 'hi-ded');
		h += icBox('واریزی به شهر قسط', fmt(d.supplier_deposit||0));
		h += icBox('چک تضمین', fmt(d.guarantee_check||0));
		h += '</div>';
		return h;
	}
	function icRenderCheckPolicy(d){
		if(!d.check_policy_messages||!d.check_policy_messages.length) return '';
		var h='<div class="cgs-ic-result" style="margin-top:12px"><div class="cgs-ic-sec-title s3">سیاست چک</div>';
		d.check_policy_messages.forEach(function(m){ h+=icBox('راهنما', m); });
		if(d.guarantee_check_applicant) h+=icBox('چک تضمین مشتری', fmt(d.guarantee_check_applicant), 'hi-s');
		if(d.guarantee_check_guarantor) h+=icBox('چک تضمین ضامن', fmt(d.guarantee_check_guarantor), 'hi-s');
		if(d.guarantee_check && !d.guarantee_check_applicant) h+=icBox('چک تضمین (جمع)', fmt(d.guarantee_check), 'hi-s');
		h+='</div>';
		return h;
	}
	function icRenderLogic(d){
		if(!d.logic_explanation||!d.logic_explanation.length) return '';
		return '<div class="cgs-ic-result" style="margin-top:12px"><div class="box" style="grid-column:1/-1"><span>منطق محاسبه</span><strong style="font-size:13px;font-weight:600;line-height:1.85">'+d.logic_explanation.map(function(x){return '• '+x;}).join('<br>')+'</strong></div></div>';
	}
	function icRenderFullResult(d){
		return icRenderKeyResults(d) + icRenderPayslip(d) + icRenderDeductions(d) + icRenderCheckPolicy(d) + icRenderLogic(d);
	}
	function icShowCalcResult(d){
		var html = icRenderFullResult(d);
		$('#ic-result').html(html).show().removeAttr('hidden');
		icOpenModal('نتیجه محاسبه اقساط — '+(d.plan_name||d.method||''), html);
		try {
			if(window.Chart && document.getElementById('ic-chart') && d.schedule && d.schedule.length){
				$('#ic-chart-wrap').show();
				var labels = d.schedule.map(function(r){ return 'قسط '+r.n; });
				var data = d.schedule.map(function(r){ return r.amount; });
				if(chartInst) chartInst.destroy();
				chartInst = new Chart(document.getElementById('ic-chart'), {
					type:'bar',
					data:{ labels:labels, datasets:[{ label:'مبلغ قسط', data:data, backgroundColor:'rgba(79,70,229,.75)', borderRadius:8 }] },
					options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
				});
			}
		} catch(e) { console.warn('chart', e); }
	}



	$('.cgs-ic-tab').on('click', function(){
		$('.cgs-ic-tab').removeClass('is-active'); $(this).addClass('is-active');
		$('.cgs-ic-panel').attr('hidden', true);
		$('#cgs-ic-panel-'+$(this).data('tab')).removeAttr('hidden');
	});

	function fillPlanSelect(){
		var $s=$('#ic-plan').empty();
		var list = (plans||[]).filter(function(pl){ return pl && (pl.active===1 || pl.active===true || pl.active==='1' || pl.active==null); });
		if(!list.length && plans && plans.length){ list = plans.slice(); } // اگر همه غیرفعال بودند، همه را نشان بده
		if(!list.length){
			$s.append($('<option>').val('').text('— طرحی تعریف نشده —'));
			$('#ic-months').html('<option value="12">12 ماه</option>');
			$('#ic-step').html('<option value="1">هر ماه</option>');
			$('#ic-run').prop('disabled', false).text('محاسبه');
			return;
		}
		list.forEach(function(pl){
			var dursJson = JSON.stringify(pl.durations||[{months:12,coef:0,steps:[1]}]);
			$s.append($('<option>').val(pl.id).text(pl.name||pl.id)
				.attr('data-durs', dursJson)
				.attr('data-durations', dursJson)
				.attr('data-method', pl.method||'tamin_social')
				.attr('data-supplier', pl.supplier_coef||0)
				.attr('data-city', pl.city_coef||0)
				.attr('data-digital', pl.digital_fee||0)
				.attr('data-primary', pl.primary_monthly_rate||0)
				.attr('data-infra', pl.digital_infra_pct||pl.supplier_coef||0)
				.attr('data-sec-chain', pl.secondary_chain_pct||6.6).attr('data-sec-base', pl.secondary_base_on||'primary_deposit')
				.attr('data-city-chain', pl.city_chain_pct||6.6)
				.attr('data-agent', pl.agent_coef||0)
				.attr('data-agent-mode', pl.agent_mode||'from_city')
				.attr('data-sup-fixed', pl.supplier_fixed_fee||0)
				.attr('data-city-fixed', pl.city_fixed_fee||0)
				.attr('data-floor', pl.cash_floor||0)
				.attr('data-ceiling', pl.cash_ceiling||0)
				.attr('data-category', pl.plan_category||'salary_auto'));
		});
		syncCalcPlan();
		$('#ic-run').prop('disabled', false).text('محاسبه');
	}
	function syncCalcPlan(){
		var $o=$('#ic-plan option:selected');
		if(!$o.length || !$o.val()){
			$('#ic-months').html('<option value="12">12 ماه</option>');
			$('#ic-step').html('<option value="1">هر ماه</option>');
			$('#ic-run').prop('disabled', false).text('محاسبه');
			return;
		}
		var method = $o.attr('data-method') || 'tamin_social';
		var cat = $o.attr('data-category') || 'salary_auto';
		$('#ic-method').val(method);

		// فقط فیلدهای همان طرح
		var isManisa = (method === 'manisa_digital' || method === 'razi_leasing' || method === 'flat_coef');
		var isSalary = (method === 'tamin_social' || cat === 'salary_auto');
		$('#ic-box-manisa').toggle(isManisa);
		$('#ic-box-salary').toggle(isSalary && !isManisa);
		$('#ic-box-agent').show();

		$('#ic-supplier').val($o.attr('data-supplier')||0);
		$('#ic-city').val($o.attr('data-city')||0);
		$('#ic-digital').val($o.attr('data-digital')||0);
		$('#ic-primary').val($o.attr('data-primary')||0);
		$('#ic-sec-chain-ro').val($o.attr('data-sec-chain')||6.6);
		$('#ic-city-chain-ro').val($o.attr('data-city-chain')||6.6);
		$('#ic-agent').val($o.attr('data-agent')||0);
		if($o.attr('data-agent-mode')) $('#ic-agent-mode').val($o.attr('data-agent-mode'));

		var durs=[];
		try { durs = JSON.parse($o.attr('data-durs')||$o.attr('data-durations')||'[]'); } catch(e){ durs=[]; }
		$('#ic-months').empty();
		(durs.length?durs:[{months:12,coef:0,steps:[1]}]).forEach(function(d){
			$('#ic-months').append($('<option>').val(d.months).text(d.months+' ماه')
				.attr('data-coef', d.coef||0)
				.attr('data-steps', JSON.stringify(d.steps||[1]))
				.attr('data-infra', d.primary_pct!=null?d.primary_pct:(d.infra_pct!=null?d.infra_pct:''))
				.attr('data-primary', d.primary_monthly_rate!=null?d.primary_monthly_rate:''));
		});
		syncMonthsFields();
		icUpdateDigitalRial();
		icCheckFloorCeiling();
	}
	function syncMonthsFields(){
		var $m = $('#ic-months option:selected');
		var coef = $m.attr('data-coef');
		$('#ic-coef').val(coef!=null&&coef!==''?coef:'');
		var infra = $m.attr('data-infra');
		if (infra!=='' && infra!=null) $('#ic-infra-pct').val(infra);
		else {
			// fallback plan-level
			var $o=$('#ic-plan option:selected');
			$('#ic-infra-pct').val($o.attr('data-infra')||$o.attr('data-supplier')||14);
		}
		var prim = $m.attr('data-primary');
		if (prim!=='' && prim!=null) $('#ic-primary').val(prim);
		syncSteps();
		icUpdateDigitalRial();
	}
	function syncSteps(){

		var $o=$('#ic-months option:selected'); var steps=[1];
		try{ steps=JSON.parse($o.attr('data-steps')||'[1]'); }catch(e){}
		$('#ic-step').empty();
		steps.forEach(function(s){ $('#ic-step').append($('<option>').val(s).text(s===1?'هر ماه':('هر '+s+' ماه'))); });
		$('#ic-coef').val($o.attr('data-coef')||0);
	}
	$('#ic-plan').on('change', syncCalcPlan);
	$('#ic-months').on('change', syncMonthsFields);

			$('#ic-run').on('click', function(){
		var principal = onlyNum($('#ic-principal').val());
		icCheckFloorCeiling();
		if(!principal || Number(principal)<=0){
			alert('لطفاً اصل مبلغ را وارد کنید.');
			$('#ic-principal').focus();
			return;
		}
		var $btn = $(this).prop('disabled', true).text('در حال محاسبه…');
		$.post(ajax, {
			action:'cgs_calc_installment', nonce:nonceCalc,
			principal: principal,
			plan_id: $('#ic-plan').val(),
			months: $('#ic-months').val()||12,
			step: $('#ic-step').val()||1,
			coef: $('#ic-coef').val()||0,
			method: $('#ic-method').val(),
			supplier_coef: $('#ic-supplier').val()||0,
			digital_infra_pct: $('#ic-infra-pct').val()||0,
			primary_pct: $('#ic-infra-pct').val()||0,
			secondary_chain_pct: $('#ic-sec-chain-ro').val()||6.6,
			secondary_base_on: $('#ic-plan option:selected').attr('data-sec-base')||'primary_deposit',
			city_chain_pct: $('#ic-city-chain-ro').val()||6.6,
			city_coef: $('#ic-city').val()||0,
			agent_coef: $('#ic-agent').val()||0,
			agent_mode: $('#ic-agent-mode').val()||'from_city',
			digital_fee: onlyNum($('#ic-digital').val())||0,
			primary_monthly_rate: $('#ic-primary').val()||0,
			net_salary: onlyNum($('#ic-net-salary').val())||0,
			salary_org: $('#ic-salary-org').val()||'',
			employment_status: $('#ic-emp-status').val()||'',
			supplier_fixed_fee: $('#ic-plan option:selected').attr('data-sup-fixed')||0,
			city_fixed_fee: $('#ic-plan option:selected').attr('data-city-fixed')||0
		}).done(function(res){
			$btn.prop('disabled', false).text('محاسبه');
			if(!res || res.success===false){
				var msg = (res && res.data) ? (typeof res.data==='string'?res.data:JSON.stringify(res.data)) : 'خطا در محاسبه';
				alert(msg);
				console.error('calc error', res);
				return;
			}
			var d0 = res.data || {};
			if(d0.amount_warning || (d0.warnings && d0.warnings.length)){
				icPlayWrong();
				var wm = d0.amount_warning || (d0.warnings||[]).join(' | ');
				alert(wm);
			}
			icShowCalcResult(d0);
			$.post(ajax, {
				action:'cgs_calc_bank_all', nonce:nonceAdmin,
				principal: principal,
				months: $('#ic-months').val()||12,
				step: $('#ic-step').val()||1,
				primary_monthly_rate: $('#ic-primary').val()
			}).done(function(br){
				if(!br||!br.success||!br.data||!br.data.length){ $('#ic-bank-all').hide(); return; }
				var $tb = $('#ic-bank-all-table tbody').empty();
				br.data.forEach(function(r){
					$tb.append('<tr><td>'+r.plan+'</td><td>'+r.method+'</td><td>'+r.months+'</td><td>'+fmt(r.plan_installment)+'</td><td>'+fmt(r.plan_total)+'</td><td>'+fmt(r.bank_installment)+'</td><td>'+fmt(r.bank_total)+'</td><td>'+fmt(r.diff_installment)+'</td><td>'+fmt(r.diff_total)+'</td><td>'+(r.monthly_coef!=null?Number(r.monthly_coef).toFixed(7):'—')+'</td></tr>');
				});
				$('#ic-bank-all').show();
			}).fail(function(){ $('#ic-bank-all').hide(); });
		}).fail(function(xhr){
			$btn.prop('disabled', false).text('محاسبه');
			var msg = 'خطای ارتباط با سرور';
			try { if(xhr.responseJSON && xhr.responseJSON.data) msg += ': '+xhr.responseJSON.data; } catch(e){}
			alert(msg + ' ('+(xhr.status||'?')+')');
			console.error(xhr);
		});
	});

$('#dc-run').on('click', function(){
		$.post(ajax,{
			action:'cgs_discover_coef', nonce:nonceAdmin,
			principal:onlyNum($('#dc-principal').val()),
			supplier_deposit:onlyNum($('#dc-deposit').val()),
			months:$('#dc-months').val(),
			total_repay:onlyNum($('#dc-total').val())
		}).done(function(res){
			if(!res||!res.success) return;
			var d=res.data, html='';
			function box(l,v){ return '<div class="box"><span>'+l+'</span><strong>'+v+'</strong></div>'; }
			html+=box('اصل', fmt(d.principal));
			html+=box('واریزی تامین‌کننده', fmt(d.supplier_deposit));
			html+=box('کارمزد ثانویه (ریال)', fmt(d.secondary_fee));
			html+=box('کارمزد ثانویه %', fmt(d.secondary_fee_pct));
			html+=box('نرخ ماهانه اولیه (اعشار)', d.primary_monthly_rate!=null?d.primary_monthly_rate:'—');
			html+=box('نرخ ماهانه اولیه %', d.primary_monthly_pct!=null?fmt(d.primary_monthly_pct):'—');
			html+=box('ضریب کل (اصل‌وفرع/اصل)', d.implied_total_factor!=null?d.implied_total_factor:'—');
			html+='<div class="box" style="grid-column:1/-1"><span>توضیح</span><strong style="font-size:13px;font-weight:600">'+d.note+'</strong></div>';
			$('#dc-result').html(html).removeAttr('hidden');
			if(d.primary_monthly_rate!=null) $('#ic-primary').val(d.primary_monthly_rate);
			if(d.secondary_fee_pct) $('#ic-supplier').val(d.secondary_fee_pct);
		});
	});

	function methodOptions(sel, cat){
		var h='';
		var allow = (cat && methodsByCategory[cat]) ? methodsByCategory[cat] : Object.keys(methods);
		Object.keys(methods).forEach(function(k){
			if(allow.indexOf(k)===-1) return;
			h+='<option value="'+k+'"'+(sel===k?' selected':'')+'>'+methods[k]+'</option>';
		});
		return h;
	}
	$(document).on('change', '.ic-plan-cat', function(){
		var cat=$(this).val();
		var $card=$(this).closest('.cgs-ic-plan-card');
		var cur=$card.find('.ic-method-plan').val();
		$card.find('.ic-method-plan').html(methodOptions(cur, cat));
		// مانیسا: مخفی کردن نرخ اولیه
		toggleManisaFields($card);
	});
	function toggleManisaFields($card){
		var m=$card.find('.ic-method-plan').val();
		var $prim=$card.find('.ic-primary-p').closest('label');
		if(m==='manisa_digital'){ $prim.hide(); }
		else { $prim.show(); }
	}
	$(document).on('change', '.ic-method-plan', function(){
		toggleManisaFields($(this).closest('.cgs-ic-plan-card'));
	});
	function durRow(d){
		d=d||{months:12,coef:0,steps:[1],primary_pct:''};
		var steps=(d.steps||[1]).join(',');
		return $('<tr>').html(
			'<td><input type="number" class="ic-d-months" value="'+(d.months||12)+'" title="مدت ماه"></td>'+
			'<td><input type="number" step="0.01" class="ic-d-coef" value="'+(d.coef||0)+'" title="ضریب سود نهایی مشتری ٪"></td>'+
			'<td><input type="text" class="ic-d-steps" value="'+steps+'" style="max-width:90px"></td>'+
			'<td><input type="number" step="0.01" class="ic-d-infra" value="'+(d.primary_pct!=null&&d.primary_pct!==''?d.primary_pct:(d.infra_pct||''))+'" placeholder="مثلاً 14" title="کسر تامین اولیه / زیرساخت ٪ — برای هر مدت جدا"></td>'+
			'<td><input type="number" step="0.0001" class="ic-d-primary" value="'+(d.primary_monthly_rate!=null&&d.primary_monthly_rate!==''?d.primary_monthly_rate:'')+'" placeholder="تامین" title="فقط کسر از حقوق"></td>'+
			'<td><button type="button" class="button ic-del-dur">×</button></td>'
		);
	}
	function esc(s){ return String(s||'').replace(/"/g,'&quot;'); }
	function renderPlans(){
		var $list=$('#ic-plans-list').empty();
		plans.forEach(function(pl,idx){
			var $c=$('<div class="cgs-ic-plan-card"></div>');
			$c.append('<div class="cgs-ic-acc-head"><button type="button" class="cgs-ic-acc-toggle" title="باز/بسته">▶</button> <strong class="cgs-ic-acc-title">'+esc(pl.name||('طرح '+(idx+1)))+'</strong> <input class="ic-name regular-text" value="'+esc(pl.name)+'" style="max-width:220px"> <label><input type="checkbox" class="ic-active" '+(pl.active?'checked':'')+'> فعال</label> <button type="button" class="button ic-del-plan" data-idx="'+idx+'">حذف</button></div>');
			var b='<div class="cgs-ic-acc-body" style="display:none"><div class="cgs-ic-form-grid">';
			b+='<label>شناسه<input class="ic-id" value="'+esc(pl.id)+'"></label>';
			b+='<label>نوع طرح<select class="ic-plan-cat">';
			Object.keys(planCategories).forEach(function(k){
				b+='<option value="'+k+'"'+((pl.plan_category||'salary_auto')===k?' selected':'')+'>'+planCategories[k]+'</option>';
			});
			b+='</select></label>';
			b+='<label>روش محاسبه<select class="ic-method-plan">'+methodOptions(pl.method, pl.plan_category||'salary_auto')+'</select></label>';
			b+='<label style="grid-column:1/-1;background:#eff6ff;padding:8px;border-radius:8px;font-weight:800;color:#1e3a8a">ضرایب و کارمزدها</label>';
			/* تامین / کسر از حقوق */
			b+='<label class="ic-show-tamin">۱) ضریب سود ماهانه تامین‌کننده اولیه (٪ از اصل)<input type="number" step="0.0001" class="ic-primary-p" value="'+(pl.primary_monthly_rate||0)+'"></label>';
			b+='<label class="ic-show-tamin">۲) ضریب کسر تامین‌کننده ثانویه (٪)<input type="number" step="0.01" class="ic-sup" value="'+(pl.supplier_coef||0)+'"></label>';
			b+='<label class="ic-show-tamin">پایه محاسبه کسر ثانویه<select class="ic-sec-base"><option value="principal"'+(pl.secondary_base_on!=='primary_deposit'?' selected':'')+'>از اصل مبلغ</option><option value="primary_deposit"'+(pl.secondary_base_on==='primary_deposit'?' selected':'')+'>از واریزی تامین‌کننده اولیه</option></select></label>';
			b+='<label class="ic-show-tamin">۳) ضریب سود شهر قسط (٪)<input type="number" step="0.01" class="ic-city-p" value="'+(pl.city_coef||0)+'"></label>';
			b+='<label class="ic-show-tamin">پایه محاسبه ضریب شهر قسط<select class="ic-city-base"><option value="principal"'+(pl.city_base_on!=='supplier_deposit'?' selected':'')+'>از اصل مبلغ</option><option value="supplier_deposit"'+(pl.city_base_on==='supplier_deposit'?' selected':'')+'>از واریزی ثانویه</option></select></label>';
			/* مانیسا / چکی زنجیره‌ای */
			b+='<label class="ic-show-manisa">هزینه زیرساخت دیجیتال — کسر تامین‌کننده اولیه (٪ از اصل)<input type="number" step="0.01" class="ic-infra-pct" value="'+(pl.digital_infra_pct!=null?pl.digital_infra_pct:(pl.supplier_coef||14))+'" title="درصدی از اصل؛ همان کسورات تامین‌کننده اولیه"></label>';
			b+='<label class="ic-show-manisa">هزینه زیرساخت (ریال) — خودکار و فقط‌خواندنی<input type="number" class="ic-dig-auto" value="0" readonly style="background:#f1f5f9;cursor:not-allowed"></label>';
			b+='<label class="ic-show-manisa">ضریب کسر تامین‌کننده ثانویه (٪)<input type="number" step="0.01" class="ic-sec-chain" value="'+(pl.secondary_chain_pct!=null?pl.secondary_chain_pct:6.6)+'"></label>';
			b+='<label class="ic-show-manisa">پایه محاسبه کسر ثانویه<select class="ic-sec-base"><option value="primary_deposit"'+(pl.secondary_base_on!=='principal'?' selected':'')+'>از واریزی تامین‌کننده اولیه</option><option value="principal"'+(pl.secondary_base_on==='principal'?' selected':'')+'>از اصل مبلغ</option></select></label>';
			b+='<label class="ic-show-manisa">کارمزد شهر قسط (٪ از مبلغ واریزی ثانویه)<input type="number" step="0.01" class="ic-city-chain" value="'+(pl.city_chain_pct!=null?pl.city_chain_pct:6.6)+'" title="باقیمانده پس از این کسر = اعتبار مشتری"></label>';
			b+='<label>درصد چک تضمین مشتری (از اصل‌وفرع)<input type="number" step="0.01" class="ic-g-app" value="'+(pl.guarantee_pct_applicant!=null?pl.guarantee_pct_applicant:(pl.guarantee_pct||120))+'"></label>';
			b+='<label>درصد چک تضمین ضامن (از اصل‌وفرع)<input type="number" step="0.01" class="ic-g-gua" value="'+(pl.guarantee_pct_guarantor||0)+'"></label>';
			b+='<label style="grid-column:1/-1;background:#e0e7ff;padding:8px;border-radius:8px;font-weight:800;color:#3730a3">کسر از حقوق — فیش و واجدین شرایط</label>';
			b+='<label>درصد از حقوق خالص قابل کسر (سقف قدرت خرید)<input type="number" step="0.1" class="ic-payslip-pct" value="'+(pl.payslip_net_pct||0)+'" title="مثلاً ۴۰ یعنی حداکثر ۴۰٪ حقوق خالص"></label>';
			b+='<label>منبع سقف<select class="ic-ceiling-src"><option value="admin"'+(pl.ceiling_source==='admin'?' selected':'')+'>ادمین</option><option value="supplier"'+(pl.ceiling_source==='supplier'?' selected':'')+'>تامین‌کننده</option><option value="payslip"'+(pl.ceiling_source==='payslip'?' selected':'')+'>فیش حقوقی</option></select></label>';
			b+='<label style="grid-column:1/-1">سازمان‌های مجاز (کسر از حقوق)<div class="ic-orgs" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px">';
			var orgs = window.cgsSalaryOrgs || {tamin:'تامین اجتماعی',armed:'نیروهای مسلح',other:'سایر'};
			var eligO = pl.eligible_orgs || Object.keys(orgs);
			Object.keys(orgs).forEach(function(k){
				b+='<label style="font-weight:600"><input type="checkbox" class="ic-elig-org" value="'+k+'" '+(eligO.indexOf(k)>=0?'checked':'')+'> '+orgs[k]+'</label>';
			});
			b+='</div></label>';
			b+='<label style="grid-column:1/-1">وضعیت شغلی مجاز<div class="ic-stats" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px">';
			var sts = window.cgsEmpStatus || {employed:'شاغل',retired:'بازنشسته',pensioner:'مستمری‌بگیر'};
			var eligS = pl.eligible_statuses || Object.keys(sts);
			Object.keys(sts).forEach(function(k){
				b+='<label style="font-weight:600"><input type="checkbox" class="ic-elig-st" value="'+k+'" '+(eligS.indexOf(k)>=0?'checked':'')+'> '+sts[k]+'</label>';
			});
			b+='</div></label>';
			b+='<label>پیش‌پرداخت نقدی ثانویه ٪ (رازی)<input type="number" step="0.1" class="ic-prepay-pct" value="'+(pl.prepay_pct||pl.secondary_chain_pct||6.6)+'" title="سهم ثانویه که نقداً از متقاضی گرفته می‌شود"></label>';

			b+='<input type="hidden" class="ic-sup-fixed" value="'+(pl.supplier_fixed_fee||0)+'">';
			b+='<input type="hidden" class="ic-city-fixed" value="'+(pl.city_fixed_fee||0)+'">';
			b+='<label>نماینده %<input type="number" step="0.01" class="ic-agent-p" value="'+(pl.agent_coef||0)+'"></label>';
			b+='<label>محل سهم نماینده<select class="ic-agent-mode-p"><option value="from_city"'+(pl.agent_mode!=='from_credit'?' selected':'')+'>از سهم شهر قسط</option><option value="from_credit"'+(pl.agent_mode==='from_credit'?' selected':'')+'>از اعتبار مشتری</option></select></label>';
			b+='<label>سقف مبلغ (ریال)<input type="number" class="ic-ceiling" value="'+(pl.cash_ceiling||0)+'"></label>';
			b+='<label>کف مبلغ (ریال) — ادمین<input type="number" class="ic-floor" value="'+(pl.cash_floor||0)+'"></label>';
			b+='<label>آستانه بدون ضامن (ریال)<input type="number" class="ic-guarantor" value="'+(pl.guarantor_threshold||0)+'" title="بالاتر از این مبلغ ضامن لازم است"></label>';
			b+='<label style="grid-column:1/-1;background:#fef3c7;padding:8px;border-radius:8px;font-weight:800;color:#92400e">سیاست چک تضمین / اقساط</label>';
			b+='<label><input type="checkbox" class="ic-need-g" '+(pl.need_guarantee_check==null||pl.need_guarantee_check?'checked':'')+'> نیاز به چک تضمین</label>';
			b+='<label>ضریب چک تضمین ٪ از اصل‌وفرع<input type="number" step="1" class="ic-g-pct" value="'+(pl.guarantee_pct||120)+'"></label>';
			b+='<label><input type="checkbox" class="ic-inst-checks" '+(pl.installment_checks?'checked':'')+'> چک اقساط (برای هر قسط)</label>';
			b+='<label><input type="checkbox" class="ic-app-gcheck" '+(pl.applicant_guarantee_check?'checked':'')+'> چک تضمین متقاضی</label>';
			b+='<label><input type="checkbox" class="ic-gua-gcheck" '+(pl.guarantor_guarantee_check?'checked':'')+'> چک تضمین ضامن</label>';
			b+='<label>منبع سقف<select class="ic-ceiling-src"><option value="admin"'+(pl.ceiling_source==='admin'?' selected':'')+'>ادمین</option><option value="supplier"'+(pl.ceiling_source==='supplier'?' selected':'')+'>تامین‌کننده / چک</option><option value="payslip"'+(pl.ceiling_source==='payslip'?' selected':'')+'>فیش حقوقی (کسر از حقوق)</option></select></label>';
			b+='<label>زیرساخت<input type="number" class="ic-dig" value="'+(pl.digital_fee||0)+'"></label>';
			
			b+='<label style="grid-column:1/-1;background:#ecfdf5;padding:8px;border-radius:8px;font-weight:800;color:#065f46">نمایش در پیش‌نمایش مشتری (فقط نمایش — روی محاسبه اثر ندارد)</label>';
			var pf = pl.preview_fields || pl.result_fields || {};
			var keys = [
				['principal','اصل مبلغ'],['purchasing_power','اعتبار تخصیص‌یافته'],['principal_interest','اصل‌وفرع'],
				['monthly_installment','قسط ماهانه'],['final_coef','ضریب طرح'],['total_deductions','جمع کسورات'],
				['guarantee_check','چک تضمین'],['supplier_cut','کارمزد ثانویه'],['city_cut','سهم شهر قسط'],
				['period_profit','سود طی دوره'],['cancel_penalty','جریمه انصراف']
			];
			keys.forEach(function(k){
				var on = pf[k[0]]==null ? 1 : (pf[k[0]]?1:0);
				b+='<label class="ic-pf-item"><input type="checkbox" class="ic-pf" data-key="'+k[0]+'" '+(on?'checked':'')+'> '+k[1]+'</label>';
			});
			b+='</div>';
			b+='<table class="cgs-ic-durs"><thead><tr><th>مدت</th><th>ضریب طرح ٪</th><th>گام‌ها</th><th>زیرساخت/کسر اولیه ٪</th><th>نرخ ماهانه تامین</th><th></th></tr></thead><tbody></tbody></table>';
			b+='<button type="button" class="button ic-add-dur">+ مدت</button>';
			b+='<div class="cgs-ic-flags">';
			var rf=pl.result_fields||{};
			Object.keys(fieldLabels).forEach(function(k){
				b+='<label><input type="checkbox" class="ic-rf" data-key="'+k+'" '+(rf[k]!==0?'checked':'')+'> '+fieldLabels[k]+'</label>';
			});
			b+='</div>';
			b+='<div style="margin-top:14px;padding-top:12px;border-top:1px dashed #cbd5e1;display:flex;gap:10px;align-items:center;flex-wrap:wrap">'
				+'<button type="button" class="button ic-save-one" data-idx="'+idx+'" style="background:linear-gradient(135deg,#059669,#10b981);border:none;color:#fff;font-weight:800;padding:8px 18px;border-radius:10px;box-shadow:0 2px 8px rgba(16,185,129,.35)">💾 ذخیره این طرح</button>'
				+'<span class="ic-save-one-msg" style="font-size:12px;color:#059669;font-weight:700"></span></div>';
			b+='</div>'; /* end acc-body */
			$c.append(b);
			(pl.durations||[]).forEach(function(d){ $c.find('tbody').append(durRow(d)); });
			togglePlanMethodFields($c);
			$list.append($c);
		});
	}

	function togglePlanMethodFields($card){
		var m = $card.find('.ic-method-plan').val() || '';
		var isManisa = (m === 'manisa_digital' || m === 'razi_leasing' || m === 'flat_coef' || (m&&(m.indexOf('check')>=0||m.indexOf('manisa')>=0)));
		$card.find('.ic-show-manisa').toggle(!!isManisa);
		$card.find('.ic-show-tamin').toggle(!isManisa);
	}
	$(document).on('click', '.cgs-ic-acc-toggle, .cgs-ic-acc-title', function(e){
		if ($(e.target).closest('input, .ic-del-plan, .ic-active').length) return;
		var $card = $(this).closest('.cgs-ic-plan-card');
		var $body = $card.find('.cgs-ic-acc-body');
		var open = $body.is(':visible');
		$body.slideToggle(180);
		$card.find('.cgs-ic-acc-toggle').text(open ? '▶' : '▼');
	});
	$(document).on('change', '.ic-method-plan', function(){
		togglePlanMethodFields($(this).closest('.cgs-ic-plan-card'));
	});
	$(document).on('click','.ic-add-dur',function(){ $(this).closest('.cgs-ic-plan-card').find('tbody').append(durRow()); });

	$(document).on('click','.ic-del-dur',function(){ $(this).closest('tr').remove(); });
	$(document).on('click','.ic-del-plan',function(){ if(confirm('حذف؟')){ plans.splice($(this).data('idx'),1); renderPlans(); fillPlanSelect(); }}); 
	$('#ic-sensitivity').on('click', function(){
		$.post(ajax, {
			action:'cgs_calc_sensitivity', nonce:nonceAdmin,
			principal: onlyNum($('#ic-principal').val()),
			plan_id: $('#ic-plan').val(), months:$('#ic-months').val(), step:$('#ic-step').val(),
			method:$('#ic-method').val(), coef:$('#ic-coef').val(),
			supplier_coef:$('#ic-supplier').val(), city_coef:$('#ic-city').val(),
			primary_monthly_rate:$('#ic-primary').val()
		}).done(function(res){
			if(!res||!res.success) return;
			var $tb=$('#ic-sens-table tbody').empty();
			(res.data.rows||[]).forEach(function(r){
				$tb.append('<tr><td>'+r.delta_pct+'٪</td><td>'+fmt(r.installment)+'</td><td>'+fmt(r.credit)+'</td><td>'+fmt(r.total_repay)+'</td><td>'+(r.effective_annual_rate!=null?fmt(r.effective_annual_rate):'—')+'</td></tr>');
			});
			$('#ic-sens-table').show();
		});
	});
	$('#ic-compare').on('click', function(){
		$.post(ajax,{action:'cgs_calc_compare',nonce:nonceAdmin,principal:onlyNum($('#ic-compare-principal').val())}).done(function(res){
			if(!res||!res.success) return;
			var $tb=$('#ic-compare-table tbody').empty();
			(res.data||[]).forEach(function(r){
				$tb.append('<tr><td>'+r.plan+'</td><td>'+r.method+'</td><td>'+r.months+'</td><td>'+r.step+'</td><td>'+fmt(r.installment)+'</td><td>'+fmt(r.credit)+'</td><td>'+fmt(r.total)+'</td><td>'+fmt(r.guarantee)+'</td><td>'+(r.effective!=null?fmt(r.effective):'—')+'</td><td>'+r.risk+'</td><td>'+r.guarantor+'</td></tr>');
			});
			$('#ic-compare-table').show();
		});
	});


	function collectPlans(){
		var out=[];
		$('#ic-plans-list .cgs-ic-plan-card').each(function(){
			var $c=$(this), durs=[], rf={};
			$c.find('tbody tr').each(function(){
				var steps=String($(this).find('.ic-d-steps').val()||'1').split(/[,،\s]+/).map(function(x){return parseInt(x,10)||1;}).filter(Boolean);
				var row={months:parseInt($(this).find('.ic-d-months').val(),10)||1,coef:parseFloat($(this).find('.ic-d-coef').val())||0,steps:steps.length?steps:[1]};
				var pr=$(this).find('.ic-d-primary').val(); if(pr!=='') row.primary_monthly_rate=parseFloat(pr)||0;
				var infra=$(this).find('.ic-d-infra').val(); if(infra!=='') row.primary_pct=parseFloat(infra)||0;
				durs.push(row);
			});
			$c.find('.ic-rf').each(function(){ rf[$(this).data('key')]=$(this).is(':checked')?1:0; });
			var gApp=parseFloat($c.find('.ic-g-app').val());
			out.push({
				id:$c.find('.ic-id').val(), name:$c.find('.ic-name').val(), active:$c.find('.ic-active').is(':checked')?1:0,
				method:$c.find('.ic-method-plan').val(), plan_category:$c.find('.ic-plan-cat').val()||'salary_auto',
				primary_monthly_rate:parseFloat($c.find('.ic-primary-p').val())||0,
				supplier_coef:parseFloat($c.find('.ic-sup').val())||parseFloat($c.find('.ic-infra-pct').val())||0,
				digital_infra_pct:parseFloat($c.find('.ic-infra-pct').val())||0,
				secondary_chain_pct:parseFloat($c.find('.ic-sec-chain').val())||6.6,
				secondary_base_on:$c.find('.ic-sec-base').val()||'principal',
				city_chain_pct:parseFloat($c.find('.ic-city-chain').val())||6.6,
				city_coef:parseFloat($c.find('.ic-city-p').val())||parseFloat($c.find('.ic-city-chain').val())||0,
				supplier_fixed_fee:parseFloat($c.find('.ic-sup-fixed').val())||0, city_fixed_fee:parseFloat($c.find('.ic-city-fixed').val())||0,
				agent_coef:parseFloat($c.find('.ic-agent-p').val())||0, agent_mode:$c.find('.ic-agent-mode-p').val()||'from_city',
				digital_fee:parseFloat($c.find('.ic-dig').val())||0, cash_ceiling:parseFloat($c.find('.ic-ceiling').val())||0,
				cash_floor:parseFloat($c.find('.ic-floor').val())||0,
				guarantor_threshold:parseFloat($c.find('.ic-guarantor').val())||0,
				need_guarantee_check:$c.find('.ic-need-g').is(':checked')?1:0,
				preview_fields:(function(){ var o={}; $c.find('.ic-pf').each(function(){ o[$(this).data('key')]=$(this).is(':checked')?1:0; }); return o; })(),
				guarantee_pct:(!isNaN(gApp)?gApp:120),
				guarantee_pct_applicant:(!isNaN(gApp)?gApp:120),
				guarantee_pct_guarantor:parseFloat($c.find('.ic-g-gua').val())||0,
				installment_checks:$c.find('.ic-inst-checks').is(':checked')?1:0,
				applicant_guarantee_check:$c.find('.ic-app-gcheck').is(':checked')?1:0,
				guarantor_guarantee_check:$c.find('.ic-gua-gcheck').is(':checked')?1:0,
				ceiling_source:$c.find('.ic-ceiling-src').val()||'admin',
				city_base_on:$c.find('.ic-city-base').val()||'principal',
				prepay_pct:parseFloat($c.find('.ic-prepay-pct').val())||6.6,
				payslip_net_pct:parseFloat($c.find('.ic-payslip-pct').val())||0,
				eligible_orgs:(function(){ var a=[]; $c.find('.ic-elig-org:checked').each(function(){ a.push($(this).val()); }); return a; })(),
				eligible_statuses:(function(){ var a=[]; $c.find('.ic-elig-st:checked').each(function(){ a.push($(this).val()); }); return a; })(),
				durations:durs, result_fields:rf
			});
		});
		return out;
	}
	$(document).on('click', '.ic-save-one', function(){
		$('#ic-save-plans').trigger('click');
		var $msg=$(this).siblings('.ic-save-one-msg');
		$msg.text('در حال ذخیره…');
		setTimeout(function(){ $msg.text('ذخیره شد ✓'); }, 800);
	});
	$('#ic-save-plans').on('click', function(){
		var data=collectPlans();
		$.post(ajax,{action:'cgs_save_installment_plans',nonce:nonceAdmin,plans:JSON.stringify(data)}).done(function(res){
			$('#ic-save-msg').text(res&&res.success?'ذخیره شد':'خطا').css('color',res&&res.success?'#0f766e':'#b91c1c');
			if(res&&res.success){ plans=data; fillPlanSelect(); }
		});
	});
	$('#ic-add-plan').on('click', function(){
		plans.push({
			id:'plan_'+Date.now(), name:'طرح جدید', active:1, method:'tamin_social', plan_category:'salary_auto',
			primary_monthly_rate:0.035, supplier_coef:3, city_coef:7, agent_coef:0, agent_mode:'from_city',
			digital_fee:0, cash_ceiling:0, cash_floor:0, city_base_on:'principal',
			durations:[{months:12,coef:0,steps:[1],primary_pct:14},{months:18,coef:0,steps:[1],primary_pct:21}],
			result_fields:{plan_name:1,principal:1,months:1,step:1,digital_fee:1,final_coef:1,purchasing_power:1,monthly_installment:1,period_installment:1,total_repay:1}
		});
		renderPlans(); fillPlanSelect();
		var $last=$('#ic-plans-list .cgs-ic-plan-card').last();
		$last.find('.cgs-ic-acc-body').show();
		$last.find('.cgs-ic-acc-toggle').text('▼');
	});
	function renderLeasing(){
		var methods=['manisa_digital','razi_leasing','flat_coef','flat_principal_fee','bank'];
		var $box=$('#ic-leasing-list').empty();
		(plans||[]).forEach(function(pl, idx){
			if(methods.indexOf(pl.method)===-1 && pl.id!=='sayadi_check') return;
			var h='<div class="cgs-ic-card" style="margin:10px 0;border:1px solid #e2e8f0">';
			h+='<h3 style="margin:0 0 8px">'+(pl.name||pl.id)+' <small style="color:#64748b">('+(pl.method||'')+')</small></h3>';
			h+='<p>سقف: '+fmt(pl.cash_ceiling||0)+' | کف: '+fmt(pl.cash_floor||0)+' | ضمانت: '+(pl.guarantee_pct||0)+'٪</p>';
			h+='<p>مدت‌ها: '+((pl.durations||[]).map(function(d){return d.months+'م';}).join('، ')||'—')+'</p>';
			h+='<p><button type="button" class="button ic-leasing-edit" data-idx="'+idx+'">ویرایش در تب طرح‌ها</button></p></div>';
			$box.append(h);
		});
		if(!$box.children().length) $box.html('<p>طرح لیزینگی تعریف نشده.</p>');
	}
	$('#ic-leasing-add').on('click', function(){
		$('.cgs-ic-tab[data-tab="plans"]').click();
		$('#ic-add-plan').click();
	});
	$('#ic-leasing-goto-save').on('click', function(){ $('.cgs-ic-tab[data-tab="plans"]').click(); });
	$(document).on('click', '.ic-leasing-edit', function(){ $('.cgs-ic-tab[data-tab="plans"]').click(); });
	$(document).on('click', '.cgs-ic-tab', function(){
		if($(this).data('tab')==='leasing') renderLeasing();
	});
	// init
	try { renderPlans(); } catch(e){ console.error('renderPlans', e); }
	try { fillPlanSelect(); } catch(e){ console.error('fillPlanSelect', e); }
	$('#ic-run').prop('disabled', false).text('محاسبه');
})(jQuery);
</script>
