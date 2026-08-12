<style id="cgs-title-anims">
@keyframes cgsFadeIn { from{opacity:0} to{opacity:1} }
@keyframes cgsSlideIn { from{transform:translateY(-8px);opacity:0} to{transform:none;opacity:1} }
@keyframes cgsPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.03)} }
.cgs-title-anim-fade { animation: cgsFadeIn .5s ease; }
.cgs-title-anim-slide { animation: cgsSlideIn .45s ease; }
.cgs-title-anim-pulse { animation: cgsPulse 1.2s ease infinite; }
@media(max-width:900px){ .cgs-title-btn-grid{ grid-template-columns:1fr !important; } }

/* تقویم جلالی همیشه روی همه لایه‌ها */
.cgs-jdp { position: fixed !important; z-index: 2147483646 !important; }
#cgs-live-preview, #cgs-live-preview .cgs-form-wrapper,
#cgs-live-preview .cgs-form-step, #cgs-live-preview .cgs-step-fields,
#cgs-live-preview .cgs-field-group, #cgs-live-preview .cgs-field-control {
  overflow: visible !important;
}
#cgs-live-preview .cgs-field-group:has(.cgs-datepicker),
#cgs-live-preview .cgs-field-group:has(.cgs-jalali-date) {
  z-index: 20;
  position: relative;
}

#cgs-live-preview .req, .cgs-preview-mode .req { color: #c62828; font-weight: 800; }
</style>

<style id="cgs-step-card-layout">
#cgs-step-meta-cards {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
  gap: 12px !important;
}
.cgs-step-card-header { flex-shrink: 0; }
.cgs-step-card .cgs-delete-step { position: static !important; float: none !important; }
.cgs-step-card input, .cgs-step-card select { width: 100% !important; box-sizing: border-box; }
</style>

<style id="cgs-emergency-unlock">
/* اضطراری: هیچ ورودی‌ای قفل نباشد */
.cgs-admin-wrap input,
.cgs-admin-wrap select,
.cgs-admin-wrap textarea,
.cgs-admin-wrap button,
.cgs-fb-wrap input,
.cgs-fb-wrap select,
.cgs-fb-wrap textarea,
.cgs-fb-wrap button,
.cgs-step-card input,
.cgs-step-card select,
.cgs-step-card textarea,
#cgs-live-preview input,
#cgs-live-preview select,
#cgs-live-preview textarea {
  pointer-events: auto !important;
  opacity: 1 !important;
  user-select: text !important;
  -webkit-user-select: text !important;
  position: relative !important;
  z-index: 60 !important;
}
body.cgs-is-sorting input,
body.cgs-is-sorting select,
body.cgs-is-sorting textarea {
  pointer-events: auto !important;
}
</style>
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$types         = cgs_get_application_types();
$current_type  = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : 'representative';
$step_meta = get_option( 'cgs_step_meta_' . $current_type, array() );

$field_types   = CGS_Form_Builder::get_field_types();
$fields        = CGS_Form_Builder::get_fields( $current_type, false );
$styles        = CGS_Form_Styles::get( $current_type );
$fonts         = array( 'Vazirmatn', 'BNazanin', 'BTitr', 'Samim', 'Shabnam', 'Tahoma', 'IranSans', 'Arial', 'Roboto', 'Segoe UI' );
?>

<style id="cgs-style-panel-ui">
.cgs-builder-styles-col {
  font-family: Tahoma, Vazirmatn, "IranSans", sans-serif !important;
}
.cgs-style-box {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
  margin-bottom: 12px;
  box-shadow: 0 4px 16px rgba(26,35,126,0.06);
  grid-column: span 1;
}
.cgs-style-box h4 {
  margin: 0 0 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eef2ff;
  color: #1a237e;
  font-size: 14px;
  font-weight: 700;
}
.cgs-style-grid-inner {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 10px 14px !important;
}
.cgs-style-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.cgs-style-row label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}
.cgs-style-row select,
.cgs-style-row input[type="number"],
.cgs-style-row input[type="text"],
.cgs-style-row input[type="color"] {
  width: 100%;
  max-width: 100%;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  padding: 6px 8px;
  font-size: 13px;
}
.cgs-style-grid.cgs-style-grid-2 {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 12px !important;
}
@media (max-width: 1100px) {
  .cgs-style-grid.cgs-style-grid-2 { grid-template-columns: 1fr !important; }
  .cgs-style-grid-inner { grid-template-columns: 1fr !important; }
}
</style>


<style id="cgs-resize-ui">
/* Flex تا عرض فیلد پایدار بماند (Grid باعث برگشت اندازه می‌شد) */
#cgs-live-preview .cgs-step-fields {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 12px !important;
  align-items: flex-start !important;
  width: 100% !important;
  min-width: 0 !important;
}
#cgs-live-preview .cgs-step-fields.cgs-has-guides {
  border: 1px dashed #9fa8da !important;
  border-radius: 12px;
  padding: 12px !important;
  background-color: #fafbff !important;
}
#cgs-live-preview .cgs-field-group {
  position: relative !important;
  box-sizing: border-box !important;
  min-width: 12% !important;
  flex: 0 0 var(--cgs-fw, 100%) !important;
  width: var(--cgs-fw, 100%) !important;
  max-width: 100% !important;
}
#cgs-live-preview .cgs-resize-handle {
  position: absolute !important;
  left: -6px !important;
  top: 50% !important;
  transform: translateY(-50%);
  width: 18px !important;
  height: 42px !important;
  cursor: ew-resize !important;
  z-index: 20 !important;
  background: linear-gradient(135deg, #3949ab, #1a237e) !important;
  border-radius: 8px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 2px 10px rgba(26,35,126,0.35) !important;
  opacity: 0.85 !important;
}
#cgs-live-preview .cgs-resize-handle:hover,
#cgs-live-preview .cgs-field-group:hover .cgs-resize-handle {
  opacity: 1 !important;
  width: 22px !important;
}
#cgs-live-preview .cgs-resize-knob {
  width: 4px; height: 18px;
  border-left: 2px solid rgba(255,255,255,0.9);
  border-right: 2px solid rgba(255,255,255,0.9);
}
#cgs-live-preview .cgs-width-badge {
  position: absolute; top: 4px; left: 22px;
  font-size: 10px; background: #eef2ff; color: #1a237e;
  padding: 1px 6px; border-radius: 6px; font-weight: 700; z-index: 15;
}
#cgs-live-preview .cgs-btn-style-glass .cgs-btn,
#cgs-live-preview.cgs-btn-style-glass .cgs-btn {
  background: rgba(255,255,255,0.35) !important;
  backdrop-filter: blur(12px) !important;
  border: 1.5px solid rgba(255,255,255,0.65) !important;
}
</style>


<style id="cgs-table-advanced">
.cgs-dynamic-table-wrap { width:100%; overflow-x:auto; border-radius:12px; background:#fff; box-shadow:0 2px 12px rgba(15,23,42,.06); padding:4px; }
.cgs-dynamic-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; border-radius:10px; overflow:hidden; }
.cgs-dynamic-table thead th {
  color:#fff; font-weight:700; padding:10px 12px; text-align:right;
  border:none; font-size:12.5px;
}
.cgs-dynamic-table tbody td {
  border:1px solid #e2e8f0; padding:6px 8px; background:#fff;
  transition: background .15s;
}
.cgs-dynamic-table tbody tr:nth-child(even) td { background:#f8fafc; }
.cgs-dynamic-table tbody tr:hover td { background:#eef2ff; }
.cgs-dynamic-table tbody input.cgs-input {
  width:100%; border:1px solid transparent !important; background:transparent !important;
  padding:6px 8px !important; font-size:13px !important; border-radius:6px !important;
  min-height:34px !important;
}
.cgs-dynamic-table tbody input.cgs-input:focus {
  border-color:#9fa8da !important; background:#fff !important; outline:none !important;
  box-shadow:0 0 0 2px rgba(26,35,126,.12) !important;
}
.cgs-table-add-row {
  margin-top:10px !important; border-radius:8px !important;
  background:#eef2ff !important; color:#1a237e !important; border:1px solid #c5cae9 !important;
  font-weight:600 !important; cursor:pointer;
}
.cgs-divider-block { padding:14px 0 8px; }
.cgs-divider-title { font-size:13.5px; font-weight:700; color:#1a237e; margin-bottom:8px; }
.cgs-divider-line, .cgs-divider-block hr { border:0; border-top:2px solid #c5cae9; margin:0; }
</style>

<style id="cgs-preview-title-fix">
#cgs-live-preview .cgs-field-label,
#cgs-live-preview label.cgs-field-label {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: #475569 !important;
  line-height: 1.4 !important;
  margin: 0 0 4px !important;
  padding: 0 !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
/* حالت کنار — اجباری */
#cgs-live-preview .cgs-labels-beside .cgs-field-group,
#cgs-live-preview.cgs-labels-beside .cgs-field-group,
.cgs-preview-mode.cgs-labels-beside .cgs-field-group {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap !important;
  align-items: center !important;
  gap: 10px !important;
}
#cgs-live-preview .cgs-labels-beside .cgs-field-label,
#cgs-live-preview.cgs-labels-beside .cgs-field-label,
.cgs-preview-mode.cgs-labels-beside .cgs-field-label {
  display: block !important;
  width: 32% !important;
  max-width: 180px !important;
  flex: 0 0 32% !important;
  margin: 0 !important;
  text-align: right !important;
}
#cgs-live-preview .cgs-labels-beside .cgs-field-control,
#cgs-live-preview .cgs-labels-beside .cgs-input,
#cgs-live-preview .cgs-labels-beside input,
#cgs-live-preview .cgs-labels-beside select,
#cgs-live-preview .cgs-labels-beside textarea,
#cgs-live-preview.cgs-labels-beside .cgs-field-control {
  flex: 1 1 auto !important;
  width: auto !important;
  max-width: 100% !important;
  min-width: 0 !important;
}
#cgs-live-preview .cgs-labels-above .cgs-field-group {
  display: block !important;
  flex-direction: column !important;
}
#cgs-live-preview .cgs-labels-above .cgs-field-label {
  width: 100% !important;
  margin-bottom: 4px !important;
}
#cgs-live-preview .cgs-field-group,
#cgs-live-preview .cgs-field-card {
  padding: 10px 12px 10px 16px !important;
}
#cgs-live-preview .cgs-sub-label {
  font-size: 11px !important;
  font-weight: 600 !important;
  color: #64748b !important;
}
#cgs-live-preview .cgs-step-label { font-size: 11px !important; color: #64748b !important; }
#cgs-live-preview .cgs-form-step .cgs-step-heading,
#cgs-live-preview .cgs-step-title { font-size: 13px !important; font-weight: 700 !important; color: #1e293b !important; }
</style>

<style id="cgs-style-layout-fix">
.cgs-builder-styles-col .cgs-style-grid-2 {
  display: grid !important;
  grid-template-columns: 1fr !important;
  gap: 12px !important;
}
.cgs-builder-styles-col .cgs-style-box {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  margin: 0 !important;
}
.cgs-builder-styles-col .cgs-style-box h4 {
  margin: 0 0 10px;
  font-size: 13px;
  color: #1a237e;
  border-bottom: 1px solid #eef2ff;
  padding-bottom: 6px;
}
.cgs-builder-styles-col .cgs-style-grid-inner {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 8px 12px !important;
  align-items: end;
}
.cgs-builder-styles-col .cgs-style-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin: 0 !important;
}
.cgs-builder-styles-col .cgs-style-row label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin: 0;
}
.cgs-builder-styles-col .cgs-style-row select,
.cgs-builder-styles-col .cgs-style-row input[type="text"],
.cgs-builder-styles-col .cgs-style-row input[type="number"] {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box;
  min-height: 34px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  padding: 4px 8px;
}
.cgs-builder-styles-col .cgs-style-row input[type="color"] {
  width: 100%;
  height: 34px;
  padding: 2px;
  border-radius: 8px;
}
</style>


<style id="cgs-preview-interact-fix">
#cgs-live-preview .cgs-drag-grip {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  background: #eef2ff;
  color: #3949ab;
  font-size: 12px;
  cursor: grab !important;
  z-index: 6;
  user-select: none;
  line-height: 1;
}
#cgs-live-preview .cgs-field-group,
#cgs-live-preview .cgs-field-card {
  position: relative !important;
}
#cgs-live-preview .cgs-field-group input,
#cgs-live-preview .cgs-field-group select,
#cgs-live-preview .cgs-field-group textarea,
#cgs-live-preview .cgs-field-group button {
  pointer-events: auto !important;
  position: relative;
  z-index: 3;
}
#cgs-live-preview .cgs-resize-handle {
  pointer-events: auto !important;
  z-index: 7 !important;
}
/* cgs-is-sorting دیگر ورودی‌ها را قفل نمی‌کند */
</style>


<style id="cgs-preview-unlock">
/* قفل‌شکن پیش‌نمایش: ورودی و تغییر عرض باید همیشه کار کنند */
#cgs-live-preview .cgs-field-group,
#cgs-live-preview .cgs-field-card {
  pointer-events: auto !important;
  position: relative !important;
}
#cgs-live-preview .cgs-field-group input,
#cgs-live-preview .cgs-field-group select,
#cgs-live-preview .cgs-field-group textarea,
#cgs-live-preview .cgs-field-group button,
#cgs-live-preview input,
#cgs-live-preview select,
#cgs-live-preview textarea {
  pointer-events: auto !important;
  position: relative !important;
  z-index: 10 !important;
  cursor: auto !important;
  -webkit-user-select: text !important;
  user-select: text !important;
}
#cgs-live-preview .cgs-resize-handle {
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 12px !important;
  cursor: ew-resize !important;
  z-index: 30 !important;
  pointer-events: auto !important;
  background: transparent !important;
}
#cgs-live-preview .cgs-field-group:hover .cgs-resize-handle {
  background: rgba(26, 35, 126, 0.18) !important;
}
#cgs-live-preview .cgs-drag-grip {
  z-index: 15 !important;
  pointer-events: auto !important;
}
/* Sortable fallback: هیچ لایهٔ تمام‌صفحه روی پیش‌نمایش نباشد */
#cgs-live-preview .sortable-ghost,
#cgs-live-preview .cgs-sortable-ghost {
  opacity: 0.5;
}
</style>


<style id="cgs-steps-unlock">
/* قفل‌شکن مراحل فرم — همیشه قابل تایپ و انتخاب */
.cgs-step-meta-panel,
.cgs-step-meta-panel * { pointer-events: auto !important; }
.cgs-step-card { cursor: default !important; }
.cgs-step-card input,
.cgs-step-card select,
.cgs-step-card textarea,
.cgs-step-card button {
  pointer-events: auto !important;
  user-select: text !important;
  -webkit-user-select: text !important;
  position: relative !important;
  z-index: 20 !important;
  opacity: 1 !important;
}
.cgs-step-drag-handle { cursor: grab !important; z-index: 5; }
body.cgs-is-sorting .cgs-step-card input,
body.cgs-is-sorting .cgs-step-card select { pointer-events: auto !important; }
</style>












<style id="cgs-appearance-layout-fix">
/* چیدمان ظاهر فرم — فقط UI */
.cgs-btn-placement-box .cgs-style-grid-inner {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 14px !important;
  align-items: stretch !important;
}
.cgs-btn-placement-box .cgs-panel {
  height: 100%;
  box-sizing: border-box;
}
.cgs-btn-placement-box .cgs-panel[style*="grid-column"],
.cgs-btn-placement-box .cgs-style-grid-inner > .cgs-panel:last-child {
  grid-column: 1 / -1 !important;
}
.cgs-btn-placement-box .cgs-style-pair {
  display: grid !important;
  grid-template-columns: repeat(3, 1fr) !important;
  gap: 12px !important;
  margin: 0 14px 12px !important;
}
@media (max-width: 900px) {
  .cgs-btn-placement-box .cgs-style-grid-inner,
  .cgs-btn-placement-box .cgs-style-pair { grid-template-columns: 1fr !important; }
}

/* پس‌زمینه فرم */
.cgs-style-box:has(#st-form-bg) .cgs-style-grid-inner,
div.cgs-style-box:has(h4) {
  /* fallback below */
}
#st-form-bg {
  min-width: 0 !important;
  flex: 1 1 200px !important;
}
#st-form-bg-browse, #st-form-bg-clear {
  flex: 0 0 auto !important;
}
.cgs-style-row:has(#st-form-bg) > div {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
  align-items: center !important;
  width: 100% !important;
}
.cgs-style-row:has(#st-form-bg-op),
.wrap.cgs-fb-wrap .cgs-style-row:has(input#st-form-bg-op) {
  max-width: 160px;
}

/* دکمه شیشه‌ای و صدا */
.cgs-style-box:has(#st-btn-sound) .cgs-style-grid-inner,
.wrap.cgs-fb-wrap #st-btn-sound {
  max-width: 100%;
}
.wrap.cgs-fb-wrap .cgs-style-row:has(#st-sound-volume) {
  display: grid !important;
  grid-template-columns: 1fr auto !important;
  gap: 10px 14px !important;
  align-items: center !important;
}
.wrap.cgs-fb-wrap .cgs-style-row:has(#st-sound-volume) label { grid-column: 1 / -1; }
.wrap.cgs-fb-wrap #st-sound-volume { width: 100% !important; max-width: 280px; }
.wrap.cgs-fb-wrap #st-sound-preview { justify-self: start; }

/* مختصات عددی دکمه — مرحله ۲ */
.cgs-btn-coords-row {
  display: grid !important;
  grid-template-columns: repeat(4, 1fr) !important;
  gap: 10px !important;
  margin: 0 14px 12px !important;
}
@media (max-width: 700px) {
  .cgs-btn-coords-row { grid-template-columns: 1fr 1fr !important; }
}
</style>



<style id="cgs-btn-tpl-gallery-css">
.cgs-tpl-grid {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)) !important;
  gap: 10px !important;
  padding: 0 16px 14px !important;
}
.cgs-tpl-swatch {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 10px 6px !important;
  border: 2px solid #e2e8f0 !important;
  border-radius: 12px !important;
  background: #f8fafc !important;
  cursor: pointer !important;
  transition: border-color .15s, box-shadow .15s;
}
.cgs-tpl-swatch.is-selected {
  border-color: #1a237e !important;
  box-shadow: 0 0 0 3px rgba(26,35,126,.15) !important;
  background: #eef2ff !important;
}
.cgs-tpl-name { font-size: 11px; color: #475569; font-weight: 600; }
.cgs-tpl-demo {
  display: inline-block;
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 8px;
  border: none;
  color: #fff;
  background: #1a237e;
  pointer-events: none;
}
.cgs-demo-flat { background: #1a237e; }
.cgs-demo-solid { background: #1a237e; }
.cgs-demo-outline { background: transparent; color: #1a237e; border: 2px solid #1a237e; }
.cgs-demo-soft { background: rgba(26,35,126,.12); color: #1a237e; }
.cgs-demo-glass { background: rgba(255,255,255,.5); color: #1a237e; border: 1px solid rgba(255,255,255,.8); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.cgs-demo-glass3d { background: linear-gradient(145deg,#fff,#e8eaf6); color: #1a237e; box-shadow: 0 6px 16px rgba(15,23,42,.15), inset 0 1px 0 #fff; }
.cgs-demo-neon { background: #1a237e; box-shadow: 0 0 10px #1a237e, 0 0 20px rgba(26,35,126,.5); }
.cgs-demo-raised3d { background: linear-gradient(180deg,#3f51b5,#1a237e); border-bottom: 3px solid rgba(0,0,0,.3); }
.cgs-demo-pill { border-radius: 999px; background: #1a237e; }
.cgs-demo-gradient { background: linear-gradient(135deg,#667eea,#764ba2); }
.cgs-demo-shadow { background: #1a237e; box-shadow: 0 8px 20px rgba(26,35,126,.45); }
.cgs-demo-minimal { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.cgs-demo-success { background: #16a34a; }
.cgs-demo-danger { background: #dc2626; }
.cgs-demo-warning { background: #f59e0b; color: #78350f; }
.cgs-demo-dark { background: #0f172a; }
.cgs-demo-bordered3d { background: #eef2ff; color: #1a237e; border: 2px solid #1a237e; box-shadow: 3px 3px 0 #1a237e; }
.cgs-demo-glow_pulse { background: #3949ab; box-shadow: 0 0 16px rgba(57,73,171,.7); }
.cgs-demo-ice { background: linear-gradient(180deg,#e0f2fe,#bae6fd); color: #0c4a6e; }
.cgs-demo-premium { background: linear-gradient(135deg,#1a237e,#c5a46f); }

.cgs-vol-row {
  display: grid !important;
  grid-template-columns: 1fr auto !important;
  gap: 12px !important;
  align-items: center !important;
}
.cgs-vol-row input[type=range] {
  width: 100% !important;
  max-width: none !important;
  height: 8px !important;
  accent-color: #1a237e;
}
.cgs-sound-grid {
  grid-template-columns: 1fr 1fr !important;
}
</style>


<style id="cgs-appearance-sections">
.cgs-appear-section {
  margin: 18px 0 24px !important;
  border: 2px solid #c5cae9 !important;
  border-radius: 16px !important;
  background: #f8fafc !important;
  padding: 0 0 8px !important;
  overflow: hidden;
}
.cgs-appear-section > .cgs-appear-section-title {
  margin: 0 !important;
  padding: 12px 16px !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  color: #fff !important;
  background: linear-gradient(90deg, #1a237e, #3949ab) !important;
}
.cgs-appear-section.cgs-section-btns > .cgs-appear-section-title {
  background: linear-gradient(90deg, #0f766e, #14b8a6) !important;
}
.cgs-appear-section .cgs-style-box {
  margin: 12px 12px 0 !important;
  border-radius: 12px !important;
}
.cgs-btn-live-demo {
  margin-top: 8px;
  padding: 10px;
  background: #eef2ff;
  border-radius: 10px;
  text-align: center;
}
.cgs-btn-tpl-select { font-size: 13px !important; }
</style>

<style id="cgs-appearance-unified">
/* ===== چیدمان یکپارچه ظاهر فرم ===== */
.wrap.cgs-fb-wrap .cgs-style-box {
  margin: 14px 0 !important;
  padding: 0 !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 14px !important;
  background: #fff !important;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(15,23,42,.04);
}
.wrap.cgs-fb-wrap .cgs-style-box > h4 {
  margin: 0 !important;
  padding: 11px 16px !important;
  font-size: 13.5px !important;
  font-weight: 700 !important;
  color: #1a237e !important;
  background: linear-gradient(90deg,#eef2ff,#f8fafc) !important;
  border-bottom: 1px solid #e2e8f0 !important;
}
.wrap.cgs-fb-wrap .cgs-style-grid-inner {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 12px 16px !important;
  padding: 14px 16px !important;
  align-items: start !important;
}
@media (max-width: 900px) {
  .wrap.cgs-fb-wrap .cgs-style-grid-inner { grid-template-columns: 1fr !important; }
}
.wrap.cgs-fb-wrap .cgs-style-row {
  display: flex !important;
  flex-direction: column !important;
  gap: 5px !important;
  margin: 0 !important;
  min-width: 0 !important;
}
.wrap.cgs-fb-wrap .cgs-style-row > label {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  line-height: 1.3 !important;
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  margin: 0 !important;
}
.wrap.cgs-fb-wrap .cgs-style-row input[type="text"],
.wrap.cgs-fb-wrap .cgs-style-row input[type="number"],
.wrap.cgs-fb-wrap .cgs-style-row input[type="url"],
.wrap.cgs-fb-wrap .cgs-style-row input[type="color"],
.wrap.cgs-fb-wrap .cgs-style-row select,
.wrap.cgs-fb-wrap .cgs-style-row textarea {
  width: 100% !important;
  max-width: 100% !important;
  min-height: 36px !important;
  box-sizing: border-box !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  padding: 7px 10px !important;
  font-size: 13px !important;
  background: #fff !important;
}
.wrap.cgs-fb-wrap .cgs-style-row input[type="color"] { padding: 3px !important; height: 36px !important; }
.wrap.cgs-fb-wrap .cgs-unit {
  font-size: 10px !important;
  color: #64748b !important;
  background: #f1f5f9 !important;
  padding: 1px 6px !important;
  border-radius: 4px !important;
  font-weight: 600 !important;
}

/* محل قرارگیری / قالب دکمه */
.wrap.cgs-fb-wrap .cgs-btn-placement-box .cgs-style-grid-inner {
  grid-template-columns: 1fr 1fr !important;
}
.wrap.cgs-fb-wrap .cgs-btn-coords-row {
  display: grid !important;
  grid-template-columns: repeat(3, 1fr) !important;
  gap: 10px !important;
  margin: 0 !important;
  grid-column: 1 / -1 !important;
  padding: 0 16px 14px !important;
}
@media (max-width: 700px) {
  .wrap.cgs-fb-wrap .cgs-btn-coords-row { grid-template-columns: 1fr 1fr !important; }
}

/* عنوان + متن دکمه */
.wrap.cgs-fb-wrap .cgs-title-btn-grid {
  display: grid !important;
  grid-template-columns: 1.15fr 0.85fr !important;
  gap: 14px !important;
  padding: 14px 16px !important;
  align-items: stretch !important;
}
.wrap.cgs-fb-wrap .cgs-title-btn-grid .cgs-panel {
  display: flex !important;
  flex-direction: column !important;
  height: 100% !important;
}
.wrap.cgs-fb-wrap .cgs-title-btn-grid .cgs-style-pair {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 10px !important;
  margin: 0 14px 0 !important;
}
.wrap.cgs-fb-wrap .cgs-title-btn-grid .cgs-style-row {
  margin: 0 14px 10px !important;
}

/* پس‌زمینه: Browse کنار کادر */
.wrap.cgs-fb-wrap .cgs-bg-url-row {
  display: grid !important;
  grid-template-columns: 1fr auto auto !important;
  gap: 8px !important;
  align-items: center !important;
  grid-column: 1 / -1 !important;
}
.wrap.cgs-fb-wrap .cgs-bg-url-row input { width: 100% !important; }

/* مراحل کوچکتر */
.wrap.cgs-fb-wrap .cgs-step-card {
  min-width: 160px !important;
  max-width: 200px !important;
  padding: 10px 12px !important;
  font-size: 12px !important;
}
.wrap.cgs-fb-wrap .cgs-step-card h4,
.wrap.cgs-fb-wrap .cgs-step-card .cgs-step-card-title {
  font-size: 12.5px !important;
  margin: 0 0 6px !important;
}
.wrap.cgs-fb-wrap .cgs-step-card input,
.wrap.cgs-fb-wrap .cgs-step-card select {
  min-height: 32px !important;
  font-size: 12px !important;
  padding: 4px 8px !important;
}
.wrap.cgs-fb-wrap .cgs-step-card label {
  font-size: 11px !important;
  margin-bottom: 2px !important;
}
.wrap.cgs-fb-wrap .cgs-step-card .button,
.wrap.cgs-fb-wrap .cgs-step-card button {
  font-size: 11px !important;
  padding: 4px 8px !important;
  min-height: 28px !important;
}

/* پیش‌نمایش همیشه دیده شود */
.wrap.cgs-fb-wrap .cgs-builder-col-preview,
.wrap.cgs-fb-wrap #cgs-live-preview,
.wrap.cgs-fb-wrap .cgs-preview-panel {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  height: auto !important;
  max-height: none !important;
  overflow: visible !important;
  position: relative !important;
  z-index: 5 !important;
}
</style>

<style id="cgs-title-btn-layout">
.cgs-title-btn-section { margin: 14px 0; }
.cgs-title-btn-section > h4 {
  margin: 0 0 10px;
  font-size: 14px;
  color: #1a237e;
  font-weight: 700;
}
.cgs-title-btn-grid {
  display: grid !important;
  grid-template-columns: 1.2fr 0.9fr !important;
  gap: 14px !important;
  align-items: start !important;
  direction: rtl;
}
@media (max-width: 900px) {
  .cgs-title-btn-grid { grid-template-columns: 1fr !important; }
}
.cgs-title-btn-grid .cgs-panel {
  margin: 0 !important;
  padding: 0 0 14px !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 14px !important;
  background: #fff !important;
  box-shadow: 0 2px 10px rgba(15,23,42,0.05);
  overflow: hidden;
}
.cgs-form-title-style { background: #fafafe !important; }
.cgs-form-btn-texts { background: #f6fdf8 !important; }
.cgs-panel-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 700;
  margin: 0 0 8px;
}
.cgs-head-blue { background: linear-gradient(90deg,#eef2ff,#e8eaf6); color: #1a237e; border-bottom: 1px solid #c5cae9; }
.cgs-head-green { background: linear-gradient(90deg,#ecfdf5,#d1fae5); color: #166534; border-bottom: 1px solid #a7f3d0; }
.cgs-panel-ico { font-size: 15px; }
.cgs-panel-desc {
  margin: 0 14px 12px;
  font-size: 11.5px;
  color: #64748b;
  line-height: 1.5;
}
.cgs-title-btn-grid .cgs-style-row {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  margin: 0 14px 10px !important;
  padding: 0 !important;
}
.cgs-title-btn-grid .cgs-style-row > label {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-direction: row-reverse;
  justify-content: flex-end;
}
.cgs-title-btn-grid .cgs-unit {
  font-size: 10px !important;
  color: #94a3b8 !important;
  background: #f1f5f9;
  padding: 1px 5px;
  border-radius: 4px;
  font-weight: 600;
}
.cgs-title-btn-grid .cgs-style-row input[type="text"],
.cgs-title-btn-grid .cgs-style-row input[type="number"],
.cgs-title-btn-grid .cgs-style-row select {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  padding: 7px 10px !important;
  font-size: 13px !important;
  background: #fff !important;
  min-height: 36px;
}
.cgs-title-btn-grid .cgs-style-row input[type="color"] {
  width: 100% !important;
  height: 36px !important;
  padding: 3px !important;
  border-radius: 8px !important;
  border: 1px solid #cbd5e1 !important;
  cursor: pointer;
}
.cgs-style-pair {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 8px !important;
  margin: 0 14px 0 !important;
}
.cgs-style-pair .cgs-style-row { margin-left: 0 !important; margin-right: 0 !important; }
</style>

<style id="cgs-style-unit-align">
.cgs-style-row {
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  gap: 4px !important;
  margin-bottom: 10px !important;
}
.cgs-style-row > label {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  margin: 0 !important;
}
.cgs-style-row .cgs-unit-row {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}
.cgs-style-row .cgs-unit {
  font-size: 11px !important;
  color: #64748b !important;
  font-weight: 600 !important;
  min-width: 22px;
}
.cgs-style-row input[type="number"],
.cgs-style-row input[type="text"],
.cgs-style-row select {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
}
.cgs-style-row input[type="number"] {
  max-width: 100px !important;
}
</style>

<style id="cgs-fb-tabs-ui">
.cgs-fb-tabs-bar { display: none !important; }

/* بالا: فقط فیلدها + ظاهر */
.wrap.cgs-fb-wrap .cgs-builder-grid,
.wrap.cgs-fb-wrap .cgs-fb-grid {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 16px !important;
  align-items: start !important;
  width: 100% !important;
}

/* پایین: پیش‌نمایش تمام‌عرض صفحه */
.wrap.cgs-fb-wrap .cgs-builder-col-preview,
.wrap.cgs-fb-wrap .cgs-preview-fullwidth-inner,
#cgs-preview-fullwidth {
  display: block !important;
  visibility: visible !important;
  width: 100% !important;
  max-width: 100% !important;
  min-height: 420px !important;
  margin: 20px 0 40px !important;
  float: none !important;
  clear: both !important;
  box-sizing: border-box !important;
}
.wrap.cgs-fb-wrap .cgs-preview-panel,
.wrap.cgs-fb-wrap .cgs-builder-col-preview > .cgs-panel {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
}
.wrap.cgs-fb-wrap #cgs-live-preview,
.wrap.cgs-fb-wrap .cgs-preview-box {
  display: block !important;
  width: 100% !important;
  max-width: 100% !important;
  min-height: 400px !important;
  margin: 0 !important;
  box-sizing: border-box !important;
  pointer-events: auto !important;
}
.wrap.cgs-fb-wrap #cgs-live-preview-toolbar,
.wrap.cgs-fb-wrap #cgs-lp-settings-box {
  display: block !important;
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  margin: 0 0 12px !important;
}
.wrap.cgs-fb-wrap #cgs-live-preview-toolbar {
  display: flex !important;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  padding: 12px 14px;
  background: #eef2ff;
  border: 1px solid #c5cae9;
  border-radius: 10px;
}

/* ورودی‌ها همیشه آزاد — درگ فقط از grip */
#cgs-live-preview input,
#cgs-live-preview select,
#cgs-live-preview textarea,
#cgs-live-preview button {
  pointer-events: auto !important;
  opacity: 1 !important;
  z-index: 40 !important;
  position: relative !important;
}
#cgs-live-preview .cgs-drag-grip {
  position: absolute !important;
  top: 6px; right: 6px;
  z-index: 60 !important;
  cursor: grab !important;
  pointer-events: auto !important;
  background: #1a237e !important;
  color: #fff !important;
  border: none !important;
  padding: 4px 8px !important;
  border-radius: 6px !important;
  font-size: 13px !important;
  line-height: 1 !important;
  user-select: none !important;
}
#cgs-live-preview .cgs-resize-handle {
  position: absolute !important;
  left: 0; top: 0; bottom: 0;
  width: 14px !important;
  cursor: ew-resize !important;
  z-index: 55 !important;
  pointer-events: auto !important;
  background: linear-gradient(90deg, rgba(26,35,126,.25), transparent) !important;
}
#cgs-live-preview .cgs-field-group,
#cgs-live-preview .cgs-field-card {
  position: relative !important;
  pointer-events: auto !important;
  box-sizing: border-box !important;
}
#cgs-fb-plugins-panel { display: none !important; }
@media (max-width: 900px) {
  .wrap.cgs-fb-wrap .cgs-builder-grid { grid-template-columns: 1fr !important; }
}
</style>








<div class="wrap cgs-admin-wrap cgs-fb-wrap">

<!-- تب‌بندی غیرفعال شد تا پیش‌نمایش خراب نشود — چیدمان کلاسیک -->
<div class="cgs-fb-classic-note" style="margin:0 0 12px;padding:10px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;font-size:12.5px;color:#065f46;">
  <strong>فیلدها</strong> و <strong>ظاهر</strong> بالا — <strong>پیش‌نمایش زنده</strong> تمام‌عرض زیر همه باکس‌ها
</div>




<details class="cgs-help" style="margin:12px 0;">
  <summary>راهنمای سریع فرم‌ساز</summary>
  <div class="cgs-help-body">
    <ol>
      <li><strong>افزودن فیلد:</strong> دکمه افزودن → برچسب را بنویسید → نوع فیلد را انتخاب کنید.</li>
      <li><strong>ماتریس داده:</strong> نوع «ماتریس داده» را انتخاب کنید؛ ستون، ردیف، رنگ و محاسبه را تنظیم کنید.</li>
      <li><strong>تلفن ثابت:</strong> برچسب را «تلفن ثابت» بگذارید؛ کد شهرستان خودکار سمت چپ می‌آید.</li>
      <li><strong>پیش‌نمایش:</strong> سمت چپ/وسط صفحه زنده است؛ ذخیره چیدمان را بعد از جابجایی فیلدها بزنید.</li>
    </ol>
    <div class="cgs-help-tip">💡 اگر نوع ماتریس را نمی‌بینید، Ctrl+Shift+R بزنید یا افزونه را دوباره بارگذاری کنید.</div>
  </div>
</details>


    
<style id="cgs-fb-hero-fix">
/* جلوگیری از کادر سفید خالی و تزریق افزونه‌های SEO داخل هیرو */
.cgs-fb-hero {
  position: relative !important;
  overflow: hidden !important;
  padding: 18px 22px !important;
  border-radius: 16px !important;
  background: linear-gradient(135deg, #1a237e 0%, #283593 55%, #3949ab 100%) !important;
  color: #fff !important;
  margin-bottom: 16px !important;
}
.cgs-fb-hero h1,
.cgs-fb-hero .cgs-fb-hero-title {
  color: #ffffff !important;
  margin: 0 0 8px !important;
  font-size: 1.35rem !important;
  font-weight: 800 !important;
  line-height: 1.4 !important;
  position: relative;
  z-index: 2;
}
.cgs-fb-hero p,
.cgs-fb-hero .cgs-fb-hero-desc {
  color: rgba(255,255,255,0.92) !important;
  margin: 0 !important;
  font-size: 0.92rem !important;
  position: relative;
  z-index: 2;
}
/* مخفی‌سازی تزریق Rank Math / Yoast / SEOPress و فیلدهای اضافی داخل هیرو */
.cgs-fb-hero input,
.cgs-fb-hero textarea,
.cgs-fb-hero select,
.cgs-fb-hero .rank-math-title,
.cgs-fb-hero [class*="rank-math"],
.cgs-fb-hero [class*="yoast"],
.cgs-fb-hero [class*="seopress"],
.cgs-fb-hero [class*="aioseo"],
.cgs-fb-hero .edit-seo,
.cgs-fb-hero [contenteditable="true"] {
  display: none !important;
  visibility: hidden !important;
  height: 0 !important;
  max-height: 0 !important;
  overflow: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
  position: absolute !important;
  left: -9999px !important;
}
body.cgs-fb-page .rank-math-toolbar,
body.cgs-fb-page #rank-math-metabox-wrapper {
  /* فقط داخل هیرو مسدود شد؛ متاباکس جدا دست نخورده */
}
</style>
<script>
(function(){
  document.body && document.body.classList.add('cgs-fb-page');
  function scrubHero(){
    var h = document.querySelector('.cgs-fb-hero');
    if (!h) return;
    Array.prototype.slice.call(h.children).forEach(function(ch){
      var tag = (ch.tagName||'').toLowerCase();
      if (tag === 'h1' || tag === 'p' || (ch.classList && (ch.classList.contains('cgs-fb-hero-title') || ch.classList.contains('cgs-fb-hero-desc')))) return;
      // هر چیز تزریقی (مثلاً کادر سفید SEO) را حذف کن
      try { ch.remove(); } catch(e) { ch.style.display = 'none'; }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scrubHero);
  else scrubHero();
  setTimeout(scrubHero, 300);
  setTimeout(scrubHero, 1200);
})();
</script>

    <div class="cgs-fb-hero">
        <div class="cgs-fb-hero-title">فرم‌ساز داینامیک شهر قسط</div>
        <div class="cgs-fb-hero-desc">طراحی فیلدها، مراحل، ظاهر و پیش‌نمایش زنده — قالب‌ها در دیتابیس ذخیره می‌شوند.</div>
    </div>
    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'form_builder.main' ); } ?>

    <div class="cgs-type-pills">
        <?php foreach ( $types as $tk => $tlabel ) :
            $url = admin_url( 'admin.php?page=cgs-form-builder&type=' . urlencode( $tk ) );
        ?>
            <a href="<?php echo esc_url( $url ); ?>" class="<?php echo $tk === $current_type ? 'is-active' : ''; ?>"><?php echo esc_html( is_array($tlabel) ? ($tlabel['label']??$tk) : $tlabel ); ?></a>
        <?php endforeach; ?>
    </div>

    
    <div id="cgs-tpl-version-panel" class="cgs-fb-card" style="display:none;margin-bottom:16px;">
        <h2 style="margin-top:0;">مدیریت نسخه‌های قالب</h2>
        <p class="description">نسخه‌های مرتبط با قالب انتخاب‌شده. می‌توانید نسخه را اعمال یا حذف کنید.</p>
        <div id="cgs-tpl-versions" style="overflow-x:auto;"></div>
    </div>

    <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'form_builder.templates' ); } ?>
    <div class="cgs-fb-toolbar">
        <strong style="color:#1a237e;">قالب:</strong>
        <select id="cgs-template-select" class="cgs-template-gallery-select" style="min-width:320px;max-width:100%;width:100%;max-width:480px;padding:8px 12px;border-radius:10px;border:1px solid #c5cae9;">
            <option value="">— انتخاب قالب —</option>
            <?php
            if ( class_exists( 'CGS_Form_Templates' ) ) {
                CGS_Form_Templates::maybe_seed();
                $all_tpl = CGS_Form_Templates::all();
                $type_labels = array(
                    'representative' => 'نماینده',
                    'seller'         => 'فروشنده',
                    'marketer'       => 'بازاریاب',
                    'investor'       => 'سرمایه‌گذار',
                    'applicant'      => 'متقاضی اعتبار',
                );
                $grouped = array( 'custom' => array(), 'builtin' => array() );
                foreach ( $all_tpl as $tpl ) {
                    $src = ( ( $tpl['source'] ?? '' ) === 'custom' ) ? 'custom' : 'builtin';
                    $tk  = $tpl['type_key'] ?? 'other';
                    if ( ! isset( $grouped[ $src ][ $tk ] ) ) {
                        $grouped[ $src ][ $tk ] = array();
                    }
                    $grouped[ $src ][ $tk ][] = $tpl;
                }
                // سفارشی first
                if ( ! empty( $grouped['custom'] ) ) {
                    echo '<optgroup label="★ قالب‌های سفارشی من">';
                    foreach ( $grouped['custom'] as $tk => $list ) {
                        $tl = $type_labels[ $tk ] ?? $tk;
                        foreach ( $list as $tpl ) {
                            $ver = (int) ( $tpl['version_num'] ?? 1 );
                            $label = sprintf( '[%s] %s (v%d)', $tl, $tpl['name'] ?? '', $ver );
                            echo '<option value="' . esc_attr( $tpl['id'] ) . '" data-source="custom" data-type="' . esc_attr( $tk ) . '">' . esc_html( $label ) . '</option>';
                        }
                    }
                    echo '</optgroup>';
                }
                if ( ! empty( $grouped['builtin'] ) ) {
                    echo '<optgroup label="قالب‌های آماده سیستم">';
                    foreach ( $grouped['builtin'] as $tk => $list ) {
                        $tl = $type_labels[ $tk ] ?? $tk;
                        foreach ( $list as $tpl ) {
                            $ver = (int) ( $tpl['version_num'] ?? 1 );
                            $label = sprintf( '[%s] %s', $tl, $tpl['name'] ?? '' );
                            echo '<option value="' . esc_attr( $tpl['id'] ) . '" data-source="builtin" data-type="' . esc_attr( $tk ) . '">' . esc_html( $label ) . '</option>';
                        }
                    }
                    echo '</optgroup>';
                }
                if ( empty( $all_tpl ) ) {
                    echo '<option value="" disabled>قالبی یافت نشد — ذخیره یا فعال‌سازی مجدد افزونه</option>';
                }
            }
            ?>
        </select>
        <label style="font-size:12px;display:flex;align-items:center;gap:4px;"><input type="checkbox" id="cgs-tpl-replace" value="1" checked> جایگزینی فیلدها</label>
        <button type="button" id="cgs-btn-apply-template" class="cgs-btn-admin cgs-btn-admin-primary">اعمال قالب</button>
        <button type="button" id="cgs-btn-tpl-versions" class="cgs-btn-admin cgs-btn-admin-warning">نسخه‌ها</button>
        <button type="button" id="cgs-btn-delete-template" class="cgs-btn-admin cgs-btn-admin-danger">حذف قالب</button>
        <span style="width:1px;height:28px;background:#e2e8f0;margin:0 4px;"></span>
        <input type="text" id="cgs-template-name" placeholder="نام قالب جدید...">
        <label style="font-size:12px;display:flex;align-items:center;gap:4px;"><input type="checkbox" id="cgs-tpl-new-version" value="1"> نسخه جدید از انتخاب‌شده</label>
        <button type="button" id="cgs-btn-save-template" class="cgs-btn-admin cgs-btn-admin-success">ذخیره قالب</button>
        <span id="cgs-tpl-msg" style="font-size:13px;"></span>
    </div>

    <div class="cgs-panel cgs-fb-card" style="margin:12px 0;padding:10px 14px;"><strong>پیش‌نمایش زنده</strong> در ستون چپ (یا پایین در موبایل) همیشه نمایش داده می‌شود. پس از افزودن/ویرایش فیلد، صفحه را رفرش کنید تا پیش‌نمایش به‌روز شود. تنظیمات ظاهری پس از «ذخیره ظاهر فرم» روی پیش‌نمایش اعمال می‌شوند.</div>
<div class="cgs-shortcode-box" style="background:#f0f4ff;border:1px solid #c5cae9;border-radius:10px;padding:16px 20px;margin:16px 0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <strong style="white-space:nowrap;">شورت‌کد این فرم:</strong>
        <code id="cgs-shortcode-text" style="background:#fff;padding:8px 14px;border-radius:6px;font-size:14px;border:1px solid #ddd;flex:1;min-width:200px;">[cgs_form type="<?php echo esc_attr($current_type); ?>"]</code>
        <button type="button" id="cgs-copy-shortcode" class="button button-primary" style="white-space:nowrap;">
            <span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> کپی شورت‌کد
        </button>
        <span id="cgs-copy-msg" style="color:green;display:none;">کپی شد ✓</span>
    </div>


    <?php
    // Step names & educational images — ادغام فیلدها + متای ذخیره‌شده
    $all_fields = $fields;
    $used_steps = array();
    foreach ( $all_fields as $ff ) {
        $sn = (int) ( $ff['step_number'] ?? 1 );
        if ( $sn >= 1 ) {
            $used_steps[ $sn ] = true;
        }
    }
    if ( is_array( $step_meta ) ) {
        foreach ( array_keys( $step_meta ) as $sk ) {
            $sn = (int) $sk;
            if ( $sn >= 1 && $sn <= 20 ) {
                $used_steps[ $sn ] = true;
            }
        }
    }
    ksort( $used_steps );
    ?>
    
    <div class="cgs-panel cgs-advanced-template-settings" style="margin:14px 0;padding:16px;border:1px solid #c5cae9;border-radius:14px;background:linear-gradient(135deg,#fff,#f8fafc);box-shadow:0 4px 18px rgba(26,35,126,.06);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1.05rem;color:#1a237e;">⚙️ تنظیمات پیشرفته قالب</h2>
            <details class="cgs-help" style="max-width:420px;">
                <summary style="cursor:pointer;color:#3949ab;font-size:12px;font-weight:600;">راهنما</summary>
                <div style="font-size:12px;line-height:1.7;padding:8px 0;color:#475569;">
                    قالب را ذخیره کنید تا بعداً بتوانید همان ساختار فیلدها و مراحل را با یک کلیک بازیابی کنید.
                    «ذخیره با همین نام» نسخه قبلی را به‌روز می‌کند. قالب خالی برای شروع از صفر است.
                </div>
            </details>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
            <button type="button" class="button button-primary" id="cgs-tpl-save-as" style="border-radius:10px;padding:8px 12px;">💾 ذخیره قالب جدید</button>
            <button type="button" class="button" id="cgs-tpl-overwrite" style="border-radius:10px;padding:8px 12px;">✏️ ذخیره با همین نام</button>
            <button type="button" class="button" id="cgs-tpl-load-selected" style="border-radius:10px;padding:8px 12px;">📥 بارگذاری قالب انتخابی</button>
            <button type="button" class="button" id="cgs-tpl-blank" style="border-radius:10px;padding:8px 12px;">🆕 قالب خالی</button>
            <button type="button" class="button" id="cgs-tpl-export-json" style="border-radius:10px;padding:8px 12px;">📤 خروجی JSON</button>
            <button type="button" class="button" id="cgs-tpl-import-json" style="border-radius:10px;padding:8px 12px;">📥 ورود JSON</button>
        </div>
        <p id="cgs-adv-tpl-msg" style="margin:10px 0 0;font-size:12px;"></p>
        <input type="file" id="cgs-tpl-import-file" accept="application/json,.json" style="display:none;">
    </div>
<div class="cgs-panel cgs-step-meta-panel" style="margin:16px 0;padding:14px;" data-cgs-section="steps">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1.1rem;">مراحل فرم — نام، آیکن، ستون، فایل</h2>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <button type="button" id="cgs-add-step-btn" class="cgs-btn-admin cgs-btn-admin-primary">+ مرحله</button>
                <button type="button" id="cgs-save-step-meta" class="cgs-btn-admin cgs-btn-admin-success">ذخیره مراحل</button>
                <span id="cgs-step-meta-msg"></span>
            </div>
        </div>
        <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'form_builder.steps' ); } ?>
        <p class="description" style="margin:0 0 10px;font-size:12px;">با «+ مرحله» کارت جدید بسازید. نام/آیکن/ستون را ویرایش کنید. با ☰ جابه‌جا کنید. حذف با تأیید. در پایان «ذخیره مراحل» را بزنید.</p>
        <div id="cgs-step-meta-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
            <?php
            $cgs_step_icons = array(
                'user'=>'کاربر','users'=>'کاربران','phone'=>'تلفن','mobile'=>'موبایل','id-card'=>'کارت ملی',
                'map'=>'آدرس','calendar'=>'تاریخ','bank'=>'بانک','file'=>'فایل','camera'=>'تصویر',
                'lock'=>'امنیت','home'=>'خانه','mail'=>'ایمیل','building'=>'شرکت','money'=>'مالی',
                'shield'=>'تضمین','edit'=>'ویرایش','star'=>'ستاره','check'=>'تأیید','success'=>'موفقیت',
            );
            if ( empty( $used_steps ) ) {
                echo '<p class="cgs-step-empty-msg" style="grid-column:1/-1;color:#666;">هنوز مرحله‌ای نیست. دکمه «+ مرحله» را بزنید.</p>';
            } else {
                foreach ( array_keys( $used_steps ) as $sn ) {
                    $meta = isset( $step_meta[ $sn ] ) ? $step_meta[ $sn ] : ( isset( $step_meta[ (string) $sn ] ) ? $step_meta[ (string) $sn ] : array() );
                    $sname = $meta['name'] ?? '';
                    $sicon = $meta['icon'] ?? '';
                    $sicon_url = $meta['icon_url'] ?? '';
                    $scols = isset( $meta['columns'] ) ? max(1,min(6,(int)$meta['columns'])) : 2;
                    $files = array();
                    if ( ! empty( $meta['files'] ) && is_array( $meta['files'] ) ) $files = $meta['files'];
                    elseif ( ! empty( $meta['image'] ) ) $files[] = array( 'url' => $meta['image'], 'type' => 'image' );
                    ?>
                    <div class="cgs-step-card" data-step="<?php echo (int)$sn; ?>" style="border:1px solid #e2e8f0;border-radius:10px;padding:8px 10px;background:#fafbff;position:relative;font-size:12px;cursor:default;">
                        <div class="cgs-step-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #eef2ff;">
                            <span class="cgs-step-drag-handle" title="جابه‌جایی" style="cursor:grab;color:#94a3b8;font-size:16px;user-select:none;">☰</span>
                            <div class="cgs-step-card-title" style="font-weight:800;color:#1a237e;flex:1;text-align:center;">مرحله <?php echo (int)$sn; ?></div>
                            <button type="button" class="cgs-delete-step button button-small" data-step="<?php echo (int)$sn; ?>" style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:2px 10px;font-size:11px;">حذف</button>
                        </div>
                        <label style="font-size:12px;display:block;">نام نمایشی</label>
                        <input type="text" class="cgs-step-name" data-step="<?php echo (int)$sn; ?>" value="<?php echo esc_attr($sname); ?>" placeholder="مثلاً اطلاعات شخصی" style="width:100%;margin-bottom:6px;padding:6px 8px;border-radius:8px;border:1px solid #cbd5e1;">
                        <label style="font-size:12px;display:block;">آیکن</label>
                        <select class="cgs-step-icon" data-step="<?php echo (int)$sn; ?>" style="width:100%;margin-bottom:4px;padding:6px;border-radius:8px;">
                            <option value="">— بدون —</option>
                            <?php foreach ( $cgs_step_icons as $ic => $lab ) : ?>
                            <option value="<?php echo esc_attr($ic); ?>" <?php selected($sicon,$ic); ?>><?php echo esc_html($lab); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" class="cgs-step-icon-url" data-step="<?php echo (int)$sn; ?>" value="<?php echo esc_attr($sicon_url); ?>">
                        <div class="cgs-step-icon-preview" data-step="<?php echo (int)$sn; ?>" style="min-height:22px;margin:4px 0;">
                            <?php if ($sicon_url): ?><img src="<?php echo esc_url($sicon_url); ?>" style="height:22px;"><?php elseif ($sicon): ?><span class="cgs-icon cgs-icon-<?php echo esc_attr($sicon); ?>"></span><?php endif; ?>
                        </div>
                        <button type="button" class="cgs-btn-admin cgs-upload-step-icon" data-step="<?php echo (int)$sn; ?>" style="font-size:11px;margin-bottom:6px;">آیکن سفارشی</button>
                        <label style="font-size:12px;display:block;">تعداد ستون</label>
                        <div style="display:flex;gap:6px;margin-bottom:6px;">
                            <select class="cgs-step-columns" data-step="<?php echo (int)$sn; ?>" style="flex:1;padding:6px;border-radius:8px;">
                                <?php for ($ci=1;$ci<=6;$ci++): ?>
                                <option value="<?php echo $ci; ?>" <?php selected($scols,$ci); ?>><?php echo $ci; ?> ستون</option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" class="cgs-btn-admin cgs-apply-cols-all" data-step="<?php echo (int)$sn; ?>" style="font-size:11px;">به همه</button>
                        </div>
                        <label style="font-size:12px;display:block;">فایل آموزشی</label>
                        <div class="cgs-step-files" data-step="<?php echo (int)$sn; ?>">
                            <?php foreach ($files as $fobj):
                                $fu = esc_url($fobj['url']??''); $ft = esc_attr($fobj['type']??'image'); ?>
                            <div class="cgs-step-file-item" data-url="<?php echo $fu; ?>" data-type="<?php echo $ft; ?>" style="display:flex;gap:6px;align-items:center;margin:3px 0;">
                                <?php if ($ft==='pdf'||substr($fu,-4)==='.pdf'): ?><a href="<?php echo $fu; ?>" target="_blank">PDF</a>
                                <?php else: ?><img src="<?php echo $fu; ?>" style="height:26px;border-radius:4px;"><?php endif; ?>
                                <button type="button" class="cgs-btn-admin cgs-btn-admin-danger cgs-remove-step-file" style="font-size:10px;padding:2px 6px;">حذف</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="cgs-btn-admin cgs-add-step-file" data-step="<?php echo (int)$sn; ?>" style="font-size:11px;margin-top:4px;">+ تصویر/PDF</button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

        <div class="cgs-builder-grid cgs-fb-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;align-items:start;">

        <!-- ستون ۱: فیلدها -->
        <div class="cgs-builder-col-fields">
            <div class="cgs-panel cgs-fb-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h2 style="margin:0; font-size:1.2rem;">فیلدهای فرم</h2>
                    <button type="button" id="cgs-btn-add" class="cgs-btn-admin cgs-btn-admin-primary">+ افزودن فیلد</button>
                </div>

                <p style="margin:8px 0;"><button type="button" class="button" id="cgs-open-formula-help">📘 راهنمای فرمول‌نویسی پیشرفته (مثال تصویری)</button></p>
                <details class="cgs-help" id="cgs-formula-help-panel" style="margin:8px 0;display:none;">
                  <summary>راهنما: ماتریس داده، فرمول‌نویسی و محاسبات (با مثال تصویری)</summary>
                  <div class="cgs-help-body">
                    <p><strong>۱) ساخت ماتریس:</strong> افزودن فیلد → نوع «ماتریس داده» → عناوین ستون با کاما → ردیف اولیه → رنگ → ردیف محاسباتی.</p>
                    <p><strong>۲) انواع محاسبه (مثل اکسل روی هر ستون):</strong></p>
                    <ul>
                      <li><b>SUM جمع:</b> همه اعداد همان ستون با هم جمع می‌شوند.</li>
                      <li><b>AVG میانگین:</b> جمع ÷ تعداد سلول‌های عددی آن ستون.</li>
                      <li><b>COUNT تعداد:</b> چند سلول عددی پر شده است.</li>
                      <li><b>MIN / MAX:</b> کوچک‌ترین یا بزرگ‌ترین عدد ستون.</li>
                    </ul>
                    <p><strong>۳) مثال تصویری — محاسبه جمع (SUM):</strong></p>
                    <div style="overflow-x:auto;margin:10px 0;border-radius:12px;border:1px solid #c5cae9;box-shadow:0 4px 14px rgba(26,35,126,.08);">
                      <table style="width:100%;border-collapse:collapse;font-size:12.5px;text-align:center;font-family:Tahoma,sans-serif;">
                        <thead>
                          <tr style="background:#1a237e;color:#fff;">
                            <th style="padding:8px;">مبلغ کالا</th>
                            <th style="padding:8px;">هزینه ارسال</th>
                            <th style="padding:8px;">تخفیف</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr style="background:#fff;"><td style="padding:8px;border:1px solid #e2e8f0;">۱۰۰</td><td style="padding:8px;border:1px solid #e2e8f0;">۲۰</td><td style="padding:8px;border:1px solid #e2e8f0;">۵</td></tr>
                          <tr style="background:#f8fafc;"><td style="padding:8px;border:1px solid #e2e8f0;">۵۰</td><td style="padding:8px;border:1px solid #e2e8f0;">۱۰</td><td style="padding:8px;border:1px solid #e2e8f0;">۰</td></tr>
                          <tr style="background:#fff;"><td style="padding:8px;border:1px solid #e2e8f0;">۳۰</td><td style="padding:8px;border:1px solid #e2e8f0;">—</td><td style="padding:8px;border:1px solid #e2e8f0;">۲</td></tr>
                        </tbody>
                        <tfoot>
                          <tr style="background:#eef2ff;font-weight:700;">
                            <td style="padding:10px;border:1px solid #c5cae9;color:#1a237e;">جمع<br><span style="font-size:16px;">۱۸۰</span></td>
                            <td style="padding:10px;border:1px solid #c5cae9;color:#1a237e;">جمع<br><span style="font-size:16px;">۳۰</span></td>
                            <td style="padding:10px;border:1px solid #c5cae9;color:#1a237e;">جمع<br><span style="font-size:16px;">۷</span></td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                    <p style="font-size:12.5px;color:#475569;">ستون اول: ۱۰۰+۵۰+۳۰ = <b>۱۸۰</b> · ستون دوم: ۲۰+۱۰ = <b>۳۰</b> (سلول خالی نادیده) · ستون سوم: ۵+۰+۲ = <b>۷</b></p>
                    <p><strong>۴) نکات مهم:</strong></p>
                    <ol>
                      <li>محاسبه <b>ستونی</b> است نه سطری (هر ستون جدا).</li>
                      <li>با تایپ عدد، نتیجه همان لحظه در ردیف پایین به‌روز می‌شود.</li>
                      <li>اگر ردیف محاسباتی را «بدون محاسبه» بگذارید، ردیف جمع نشان داده نمی‌شود.</li>
                      <li>ویرایش فیلد موجود: دکمه ویرایش → تنظیمات ماتریس و فرمول دوباره باز می‌شود.</li>
                    </ol>
                    <p><strong>۵) فرمول اکسل‌مانند با علامت =</strong></p>
                    <ul>
                      <li><code>=A1+B1</code> جمع دو سلول</li>
                      <li><code>=A1*B1</code> ضرب</li>
                      <li><code>=SUM(A:A)</code> جمع کل ستون A</li>
                      <li><code>=AVG(B:B)</code> میانگین ستون B</li>
                      <li>ستون‌ها از راست به چپ در RTL: A = ستون اول، B = دوم، …</li>
                    </ul>
                    <div class="cgs-help-tip">💡 این راهنما فقط برای ادمین در فرم‌ساز است و در فرم نهایی کاربر دیده نمی‌شود. اعداد پارسی/انگلیسی هر دو قبول‌اند.</div>
                  </div>
                </details>
                <ul id="cgs-fields-list" class="cgs-sortable">
                    <?php if ( empty( $fields ) ) : ?>
                        <li class="cgs-empty">هنوز فیلدی وجود ندارد. روی «افزودن فیلد» کلیک کنید.</li>
                    <?php else : foreach ( $fields as $f ) :
                        $opts = '';
                        if ( ! empty( $f['options'] ) ) {
                            $decoded = json_decode( $f['options'], true );
                            if ( is_array( $decoded ) ) $opts = implode( "\n", $decoded );
                        }
                    ?>
                        <li class="cgs-field-row" data-id="<?php echo (int)$f['id']; ?>">
                            <span class="dashicons dashicons-menu cgs-handle"></span>
                            <div class="cgs-field-main">
                                <strong><?php echo esc_html( $f['label'] ); ?></strong>
                                <small>
                                    <?php
                                    $ftlab = $field_types[$f['field_type']] ?? $f['field_type'];
                                    if ( $f['field_type'] === 'table' ) {
                                        $ftlab = 'ماتریس · محاسبه';
                                    }
                                    echo esc_html( $ftlab );
                                    ?>
                                    | مرحله <?php echo (int)$f['step_number']; ?>
                                    <?php if ( $f['is_required'] ) echo ' | <span style="color:#c00">الزامی</span>'; ?>
                                </small>
                            </div>
                            <div class="cgs-field-btns">
                                <button type="button" class="button button-small cgs-btn-edit"
                                    data-id="<?php echo (int)$f['id']; ?>"
                                    data-key="<?php echo esc_attr($f['field_key']); ?>"
                                    data-label="<?php echo esc_attr($f['label']); ?>"
                                    data-type="<?php echo esc_attr($f['field_type']); ?>"
                                    data-placeholder="<?php echo esc_attr($f['placeholder']); ?>"
                                    data-required="<?php echo (int)$f['is_required']; ?>"
                                    data-step="<?php echo (int)$f['step_number']; ?>"
                                    data-options="<?php echo esc_attr($opts); ?>"
                                    data-validation="<?php echo esc_attr( is_string($f['validation'] ?? '') ? $f['validation'] : wp_json_encode( $f['validation'] ?? array() ) ); ?>">ویرایش</button>
                                <button type="button" class="button button-small cgs-btn-del" data-id="<?php echo (int)$f['id']; ?>">حذف</button>
                            </div>
                            <input type="hidden" class="cgs-raw" value="<?php echo esc_attr( wp_json_encode( $f ) ); ?>">
                        </li>
                    <?php endforeach; endif; ?>
                </ul>

                <p style="margin-top:12px;">
                    <button type="button" id="cgs-btn-save-order" class="cgs-btn-admin cgs-btn-admin-success">ذخیره ترتیب</button>
                    <span id="cgs-order-msg"></span>
                </p>
            </div>
        </div><!-- /col fields -->

        <!-- ستون ۲: تنظیمات ظاهری -->
        <div class="cgs-builder-col-styles">
            <div class="cgs-panel cgs-builder-styles-col" style="margin-top:0; position:sticky; top:32px; max-height:88vh; overflow-y:auto;">
                <h2 style="margin-top:0; font-size:1.2rem;">تنظیمات ظاهری فرم</h2>
                <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'form_builder.styles' ); } ?>
                
<style id="cgs-style-mother-boxes">
.cgs-mother-box {
  border: 1px solid #c5cae9;
  border-radius: 16px;
  background: #fafbff;
  padding: 0 0 14px;
  margin-bottom: 16px;
  grid-column: 1 / -1;
}
.cgs-mother-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 16px 16px 0 0;
  color: #fff;
  font-weight: 800;
  font-size: 1rem;
}
.cgs-mother-head-blue { background: linear-gradient(135deg,#1a237e,#3949ab); }
.cgs-mother-head-green { background: linear-gradient(135deg,#0f766e,#14b8a6); }
.cgs-mother-head-purple { background: linear-gradient(135deg,#5b21b6,#7c3aed); }
.cgs-mother-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 14px 14px 0;
}
@media (max-width: 960px) {
  .cgs-mother-body { grid-template-columns: 1fr; }
}
.cgs-mother-body > .cgs-style-box {
  margin-bottom: 0 !important;
  height: auto !important;
}
.cgs-mother-save {
  background: rgba(255,255,255,.2) !important;
  border: 1px solid rgba(255,255,255,.5) !important;
  color: #fff !important;
  border-radius: 8px !important;
  padding: 6px 12px !important;
  cursor: pointer;
  font-weight: 700;
}
.cgs-mother-save:hover { background: rgba(255,255,255,.35) !important; }
</style>

<div class="cgs-style-grid cgs-style-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:8px 12px;">
                    <div class="cgs-mother-box cgs-mother-base" style="grid-column:1/-1">
  <div class="cgs-mother-head cgs-mother-head-purple">
    <span>🎛️ تنظیمات پایه فرم</span>
    <button type="button" class="cgs-mother-save cgs-save-styles-btn">💾 ذخیره تغییرات</button>
  </div>
  <div class="cgs-mother-body">
<div class="cgs-style-box"><h4>📝 عنوان فیلدها</h4><div class="cgs-style-grid-inner">
                    <div class="cgs-style-row">
                        <label>فونت</label>
                        <select id="st-label-font">
                            <?php foreach ( $fonts as $font ) : ?>
                                <option value="<?php echo esc_attr($font); ?>" <?php selected($styles['label_font'], $font); ?>><?php echo esc_html($font); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>اندازه <span class="cgs-unit">(px)</span></label>
                        <input type="number" id="st-label-size" value="<?php echo esc_attr($styles['label_size']); ?>" min="10" max="30" style="width:70px">
                    </div>
                    <div class="cgs-style-row">
                        <label>درشت</label>
                        <select id="st-label-weight">
                            <option value="400" <?php selected($styles['label_weight'],'400'); ?>>عادی</option>
                            <option value="600" <?php selected($styles['label_weight'],'600'); ?>>نیمه‌ضخیم</option>
                            <option value="700" <?php selected($styles['label_weight'],'700'); ?>>ضخیم</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>حالت</label>
                        <select id="st-label-style">
                            <option value="normal" <?php selected($styles['label_style'],'normal'); ?>>عادی</option>
                            <option value="italic" <?php selected($styles['label_style'],'italic'); ?>>مورب</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>زیرخط</label>
                        <select id="st-label-decoration">
                            <option value="none" <?php selected($styles['label_decoration'],'none'); ?>>بدون</option>
                            <option value="underline" <?php selected($styles['label_decoration'],'underline'); ?>>زیرخط‌دار</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>تراز</label>
                        <select id="st-label-align">
                            <option value="right" <?php selected($styles['label_align'],'right'); ?>>راست‌چین</option>
                            <option value="center" <?php selected($styles['label_align'],'center'); ?>>وسط‌چین</option>
                            <option value="left" <?php selected($styles['label_align'],'left'); ?>>چپ‌چین</option>
                        </select>
                    </div>

                    </div></div><div class="cgs-style-box"><h4>⌨️ متن ورودی</h4><div class="cgs-style-grid-inner">
                    <div class="cgs-style-row">
                        <label>فونت</label>
                        <select id="st-input-font">
                            <?php foreach ( $fonts as $font ) : ?>
                                <option value="<?php echo esc_attr($font); ?>" <?php selected($styles['input_font'], $font); ?>><?php echo esc_html($font); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>اندازه</label>
                        <input type="number" id="st-input-size" value="<?php echo esc_attr($styles['input_size']); ?>" min="10" max="30" style="width:70px">
                    </div>
                    <div class="cgs-style-row">
                        <label>درشت</label>
                        <select id="st-input-weight">
                            <option value="400" <?php selected($styles['input_weight'],'400'); ?>>عادی</option>
                            <option value="600" <?php selected($styles['input_weight'],'600'); ?>>نیمه‌ضخیم</option>
                            <option value="700" <?php selected($styles['input_weight'],'700'); ?>>ضخیم</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>حالت</label>
                        <select id="st-input-style">
                            <option value="normal" <?php selected($styles['input_style'],'normal'); ?>>عادی</option>
                            <option value="italic" <?php selected($styles['input_style'],'italic'); ?>>مورب</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>زیرخط</label>
                        <select id="st-input-decoration">
                            <option value="none" <?php selected($styles['input_decoration'],'none'); ?>>بدون</option>
                            <option value="underline" <?php selected($styles['input_decoration'],'underline'); ?>>زیرخط‌دار</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>تراز</label>
                        <select id="st-input-align">
                            <option value="right" <?php selected($styles['input_align'],'right'); ?>>راست‌چین</option>
                            <option value="center" <?php selected($styles['input_align'],'center'); ?>>وسط‌چین</option>
                            <option value="left" <?php selected($styles['input_align'],'left'); ?>>چپ‌چین</option>
                        </select>
                    </div>
                </div>
                
                </div></div><div class="cgs-style-box"><h4>🎨 رنگ‌ها</h4><div class="cgs-style-grid-inner">
<div class="cgs-style-row"><label>عنوان فیلد</label><input type="text" id="st-color-label" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_label'] ?? '#1a1a2e' ); ?>"></div>
                <div class="cgs-style-row"><label>ستاره اجباری</label><input type="text" id="st-color-required" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_required'] ?? '#c62828' ); ?>"></div>
                <div class="cgs-style-row"><label>متن ورودی</label><input type="text" id="st-color-input" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_input'] ?? '#1a1a2e' ); ?>"></div>
                <div class="cgs-style-row"><label>کادر ورودی</label><input type="text" id="st-color-border" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_border'] ?? '#e0e4ec' ); ?>"></div>
                <div class="cgs-style-row"><label>پس‌زمینه ورودی</label><input type="text" id="st-color-bg" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_bg'] ?? '#ffffff' ); ?>"></div>
                <div class="cgs-style-row"><label>دکمه</label><input type="text" id="st-color-button" class="cgs-color-picker" value="<?php echo esc_attr( $styles['color_button'] ?? '#1a237e' ); ?>"></div>
                    </div></div><div class="cgs-style-box"><h4>📐 چیدمان فرم</h4><div class="cgs-style-grid-inner">
                    <div class="cgs-style-row">
                        <label>موقعیت عنوان فیلد</label>
                        <select id="st-label-position">
                            <option value="above" <?php selected( ($styles['label_position'] ?? 'above'), 'above' ); ?>>بالای کادر</option>
                            <option value="beside" <?php selected( ($styles['label_position'] ?? ''), 'beside' ); ?>>کنار کادر (افقی)</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>تعداد ستون پیش‌نمایش / فرم</label>
                        <p class="description" style="margin:0 0 4px;font-size:11px;">این مقدار روی همه مراحل پیش‌نمایش اعمال می‌شود. خطوط راهنما بین ستون‌ها نمایش داده می‌شود.</p>
                        <select id="st-form-columns" class="cgs-select-fix">
                            <option value="1" <?php selected( ($styles['form_columns'] ?? '1'), '1' ); ?>>۱ ستون</option>
                            <option value="2" <?php selected( ($styles['form_columns'] ?? ''), '2' ); ?>>۲ ستون</option>
                            <option value="3" <?php selected( ($styles['form_columns'] ?? ''), '3' ); ?>>۳ ستون</option>
                            <option value="4" <?php selected( ($styles['form_columns'] ?? ''), '4' ); ?>>۴ ستون</option>
                            <option value="5" <?php selected( ($styles['form_columns'] ?? ''), '5' ); ?>>۵ ستون</option>
                            <option value="6" <?php selected( ($styles['form_columns'] ?? ''), '6' ); ?>>۶ ستون</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>فاصله فیلدها</label>
                        <input type="number" id="st-field-gap" value="<?php echo esc_attr( $styles['field_gap'] ?? '12' ); ?>" min="4" max="40" style="width:70px">
                    </div>
                    <div class="cgs-style-row">
                        <label>عرض برچسب (حالت کنار)</label>
                        <input type="number" id="st-label-width" value="<?php echo esc_attr( $styles['label_width'] ?? '30' ); ?>" min="20" max="50" style="width:70px"> %
                    </div>

                    
                <div class="cgs-style-row"><label>زاویه دکمه <span class="cgs-unit">(px)</span></label><input type="number" id="st-button-radius" value="<?php echo esc_attr( $styles['button_radius'] ?? '10' ); ?>" min="0" max="50" style="width:70px"></div>

                    </div></div><div class="cgs-style-box"><h4>✨ سایه و افکت</h4><div class="cgs-style-grid-inner">
                    <div class="cgs-style-row">
                        <label>سایه باکس فرم</label>
                        <select id="st-shadow-form">
                            <option value="none" <?php selected( ($styles['shadow_form'] ?? ''), 'none' ); ?>>بدون سایه</option>
                            <option value="soft" <?php selected( ($styles['shadow_form'] ?? ''), 'soft' ); ?>>نرم</option>
                            <option value="medium" <?php selected( ($styles['shadow_form'] ?? ''), 'medium' ); ?>>متوسط</option>
                            <option value="strong" <?php selected( ($styles['shadow_form'] ?? ''), 'strong' ); ?>>قوی</option>
                            <option value="glow" <?php selected( ($styles['shadow_form'] ?? ''), 'glow' ); ?>>درخشان رنگی</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>سایه کادر فیلد</label>
                        <select id="st-shadow-field">
                            <option value="none" <?php selected( ($styles['shadow_field'] ?? ''), 'none' ); ?>>بدون سایه</option>
                            <option value="soft" <?php selected( ($styles['shadow_field'] ?? ''), 'soft' ); ?>>نرم</option>
                            <option value="medium" <?php selected( ($styles['shadow_field'] ?? ''), 'medium' ); ?>>متوسط</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>سایه دکمه</label>
                        <select id="st-shadow-btn">
                            <option value="none" <?php selected( ($styles['shadow_btn'] ?? ''), 'none' ); ?>>بدون سایه</option>
                            <option value="soft" <?php selected( ($styles['shadow_btn'] ?? ''), 'soft' ); ?>>نرم</option>
                            <option value="medium" <?php selected( ($styles['shadow_btn'] ?? ''), 'medium' ); ?>>متوسط</option>
                            <option value="strong" <?php selected( ($styles['shadow_btn'] ?? ''), 'strong' ); ?>>قوی</option>
                        </select>
                    </div>
                    <div class="cgs-style-row">
                        <label>افکت هاور دکمه</label>
                        <select id="st-btn-hover">
                            <option value="lift" <?php selected( ($styles['btn_hover'] ?? ''), 'lift' ); ?>>بالا آمدن</option>
                            <option value="scale" <?php selected( ($styles['btn_hover'] ?? ''), 'scale' ); ?>>بزرگ‌نمایی</option>
                            <option value="glow" <?php selected( ($styles['btn_hover'] ?? ''), 'glow' ); ?>>درخشش</option>
                            <option value="none" <?php selected( ($styles['btn_hover'] ?? ''), 'none' ); ?>>بدون افکت</option>
                        </select>
                    </div>
                    </div></div><div class="cgs-appear-section cgs-section-btns"><div class="cgs-appear-section-title">🔘 تنظیمات دکمه‌ها (قالب، متن، محل، صدا)</div>
</div></div><!-- /mother base -->

<div class="cgs-style-box cgs-sound-box">
  <h4>🔊 صدا و سبک پایه دکمه</h4>
  <div class="cgs-style-grid-inner cgs-sound-grid">
    <div class="cgs-style-row">
      <label>صدای کلیک دکمه</label>
      <select id="st-btn-sound">
        <option value="0" <?php selected( ($styles['btn_sound'] ?? ''), '0' ); ?>>خاموش</option>
        <option value="1" <?php selected( ($styles['btn_sound'] ?? '1'), '1' ); ?>>روشن</option>
      </select>
    </div>
    <div class="cgs-style-row">
      <label>نوع صدا</label>
      <select id="st-sound-type">
        <?php
        $stypes = array('chime'=>'زنگ ملایم','bell'=>'زنگوله','success'=>'موفقیت','sparkle'=>'درخشش','coin'=>'سکه','ding'=>'دینگ','double'=>'دوبل','rising'=>'صعودی','levelup'=>'ارتقا','fanfare'=>'جشن','glass'=>'شیشه','harp'=>'چنگ');
        $cur_st = $styles['sound_type'] ?? 'chime';
        foreach ($stypes as $sk=>$sl) {
          echo '<option value="'.esc_attr($sk).'" '.selected($cur_st,$sk,false).'>'.esc_html($sl).'</option>';
        }
        ?>
      </select>
    </div>
    <div class="cgs-style-row" style="grid-column:1/-1">
      <label>بلندی صدا <span id="st-sound-vol-label"><?php echo (int)($styles['sound_volume'] ?? 40); ?></span>%</label>
      <div class="cgs-vol-row">
        <input type="range" id="st-sound-volume" min="0" max="100" value="<?php echo esc_attr( $styles['sound_volume'] ?? '40' ); ?>">
        <button type="button" class="button" id="st-sound-preview">▶ تست صدا</button>
      </div>
    </div>
  </div>
</div>


<style id="cgs-title-panel-layout">
.cgs-title-btn-section .cgs-title-btn-grid {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 16px !important;
  align-items: start !important;
}
@media (max-width: 900px) {
  .cgs-title-btn-section .cgs-title-btn-grid { grid-template-columns: 1fr !important; }
}
.cgs-form-title-style,
.cgs-form-btn-texts {
  min-height: 0 !important;
  padding: 14px 16px 18px !important;
  box-sizing: border-box !important;
  overflow: visible !important;
}
.cgs-form-title-style .cgs-style-row,
.cgs-form-btn-texts .cgs-style-row {
  margin-bottom: 10px !important;
}
.cgs-form-title-style .cgs-style-pair {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 10px !important;
  margin-bottom: 10px !important;
}
.cgs-title-bg-box input[type=color] {
  width: 100% !important;
  min-height: 38px !important;
}
</style>

<div class="cgs-mother-box cgs-mother-title" style="grid-column:1/-1">
  <div class="cgs-mother-head cgs-mother-head-blue">
    <span>📝 تنظیمات عنوان قابل‌نمایش فرم</span>
    <button type="button" class="cgs-mother-save cgs-save-styles-btn">💾 ذخیره تغییرات</button>
  </div>
  <div class="cgs-mother-body" style="grid-template-columns:1fr">
<div class="cgs-style-box cgs-title-btn-section" style="border:none;box-shadow:none;background:transparent">
  <h4 style="display:none">📝 عنوان و متن دکمه‌ها</h4>
  <div class="cgs-title-btn-grid">
    <!-- عنوان -->
    <div class="cgs-panel cgs-form-title-style">
      <div class="cgs-panel-head cgs-head-blue">
        <span class="cgs-panel-ico">📌</span>
        <span>عنوان قابل‌نمایش فرم</span>
      </div>
      <p class="cgs-panel-desc">نام، فونت، کادر، سایه و آیکن عنوان در پیش‌نمایش و فرم نهایی</p>

      <div class="cgs-style-row">
        <label>متن عنوان</label>
        <input type="text" id="st-form-title-text" value="<?php echo esc_attr( $styles['form_title_text'] ?? '' ); ?>" placeholder="خالی = نام نوع درخواست">
      </div>

      <div class="cgs-style-pair">
        <div class="cgs-style-row">
          <label>فونت</label>
          <select id="st-form-title-font">
            <?php
            $tf = $styles['form_title_font'] ?? '';
            foreach ( array('','Vazirmatn','IRANSans','Tahoma','Arial') as $ff ) {
                echo '<option value="'.esc_attr($ff).'" '.selected($tf,$ff,false).'>'.esc_html($ff?:'پیش‌فرض').'</option>';
            }
            ?>
          </select>
        </div>
        <div class="cgs-style-row">
          <label>اندازه <span class="cgs-unit">px</span></label>
          <input type="number" id="st-form-title-size" min="12" max="48" value="<?php echo esc_attr( $styles['form_title_size'] ?? '20' ); ?>">
        </div>
      </div>

      <div class="cgs-style-pair">
        <div class="cgs-style-row">
          <label>رنگ متن</label>
          <input type="color" id="st-form-title-color" value="<?php echo esc_attr( $styles['form_title_color'] ?? '#1a237e' ); ?>">
        </div>
        <div class="cgs-style-row">
          <label>رنگ کادر</label>
          <input type="color" id="st-form-title-border" value="<?php echo esc_attr( $styles['form_title_border'] ?? '#c5cae9' ); ?>">
        </div>
      </div>

      <div class="cgs-style-pair">
        <div class="cgs-style-row">
          <label>ضخامت کادر <span class="cgs-unit">px</span></label>
          <input type="number" id="st-form-title-bw" min="0" max="8" value="<?php echo esc_attr( $styles['form_title_bw'] ?? '0' ); ?>">
        </div>
        <div class="cgs-style-row">
          <label>سایه</label>
          <select id="st-form-title-shadow">
            <?php
            $sh = $styles['form_title_shadow'] ?? 'none';
            foreach ( array('none'=>'بدون','soft'=>'نرم','medium'=>'متوسط','strong'=>'قوی','glow'=>'درخشان') as $k=>$lab ) {
                echo '<option value="'.$k.'" '.selected($sh,$k,false).'>'.$lab.'</option>';
            }
            ?>
          </select>
        </div>
      </div>

      <div class="cgs-style-pair">
        <div class="cgs-style-row">
          <label>انیمیشن</label>
          <select id="st-form-title-anim">
            <?php
            $an = $styles['form_title_anim'] ?? 'none';
            foreach ( array('none'=>'بدون','fade'=>'محو','slide'=>'اسلاید','pulse'=>'ضربان') as $k=>$lab ) {
                echo '<option value="'.$k.'" '.selected($an,$k,false).'>'.$lab.'</option>';
            }
            ?>
          </select>
        </div>
        <div class="cgs-style-row">
          <label>آیکن (emoji)</label>
          <input type="text" id="st-form-title-icon" value="<?php echo esc_attr( $styles['form_title_icon'] ?? '' ); ?>" placeholder="📋">
        </div>
      </div>

      <div class="cgs-style-row">
        <label>اندازه آیکن <span class="cgs-unit">px</span></label>
        <input type="number" id="st-form-title-icon-size" min="12" max="64" value="<?php echo esc_attr( $styles['form_title_icon_size'] ?? '24' ); ?>">
      </div>
    </div>


      <div class="cgs-title-bg-box" style="margin-top:12px;padding:12px;border:1px solid #c5cae9;border-radius:12px;background:#f5f7ff;">
        <div style="font-weight:700;color:#1a237e;margin-bottom:10px;">🎨 پس‌زمینه عنوان فرم</div>
        <div class="cgs-style-pair">
          <div class="cgs-style-row">
            <label>نوع پس‌زمینه</label>
            <select id="st-form-title-bg-type">
              <?php $tbt = $styles['form_title_bg_type'] ?? 'color'; ?>
              <option value="color" <?php selected($tbt,'color'); ?>>رنگ (کد رنگ)</option>
              <option value="image" <?php selected($tbt,'image'); ?>>تصویر</option>
              <option value="video" <?php selected($tbt,'video'); ?>>ویدئو</option>
              <option value="none" <?php selected($tbt,'none'); ?>>بدون</option>
            </select>
          </div>
          <div class="cgs-style-row">
            <label>کد رنگ پس‌زمینه</label>
            <input type="color" id="st-form-title-bg-color" value="<?php echo esc_attr( $styles['form_title_bg_color'] ?? '#eef2ff' ); ?>">
          </div>
        </div>
        <div class="cgs-style-row">
          <label>تصویر / ویدئو عنوان</label>
          <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
            <input type="url" id="st-form-title-bg-media" class="regular-text" style="flex:1;min-width:160px;" value="<?php echo esc_attr( $styles['form_title_bg_media'] ?? '' ); ?>" placeholder="آدرس اینترنتی فایل">
            <button type="button" class="button cgs-browse-media" data-target="#st-form-title-bg-media" data-type="image">Browse مخزن</button>
            <label class="button" style="margin:0;cursor:pointer;">از سیستم
              <input type="file" id="st-form-title-bg-file" accept="image/*,video/*" style="display:none;">
            </label>
          </div>
          <p class="description" style="margin:6px 0 0;">مخزن وردپرس · آدرس اینترنتی · آپلود از کامپیوتر</p>
        </div>
      </div>
    
<div class="cgs-panel" style="padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
      <div class="cgs-panel-head cgs-head-blue" style="margin:-12px -12px 10px;border-radius:12px 12px 0 0;"><span class="cgs-panel-ico">📌</span><span>محل عنوان فرم</span></div>
      <div class="cgs-style-row">
        <label>موقعیت عنوان</label>
        <select id="st-title-position">
          <?php $tp = $styles['title_position'] ?? 'top'; ?>
          <option value="top" <?php selected($tp,'top'); ?>>بالای فرم (همه مراحل)</option>
          <option value="top-first" <?php selected($tp,'top-first'); ?>>فقط مرحله اول</option>
          <option value="each-step" <?php selected($tp,'each-step'); ?>>بالای هر مرحله</option>
          <option value="hidden" <?php selected($tp,'hidden'); ?>>مخفی</option>
        </select>
      </div>
    </div>
</div><!-- /cgs-form-title-style -->

    <!-- دکمه‌ها -->
    
  </div>
</div>


</div></div><!-- /mother title -->

<div class="cgs-mother-box cgs-mother-btns" style="grid-column:1/-1">
  <div class="cgs-mother-head cgs-mother-head-green">
    <span>🔘 تنظیمات دکمه‌های فرم</span>
    <button type="button" class="cgs-mother-save cgs-save-styles-btn">💾 ذخیره تغییرات</button>
  </div>
  <div class="cgs-mother-body" style="grid-template-columns:1fr">
<div class="cgs-panel cgs-form-btn-texts">
      <div class="cgs-panel-head cgs-head-green">
        <span class="cgs-panel-ico">💬</span>
        <span>متن دکمه‌های فرم</span>
      </div>
      <p class="cgs-panel-desc">متن دکمه‌های مرحله و ثبت نهایی در پیش‌نمایش</p>
      <div class="cgs-style-row">
        <label>مرحله بعد</label>
        <input type="text" id="st-btn-next" value="<?php echo esc_attr( $styles['btn_next_text'] ?? 'مرحله بعد' ); ?>">
      </div>
      <div class="cgs-style-row">
        <label>مرحله قبل</label>
        <input type="text" id="st-btn-prev" value="<?php echo esc_attr( $styles['btn_prev_text'] ?? 'مرحله قبل' ); ?>">
      </div>
      <div class="cgs-style-row">
        <label>ثبت نهایی</label>
        <input type="text" id="st-btn-submit" value="<?php echo esc_attr( $styles['btn_submit_text'] ?? 'ثبت نهایی درخواست' ); ?>">
      </div>
    </div>
<div class="cgs-style-box cgs-btn-placement-box" id="cgs-btn-placement-box" style="border:none;box-shadow:none">
  <h4>📍 محل قرارگیری و قالب دکمه / عنوان</h4>
  <div class="cgs-style-grid-inner" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    
    <div class="cgs-panel" style="padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
      <div class="cgs-panel-head cgs-head-green" style="margin:-12px -12px 10px;border-radius:12px 12px 0 0;"><span class="cgs-panel-ico">🔘</span><span>محل قرارگیری دکمه</span></div>
      <div class="cgs-style-row">
        <label>موقعیت دکمه‌های مرحله</label>
        <select id="st-btn-position">
          <?php $bp = $styles['btn_position'] ?? 'bottom'; ?>
          <option value="bottom" <?php selected($bp,'bottom'); ?>>پایین مرحله (پیش‌فرض)</option>
          <option value="top" <?php selected($bp,'top'); ?>>بالای مرحله</option>
          <option value="both" <?php selected($bp,'both'); ?>>بالا و پایین</option>
          <option value="sticky-bottom" <?php selected($bp,'sticky-bottom'); ?>>ثابت پایین صفحه</option>
        </select>
      </div>
      <div class="cgs-style-row">
        <label>اعمال روی</label>
        <select id="st-btn-position-scope">
          <?php $bps = $styles['btn_position_scope'] ?? 'all'; ?>
          <option value="all" <?php selected($bps,'all'); ?>>همه مراحل</option>
          <option value="first" <?php selected($bps,'first'); ?>>فقط مرحله اول</option>
          <option value="last" <?php selected($bps,'last'); ?>>فقط مرحله آخر</option>
          <option value="middle" <?php selected($bps,'middle'); ?>>مراحل میانی</option>
        </select>
      </div>
      <div class="cgs-style-row">
        <label>تراز افقی</label>
        <select id="st-btn-align">
          <?php $ba = $styles['btn_align'] ?? 'space-between'; ?>
          <option value="space-between" <?php selected($ba,'space-between'); ?>>دو طرف (قبل | بعد)</option>
          <option value="flex-start" <?php selected($ba,'flex-start'); ?>>سمت راست</option>
          <option value="center" <?php selected($ba,'center'); ?>>وسط</option>
          <option value="flex-end" <?php selected($ba,'flex-end'); ?>>سمت چپ</option>
        </select>
      </div>
    </div>
    <div class="cgs-panel" style="padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;grid-column:1/-1;">
      <div class="cgs-panel-head" style="margin:-12px -12px 10px;border-radius:12px 12px 0 0;background:linear-gradient(90deg,#fef3c7,#fde68a);color:#92400e;padding:10px 14px;font-weight:700;"><span>✨</span> قالب دکمه</div>
      <div class="cgs-btn-tpl-clean" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:start;">
        <div class="cgs-style-row" style="grid-column:1/-1;margin:0;">
          <label>قالب ظاهری دکمه</label>
          <select id="st-btn-template" class="cgs-btn-tpl-select" style="width:100%;min-height:38px;">
            <?php
            $tpl_list = array(
              'flat'=>'فلت','solid'=>'توپر','outline'=>'حاشیه','soft'=>'ملایم',
              'glass'=>'شیشه‌ای','glass3d'=>'شیشه‌ای ۳بعدی','neon'=>'نئون درخشان','raised3d'=>'برجسته ۳بعدی',
              'pill'=>'کپسولی','gradient'=>'گرادیان','shadow'=>'سایه قوی','minimal'=>'مینیمال',
              'success'=>'سبز موفقیت','danger'=>'قرمز','warning'=>'نارنجی','dark'=>'تیره',
              'bordered3d'=>'۳بعدی حاشیه‌دار','glow_pulse'=>'درخشش پالسی','ice'=>'یخی','premium'=>'پریمیوم'
            );
            $cur_bt = $styles['btn_template'] ?? 'flat';
            if ($cur_bt === 'default') $cur_bt = 'flat';
            echo '<optgroup label="فلت و ساده">';
            foreach (array('flat','solid','outline','soft','minimal') as $tv) {
              echo '<option value="'.esc_attr($tv).'" '.selected($cur_bt,$tv,false).'>'.esc_html($tpl_list[$tv]).'</option>';
            }
            echo '</optgroup><optgroup label="شیشه و درخشش">';
            foreach (array('glass','glass3d','neon','glow_pulse','ice') as $tv) {
              echo '<option value="'.esc_attr($tv).'" '.selected($cur_bt,$tv,false).'>'.esc_html($tpl_list[$tv]).'</option>';
            }
            echo '</optgroup><optgroup label="۳بعدی و ویژه">';
            foreach (array('raised3d','bordered3d','shadow','gradient','pill','premium') as $tv) {
              echo '<option value="'.esc_attr($tv).'" '.selected($cur_bt,$tv,false).'>'.esc_html($tpl_list[$tv]).'</option>';
            }
            echo '</optgroup><optgroup label="رنگی">';
            foreach (array('success','danger','warning','dark') as $tv) {
              echo '<option value="'.esc_attr($tv).'" '.selected($cur_bt,$tv,false).'>'.esc_html($tpl_list[$tv]).'</option>';
            }
            echo '</optgroup>';
            ?>
          </select>
          <div id="cgs-btn-tpl-preview-bar" class="cgs-btn-live-demo" style="margin-top:10px;">
            <span class="cgs-tpl-demo cgs-demo-<?php echo esc_attr($cur_bt); ?>" id="cgs-btn-demo-label">نمونه دکمه</span>
          </div>
        </div>
        <div class="cgs-style-row" style="margin:0;">
          <label>رنگ دکمه</label>
          <input type="color" id="st-btn-color" value="<?php echo esc_attr( $styles['btn_color'] ?? ($styles['color_button'] ?? '#1a237e') ); ?>" style="width:100%;height:38px;">
        </div>
        <div class="cgs-style-row" style="margin:0;">
          <label>قلم دکمه</label>
          <select id="st-btn-font" style="width:100%;min-height:38px;">
            <?php $bf = $styles['btn_font'] ?? '';
            foreach (array('','Vazirmatn','IRANSans','Tahoma','Arial') as $ff) {
              echo '<option value="'.esc_attr($ff).'" '.selected($bf,$ff,false).'>'.esc_html($ff?:'پیش‌فرض').'</option>';
            } ?>
          </select>
        </div>
        <div class="cgs-style-row" style="margin:0;">
          <label>اندازه قلم <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-font-size" min="11" max="24" value="<?php echo esc_attr( $styles['btn_font_size'] ?? '14' ); ?>" style="width:100%;min-height:38px;">
        </div>
<div class="cgs-style-row">
          <label>اندازه دکمه</label>
          <select id="st-btn-size">
            <?php $bsz = $styles['btn_size'] ?? 'md'; ?>
            <option value="sm" <?php selected($bsz,'sm'); ?>>کوچک</option>
            <option value="md" <?php selected($bsz,'md'); ?>>متوسط</option>
            <option value="lg" <?php selected($bsz,'lg'); ?>>بزرگ</option>
          </select>
        </div>
        <div class="cgs-style-row">
          <label>انیمیشن دکمه</label>
          <select id="st-btn-anim">
            <?php $ban = $styles['btn_anim'] ?? 'none'; ?>
            <option value="none" <?php selected($ban,'none'); ?>>بدون</option>
            <option value="pulse" <?php selected($ban,'pulse'); ?>>ضربان</option>
            <option value="shine" <?php selected($ban,'shine'); ?>>درخشش عبوری</option>
            <option value="bounce" <?php selected($ban,'bounce'); ?>>پرشی ملایم</option>
          </select>
        </div>
        <div class="cgs-style-row">
          <label>تمام‌عرض</label>
          <select id="st-btn-fullwidth">
            <?php $bfw = $styles['btn_fullwidth'] ?? '0'; ?>
            <option value="0" <?php selected($bfw,'0'); ?>>خیر</option>
            <option value="1" <?php selected($bfw,'1'); ?>>بله (ثبت نهایی)</option>
          </select>
        </div>
      </div>

      <div class="cgs-btn-coords-row">
        <div class="cgs-style-row" style="margin:0">
          <label>فاصله بالا <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-mt" min="0" max="120" value="<?php echo esc_attr( $styles['btn_mt'] ?? '12' ); ?>">
        </div>
        <div class="cgs-style-row" style="margin:0">
          <label>فاصله پایین <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-mb" min="0" max="120" value="<?php echo esc_attr( $styles['btn_mb'] ?? '0' ); ?>">
        </div>
        <div class="cgs-style-row" style="margin:0">
          <label>فاصله راست <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-mr" min="0" max="120" value="<?php echo esc_attr( $styles['btn_mr'] ?? ($styles['btn_mx'] ?? '0') ); ?>">
        </div>
        <div class="cgs-style-row" style="margin:0">
          <label>فاصله چپ <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-ml" min="0" max="120" value="<?php echo esc_attr( $styles['btn_ml'] ?? ($styles['btn_mx'] ?? '0') ); ?>">
        </div>
        <div class="cgs-style-row" style="margin:0">
          <label>فاصله بین دکمه‌ها <span class="cgs-unit">px</span></label>
          <input type="number" id="st-btn-gap" min="0" max="40" value="<?php echo esc_attr( $styles['btn_gap'] ?? '8' ); ?>">
        </div>
      </div>

    </div>
  </div>
</div>

</div></div><!-- /mother btns -->
</div><!-- /btn section -->
<div class="cgs-appear-section cgs-section-form"><div class="cgs-appear-section-title">🎨 ظاهر فرم (فونت، رنگ، چیدمان، پس‌زمینه)</div>
<div class="cgs-style-box"><h4>🖼️ پس‌زمینه فرم</h4><div class="cgs-style-grid-inner">
                <div class="cgs-style-row"><label>تصویر پس‌زمینه</label>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input type="url" id="st-form-bg" placeholder="https://..." style="flex:1;min-width:180px;" dir="ltr" value="<?php echo esc_attr( $styles['form_bg_image'] ?? '' ); ?>">
    <button type="button" class="button" id="st-form-bg-browse">📁 Browse</button>
    <button type="button" class="button" id="st-form-bg-clear">حذف</button>
  </div>
  <div id="st-form-bg-preview" style="margin-top:8px;"><?php if (!empty($styles['form_bg_image'])): ?><img src="<?php echo esc_url($styles['form_bg_image']); ?>" alt="" style="max-height:80px;border-radius:8px;border:1px solid #e2e8f0;"><?php endif; ?></div>
</div>
                <div class="cgs-style-row"><label>شفافیت %</label><input type="number" id="st-form-bg-op" value="<?php echo esc_attr( $styles['form_bg_opacity'] ?? '85' ); ?>" min="0" max="100" style="width:70px"></div>
                <div class="cgs-style-row"><label>افکت</label>
                    <select id="st-form-bg-effect">
                        <option value="none">بدون افکت</option>
                        <option value="blur">محو ملایم</option>
                        <option value="darken">تیره</option>
                    </select>
                </div>

                </div></div>
                    </div><!-- /form section -->
<p style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" id="cgs-btn-save-styles" class="cgs-btn-admin cgs-btn-admin-success">ذخیره ظاهر فرم</button>
                    <button type="button" id="cgs-btn-reset-styles" class="cgs-btn-admin cgs-btn-admin-muted">ریست ظاهر این فرم</button>
                    <span id="cgs-style-msg"></span>
                </p>
                <div class="cgs-style-tools" style="margin-top:10px;padding:12px;border:1px dashed #c5cae9;border-radius:12px;background:#f8fafc;">
                  <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                    <label style="font-size:12px;font-weight:600;color:#334155;">کپی ظاهر از نوع دیگر:</label>
                    <select id="cgs-copy-styles-from" style="min-width:160px;">
                      <option value="">— انتخاب مبدأ —</option>
                      <?php
                      $app_types = function_exists('cgs_get_application_types') ? cgs_get_application_types() : array();
                      $cur_type = isset($type_key) ? $type_key : (isset($_GET['type']) ? sanitize_key($_GET['type']) : '');
                      foreach ( $app_types as $tk => $ti ) {
                        if ( $tk === $cur_type ) continue;
                        $lab = is_array($ti) ? ($ti['label'] ?? $tk) : $ti;
                        echo '<option value="'.esc_attr($tk).'">'.esc_html($lab).'</option>';
                      }
                      ?>
                    </select>
                    <button type="button" id="cgs-btn-copy-styles" class="button">کپی به فرم فعلی</button>
                  </div>
                  <?php
                  $last = get_option('cgs_form_styles_last_save', array());
                  $last_txt = '';
                  if ( is_array($last) && ! empty($last['time']) ) {
                    $last_txt = 'آخرین ذخیره ظاهر: ' . esc_html( $last['time'] );
                    if ( ! empty($last['type_key']) ) $last_txt .= ' — نوع: ' . esc_html( $last['type_key'] );
                    if ( ! empty($last['copied_from']) ) $last_txt .= ' (کپی از ' . esc_html($last['copied_from']) . ')';
                  }
                  ?>
                  <p id="cgs-styles-last-save" style="margin:8px 0 0;font-size:12px;color:#64748b;"><?php echo $last_txt ? $last_txt : 'هنوز ذخیره‌ای ثبت نشده است.'; ?></p>
                </div>
                
            </div>
        </div><!-- /col styles -->

        



<style id="cgs-device-frame-extra">
#cgs-live-preview.cgs-device-frame.cgs-preview-tablet,
#cgs-live-preview.cgs-device-frame:not(.cgs-preview-mobile) {
  border-radius: 12px !important;
}
</style>


<style id="cgs-btn-force-css">
#cgs-live-preview .cgs-btn,
#cgs-live-preview .cgs-btn-primary,
#cgs-live-preview .cgs-next-step,
#cgs-live-preview #cgs-preview-submit {
  background-image: none !important;
  opacity: 1 !important;
  visibility: visible !important;
}
#cgs-live-preview.cgs-btn-style-glass .cgs-btn-primary,
#cgs-live-preview .cgs-btn-style-glass .cgs-btn-primary {
  background: #1a237e !important;
  color: #fff !important;
  border: none !important;
}
</style>

<style id="cgs-device-frame-css">
#cgs-live-preview.cgs-device-frame {
  box-sizing: border-box !important;
  margin-left: auto !important;
  margin-right: auto !important;
  border: 3px solid #1e293b !important;
  border-radius: 24px !important;
  box-shadow: 0 16px 40px rgba(15,23,42,.18) !important;
  background: #fff !important;
  overflow-x: hidden !important;
  overflow-y: auto !important;
  padding: 12px 10px 16px !important;
}
#cgs-live-preview.cgs-preview-mobile .cgs-field-group,
#cgs-live-preview.cgs-preview-mobile .cgs-field-card {
  width: 100% !important;
  max-width: 100% !important;
  flex: 1 1 100% !important;
  grid-column: 1 / -1 !important;
}
#cgs-live-preview.cgs-preview-mobile .cgs-step-fields,
#cgs-live-preview.cgs-preview-mobile .cgs-form-fields {
  display: flex !important;
  flex-direction: column !important;
  gap: 10px !important;
}
/* تلفن ثابت: در موبایل ستونی تا روی هم سوار نشوند */
#cgs-live-preview.cgs-preview-mobile .cgs-landline-row,
#cgs-live-preview.cgs-preview-mobile .cgs-two-fields {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
  width: 100% !important;
  direction: rtl !important;
}
#cgs-live-preview.cgs-preview-mobile .cgs-landline-row > div,
#cgs-live-preview.cgs-preview-mobile .cgs-landline-row input {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
}
#cgs-live-preview.cgs-preview-mobile .cgs-area-code {
  max-width: 100% !important;
  width: 100% !important;
}
#cgs-live-preview.cgs-preview-tablet .cgs-landline-row {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}
#cgs-live-preview.cgs-device-frame .cgs-form-actions {
  flex-wrap: wrap !important;
  gap: 8px !important;
}
#cgs-live-preview.cgs-device-frame .cgs-btn {
  max-width: 100% !important;
}
</style>

<style id="cgs-preview-device-fix">
/* باید بعد از قوانین تمام‌عرض بیاید */
.wrap.cgs-fb-wrap #cgs-live-preview.cgs-preview-mobile,
#cgs-live-preview.cgs-preview-mobile {
  max-width: 375px !important;
  width: 375px !important;
  margin-left: auto !important;
  margin-right: auto !important;
  border: 2px solid #3949ab !important;
  border-radius: 18px !important;
  box-shadow: 0 12px 32px rgba(26,35,126,.15) !important;
  padding: 14px !important;
  box-sizing: border-box !important;
}
#cgs-preview-device-toggle .cgs-dev-btn.is-active {
  background: #1a237e !important;
  color: #fff !important;
  border-color: #1a237e !important;
}
.wrap.cgs-fb-wrap #cgs-live-preview:not(.cgs-preview-mobile) {
  max-width: 100% !important;
  width: 100% !important;
}
</style>


<style id="cgs-preview-tablet-frame">
#cgs-live-preview.cgs-preview-tablet {
  border: 3px solid #475569 !important;
  border-radius: 16px !important;
  box-shadow: 0 12px 32px rgba(15,23,42,.15) !important;
  background: #fff !important;
  padding: 12px !important;
}
#cgs-live-preview.cgs-device-frame:not(.cgs-preview-mobile):not(.cgs-preview-tablet) {
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  box-shadow: 0 8px 24px rgba(15,23,42,.08) !important;
}
</style>
<style id="cgs-mobile-frame-std">
/* قاب موبایل استاندارد + اسکرول عمودی */
.wrap.cgs-fb-wrap #cgs-live-preview.cgs-preview-mobile,
#cgs-live-preview.cgs-preview-mobile {
  width: 375px !important;
  max-width: 375px !important;
  height: 667px !important;
  max-height: 667px !important;
  min-height: 667px !important;
  overflow-x: hidden !important;
  overflow-y: auto !important;
  -webkit-overflow-scrolling: touch !important;
  margin-left: auto !important;
  margin-right: auto !important;
  border: 2px solid #3949ab !important;
  border-radius: 28px !important;
  box-shadow: 0 12px 32px rgba(26,35,126,.18) !important;
  padding: 12px !important;
  box-sizing: border-box !important;
  background: #fff !important;
}
#cgs-live-preview.cgs-preview-mobile.cgs-device-iphone_14,
#cgs-live-preview.cgs-preview-mobile[data-device="iphone_14"] {
  height: 812px !important;
  max-height: 812px !important;
  min-height: 812px !important;
  width: 390px !important;
  max-width: 390px !important;
}
#cgs-live-preview.cgs-preview-mobile .cgs-step-fields,
#cgs-live-preview.cgs-preview-mobile #cgs-preview-form {
  min-height: auto !important;
  height: auto !important;
  overflow: visible !important;
}
</style>


<style id="cgs-phone-chrome">
/* قاب واقعی گوشی برای پیش‌نمایش موبایل */
.cgs-phone-shell {
  display: none;
  margin: 12px auto;
  width: 320px;
  padding: 12px 10px 18px;
  background: linear-gradient(160deg, #1e293b 0%, #0f172a 40%, #334155 100%);
  border-radius: 36px;
  box-shadow:
    0 0 0 3px #0f172a,
    0 0 0 5px #64748b,
    0 20px 40px rgba(15,23,42,.35);
  position: relative;
  box-sizing: border-box;
}
.cgs-phone-shell.is-on {
  display: block;
}
.cgs-phone-notch {
  width: 110px;
  height: 22px;
  background: #0f172a;
  border-radius: 0 0 14px 14px;
  margin: 0 auto 8px;
  position: relative;
}
.cgs-phone-notch::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 6px;
  transform: translateX(-50%);
  width: 48px;
  height: 6px;
  border-radius: 4px;
  background: #334155;
}
.cgs-phone-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 14px 6px;
  color: #e2e8f0;
  font-size: 10px;
  font-family: system-ui, sans-serif;
  direction: ltr;
}
.cgs-phone-brand {
  text-align: center;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  margin-bottom: 6px;
}
.cgs-phone-screen {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  height: 560px;
  max-height: 560px;
  position: relative;
}
.cgs-phone-screen #cgs-live-preview.cgs-preview-mobile,
.cgs-phone-shell #cgs-live-preview {
  width: 100% !important;
  max-width: 100% !important;
  height: 100% !important;
  max-height: 100% !important;
  min-height: 0 !important;
  margin: 0 !important;
  border: none !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  overflow-x: hidden !important;
  overflow-y: auto !important;
  padding: 10px !important;
  box-sizing: border-box !important;
}
.cgs-phone-home {
  width: 100px;
  height: 4px;
  background: #94a3b8;
  border-radius: 4px;
  margin: 10px auto 0;
}
/* وقتی موبایل نیست شل مخفی */
body:not(.cgs-preview-is-mobile) .cgs-phone-shell { display: none !important; }
body.cgs-preview-is-mobile .cgs-phone-shell { display: block !important; }
body.cgs-preview-is-mobile .cgs-preview-panel > #cgs-live-preview:not(.cgs-in-phone) {
  /* preview moved into phone */
}
</style>

</div><!-- /builder-grid -->

<!-- ستون ۳: پیش‌نمایش -->
        <div class="cgs-builder-col-preview cgs-preview-fullwidth-inner" style="display:block!important;width:100%!important;max-width:100%!important;min-height:400px;box-sizing:border-box;">
            <div class="cgs-panel cgs-fb-card cgs-preview-panel" style="display:block!important;visibility:visible!important;overflow:visible;min-height:400px;">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;">
                <h2 style="margin:0; font-size:1.15rem;color:#1a237e;">👁 پیش‌نمایش زنده فرم</h2>
                <div id="cgs-preview-device-toggle" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                  <label style="font-size:12px;font-weight:600;color:#334155;margin:0;">ابعاد دستگاه</label>
                                    <select id="cgs-preview-device-select" style="min-width:260px;min-height:34px;max-width:100%;">
                    <optgroup label="دسکتاپ و لپ‌تاپ">
                      <option value="desktop">دسکتاپ — تمام عرض</option>
                      <option value="laptop_14">لپ‌تاپ ۱۴″ — 1280×720</option>
                      <option value="laptop_15">لپ‌تاپ ۱۵٫۵″ — 1366×768</option>
                      <option value="laptop_16">لپ‌تاپ ۱۶″ — 1536×960</option>
                      <option value="laptop_17">لپ‌تاپ ۱۷″ — 1600×900</option>
                      <option value="desktop_1366">دسکتاپ HD — 1366×768</option>
                      <option value="monitor_19">مانیتور ۱۹″ — 1440×900</option>
                      <option value="monitor_21">مانیتور ۲۱″ — 1600×900</option>
                      <option value="monitor_24">مانیتور ۲۴″ — 1920×1080</option>
                      <option value="monitor_27">مانیتور ۲۷″ — 2560×1440</option>
                    </optgroup>
                    <optgroup label="آیفون">
                      <option value="iphone_se">iPhone SE — 375×667</option>
                      <option value="iphone_13_mini">iPhone 13 mini — 360×780</option>
                      <option value="iphone_14">iPhone 14 — 390×844</option>
                      <option value="iphone_15_pro">iPhone 15 Pro — 393×852</option>
                      <option value="iphone_14_pro_max">iPhone 14 Pro Max — 430×932</option>
                      <option value="iphone_16_pro_max">iPhone 16 Pro Max — 440×956</option>
                    </optgroup>
                    <optgroup label="سامسونگ">
                      <option value="samsung_s21">Galaxy S21 — 360×800</option>
                      <option value="samsung_s23">Galaxy S23 — 360×780</option>
                      <option value="samsung_s24_ultra">Galaxy S24 Ultra — 384×824</option>
                      <option value="samsung_a54">Galaxy A54 — 412×915</option>
                    </optgroup>
                    <optgroup label="شیائومی و اندروید">
                      <option value="xiaomi_13">Xiaomi 13 — 393×873</option>
                      <option value="xiaomi_redmi_note">Redmi Note — 393×873</option>
                      <option value="xiaomi_poco">POCO — 393×851</option>
                      <option value="pixel_7">Pixel 7 — 412×915</option>
                      <option value="android_small">اندروید کوچک — 360×640</option>
                      <option value="android_common">اندروید رایج — 360×800</option>
                    </optgroup>
                    <optgroup label="تبلت">
                      <option value="tablet_port">تبلت عمودی — 768×1024</option>
                      <option value="tablet_land">تبلت افقی — 1024×768</option>
                      <option value="ipad_port">iPad عمودی — 820×1180</option>
                      <option value="ipad_land">iPad افقی — 1180×820</option>
                    </optgroup>
                  </select>
                </div>
              </div>
              <style>
              #cgs-live-preview.cgs-preview-mobile {
                max-width: 375px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                border: 2px solid #c5cae9 !important;
                border-radius: 16px !important;
                padding: 12px !important;
                box-shadow: 0 8px 28px rgba(26,35,126,0.12) !important;
                background: #fff;
              }
              #cgs-preview-device-toggle .cgs-dev-btn.is-active {
                background: #1a237e !important;
                color: #fff !important;
                border-color: #1a237e !important;
              }
              </style>
                <?php if ( function_exists( 'cgs_help' ) ) { cgs_help( 'form_builder.preview' ); } ?>
                <p style="background:#e8f5e9;padding:10px 12px;border-radius:8px;font-size:0.88rem;margin:0 0 12px;border:1px solid #a5d6a7;">
                    <strong>چیدمان:</strong>
                    با <b>کشیدن کارت</b> جابه‌جا کنید.
                    از <b>لبه سمت چپ کارت</b> عرض را با ماوس کم/زیاد کنید.
                    عرض اولیه بر اساس «حداکثر کاراکتر» فیلد است.
                    سپس «ذخیره چیدمان» را بزنید.
                </p>
                <p style="margin:0 0 10px;">
                    <button type="button" id="cgs-btn-save-layout" class="cgs-btn-admin cgs-btn-admin-success">ذخیره چیدمان پیش‌نمایش</button>
                    <span id="cgs-layout-msg" style="margin-right:8px;"></span>
                </p>
                <p class="description">فیلدها را پر کنید، بین مراحل جابجا شوید و ثبت را بزنید. اطلاعات ذخیره نمی‌شود.</p>


                <?php
                $cgs_label_pos = isset( $styles['label_position'] ) ? $styles['label_position'] : 'beside';
                $cgs_preview_lbl_class = ( $cgs_label_pos === 'above' ) ? 'cgs-labels-above' : 'cgs-labels-beside';
                ?>
                
                <div id="cgs-live-preview-toolbar" class="cgs-lp-toolbar-static" style="display:flex!important;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px;padding:10px 12px;background:#eef2ff;border:1px solid #c5cae9;border-radius:10px;">
                    <strong style="color:#1a237e;font-size:12px;">مدیریت پیش‌نمایش زنده:</strong>
                    <button type="button" class="button button-small" id="cgs-lp-refresh">🔄 بروزرسانی</button>
                    <button type="button" class="button button-small" id="cgs-lp-step-prev">مرحله قبل</button>
                    <button type="button" class="button button-small" id="cgs-lp-step-next">مرحله بعد</button>
                    <button type="button" class="button button-primary button-small" id="cgs-lp-unlock">🔓 آزادسازی ورودی‌ها</button>
                    <button type="button" class="button button-small" id="cgs-lp-dnd">⠿ فعال‌سازی درگ فیلدها</button>
                    <span id="cgs-lp-step-info" style="font-size:12px;color:#475569;"></span>
                    <span id="cgs-layout-msg" style="font-size:12px;"></span>
                </div>

                <div id="cgs-lp-settings-box" style="display:block;margin-bottom:10px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                    <strong style="display:block;margin-bottom:8px;color:#1a237e;font-size:13px;">⚙️ تنظیمات پیش‌نمایش زنده</strong>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;font-size:12.5px;">
                        <label><input type="checkbox" id="cgs-lp-opt-inputs" checked> ورود اطلاعات آزمایشی</label>
                        <label><input type="checkbox" id="cgs-lp-opt-trial-label" checked> نمایش متن «حالت آزمایشی»</label>
                        <label><input type="checkbox" id="cgs-lp-opt-resize" checked> تغییر عرض فیلدها</label>
                        <label><input type="checkbox" id="cgs-lp-opt-dnd" checked> جابجایی با درگ</label>
                        <label><input type="checkbox" id="cgs-lp-opt-steps" checked> ناوبری مراحل</label>
                        <label><input type="checkbox" id="cgs-lp-opt-progress" checked> نوار پیشرفت</label>
                        <label>مقیاس پیش‌نمایش
                            <select id="cgs-lp-opt-scale" style="width:100%;margin-top:4px;">
                                <option value="1">۱۰۰٪</option>
                                <option value="0.9">۹۰٪</option>
                                <option value="0.8">۸۰٪</option>
                                <option value="0.7">۷۰٪</option>
                            </select>
                        </label>
                    </div>
                    <p style="margin:10px 0 0;font-size:11.5px;color:#64748b;">این تنظیمات فقط روی پیش‌نمایش ادمین اثر دارد؛ فرم واقعی سایت را تغییر نمی‌دهد.</p>
                </div>

<style id="cgs-all-steps-visible">
#cgs-live-preview .cgs-progress-steps {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
  justify-content: flex-start !important;
  max-width: 100% !important;
  overflow: visible !important;
}
#cgs-live-preview .cgs-step-indicator {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  flex: 0 0 auto !important;
  min-width: 72px !important;
}
#cgs-live-preview .cgs-form-subtitle.is-hidden,
#cgs-live-preview .cgs-form-subtitle.cgs-sub-off {
  display: none !important;
}
</style>

<div id="cgs-live-preview" class="cgs-preview-box <?php echo esc_attr( $cgs_preview_lbl_class ); ?>" style="display:block!important;visibility:visible!important;opacity:1!important;min-height:280px;background:#fff;padding:16px;border-radius:12px;border:1px solid #e0e4ec;">
                    <?php
                    $preview_steps = CGS_Form_Builder::get_fields_by_step( $current_type, false, $fields );
                    // ادغام مراحل تعریف‌شده در متا (حتی بدون فیلد) تا مرحله ۵+ در پیش‌نمایش دیده شود
                    if ( is_array( $step_meta ) ) {
                        foreach ( array_keys( $step_meta ) as $sk ) {
                            $sn = (int) $sk;
                            if ( $sn >= 1 && $sn <= 30 && ! isset( $preview_steps[ $sn ] ) ) {
                                $preview_steps[ $sn ] = array();
                            }
                        }
                    }
                    // اطمینان از وجود همه شماره‌های مرحله‌ای که فیلد دارند
                    ksort( $preview_steps );
                    $type_info = cgs_get_application_type( $current_type );
                    if ( empty( $preview_steps ) ) {
                        echo '<p style="text-align:center;color:#999;padding:30px 0;">ابتدا فیلد اضافه کنید.</p>';
                    } else {
                        $total_prev = max( 1, count( $preview_steps ) );
                        echo '<div class="cgs-form-wrapper cgs-preview-mode ' . esc_attr( $cgs_preview_lbl_class ) . '" id="cgs-preview-form" data-preview="1" data-label-pos="' . esc_attr( $cgs_label_pos ) . '">';
                        echo '<div class="cgs-form-header"><h3 class="cgs-form-title" style="font-size:1.2rem;margin:0 0 4px;">' . esc_html( $type_info['label'] ?? '' ) . '</h3><p class="cgs-form-subtitle" style="margin:0 0 12px;color:#888;font-size:0.9rem;">حالت آزمایشی</p></div>';
                        echo '<div class="cgs-progress"><div class="cgs-progress-bar"><div class="cgs-progress-fill" style="width:' . round(100/$total_prev) . '%;"></div></div><div class="cgs-progress-steps">';
                        $ii = 0;
                        foreach ( array_keys($preview_steps) as $sn ) {
                            $ii++;
                            $sm_p = isset( $step_meta[ $sn ] ) ? $step_meta[ $sn ] : ( isset( $step_meta[ (string) $sn ] ) ? $step_meta[ (string) $sn ] : array() );
                            $sn_name = ! empty( $sm_p['name'] ) ? $sm_p['name'] : ( 'مرحله ' . $ii );
                            $sn_icon = $sm_p['icon'] ?? '';
                            $sn_icon_url = $sm_p['icon_url'] ?? '';
                            echo '<div class="cgs-step-indicator ' . ( $ii === 1 ? 'active' : '' ) . '" data-step="' . (int) $sn . '">';
                            echo '<span class="cgs-step-num">' . $ii . '</span>';
                            echo '<span class="cgs-step-label">';
                            if ( $sn_icon_url ) {
                                echo '<img src="' . esc_url( $sn_icon_url ) . '" alt="" style="width:12px;height:12px;vertical-align:middle;"> ';
                            } elseif ( $sn_icon ) {
                                echo '<span class="cgs-icon cgs-icon-' . esc_attr( $sn_icon ) . '" style="width:12px;height:12px;"></span> ';
                            }
                            echo esc_html( $sn_name ) . '</span></div>';
                        }
                        echo '</div></div>';
                        echo '<form id="cgs-preview-form" onsubmit="return false;">';
                        $si = 0;
                        foreach ( $preview_steps as $step_num => $pfields ) {
                            $si++;
                            echo '<div class="cgs-form-step ' . ($si===1?'active':'') . '" data-step="' . (int)$step_num . '">';
                            $sm_head = isset( $step_meta[ $step_num ] ) ? $step_meta[ $step_num ] : ( isset( $step_meta[ (string) $step_num ] ) ? $step_meta[ (string) $step_num ] : array() );
                            $head_name = ! empty( $sm_head['name'] ) ? $sm_head['name'] : ( 'مرحله ' . $si );
                            $head_icon = $sm_head['icon'] ?? '';
                            $head_icon_url = $sm_head['icon_url'] ?? '';
                            echo '<h3 class="cgs-step-heading cgs-step-title" style="margin:0 0 10px;font-size:15px;color:#1a237e;">';
                            if ( $head_icon_url ) {
                                echo '<img src="' . esc_url( $head_icon_url ) . '" alt="" style="height:18px;vertical-align:middle;margin-left:6px;"> ';
                            } elseif ( $head_icon ) {
                                echo '<span class="cgs-icon cgs-icon-' . esc_attr( $head_icon ) . '" style="margin-left:6px;"></span> ';
                            }
                            echo esc_html( $head_name ) . '</h3>';
                            $__cols = 2;
                            if ( isset( $step_meta[ $step_num ]['columns'] ) ) $__cols = max(1, min(6, (int) $step_meta[ $step_num ]['columns'] ));
                            elseif ( isset( $step_meta[ (string) $step_num ]['columns'] ) ) $__cols = max(1, min(6, (int) $step_meta[ (string) $step_num ]['columns'] ));
                            echo '<div class="cgs-step-fields cgs-layout-canvas" data-step-cols="' . $__cols . '">';
                            // column control bar
foreach ( $pfields as $field ) {
                                $key = esc_attr( $field['field_key'] );
                                $label = esc_html( $field['label'] );
                                $ph = esc_attr( $field['placeholder'] );
                                $req = ! empty( $field['is_required'] ) ? 'required' : '';
                                $val_data = array();
                                if ( ! empty( $field['validation'] ) ) {
                                    $val_data = is_array( $field['validation'] ) ? $field['validation'] : ( json_decode( $field['validation'], true ) ?: array() );
                                }
                                $maxlen = isset( $val_data['max_length'] ) ? absint( $val_data['max_length'] ) : 0;
                                $charset = isset( $val_data['charset'] ) ? $val_data['charset'] : '';
                                if ( ! $charset && in_array( $field['field_type'], array( 'number', 'tel' ), true ) ) {
                                    $charset = 'numeric';
                                }
                                $ml_attr = $maxlen > 0 ? ' maxlength="' . $maxlen . '" data-maxlength="' . $maxlen . '"' : '';
                                $cs_attr = $charset ? ' data-charset="' . esc_attr( $charset ) . '"' : '';
                                $num_cls = ( $charset === 'numeric' || $field['field_type'] === 'number' || $field['field_type'] === 'tel' ) ? ' cgs-numeric' : '';

                                // عرض آزاد از css_class (۱۵–۱۰۰) یا پیش‌فرض هوشمند
                                $fw = ! empty( $field['css_class'] ) ? preg_replace( '/\D/', '', $field['css_class'] ) : '';
                                if ( $fw === '' || (int) $fw < 15 ) {
                                    if ( $field['field_type'] === 'divider' || $field['field_type'] === 'table' || $field['field_type'] === 'textarea' || $field['field_type'] === 'file' ) {
                                        $fw = '100';
                                    } elseif ( $maxlen > 0 && $maxlen <= 6 ) {
                                        $fw = '25';
                                    } elseif ( $maxlen > 0 && $maxlen <= 12 ) {
                                        $fw = '33';
                                    } elseif ( $maxlen > 0 && $maxlen <= 24 ) {
                                        $fw = '50';
                                    } else {
                                        $fw = '100';
                                    }
                                }
                                $fw = (string) max( 15, min( 100, (int) $fw ) );
                                $fid = (int) ( $field['id'] ?? 0 );
                                $is_landline = (
                                    $key === 'landline' || $key === 'phone_fixed'
                                    || ( ! empty( $val_data['role'] ) && $val_data['role'] === 'landline' )
                                    || false !== strpos( $field['label'] ?? '', 'تلفن ثابت' )
                                    || false !== strpos( $field['label'] ?? '', 'شماره ثابت' )
                                    || false !== strpos( $key, 'landline' )
                                    || false !== strpos( $key, 'phone_fixed' )
                                    || ( ( $field['field_type'] ?? '' ) === 'tel' && false !== strpos( $field['label'] ?? '', 'ثابت' ) )
                                );

                                echo '<div class="cgs-field-card cgs-field-group" data-field-id="' . $fid . '" data-width="' . esc_attr( $fw ) . '" data-maxlen="' . (int) $maxlen . '" style="--cgs-fw:' . esc_attr( $fw ) . '%;width:' . esc_attr( $fw ) . '%;max-width:100%;flex:0 0 ' . esc_attr( $fw ) . '%;">';
                                echo '<span class="cgs-drag-grip" title="جابجایی">⋮⋮</span>';
                                echo '<span class="cgs-resize-handle" title="کشیدن برای تغییر عرض"><span class="cgs-resize-knob"></span></span>';
                                echo '<span class="cgs-width-badge">' . esc_html( $fw ) . '٪</span>';
                                // برچسب بیرونی ماتریس: اگر فقط واژه جدول/ماتریس باشد نشان داده نشود (صرفه‌جویی فضا)
                                $show_outer_label = true;
                                if ( $field['field_type'] === 'table' ) {
                                    $plain = trim( wp_strip_all_tags( $field['label'] ?? '' ) );
                                    if ( $plain === '' || $plain === 'جدول' || false !== strpos( $plain, 'جدول داینامیک' ) || false !== strpos( $plain, 'ماتریس داده' ) ) {
                                        $show_outer_label = false;
                                    }
                                }
                                if ( $show_outer_label ) {
                                    echo '<label class="cgs-field-label">' . $label . ( $req ? ' <span class="req">*</span>' : '' ) . '</label>';
                                }
                                $__fw = ($maxlen > 0 && $maxlen <= 8) ? 38 : (($maxlen > 0 && $maxlen <= 15) ? 52 : (($maxlen > 0 && $maxlen <= 30) ? 72 : 100));
                                echo '<div class="cgs-field-control" data-cgs-fw="' . $__fw . '" style="width:' . $__fw . '%;max-width:100%;">';

                                if ( $field['field_type'] === 'divider' ) {
                                    $dtitle = ( $label && $label !== 'divider' ) ? $label : $ph;
                                    echo '<div class="cgs-divider-block" style="width:100%;padding:10px 0;">';
                                    if ( $dtitle ) {
                                        echo '<div style="font-weight:700;color:#1a237e;margin-bottom:6px;font-size:14px;">' . esc_html( $dtitle ) . '</div>';
                                    }
                                    echo '<hr style="border:0;border-top:2px solid #c5cae9;margin:0;">';
                                    echo '</div>';
                                } elseif ( $field['field_type'] === 'table' ) {
                                    $tcols = max( 2, min( 12, (int) ( $val_data['table_cols'] ?? 3 ) ) );
                                    $trows = max( 1, min( 30, (int) ( $val_data['table_rows'] ?? 2 ) ) );
                                    $tmax  = max( $trows, min( 50, (int) ( $val_data['table_max_rows'] ?? 10 ) ) );
                                    $tcolor = ! empty( $val_data['table_color'] ) ? $val_data['table_color'] : '#1a237e';
                                    $tctext = ! empty( $val_data['table_color_text'] ) ? $val_data['table_color_text'] : '#ffffff';
                                    $thdrs  = ! empty( $val_data['table_headers'] ) && is_array( $val_data['table_headers'] ) ? $val_data['table_headers'] : array();
                                    $cls = 'cgs-dynamic-table';
                                    if ( ! empty( $val_data['table_striped'] ) ) $cls .= ' is-striped';
                                    if ( ! empty( $val_data['table_bordered'] ) ) $cls .= ' is-bordered';
                                    if ( ! empty( $val_data['table_compact'] ) ) $cls .= ' is-compact';
                                    echo '<div class="cgs-dynamic-table-wrap" style="width:100%;position:relative;" data-max-rows="' . (int) $tmax . '">';
                                    echo '<button type="button" class="cgs-matrix-help-btn" title="راهنمای فرمول" style="position:absolute;top:4px;left:4px;z-index:5;width:28px;height:28px;border-radius:50%;border:1px solid #c5cae9;background:#eef2ff;color:#1a237e;font-size:14px;cursor:pointer;line-height:1;">?</button>';
                                    echo '<table class="' . esc_attr( $cls ) . '" data-cols="' . $tcols . '" style="width:100%;--cgs-th-bg:' . esc_attr( $tcolor ) . ';--cgs-th-fg:' . esc_attr( $tctext ) . ';">';
                                    echo '<thead><tr>';
                                    for ( $ci = 0; $ci < $tcols; $ci++ ) {
                                        $ht = isset( $thdrs[ $ci ] ) ? $thdrs[ $ci ] : ( 'ستون ' . ( $ci + 1 ) );
                                        echo '<th style="background:' . esc_attr( $tcolor ) . ';color:' . esc_attr( $tctext ) . ';">' . esc_html( $ht ) . '</th>';
                                    }
                                    echo '</tr></thead><tbody>';
                                    for ( $ri = 0; $ri < $trows; $ri++ ) {
                                        echo '<tr>';
                                        for ( $ci = 0; $ci < $tcols; $ci++ ) {
                                            echo '<td><input type="text" class="cgs-input" placeholder="—"></td>';
                                        }
                                        echo '</tr>';
                                    }
                                    echo '</tbody>';
                                    $formula = $val_data['table_formula'] ?? '';
                                    if ( $formula && in_array( $formula, array( 'sum', 'avg', 'count', 'min', 'max' ), true ) ) {
                                        $flabels = array( 'sum' => 'جمع', 'avg' => 'میانگین', 'count' => 'تعداد', 'min' => 'کمینه', 'max' => 'بیشینه' );
                                        echo '<tfoot><tr>';
                                        for ( $ci = 0; $ci < $tcols; $ci++ ) {
                                            echo '<td style="background:#f1f5f9;font-weight:700;text-align:center;"><span data-cgs-agg="' . esc_attr( $formula ) . '" data-col="' . $ci . '">—</span><br><small style="font-weight:500;color:#64748b;">' . esc_html( $flabels[ $formula ] ) . '</small></td>';
                                        }
                                        echo '</tr></tfoot>';
                                    }
                                    echo '</table>';
                                    if ( ! isset( $val_data['table_addrow'] ) || ! empty( $val_data['table_addrow'] ) ) {
                                        echo '<button type="button" class="cgs-table-add-row">+ افزودن ردیف</button>';
                                    }
                                    echo '</div>';
                                } elseif ( $is_landline ) {
                                    echo '<div class="cgs-two-fields cgs-landline-row" style="display:flex;flex-direction:row;direction:ltr;gap:10px;align-items:flex-end;width:100%;">';
                                    echo '<div style="flex:0 0 88px;order:1;"><label class="cgs-sub-label" style="font-size:11px;display:block;margin-bottom:4px;text-align:center;">کد شهرستان</label>';
                                    echo '<input type="text" class="cgs-input cgs-area-code cgs-numeric" name="area_code" data-role="area_code" data-field-key="area_code" placeholder="—" maxlength="4" data-charset="numeric" readonly style="width:100%;text-align:center;font-weight:700;background:#eef2ff;color:#1a237e;direction:ltr;">';
                                    echo '</div><div style="flex:1;order:2;min-width:0;"><label class="cgs-sub-label" style="font-size:11px;display:block;margin-bottom:4px;text-align:right;direction:rtl;">شماره ثابت</label>';
                                    echo '<input type="tel" class="cgs-input cgs-landline' . $num_cls . '" name="' . $key . '" data-role="landline" placeholder="' . ( $ph ?: 'بدون کد شهر' ) . '" ' . $req . $ml_attr . $cs_attr . ' style="direction:ltr;text-align:left;">';
                                    echo '</div></div>';
                                } elseif ( $field['field_type'] === 'textarea' ) {
                                    echo '<textarea class="cgs-input" name="' . $key . '" rows="3" placeholder="' . $ph . '" ' . $req . $ml_attr . '></textarea>';
                                } elseif ( $field['field_type'] === 'select' ) {
                                    $role_attr = esc_attr( $key );
                                    $extra_cls = '';
                                    if ( $key === 'province' || strpos( $key, 'province' ) !== false ) { $extra_cls = ' cgs-province'; $role_attr = 'province'; }
                                    if ( $key === 'city' || strpos( $key, 'city' ) !== false ) { $extra_cls = ' cgs-city'; $role_attr = 'city'; }
                                    echo '<select class="cgs-input' . $extra_cls . '" name="' . $key . '" ' . $req . ' data-role="' . $role_attr . '"><option value="">انتخاب کنید</option>';
                                    if ( $role_attr === 'province' && function_exists( 'cgs_get_iran_locations' ) ) {
                                        foreach ( array_keys( cgs_get_iran_locations() ) as $prov ) {
                                            echo '<option value="' . esc_attr( $prov ) . '">' . esc_html( $prov ) . '</option>';
                                        }
                                    }
                                    echo '</select>';
                                } elseif ( $field['field_type'] === 'file' ) {
                                    echo '<div class="cgs-upload-row" style="display:flex;gap:12px;align-items:flex-start;width:100%;flex-wrap:wrap;">';
                                    echo '<div class="cgs-upload-input-wrap" style="flex:1;min-width:140px;">';
                                    echo '<input type="file" class="cgs-input cgs-file-input" name="' . $key . '" accept="image/*,.pdf" ' . $req . ' data-preview-target="cgs-prev-' . $key . '">';
                                    echo '</div>';
                                    echo '<div class="cgs-upload-preview" id="cgs-prev-' . $key . '" data-empty="1" style="flex:0 0 110px;width:110px;height:110px;border:2px dashed #c5cae9;border-radius:12px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#94a3b8;font-size:11px;text-align:center;padding:6px;">پیش‌نمایش تصویر</div>';
                                    echo '</div>';
                                } elseif ( $field['field_type'] === 'date' ) {
                                    echo '<input type="text" class="cgs-input cgs-datepicker cgs-jalali-date" name="' . $key . '" placeholder="' . ( $ph ?: 'تاریخ' ) . '" ' . $req . ' autocomplete="off">';
                                } else {
                                    $itype = in_array( $field['field_type'], array( 'email', 'tel', 'number' ), true ) ? $field['field_type'] : 'text';
                                    echo '<input type="' . esc_attr( $itype ) . '" class="cgs-input' . $num_cls . '" name="' . $key . '" placeholder="' . $ph . '" ' . $req . $ml_attr . $cs_attr . '>';
                                }
                                if ( $maxlen > 0 ) {
                                    echo '<span class="cgs-maxlen-hint">حداکثر ' . (int) $maxlen . ' کاراکتر</span>';
                                }
                                echo '</div>'; // control
                                echo '</div>'; // card
                            }
                            echo '</div><div class="cgs-step-actions" style="margin-top:14px;display:flex;gap:8px;">';
                            echo '<div class="cgs-form-actions cgs-step-nav">';
                            if ($si>1) echo '<button type="button" class="cgs-btn cgs-btn-secondary cgs-prev-step" style="background:#eef2ff!important;color:#1a237e!important;border:2px solid #1a237e!important;padding:10px 20px!important;border-radius:10px!important;font-weight:700!important;">مرحله قبل</button>';
                            if ($si < $total_prev) echo '<button type="button" class="cgs-btn cgs-btn-primary cgs-next-step" style="background:#1a237e!important;color:#fff!important;border:none!important;padding:10px 20px!important;border-radius:10px!important;font-weight:700!important;">مرحله بعد</button>';
                            else echo '<button type="button" class="cgs-btn cgs-btn-success" id="cgs-preview-submit" style="background:#16a34a!important;color:#fff!important;border:none!important;padding:10px 20px!important;border-radius:10px!important;font-weight:700!important;">ثبت نهایی (تست)</button>';
                            echo '</div>';
                            echo '</div></div>';
                        }
                        echo '</form><div class="cgs-form-message" style="display:none;"></div></div>';
                        echo '<script>window.cgsLocations=' . wp_json_encode(cgs_get_iran_locations()) . ';</script>';
                        if ( class_exists( 'CGS_Form_Styles' ) ) {
                            echo '<style id="cgs-saved-form-styles">' . CGS_Form_Styles::get_css( $current_type ) . '</style>';
                            echo '<style id="cgs-preview-layout-css">'
                                . '#cgs-live-preview .cgs-step-fields{display:grid!important;grid-template-columns:repeat(var(--cgs-cols,2),minmax(0,1fr))!important;gap:12px!important;min-height:60px;padding:12px;border:2px dashed #cbd5e1;border-radius:12px;}'
                                . '#cgs-live-preview .cgs-step-fields.cgs-has-guides{border-color:#9fa8da;background-image:repeating-linear-gradient(to left,transparent 0,transparent calc(100%/var(--cgs-cols,1) - 1px),rgba(26,35,126,.15) calc(100%/var(--cgs-cols,1) - 1px),rgba(26,35,126,.15) calc(100%/var(--cgs-cols,1)));}'
                                . '#cgs-live-preview .cgs-field-group{position:relative;box-sizing:border-box!important;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px;}'
                                . '#cgs-live-preview .cgs-resize-handle{position:absolute;left:0;top:0;bottom:0;width:8px;cursor:ew-resize;z-index:5;}'
                                . '#cgs-live-preview .cgs-field-group:hover .cgs-resize-handle{background:rgba(26,35,126,.15);}'
                                . '.cgs-fb-wrap select,.cgs-admin-wrap select,#st-form-columns,#st-label-position,.cgs-step-columns{-webkit-appearance:none;appearance:none;padding-left:28px!important;padding-right:10px!important;background-repeat:no-repeat;background-position:left 10px center;}'
                                . '</style>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>


<script>
jQuery(function($){
  $(document).on('click', '.cgs-matrix-help-btn, #cgs-open-formula-help', function(e){
    e.preventDefault();
    var $p = $('#cgs-formula-help-panel');
    $p.show().attr('open', true);
    $('html,body').animate({scrollTop: $p.offset().top - 80}, 200);
  });
  $('#cgs-open-formula-help').on('click', function(){
    var $p = $('#cgs-formula-help-panel');
    $p.toggle();
    if ($p.is(':visible')) { $p.attr('open', true); }
  });
});
</script>

<!-- plugins moved to settings -->
<div id="cgs-modal" style="display:none;">
    <div class="cgs-modal-backdrop"></div>
    <div class="cgs-modal-content">
        <h2 id="cgs-modal-title">افزودن فیلد جدید</h2>
        <input type="hidden" id="mf-id" value="">
        <div class="cgs-modal-body">
            <p style="background:#f0f4ff;padding:12px;border-radius:8px;border:1px solid #c5cae9;">
                <label style="font-weight:700;color:#1a237e;">نقش ویژه فیلد (راهنمای ادمین)</label>
                <select id="mf-role" class="widefat" style="margin-top:6px;">
                    <option value="">— عادی (بدون نقش ویژه) —</option>
                    <option value="province">استان (لیست استان‌های ایران + اتصال به شهر)</option>
                    <option value="city">شهر (وابسته به استان انتخاب‌شده)</option>
                    <option value="mobile">موبایل (فقط عدد، ۱۱ رقم)</option>
                    <option value="landline">تلفن ثابت (شماره بدون کد)</option>
                    <option value="area_code">کد مخابراتی شهرستان (خودکار از استان)</option>
                    <option value="national_id">کد ملی (۱۰ رقم)</option>
                    <option value="email">ایمیل</option>
                    <option value="full_name">نام و نام خانوادگی</option>
                    <option value="postal_code">کد پستی</option>
                    <option value="address">آدرس کامل</option>
                    <option value="birth_date">تاریخ تولد (تقویم شمسی)</option>
                    <option value="id_card_front">تصویر روی کارت ملی</option>
                    <option value="id_card_back">تصویر پشت کارت ملی</option>
                    <option value="website">نشانی اینترنتی / وب‌سایت</option>
                    <option value="person_type">نوع شخص (حقیقی / حقوقی)</option>
                    <option value="business_type">نوع صنف (فهرست صمت)</option>
                    <option value="business_detail">توضیح جزئی صنف (دستی)</option>
                    <option value="company_name">نام شرکت / فروشگاه</option>
                    <option value="economic_code">کد اقتصادی</option>
                    <option value="national_id_company">شناسه ملی شرکت</option>
                    <optgroup label="— اطلاعات بانکی —"></optgroup>
                    <option value="bank_account">شماره حساب</option>
                    <option value="bank_card">شماره کارت بانکی</option>
                    <option value="card_name">نام روی کارت</option>
                    <option value="bank_name">نام بانک</option>
                    <option value="bank_branch">نام شعبه</option>
                    <option value="branch_code">کد شعبه</option>
                    <option value="sheba">شماره شبا</option>
                    <option value="account_holder">نام صاحب حساب</option>
                    <optgroup label="— تضمین (چک / سفته) —"></optgroup>
                    <option value="guarantee_type">نوع تضمین (چک یا سفته)</option>
                    <option value="check_bank">نام بانک چک</option>
                    <option value="check_date">تاریخ ثبت چک</option>
                    <option value="check_subject">موضوع چک</option>
                    <option value="check_sheba">شبا چک</option>
                    <option value="check_series">شماره سری چک</option>
                    <option value="check_serial">شماره سریال چک</option>
                    <option value="check_sayad_image">تصویر ثبت چک در صیاد</option>
                    <option value="promissory_count">تعداد برگ سفته</option>
                    <option value="promissory_amount">مبلغ سفته</option>
                    <option value="promissory_date">تاریخ سفته</option>
                    <option value="promissory_serial">شماره سریال سفته</option>
                    <option value="promissory_image">تصویر سفته</option>
                    <option value="guarantee_owner">صاحب سند تضمین (خودم / شخص دیگر)</option>
                    <option value="guarantor_name">نام صاحب سند (شخص دیگر)</option>
                    <option value="guarantor_national_id">کد ملی صاحب سند</option>
                    <option value="guarantor_mobile">موبایل صاحب سند</option>
                    <option value="guarantor_relation">نسبت با متقاضی</option>
                    <option value="guarantor_sign_status">وضعیت امضای دیجیتال صاحب سند</option>
                </select>
                <span class="description" style="display:block;margin-top:6px;color:#555;">با انتخاب هر مورد، تنظیمات لازم به‌صورت خودکار اعمال می‌شود.</span>
            </p>
            <p><label>برچسب فیلد *</label><input type="text" id="mf-label" class="widefat" placeholder="مثلاً: نام و نام خانوادگی"></p>
            <p><label>نوع فیلد</label>
                <select id="mf-type" class="widefat" style="font-size:14px;">
                    <optgroup label="—— طراحی و چیدمان ——">
                        <option value="table">ماتریس داده — ستون / ردیف / رنگ / محاسبه</option>
                        <option value="divider">فاصله / بخش‌بندی (عنوان اختیاری)</option>
                    </optgroup>
                    <optgroup label="—— ورودی‌های استاندارد ——">
                    <?php
                    $cgs_skip_types = array( 'table', 'divider' );
                    foreach ( $field_types as $k => $v ) :
                        if ( in_array( $k, $cgs_skip_types, true ) ) continue;
                    ?>
                        <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                    </optgroup>
                </select>
            </p>
            <p class="description" style="color:#1a237e;font-weight:600;">برای ساخت ماتریس: «ماتریس داده» را انتخاب کنید — تنظیمات ستون/ردیف/رنگ زیر همین فرم ظاهر می‌شود.</p>
            <p><label>متن راهنما (Placeholder)</label><input type="text" id="mf-placeholder" class="widefat"></p>
            <p id="mf-options-wrap" style="display:none;"><label>گزینه‌ها (هر خط یک گزینه)</label><textarea id="mf-options" class="widefat" rows="4"></textarea></p>
            <div id="mf-file-wrap" style="display:none;">
                <p><label>فرمت‌های مجاز (با کاما جدا کنید)</label>
                    <input type="text" id="mf-file-types" class="widefat" value="jpg,jpeg,png,pdf,webp" placeholder="jpg,jpeg,png,pdf,webp">
                </p>
                <p><label>حداکثر حجم فایل (کیلوبایت)</label>
                    <input type="number" id="mf-file-size" class="widefat" value="2048" min="50" max="20480" placeholder="2048 = 2 مگابایت">
                    <span class="description">مثلاً 1024 = ۱ مگابایت ، 2048 = ۲ مگابایت</span>
                </p>
            </div>
            <div id="mf-table-wrap" style="display:none;background:linear-gradient(135deg,#f8fafc,#eef2ff);border:1px solid #c5cae9;border-radius:14px;padding:14px;margin:10px 0;box-shadow:0 4px 14px rgba(26,35,126,.06);">
                <p style="margin:0 0 10px;font-weight:700;color:#1a237e;font-size:14px;">⚙️ پیکربندی ماتریس داده</p>
                <p><label>عنوان جدول (لیبل)</label>
                    <input type="text" id="mf-table-label" class="widefat" placeholder="مثلاً: مشخصات کالاها">
                </p>
                <p style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                    <label>تعداد ستون (۲–۱۲)
                        <input type="number" id="mf-table-cols" class="widefat" min="2" max="12" value="3">
                    </label>
                    <label>ردیف اولیه (۱–۳۰)
                        <input type="number" id="mf-table-rows" class="widefat" min="1" max="30" value="2">
                    </label>
                    <label>حداکثر ردیف کاربر
                        <input type="number" id="mf-table-max-rows" class="widefat" min="1" max="50" value="10">
                    </label>
                </p>
                <p style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <label>رنگ هدر
                        <input type="color" id="mf-table-color" value="#1a237e" style="width:100%;height:36px;padding:2px;">
                    </label>
                    <label>رنگ متن هدر
                        <input type="color" id="mf-table-color-text" value="#ffffff" style="width:100%;height:36px;padding:2px;">
                    </label>
                </p>
                <p><label>عناوین ستون‌ها (با کاما جدا کنید — به تعداد ستون‌ها)</label>
                    <input type="text" id="mf-table-headers" class="widefat" placeholder="نام کالا، تعداد، مبلغ">
                </p>
                <p style="display:flex;flex-wrap:wrap;gap:14px;">
                    <label><input type="checkbox" id="mf-table-striped" value="1" checked> ردیف‌های راه‌راه</label>
                    <label><input type="checkbox" id="mf-table-bordered" value="1" checked> حاشیه سلول‌ها</label>
                    <label><input type="checkbox" id="mf-table-compact" value="1"> حالت فشرده</label>
                    <label><input type="checkbox" id="mf-table-addrow" value="1" checked> اجازه افزودن ردیف توسط کاربر</label>
                </p>
                <p><label>ردیف محاسباتی (مانند اکسل)</label>
                    <select id="mf-table-formula" class="widefat">
                        <option value="">— بدون محاسبه —</option>
                        <option value="sum">جمع ستون (SUM)</option>
                        <option value="avg">میانگین ستون (AVG)</option>
                        <option value="count">تعداد پرشده (COUNT)</option>
                        <option value="min">کمینه (MIN)</option>
                        <option value="max">بیشینه (MAX)</option>
                        <option value="product">ضرب ستون (PRODUCT)</option>
                    </select>
                <p><label>فرمول اکسل‌مانند (اختیاری)</label>
                    <input type="text" id="mf-table-excel" class="widefat" dir="ltr" placeholder="=A1+B1 یا =SUM(A:A)">
                    <span class="description">سلول‌ها: A1 = ردیف۱ ستون۱. با = شروع کنید. برای ادمین در پیش‌نمایش قابل تست است.</span>
                </p>
                <p><button type="button" class="button cgs-matrix-help-btn">📘 راهنمای فرمول‌نویسی</button></p>
                </p>
                <p class="description">عناوین خالی = «ستون ۱…». ردیف محاسباتی در پایین جدول برای <strong>همه ستون‌ها</strong> همان تابع را نشان می‌دهد. فقط اعداد در محاسبه شرکت می‌کنند.</p>
                <details class="cgs-help" open><summary>راهنما: فرمول‌نویسی ماتریس (مثال)</summary><div class="cgs-help-body">
                <p>مثال SUM: ستون‌ها ۱۰۰ و ۵۰ → ردیف پایین <b>۱۵۰</b>. AVG همان ستون‌ها → <b>۷۵</b>. COUNT → <b>۲</b>.</p>
                <ol>
                  <li>عناوین ستون را با کاما بنویسید.</li>
                  <li>ردیف محاسباتی را انتخاب کنید.</li>
                  <li>ذخیره → در پیش‌نمایش عدد بزنید و نتیجه را ببینید.</li>
                </ol>
                </div></details>
            </div>
            <p><label>شماره مرحله</label>
                <select id="mf-step" class="widefat">
                    <option value="1">مرحله 1</option>
                    <option value="2">مرحله 2</option>
                    <option value="3">مرحله 3</option>
                    <option value="4">مرحله 4</option>
                    <option value="5">مرحله 5</option>
                    <option value="6">مرحله 6</option>
                    <option value="7">مرحله 7</option>
                    <option value="8">مرحله 8</option>
                    <option value="9">مرحله 9</option>
                    <option value="10">مرحله 10</option>
                    <option value="11">مرحله 11</option>
                    <option value="12">مرحله 12</option>
                    <option value="13">مرحله 13</option>
                    <option value="14">مرحله 14</option>
                    <option value="15">مرحله 15</option>
                    <option value="16">مرحله 16</option>
                    <option value="17">مرحله 17</option>
                    <option value="18">مرحله 18</option>
                    <option value="19">مرحله 19</option>
                    <option value="20">مرحله 20</option>
                </select>
            </p>
            <p><label>عرض فیلد (چیدمان ستونی)</label>
                <select id="mf-width" class="widefat">
                    <option value="100">کامل (۱۰۰٪)</option>
                    <option value="50">نیم (۵۰٪)</option>
                    <option value="33">یک‌سوم (۳۳٪)</option>
                    <option value="25">یک‌چهارم (۲۵٪)</option>
                </select>
            </p>
            <p><label>حداکثر تعداد کاراکتر (۰ = بدون محدودیت)</label>
                <input type="number" id="mf-maxlen" class="widefat" min="0" max="500" value="0" placeholder="مثلاً ۱۱ برای موبایل">
            </p>
            <p style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <label>حداقل سن (تاریخ تولد)<input type="number" id="mf-min-age" class="widefat" min="0" max="120" value="0" placeholder="مثلاً ۱۸"></label>
                <label>حداکثر سن<input type="number" id="mf-max-age" class="widefat" min="0" max="120" value="0" placeholder="مثلاً ۷۰"></label>
            </p>
            <p class="description">فقط برای فیلد تاریخ تولد معنادار است؛ بر اساس هر فرم/طرح جدا تنظیم می‌شود.</p>

            <p><label><input type="checkbox" id="mf-required" value="1"> این فیلد الزامی باشد</label></p>
        </div>

        <div class="cgs-cond-box" style="margin:16px 0;padding:14px;background:linear-gradient(135deg,#f8fafc,#eef2ff);border:1px solid #c5cae9;border-radius:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                <strong style="color:#1a237e;">⚡ منطق شرطی (نمایش/مخفی فیلد)</strong>
                <label style="font-weight:600;font-size:12px;"><input type="checkbox" id="cgs-cond-enabled"> فعال</label>
            </div>
            <details class="cgs-help" style="margin-bottom:8px;">
                <summary style="cursor:pointer;font-size:12px;color:#3949ab;">راهنما: کوئری شرطی</summary>
                <p style="font-size:12px;line-height:1.6;margin:6px 0;">اگر شرط برقرار باشد فیلد <em>نمایش</em> یا <em>مخفی</em> می‌شود.</p>
                <div class="cgs-cond-guide-card">
                  <div class="step"><span class="num">۱</span> ویرایش فیلد</div>
                  <div class="step"><span class="num">۲</span> تیک فعال منطق شرطی</div>
                  <div class="step"><span class="num">۳</span> شرط: فیلد مرجع + عملگر + مقدار</div>
                  <div class="step"><span class="num">۴</span> ذخیره فیلد</div>
                </div>
                <p style="margin:8px 0 0;"><a class="button button-small" href="<?php echo esc_url( defined( 'CGS_PLUGIN_URL' ) ? ( CGS_PLUGIN_URL . 'modules/form-builder/help/conditional-guide.html' ) : plugins_url( 'modules/form-builder/help/conditional-guide.html', dirname( dirname( dirname( __FILE__ ) ) ) . '/city-ghest-system.php' ) ); ?>" target="_blank" rel="noopener">📖 راهنمای تصویری کامل</a></p>
                <details style="margin-top:10px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:8px 10px;">
                  <summary style="cursor:pointer;font-weight:700;color:#1a237e;font-size:12px;">📚 شرط‌های تو در تو (چند شرط با هم)</summary>
                  <div style="font-size:12px;line-height:1.7;margin-top:8px;color:#334155;">
                    <p><strong>منطق «و»:</strong> همه شرط‌ها باید برقرار باشند تا فیلد دیده شود.</p>
                    <p><strong>منطق «یا»:</strong> اگر فقط یکی برقرار باشد کافی است.</p>
                    <div class="cgs-cond-guide-card">
                      <div class="step"><span class="num">۱</span> شرط اول: person_type برابر حقوقی</div>
                      <div class="step"><span class="num">۲</span> شرط دوم: province برابر تهران</div>
                      <div class="step"><span class="num">۳</span> منطق = و → فقط حقوقی‌های تهران</div>
                      <div class="step"><span class="num">۴</span> منطق = یا → حقوقی یا تهرانی</div>
                    </div>
                    <p style="margin-top:8px;">برای شرط عمیق‌تر: فیلد میانی بسازید که خودش شرطی است، بعد فیلد سوم را به آن وابسته کنید.</p>
                  </div>
                </details>
            </details>
            <div id="cgs-cond-body" style="display:none;">
                <p style="margin:0 0 8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <label>عمل:</label>
                    <select id="cgs-cond-action"><option value="show">نمایش اگر شرط برقرار</option><option value="hide">مخفی اگر شرط برقرار</option></select>
                    <label>منطق:</label>
                    <select id="cgs-cond-logic"><option value="and">و (همه)</option><option value="or">یا (یکی)</option></select>
                </p>
                <div id="cgs-cond-rules"></div>
                <button type="button" class="button button-small" id="cgs-cond-add-rule" style="margin-top:6px;">+ شرط</button>
            </div>
        </div>
        <div class="cgs-modal-footer">
            <button type="button" id="mf-save" class="button button-primary">ذخیره</button>
            <button type="button" id="mf-cancel" class="button">انصراف</button>
            <span id="mf-msg"></span>
        </div>
    </div>
</div>

<style>

.cgs-builder-grid { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 16px; align-items: start; }
@media (max-width: 1400px) {
  .cgs-builder-grid { grid-template-columns: 1fr 1fr !important; }
  .cgs-builder-col-preview { grid-column: 1 / -1; }
}
@media (max-width: 900px) {
  .cgs-builder-grid { grid-template-columns: 1fr !important; }
}

.cgs-panel { background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; }
.cgs-field-row { display:flex; align-items:center; gap:10px; background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; padding:10px 12px; margin-bottom:8px; }
.cgs-field-row:hover { background:#f0f4ff; }
.cgs-handle { cursor:grab; color:#999; }
.cgs-field-main { flex:1; }
.cgs-field-main small { display:block; color:#777; margin-top:2px; }
.cgs-field-btns { display:flex; gap:4px; }
.cgs-empty { text-align:center; color:#999; padding:30px; background:#f9f9f9; border-radius:6px; list-style:none; }
.cgs-style-row { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.cgs-style-row label { width:70px; margin:0; }
.cgs-preview-box { background:#f8f9fc; border:1px solid #e0e4ec; border-radius:10px; padding:24px; min-height:300px; }
.cgs-preview-field { margin-bottom:16px; }
.cgs-preview-field label { display:block; font-weight:600; margin-bottom:6px; }
.cgs-preview-field input, .cgs-preview-field select, .cgs-preview-field textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; }
.cgs-preview-file { padding:14px; border:2px dashed #ccc; border-radius:8px; text-align:center; color:#666; background:#fff; }
#cgs-modal { position:fixed; inset:0; z-index:100000; display:none; align-items:center; justify-content:center; }
#cgs-modal.active { display:flex !important; }
.cgs-modal-backdrop { position:absolute; inset:0; background:rgba(0,0,0,0.55); }
.cgs-modal-content { position:relative; background:#fff; width:95%; max-width:480px; border-radius:12px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,0.3); max-height:90vh; overflow-y:auto; }
.cgs-modal-body p { margin-bottom:14px; }
.cgs-modal-body label { display:block; font-weight:600; margin-bottom:4px; }
.cgs-modal-footer { margin-top:20px; display:flex; gap:10px; align-items:center; }

/* ===== PREVIEW LAYOUT — clean cards, drag, resize ===== */
#cgs-live-preview .cgs-step-fields,
#cgs-live-preview .cgs-layout-canvas {
  display: flex !important;
  flex-wrap: wrap !important;
  align-items: flex-start !important;
  gap: 12px !important;
  min-height: 80px;
  padding: 14px !important;
  background: #f1f5f9 !important;
  border: 2px dashed #94a3b8 !important;
  border-radius: 14px !important;
  grid-template-columns: none !important;
}
#cgs-live-preview .cgs-step-col-bar {
  flex: 0 0 100% !important;
  width: 100% !important;
  grid-column: auto !important;
}
#cgs-live-preview .cgs-field-card,
#cgs-live-preview .cgs-field-group {
  position: relative !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 6px !important;
  box-sizing: border-box !important;
  background: #fff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 12px !important;
  padding: 12px 14px 12px 18px !important;
  margin: 0 !important;
  cursor: grab !important;
  box-shadow: 0 2px 10px rgba(15,23,42,0.06) !important;
  min-width: 120px;
}
#cgs-live-preview .cgs-drag-grip { cursor: grab !important; user-select: none; }
#cgs-live-preview .cgs-drag-grip:active { cursor: grabbing !important; }
#cgs-live-preview .cgs-field-group input,
#cgs-live-preview .cgs-field-group select,
#cgs-live-preview .cgs-field-group textarea { pointer-events: auto !important; cursor: auto !important; }
#cgs-live-preview .cgs-field-card.cgs-w-25,
#cgs-live-preview .cgs-field-group.cgs-w-25 { flex: 0 0 calc(25% - 9px) !important; width: calc(25% - 9px) !important; max-width: calc(25% - 9px) !important; }
#cgs-live-preview .cgs-field-card.cgs-w-33,
#cgs-live-preview .cgs-field-group.cgs-w-33 { flex: 0 0 calc(33.333% - 9px) !important; width: calc(33.333% - 9px) !important; max-width: calc(33.333% - 9px) !important; }
#cgs-live-preview .cgs-field-card.cgs-w-50,
#cgs-live-preview .cgs-field-group.cgs-w-50 { flex: 0 0 calc(50% - 9px) !important; width: calc(50% - 9px) !important; max-width: calc(50% - 9px) !important; }
#cgs-live-preview .cgs-field-card.cgs-w-100,
#cgs-live-preview .cgs-field-group.cgs-w-100 { flex: 0 0 100% !important; width: 100% !important; max-width: 100% !important; }
#cgs-live-preview .cgs-drag-grip {
  position: absolute; top: 6px; left: 8px;
  font-size: 12px; color: #94a3b8; cursor: grab; user-select: none;
  letter-spacing: -2px;
}
#cgs-live-preview .cgs-resize-handle {
  position: absolute !important;
  left: 0 !important; top: 0 !important; bottom: 0 !important;
  width: 10px !important;
  cursor: ew-resize !important;
  background: linear-gradient(90deg, #c7d2fe, transparent) !important;
  border-radius: 12px 0 0 12px !important;
  opacity: 0.35 !important;
  z-index: 3 !important;
}
#cgs-live-preview .cgs-field-card:hover .cgs-resize-handle,
#cgs-live-preview .cgs-field-group:hover .cgs-resize-handle { opacity: 1 !important; }
#cgs-live-preview .cgs-field-card.is-resizing {
  outline: 2px solid #1a237e !important;
  z-index: 5 !important;
}
#cgs-live-preview .cgs-field-label {
  display: block !important;
  width: 100% !important;
  margin: 0 0 4px 0 !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  line-height: 1.45 !important;
  letter-spacing: 0 !important;
}
#cgs-live-preview .cgs-step-label {
  font-size: 11px !important;
  font-weight: 600 !important;
  color: #475569 !important;
}
#cgs-live-preview .cgs-form-step > h3,
#cgs-live-preview .cgs-step-title {
  font-size: 14px !important;
  font-weight: 700 !important;
  color: #1a237e !important;
  margin: 0 0 10px !important;
}
#cgs-live-preview .cgs-field-control {
  width: 100% !important;
  display: block !important;
}
#cgs-live-preview .cgs-field-control .cgs-input,
#cgs-live-preview .cgs-field-control input,
#cgs-live-preview .cgs-field-control select,
#cgs-live-preview .cgs-field-control textarea {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  height: 40px !important;
  min-height: 40px !important;
  padding: 8px 10px !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  font-size: 13px !important;
  background: #fff !important;
}
#cgs-live-preview .cgs-field-control textarea.cgs-input {
  height: auto !important;
  min-height: 72px !important;
}
#cgs-live-preview .cgs-two-fields {
  display: flex !important;
  gap: 8px !important;
  width: 100% !important;
}
#cgs-live-preview .cgs-maxlen-hint {
  display: block;
  font-size: 10px;
  color: #64748b;
  margin-top: 2px;
}
#cgs-live-preview .cgs-layout-handles { display: none !important; }
#cgs-live-preview .cgs-sortable-placeholder {
  border: 2px dashed #1a237e !important;
  background: #e8eaf6 !important;
  border-radius: 12px !important;
  visibility: visible !important;
  min-height: 56px;
}

</style>






<style id="cgs-dnd-stable">
/* دستگیره‌ها — سازگار با jQuery UI Sortable نسخه 4.0.3 */
#cgs-live-preview .cgs-field-group,
#cgs-live-preview .cgs-field-card {
  position: relative !important;
  pointer-events: auto !important;
}
#cgs-live-preview input,
#cgs-live-preview select,
#cgs-live-preview textarea {
  pointer-events: auto !important;
  opacity: 1 !important;
  user-select: text !important;
  -webkit-user-select: text !important;
  z-index: 5 !important;
  position: relative !important;
}
#cgs-live-preview .cgs-drag-grip {
  position: absolute !important;
  top: 4px !important;
  right: 4px !important;
  z-index: 20 !important;
  cursor: grab !important;
  background: #1a237e !important;
  color: #fff !important;
  padding: 4px 8px !important;
  border-radius: 6px !important;
  font-size: 13px !important;
  pointer-events: auto !important;
  user-select: none !important;
}
#cgs-live-preview .cgs-resize-handle {
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 14px !important;
  cursor: ew-resize !important;
  z-index: 15 !important;
  pointer-events: auto !important;
  background: rgba(26,35,126,0.15) !important;
}
#cgs-live-preview .cgs-field-group:hover .cgs-resize-handle {
  background: rgba(26,35,126,0.35) !important;
}
#cgs-live-preview .cgs-width-badge { pointer-events: none !important; }
.cgs-sortable-placeholder {
  border: 2px dashed #1a237e !important;
  background: #e8eaf6 !important;
  border-radius: 12px !important;
  visibility: visible !important;
  min-height: 56px !important;
}
</style>

>
>
