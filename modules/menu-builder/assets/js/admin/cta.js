/**
 * CGS MB Admin — CTA (v4.10.90)
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function applyBtnCat(sel) {
    var v = $(sel).val();
    if (!v) return;
    var parts = String(v).split('|');
    var st = parts[0], col = parts[1] || '#00bcd4';
    $('#m-cta-style').val(st);
    $('#m-cta-color').val(col);
    $('#m-cta-color-hex').val(col);
    $('#m-btn-cat-3d,#m-btn-cat-capsule,#m-btn-cat-flat,#m-btn-cat-simple').not(sel).val('');
  }

  function syncCtaPreviews() {

    var c1 = ($('#m-cta-color').val() || (state.current && state.current.cta_color) || '#e11d48');
    var c2 = ($('#m-cta-color2').val() || (state.current && state.current.cta_color2) || c1);
    var mode = ($('#m-cta-color-mode').val() || (state.current && state.current.cta_color_mode) || 'gradient');
    var op = Math.max(0, Math.min(100, parseInt($('#m-cta-opacity').val() || (state.current && state.current.cta_opacity) || 100, 10))) / 100;
    var scalePct = Math.max(40, Math.min(200, parseInt($('#m-cta-scale').val() || (state.current && state.current.cta_scale) || 100, 10)));
    var scale = scalePct / 100;
    var light = ($('#m-cta-light').val() || (state.current && state.current.cta_light) || 'tl');
    var lightMap = { tl: '15% 10%', tr: '85% 10%', bl: '15% 90%', br: '85% 90%', top: '50% 5%', bottom: '50% 95%', left: '5% 50%', right: '95% 50%' };
    var lp = lightMap[light] || '15% 10%';
    var bg = (mode === 'solid') ? c1 : ('linear-gradient(135deg,' + c1 + ' 0%,' + c2 + ' 100%)');
    var glass = 'radial-gradient(ellipse 90% 70% at ' + lp + ', rgba(255,255,255,.85) 0%, rgba(255,255,255,0) 55%),' + bg;
    var font = ($('#m-cta-font').val() || (state.current && state.current.cta_font) || 'Tahoma,sans-serif');
    var fsize = Math.max(10, Math.min(32, parseInt($('#m-cta-font-size').val() || (state.current && state.current.cta_font_size) || 14, 10)));
    var radius = Math.max(0, parseInt($('#m-cta-radius').val() || (state.current && state.current.cta_radius) || 22, 10));
    var label = ($('#m-cta-text').val() || (state.current && state.current.cta_text) || 'دکمه');
    var emoji = ($('#m-cta-emoji').val() || $('#m-cta-icon').val() || (state.current && (state.current.cta_emoji || state.current.cta_icon)) || '');
    var img = ($('#m-cta-img').val() || (state.current && state.current.cta_img) || '');
    var style = ($('#m-cta-style').val() || (state.current && state.current.cta_style) || 'glass-capsule');
    var role = ($('#m-cta-role').val() || (state.current && state.current.cta_role) || 'cta_link');

    if ($('#m-cta-scale-val').length) $('#m-cta-scale-val').text(scalePct + '%');
    if ($('#m-cta-opacity-val').length) $('#m-cta-opacity-val').text(Math.round(op * 100) + '%');

    if (state.current) {
      state.current.cta_scale = scalePct;
      state.current.cta_opacity = Math.round(op * 100);
      state.current.cta_color = c1;
      state.current.cta_color2 = c2;
      state.current.cta_color_mode = mode;
      state.current.cta_light = light;
      state.current.cta_style = style;
      state.current.cta_text = label;
      state.current.cta_radius = radius;
      state.current.cta_font = font;
      state.current.cta_font_size = fsize;
    }

    var icoHtml = '';
    if (img) {
      icoHtml = '<img class="cgs-cta-img" src="' + String(img).replace(/"/g, '') + '" alt="" style="width:' + (fsize * 1.3) + 'px;height:' + (fsize * 1.3) + 'px;object-fit:cover;border-radius:6px;margin-inline-end:6px;vertical-align:middle">';
    } else if (emoji) {
      icoHtml = '<span class="cgs-glass-ico" style="margin-inline-end:6px">' + $('<div>').text(emoji).html() + '</span>';
    }
    var showLabel = (role !== 'icon_only');
    var htmlInner = icoHtml + (showLabel ? ('<span class="cgs-cta-label">' + $('<div>').text(label).html() + '</span>') : '');

    var cssObj = {
      background: glass,
      opacity: op,
      transform: 'scale(' + scale + ')',
      fontFamily: font,
      fontSize: fsize + 'px',
      borderRadius: radius + 'px',
      display: 'inline-flex',
      alignItems: 'center',
      padding: '10px 18px',
      color: '#fff',
      textDecoration: 'none',
      boxShadow: '0 8px 24px rgba(0,0,0,.18), inset 0 1px 0 rgba(255,255,255,.35)',
      border: '1px solid rgba(255,255,255,.35)',
      fontWeight: '700',
      gap: '6px',
      cursor: 'default',
      transition: 'transform .12s ease'
    };
    if (window.CGS_MB_Modules && CGS_MB_Modules.cta && typeof CGS_MB_Modules.cta.computeStyle === 'function') {
      try {
        var modStyle = CGS_MB_Modules.cta.computeStyle({
          c1: c1, c2: c2, mode: mode, opacity: op, scale: scale,
          light: light, font: font, fontSize: fsize, radius: radius
        });
        for (var mk in modStyle) { if (Object.prototype.hasOwnProperty.call(modStyle, mk)) cssObj[mk] = modStyle[mk]; }
      } catch (eMod) {}
    }

    // Primary sample
    var $sample = $('#cgs-cta-sample');
    if ($sample.length) {
      $sample.attr('class', 'cgs-cta-sample cgs-nav-cta cgs-glass-btn cgs-cta--' + style);
      $sample.css(cssObj).html(htmlInner);
    }
    // Live swatch (second box) — SAME data, no divergence
    var $sw = $('#cgs-cta-live-swatch');
    if ($sw.length) {
      $sw.empty();
      var $btn = $('<span class="cgs-glass-btn cgs-cta-swatch-btn">').attr('data-light', light).css(cssObj).html(htmlInner);
      $sw.append($btn);
    }
    // In-menu CTA in server/client preview
    $('#cgs-ma-preview [data-cta="1"]').css({
      background: glass,
      opacity: op,
      transform: 'scale(' + scale + ')',
      fontFamily: font,
      fontSize: fsize + 'px',
      borderRadius: radius + 'px'
    });
  
  }

  w.CGS_MB_Modules.cta = {
    applyBtnCat: applyBtnCat,
    syncCtaPreviews: syncCtaPreviews
  };
})(window, jQuery);
