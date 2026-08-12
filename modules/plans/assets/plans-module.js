/**
 * اضطراری طرح‌ها: فقط آزادسازی — بدون Sortable
 */
(function ($) {
  'use strict';
  function unlock() {
    try { document.body.classList.remove('cgs-is-sorting'); } catch (e) {}
    $('#cgs-categories-editor').each(function () {
      if (this._cgsSortable) {
        try { this._cgsSortable.destroy(); } catch (e) {}
        this._cgsSortable = null;
      }
    });
    $('#cgs-categories-editor .cgs-opt-sortable').each(function () {
      if (this._cgsSortable) {
        try { this._cgsSortable.destroy(); } catch (e) {}
        this._cgsSortable = null;
      }
    });
    $('#tab-cats input, #tab-cats select, #tab-cats textarea, #tab-cats button, #cgs-categories-editor input, #cgs-categories-editor select, #cgs-categories-editor textarea, #cgs-categories-editor button')
      .prop('disabled', false)
      .css({ pointerEvents: 'auto', opacity: 1, zIndex: 50 });
  }
  $(function () {
    unlock();
    setTimeout(unlock, 200);
    setTimeout(unlock, 800);
  });
  $(document).on('click', '#cgs-add-category, .cgs-add-option, .nav-tab', function () {
    setTimeout(unlock, 50);
  });
})(jQuery);
