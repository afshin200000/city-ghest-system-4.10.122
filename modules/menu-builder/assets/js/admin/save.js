/**
 * CGS MB Admin — Save module (v4.10.85)
 * Optimistic lock + toast + reload on conflict.
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function ajaxUrl() {
    return (w.cgsMenuBuilder && cgsMenuBuilder.ajaxUrl) || (w.ajaxurl || '');
  }
  function nonce() {
    return (w.cgsMenuBuilder && cgsMenuBuilder.nonce) || '';
  }

  /**
   * @param {object} menu
   * @param {object} opts { onSuccess, onError, toast }
   */
  function saveMenu(menu, opts) {
    opts = opts || {};
    var toast = opts.toast || function (m) { try { console.log('[CGS]', m); } catch (e) {} };
    if (!menu || !menu.id) {
      toast('شناسه خالی');
      return $.Deferred().reject().promise();
    }
    return $.post(ajaxUrl(), {
      action: 'cgs_menu_save',
      nonce: nonce(),
      menu: JSON.stringify(menu)
    }).then(function (res) {
      if (res && res.success) {
        var saved = (res.data && res.data.menu) ? res.data.menu : menu;
        if (typeof opts.onSuccess === 'function') opts.onSuccess(saved, res);
        toast('ذخیره شد ✓ (v' + (saved._version || (res.data && res.data.version) || '?') + ')');
        return saved;
      }
      var msg = (res && res.data && (res.data.message || res.data)) || 'خطا در ذخیره';
      if (res && res.data && res.data.code === 'version_conflict') {
        msg = 'تداخل نسخه — صفحه را تازه کنید (Ctrl+Shift+R)';
      }
      toast(String(msg));
      if (typeof opts.onError === 'function') opts.onError(res);
      return $.Deferred().reject(res).promise();
    }, function (xhr) {
      var msg = 'خطای شبکه ذخیره';
      try {
        var r = xhr.responseJSON;
        if (xhr.status === 409 || (r && r.data && r.data.code === 'version_conflict')) {
          msg = (r && r.data && r.data.message) || 'تداخل نسخه';
          if (window.confirm('منو در سرور جدیدتر است. بارگذاری مجدد؟')) {
            if (typeof opts.onConflictReload === 'function') opts.onConflictReload();
          }
        }
      } catch (e) {}
      toast(msg);
      if (typeof opts.onError === 'function') opts.onError(xhr);
      return $.Deferred().reject(xhr).promise();
    });
  }

  w.CGS_MB_Modules.save = { saveMenu: saveMenu };
})(window, jQuery);
