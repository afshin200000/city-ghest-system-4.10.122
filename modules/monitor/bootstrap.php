<?php
/**
 * ماژول پایش هوشمند (فاز ۱ — تشخیص + گزارش فارسی)
 * اصلاح خودکار فقط پس از تأیید ادمین و فقط برای اقدامات از پیش تعریف‌شده امن.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cgs_module_monitor_enabled' ) ) {
    function cgs_module_monitor_enabled() {
        $flags = get_option( 'cgs_module_flags', array() );
        if ( is_array( $flags ) && array_key_exists( 'monitor', $flags ) ) {
            return ! empty( $flags['monitor'] );
        }
        return true;
    }
}
// همیشه کلاس و AJAX را ثبت کن — حتی اگر UI مخفی باشد (رفع 400)
if ( ! function_exists( 'cgs_module_monitor_enabled' ) ) {
    function cgs_module_monitor_enabled() { return true; }
}

class CGS_Smart_Monitor {

    public static function init() {
        // منوی جدا حذف شد — محتوا داخل صفحه «پایش عملکرد» ادغام می‌شود
        add_action( 'wp_ajax_cgs_monitor_run', array( __CLASS__, 'ajax_run' ) );
        add_action( 'wp_ajax_cgs_monitor_apply_fix', array( __CLASS__, 'ajax_apply_fix' ) );
    }

    /**
     * اجرای چک‌های از پیش تعریف‌شده — بدون تغییر سیستم
     */
    public static function run_checks() {
        $issues = array();

        // Chart.js file
        $chart = CGS_PLUGIN_DIR . 'assets/js/chart.umd.min.js';
        if ( ! is_readable( $chart ) || filesize( $chart ) < 1000 ) {
            $issues[] = array(
                'id'       => 'chart_js_missing',
                'module'   => 'charts',
                'severity' => 'error',
                'title'    => 'فایل Chart.js در دسترس نیست',
                'detail'   => 'مسیر assets/js/chart.umd.min.js خوانا نیست یا خالی است.',
                'solution' => 'فایل chart.umd.min.js را دوباره در پوشه assets/js قرار دهید یا افزونه را از نسخه کامل نصب کنید.',
                'fix_code'=> 'none',
            );
        }

        // Module load errors from log
        $log = get_option( 'cgs_module_load_log', array() );
        if ( is_array( $log ) ) {
            $recent = array_slice( $log, -30 );
            foreach ( $recent as $row ) {
                if ( ( $row['status'] ?? '' ) === 'error' ) {
                    $issues[] = array(
                        'id'       => 'mod_err_' . ( $row['id'] ?? 'x' ),
                        'module'   => $row['id'] ?? 'unknown',
                        'severity' => 'error',
                        'title'    => 'خطا در بارگذاری ماژول «' . ( $row['id'] ?? '' ) . '»',
                        'detail'   => $row['message'] ?? '',
                        'solution' => 'لاگ را بررسی کنید. معمولاً با نصب مجدد همان ماژول یا فعال‌کردن دوباره پرچم cgs_module_flags برطرف می‌شود.',
                        'fix_code' => 'none',
                    );
                }
            }
        }

        // Chart format backup
        $cf = function_exists( 'cgs_get_chart_format' ) ? cgs_get_chart_format() : array();
        if ( empty( $cf ) || ! is_array( $cf ) ) {
            $issues[] = array(
                'id'       => 'chart_format_empty',
                'module'   => 'charts',
                'severity' => 'warning',
                'title'    => 'قالب‌بندی نمودار خالی است',
                'detail'   => 'تنظیمات chart_format مقدار معتبری ندارد.',
                'solution' => 'به تنظیمات → تب نمودارها بروید و یک‌بار «ذخیره قالب‌بندی نمودار» را بزنید.',
                'fix_code' => 'reset_chart_defaults',
            );
        }

        // Form styles option
        $fs = get_option( 'cgs_form_styles', null );
        if ( $fs !== null && ! is_array( $fs ) ) {
            $issues[] = array(
                'id'       => 'form_styles_corrupt',
                'module'   => 'appearance',
                'severity' => 'error',
                'title'    => 'داده ظاهر فرم آسیب‌دیده',
                'detail'   => 'option مربوط به cgs_form_styles آرایه نیست.',
                'solution' => 'پس از پشتیبان‌گیری، می‌توانید option را به آرایه خالی بازنشانی کنید (فقط با تأیید ادمین).',
                'fix_code' => 'reset_form_styles',
            );
        }

        // Duplicate closing / health of modules dir
        $mods = glob( CGS_PLUGIN_DIR . 'modules/*/bootstrap.php' );
        if ( ! $mods || count( $mods ) < 5 ) {
            $issues[] = array(
                'id'       => 'modules_dir_thin',
                'module'   => 'system',
                'severity' => 'warning',
                'title'    => 'تعداد ماژول‌های پوشه‌ای کم است',
                'detail'   => 'احتمالاً نصب ناقص است.',
                'solution' => 'نسخه کامل zip افزونه را دوباره آپلود کنید.',
                'fix_code' => 'none',
            );
        }

        if ( empty( $issues ) ) {
            $issues[] = array(
                'id'       => 'all_ok',
                'module'   => 'system',
                'severity' => 'ok',
                'title'    => 'مشکل بحرانی یافت نشد',
                'detail'   => 'چک‌های پایه پاس شدند. این به معنای نبود باگ UI نیست؛ فقط سلامت ساختاری.',
                'solution' => 'در صورت باگ ظاهری، همان بخش را جداگانه گزارش دهید.',
                'fix_code'=> 'none',
            );
        }

        update_option( 'cgs_monitor_last_report', array(
            'time'   => current_time( 'mysql' ),
            'issues' => $issues,
        ), false );

        return $issues;
    }

    public static function ajax_run() {
        // accept both nonces (legacy + admin)
        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'cgs_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'cgs_monitor_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'nonce نامعتبر' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز', 403 );
        }
        $issues = self::run_checks();
        $report = array(
            'time'   => current_time( 'mysql' ),
            'count'  => count( $issues ),
            'issues' => $issues,
        );
        update_option( 'cgs_monitor_last_report', $report, false );
        wp_send_json_success( $report );
    }

    /**
     * اصلاح امن فقط برای کدهای از پیش تعریف‌شده — نه اجرای کد دلخواه
     */
    public static function ajax_apply_fix() {
        check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'دسترسی غیرمجاز' );
        }
        $code = sanitize_key( $_POST['fix_code'] ?? '' );
        $allowed = array( 'reset_chart_defaults', 'reset_form_styles' );
        if ( ! in_array( $code, $allowed, true ) ) {
            wp_send_json_error( 'این اصلاح خودکار مجاز نیست. فقط اقدامات سفیدلیست‌شده پس از تأیید ادمین اجرا می‌شوند.' );
        }
        if ( empty( $_POST['confirm'] ) || $_POST['confirm'] !== 'yes' ) {
            wp_send_json_error( 'تأیید ادمین الزامی است.' );
        }
        if ( $code === 'reset_chart_defaults' && function_exists( 'cgs_get_chart_format' ) ) {
            // force save defaults via option backup clear
            delete_option( 'cgs_chart_format_v' );
            if ( function_exists( 'cgs_update_option' ) ) {
                $defs = cgs_get_chart_format();
                cgs_update_option( 'chart_format', is_array( $defs ) ? $defs : array() );
            }
            wp_send_json_success( 'قالب نمودار بازنشانی شد.' );
        }
        if ( $code === 'reset_form_styles' ) {
            update_option( 'cgs_form_styles', array(), false );
            wp_send_json_success( 'ظاهر فرم به حالت خالی بازنشانی شد (انواع باید دوباره ذخیره شوند).' );
        }
        wp_send_json_error( 'عملیات ناشناخته' );
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $report = get_option( 'cgs_monitor_last_report', array() );
        $boot   = get_option( 'cgs_module_last_boot', array() );
        $log    = get_option( 'cgs_module_load_log', array() );
        $nonce  = wp_create_nonce( 'cgs_admin_nonce' );
        ?>
        <div class="wrap" dir="rtl">
            <h1>پایش هوشمند شهر قسط</h1>
            <p>این بخش وضعیت ماژول‌ها و مشکلات ساختاری را گزارش می‌کند. <strong>اصلاح خودکار فقط پس از تأیید شما</strong> و فقط برای اقدامات امن از پیش تعریف‌شده انجام می‌شود — نه اجرای کد آزاد توسط هوش مصنوعی داخل سرور.</p>
            <p>
                <button type="button" class="button button-primary" id="cgs-mon-run">اجرای پایش اکنون</button>
                <span id="cgs-mon-msg" style="margin-right:12px;"></span>
            </p>
            <div class="cgs-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:10px;padding:16px;margin:16px 0;">
                <h2 style="margin-top:0;">آخرین بوت ماژول‌ها</h2>
                <?php if ( ! empty( $boot['time'] ) ) : ?>
                    <p>زمان: <code><?php echo esc_html( $boot['time'] ); ?></code> — تعداد: <?php echo (int) ( $boot['count'] ?? 0 ); ?></p>
                    <p style="font-size:12px;color:#555;"><?php echo esc_html( implode( '، ', (array) ( $boot['loaded'] ?? array() ) ) ); ?></p>
                <?php else : ?>
                    <p>هنوز گزارشی نیست.</p>
                <?php endif; ?>
            </div>
            <div class="cgs-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:10px;padding:16px;margin:16px 0;">
                <h2 style="margin-top:0;">نتیجه پایش</h2>
                <div id="cgs-mon-results">
                <?php
                $issues = $report['issues'] ?? array();
                if ( empty( $issues ) ) {
                    echo '<p>هنوز پایشی اجرا نشده. دکمه بالا را بزنید.</p>';
                } else {
                    foreach ( $issues as $iss ) {
                        $c = $iss['severity'] === 'error' ? '#fee2e2' : ( $iss['severity'] === 'warning' ? '#fef3c7' : '#dcfce7' );
                        echo '<div style="border:1px solid #e2e8f0;border-right:4px solid #1a237e;background:' . esc_attr( $c ) . ';padding:12px;border-radius:8px;margin-bottom:10px;">';
                        echo '<strong>' . esc_html( $iss['title'] ?? '' ) . '</strong>';
                        echo ' <span style="font-size:11px;color:#64748b;">[' . esc_html( $iss['module'] ?? '' ) . ']</span>';
                        echo '<p style="margin:6px 0;font-size:13px;">' . esc_html( $iss['detail'] ?? '' ) . '</p>';
                        echo '<p style="margin:0;font-size:13px;"><strong>راه‌حل:</strong> ' . esc_html( $iss['solution'] ?? '' ) . '</p>';
                        if ( ! empty( $iss['fix_code'] ) && $iss['fix_code'] !== 'none' ) {
                            echo '<p style="margin:8px 0 0;"><button type="button" class="button cgs-mon-fix" data-fix="' . esc_attr( $iss['fix_code'] ) . '">درخواست اصلاح امن (نیاز به تأیید)</button></p>';
                        }
                        echo '</div>';
                    }
                }
                ?>
                </div>
            </div>
            <div class="cgs-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:10px;padding:16px;">
                <h2 style="margin-top:0;">لاگ بارگذاری ماژول (۳۰ آخر)</h2>
                <table class="widefat striped"><thead><tr><th>زمان</th><th>ماژول</th><th>وضعیت</th><th>پیام</th></tr></thead><tbody>
                <?php
                $rows = is_array( $log ) ? array_reverse( array_slice( $log, -30 ) ) : array();
                foreach ( $rows as $r ) {
                    echo '<tr><td>' . esc_html( $r['time'] ?? '' ) . '</td><td>' . esc_html( $r['id'] ?? '' ) . '</td><td>' . esc_html( $r['status'] ?? '' ) . '</td><td>' . esc_html( $r['message'] ?? '' ) . '</td></tr>';
                }
                if ( empty( $rows ) ) {
                    echo '<tr><td colspan="4">خالی</td></tr>';
                }
                ?>
                </tbody></table>
            </div>
            <script>
            jQuery(function($){
              var nonce = <?php echo wp_json_encode( $nonce ); ?>;
              $('#cgs-mon-run').on('click', function(){
                var $b=$(this).prop('disabled',true);
                $('#cgs-mon-msg').text('در حال پایش...');
                $.post(ajaxurl,{action:'cgs_monitor_run',nonce:nonce}).done(function(res){
                  if(res&&res.success){ location.reload(); }
                  else { $('#cgs-mon-msg').text('خطا'); }
                }).always(function(){ $b.prop('disabled',false); });
              });
              $(document).on('click','.cgs-mon-fix',function(){
                var code=$(this).data('fix');
                if(!confirm('این اصلاح فقط اقدامات سفیدلیست‌شده را انجام می‌دهد. ادامه؟')) return;
                $.post(ajaxurl,{action:'cgs_monitor_apply_fix',nonce:nonce,fix_code:code,confirm:'yes'}).done(function(res){
                  alert((res&&res.data)?res.data:(res&&res.success?'انجام شد':'خطا'));
                  if(res&&res.success) location.reload();
                });
              });
            });
            </script>
        </div>
        <?php
    }
}

CGS_Smart_Monitor::init();
