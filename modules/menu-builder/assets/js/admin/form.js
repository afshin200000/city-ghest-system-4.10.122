/**
 * CGS MB Admin — Form module (v4.10.89)
 * fillForm extracted from admin.js
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  /* Local helpers — must NOT depend on admin.js closure (extraction break fixed in 4.10.93) */
  function S(sel, v) {
    var $e = $(sel);
    if ($e.length) { $e.val(v); }
  }
  function C(sel, on) {
    var $e = $(sel);
    if ($e.length) { $e.prop('checked', !!on); }
  }
  function defMenu() {
    if (w.CGS_MB_Modules.defaults && typeof w.CGS_MB_Modules.defaults.defMenu === 'function') {
      return w.CGS_MB_Modules.defaults.defMenu();
    }
    return { id: 'main', title: 'منوی اصلی', slug: 'main', items: [] };
  }

  /**
   * @param {object} m menu data
   * @param {object} deps { state, schemaWriteFrom, renderItems, schedulePreview, syncCtaPreviews, refreshCtaSample }
   */
  function fillForm(m, deps) {
    deps = deps || {};
    if (!deps.state) return;
    if (typeof deps.schemaWriteFrom !== 'function') return;

    if (!m) m = defMenu();
    deps.schemaWriteFrom(m);

    if (!m) return;
    S('#m-id', m.id || '');
    S('#m-title', m.title || '');
    S('#m-slug', m.slug || '');
    S('#m-layout', m.layout || 'horizontal');
    S('#m-placement', m.placement || 'header');
    S('#m-effect', m.effect || 'slide');
    S('#m-effect-speed', m.effect_speed != null ? m.effect_speed : 220);
    S('#m-sound', m.sound || 'none');
    S('#m-sound-vol', m.sound_vol != null ? m.sound_vol : 35);
    $('#m-sound-vol-val').text(m.sound_vol != null ? m.sound_vol : 35);
    S('#m-sub-dir', m.sub_open_dir || 'bottom');
    S('#m-logo-url', m.logo_url || '');
    $('#m-hamburger').prop('checked', m.hamburger == null ? true : !!m.hamburger);
    $('#m-search-in-bar, #m-search-box').prop('checked', !!m.search_in_bar || !!m.search_box);
    S('#m-search-placeholder', m.search_placeholder || 'جستجو…');
    S('#m-root-style', m.root_style || 'link');
    S('#m-root-glass-color', m.root_glass_color || '#e11d48');
    S('#m-root-glass-hex', m.root_glass_color || '#e11d48');
    S('#m-root-glass-size', m.root_glass_size || 'md');
    S('#m-root-glass-radius', m.root_glass_radius != null ? m.root_glass_radius : 22);
    S('#m-cta-pos', m.cta_pos || 'end');
    S('#m-cta-size', m.cta_size || 'md');
    S('#m-cta-radius', m.cta_radius != null ? m.cta_radius : 22);
    S('#m-cta-color', m.cta_color || '#e11d48');
    S('#m-cta-color-hex', m.cta_color || '#e11d48');
    S('#m-cta-style', m.cta_style || 'glass-capsule');
    S('#m-cta-icon', m.cta_icon || '');
    S('#m-cta-img', m.cta_img || '');
    S('#m-cta-font', m.cta_font || 'inherit');
    S('#m-cta-font-size', m.cta_font_size != null ? m.cta_font_size : 14);
    S('#m-cta-color2', m.cta_color2 || m.cta_color || '#0097a7');
    S('#m-cta-color2-hex', m.cta_color2 || m.cta_color || '#0097a7');
    S('#m-cta-emoji', m.cta_emoji || m.cta_icon || '');
    S('#m-cta-opacity', m.cta_opacity != null ? m.cta_opacity : 100);
    S('#m-cta-scale', m.cta_scale != null ? m.cta_scale : 100);
    if ($('#m-cta-scale-val').length) $('#m-cta-scale-val').text((m.cta_scale != null ? m.cta_scale : 100) + '%');
    if ($('#m-cta-opacity-val').length) $('#m-cta-opacity-val').text((m.cta_opacity != null ? m.cta_opacity : 100) + '%');
    S('#m-cta-light', m.cta_light || 'tl');
    S('#m-cta-target', m.cta_target || 'bar');
    S('#m-cta-x', m.cta_x != null ? m.cta_x : 0);
    S('#m-cta-y', m.cta_y != null ? m.cta_y : 0);
    S('#m-cta-col', m.cta_col != null ? m.cta_col : 1);
    S('#m-cta-color-mode', m.cta_color_mode || 'gradient');
    S('#m-cta-role', m.cta_role || 'cta_link');
    if (!(m.cta_text || '').trim()) { /* keep default in UI */ }
    S('#m-bg-type', m.bg_type || 'solid');
    S('#m-gradient-dir', m.gradient_dir || 'ltr');
    S('#m-bg', m.bg_color || '#0f172a'); S('#m-bg-hex', m.bg_color || '#0f172a');
    S('#m-bg2', m.bg_color2 || '#1e3a5f'); S('#m-bg2-hex', m.bg_color2 || '#1e3a5f');
    S('#m-bg-img', m.bg_image || '');
    S('#m-bg-img-opacity', m.bg_image_opacity != null ? m.bg_image_opacity : 100);
    $('#m-bg-img-opacity-val').text((m.bg_image_opacity != null ? m.bg_image_opacity : 100) + '%');
    S('#m-text', m.text_color || '#f8fafc'); S('#m-text-hex', m.text_color || '#f8fafc');
    S('#m-hover', m.hover_color || '#38bdf8'); S('#m-hover-hex', m.hover_color || '#38bdf8');
    S('#m-active', m.active_color || '#6366f1'); S('#m-active-hex', m.active_color || '#6366f1');
    S('#m-radius', m.radius != null ? m.radius : 12);
    C('#m-shadow', m.shadow !== 0);
    C('#m-sticky', m.sticky);
    C('#m-rtl', m.rtl !== 0);
    S('#m-page-anim-in', m.page_anim_in || 'none');
    S('#m-page-anim-out', m.page_anim_out || 'none');
    S('#m-mega-cols', m.mega_cols || 3);
    C('#m-mobile-sync', m.mobile_sync);
    S('#m-mobile-endpoint', m.mobile_endpoint || '');
    S('#m-trigger', m.trigger || 'hover');
    S('#m-intent-ms', m.intent_ms != null ? m.intent_ms : 200);
    S('#m-breakpoint', m.breakpoint || 768);
    C('#m-search-box', m.search_box);
    C('#m-fullwidth-sub', m.fullwidth_sub);
    C('#m-sticky-hide', m.sticky_hide);
    S('#m-cta-text', m.cta_text || '');
    S('#m-cta-url', m.cta_url || '');
    var home = cgsMenuBuilder.home || '/';
    $('#cgs-ma-json-url').text(home + (home.indexOf('?') >= 0 ? '&' : '?') + 'cgs_menu_json=' + (m.id || 'main'));
  
  }

  w.CGS_MB_Modules.form = { fillForm: fillForm };
})(window, jQuery);
