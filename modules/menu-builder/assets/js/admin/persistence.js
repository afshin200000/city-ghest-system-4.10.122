/**
 * CGS MB Admin — Persistence / optimistic lock helpers (v4.10.83)
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};
  w.CGS_MB_Modules.persistence = {
    attachVersion: function (menu, state) {
      if (!menu) return menu;
      if (state && state.current && state.current._version != null) {
        menu._version = state.current._version;
      }
      return menu;
    },
    handleSaveResponse: function (res, state, onConflict) {
      if (res && res.success) {
        return { ok: true, menu: (res.data && res.data.menu) ? res.data.menu : null };
      }
      var code = res && res.data && res.data.code;
      if (code === 'version_conflict') {
        if (typeof onConflict === 'function') onConflict(res.data);
        return { ok: false, conflict: true, data: res.data };
      }
      return { ok: false, conflict: false, data: res && res.data };
    }
  };
})(window, jQuery);
