<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ماژول مدیریت داده — مستقل از هسته فرم‌ساز
 * خروجی/ورودی CSV درخواست‌ها و فیلدها بدون دست‌زدن به منطق اصلی
 */
class CGS_Data_Manager {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'wp_ajax_cgs_export_apps_csv', array( __CLASS__, 'ajax_export_apps' ) );
		add_action( 'wp_ajax_cgs_export_fields_csv', array( __CLASS__, 'ajax_export_fields' ) );
	}

	public static function menu() {
		add_submenu_page(
			'city-ghest',
			'مدیریت داده',
			'مدیریت داده',
			'manage_options',
			'cgs-data-manager',
			array( __CLASS__, 'page' )
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$types = function_exists( 'cgs_get_application_types' ) ? cgs_get_application_types() : array();
		?>
		<div class="wrap cgs-admin-wrap">
			<h1>مدیریت داده (ماژولار)</h1>
			<details class="cgs-help" open>
				<summary>راهنما: خروجی و پشتیبان</summary>
				<div class="cgs-help-body">
					<p>این بخش مستقل است و روی فرم‌ساز اثر نمی‌گذارد. برای گزارش یا پشتیبان، CSV بگیرید.</p>
					<ol>
						<li>نوع مخاطب را انتخاب کنید (یا همه).</li>
						<li>دکمه خروجی را بزنید؛ فایل دانلود می‌شود.</li>
					</ol>
				</div>
			</details>
			<div class="cgs-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:14px;padding:18px;max-width:640px;">
				<h2 style="margin-top:0;">خروجی درخواست‌ها (CSV)</h2>
				<p>
					<select id="cgs-dm-type">
						<option value="">— همه انواع —</option>
						<?php foreach ( $types as $k => $t ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $t['label'] ?? $k ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<button type="button" class="cgs-btn-admin cgs-btn-admin-primary" id="cgs-dm-export-apps">⬇️ خروجی CSV درخواست‌ها</button>
					<button type="button" class="cgs-btn-admin cgs-btn-admin-success" id="cgs-dm-export-fields">⬇️ خروجی فیلدهای فرم‌ساز</button>
				</p>
				<span id="cgs-dm-msg"></span>
			</div>
		</div>
		<script>
		jQuery(function($){
			var nonce = '<?php echo wp_create_nonce( 'cgs_admin_nonce' ); ?>';
			function dl(action, extra){
				$('#cgs-dm-msg').text('در حال آماده‌سازی...');
				var data = $.extend({ action: action, nonce: nonce }, extra||{});
				$.post(ajaxurl, data).done(function(res){
					if (!res.success || !res.data || !res.data.csv) {
						$('#cgs-dm-msg').text(res.data || 'خطا').css('color','red');
						return;
					}
					var blob = new Blob(["\uFEFF" + res.data.csv], { type: 'text/csv;charset=utf-8;' });
					var a = document.createElement('a');
					a.href = URL.createObjectURL(blob);
					a.download = res.data.filename || 'cgs-export.csv';
					a.click();
					$('#cgs-dm-msg').text('✓ دانلود شد').css('color','green');
				}).fail(function(){ $('#cgs-dm-msg').text('خطای ارتباط').css('color','red'); });
			}
			$('#cgs-dm-export-apps').on('click', function(){ dl('cgs_export_apps_csv', { type_key: $('#cgs-dm-type').val() }); });
			$('#cgs-dm-export-fields').on('click', function(){ dl('cgs_export_fields_csv', { type_key: $('#cgs-dm-type').val() || 'representative' }); });
		});
		</script>
		<?php
	}

	public static function ajax_export_apps() {
		check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی غیرمجاز' );
		}
		global $wpdb;
		$table = $wpdb->prefix . 'cgs_applications';
		$type  = sanitize_key( $_POST['type_key'] ?? '' );
		$sql   = "SELECT code, full_name, mobile, type_key, status, created_at FROM $table";
		$args  = array();
		if ( $type ) {
			$sql   .= ' WHERE type_key = %s';
			$args[] = $type;
		}
		$sql .= ' ORDER BY id DESC LIMIT 5000';
		$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		$csv  = self::to_csv( array( 'کد', 'نام', 'موبایل', 'نوع', 'وضعیت', 'تاریخ' ), $rows ?: array() );
		wp_send_json_success( array(
			'csv'      => $csv,
			'filename' => 'cgs-applications-' . date( 'Ymd-His' ) . '.csv',
		) );
	}

	public static function ajax_export_fields() {
		check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی غیرمجاز' );
		}
		global $wpdb;
		$table = $wpdb->prefix . 'cgs_form_fields';
		$type  = sanitize_key( $_POST['type_key'] ?? 'representative' );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT field_key, label, field_type, step_number, is_required, sort_order FROM $table WHERE type_key = %s ORDER BY step_number, sort_order",
			$type
		), ARRAY_A );
		$csv = self::to_csv( array( 'کلید', 'برچسب', 'نوع', 'مرحله', 'اجباری', 'ترتیب' ), $rows ?: array() );
		wp_send_json_success( array(
			'csv'      => $csv,
			'filename' => 'cgs-fields-' . $type . '-' . date( 'Ymd' ) . '.csv',
		) );
	}

	private static function to_csv( $headers, $rows ) {
		$lines = array( implode( ',', array_map( array( __CLASS__, 'esc_csv' ), $headers ) ) );
		foreach ( $rows as $r ) {
			$lines[] = implode( ',', array_map( array( __CLASS__, 'esc_csv' ), array_values( $r ) ) );
		}
		return implode( "\n", $lines );
	}

	private static function esc_csv( $v ) {
		$v = (string) $v;
		if ( strpos( $v, ',' ) !== false || strpos( $v, '"' ) !== false || strpos( $v, "\n" ) !== false ) {
			return '"' . str_replace( '"', '""', $v ) . '"';
		}
		return $v;
	}
}
