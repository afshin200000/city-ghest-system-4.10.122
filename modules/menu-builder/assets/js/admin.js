/**
 * City Ghest Menu Builder — Admin (clean rewrite v4.10.43)
 * Preview mirrors frontend .cgs-nav structure with live interactions.
 */
(function ($) {
  'use strict';
  if (typeof cgsMenuBuilder === 'undefined') {
    console.warn('[CGS MB] cgsMenuBuilder missing — using fallbacks');
    window.cgsMenuBuilder = { ajax: (typeof ajaxurl !== 'undefined' ? ajaxurl : ''), nonce: '', menus: {}, effects: {}, layouts: {}, sounds: {}, pageAnims: {}, iconBank: {icons:[]}, badgeShapes: [], megaPresets: [], home: '/' };
  }

  var state = { id: 'main', menus: {}, current: null };
  var pvTimer = null;
  var iconTarget = null;

  function ajaxUrl() {
    return (cgsMenuBuilder && cgsMenuBuilder.ajax) || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
  }
  function nonce() {
    return (cgsMenuBuilder && cgsMenuBuilder.nonce) || '';
  }
  function uid() {
    return 'i' + Math.random().toString(36).slice(2, 9);
  }
  function toast(msg) {
    var $t = $('#cgs-ma-toast');
    if (!$t.length) {
      $t = $('<div id="cgs-ma-toast"></div>').css({
        position: 'fixed', bottom: 24, left: 24, zIndex: 999999,
        background: '#0f172a', color: '#fff', padding: '10px 16px', borderRadius: 10
      }).appendTo('body');
    }
    $t.text(msg).stop(true, true).fadeIn(100);
    setTimeout(function () { $t.fadeOut(200); }, 2200);
  }
  function g(sel, d) {
    var $e = $(sel);
    return $e.length ? $e.val() : d;
  }
  function c(sel) {
    var $e = $(sel);
    return ($e.length && $e.is(':checked')) ? 1 : 0;
  }
  function S(sel, v) {
    var $e = $(sel);
    if ($e.length) $e.val(v);
  }

  /**
   * Menu field schema — single source for readForm/fillForm (Phase-1 audit fix W2)
   * type: text|int|check|sel
   */
  var MENU_FIELD_SCHEMA = [
    { key: 'id', sel: '#m-id', type: 'text', def: 'main' },
    { key: 'title', sel: '#m-title', type: 'text', def: '' },
    { key: 'slug', sel: '#m-slug', type: 'text', def: '' },
    { key: 'layout', sel: '#m-layout', type: 'sel', def: 'horizontal' },
    { key: 'placement', sel: '#m-placement', type: 'sel', def: 'header' },
    { key: 'effect', sel: '#m-effect', type: 'sel', def: 'slide' },
    { key: 'effect_speed', sel: '#m-effect-speed', type: 'int', def: 220, min: 50, max: 1200 },
    { key: 'sound', sel: '#m-sound', type: 'sel', def: 'none' },
    { key: 'sound_vol', sel: '#m-sound-vol', type: 'int', def: 35, min: 0, max: 100 },
    { key: 'sub_open_dir', sel: '#m-sub-dir', type: 'sel', def: 'bottom' },
    { key: 'logo_url', sel: '#m-logo-url', type: 'text', def: '' },
    { key: 'hamburger', sel: '#m-hamburger', type: 'check', def: 0 },
    { key: 'search_in_bar', sel: '#m-search-in-bar', type: 'check', def: 0 },
    { key: 'search_placeholder', sel: '#m-search-placeholder', type: 'text', def: 'جستجو…' },
    { key: 'intent_ms', sel: '#m-intent-ms', type: 'int', def: 200, min: 0, max: 800 },
    { key: 'breakpoint', sel: '#m-breakpoint', type: 'int', def: 768, min: 480, max: 1200 },
    { key: 'second_tap', sel: '#m-second-tap', type: 'sel', def: 'open' },
    { key: 'sticky', sel: '#m-sticky', type: 'check', def: 0 },
    { key: 'sticky_hide', sel: '#m-sticky-hide', type: 'check', def: 0 },
    { key: 'cta_text', sel: '#m-cta-text', type: 'text', def: '' },
    { key: 'cta_url', sel: '#m-cta-url', type: 'text', def: '' },
    { key: 'cta_style', sel: '#m-cta-style', type: 'sel', def: 'glass-capsule' },
    { key: 'cta_color', sel: '#m-cta-color', type: 'text', def: '#e11d48' },
    { key: 'cta_color2', sel: '#m-cta-color2', type: 'text', def: '#0097a7' },
    { key: 'cta_color_mode', sel: '#m-cta-color-mode', type: 'sel', def: 'gradient' },
    { key: 'cta_opacity', sel: '#m-cta-opacity', type: 'int', def: 100, min: 0, max: 100 },
    { key: 'cta_scale', sel: '#m-cta-scale', type: 'int', def: 100, min: 40, max: 200 },
    { key: 'cta_light', sel: '#m-cta-light', type: 'sel', def: 'tl' },
    { key: 'cta_font', sel: '#m-cta-font', type: 'text', def: 'inherit' },
    { key: 'cta_font_size', sel: '#m-cta-font-size', type: 'int', def: 14, min: 10, max: 32 },
    { key: 'cta_img', sel: '#m-cta-img', type: 'text', def: '' },
    { key: 'cta_icon', sel: '#m-cta-icon', type: 'text', def: '' },
    { key: 'cta_emoji', sel: '#m-cta-emoji', type: 'text', def: '' },
    { key: 'cta_role', sel: '#m-cta-role', type: 'sel', def: 'cta_link' },
    { key: 'cta_pos', sel: '#m-cta-pos', type: 'sel', def: 'end' },
    { key: 'cta_target', sel: '#m-cta-target', type: 'sel', def: 'bar' },
    { key: 'cta_x', sel: '#m-cta-x', type: 'int', def: 0 },
    { key: 'cta_y', sel: '#m-cta-y', type: 'int', def: 0 },
    { key: 'cta_col', sel: '#m-cta-col', type: 'int', def: 1, min: 1, max: 8 },
    { key: 'cta_radius', sel: '#m-cta-radius', type: 'int', def: 22 },
    { key: 'cta_size', sel: '#m-cta-size', type: 'sel', def: 'md' },
    { key: 'bg_type', sel: '#m-bg-type', type: 'sel', def: 'solid' },
    { key: 'bg_color', sel: '#m-bg', type: 'text', def: '#0f172a' },
    { key: 'bg_color2', sel: '#m-bg2', type: 'text', def: '#1e3a5f' },
    { key: 'gradient_dir', sel: '#m-gradient-dir', type: 'sel', def: 'ltr' },
    { key: 'bg_image', sel: '#m-bg-img', type: 'text', def: '' },
    { key: 'bg_image_opacity', sel: '#m-bg-img-opacity', type: 'int', def: 100, min: 0, max: 100 },
    { key: 'text_color', sel: '#m-text', type: 'text', def: '#f8fafc' },
    { key: 'hover_color', sel: '#m-hover', type: 'text', def: '#38bdf8' },
    { key: 'active_color', sel: '#m-active', type: 'text', def: '#7dd3fc' },
    { key: 'radius', sel: '#m-radius', type: 'int', def: 12 },
    { key: 'shadow', sel: '#m-shadow', type: 'check', def: 1 },
    { key: 'rtl', sel: '#m-rtl', type: 'check', def: 1 },
    { key: 'trigger', sel: '#m-trigger', type: 'sel', def: 'hover' },
    { key: 'mega_cols', sel: '#m-mega-cols', type: 'int', def: 3, min: 1, max: 8 },
    { key: 'page_anim_in', sel: '#m-page-anim-in', type: 'sel', def: 'none' },
    { key: 'page_anim_out', sel: '#m-page-anim-out', type: 'sel', def: 'none' },
    { key: 'search_box', sel: '#m-search-box', type: 'check', def: 0 },
    { key: 'logo_x', sel: '#m-logo-x', type: 'int', def: 0 },
    { key: 'logo_y', sel: '#m-logo-y', type: 'int', def: 0 },
    { key: 'logo_target', sel: '#m-logo-target', type: 'sel', def: 'bar' },
    { key: 'logo_col', sel: '#m-logo-col', type: 'int', def: 1, min: 1, max: 8 },
    { key: 'search_place', sel: '#m-search-place', type: 'sel', def: 'bar-end' },
    { key: 'search_x', sel: '#m-search-x', type: 'int', def: 0 },
    { key: 'search_y', sel: '#m-search-y', type: 'int', def: 0 },
    { key: 'mobile_sync', sel: '#m-mobile-sync', type: 'check', def: 0 },
    { key: 'mobile_endpoint', sel: '#m-mobile-endpoint', type: 'text', def: '' },
    { key: 'fullwidth_sub', sel: '#m-fullwidth-sub', type: 'check', def: 0 }
  ];

  function schemaReadInto(m) {
    MENU_FIELD_SCHEMA.forEach(function (f) {
      var $el = $(f.sel);
      if (!$el.length) return;
      if (f.type === 'check') {
        if (f.key === 'search_in_bar') {
          m[f.key] = ($('#m-search-in-bar').is(':checked') || $('#m-search-box').is(':checked')) ? 1 : 0;
        } else {
          m[f.key] = $el.is(':checked') ? 1 : 0;
        }
      } else if (f.type === 'int') {
        var n = parseInt($el.val(), 10);
        if (isNaN(n)) n = f.def;
        if (f.min != null && n < f.min) n = f.min;
        if (f.max != null && n > f.max) n = f.max;
        m[f.key] = n;
      } else {
        var v = $el.val();
        m[f.key] = (v == null || v === '') ? f.def : v;
      }
    });
    /* color pickers: prefer #m-bg over missing schema aliases */
    if ($('#m-bg').length) m.bg_color = $('#m-bg').val() || m.bg_color;
    if ($('#m-bg2').length) m.bg_color2 = $('#m-bg2').val() || m.bg_color2;
    if ($('#m-text').length) m.text_color = $('#m-text').val() || m.text_color;
    if ($('#m-hover').length) m.hover_color = $('#m-hover').val() || m.hover_color;
    if ($('#m-active').length) m.active_color = $('#m-active').val() || m.active_color;
    if ($('#m-bg-img').length) m.bg_image = $('#m-bg-img').val() || m.bg_image || '';
    /* keep hex pairs in sync */
    if (m.bg_color) $('#m-bg-hex').val(m.bg_color);
    if (m.bg_color2) $('#m-bg2-hex').val(m.bg_color2);
    if (m.text_color) $('#m-text-hex').val(m.text_color);
    if (m.hover_color) $('#m-hover-hex').val(m.hover_color);
    if (m.active_color) $('#m-active-hex').val(m.active_color);
  }

  function schemaWriteFrom(m) {
    if (!m) return;
    MENU_FIELD_SCHEMA.forEach(function (f) {
      var $el = $(f.sel);
      if (!$el.length) return;
      var val = m[f.key];
      if (val == null) val = f.def;
      if (f.type === 'check') {
        $el.prop('checked', !!val);
      } else {
        $el.val(val);
      }
    });
    /* explicit color + hex pairs (DOM ids) */
    if (m.bg_color != null) { $('#m-bg').val(m.bg_color); $('#m-bg-hex').val(m.bg_color); }
    if (m.bg_color2 != null) { $('#m-bg2').val(m.bg_color2); $('#m-bg2-hex').val(m.bg_color2); }
    if (m.text_color != null) { $('#m-text').val(m.text_color); $('#m-text-hex').val(m.text_color); }
    if (m.hover_color != null) { $('#m-hover').val(m.hover_color); $('#m-hover-hex').val(m.hover_color); }
    if (m.active_color != null) { $('#m-active').val(m.active_color); $('#m-active-hex').val(m.active_color); }
    if (m.bg_image != null) $('#m-bg-img').val(m.bg_image);
    if (m.bg_image_opacity != null) {
      $('#m-bg-img-opacity').val(m.bg_image_opacity);
      $('#m-bg-img-opacity-val').text(m.bg_image_opacity + '%');
    }
  }

  /** Chain self-check for admin diagnostics */
  function runChainDiagnostics() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.diagnostics && CGS_MB_Modules.diagnostics.runChainDiagnostics) {
      return CGS_MB_Modules.diagnostics.runChainDiagnostics();
    }
  }

  function C(sel, on) {
    var $e = $(sel);
    if ($e.length) $e.prop('checked', !!on);
  }

  function defItem() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.defaults) return CGS_MB_Modules.defaults.defItem();
    return { id: '', title: '', url: '#', type: 'link', children: [] };
  }


    /* CGS_READY_TPL → admin/templates-data.js */
  var CGS_READY_TPL = window.CGS_READY_TPL || {};


  function defMenu() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.defaults) return CGS_MB_Modules.defaults.defMenu();
    return { id: 'main', title: 'منوی اصلی', items: [] };
  }

  function ensureTree() {
    if (!state.current) state.current = defMenu();
    if (!state.current.items) state.current.items = [];
    return state.current.items;
  }

  function findById(items, id) {
    for (var i = 0; i < (items || []).length; i++) {
      if (items[i].id === id) return items[i];
      var f = findById(items[i].children || [], id);
      if (f) return f;
    }
    return null;
  }
  function removeById(items, id) {
    for (var i = 0; i < (items || []).length; i++) {
      if (items[i].id === id) { items.splice(i, 1); return true; }
      if (removeById(items[i].children || [], id)) return true;
    }
    return false;
  }
  /* Prefer tree module when loaded */
  if (window.CGS_MB_Modules && CGS_MB_Modules.tree) {
    findById = function (items, id) { return CGS_MB_Modules.tree.findById(items, id); };
    removeById = function (items, id) { return CGS_MB_Modules.tree.removeById(items, id); };
  }

  function flatten(items, depth) {
    depth = depth || 0;
    var out = [];
    (items || []).forEach(function (it) {
      var copy = $.extend({}, it);
      copy.depth = depth;
      var kids = copy.children || [];
      delete copy.children;
      out.push(copy);
      out = out.concat(flatten(kids, depth + 1));
    });
    return out;
  }
  function unflatten(flat) {
    var roots = [], stack = [];
    (flat || []).forEach(function (raw) {
      var it = $.extend({}, raw);
      it.depth = Math.max(0, parseInt(it.depth, 10) || 0);
      it.children = [];
      while (stack.length && stack[stack.length - 1].depth >= it.depth) stack.pop();
      if (!stack.length) roots.push(it);
      else stack[stack.length - 1].children.push(it);
      stack.push(it);
    });
    return roots;
  }

  function fillSelects() {
    var fx = cgsMenuBuilder.effects || {};
    var sd = cgsMenuBuilder.sounds || {};
    var ly = cgsMenuBuilder.layouts || {};
    var pa = cgsMenuBuilder.pageAnims || {};
    $('#m-effect').empty();
    $.each(fx, function (k, v) { $('#m-effect').append($('<option>').val(k).text(v)); });
    $('#m-sound').empty();
    $.each(sd, function (k, v) { $('#m-sound').append($('<option>').val(k).text(v)); });
    $('#m-layout').empty();
    $.each(ly, function (k, v) { $('#m-layout').append($('<option>').val(k).text(v)); });
    $('#m-page-anim-in, #m-page-anim-out').empty();
    $.each(pa, function (k, v) {
      $('#m-page-anim-in').append($('<option>').val(k).text(v));
      $('#m-page-anim-out').append($('<option>').val(k).text(v));
    });
  }

  function openMedia(cb) {
    if (typeof wp === 'undefined' || !wp.media) {
      toast('مخزن رسانه در دسترس نیست');
      return;
    }
    var frame = wp.media({ title: 'انتخاب فایل', button: { text: 'انتخاب' }, multiple: false });
    frame.on('select', function () {
      var att = frame.state().get('selection').first().toJSON();
      cb(att.url || '', att);
    });
    frame.open();
  }

  function computeBarBg(m) {
    var c1 = m.bg_color || '#0f172a';
    var c2 = m.bg_color2 || '#1e3a5f';
    var dir = m.gradient_dir || 'ltr';
    var type = m.bg_type || 'solid';
    var op = Number(m.bg_image_opacity != null ? m.bg_image_opacity : 100) / 100;
    if (isNaN(op)) op = 1;
    if (type === 'gradient') {
      if (dir === 'radial') return 'radial-gradient(circle at center, ' + c1 + ', ' + c2 + ')';
      var ang = { ltr: '90deg', rtl: '270deg', ttb: '180deg', btt: '0deg' }[dir] || '135deg';
      return 'linear-gradient(' + ang + ', ' + c1 + ', ' + c2 + ')';
    }
    if (type === 'image' && m.bg_image) {
      var veil = (1 - op).toFixed(2);
      return 'linear-gradient(rgba(255,255,255,' + veil + '), rgba(255,255,255,' + veil + ')), url(' + m.bg_image + ') center/cover no-repeat, ' + c1;
    }
    if (type === 'glass') return 'rgba(15,23,42,0.72)';
    return c1;
  }

  function badgeStyle(shapeId) {
    var shapes = cgsMenuBuilder.badgeShapes || [];
    for (var i = 0; i < shapes.length; i++) {
      if (shapes[i].id === shapeId) return shapes[i];
    }
    return { bg: '#4f46e5', color: '#fff', radius: '999px' };
  }
  function renderBadgeEl(text, shapeId, fallbackBg) {
    if (!text) return null;
    var sh = badgeStyle(shapeId);
    return $('<span class="cgs-nav-badge">').text(text).css({
      display: 'inline-block', fontSize: 10, fontWeight: 700, padding: '2px 8px',
      marginRight: 4, lineHeight: 1.4, background: sh.bg || fallbackBg || '#4f46e5',
      color: sh.color || '#fff', borderRadius: sh.radius || '999px', border: sh.border || 'none'
    });
  }

  function readForm() {
    var m = state.current ? $.extend({}, state.current) : defMenu();
    schemaReadInto(m);
    // dual search checkbox legacy
    if ($('#m-search-box').length && $('#m-search-box').is(':checked')) m.search_in_bar = 1;
    m.items = (state.current && state.current.items) ? state.current.items : (m.items || []);
    state.current = $.extend(state.current || {}, m, { items: m.items });
    return state.current;
  }

  function fillForm(m) {
    m = m || (typeof defMenu === 'function' ? defMenu() : { id: 'main', items: [] });
    state.current = $.extend(true, {}, m);
    if (typeof schemaWriteFrom === 'function') schemaWriteFrom(m);
    if (window.CGS_MB_Modules && CGS_MB_Modules.form && typeof CGS_MB_Modules.form.fillForm === 'function') {
      try {
        CGS_MB_Modules.form.fillForm(m, {
          state: state,
          schemaWriteFrom: typeof schemaWriteFrom === 'function' ? schemaWriteFrom : function () {},
          renderItems: typeof renderItems === 'function' ? renderItems : function () {},
          schedulePreview: typeof schedulePreview === 'function' ? schedulePreview : function () {},
          syncCtaPreviews: typeof syncCtaPreviews === 'function' ? syncCtaPreviews : function () {},
          refreshCtaSample: typeof refreshCtaSample === 'function' ? refreshCtaSample : function () {}
        });
      } catch (eFill) {
        console.error('[CGS MB] fillForm module', eFill);
      }
    }
    if (typeof renderItems === 'function') renderItems();
    try { if (typeof syncCtaPreviews === 'function') syncCtaPreviews(); } catch (e1) {}
    try { if (typeof refreshCtaSample === 'function') refreshCtaSample(); } catch (e2) {}
  }

  
  function openMediaPicker(kind, cb) {
    if (typeof wp === 'undefined' || !wp.media) {
      var u = window.prompt('آدرس فایل را وارد کنید:');
      if (u) cb(u);
      return;
    }
    var frame = wp.media({
      title: kind === 'video' ? 'انتخاب ویدئو' : 'انتخاب تصویر',
      button: { text: 'انتخاب' },
      multiple: false,
      library: kind === 'video' ? { type: 'video' } : { type: 'image' }
    });
    frame.on('select', function () {
      var att = frame.state().get('selection').first().toJSON();
      if (att && att.url) cb(att.url);
    });
    frame.open();
  }
  $(document).on('click', '.cgs-browse', function () {
    var target = $(this).data('target');
    var kind = $(this).data('kind') || 'image';
    openMediaPicker(kind, function (url) {
      $(target).val(url).trigger('input').trigger('change');
      schedulePreview();
    });
  });

  
  function renderEmojiBank() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.icons && typeof CGS_MB_Modules.icons.renderEmojiBank === 'function') {
      return CGS_MB_Modules.icons.renderEmojiBank();
    }
  }
  function pickEmoji(e) {
    var $t = $('#cgs-ma-items .it-icon').filter(function () { return $(this).closest('li').find('.cgs-ma-item-body').is(':visible'); }).first();
    if (!$t.length) $t = $('#cgs-ma-items .it-icon').last();
    if ($t.length) { $t.val(e).trigger('input'); toast('ایموجی روی آیکن آیتم: ' + e); }
    else if ($('#m-cta-icon').length) { $('#m-cta-icon').val(e).trigger('input'); toast('ایموجی روی CTA: ' + e); }
    else { try { navigator.clipboard.writeText(e); toast('کپی شد: ' + e); } catch (err) { toast(e); } }
  }

  function bindMegaTabs($root) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.megaUi) return CGS_MB_Modules.megaUi.bindMegaTabs($root);
  }
  $(document).on('change input', '#m-intent-ms,#m-breakpoint,#m-second-tap,#m-sticky,#m-sticky-hide', function () {
    if (!state.current) return;
    state.current.intent_ms = parseInt($('#m-intent-ms').val(), 10) || 200;
    state.current.breakpoint = parseInt($('#m-breakpoint').val(), 10) || 768;
    state.current.second_tap = $('#m-second-tap').val() || 'open';
    state.current.sticky = $('#m-sticky').is(':checked') ? 1 : 0;
    state.current.sticky_hide = $('#m-sticky-hide').is(':checked') ? 1 : 0;
    schedulePreview();
  });

  /** Phase-2 W3 fix: load HTML from PHP renderer (same as shortcode/front) */
  var _serverPreviewTimer = null;
  
  var _serverPreviewBusy = false;
  var _srvPrevTimer = null;
  function debouncedServerPreview() {
    if (!window.__cgsForceServerPreview) return;
    clearTimeout(_srvPrevTimer);
    _srvPrevTimer = setTimeout(function () { loadServerPreview(true); }, 320);
  }

  /**
   * Canonical preview path: always use PHP render_menu_html via AJAX.
   * On failure show explicit error — never silently fall back to client demo markup.
   */
  
  /* ---- Preview runtime (clean rewrite 4.10.76) ---- */
  var _previewTimer = null;
  /* serverPreviewBusy already declared */
  window.__cgsForceServerPreview = true; /* always server — friend audit P0 */

  function schedulePreview() {
    if (pvTimer) clearTimeout(pvTimer);
    pvTimer = setTimeout(function () {
      try {
        window.__cgsForceServerPreview = true;
        if (typeof loadServerPreview === 'function') {
          loadServerPreview(false);
        }
      } catch (err) {
        console.error('[CGS Menu] schedulePreview', err);
      }
    }, 480);
  }

  function loadServerPreview(force) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.preview && typeof CGS_MB_Modules.preview.loadFromServer === 'function') {
      var m = null;
      try { m = readForm(); } catch (e) { m = state.current || (typeof defMenu === 'function' ? defMenu() : { id: 'preview' }); }
      if (!m || typeof m !== 'object') m = { id: 'preview' };
      return CGS_MB_Modules.preview.loadFromServer(m, { force: !!force, $root: $('#cgs-ma-preview') });
    }
    var $root = $('#cgs-ma-preview');
    if ($root.length) $root.html('<div class="cgs-preview-error">ماژول preview بارگذاری نشد</div>');
  }

  function bindServerPreviewNav($root) {
    var root = $root && $root[0] ? $root[0] : document.getElementById('cgs-ma-preview');
    if (!root) return;
    var navs = root.querySelectorAll('.cgs-nav');
    if (!navs.length) return;
    if (window.CGSMenuFront && typeof window.CGSMenuFront.bindNav === 'function') {
      navs.forEach(function (nav) {
        nav._cgsBound = false;
        nav.querySelectorAll('.cgs-nav-item').forEach(function (li) { li._cgsItemBound = false; });
        try { window.CGSMenuFront.bindNav(nav); } catch (e) { console.warn('bindNav', e); }
      });
      return;
    }
    // minimal fallback if front.js not loaded
    navs.forEach(function (nav) {
      nav.querySelectorAll('.cgs-nav-item.has-children').forEach(function (li) {
        var link = li.querySelector(':scope > .cgs-nav-link, :scope > a');
        if (!link || link._cgsFb) return;
        link._cgsFb = true;
        li.addEventListener('mouseenter', function () {
          li.parentElement && li.parentElement.querySelectorAll(':scope > .cgs-nav-item.is-open').forEach(function (s) {
            if (s !== li) s.classList.remove('is-open');
          });
          li.classList.add('is-open');
          var wrap = li.querySelector(':scope > .cgs-nav-sub-wrap');
          if (wrap) { wrap.style.display = 'block'; wrap.style.zIndex = '99999'; }
        });
        li.addEventListener('mouseleave', function () { li.classList.remove('is-open'); });
      });
    });
  }

function renderPreview() {
    /* v4.10.86: single path — server iframe only (no dual client renderer) */
    window.__cgsForceServerPreview = true;
    if (typeof loadServerPreview === 'function') {
      loadServerPreview(true);
      return;
    }
    var $prev = $('#cgs-ma-preview');
    if ($prev.length) {
      $prev.html('<div class="cgs-preview-error" role="alert">پیش‌نمایش سرور در دسترس نیست</div>');
    }
  }

  /* ========== Items editor ========== */
  function renderItems() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.items && typeof CGS_MB_Modules.items.render === 'function') {
      return CGS_MB_Modules.items.render({
        ensureTree: ensureTree,
        removeById: removeById,
        indentItem: typeof indentItem === 'function' ? indentItem : function () {},
        defItem: typeof defItem === 'function' ? defItem : function () { return { id: 'i' + Date.now(), label: '', url: '#', children: [] }; },
        schedulePreview: typeof schedulePreview === 'function' ? schedulePreview : function () {},
        toast: typeof toast === 'function' ? toast : function () {},
        ajaxUrl: typeof ajaxUrl === 'function' ? ajaxUrl : function () { return (window.cgsMenuBuilder && cgsMenuBuilder.ajaxUrl) || ''; },
        nonce: typeof nonce === 'function' ? nonce : function () { return (window.cgsMenuBuilder && cgsMenuBuilder.nonce) || ''; }
      });
    }
    /* minimal fallback if module missing */
    var $box = $('#cgs-ma-items');
    if ($box.length) $box.html('<div class="cgs-ma-warn">ماژول items-ui بارگذاری نشد</div>');
  }

  function indentItem(id, delta) {
    var flat = flatten(ensureTree());
    var idx = -1;
    for (var i = 0; i < flat.length; i++) {
      if (flat[i].id === id) { idx = i; break; }
    }
    if (idx < 0) return;
    var d = (parseInt(flat[idx].depth, 10) || 0) + delta;
    if (d < 0) d = 0;
    if (d > 5) d = 5;
    if (idx === 0) d = 0;
    if (d > 0 && idx > 0) {
      var prev = parseInt(flat[idx - 1].depth, 10) || 0;
      if (d > prev + 1) d = prev + 1;
    }
    flat[idx].depth = d;
    state.current.items = unflatten(flat);
    renderItems();
    schedulePreview();
  }

  /* ========== Icon bank ========== */
  function renderIconBank(filterType) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.icons && typeof CGS_MB_Modules.icons.renderIconBank === 'function') {
      return CGS_MB_Modules.icons.renderIconBank(filterType || 'all');
    }
  }

  /* ========== Menus list / save ========== */
  function renderList() {
    var $sel = $('#cgs-ma-menu-select').empty();
    var ids = Object.keys(state.menus || {});
    if (!ids.length) {
      $sel.append($('<option>').val('').text('— منویی نیست —'));
      return;
    }
    ids.forEach(function (id) {
      var m = state.menus[id] || {};
      var label = (m.title || id) + ' (' + id + ')';
      $sel.append($('<option>').val(id).text(label));
    });
    if (state.current && state.current.id) {
      $sel.val(state.current.id);
    }
  }

  function selectMenu(id) {
    try { $('#cgs-ma-menu-select').val(id); } catch (e) {}
    if (!state.menus[id]) return;
    state.id = id;
    state.current = JSON.parse(JSON.stringify(state.menus[id]));
    renderList();
    fillForm(state.current);
    renderItems();
    try { if (typeof syncCtaPreviews === 'function') syncCtaPreviews(); } catch (e1) {}
    schedulePreview();
    $('#cgs-ma-shortcode').text('[cgs_menu id="' + id + '"]');
  }

  function loadMenus() {
    state.menus = $.extend(true, {}, cgsMenuBuilder.menus || {});
    if (!Object.keys(state.menus).length) {
      state.menus.main = defMenu();
    }
    selectMenu(Object.keys(state.menus)[0]);
  }

  function save() {
    var m = readForm();
    if (!m.id) { toast('شناسه خالی'); return; }
    if (state.current && state.current._version != null) m._version = state.current._version;
    if (window.CGS_MB_Modules && CGS_MB_Modules.save && typeof CGS_MB_Modules.save.saveMenu === 'function') {
      var $btnM = $('#cgs-ma-save').prop('disabled', true);
      CGS_MB_Modules.save.saveMenu(m, {
        toast: typeof toast === 'function' ? toast : function () {},
        onSuccess: function (saved) {
          state.menus[m.id] = saved;
          state.current = saved;
          state.id = m.id;
          cgsMenuBuilder.menus = state.menus;
          renderList();
          fillForm(saved);
          if (typeof loadServerPreview === 'function' && isLiveTabActive()) loadServerPreview(true);
        },
        onConflictReload: function () { try { if (typeof loadMenus === 'function') loadMenus(); } catch (e) {} },
        onError: function () {}
      }).always(function () { $btnM.prop('disabled', false); });
      return;
    }
    var $btn = $('#cgs-ma-save').prop('disabled', true);
    $.post(ajaxUrl(), { action: 'cgs_menu_save', nonce: nonce(), menu: JSON.stringify(m) })
      .done(function (res) {
        if (res && res.success) {
          var saved = (res.data && res.data.menu) ? res.data.menu : m;
          state.menus[m.id] = saved;
          state.current = saved;
          state.id = m.id;
          cgsMenuBuilder.menus = state.menus;
          renderList();
          fillForm(saved);
          toast('ذخیره شد ✓ (v' + (saved._version || res.data.version || '?') + ')');
          try { if (typeof loadRevisions === 'function') loadRevisions(); } catch (eRev) {}
          if (typeof loadServerPreview === 'function' && isLiveTabActive()) loadServerPreview(true);
          else if (typeof schedulePreview === 'function') schedulePreview();
        } else {
          var msg = (res && res.data && (res.data.message || res.data)) || 'خطا در ذخیره';
          if (res && res.data && res.data.code === 'version_conflict') {
            msg = 'تداخل نسخه — صفحه را تازه کنید (Ctrl+Shift+R)';
          }
          toast(String(msg));
        }
      })
      .fail(function (xhr) {
        if (xhr && xhr.status === 409) {
          toast('تداخل نسخه: منو توسط دیگری ذخیره شده. تازه‌سازی کنید.');
        } else {
          toast('خطای شبکه ذخیره');
        }
      })
      .always(function () { $btn.prop('disabled', false); });
  }

  function deleteMenu(id) {
    $.post(ajaxUrl(), { action: 'cgs_menu_delete', nonce: nonce(), id: id })
      .done(function () {
        delete state.menus[id];
        var keys = Object.keys(state.menus);
        if (!keys.length) {
          state.menus.main = defMenu();
          keys = ['main'];
        }
        selectMenu(keys[0]);
        toast('حذف شد');
      });
  }

  function runSeoLocal(m) {
    var issues = [], score = 100, count = 0, empty = 0, bad = 0, labels = {};
    function walk(list) {
      (list || []).forEach(function (it) {
        count++;
        var lab = String(it.label || '').trim();
        if (!lab) empty++;
        else labels[lab] = (labels[lab] || 0) + 1;
        if (!it.url || it.url === '#') bad++;
        if (it.children) walk(it.children);
      });
    }
    walk((m && m.items) || []);
    if (empty) { score -= Math.min(30, empty * 5); issues.push({ title: 'برچسب خالی', fix: empty + ' آیتم بدون عنوان' }); }
    if (bad) { score -= Math.min(25, bad * 4); issues.push({ title: 'لینک نامعتبر', fix: bad + ' آیتم با # یا بدون آدرس — لینک واقعی صفحه بگذارید' }); }
    Object.keys(labels).forEach(function (k) {
      if (labels[k] > 1) { score -= 5; issues.push({ title: 'عنوان تکراری', fix: '«' + k + '» ' + labels[k] + ' بار' }); }
    });
    if (count > 12 && m && m.layout === 'horizontal') {
      score -= 10; issues.push({ title: 'شلوغی منوی افقی', fix: 'مگامنو یا گروه‌بندی پیشنهاد می‌شود' });
    }
    return { score: Math.max(0, score), issues: issues, count: count };
  }

  
  /**
   * Single source of truth for ALL CTA live previews (sample + swatch + menu bar CTA).
   * Scale is independent of opacity. Always updates #cgs-cta-sample AND #cgs-cta-live-swatch.
   */
  function syncCtaPreviews() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.cta && CGS_MB_Modules.cta.syncCtaPreviews) {
      return CGS_MB_Modules.cta.syncCtaPreviews();
    }
  }

  function refreshCtaSample() { syncCtaPreviews(); }
  function updateCtaLiveSwatch() { syncCtaPreviews(); }


  function playSound(kind, vol) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.sound && typeof CGS_MB_Modules.sound.playSound === 'function') {
      return CGS_MB_Modules.sound.playSound(kind, vol);
    }
  }

  function applyMega(tpl) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.megaUi && CGS_MB_Modules.megaUi.applyMega) {
      return CGS_MB_Modules.megaUi.applyMega(tpl, {
        state: state,
        defMenu: typeof defMenu === 'function' ? defMenu : null,
        uid: typeof uid === 'function' ? uid : null,
        fillForm: typeof fillForm === 'function' ? fillForm : null,
        renderItems: typeof renderItems === 'function' ? renderItems : null,
        syncCtaPreviews: typeof syncCtaPreviews === 'function' ? syncCtaPreviews : null,
        schedulePreview: typeof schedulePreview === 'function' ? schedulePreview : null,
        loadServerPreview: typeof loadServerPreview === 'function' ? loadServerPreview : null,
        toast: typeof toast === 'function' ? toast : null
      });
    }
  }

  function refreshTpl() {
    var $s = $('#cgs-ma-tpl-select').empty().append('<option value="">— قالب ذخیره‌شده —</option>');
    $.post(ajaxUrl(), { action: 'cgs_menu_list_templates', nonce: nonce() })
      .done(function (res) {
        if (res && res.success && res.data) {
          $.each(res.data, function (id, tpl) {
            $s.append($('<option>').val(id).text(tpl.name || id));
          });
        }
      });
    /* Ready mega templates from PHP CGS_Mega_Templates::all() */
    var metaAll = (window.cgsMenuBuilder && cgsMenuBuilder.megaTemplates) ? cgsMenuBuilder.megaTemplates : {};
    var $ready = $('#cgs-ma-ready-tpl');
    if ($ready.length) {
      var curReady = $ready.val() || '';
      $ready.empty().append('<option value="">— انتخاب قالب —</option>');
      $.each(metaAll, function (id, tpl) {
        $ready.append($('<option>').val(id).text((tpl && tpl.name) ? tpl.name : id));
      });
      /* extras not in PHP meta */
      if (!$ready.find('option[value="mega_2x2"]').length) {
        $ready.append('<option value="mega_2x2">فیکسچر مگا ۲×۲ (پذیرش)</option>');
      }
      if (curReady) $ready.val(curReady);
    }
    /* duplicate mega-tpl-select removed in v4.10.122 */
    var presets = (window.cgsMenuBuilder && cgsMenuBuilder.megaPresets) ? cgsMenuBuilder.megaPresets : {};
    if (Array.isArray(presets)) {
      presets.forEach(function (tpl, i) {
        var pid = tpl.id || ('preset_' + i);
        if (!$m.find('option[value="' + pid + '"]').length) {
          $m.append($('<option>').val(pid).text(tpl.name || ('قالب ' + i)));
        }
      });
    } else {
      $.each(presets, function (id, tpl) {
        if (!$m.find('option[value="' + id + '"]').length) {
          $m.append($('<option>').val(id).text((tpl && tpl.name) ? tpl.name : id));
        }
      });
    }
  }

  /* ========== Boot ========== */
  $(function () {
    if (!$('#cgs-menu-app').length) return;

    /* Boot steps isolated — one module failure must not kill the whole UI */
    var bootErrors = [];
    function bootStep(name, fn) {
      try { fn(); }
      catch (err) {
        bootErrors.push(name + ': ' + (err && err.message ? err.message : err));
        console.error('[CGS MB boot:' + name + ']', err);
      }
    }
    bootStep('fillSelects', function () { fillSelects(); });
    bootStep('loadMenus', function () { loadMenus(); });
    bootStep('renderEmojiBank', function () { renderEmojiBank(); });
    bootStep('setTab', function () { setTab('settings'); });
    bootStep('refreshTpl', function () { refreshTpl(); });
    bootStep('renderIconBank', function () { renderIconBank('all'); });
    bootStep('schedulePreview', function () { schedulePreview(); });
    /* A) Module Contract smoke */
    var contractResult = null;
    bootStep('contract.smoke', function () {
      if (window.CGS_MB_Modules && CGS_MB_Modules.contract && typeof CGS_MB_Modules.contract.assertOrReport === 'function') {
        contractResult = CGS_MB_Modules.contract.assertOrReport();
        if (contractResult && !contractResult.ok) {
          throw new Error('Contract missing: ' + (contractResult.missing || []).join(', '));
        }
      }
    });
    /* C) Acceptance gate */
    bootStep('acceptance', function () {
      if (window.CGS_MB_Modules && CGS_MB_Modules.diagnostics && typeof CGS_MB_Modules.diagnostics.runAcceptanceGate === 'function') {
        CGS_MB_Modules.diagnostics.runAcceptanceGate({
          state: state,
          toast: typeof toast === 'function' ? toast : function () {}
        });
      }
    });
    if (bootErrors.length) {
      $('#cgs-ma-diag').css({ background: '#fff7ed', borderColor: '#fdba74', color: '#9a3412' })
        .text('⚠️ راه‌اندازی ناقص: ' + bootErrors.join(' | '));
    } else {
      var cnote = (contractResult && contractResult.ok) ? ' · قرارداد API ✓' : '';
      $('#cgs-ma-diag').css({ background: '#ecfdf5', borderColor: '#6ee7b7', color: '#065f46' })
        .html('✅ موتور منوساز آماده · منوها: ' + Object.keys(state.menus).length + ' · پیش‌نمایش: ' + ($('#cgs-ma-preview').length ? 'فعال' : 'نیست') + cnote);
    }

    // Live form → preview (all settings)
    $(document).on('change input', '#cgs-menu-app select, #cgs-menu-app input, #cgs-menu-app textarea', function (e) {
      if ($(e.target).closest('#cgs-ma-items').length) return;
      try {
        readForm();
        schedulePreview();
      } catch (err) {
        console.error(err);
      }
    });
    $('#m-bg-img-opacity').on('input', function () {
      $('#m-bg-img-opacity-val').text(($(this).val() || 100) + '%');
    });

    // Color picker ↔ hex text + live preview
    function bindColorPair(colorSel, hexSel) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.colors && typeof CGS_MB_Modules.colors.bindColorPair === 'function') {
      return CGS_MB_Modules.colors.bindColorPair(colorSel, hexSel);
    }
  }
    try {
      bindColorPair('#m-bg', '#m-bg-hex');
      bindColorPair('#m-bg2', '#m-bg2-hex');
      bindColorPair('#m-text', '#m-text-hex');
      bindColorPair('#m-hover', '#m-hover-hex');
      bindColorPair('#m-active', '#m-active-hex');
    } catch (eColor) { console.error('[CGS MB] bindColorPair', eColor); }
    /* form-dirty → live preview */
    $(document).on('cgs-mb:form-dirty', function () {
      try { readForm(); schedulePreview(); } catch (e) {}
    });

    $('#m-sound-vol').on('input', function () {
      $('#m-sound-vol-val').text($(this).val() || 0);
    });

    $('#cgs-ma-save').on('click', function (e) { e.preventDefault(); save(); });
    $('#cgs-ma-new').on('click', function () {
      var id = 'menu_' + Date.now().toString(36);
      var m = defMenu();
      m.id = id; m.slug = id; m.title = 'منوی جدید';
      state.menus[id] = m;
      selectMenu(id);
    });
    $('#cgs-ma-add-item').on('click', function () {
      ensureTree().push(defItem());
      renderItems();
      schedulePreview();
    });
    $('#m-sound-preview').on('click', function (e) { e.preventDefault(); playSound($('#m-sound').val(), parseInt($('#m-sound-vol').val(), 10) || 35); });
    $('#cgs-ma-fx-demo').on('click', function (e) {
      e.preventDefault();
      try { readForm(); } catch (err) {}
      schedulePreview();
      setTimeout(function () {
        var $w = $('#cgs-ma-preview .cgs-nav-item.has-children').first().children('.cgs-nav-sub-wrap');
        if (!$w.length) { toast('زیرمنویی برای نمایش افکت نیست'); return; }
        var spd = parseInt($('#m-effect-speed').val(), 10) || 220;
        var fx = $('#m-effect').val() || 'slide';
        var $panel = $w.children('.cgs-nav-sub').first();
        if (!$panel.length) $panel = $w;
        $w.css('display', 'block');
        $panel.css('--cgs-fx-ms', spd + 'ms');
        $panel.removeClass(function (i, c) { return (c.match(/(^|\s)cgs-anim-\S+/g) || []).join(' '); });
        if ($panel[0]) void $panel[0].offsetWidth;
        $panel.addClass('cgs-anim-' + (fx === 'none' ? 'fade' : fx));
        toast('افکت «' + fx + '» · ' + spd + 'ms · جهت: ' + ($('#m-sub-dir').val() || 'bottom'));
      }, 100);
    });
    $('#m-bg-browse').on('click', function (e) {
      e.preventDefault();
      openMedia(function (url) { $('#m-bg-img').val(url).trigger('input'); toast('تصویر پس‌زمینه'); });
    });
    $('#m-cta-browse').on('click', function (e) {
      e.preventDefault();
      openMedia(function (url) { $('#m-cta-url').val(url).trigger('input'); toast('لینک CTA'); });
    });
    $('#cgs-ma-icon-tabs').on('click', 'button, .cgs-ma-tab', function (e) {
      e.preventDefault();
      $('#cgs-ma-icon-tabs button, #cgs-ma-icon-tabs .cgs-ma-tab').removeClass('is-active');
      $(this).addClass('is-active');
      renderIconBank($(this).data('type') || 'all');
    });
    $('#cgs-ma-save-tpl').on('click', function () {
      var name = prompt('نام قالب:');
      if (!name) return;
      $.post(ajaxUrl(), { action: 'cgs_menu_save_template', nonce: nonce(), template_name: name, menu: JSON.stringify(readForm()) })
        .done(function (r) { toast(r && r.success ? 'قالب ذخیره شد' : 'خطا'); refreshTpl(); });
    });
    $('#cgs-ma-load-tpl').on('click', function () {
      var tid = $('#cgs-ma-tpl-select').val();
      if (!tid) { toast('قالب را انتخاب کنید'); return; }
      $.post(ajaxUrl(), { action: 'cgs_menu_load_template', nonce: nonce(), template_id: tid })
        .done(function (r) {
          if (r && r.success && r.data) {
            var m = r.data.menu || r.data;
            state.current = m;
            state.menus[m.id || state.id] = m;
            fillForm(m);
            renderItems();
            renderPreview();
            toast('قالب بارگذاری شد');
          }
        });
    });
    $('#cgs-ma-del-tpl').on('click', function () {
      var tid = $('#cgs-ma-tpl-select').val();
      if (!tid || !confirm('حذف قالب؟')) return;
      $.post(ajaxUrl(), { action: 'cgs_menu_delete_template', nonce: nonce(), template_id: tid })
        .done(function () { toast('حذف شد'); refreshTpl(); });
    });
    /* legacy mega-tpl-load removed — ready-tpl-apply is the single apply path */
    $('#cgs-ma-ready-tpl-apply-legacy-disabled').on('click', function () {
      var val = $('#cgs-ma-ready-tpl').val();
      if (!val) { toast('قالب را انتخاب کنید'); return; }
      /* Prefer full ready templates (PHP trees / CGS_READY_TPL) */
      var metaAll = (window.cgsMenuBuilder && cgsMenuBuilder.megaTemplates) ? cgsMenuBuilder.megaTemplates : {};
      var trees = (window.cgsMenuBuilder && cgsMenuBuilder.megaTemplateTrees) ? cgsMenuBuilder.megaTemplateTrees : {};
      if (metaAll[val] || (trees[val] && trees[val].length) || (window.CGS_READY_TPL && CGS_READY_TPL[val])) {
        $('#cgs-ma-ready-tpl').val(val);
        $('#cgs-ma-ready-tpl-apply').trigger('click');
        return;
      }
      var presets = cgsMenuBuilder.megaPresets || {};
      var tpl = null;
      if (Array.isArray(presets)) {
        tpl = presets.find(function (p) { return String(p.id) === String(val); }) || presets[parseInt(val, 10)];
      } else tpl = presets[val];
      if (tpl) applyMega(tpl); else toast('قالب را انتخاب کنید');
    });
    $('#cgs-ma-seo').on('click', function () {
      var payload = {};
      try { payload = readForm(); } catch (e) { payload = state.current || {}; }
      if (state.current && state.current.items) payload.items = state.current.items;
      $('#cgs-ma-seo-box').prop('hidden', false).show(); try { document.getElementById('cgs-ma-seo-box').scrollIntoView({behavior:'smooth',block:'nearest'}); } catch(sx){}
      var local = runSeoLocal(payload);
      function renderSeo(r, note) {
        var html = note ? '<div class="cgs-seo-note">' + note + '</div>' : '';
        html += '<div><strong>امتیاز: ' + (r.score || 0) + ' / 100</strong> · آیتم‌ها: ' + (r.count || '') + '</div>';
        (r.issues || []).forEach(function (iss) {
          html += '<div style="margin:6px 0;padding:8px;background:#fff7ed;border-radius:8px"><b>' + (iss.title || '') + '</b><div>' + (iss.fix || '') + '</div></div>';
        });
        if (!(r.issues || []).length) html += '<div style="color:#065f46">مشکل مهمی یافت نشد.</div>';
        $('#cgs-ma-seo-result').html(html);
      }
      renderSeo(local, 'تحلیل فوری (سمت مرورگر)');
      var url = ajaxUrl();
      if (!url) return;
      $.post(url, { action: 'cgs_menu_seo_analyze', nonce: nonce(), menu: JSON.stringify(payload) })
        .done(function (res) {
          if (res && res.success && res.data) {
            renderSeo(res.data, 'تحلیل سرور ✓');
          }
        })
        .fail(function (xhr) {
          var code = (xhr && xhr.status) ? xhr.status : '?';
          $('#cgs-ma-seo-result').append('<div style="margin-top:8px;color:#b45309">سرور پاسخ نداد (HTTP ' + code + ') — نتیجه محلی بالا معتبر است. اگر ۴۰۰ است nonce را با Ctrl+Shift+R تازه کنید.</div>');
        });
    });
    $('#cgs-ma-copy-sc').on('click', function () {
      var t = $('#cgs-ma-shortcode').text();
      if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { toast('کپی شد'); });
    });
    $('#cgs-ma-back-wp').on('click', function () {
      window.location.href = cgsMenuBuilder.adminUrl || 'admin.php?page=city-ghest';
    });
    $(document).on('input change', '#m-cta-color,#m-root-glass-color,#m-bg,#m-bg2', function () {
      var id = this.id, hex = $(this).val();
      $('#' + id + '-hex, #' + id.replace(/$/,'') + '-hex').filter('[id$=hex]').val(hex);
      if (id === 'm-cta-color') $('#m-cta-color-hex').val(hex);
      if (id === 'm-root-glass-color') $('#m-root-glass-hex').val(hex);
      schedulePreview();
    });
    $('#m-effect, #m-sub-dir, #m-effect-speed').on('change.fxdemo', function () {
      schedulePreview();
      setTimeout(function () { $('#cgs-ma-fx-demo').trigger('click'); }, 120);
    });
    
  // ---- Tabs: editor vs live preview ----
  function setTab(tab) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.tabs && typeof CGS_MB_Modules.tabs.setTab === 'function') {
      CGS_MB_Modules.tabs.setTab(tab, {
        readForm: typeof readForm === 'function' ? readForm : null,
        loadServerPreview: typeof loadServerPreview === 'function' ? loadServerPreview : null,
        renderItems: typeof renderItems === 'function' ? renderItems : null,
        schedulePreview: typeof schedulePreview === 'function' ? schedulePreview : null
      });
      return;
    }
    /* fallback legacy */
    window.__cgsForceServerPreview = true;
    $('#cgs-ma-tabs .cgs-ma-tab').removeClass('is-active');
    $('#cgs-ma-tabs .cgs-ma-tab[data-tab="' + tab + '"]').addClass('is-active');
    var $body = $('#cgs-ma-body');
    $body.removeClass('is-live-mode is-help-mode');
    $('#cgs-ma-panel-live').prop('hidden', true).hide();
    $('#cgs-ma-panel-settings').prop('hidden', true).hide();
    $('#cgs-ma-panel-help').prop('hidden', true).hide();
    $('#cgs-ma-panel-editor').hide();
    $('#cgs-ma-toolsbar').show();
    if (tab === 'live') {
      $body.addClass('is-live-mode');
      $('#cgs-ma-panel-live').prop('hidden', false).css('display', 'flex');
      $('#cgs-ma-toolsbar').hide();
      try {
        if (typeof readForm === 'function') readForm();
        if (typeof loadServerPreview === 'function') loadServerPreview(true);
      } catch (e) {}
    } else if (tab === 'settings') {
      $('#cgs-ma-panel-settings').prop('hidden', false).show();
    } else if (tab === 'help') {
      $body.addClass('is-help-mode');
      $('#cgs-ma-panel-help').prop('hidden', false).show();
    } else {
      $('#cgs-ma-panel-editor').show();
      if (typeof renderItems === 'function') renderItems();
      if (typeof schedulePreview === 'function') schedulePreview();
    }
  }
  $(document).on('click', '#cgs-ma-tabs .cgs-ma-tab', function () {
    var t = $(this).data('tab');
    if (t) setTab(t);
  });
  $(document).on('change', '#cgs-ma-menu-select', function () {
    var id = $(this).val();
    if (id) selectMenu(id);
  });

  function runPreviewMonitor() {
    if (window.CGS_MB_Modules && CGS_MB_Modules.diagnostics && typeof CGS_MB_Modules.diagnostics.runPreviewMonitor === 'function') {
      return CGS_MB_Modules.diagnostics.runPreviewMonitor({
        toast: typeof toast === 'function' ? toast : function () {},
        readForm: typeof readForm === 'function' ? readForm : function () { return {}; },
        state: state
      });
    }
  }

  
  /* Self-test button — must always show visible report */
  $(document).on('click', '#cgs-ma-diag-btn', function (e) {
    e.preventDefault();
    if (window.CGS_MB_Modules && CGS_MB_Modules.diagnostics && typeof CGS_MB_Modules.diagnostics.runAcceptanceGate === 'function') {
      CGS_MB_Modules.diagnostics.runAcceptanceGate({
        state: state,
        toast: typeof toast === 'function' ? toast : function (m) { try { console.log(m); } catch (x) {} }
      });
    } else {
      alert('ماژول diagnostics بارگذاری نشده — Ctrl+Shift+R');
    }
  });

  $('#cgs-ma-run-monitor').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $p = $('#cgs-ma-monitor');
      var isOpen = !$p.prop('hidden') && $p.is(':visible');
      if (isOpen) {
        $p.prop('hidden', true).hide();
      } else {
        runPreviewMonitor();
        $p.prop('hidden', false).css({ display: 'block' }).show();
        toast('پایش پیش‌نمایش اجرا شد');
      }
    });
    $(document).on('click.cgsMonitorClose', function (e) {
      if (!$(e.target).closest('.cgs-ma-monitor-drop').length) {
        $('#cgs-ma-monitor').prop('hidden', true).hide();
      }
    });
  $('#cgs-ma-copy-monitor').on('click', function () {
    var issues = window._cgsMbMonitorReport || [];
    var txt = issues.map(function (it) {
      return '[' + it.level + '] ' + it.code + ' — ' + it.title + '\n' + it.detail + '\nراهکار: ' + it.fix;
    }).join('\n\n');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(txt).then(function () { toast('گزارش پایش کپی شد'); });
    } else {
      prompt('گزارش را کپی کنید:', txt);
    }
  });
  $('#cgs-ma-fx-demo-live').on('click', function () { $('#cgs-ma-fx-demo').trigger('click'); });

  /* gallery replaced by category dropdowns */
    /* cta fields handled below */
    
    function applyBtnCat(sel) {
    if (window.CGS_MB_Modules && CGS_MB_Modules.cta && CGS_MB_Modules.cta.applyBtnCat) {
      return CGS_MB_Modules.cta.applyBtnCat(sel);
    }
  }
    /* updateCtaLiveSwatch merged into syncCtaPreviews */
  
  $(document).on('input change', '#m-cta-text,#m-cta-url,#m-cta-style,#m-cta-color,#m-cta-color2,#m-cta-color-mode,#m-cta-opacity,#m-cta-scale,#m-cta-light,#m-cta-font,#m-cta-font-size,#m-cta-img,#m-cta-icon,#m-cta-emoji,#m-cta-role,#m-cta-pos,#m-cta-target,#m-cta-x,#m-cta-y,#m-cta-col,#m-cta-radius', function () {
    if (this.id === 'm-cta-scale') {
      $('#m-cta-scale-val').text(($(this).val() || 100) + '%');
      var sc = (parseInt($(this).val(), 10) || 100) / 100;
      $('#cgs-ma-preview [data-cta="1"]').css({ transform: 'scale(' + sc + ')', '--cgs-scale': sc });
    }
    if (this.id === 'm-cta-opacity') {
      $('#m-cta-opacity-val').text(($(this).val() || 100) + '%');
      var op = (parseInt($(this).val(), 10) || 100) / 100;
      $('#cgs-ma-preview [data-cta="1"]').css({ opacity: op });
    }
    try { syncCtaPreviews(); } catch (e) {}
    schedulePreview();
  });
  $(document).on('change input', '#m-effect,#m-effect-speed,#m-sub-dir,#m-sound,#m-sound-vol,#m-page-anim-in,#m-page-anim-out', function () {
    $('#cgs-ma-preview .cgs-nav-item.is-open').removeClass('is-open');

    if (this.id === 'm-effect-speed') {
      // show live ms
      var v = $(this).val();
      if ($('#m-effect-speed-val').length) $('#m-effect-speed-val').text(v + ' ms');
    }
    schedulePreview();
  });

  $(document).on('change input', '#m-search-place,#m-search-x,#m-search-y,#m-logo-x,#m-logo-y,#m-logo-target,#m-logo-col,#m-cta-x,#m-cta-y,#m-cta-target,#m-cta-col,#m-cta-light', schedulePreview);


    $('#cgs-ma-help-toggle').on('click', function () {
      var $body = $('.cgs-ma-body');
      $body.toggleClass('is-help-collapsed');
      $(this).text($body.hasClass('is-help-collapsed') ? '›' : '‹');
      try { localStorage.setItem('cgs_mb_help_collapsed', $body.hasClass('is-help-collapsed') ? '1' : '0'); } catch (e) {}
    });
    try {
      if (localStorage.getItem('cgs_mb_help_collapsed') === '1') {
        $('.cgs-ma-body').addClass('is-help-collapsed');
        $('#cgs-ma-help-toggle').text('›');
      }
    } catch (e) {}
    $('.cgs-ma-dev').on('click', function () {
      var dev = $(this).data('dev');
      var labels = { desktop: 'دسکتاپ ۱۴۴۰×۹۰۰', tablet: 'تبلت ۷۶۸×۱۰۲۴', mobile: 'موبایل ۳۹۰×۸۴۴' };
      $('#cgs-ma-frame-label').text(labels[dev] || dev);
      $('#cgs-ma-preview-stage').removeClass('is-burger-open');
      $('.cgs-ma-dev').removeClass('is-active');
      $(this).addClass('is-active');
      $('#cgs-ma-preview-stage').removeClass('is-desktop is-tablet is-mobile').addClass('is-' + $(this).data('dev'));
      schedulePreview();
    });
  });

  // --- moved inside IIFE (critical fix) ---
// Clear / remove CTA button from menu
  $(document).on('click', '#cgs-cta-clear, .cgs-cta-clear', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (!state.current) state.current = defMenu();
    state.current.cta_text = '';
    state.current.cta_url = '';
    state.current.cta_role = 'none';
    state.current.cta_img = '';
    state.current.cta_emoji = '';
    state.current.cta_icon = '';
    $('#m-cta-text,#m-cta-url,#m-cta-img,#m-cta-emoji,#m-cta-icon').val('');
    if ($('#m-cta-role').length) {
      if ($('#m-cta-role option[value="none"]').length) $('#m-cta-role').val('none');
      else $('#m-cta-role').val('cta_link');
    }
    $('#cgs-ma-preview [data-cta="1"]').remove();
    try { syncCtaPreviews(); } catch (e3) {}
    schedulePreview();
    if (typeof toast === 'function') toast('دکمه ارجاع حذف شد');
  });

  $(document).on('click', '.cgs-ma-tab[data-tab="live"]', function () {
    setTimeout(function () { loadServerPreview(true); }, 50);
  });
  $(document).on('click', '#cgs-ma-refresh-server-preview', function (e) {
    e.preventDefault();
    loadServerPreview(true);
  });

  // Phase-3: after any successful menu save, refresh server preview if live
  $(document).ajaxSuccess(function (e, xhr, settings) {
    try {
      if (!settings || !settings.data) return;
      var d = settings.data;
      if (typeof d === 'string' && d.indexOf('cgs_menu_save') === -1) return;
      if (typeof d === 'object' && d.action !== 'cgs_menu_save') return;
      if (isLiveTabActive() && typeof loadServerPreview === 'function') loadServerPreview(true);
    } catch (err) {}
  });


  // CTA live chain: EVERY control updates sample + preview (scale independent of opacity)
  /* old scale input change removed */
  
  $(document).on('input change', '#m-cta-opacity', function () {
    var v = parseInt($(this).val(), 10) || 100;
    $('#m-cta-opacity-val').text(v + '%');
    if (state.current) state.current.cta_opacity = v;
    var op = v / 100;
    var sc = (parseInt($('#m-cta-scale').val(), 10) || 100) / 100;
    $('#cgs-ma-preview [data-cta="1"], #cgs-cta-sample .cgs-glass-btn').css({ opacity: op, transform: 'scale(' + sc + ')' });
    try { syncCtaPreviews(); } catch (e) {}
    schedulePreview();
  });
  $(document).on('input change', '#m-cta-text,#m-cta-url,#m-cta-style,#m-cta-color,#m-cta-color2,#m-cta-color-hex,#m-cta-color2-hex,#m-cta-color-mode,#m-cta-light,#m-cta-font,#m-cta-font-size,#m-cta-img,#m-cta-icon,#m-cta-emoji,#m-cta-role,#m-cta-pos,#m-cta-target,#m-cta-x,#m-cta-y,#m-cta-col,#m-cta-radius,#m-cta-size', function () {
    // sync hex↔color
    if (this.id === 'm-cta-color') $('#m-cta-color-hex').val($(this).val());
    if (this.id === 'm-cta-color-hex') { var hx = $(this).val(); if (/^#[0-9A-Fa-f]{6}$/.test(hx)) $('#m-cta-color').val(hx); }
    if (this.id === 'm-cta-color2') $('#m-cta-color2-hex').val($(this).val());
    if (this.id === 'm-cta-color2-hex') { var hx2 = $(this).val(); if (/^#[0-9A-Fa-f]{6}$/.test(hx2)) $('#m-cta-color2').val(hx2); }
    try { readForm(); } catch (e) {}
    try { syncCtaPreviews(); } catch (e2) {}
    schedulePreview();
  });


  /* ==== Unified CTA controls → syncCtaPreviews (scale independent of opacity) ==== */
  $(document).off('input.ctaSync change.ctaSync', '#m-cta-scale,#m-cta-opacity,#m-cta-light,#m-cta-style,#m-cta-color,#m-cta-color2,#m-cta-color-mode,#m-cta-text,#m-cta-font,#m-cta-font-size,#m-cta-radius,#m-cta-emoji,#m-cta-icon,#m-cta-img,#m-cta-role');
  $(document).on('input.ctaSync change.ctaSync', '#m-cta-scale,#m-cta-opacity,#m-cta-light,#m-cta-style,#m-cta-color,#m-cta-color2,#m-cta-color-mode,#m-cta-text,#m-cta-font,#m-cta-font-size,#m-cta-radius,#m-cta-emoji,#m-cta-icon,#m-cta-img,#m-cta-role', function () {
    try { syncCtaPreviews(); } catch (e) { console.warn('syncCta', e); }
    schedulePreview();
  });



  $(document).on('click', '.cgs-it-del-sum, .cgs-it-del, .cgs-ma-item-del', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var id = $(this).closest('[data-id]').attr('data-id');
    if (!id) return;
    if (!confirm('این آیتم و زیرمجموعه‌ها حذف شوند؟')) return;
    removeById(ensureTree(), id);
    renderItems();
    schedulePreview();
    if (typeof toast === 'function') toast('آیتم حذف شد');
  });


  $(document).on('input change', '#m-cta-scale', function () {
    var v = parseInt($(this).val(), 10) || 100;
    $('#m-cta-scale-val').text(v + '%');
    if (state.current) state.current.cta_scale = v;
    try { syncCtaPreviews(); } catch (e) {}
    schedulePreview();
  });
  $(document).on('input change', '#m-cta-opacity', function () {
    var v = parseInt($(this).val(), 10) || 100;
    $('#m-cta-opacity-val').text(v + '%');
    if (state.current) state.current.cta_opacity = v;
    try { syncCtaPreviews(); } catch (e) {}
    schedulePreview();
  });

  $(document).on('click', '#cgs-cta-clear, .cgs-cta-clear', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (!state.current) state.current = defMenu();
    state.current.cta_text = '';
    state.current.cta_url = '';
    state.current.cta_img = '';
    state.current.cta_emoji = '';
    state.current.cta_icon = '';
    state.current.cta_role = 'none';
    $('#m-cta-text,#m-cta-url,#m-cta-img,#m-cta-emoji,#m-cta-icon').val('');
    if ($('#m-cta-role').length) {
      if ($('#m-cta-role option[value="none"]').length) $('#m-cta-role').val('none');
      else $('#m-cta-role').val('cta_link');
    }
    try { if (typeof schemaWriteFrom === 'function') schemaWriteFrom(state.current); } catch (e0) {}
    try { if (typeof syncCtaPreviews === 'function') syncCtaPreviews(); } catch (e1) {}
    schedulePreview();
    toast('دکمه ارجاع حذف شد');
  });


  /* Phase B complete: apply ready template trees OR megaPresets OR fixture */
  
  /* Hard guard: never navigate away from menu-builder while clicking inside preview */
  $(document).on('click.cgsHardNavGuard', '#cgs-ma-preview, #cgs-ma-preview-frame, .cgs-preview-iframe-wrap', function (e) {
    var a = e.target.closest ? e.target.closest('a') : null;
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();
    return false;
  });
  $(document).on('click.cgsHardNavGuard2', 'a.cgs-nav-link, a.cgs-hub-card, a.cgs-product-card, a.cgs-mega-card', function (e) {
    if (!$(e.target).closest('#cgs-ma-preview, .cgs-preview-enduser, #cgs-menu-app .cgs-ma-live').length) return;
    var href = $(this).attr('href') || '';
    if (href && href !== '#' && href.indexOf('javascript:') !== 0) {
      e.preventDefault();
      e.stopPropagation();
      $(this).attr('href', '#');
      return false;
    }
  });

  $(document).on('click', '#cgs-ma-load-fixture-2x2, #cgs-ma-ready-tpl-apply', function (e) {
    e.preventDefault();
    var isFixtureBtn = $(this).is('#cgs-ma-load-fixture-2x2');
    var readyVal = isFixtureBtn ? 'mega_2x2' : ($('#cgs-ma-ready-tpl').val() || '');
    if (!readyVal) {
      toast('یک قالب آماده انتخاب کنید');
      return;
    }

    var id = (state.current && state.current.id) ? state.current.id : 'main';
    var trees = (window.cgsMenuBuilder && cgsMenuBuilder.megaTemplateTrees) ? cgsMenuBuilder.megaTemplateTrees : {};
    var presets = (window.cgsMenuBuilder && cgsMenuBuilder.megaPresets) ? cgsMenuBuilder.megaPresets : [];
    var metaAll = (window.cgsMenuBuilder && cgsMenuBuilder.megaTemplates) ? cgsMenuBuilder.megaTemplates : {};
    var items = null;
    var meta = metaAll[readyVal] || {};
    var layout = meta.layout || 'mega';
    var megaCols = meta.mega_cols || 4;

    if (readyVal === 'mega_2x2' || readyVal === 'fixture_2x2') {
      if (!(window.CGS_MB_Modules && CGS_MB_Modules.defaults && typeof CGS_MB_Modules.defaults.fixtureMega2x2 === 'function')) {
        toast('فیکسچر ۲×۲ در دسترس نیست');
        return;
      }
      var fx = CGS_MB_Modules.defaults.fixtureMega2x2();
      items = fx.items || [];
      layout = fx.layout || 'mega';
      megaCols = fx.mega_cols || 2;
    } else if (trees[readyVal] && trees[readyVal].length) {
      items = JSON.parse(JSON.stringify(trees[readyVal]));
    } else if (window.CGS_READY_TPL && CGS_READY_TPL[readyVal] && CGS_READY_TPL[readyVal].items) {
      items = JSON.parse(JSON.stringify(CGS_READY_TPL[readyVal].items));
      layout = CGS_READY_TPL[readyVal].layout || layout;
      megaCols = CGS_READY_TPL[readyVal].mega_cols || megaCols;
    } else if (presets && presets.length) {
      for (var pi = 0; pi < presets.length; pi++) {
        if (!presets[pi] || presets[pi].id !== readyVal) continue;
        var pr = presets[pi];
        layout = 'mega';
        megaCols = pr.cols || 4;
        var root = { id: 'pr_root', label: pr.name || 'منو', url: '#', icon: '📂', children: [] };
        (pr.columns || []).forEach(function (col, ci) {
          root.children.push({
            id: 'pr_c' + ci,
            label: col.title || ('ستون ' + (ci + 1)),
            url: '#',
            icon: col.icon || '',
            content_type: 'heading',
            children: (col.links || []).map(function (lk, li) {
              return { id: 'pr_c' + ci + '_' + li, label: lk.label || 'لینک', url: lk.url || '#', content_type: 'link' };
            })
          });
        });
        items = [root];
        meta = {
          name: pr.name,
          layout: 'mega',
          mega_cols: megaCols,
          bg_type: pr.bg_type,
          bg_color: pr.bg_color,
          bg_color2: pr.bg_color2,
          effect: pr.effect
        };
        break;
      }
    }

    if (!items || !items.length) {
      toast('درخت قالب «' + readyVal + '» خالی یا ناموجود است');
      return;
    }

    if (!state.current) {
      state.current = (typeof defMenu === 'function') ? defMenu() : { id: id, items: [] };
    }
    state.current.id = id;
    state.current.slug = state.current.slug || id;
    state.current.items = items;
    state.current.layout = layout;
    state.current.mega_cols = megaCols;
    if (meta.name) state.current.title = state.current.title || meta.name;
    if (meta.bg_type) state.current.bg_type = meta.bg_type;
    if (meta.bg_color) state.current.bg_color = meta.bg_color;
    if (meta.bg_color2) state.current.bg_color2 = meta.bg_color2;
    if (meta.effect) state.current.effect = meta.effect;
    state.menus[id] = JSON.parse(JSON.stringify(state.current));

    try { fillForm(state.current); } catch (e0) { console.error(e0); }
    try { renderItems(); } catch (e1) { console.error(e1); }
    try { schedulePreview(); } catch (e2) { console.error(e2); }
    toast('قالب «' + (meta.name || readyVal) + '» اعمال شد — پیش‌نمایش را ببینید');
  });


  /* Phase C: revisions list/restore */
  function loadRevisions() {
    var id = (state.current && state.current.id) ? state.current.id : ($('#m-id').val() || '');
    if (!id) { toast('شناسه منو خالی است'); return; }
    $('#cgs-ma-rev-msg').text('…');
    $.post(ajaxUrl(), { action: 'cgs_menu_revisions_list', nonce: nonce(), id: id })
      .done(function (res) {
        var $sel = $('#cgs-ma-rev-list').empty();
        if (!res || !res.success || !res.data || !res.data.revisions || !res.data.revisions.length) {
          $sel.append($('<option>').val('').text('نسخه‌ای نیست'));
          $('#cgs-ma-rev-msg').text('خالی');
          return;
        }
        res.data.revisions.forEach(function (r) {
          var d = r.saved_at ? new Date(r.saved_at * 1000) : null;
          var label = 'v' + (r.version || '?') + (d ? (' — ' + d.toLocaleString()) : '');
          $sel.append($('<option>').val(String(r.index)).text(label));
        });
        $('#cgs-ma-rev-msg').text(res.data.revisions.length + ' نسخه');
      })
      .fail(function () { $('#cgs-ma-rev-msg').text('خطای شبکه'); });
  }
  $(document).on('click', '#cgs-ma-rev-refresh', function (e) {
    e.preventDefault();
    loadRevisions();
  });
  $(document).on('click', '#cgs-ma-rev-restore', function (e) {
    e.preventDefault();
    var id = (state.current && state.current.id) ? state.current.id : ($('#m-id').val() || '');
    var index = $('#cgs-ma-rev-list').val();
    if (!id || index === '' || index == null) { toast('نسخه را انتخاب کنید'); return; }
    if (!confirm('بازگردانی این نسخه؟ نسخه فعلی هم در تاریخچه می‌ماند.')) return;
    $.post(ajaxUrl(), { action: 'cgs_menu_revision_restore', nonce: nonce(), id: id, index: index })
      .done(function (res) {
        if (res && res.success && res.data && res.data.menu) {
          state.menus[id] = res.data.menu;
          state.current = res.data.menu;
          fillForm(res.data.menu);
          renderItems();
          schedulePreview();
          loadRevisions();
          toast('نسخه بازگردانی شد (v' + (res.data.version || '') + ')');
        } else {
          toast((res && res.data && res.data.message) || 'خطا در بازگردانی');
        }
      })
      .fail(function (xhr) {
        toast(xhr && xhr.status === 429 ? 'محدودیت نرخ — کمی صبر کنید' : 'خطای شبکه');
      });
  });

})(jQuery);


  // --- integrity probe (read-only, Phase A/B/C seal) ---
  $(document).on('click', '#cgs-ma-integrity', function (e) {
    e.preventDefault();
    var $msg = $('#cgs-ma-integrity-msg').text('…');
    $.post(ajaxUrl(), { action: 'cgs_menu_integrity', nonce: nonce() })
      .done(function (res) {
        if (!res || !res.success || !res.data) {
          $msg.text('ناموفق');
          return;
        }
        var d = res.data;
        $msg.text((d.ok ? 'سالم' : 'نقص') + ' ' + (d.percent || 0) + '% (v' + (d.version || '?') + ')');
      })
      .fail(function () { $msg.text('خطای شبکه'); });
  });

  // --- v4.10.108: layout change must immediately affect mega preview ---
  $(document).on('change', '#m-layout, #m-mega-cols, select[name="layout"]', function () {
    try {
      if (state && state.current) {
        if (this.id === 'm-layout' || $(this).is('#m-layout')) {
          state.current.layout = $(this).val() || 'horizontal';
        }
        if (this.id === 'm-mega-cols' || $(this).is('#m-mega-cols')) {
          state.current.mega_cols = parseInt($(this).val(), 10) || 3;
        }
      }
      if (typeof schemaReadInto === 'function' && state && state.current) {
        schemaReadInto(state.current);
      }
      if (typeof schedulePreview === 'function') schedulePreview();
      else if (typeof forcePreview === 'function') forcePreview();
    } catch (eLayout) {}
  });
