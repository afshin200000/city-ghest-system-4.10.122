<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class CGS_SH_Engine {
	const OPT_ACTIVE = 'cgs_sh_active';
	const OPT_REPORT = 'cgs_sh_last_report';
	const OPT_LOG    = 'cgs_sh_log';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 70 );
		add_action( 'wp_ajax_cgs_sh_run', array( __CLASS__, 'ajax_run' ) );
		add_action( 'wp_ajax_cgs_sh_toggle', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice_critical' ), 5 );
	}

	public static function is_active() {
		return get_option( self::OPT_ACTIVE, '1' ) === '1';
	}

	public static function menu() {
		add_submenu_page(
			'city-ghest',
			'پایش خودترمیم',
			'پایش خودترمیم',
			'manage_options',
			'cgs-self-healing',
			array( __CLASS__, 'render_admin' )
		);
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$report = get_option( self::OPT_REPORT, array() );
		$active = self::is_active();
		$nonce  = wp_create_nonce( 'cgs_sh' );
		$log    = get_option( self::OPT_LOG, array() );
		if ( ! is_array( $log ) ) { $log = array(); }
		?>
		<div class="wrap" dir="rtl" style="max-width:960px;font-family:Tahoma,sans-serif">
			<h1>پایش خودترمیم (ایزوله)</h1>
			<p>این ماژول فقط گزارش و Runbook تولید می‌کند. تغییر خودکار فایل‌های هسته انجام نمی‌شود.</p>
			<p>
				وضعیت:
				<strong style="color:<?php echo $active ? '#065f46' : '#991b1b'; ?>">
					<?php echo $active ? 'فعال (Resume)' : 'متوقف (Stop)'; ?>
				</strong>
				<button type="button" class="button" id="cgs-sh-toggle"><?php echo $active ? 'Stop' : 'Resume'; ?></button>
				<button type="button" class="button button-primary" id="cgs-sh-run">اجرای پایش</button>
				<span id="cgs-sh-msg"></span>
			</p>
			<div id="cgs-sh-results">
			<?php
			$findings = is_array( $report ) ? ( $report['findings'] ?? array() ) : array();
			if ( empty( $findings ) ) {
				echo '<p style="color:#64748b">هنوز گزارشی نیست. «اجرای پایش» را بزنید.</p>';
			} else {
				foreach ( $findings as $f ) {
					$c = ( $f['severity'] ?? '' ) === 'error' ? '#fee2e2' : ( ( $f['severity'] ?? '' ) === 'warning' ? '#fef3c7' : '#dcfce7' );
					echo '<div style="background:' . esc_attr( $c ) . ';border:1px solid #e2e8f0;border-radius:10px;padding:12px;margin-bottom:10px">';
					echo '<strong>' . esc_html( $f['title'] ?? '' ) . '</strong>';
					echo '<div style="font-size:13px;margin-top:6px">' . esc_html( $f['detail'] ?? '' ) . '</div>';
					if ( ! empty( $f['runbook'] ) ) {
						echo '<div style="margin-top:8px;font-size:13px;background:#fff;border-radius:8px;padding:8px"><strong>Runbook:</strong><br>' . nl2br( esc_html( $f['runbook'] ) ) . '</div>';
					}
					echo '</div>';
				}
			}
			?>
			</div>
			<h2>لاگ عملیات (۲۰ آخر)</h2>
			<pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;max-height:240px;overflow:auto;font-size:11px"><?php
			echo esc_html( wp_json_encode( array_slice( array_reverse( $log ), 0, 20 ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
			?></pre>
			<script>
			jQuery(function($){
				var nonce = <?php echo wp_json_encode( $nonce ); ?>;
				var url = (typeof ajaxurl!=='undefined')?ajaxurl:<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
				$('#cgs-sh-run').on('click', function(){
					var $b=$(this).prop('disabled',true);
					$('#cgs-sh-msg').text('در حال پایش...');
					$.post(url,{action:'cgs_sh_run',nonce:nonce}).done(function(res){
						if(res&&res.success) location.reload();
						else $('#cgs-sh-msg').text((res&&res.data)?String(res.data):'خطا');
					}).fail(function(x){ $('#cgs-sh-msg').text('خطا '+(x&&x.status?x.status:'')); })
					.always(function(){ $b.prop('disabled',false); });
				});
				$('#cgs-sh-toggle').on('click', function(){
					$.post(url,{action:'cgs_sh_toggle',nonce:nonce}).done(function(){ location.reload(); });
				});
			});
			</script>
		</div>
		<?php
	}

	public static function ajax_toggle() {
		if ( ! check_ajax_referer( 'cgs_sh', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$on = self::is_active() ? '0' : '1';
		update_option( self::OPT_ACTIVE, $on, false );
		self::log( $on === '1' ? 'resume' : 'stop', array() );
		wp_send_json_success( array( 'active' => $on === '1' ) );
	}

	public static function ajax_run() {
		if ( ! check_ajax_referer( 'cgs_sh', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		if ( ! self::is_active() ) {
			wp_send_json_error( 'ماژول در حالت Stop است. Resume کنید.' );
		}
		$findings = self::scan();
		update_option( self::OPT_REPORT, array(
			'time'     => current_time( 'mysql' ),
			'findings' => $findings,
		), false );
		self::log( 'scan', array( 'count' => count( $findings ) ) );
		wp_send_json_success( array( 'findings' => $findings ) );
	}

	public static function scan() {
		$out = array();
		// Chart asset
		$chart = CGS_PLUGIN_DIR . 'assets/js/chart.umd.min.js';
		if ( ! is_readable( $chart ) || filesize( $chart ) < 1000 ) {
			$out[] = array(
				'severity' => 'warning',
				'title'  => 'فایل Chart.js ناقص یا غایب',
				'detail' => $chart,
				'runbook'=> "[Root Cause]\nAsset chart.umd.min.js missing/small\n[Impact]\nCRM/Dashboard charts blank\n[Source]\nassets/js/chart.umd.min.js\n[Fix]\nRestore file from release zip.",
			);
		}
		// Menu builder
		$mb = CGS_PLUGIN_DIR . 'modules/menu-builder/assets/js/admin.js';
		if ( ! is_readable( $mb ) ) {
			$out[] = array(
				'severity' => 'error',
				'title'  => 'اسکریپت منوساز یافت نشد',
				'detail' => $mb,
				'runbook'=> "[Root Cause]\nmenu-builder admin.js missing\n[Impact]\nMenu builder UI dead\n[Source]\nmodules/menu-builder/assets/js/admin.js\n[Fix]\nRe-upload module from stable zip 4.10.16+.",
			);
		}
		// Modules dir
		$mods = glob( CGS_PLUGIN_DIR . 'modules/*/bootstrap.php' );
		if ( ! $mods || count( $mods ) < 5 ) {
			$out[] = array(
				'severity' => 'warning',
				'title'  => 'تعداد ماژول‌های پوشه‌ای کم است',
				'detail' => 'نصب ممکن است ناقص باشد.',
				'runbook'=> "[Root Cause]\nIncomplete plugin extract\n[Impact]\nMissing features\n[Fix]\nReinstall full zip.",
			);
		}
		// Critical classes present after load
		foreach ( array( 'CGS_Menu_Builder' => 'menu-builder', 'CGS_Installment_Calculator' => 'installment' ) as $cls => $label ) {
			if ( ! class_exists( $cls ) ) {
				$out[] = array(
					'severity' => 'warning',
					'title'  => "کلاس {$cls} در این درخواست لود نشده",
					'detail' => "ماژول {$label} ممکن است بعداً در init لود شود یا غیرفعال باشد.",
					'runbook'=> "[Root Cause]\nClass not loaded at scan time or flag off\n[Impact]\nFeature page empty\n[Fix]\nCheck cgs_module_flags and modules/loader.php order.",
				);
			}
		}
		if ( empty( $out ) ) {
			$out[] = array(
				'severity' => 'ok',
				'title'  => 'مورد بحرانی در چک پایه یافت نشد',
				'detail' => 'این به معنی نبود باگ UI نیست؛ فقط سلامت ساختاری پایه.',
				'runbook'=> '',
			);
		}
		return $out;
	}

	public static function log( $type, $data ) {
		$log = get_option( self::OPT_LOG, array() );
		if ( ! is_array( $log ) ) { $log = array(); }
		$log[] = array( 't' => sanitize_key( $type ), 'd' => $data, 'ts' => current_time( 'mysql' ) );
		if ( count( $log ) > 50 ) { $log = array_slice( $log, -50 ); }
		update_option( self::OPT_LOG, $log, false );
	}

	public static function notice_critical() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_active() ) { return; }
		$report = get_option( self::OPT_REPORT, array() );
		$findings = is_array( $report ) ? ( $report['findings'] ?? array() ) : array();
		$errors = array_filter( $findings, function ( $f ) { return ( $f['severity'] ?? '' ) === 'error'; } );
		if ( empty( $errors ) ) { return; }
		// Only on city-ghest pages — avoid noise on all WP admin
		$page = isset( $_GET['page'] ) ? (string) $_GET['page'] : '';
		if ( $page === '' || ( strpos( $page, 'cgs-' ) !== 0 && $page !== 'city-ghest' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>پایش خودترمیم:</strong> ' . count( $errors ) . ' مورد خطا — <a href="' . esc_url( admin_url( 'admin.php?page=cgs-self-healing' ) ) . '">مشاهده Runbook</a></p></div>';
	}
}
