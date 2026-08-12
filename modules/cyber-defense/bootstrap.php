<?php
/**
 * Cyber Defense — anti-recon / anti-clone / anti-dump layer (isolated module)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'cgs_module_cyber_defense_enabled' ) ) {
	function cgs_module_cyber_defense_enabled() {
		$flags = get_option( 'cgs_module_flags', array() );
		if ( is_array( $flags ) && array_key_exists( 'cyber-defense', $flags ) ) {
			return ! empty( $flags['cyber-defense'] );
		}
		return true;
	}
}
if ( ! cgs_module_cyber_defense_enabled() ) { return; }

$dir = __DIR__ . '/includes/';
foreach ( array( 'class-headers.php', 'class-api-harden.php', 'class-rate.php', 'class-crypto.php', 'class-license.php', 'class-db-guard.php', 'class-stealth.php', 'class-anti-abuse.php' ) as $f ) {
	if ( is_readable( $dir . $f ) ) {
		require_once $dir . $f;
	}
}

if ( class_exists( 'CGS_CD_Headers' ) ) { CGS_CD_Headers::init(); }
if ( class_exists( 'CGS_CD_API_Harden' ) ) { CGS_CD_API_Harden::init(); }
if ( class_exists( 'CGS_CD_Rate' ) ) { CGS_CD_Rate::init(); }
if ( class_exists( 'CGS_CD_License' ) ) { CGS_CD_License::init(); }
if ( class_exists( 'CGS_CD_DB_Guard' ) ) { CGS_CD_DB_Guard::init(); }
if ( class_exists( 'CGS_CD_Stealth' ) ) { CGS_CD_Stealth::init(); }
if ( class_exists( 'CGS_CD_Anti_Abuse' ) ) { CGS_CD_Anti_Abuse::init(); }

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'city-ghest', 'دفاع سایبری', 'دفاع سایبری', 'manage_options', 'cgs-cyber-defense', 'cgs_cd_admin_page' );
}, 88 );

function cgs_cd_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$opts = get_option( 'cgs_cd_opts', array() );
	$opts = wp_parse_args( is_array( $opts ) ? $opts : array(), array(
		'hsts' => 1, 'csp' => 1, 'rest_strict' => 1, 'rate_front' => 1, 'cloak_ver' => 1, 'stealth' => 1,
	) );
	if ( isset( $_POST['cgs_cd_save'] ) && check_admin_referer( 'cgs_cd_save' ) ) {
		foreach ( array( 'hsts', 'csp', 'rest_strict', 'rate_front', 'cloak_ver', 'stealth' ) as $k ) {
			$opts[ $k ] = ! empty( $_POST[ $k ] ) ? 1 : 0;
		}
		update_option( 'cgs_cd_opts', $opts, false );
		echo '<div class="updated"><p>ذخیره شد.</p></div>';
	}
	$ok = class_exists( 'CGS_CD_License' ) ? CGS_CD_License::ensure_anchor() : true;
	?>
	<div class="wrap" dir="rtl" style="max-width:960px;font-family:Tahoma,Vazirmatn,sans-serif">
		<h1>دفاع سایبری — لایه نظامی</h1>
		<p style="color:#334155">هدف: پنهان‌سازی ردپای وردپرس/افزونه‌ها، جلوگیری از کپی دامنه، مسدودسازی اسکنر، رمزنگاری کمکی، ضد دامپ مخرب.</p>
		<form method="post"><?php wp_nonce_field( 'cgs_cd_save' ); ?>
			<table class="form-table" style="background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(15,23,42,.06)">
				<tr><th>HSTS</th><td><label><input type="checkbox" name="hsts" value="1" <?php checked( $opts['hsts'] ); ?>> فعال روی HTTPS</label></td></tr>
				<tr><th>CSP</th><td><label><input type="checkbox" name="csp" value="1" <?php checked( $opts['csp'] ); ?>> Content-Security-Policy</label></td></tr>
				<tr><th>REST سخت</th><td><label><input type="checkbox" name="rest_strict" value="1" <?php checked( $opts['rest_strict'] ); ?>> مسدود کاربران عمومی REST</label></td></tr>
				<tr><th>Rate-limit</th><td><label><input type="checkbox" name="rate_front" value="1" <?php checked( $opts['rate_front'] ); ?>> محدودیت نرخ مهمان</label></td></tr>
				<tr><th>حذف ver=</th><td><label><input type="checkbox" name="cloak_ver" value="1" <?php checked( $opts['cloak_ver'] ); ?>> ضد انگشت‌نگاری نسخه</label></td></tr>
				<tr><th>حالت Stealth</th><td><label><input type="checkbox" name="stealth" value="1" <?php checked( $opts['stealth'] ); ?>> پنهان‌سازی CMS / مسیرهای اسکن</label></td></tr>
			</table>
			<p><button class="button button-primary" name="cgs_cd_save" value="1">ذخیره</button></p>
		</form>
		<div style="margin-top:20px;padding:16px;background:#f8fafc;border-radius:12px;line-height:1.9">
			<p><strong>وضعیت لنگر دامنه:</strong> <span style="color:<?php echo $ok ? '#065f46' : '#b91c1c'; ?>"><?php echo $ok ? 'منطبق با میزبان فعلی' : 'عدم تطابق — احتمال کلون'; ?></span></p>
			<ul>
				<li>XML-RPC خاموش · مسیرهای .env / .git / xmlrpc / readme مسدود</li>
				<li>حذف Generator، RSD، emoji scripts، shortlink از head</li>
				<li>فیلتر body_class برای کاهش ردپای تم</li>
				<li>AES-256-GCM: <code>CGS_CD_Crypto::encrypt/decrypt</code></li>
				<li>بلاک OUTFILE / LOAD_FILE / UNION+INFORMATION_SCHEMA مشکوک</li>
				<li>Throttle ورود و Rate-limit فرانت (admin-ajax مستثنی)</li>
			</ul>
			<p style="color:#64748b;font-size:13px">توجه: مخفی‌سازی کامل از ابزارهایی مثل Wappalyzer ۱۰۰٪ تضمین نمی‌شود (سیگنال‌های سمت سرور/هاست خارج از کنترل PHP است)، اما لایه فعلی سطح حمله را به‌طور محسوس کاهش می‌دهد.</p>
		</div>
	</div>
	<?php
}
