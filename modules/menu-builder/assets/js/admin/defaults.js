/**
 * CGS MB Admin — Defaults + Mega 2×2 fixture (clean rewrite v4.10.96)
 */
(function (w) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function uid() {
    return 'i' + Math.random().toString(36).slice(2, 9);
  }

  function defItem(label) {
    return {
      id: uid(),
      label: label || 'آیتم جدید',
      url: '#',
      icon: '•',
      image: '',
      video: '',
      target: '_self',
      badge: '',
      badge_shape: '',
      description: '',
      highlight: 0,
      featured: 0,
      hide_mobile: 0,
      hide_desktop: 0,
      display: 'link',
      role: 'link',
      content_type: 'link',
      btn_style: 'glass',
      btn_color: '#e11d48',
      children: [],
      columns: []
    };
  }

  function defMenu() {
    return {
      id: 'main',
      title: 'منوی اصلی',
      slug: 'main',
      layout: 'horizontal',
      placement: 'header',
      bg_type: 'gradient',
      bg_color: '#0f172a',
      bg_color2: '#1e3a8a',
      text_color: '#f8fafc',
      hover_color: '#38bdf8',
      active_color: '#6366f1',
      radius: 12,
      effect: 'fade',
      effect_speed: 280,
      sub_open_dir: 'bottom',
      sticky: 0,
      hamburger: 1,
      search_box: 0,
      cta_text: '',
      cta_url: '',
      cta_style: 'glass-capsule',
      cta_color: '#e11d48',
      items: []
    };
  }

  /**
   * Acceptance fixture: 2 rows × 2 columns mega structure.
   * Uses content_type row/column so PHP structural renderer paints real chrome.
   */
  function fixtureMega2x2() {
    var m = defMenu();
    m.id = 'mega_fixture_2x2';
    m.title = 'فیکسچر مگا ۲×۲';
    m.slug = 'mega-fixture-2x2';
    m.layout = 'mega';
    m.effect = 'fade';
    m.mega_cols = 2;

    function col(title, links) {
      var c = defItem(title);
      c.content_type = 'column';
      c.column_span = 6;
      c.children = links.map(function (lab) {
        var it = defItem(lab);
        it.content_type = 'link';
        it.url = '#';
        return it;
      });
      return c;
    }

    function row(cols) {
      var r = defItem('ردیف');
      r.content_type = 'row';
      r.children = cols;
      return r;
    }

    var top = defItem('محصولات');
    top.url = '#products';
    top.icon = '🛒';
    top.display = 'mega';
    top.children = [
      row([
        col('ستون ۱', ['لینک ۱-۱', 'لینک ۱-۲']),
        col('ستون ۲', ['لینک ۲-۱', 'لینک ۲-۲'])
      ]),
      row([
        col('ستون ۳', ['لینک ۳-۱', 'لینک ۳-۲']),
        col('ستون ۴', ['لینک ۴-۱', 'لینک ۴-۲'])
      ])
    ];

    m.items = [
      top,
      (function () { var x = defItem('درباره'); x.icon = 'ℹ️'; return x; })(),
      (function () { var x = defItem('تماس'); x.icon = '📞'; return x; })()
    ];
    return m;
  }

  w.CGS_MB_Modules.defaults = {
    defMenu: defMenu,
    defItem: defItem,
    fixtureMega2x2: fixtureMega2x2,
    uid: uid
  };
})(window);
