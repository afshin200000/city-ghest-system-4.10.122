/**
 * CGS MB Admin — Mega UI (v4.10.90)
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function bindMegaTabs($root) {
    if (!$root || !$root.length) return;
    var $tabs = $root.find('.cgs-mega-tabs').first();
    if (!$tabs.length) return;
    var $items = $tabs.children('.cgs-nav-item, li');
    if ($items.length < 2) return;
    $items.addClass('cgs-tab-side');
    $items.removeClass('is-tab-active');
    $items.first().addClass('is-tab-active');
    $items.off('mouseenter.cgsTab click.cgsTab').on('mouseenter.cgsTab click.cgsTab', function (e) {
      e.preventDefault();
      $items.removeClass('is-tab-active');
      $(this).addClass('is-tab-active');
    });
  }

  /**
   * Apply a mega template object onto current menu state.
   * @param {object} tpl template
   * @param {object} deps { state, defMenu, uid, fillForm, renderItems, syncCtaPreviews, schedulePreview, loadServerPreview, toast }
   */
  function applyMega(tpl, deps) {
    deps = deps || {};
    if (!tpl) return;
    var state = deps.state || {};
    var defMenu = deps.defMenu || function () { return { items: [] }; };
    var uid = deps.uid || function () { return 'i' + Math.random().toString(36).slice(2, 9); };
    var fillForm = deps.fillForm || function () {};
    var renderItems = deps.renderItems || function () {};
    var syncCtaPreviews = deps.syncCtaPreviews || function () {};
    var schedulePreview = deps.schedulePreview || function () {};
    var loadServerPreview = deps.loadServerPreview || function () {};
    var toast = deps.toast || function () {};

    state.current = state.current || defMenu();
    if (!state.current.layout || state.current.layout === 'horizontal') state.current.layout = 'mega';
    state.current.mega_cols = tpl.cols || 4;
    if (tpl.bg_color) state.current.bg_color = tpl.bg_color;
    if (tpl.bg_color2) state.current.bg_color2 = tpl.bg_color2;
    if (tpl.bg_type) state.current.bg_type = tpl.bg_type;
    if (tpl.effect) state.current.effect = tpl.effect;
    if (tpl.columns) {
      var children = (tpl.columns || []).map(function (col) {
        return {
          id: uid(),
          label: col.title || 'ستون',
          url: col.url || '#',
          icon: col.icon || '▸',
          badge: col.badge || '',
          children: (col.links || []).map(function (lk) {
            return {
              id: uid(),
              label: (typeof lk === 'string' ? lk : (lk.label || '')),
              url: (typeof lk === 'object' && lk.url) ? lk.url : '#',
              icon: '•',
              children: []
            };
          })
        };
      });
      state.current.items = [{
        id: uid(),
        label: tpl.name || 'مگامنو',
        url: '#',
        icon: '▦',
        badge: 'مگا',
        children: children
      }];
    }
    fillForm(state.current);
    renderItems();
    try { syncCtaPreviews(); } catch (e) {}
    w.__cgsForceServerPreview = true;
    schedulePreview();
    try { loadServerPreview(true); } catch (e2) {}
    toast('قالب مگا اعمال شد');
  }

  w.CGS_MB_Modules.megaUi = { bindMegaTabs: bindMegaTabs, applyMega: applyMega };
})(window, jQuery);
