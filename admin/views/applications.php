<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = CGS_Database::get_table( 'applications' );

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$type_filter   = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';

$where = '1=1';
if ( $status_filter ) {
    $where .= $wpdb->prepare( ' AND status = %s', $status_filter );
}
if ( $type_filter ) {
    $where .= $wpdb->prepare( ' AND type_key = %s', $type_filter );
}

$apps = $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT 100" );
$statuses = cgs_get_statuses();
$types = cgs_get_application_types();
?>
<div class="wrap cgs-admin-wrap">
    <h1>مدیریت درخواست‌ها</h1>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'applications' ); } ?>

    <div class="cgs-filters">
        <form method="get">
            <input type="hidden" name="page" value="cgs-applications">
            <select name="status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach ( $statuses as $k => $s ) : ?>
                    <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $status_filter, $k ); ?>><?php echo esc_html( $s['label'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type">
                <option value="">همه انواع</option>
                <?php foreach ( $types as $k => $t ) : ?>
                    <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $type_filter, $k ); ?>><?php echo esc_html( $t['label'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">فیلتر</button>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>کد</th>
                <th>نام</th>
                <th>موبایل</th>
                <th>نوع</th>
                <th>وضعیت</th>
                <th>تاریخ</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $apps ) ) : ?>
                <tr><td colspan="7">درخواستی یافت نشد.</td></tr>
            <?php else : foreach ( $apps as $app ) :
                $st = $statuses[ $app->status ] ?? array( 'label' => $app->status, 'color' => '#999' );
                $tp = $types[ $app->type_key ]['label'] ?? $app->type_key;
            ?>
                <tr>
                    <td><code><?php echo esc_html( $app->code ); ?></code></td>
                    <td><?php echo esc_html( $app->full_name ); ?></td>
                    <td><?php echo esc_html( $app->mobile ); ?></td>
                    <td><?php echo esc_html( $tp ); ?></td>
                    <td><span style="color:<?php echo esc_attr( $st['color'] ); ?>;font-weight:bold;"><?php echo esc_html( $st['label'] ); ?></span></td>
                    <td><?php echo esc_html( cgs_format_date( strtotime( $app->created_at ) ) ); ?></td>
                    <td>
                        <select class="cgs-status-change" data-id="<?php echo (int) $app->id; ?>">
                            <?php foreach ( $statuses as $k => $s ) : ?>
                                <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $app->status, $k ); ?>><?php echo esc_html( $s['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

<?php
/* نقشه فقط برای نمایندگان/فروشندگان تاییدشده — سطح شهر */
$agent_types = array( 'agent', 'dealer', 'seller', 'representative', 'store' );
$show_map_apps = array();
foreach ( $apps as $__a ) {
    $tk = $__a->type_key ?? '';
    $st = $__a->status ?? '';
    if ( in_array( $st, array( 'approved', 'active' ), true ) || $st === 'تایید' ) {
        // also allow status key approved
        $is_agent = false;
        foreach ( $agent_types as $at ) {
            if ( strpos( $tk, $at ) !== false || $tk === 'نماینده' ) { $is_agent = true; break; }
        }
        // type keys used in plugin
        if ( in_array( $tk, array( 'agent', 'seller', 'representative' ), true ) || strpos( $tk, 'agent' ) !== false || strpos( $tk, 'seller' ) !== false ) {
            $is_agent = true;
        }
        if ( $is_agent || in_array( $tk, array( 'agent', 'seller' ), true ) ) {
            $meta = class_exists( 'CGS_Application' ) ? CGS_Application::get_meta( $__a->id ) : array();
            $city = $meta['city'] ?? ( $__a->city ?? '' );
            $province = $meta['province'] ?? ( $__a->province ?? '' );
            if ( $city || $province ) {
                $show_map_apps[] = array(
                    'name' => $__a->full_name,
                    'city' => $city,
                    'province' => $province,
                    'code' => $__a->code,
                );
            }
        }
    }
}
if ( ! empty( $show_map_apps ) ) :
?>
<div class="cgs-panel" style="margin-top:20px;background:#fff;border:1px solid #c3c4c7;border-radius:12px;padding:14px;">
  <h2 style="margin-top:0;">موقعیت تقریبی نمایندگان / فروشگاه‌های تاییدشده (سطح شهر)</h2>
  <p class="description">فقط مرکز شهر نمایش داده می‌شود — موقعیت دقیق آدرس نمایش داده نمی‌شود.</p>
  <div id="cgs-agent-map" style="height:320px;border-radius:10px;border:1px solid #e2e8f0;"></div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
  var agents = <?php echo wp_json_encode( $show_map_apps ); ?>;
  var coords = <?php echo function_exists('cgs_get_city_coords') ? wp_json_encode( cgs_get_city_coords() ) : '{}'; ?>;
  function findLatLng(city, province) {
    if (coords[city]) return coords[city];
    if (coords[province]) return coords[province];
    // fuzzy
    for (var k in coords) {
      if (city && k.indexOf(city) !== -1) return coords[k];
    }
    return [32.4279, 53.6880]; // مرکز ایران
  }
  function init(){
    if (typeof L === 'undefined') return;
    var map = L.map('cgs-agent-map').setView([32.4279, 53.6880], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 12, attribution: '© OSM'
    }).addTo(map);
    var bounds = [];
    agents.forEach(function(a){
      var ll = findLatLng(a.city, a.province);
      var lat = ll[0], lng = ll[1];
      // jitter lightly so multiple in same city don't stack exactly — still city-level
      lat += (Math.random()-0.5)*0.08;
      lng += (Math.random()-0.5)*0.08;
      var m = L.marker([lat,lng]).addTo(map);
      m.bindPopup('<strong>'+ (a.name||'') +'</strong><br>'+ (a.city||a.province||'') +'<br><code>'+ (a.code||'') +'</code>');
      bounds.push([lat,lng]);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [30,30], maxZoom: 10 });
  }
  if (document.readyState === 'complete') init();
  else window.addEventListener('load', init);
})();
</script>
<?php endif; ?>

</div>
