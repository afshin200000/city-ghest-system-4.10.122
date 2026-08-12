/**
 * CGS MB Admin — Tabs module (v4.10.86)
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function setTab(tab, deps) {
    deps = deps || {};
    w.__cgsForceServerPreview = true;

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
      $('#cgs-ma-monitor').prop('hidden', true);
      try {
        if (typeof deps.readForm === 'function') deps.readForm();
        $('#cgs-ma-preview').html('<div class="cgs-ma-preview-loading">در حال رندر واقعی فرانت…</div>');
        if (typeof deps.loadServerPreview === 'function') deps.loadServerPreview(true);
      } catch (e) {
        try { console.warn('[CGS tabs]', e); } catch (e2) {}
      }
    } else if (tab === 'settings') {
      $('#cgs-ma-panel-settings').prop('hidden', false).show();
    } else if (tab === 'help') {
      $body.addClass('is-help-mode');
      $('#cgs-ma-panel-help').prop('hidden', false).show();
      $('#cgs-ma-toolsbar').hide();
    } else {
      /* editor (default) */
      $('#cgs-ma-panel-editor').show();
      try {
        if (typeof deps.renderItems === 'function') deps.renderItems();
        if (typeof deps.schedulePreview === 'function') deps.schedulePreview();
      } catch (e3) {}
    }
  }

  w.CGS_MB_Modules.tabs = { setTab: setTab };
})(window, jQuery);
