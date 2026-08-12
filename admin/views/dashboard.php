<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$table = CGS_Database::get_table( 'applications' );
$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

$types = function_exists( 'cgs_get_type_labels' ) ? cgs_get_type_labels() : array(
    'representative' => 'نمایندگان',
    'seller'         => 'فروشندگان',
    'marketer'       => 'بازاریابان',
    'investor'       => 'سرمایه‌گذاران',
    'applicant'      => 'متقاضیان اعتبار',
);
$statuses = function_exists( 'cgs_get_status_labels' ) ? cgs_get_status_labels() : array(
    'pending'  => 'در انتظار بررسی',
    'review'   => 'در حال بررسی',
    'approved' => 'تأیید شده',
    'rejected' => 'رد شده',
);
$colors_status = function_exists( 'cgs_get_status_colors' ) ? cgs_get_status_colors() : array(
    'pending'  => '#ffc107',
    'review'   => '#2196f3',
    'approved' => '#4caf50',
    'rejected' => '#f44336',
);
$colors_type = function_exists( 'cgs_get_type_colors' ) ? cgs_get_type_colors() : array( '#1a237e', '#3949ab', '#5c6bc0', '#7986cb', '#9fa8da' );
$cf = function_exists( 'cgs_charts_get_format' ) ? cgs_charts_get_format() : ( function_exists( 'cgs_get_chart_format' ) ? cgs_get_chart_format() : array() );

$counts_status = array();
$counts_type = array();
foreach ( $statuses as $k => $v ) $counts_status[ $k ] = 0;
foreach ( $types as $k => $v ) $counts_type[ $k ] = 0;

if ( $exists ) {
    foreach ( $statuses as $k => $v ) {
        $counts_status[ $k ] = class_exists( 'CGS_Database' )
            ? CGS_Database::count_applications( array( 'status' => $k ) )
            : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = %s", $k ) );
    }
    foreach ( $types as $k => $v ) {
        $counts_type[ $k ] = class_exists( 'CGS_Database' )
            ? CGS_Database::count_applications( array( 'type' => $k ) )
            : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE type_key = %s", $k ) );
    }
}

$filter_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$filter_type   = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
$apps = array();
if ( $exists && ( $filter_status || $filter_type ) ) {
    $where = '1=1';
    $params = array();
    if ( $filter_status ) { $where .= ' AND status = %s'; $params[] = $filter_status; }
    if ( $filter_type ) { $where .= ' AND type_key = %s'; $params[] = $filter_type; }
    $sql = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT 100";
    $apps = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
}

// Last 14 days trend
$trend_labels = array();
$trend_data = array();
if ( $exists ) {
    for ( $i = 13; $i >= 0; $i-- ) {
        $day = date( 'Y-m-d', strtotime( "-{$i} days" ) );
        $trend_labels[] = date_i18n( 'm/d', strtotime( $day ) );
        $trend_data[] = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE DATE(created_at) = %s",
            $day
        ) );
    }
}
?>
<div class="wrap">
    <h1>داشبورد شهر قسط</h1>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'dashboard' ); } ?>

    <h2>وضعیت درخواست‌ها</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:20px;">
        <?php foreach ( $statuses as $k => $lab ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=city-ghest&status=' . $k ) ); ?>" style="text-decoration:none;color:inherit;">
            <div style="background:#fff;border-right:4px solid <?php echo $colors_status[$k]; ?>;border-radius:10px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);<?php echo $filter_status===$k?'outline:2px solid #1a237e;':''; ?>">
                <div style="font-size:1.6rem;font-weight:800;"><?php echo (int)$counts_status[$k]; ?></div>
                <div style="font-size:0.88rem;color:#555;"><?php echo esc_html( $lab ); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <h2>انواع درخواست</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:20px;">
        <?php foreach ( $types as $k => $lab ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=city-ghest&type=' . $k ) ); ?>" style="text-decoration:none;color:inherit;">
            <div style="background:#fff;border:1px solid #e0e4ec;border-radius:10px;padding:16px;<?php echo $filter_type===$k?'outline:2px solid #1a237e;':''; ?>">
                <div style="font-size:1.6rem;font-weight:800;color:#1a237e;"><?php echo (int)$counts_type[$k]; ?></div>
                <div style="font-size:0.88rem;"><?php echo esc_html( $lab ); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Charts row -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1.2fr;gap:16px;margin-bottom:24px;">
        <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;">
            <h3 style="margin-top:0;font-size:1rem;"><?php echo esc_html( $cf['title_status'] ?? 'نمودار وضعیت' ); ?></h3>
            <div class="cgs-chart-wrap" style="height:260px;min-height:260px;position:relative;width:100%;"><canvas id="cgsChartStatus" height="<?php echo (int)($cf['min_height'] ?? 220); ?>"></canvas></div>
        </div>
        <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;">
            <h3 style="margin-top:0;font-size:1rem;"><?php echo esc_html( $cf['title_types'] ?? 'نمودار انواع' ); ?></h3>
            <div class="cgs-chart-wrap" style="height:260px;min-height:260px;position:relative;width:100%;"><canvas id="cgsChartTypes" height="<?php echo (int)($cf['min_height'] ?? 220); ?>"></canvas></div>
        </div>
        <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;">
            <h3 style="margin-top:0;font-size:1rem;"><?php echo esc_html( $cf['title_trend'] ?? 'روند' ); ?></h3>
            <div class="cgs-chart-wrap" style="height:260px;min-height:260px;position:relative;width:100%;"><canvas id="cgsChartTrend" height="<?php echo (int)($cf['min_height'] ?? 220); ?>"></canvas></div>
        </div>
    </div>

    <div class="cgs-panel" style="background:#fff;border:1px solid #ccd0d4;border-radius:12px;padding:16px;margin-bottom:24px;">
        <h3 style="margin-top:0;">
            <?php
            if ( $filter_status ) echo 'فیلتر وضعیت: ' . esc_html( $statuses[ $filter_status ] ?? $filter_status );
            elseif ( $filter_type ) echo 'فیلتر نوع: ' . esc_html( $types[ $filter_type ] ?? $filter_type );
            else echo 'جزئیات — روی کادرهای بالا کلیک کنید';
            ?>
        </h3>
        <?php if ( $filter_status || $filter_type ) : ?>
        <div style="margin-bottom:12px;">
            <?php if ( $filter_status ) :
                foreach ( $types as $tk => $tl ) :
                    $cnt = $exists ? (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status=%s AND type_key=%s", $filter_status, $tk)) : 0;
            ?>
            <a class="button <?php echo $filter_type===$tk?'button-primary':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=city-ghest&status='.$filter_status.'&type='.$tk ) ); ?>" style="margin:2px;"><?php echo esc_html($tl); ?> (<?php echo $cnt; ?>)</a>
            <?php endforeach; else :
                foreach ( $statuses as $sk => $sl ) :
                    $cnt = $exists ? (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE type_key=%s AND status=%s", $filter_type, $sk)) : 0;
            ?>
            <a class="button <?php echo $filter_status===$sk?'button-primary':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=city-ghest&type='.$filter_type.'&status='.$sk ) ); ?>" style="margin:2px;"><?php echo esc_html($sl); ?> (<?php echo $cnt; ?>)</a>
            <?php endforeach; endif; ?>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=city-ghest' ) ); ?>">پاک کردن فیلتر</a>
        </div>
        <table class="widefat striped">
            <thead><tr><th>کد</th><th>نام</th><th>نوع</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
            <tbody>
            <?php if ( empty( $apps ) ) : ?>
                <tr><td colspan="6" style="text-align:center;color:#888;">موردی نیست.</td></tr>
            <?php else : foreach ( $apps as $app ) : ?>
                <tr>
                    <td><?php echo esc_html( $app['code'] ?? $app['id'] ); ?></td>
                    <td><?php echo esc_html( $app['full_name'] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $types[ $app['type_key'] ] ?? $app['type_key'] ); ?></td>
                    <td><?php echo esc_html( $statuses[ $app['status'] ] ?? $app['status'] ); ?></td>
                    <td><?php echo esc_html( $app['created_at'] ?? '' ); ?></td>
                    <td>
                        <a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=cgs-applications&view=' . (int)$app['id'] ) ); ?>">جزئیات</a>
                        <select class="cgs-status-change" data-id="<?php echo (int)$app['id']; ?>" style="max-width:120px;">
                            <?php foreach ( $statuses as $sk => $sl ) : ?>
                            <option value="<?php echo esc_attr($sk); ?>" <?php selected( $app['status'], $sk ); ?>><?php echo esc_html($sl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p class="description">با کلیک روی کادرهای وضعیت یا نوع، لیست فیلترشده نمایش داده می‌شود.</p>
        <?php endif; ?>
    </div>
</div>

<script src="<?php echo esc_url( CGS_PLUGIN_URL . 'assets/js/chart.umd.min.js?ver=' . ( defined('CGS_VERSION') ? CGS_VERSION : '1' ) ); ?>"></script>
<script>
(function(){
  var cf = <?php echo wp_json_encode( $cf ); ?> || {};
  var statusLabels = <?php echo wp_json_encode( array_values( $statuses ) ); ?>;
  var statusData = <?php echo wp_json_encode( array_values( $counts_status ) ); ?>;
  var statusColors = <?php echo wp_json_encode( array_values( $colors_status ) ); ?>;
  var typeLabels = <?php echo wp_json_encode( array_values( $types ) ); ?>;
  var typeData = <?php echo wp_json_encode( array_values( $counts_type ) ); ?>;
  var typeColors = <?php echo wp_json_encode( array_values( (array) $colors_type ) ); ?>;
  var trendLabels = <?php echo wp_json_encode( $trend_labels ); ?>;
  var trendData = <?php echo wp_json_encode( $trend_data ); ?>;

  function norm(t) {
    t = t || 'doughnut';
    if (t === 'horizontalBar') return 'bar';
    return t;
  }
  function sumArr(a){ var s=0; (a||[]).forEach(function(n){ s+=Number(n)||0; }); return s; }
  function makeChart(id, cfg) {
    var el = document.getElementById(id);
    if (!el || typeof Chart === 'undefined') return;
    try {
      if (typeof Chart.getChart === 'function') {
        var old = Chart.getChart(el);
        if (old) old.destroy();
      }
      cfg.options = cfg.options || {};
      cfg.options.responsive = true;
      cfg.options.maintainAspectRatio = false;
      // داده صفر → برچسب راهنما تا باکس کاملاً خالی نماند
      var ds = (cfg.data && cfg.data.datasets && cfg.data.datasets[0]) ? cfg.data.datasets[0].data : [];
      if (sumArr(ds) === 0 && (cfg.type === 'doughnut' || cfg.type === 'pie')) {
        cfg.data.labels = ['هنوز درخواستی نیست'];
        cfg.data.datasets[0].data = [1];
        cfg.data.datasets[0].backgroundColor = ['#e2e8f0'];
        cfg.options.plugins = cfg.options.plugins || {};
        cfg.options.plugins.tooltip = { enabled: false };
      }
      return new Chart(el, cfg);
    } catch (e) {
      console.error('CGS chart', id, e);
      return null;
    }
  }
  function run() {
    if (typeof Chart === 'undefined') {
      console.warn('CGS: Chart.js missing');
      return;
    }
    var fontFamily = cf.font_family || 'Tahoma, sans-serif';
    var fontSize = parseInt(cf.font_size, 10) || 12;
    var showLegend = cf.show_legend !== '0' && cf.show_legend !== 0;
    var legendPos = cf.legend_position || 'bottom';
    var common = {
      plugins: {
        legend: { display: showLegend, position: legendPos, rtl: true, labels: { font: { family: fontFamily, size: fontSize } } }
      }
    };

    var st = norm(cf.status_type || 'doughnut');
    makeChart('cgsChartStatus', {
      type: st,
      data: { labels: statusLabels, datasets: [{ data: statusData, backgroundColor: statusColors, borderWidth: 2, borderColor: '#fff' }] },
      options: Object.assign({}, common, st === 'doughnut' ? { cutout: '55%' } : {}, (st === 'bar') ? { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } } : {})
    });

    var tt = norm(cf.types_type || 'bar');
    var tOpts = Object.assign({}, common);
    if (tt === 'bar') {
      tOpts.plugins = { legend: { display: false } };
      tOpts.scales = { y: { beginAtZero: true, ticks: { stepSize: 1 } } };
      if ((cf.types_type || '') === 'horizontalBar') tOpts.indexAxis = 'y';
    }
    makeChart('cgsChartTypes', {
      type: tt === 'horizontalBar' ? 'bar' : tt,
      data: { labels: typeLabels, datasets: [{ data: typeData, backgroundColor: typeColors.length ? typeColors : statusColors, borderWidth: 2, borderColor: '#fff' }] },
      options: tOpts
    });

    var tr = norm(cf.trend_type || 'line');
    if (tr !== 'line' && tr !== 'bar') tr = 'line';
    makeChart('cgsChartTrend', {
      type: tr,
      data: {
        labels: trendLabels,
        datasets: [{
          label: 'درخواست',
          data: trendData,
          borderColor: '#1a237e',
          backgroundColor: 'rgba(26,35,126,0.15)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(run, 50); });
  } else {
    setTimeout(run, 50);
  }
  window.addEventListener('load', function(){ setTimeout(run, 200); });
})();
</script>

<script>
jQuery(function($){
  $(document).on('change', '.cgs-status-change', function(){
    var $el = $(this);
    var id = $el.data('id');
    var status = $el.val();
    if (!window.confirm('وضعیت این درخواست تغییر کند؟')) {
      location.reload();
      return;
    }
    $.post(ajaxurl, {
      action: 'cgs_update_status',
      nonce: (window.cgsAdmin && cgsAdmin.nonce) ? cgsAdmin.nonce : '',
      id: id,
      status: status
    }).done(function(res){
      if (res && res.success) {
        location.reload();
      } else {
        alert((res && res.data) ? res.data : 'خطا در تغییر وضعیت');
      }
    }).fail(function(){ alert('خطای شبکه'); });
  });
});
</script>
