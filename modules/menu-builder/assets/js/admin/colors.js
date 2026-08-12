/**
 * CGS MB Admin — Color pair binders (clean rewrite v4.10.95)
 * Self-contained: accepts (colorSelector, hexSelector); no free vars.
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  /**
   * @param {string} colorSel  e.g. '#m-bg'
   * @param {string} hexSel    e.g. '#m-bg-hex'
   */
  function bindColorPair(colorSel, hexSel) {
    if (!colorSel || !hexSel) return;
    $(document).off('input.cgsColor change.cgsColor', colorSel);
    $(document).off('input.cgsColor change.cgsColor', hexSel);

    $(document).on('input.cgsColor change.cgsColor', colorSel, function () {
      var v = $(this).val();
      $(hexSel).val(v);
      $(document).trigger('cgs-mb:form-dirty');
    });
    $(document).on('input.cgsColor change.cgsColor', hexSel, function () {
      var v = (($(this).val() || '') + '').trim();
      if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
        if (v.charAt(0) !== '#') v = '#' + v;
        $(colorSel).val(v);
        $(document).trigger('cgs-mb:form-dirty');
      }
    });
  }

  w.CGS_MB_Modules.colors = { bindColorPair: bindColorPair };
})(window, jQuery);
