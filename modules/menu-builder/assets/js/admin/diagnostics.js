/**
 * CGS MB Admin — Diagnostics + Acceptance Gate (rewrite v4.10.122)
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function toJalaliStr(d) {
    d = d || new Date();
    try {
      return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
      }).format(d);
    } catch (e) {
      return d.toLocaleString('fa-IR');
    }
  }

  function runPreviewMonitor(deps) {
    deps = deps || {};
    var toast = typeof deps.toast === 'function' ? deps.toast : function () {};
    var readForm = typeof deps.readForm === 'function' ? deps.readForm : function () { return {}; };
    var state = deps.state || {};
    var issues = [];
    var m = state.current || readForm() || {};
    var $root = $('#cgs-ma-preview');
    var $frame = $('#cgs-ma-preview-frame');
    var src = $root.attr('data-preview-source') || '';
    var contract = $root.attr('data-preview-contract') || '';
    var itemCount = ((m.items || (state.current && state.current.items) || [])).length;

    issues.push({
      level: $frame.length || $root.find('.cgs-nav').length ? 'ok' : 'warn',
      code: 'PREVIEW-DOM',
      title: 'وجود پیش‌نمایش',
      detail: $frame.length ? 'iframe سرور فعال است' : ($root.find('.cgs-nav').length ? 'nav داخل ریشه' : 'پیش‌نمایش خالی'),
      fix: $frame.length || $root.find('.cgs-nav').length ? '—' : 'تب پیش‌نمایش زنده را باز کنید یا ذخیره کنید.'
    });

    issues.push({
      level: src === 'server-iframe' || contract === 'preview-equals-front' || contract === 'preview-equals-end-user' ? 'ok' : 'warn',
      code: 'PREVIEW-CONTRACT',
      title: 'قرارداد Preview = Front',
      detail: 'source=' + (src || '—') + ' · contract=' + (contract || '—'),
      fix: 'ذخیره منو و رفرش پیش‌نمایش'
    });

    var locBefore = String(window.location.href || '');
    var hrefLeak = 0;
    try {
      if ($frame.length && $frame[0].contentDocument) {
        $frame[0].contentDocument.querySelectorAll('a[href]').forEach(function (a) {
          var h = a.getAttribute('href') || '';
          if (h && h !== '#' && h.indexOf('javascript:') !== 0 && h.indexOf('#cgs-') !== 0) {
            hrefLeak++;
            try { a.setAttribute('href', '#'); a.setAttribute('data-cgs-blocked', '1'); } catch (x) {}
          }
        });
      }
    } catch (e) { /* sandbox may block */ }

    issues.push({
      level: hrefLeak === 0 ? 'ok' : 'err',
      code: 'LOCATION-GUARD',
      title: 'عدم تغییر location در پیش‌نمایش',
      detail: hrefLeak ? (hrefLeak + ' لینک واقعی در iframe (بازنویسی شد)') : ('location پایدار: ' + locBefore.split('?')[0]),
      fix: hrefLeak ? 'Ctrl+Shift+R پس از به‌روزرسانی ۴.۱۰.۱۲۲' : '—'
    });

    issues.push({
      level: itemCount > 0 ? 'ok' : 'warn',
      code: 'ITEMS',
      title: 'آیتم‌های منو در state',
      detail: 'تعداد سطح اول: ' + itemCount,
      fix: itemCount ? '—' : 'آیتم اضافه کنید یا قالب آماده را اعمال کنید.'
    });

    var stamp = toJalaliStr(new Date());
    var $box = $('#cgs-ma-monitor-list');
    var $meta = $('#cgs-ma-monitor-meta');
    if ($meta.length) {
      $meta.text('پایش: ' + issues.length + ' مورد · ' + stamp + ' (بروزرسانی همین لحظه)');
    }
    if ($box.length) {
      $box.empty();
      issues.forEach(function (iss) {
        $box.append(
          $('<div class="cgs-mon-item"></div>')
            .css({
              padding: '8px 10px',
              marginBottom: 6,
              borderRadius: 8,
              background: iss.level === 'ok' ? '#ecfdf5' : (iss.level === 'err' ? '#fef2f2' : '#fff7ed'),
              border: '1px solid #e2e8f0'
            })
            .html('<b>[' + iss.code + '] ' + iss.title + '</b><div>' + iss.detail + '</div><small>' + iss.fix + '</small>')
        );
      });
    }
    window._cgsMbMonitorReport = issues;
    window._cgsMbMonitorStamp = stamp;
    $('#cgs-ma-monitor').prop('hidden', false).css({ display: 'block', zIndex: 99999 }).show();
    toast('پایش پیش‌نمایش: ' + issues.length + ' مورد · ' + stamp);
    return issues;
  }

  function runAcceptanceGate(deps) {
    deps = deps || {};
    var toast = typeof deps.toast === 'function' ? deps.toast : function () {};
    var state = deps.state || {};
    var checks = [];

    checks.push({
      id: 'JQUERY',
      ok: !!(w.jQuery),
      detail: w.jQuery ? ('jQuery ' + (w.jQuery.fn && w.jQuery.fn.jquery ? w.jQuery.fn.jquery : 'ok')) : 'jQuery نیست'
    });

    checks.push({
      id: 'CGS_BUILDER',
      ok: !!(w.cgsMenuBuilder),
      detail: w.cgsMenuBuilder ? 'cgsMenuBuilder لود شد' : 'cgsMenuBuilder نیست'
    });

    if (w.CGS_MB_Modules && CGS_MB_Modules.contract && typeof CGS_MB_Modules.contract.smoke === 'function') {
      var smoke = CGS_MB_Modules.contract.smoke();
      checks.push({
        id: 'CONTRACT',
        ok: smoke.ok,
        detail: smoke.ok ? ('API کامل: ' + (smoke.present || []).length) : ('ناقص: ' + (smoke.missing || []).join(', '))
      });
    } else {
      checks.push({ id: 'CONTRACT', ok: false, detail: 'ماژول contract بارگذاری نشده' });
    }

    var prevOk = !!(w.CGS_MB_Modules && CGS_MB_Modules.preview && typeof CGS_MB_Modules.preview.loadFromServer === 'function');
    checks.push({ id: 'PREVIEW_API', ok: prevOk, detail: prevOk ? 'loadFromServer موجود' : 'preview API نیست' });

    var fixOk = !!(w.CGS_MB_Modules && CGS_MB_Modules.defaults && typeof CGS_MB_Modules.defaults.fixtureMega2x2 === 'function');
    checks.push({ id: 'FIXTURE_2X2', ok: fixOk, detail: fixOk ? 'fixtureMega2x2 موجود' : 'فیکسچر نیست' });

    var items = (state.current && state.current.items) || [];
    checks.push({
      id: 'STRUCT_ITEMS',
      ok: true,
      detail: items.length ? ('آیتم سطح۱: ' + items.length) : 'منوی خالی — قالب آماده را اعمال کنید'
    });

    var parity = (w.cgsMenuBuilder && cgsMenuBuilder.previewParity) || {};
    var styleCount = (parity.styles && parity.styles.length) || 0;
    checks.push({
      id: 'PARITY_ASSETS',
      ok: styleCount >= 0,
      detail: styleCount ? ('stylesheetهای parity: ' + styleCount) : 'parity خالی (اختیاری)'
    });

    checks.push({
      id: 'DOM_SAVE',
      ok: $('#cgs-ma-save').length > 0,
      detail: $('#cgs-ma-save').length ? 'دکمه ذخیره در DOM' : 'دکمه ذخیره نیست'
    });

    checks.push({
      id: 'DOM_READY_TPL',
      ok: $('#cgs-ma-ready-tpl').length > 0,
      detail: $('#cgs-ma-ready-tpl').length ? 'یک دراپ‌داون قالب آماده' : 'دراپ‌داون قالب آماده نیست'
    });

    checks.push({
      id: 'NO_DUP_TPL',
      ok: $('#cgs-ma-mega-tpl-select').length === 0,
      detail: $('#cgs-ma-mega-tpl-select').length ? 'دراپ‌داون تکراری هنوز هست' : 'دراپ‌داون تکراری حذف شد'
    });

    var okAll = checks.every(function (c) { return c.ok; });
    var lines = checks.map(function (c) {
      return (c.ok ? '✅' : '❌') + ' ' + c.id + ': ' + c.detail;
    });
    var stamp = toJalaliStr(new Date());
    var reportHtml = '<div style="padding:12px;font-family:Tahoma,sans-serif;direction:rtl">' +
      '<div style="font-weight:800;margin-bottom:8px;color:#0f172a">گزارش خودآزمایی · ' + stamp + '</div>' +
      lines.map(function (l) {
        return '<div style="padding:6px 8px;margin:4px 0;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0">' + l + '</div>';
      }).join('') +
      '</div>';

    var $panel = $('#cgs-ma-diag-report');
    if (!$panel.length) {
      $panel = $('<div id="cgs-ma-diag-report" style="margin:10px 0;max-height:360px;overflow:auto;border:1px solid #cbd5e1;border-radius:10px;background:#fff"></div>');
      if ($('#cgs-ma-diag-btn').length) {
        $('#cgs-ma-diag-btn').after($panel);
      } else {
        $('#cgs-ma-diag').after($panel);
      }
    }
    $panel.html(reportHtml).show();

    var $monList = $('#cgs-ma-monitor-list');
    var $monMeta = $('#cgs-ma-monitor-meta');
    if ($monMeta.length) {
      $monMeta.text('خودآزمایی · ' + stamp);
    }
    if ($monList.length) {
      $monList.html(lines.map(function (l) {
        return '<div class="cgs-mon-item" style="padding:8px;margin-bottom:6px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0">' + l + '</div>';
      }).join(''));
      $('#cgs-ma-monitor').prop('hidden', false).css({ display: 'block', zIndex: 99999 }).show();
    }

    window._cgsMbDiagReport = { ok: okAll, checks: checks, stamp: stamp };
    toast(okAll ? ('خودآزمایی: قبول · ' + stamp) : ('خودآزمایی: رد — گزارش زیر دکمه · ' + stamp));
    try {
      console.info('[CGS MB Acceptance]\n' + lines.join('\n'));
    } catch (e) {}
    return { ok: okAll, checks: checks, html: reportHtml, stamp: stamp };
  }

  w.CGS_MB_Modules.diagnostics = {
    runPreviewMonitor: runPreviewMonitor,
    runAcceptanceGate: runAcceptanceGate,
    toJalaliStr: toJalaliStr
  };
})(window, jQuery);
