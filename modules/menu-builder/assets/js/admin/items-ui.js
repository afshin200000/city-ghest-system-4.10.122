/**
 * CGS MB Admin — Items UI (clean rewrite v4.10.109)
 * Full submenu panel editor: colors, columns, nested items, media, buttons, effects.
 * API: CGS_MB_Modules.items.render(deps) — unchanged contract.
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function uid() {
    return 'i' + Math.random().toString(36).slice(2, 9);
  }

  function defItem(overrides) {
    var base = {
      id: uid(),
      label: 'آیتم جدید',
      url: '#',
      icon: '',
      image: '',
      video: '',
      target: '_self',
      badge: '',
      content_type: 'link',
      panel_bg: '',
      panel_text: '',
      panel_bg_image: '',
      btn_label: '',
      btn_url: '',
      item_effect: 'none',
      item_sound: 'none',
      sub_open_dir: '',
      col: 1,
      children: []
    };
    if (overrides) {
      Object.keys(overrides).forEach(function (k) { base[k] = overrides[k]; });
    }
    return base;
  }

  function fld(label, $input) {
    return $('<label>').css({
      display: 'flex',
      flexDirection: 'column',
      gap: 2,
      fontSize: 11,
      fontWeight: 700,
      color: '#334155',
      minWidth: 0
    }).append($('<span>').text(label), $input);
  }

  function inp(val, ph) {
    return $('<input type="text">').val(val || '').attr('placeholder', ph || '').css({
      width: '100%',
      maxWidth: '100%',
      minHeight: 28,
      fontSize: 12,
      padding: '3px 6px',
      boxSizing: 'border-box'
    });
  }

  function colorInp(val) {
    return $('<input type="color">').val(val || '#ffffff').css({
      width: 42,
      height: 28,
      padding: 0,
      border: '1px solid #cbd5e1',
      borderRadius: 6
    });
  }

  function sel(options, val) {
    var $s = $('<select>').css({
      width: '100%',
      minHeight: 28,
      fontSize: 12,
      padding: '2px 4px'
    });
    (options || []).forEach(function (o) {
      $s.append($('<option>').val(o[0]).text(o[1]));
    });
    if (val != null) $s.val(val);
    return $s;
  }

  function btn(text, title, cls, onClick) {
    return $('<button type="button">').addClass(cls || '').attr('title', title || text).text(text).css({
      fontSize: 11,
      padding: '3px 8px',
      borderRadius: 6,
      border: '1px solid #cbd5e1',
      background: '#f8fafc',
      cursor: 'pointer',
      lineHeight: 1.3
    }).on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      onClick(e);
    });
  }

  function section(title, color) {
    return $('<div class="cgs-it-sec">').css({
      border: '1px solid #e2e8f0',
      borderRadius: 8,
      marginTop: 6,
      overflow: 'hidden'
    }).append(
      $('<div>').text(title).css({
        background: color || '#0f172a',
        color: '#fff',
        fontSize: 11,
        fontWeight: 800,
        padding: '5px 8px'
      })
    );
  }

  function openMedia(onPicked) {
    if (!(w.wp && wp.media)) {
      var url = window.prompt('آدرس تصویر / فایل را وارد کنید:');
      if (url) onPicked(url);
      return;
    }
    var frame = wp.media({
      title: 'انتخاب فایل',
      button: { text: 'استفاده' },
      multiple: false
    });
    frame.on('select', function () {
      var att = frame.state().get('selection').first().toJSON();
      if (att && att.url) onPicked(att.url);
    });
    frame.open();
  }

  function render(deps) {
    deps = deps || {};
    if (typeof deps.ensureTree !== 'function') return;
    if (typeof deps.removeById !== 'function') return;
    if (typeof deps.schedulePreview !== 'function') deps.schedulePreview = function () {};
    if (typeof deps.toast !== 'function') deps.toast = function () {};
    if (typeof deps.defItem !== 'function') deps.defItem = defItem;
    if (typeof deps.indentItem !== 'function') deps.indentItem = function () {};

    var $box = $('#cgs-ma-items');
    if (!$box.length) return;

    if ($box.hasClass('ui-sortable')) {
      try { $box.sortable('destroy'); } catch (e0) {}
    }
    $box.empty();

    function redraw() {
      render(deps);
      deps.schedulePreview();
    }

    function moveItem(id, dir) {
      function walk(list) {
        list = list || [];
        for (var i = 0; i < list.length; i++) {
          if (list[i].id === id) {
            var j = i + dir;
            if (j < 0 || j >= list.length) return false;
            var tmp = list[i];
            list[i] = list[j];
            list[j] = tmp;
            return true;
          }
          if (list[i].children && walk(list[i].children)) return true;
        }
        return false;
      }
      if (walk(deps.ensureTree())) {
        redraw();
        deps.toast(dir < 0 ? 'جابه‌جا به بالا' : 'جابه‌جا به پایین');
      }
    }

    function walk(list, depth) {
      depth = depth || 0;
      (list || []).forEach(function (it) {
        if (!it || !it.id) return;
        if (!it.children) it.children = [];

        var hasKids = it.children && it.children.length;
        var $li = $('<li class="cgs-ma-item">').attr({ 'data-id': it.id, 'data-depth': depth }).css({
          marginInlineStart: depth * 14,
          marginBottom: 6,
          background: depth ? '#f8fafc' : '#fff',
          border: '1px solid ' + (depth ? '#cbd5e1' : '#e2e8f0'),
          borderRadius: 8
        });

        var $sum = $('<div class="cgs-ma-item-summary">').css({
          display: 'flex',
          alignItems: 'center',
          gap: 6,
          padding: '6px 8px',
          cursor: 'pointer',
          flexWrap: 'wrap'
        });

        $sum.append($('<span class="cgs-drag-handle">').text('☰').css({ cursor: 'grab', color: '#94a3b8', fontSize: 12 }));
        if (depth) {
          $sum.append($('<span>').text('L' + depth).css({
            fontSize: 10, background: '#e2e8f0', borderRadius: 4, padding: '1px 5px', color: '#475569'
          }));
        }
        $sum.append($('<strong>').text((it.icon ? it.icon + ' ' : '') + (it.label || 'بدون عنوان')).css({ fontSize: 12 }));
        if (hasKids) {
          $sum.append($('<span>').text('زیرمنو ' + it.children.length).css({
            fontSize: 10, background: '#dbeafe', color: '#1d4ed8', borderRadius: 99, padding: '1px 7px'
          }));
        }

        var $acts = $('<div>').css({ display: 'flex', gap: 4, marginInlineStart: 'auto', flexWrap: 'wrap' });
        $acts.append(btn('↑', 'بالا', '', function () { moveItem(it.id, -1); }));
        $acts.append(btn('↓', 'پایین', '', function () { moveItem(it.id, 1); }));
        $acts.append(btn('↳', 'زیرمنو کردن', '', function () {
          if (typeof deps.indentItem === 'function') deps.indentItem(it.id);
          redraw();
        }));
        $acts.append(btn('＋', 'زیرآیتم', '', function () {
          it.children.push(deps.defItem({ label: 'زیرآیتم', content_type: 'link' }));
          redraw();
          deps.toast('زیرآیتم اضافه شد');
        }));
        $acts.append(btn('ستون', 'افزودن ستون', '', function () {
          it.children.push(deps.defItem({
            label: 'ستون جدید',
            content_type: 'column',
            children: [
              deps.defItem({ label: 'لینک ۱', url: '#', content_type: 'link' }),
              deps.defItem({ label: 'لینک ۲', url: '#', content_type: 'link' })
            ]
          }));
          redraw();
          deps.toast('ستون اضافه شد');
        }));
        $acts.append(btn('حذف', 'حذف', '', function () {
          if (!window.confirm('این آیتم و زیرمجموعه‌ها حذف شوند؟')) return;
          deps.removeById(deps.ensureTree(), it.id);
          redraw();
          deps.toast('حذف شد');
        }));
        $sum.append($acts);

        var $body = $('<div class="cgs-ma-item-body">').css({
          display: 'none',
          padding: '8px 10px 10px',
          borderTop: '1px solid #e2e8f0',
          background: '#fff'
        });

        /* --- Basic --- */
        var $secBasic = section('۱) پایه — عنوان، لینک، نوع', '#0f172a');
        var $grid = $('<div>').css({
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill,minmax(140px,1fr))',
          gap: 8,
          padding: 8
        });
        var $label = inp(it.label, 'عنوان');
        var $url = inp(it.url, 'https://');
        var $icon = inp(it.icon, '🏠');
        var $badge = inp(it.badge, 'جدید');
        var $target = sel([['_self', 'همین صفحه'], ['_blank', 'تب جدید']], it.target || '_self');
        var $ct = sel([
          ['link', 'لینک'],
          ['column', 'ستون'],
          ['row', 'ردیف'],
          ['heading', 'سرستون (عنوان ثابت — نه زیرمنو)'],
          ['image', 'تصویر'],
          ['card', 'کارت'],
          ['button', 'دکمه (ثابت در پنل)'],
          ['divider', 'جداکننده'],
          ['widget', 'ویجت'],
          ['brand', 'برند']
        ], it.content_type || 'link');
        $grid.append(fld('عنوان', $label));
        $grid.append(fld('لینک (URL)', $url));
        $grid.append(fld('آیکن / ایموجی', $icon));
        $grid.append(fld('نشان (Badge)', $badge));
        $grid.append(fld('هدف لینک', $target));
        $grid.append(fld('نوع المان', $ct));
        $secBasic.append($grid);
        $body.append($secBasic);

        /* --- Panel style (submenu chrome) --- */
        var $secPanel = section('۲) ظاهر پنل زیرمنو — رنگ و پس‌زمینه', '#1d4ed8');
        var $pg = $('<div>').css({
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill,minmax(120px,1fr))',
          gap: 8,
          padding: 8
        });
        var $pbg = colorInp(it.panel_bg || '#ffffff');
        var $ptx = colorInp(it.panel_text || '#0f172a');
        var $pimg = inp(it.panel_bg_image, 'آدرس تصویر پس‌زمینه');
        var $pimgBtn = btn('Browse', 'انتخاب از رسانه', '', function () {
          openMedia(function (url) {
            it.panel_bg_image = url;
            $pimg.val(url);
            deps.schedulePreview();
          });
        });
        var $pimgRow = $('<div>').css({ display: 'flex', gap: 6, alignItems: 'center' }).append($pimg.css({ flex: 1 }), $pimgBtn);
        var $dir = sel([
          ['', 'ارث از منو'],
          ['bottom', 'از بالا به پایین'],
          ['top', 'از پایین به بالا'],
          ['left', 'از راست به چپ'],
          ['right', 'از چپ به راست']
        ], it.sub_open_dir || '');
        /* فقط وقتی واقعاً زیرمنو (فرزند) دارد معنا دارد */
        $pg.append(fld('رنگ پس‌زمینه پنل', $pbg));
        $pg.append(fld('رنگ متن پنل', $ptx));
        $pg.append(fld('تصویر پس‌زمینه', $pimgRow));
        $pg.append(fld('جهت باز شدن این زیرمنو', $dir));
        $secPanel.append($pg);
        $body.append($secPanel);

        /* --- Media --- */
        var $secMedia = section('۳) تصویر / ویدئو این آیتم', '#0f766e');
        var $mg = $('<div>').css({
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill,minmax(160px,1fr))',
          gap: 8,
          padding: 8
        });
        var $img = inp(it.image, 'آدرس تصویر');
        var $vid = inp(it.video, 'آدرس ویدئو');
        var $imgBtn = btn('Browse تصویر', '', '', function () {
          openMedia(function (url) { it.image = url; $img.val(url); deps.schedulePreview(); });
        });
        var $vidBtn = btn('Browse ویدئو', '', '', function () {
          openMedia(function (url) { it.video = url; $vid.val(url); deps.schedulePreview(); });
        });
        var $clrImg = btn('حذف تصویر', '', '', function () {
          it.image = ''; $img.val(''); deps.schedulePreview();
        });
        var $clrVid = btn('حذف ویدئو', '', '', function () {
          it.video = ''; $vid.val(''); deps.schedulePreview();
        });
        $mg.append(fld('تصویر', $('<div>').css({ display: 'flex', gap: 6, flexWrap: 'wrap' }).append($img.css({ flex: '1 1 120px' }), $imgBtn, $clrImg)));
        $mg.append(fld('ویدئو', $('<div>').css({ display: 'flex', gap: 6, flexWrap: 'wrap' }).append($vid.css({ flex: '1 1 120px' }), $vidBtn, $clrVid)));
        $secMedia.append($mg);
        $body.append($secMedia);

        /* --- Button --- */
        var $secBtn = section('۴) دکمه ارجاع داخل این آیتم/ستون', '#b45309');
        var $bg = $('<div>').css({
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill,minmax(140px,1fr))',
          gap: 8,
          padding: 8
        });
        var $bl = inp(it.btn_label, 'متن دکمه');
        var $bu = inp(it.btn_url, 'لینک دکمه');
        var $clrBtn = btn('حذف دکمه', '', '', function () {
          it.btn_label = ''; it.btn_url = ''; $bl.val(''); $bu.val(''); deps.schedulePreview();
        });
        $bg.append(fld('متن دکمه', $bl));
        $bg.append(fld('لینک دکمه', $bu));
        $bg.append(fld(' ', $clrBtn));
        $secBtn.append($bg);
        $body.append($secBtn);

        /* --- Effects --- */
        var $secFx = section('۵) افکت و صدا', '#7e22ce');
        var $fg = $('<div>').css({
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill,minmax(140px,1fr))',
          gap: 8,
          padding: 8
        });
        var effects = (w.cgsMenuBuilder && cgsMenuBuilder.effects) ? cgsMenuBuilder.effects : {
          none: 'بدون افکت', fade: 'محو', slide: 'اسلاید', grow: 'بزرگ‌شدن', flip: 'چرخش', bounce: 'پرشی', glow: 'درخشان'
        };
        var sounds = (w.cgsMenuBuilder && cgsMenuBuilder.sounds) ? cgsMenuBuilder.sounds : {
          none: 'بدون صدا', click: 'کلیک', 'button-click': 'دکمه', chime: 'زنگ', soft: 'ملایم'
        };
        var fxOpts = Object.keys(effects).map(function (k) { return [k, effects[k]]; });
        var sndOpts = Object.keys(sounds).map(function (k) { return [k, sounds[k]]; });
        var $fx = sel(fxOpts, it.item_effect || 'none');
        var $snd = sel(sndOpts, it.item_sound || 'none');
        $fg.append(fld('افکت باز شدن', $fx));
        $fg.append(fld('صدای این آیتم', $snd));
        $secFx.append($fg);
        $body.append($secFx);

        /* --- Structure tools --- */
        var $secStruct = section('۶) ساختار زیرمنو — ستون / لینک / تو در تو', '#be123c');
        var $tools = $('<div>').css({ display: 'flex', flexWrap: 'wrap', gap: 6, padding: 8 });
        $tools.append(btn('+ لینک', 'افزودن لینک زیر این آیتم', '', function () {
          it.children.push(deps.defItem({ label: 'لینک جدید', content_type: 'link' }));
          redraw();
        }));
        $tools.append(btn('+ سرستون', 'عنوان ثابت داخل ستون — زیرمنو نیست', '', function () {
          it.children.push(deps.defItem({ label: 'عنوان ستون', content_type: 'heading', url: '#', children: [] }));
          redraw();
        }));
        $tools.append(btn('+ گروه سرستون (دیجیکالا)', 'سرستون + ۳ لینک نمونه — بدون بازشو', '#0f766e', function () {
          it.children.push(deps.defItem({
            label: 'سرستون جدید',
            content_type: 'heading',
            url: '#',
            children: [
              deps.defItem({ label: 'لینک ۱', content_type: 'link', url: '#' }),
              deps.defItem({ label: 'لینک ۲', content_type: 'link', url: '#' }),
              deps.defItem({ label: 'لینک ۳', content_type: 'link', url: '#' })
            ]
          }));
          redraw();
        }));
        $tools.append(btn('+ برند', 'لوگو/برند داخل پنل', '', function () {
          it.children.push(deps.defItem({ label: 'برند', content_type: 'brand', image: '', url: '#' }));
          redraw();
        }));
        $tools.append(btn('+ ستون', 'ستون مگا', '', function () {
          it.children.push(deps.defItem({
            label: 'ستون',
            content_type: 'column',
            children: [deps.defItem({ label: 'آیتم ستون', content_type: 'link' })]
          }));
          redraw();
        }));
        $tools.append(btn('+ ردیف', 'ردیف', '', function () {
          it.children.push(deps.defItem({ label: 'ردیف', content_type: 'row', children: [] }));
          redraw();
        }));
        $tools.append(btn('+ دکمه', 'دکمه به‌عنوان فرزند', '', function () {
          it.children.push(deps.defItem({
            label: 'دکمه',
            content_type: 'button',
            btn_label: 'کلیک کنید',
            btn_url: '#',
            url: '#'
          }));
          redraw();
        }));
        $tools.append(btn('+ تصویر', 'المان تصویر', '', function () {
          it.children.push(deps.defItem({ label: 'تصویر', content_type: 'image', image: '' }));
          redraw();
        }));
        $tools.append(btn('+ زیرمنوی تو در تو', 'سطح عمیق‌تر', '', function () {
          it.children.push(deps.defItem({
            label: 'زیرمنوی تو در تو',
            content_type: 'link',
            children: [deps.defItem({ label: 'فرزند', content_type: 'link' })]
          }));
          redraw();
        }));
        $secStruct.append($tools);
        $secStruct.append($('<p>').text('پس از افزودن، روی همان آیتم در درخت کلیک کنید تا ویرایش کامل شود. ستون‌ها و لینک‌ها قابل حذف/ویرایش‌اند.').css({
          fontSize: 11, color: '#64748b', margin: '0 8px 8px'
        }));
        $body.append($secStruct);

        function bind($el, key, isColor) {
          $el.on('input change', function () {
            var v = $(this).val();
            if (isColor && (v === '#000000' || v === '#ffffff') && !it[key]) {
              /* allow empty meaning inherit - still store chosen */
            }
            it[key] = v;
            if (key === 'label' || key === 'icon') {
              $sum.find('strong').text((it.icon ? it.icon + ' ' : '') + (it.label || 'بدون عنوان'));
            }
            deps.schedulePreview();
          });
        }
        bind($label, 'label');
        bind($url, 'url');
        bind($icon, 'icon');
        bind($badge, 'badge');
        bind($target, 'target');
        bind($ct, 'content_type');
        $ct.on('change', function () {
          var v = $(this).val();
          var panelTypes = { heading: 1, image: 1, divider: 1, button: 1, card: 1, brand: 1 };
          if (panelTypes[v]) {
            /* سرستون و امثال آن زیرمنو نیستند */
            if (it.children && it.children.length) {
              deps.toast('توجه: سرستون/المان پنل زیرمنوی بازشو نیست. فرزندان در صورت وجود فقط به‌صورت محتوا نمایش داده می‌شوند.');
            }
          }
          deps.schedulePreview();
        });
        bind($pbg, 'panel_bg', true);
        bind($ptx, 'panel_text', true);
        bind($pimg, 'panel_bg_image');
        bind($dir, 'sub_open_dir');
        bind($img, 'image');
        bind($vid, 'video');
        bind($bl, 'btn_label');
        bind($bu, 'btn_url');
        bind($fx, 'item_effect');
        bind($snd, 'item_sound');

        $sum.on('click', function (e) {
          if ($(e.target).closest('button').length) return;
          $li.toggleClass('is-open');
          $body.toggle();
        });

        $li.append($sum).append($body);
        $box.append($li);

        if (it.children && it.children.length) {
          walk(it.children, depth + 1);
        }
      });
    }

    walk(deps.ensureTree(), 0);

    try {
      if ($.fn.sortable) {
        $box.sortable({
          handle: '.cgs-drag-handle',
          items: '> li.cgs-ma-item',
          placeholder: 'cgs-ma-sort-ph',
          update: function () {
            var tree = deps.ensureTree();
            var byId = {};
            tree.forEach(function (n) { byId[n.id] = n; });
            var next = [];
            $box.children('li.cgs-ma-item').each(function () {
              var id = $(this).attr('data-id');
              var d = parseInt($(this).attr('data-depth'), 10) || 0;
              if (d === 0 && byId[id]) next.push(byId[id]);
            });
            if (next.length) {
              var rootIds = next.map(function (n) { return n.id; });
              tree.forEach(function (n) {
                if (rootIds.indexOf(n.id) === -1) next.push(n);
              });
              tree.length = 0;
              next.forEach(function (n) { tree.push(n); });
              deps.schedulePreview();
            }
          }
        });
      }
    } catch (eSort) {}
  }

  w.CGS_MB_Modules.items = { render: render, defItem: defItem };
})(window, jQuery);
