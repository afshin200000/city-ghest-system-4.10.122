/**
 * CGS MB Admin — Preview = Front Menu ONLY (clean rewrite v4.10.98)
 * NEVER embed WordPress admin, full site chrome, or non-menu HTML.
 * End-User parity = how the MENU looks/behaves on the public site, not cloning WP admin.
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  var _busy = false;

  function ajaxUrl() {
    return (w.cgsMenuBuilder && (cgsMenuBuilder.ajaxUrl || cgsMenuBuilder.ajax)) || (w.ajaxurl || '');
  }
  function nonce() {
    return (w.cgsMenuBuilder && cgsMenuBuilder.nonce) || '';
  }
  function parity() {
    return (w.cgsMenuBuilder && cgsMenuBuilder.previewParity) || {};
  }

  function deviceMode() {
    try {
      return ($('#cgs-ma-device').val() || localStorage.getItem('cgs_mb_device') || 'desktop');
    } catch (e) {
      return 'desktop';
    }
  }

  /**
   * Reject full HTML documents / admin chrome. Only accept menu fragment.
   */
  function sanitizeMenuHtml(html) {
    /* ADMIN CHROME REJECT v4.10.115 */
    if (!html || typeof html !== 'string') return '';
    var low0 = html.toLowerCase();
    if (low0.indexOf('id="wpadminbar"') !== -1 || low0.indexOf("id='wpadminbar'") !== -1 ||
        low0.indexOf('id="adminmenu"') !== -1 || low0.indexOf('wp-admin/css') !== -1 ||
        low0.indexOf('cgs-menu-app') !== -1 || low0.indexOf('cgs-ma-topbar') !== -1) {
      var onlyNav = html.match(/<nav[^>]*class="[^"]*cgs-nav[^"]*"[\s\S]*?<\/nav>/i);
      return onlyNav ? onlyNav[0] : '<div class="cgs-preview-error">پیش‌نمایش فقط منوی فرانت را نشان می‌دهد (پنل ادمین حذف شد)</div>';
    }

    if (html == null) return '';
    if (typeof html !== 'string') {
      try { html = String(html); } catch (e) { return ''; }
    }
    var s = html.replace(/^\uFEFF/, '').trim();
    if (!s) return '';

    // Full document or WP admin markers → reject
    var lower = s.slice(0, 2000).toLowerCase();
    if (
      lower.indexOf('<html') !== -1 ||
      lower.indexOf('<!doctype') !== -1 ||
      lower.indexOf('id="wpadminbar"') !== -1 ||
      lower.indexOf('id="adminmenumain"') !== -1 ||
      lower.indexOf('id="cgs-menu-app"') !== -1 ||
      lower.indexOf('cgs-ma-diag') !== -1 ||
      lower.indexOf('cgs-ma-topbar') !== -1 ||
      lower.indexOf('wp-admin') !== -1 && lower.indexOf('cgs-nav') === -1
    ) {
      return '';
    }

    // Must contain menu root or explicit empty placeholder
    if (s.indexOf('cgs-nav') === -1 && s.indexOf('cgs-preview-error') === -1) {
      // try extract nav if buried
      var m = s.match(/<nav[^>]*class="[^"]*cgs-nav[^"]*"[\s\S]*?<\/nav>/i);
      if (m) return m[0];
      return '';
    }
    return s;
  }

  function neutralizePreviewLinks(html) {
    /* HREF NEUTRALIZE v4.10.119 — never navigate parent/iframe to wp-admin */
    if (!html || typeof html !== 'string') return '';
    return html
      .replace(/\s(target)\s*=\s*("|')[^"']*\2/gi, '')
      .replace(/\s(href)\s*=\s*("|')[^"']*\2/gi, ' href="javascript:void(0)"')
      .replace(/\s(href)\s*=\s*([^\s>]+)/gi, ' href="javascript:void(0)"')
      .replace(/\s(target)\s*=\s*("|')[^"']*\2/gi, ' target="_self"')
      .replace(/<form\b/gi, '<form onsubmit="return false;" ');
  }

  function buildSrcdoc(html) {
    html = neutralizePreviewLinks(html);

    var p = parity();
    var cfg = w.cgsMenuBuilder || {};
    // ONLY front menu assets by default (not full theme/admin)
    var ver = (cfg.version || cfg.ver || Date.now());
    var frontCss = cfg.frontCss || (cfg.pluginUrl ? cfg.pluginUrl + 'assets/css/front.css' : '');
    var frontJs = cfg.frontJs || (cfg.pluginUrl ? cfg.pluginUrl + 'assets/js/front.js' : '');
    if (frontCss && frontCss.indexOf('?') === -1) frontCss += '?v=' + ver;
    if (frontJs && frontJs.indexOf('?') === -1) frontJs += '?v=' + ver;

    var fontStack = p.fontStack || 'Tahoma, Vazirmatn, "Segoe UI", sans-serif';
    var zNav = p.zIndexNav != null ? p.zIndexNav : 100;
    var zSub = p.zIndexSub != null ? p.zIndexSub : 99999;
    var zSticky = p.zIndexSticky != null ? p.zIndexSticky : 9990;
    var containerMax = p.containerMax || '1200px';
    var mode = deviceMode();

    var links = frontCss
      ? '<link rel="stylesheet" href="' + String(frontCss).replace(/"/g, '') + '">'
      : '';

    // Optional deep parity (theme CSS) only if explicitly enabled via data flag
    var deep = false;
    try {
      deep = localStorage.getItem('cgs_mb_deep_parity') === '1';
    } catch (e) {}
    if (deep && p.styles && p.styles.length) {
      p.styles.forEach(function (href) {
        if (!href) return;
        // skip admin stylesheets
        var h = String(href);
        if (/wp-admin|admin-bar|dashicons/i.test(h)) return;
        if (frontCss && h.indexOf('front.css') !== -1) return;
        links += '<link rel="stylesheet" href="' + h.replace(/"/g, '') + '">';
      });
    }

    var parityCss =
      'html,body{margin:0;padding:0;background:#f1f5f9;font-family:' + fontStack + '}' +
      '.cgs-preview-container{width:100%;max-width:' + containerMax + ';margin:0 auto;padding:12px 16px 24px;position:relative;overflow:visible!important}' +
      '.cgs-nav{position:relative;z-index:' + zNav + ';font-family:' + fontStack + ';overflow:visible!important}' +
      '.cgs-preview-container,.cgs-preview-enduser{overflow:visible!important}' +
      '.cgs-nav-list{display:flex;flex-wrap:wrap;gap:0;list-style:none;margin:0;padding:0;overflow:visible}' +
      '.cgs-nav-sub-wrap{position:absolute;z-index:' + zSub + ';display:none}' +
      '.cgs-nav-item.is-open>.cgs-nav-sub-wrap{display:block!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important;z-index:100000!important}' +
      '.cgs-nav-item.is-open>.cgs-nav-sub-wrap>.cgs-nav-sub{display:grid!important;grid-template-columns:repeat(var(--cgs-mega-cols,4),minmax(140px,1fr));gap:12px 18px;background:#fff;padding:18px;border-radius:12px;box-shadow:0 18px 50px rgba(15,23,42,.2);min-width:min(1000px,94vw)}' +
      '.cgs-nav.cgs-preview-demo .cgs-nav-item.has-children.is-open>.cgs-nav-sub-wrap{position:relative!important;top:10px!important;inset-inline-start:0!important;width:100%!important;min-width:100%!important}' +
      '.cgs-nav.cgs-preview-demo .cgs-nav-item.has-children.is-open>.cgs-nav-sub-wrap>.cgs-nav-sub{min-width:100%!important;width:100%!important}' +
      '.cgs-mega-heading{font-weight:800;font-size:13px;border-bottom:1px solid #e2e8f0;padding-bottom:6px;margin-bottom:6px;color:#0f172a}' +
      '.cgs-panel-kids{list-style:none;margin:0;padding:0}.cgs-panel-kids .cgs-nav-link{display:block;padding:3px 0;font-size:12.5px;color:#475569}' +
      '.cgs-panel-kids .cgs-nav-link:hover{color:#e11d48}' +
      '.cgs-nav-sub>li,.cgs-nav-sub>.cgs-panel-el,.cgs-nav-sub>.cgs-nav-item{min-width:140px!important;writing-mode:horizontal-tb!important;white-space:normal!important}' +
      '.cgs-nav-sub .cgs-nav-link,.cgs-nav-sub .cgs-mega-heading{writing-mode:horizontal-tb!important;white-space:normal!important;display:block}' +
      '.cgs-nav--mega-sidebar .cgs-nav-sub{grid-template-columns:repeat(var(--cgs-mega-cols,5),minmax(150px,1fr))!important}' +
      '.cgs-nav.is-sticky{position:sticky;top:0;z-index:' + zSticky + '}' +
      '.cgs-preview-device-mobile .cgs-preview-container{max-width:390px}' +
      '.cgs-preview-device-tablet .cgs-preview-container{max-width:768px}' +
      'a{text-decoration:none}img{max-width:100%;height:auto}' +
      '.cgs-preview-empty{padding:24px;color:#64748b;text-align:center;font-size:13px}';

    var safe = sanitizeMenuHtml(html);
    if (!safe) {
      safe = '<div class="cgs-preview-empty">منویی برای پیش‌نمایش نیست. آیتم اضافه کنید یا فیکسچر ۲×۲ را بارگذاری کنید.</div>';
    }

    var bodyCls = 'cgs-preview-enduser cgs-preview-device-' + mode;

    return '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">' +
      '<meta name="viewport" content="width=device-width,initial-scale=1">' +
      links +
      '<style id="cgs-preview-parity">' + parityCss + '</style>' +
      '</head><body class="' + bodyCls + '">' +
      '<div class="cgs-preview-container" id="cgs-preview-mount" data-parity="menu-only">' +
      safe +
      '</div>' +
      (frontJs ? '<script src="' + String(frontJs).replace(/"/g, '') + '"><\/script>' : '') +
      '<script>(function(){try{' +
      'function boot(){' +
      'if(window.CGSMenuFront&&CGSMenuFront.unlockAudio){try{CGSMenuFront.unlockAudio();}catch(x){}}' +
      'if(window.CGSMenuFront&&CGSMenuFront.bindAll){CGSMenuFront.bindAll(document);}' +
      'else if(window.CGSMenuFront&&CGSMenuFront.bindNav){var n=document.querySelector(".cgs-nav");if(n)CGSMenuFront.bindNav(n);}' +
      /* Preview demo: always show first mega panel open so admin sees Digikala columns */ +
      'var nav=document.querySelector(".cgs-nav");' +
      'if(nav){nav.classList.add("cgs-preview-demo");' +
      'var first=nav.querySelector(".cgs-nav-item.has-children");' +
      'if(first){first.classList.add("is-open");' +
      'if(window.CGSMenuFront&&CGSMenuFront.openItem){try{CGSMenuFront.openItem(first,nav,true);}catch(e){}}' +
      '}}' +
      '}' +
      'boot();' +
      'setTimeout(boot,80);' +
      'setTimeout(boot,300);' +
      'document.addEventListener("pointerdown",function(){if(window.CGSMenuFront&&CGSMenuFront.unlockAudio)CGSMenuFront.unlockAudio();},{once:true,capture:true});' +
      'document.addEventListener("click",function(e){' +
      'var t=e.target;' +
      'if(t&&t.closest){var a=t.closest("a,button[type=submit],area");' +
      'if(a){e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();return false;}}' +
      '},true);' +
      'document.addEventListener("auxclick",function(e){e.preventDefault();},true);' +
      'document.addEventListener("submit",function(e){e.preventDefault();},true);' +
      'window.addEventListener("beforeunload",function(e){e.preventDefault();e.returnValue="";});' +
      'try{document.querySelectorAll("a[href]").forEach(function(a){a.setAttribute("href","javascript:void(0)");a.addEventListener("click",function(ev){ev.preventDefault();},true);});}catch(x){}' +
      '}catch(err){console.warn("[CGS preview]",err);}})();<\/script>' +
      '</body></html>';
  }

  /* Parent-level guard: never let preview links navigate wp-admin */
  function bindPreviewNavGuard($root) {
    if (!$root || !$root.length) return;
    var el = $root[0];
    if (el._cgsNavGuard) return;
    el._cgsNavGuard = true;
    el.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a');
      if (!a) return;
      // only block inside preview chrome
      if (!el.contains(a)) return;
      e.preventDefault();
      e.stopPropagation();
    }, true);
  }

  function injectIframe($root, html, mode) {
    if (!$root || !$root.length) return;
    bindPreviewNavGuard($root);
    mode = mode || deviceMode();
    $root.html(
      '<div class="cgs-preview-iframe-wrap" data-device="' + mode + '" ' +
      'style="position:relative;width:100%;min-height:400px;border:1px solid #e2e8f0;border-radius:10px;overflow:visible;background:#fff">' +
      '<iframe id="cgs-ma-preview-frame" title="پیش‌نمایش منو (فقط فرانت)" ' +
      'sandbox="allow-scripts" ' +
      'style="width:100%;min-height:480px;border:0;display:block;background:#f8fafc;overflow:visible"></iframe></div>'
    );
    var frame = document.getElementById('cgs-ma-preview-frame');
    if (!frame) return;
    frame.setAttribute('srcdoc', buildSrcdoc(html));
    // never set src to admin URL
    try { frame.removeAttribute('src'); } catch (e) {}
    if (mode === 'mobile') {
      frame.style.height = '680px';
      frame.style.maxWidth = '390px';
      frame.style.margin = '0 auto';
    } else if (mode === 'tablet') {
      frame.style.height = '640px';
      frame.style.maxWidth = '768px';
      frame.style.margin = '0 auto';
    } else {
      frame.style.height = '720px';
      frame.style.maxWidth = '100%';
    }
    $root.attr('data-preview-source', 'server-iframe');
    $root.attr('data-preview-contract', 'preview-equals-end-user-menu-only');
  }

  function loadFromServer(menu, opts) {
    opts = opts || {};
    var $root = opts.$root && opts.$root.length ? opts.$root : $('#cgs-ma-preview');
    if (!$root.length) return;
    if (_busy && !opts.force) return;
    _busy = true;
    $root.addClass('is-loading-server');

    var url = ajaxUrl();
    if (!url) {
      _busy = false;
      $root.removeClass('is-loading-server');
      injectIframe($root, '<div class="cgs-preview-error">ajaxUrl موجود نیست</div>', deviceMode());
      return;
    }

    $.ajax({
      url: url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'cgs_menu_preview_html',
        nonce: nonce(),
        menu: JSON.stringify(menu || {}),
        device: deviceMode()
      }
    })
      .done(function (res) {
        var html = '';
        if (res && res.success && res.data) {
          html = res.data.html || '';
        } else if (res && res.data && typeof res.data.html === 'string') {
          html = res.data.html;
        }
        // Never treat full response object/string page as HTML
        html = sanitizeMenuHtml(html);
        if (!html) {
          html = '<div class="cgs-preview-error">رندر منو خالی یا نامعتبر بود (ادمین هرگز در پیش‌نمایش تزریق نمی‌شود)</div>';
        }
        injectIframe($root, html, deviceMode());
      })
      .fail(function (xhr) {
        var msg = 'خطای شبکه پیش‌نمایش (' + (xhr && xhr.status ? xhr.status : '?') + ')';
        injectIframe(
          $root,
          '<div class="cgs-preview-error" role="alert">' + msg +
            '<br><small>فقط منوی فرانت — بدون کل وردپرس</small></div>',
          deviceMode()
        );
        if (typeof opts.onError === 'function') opts.onError(xhr);
      })
      .always(function () {
        _busy = false;
        $root.removeClass('is-loading-server');
      });
  }

  w.CGS_MB_Modules.preview = {
    mode: 'iframe',
    setMode: function (m) {
      w.CGS_MB_Modules.preview.mode = m;
      w.__cgsPreviewMode = m;
    },
    deviceMode: deviceMode,
    buildSrcdoc: buildSrcdoc,
    injectIframe: injectIframe,
    loadFromServer: loadFromServer,
    sanitizeMenuHtml: sanitizeMenuHtml,
    parity: parity
  };
  w.__cgsPreviewMode = 'iframe';
})(window, jQuery);
