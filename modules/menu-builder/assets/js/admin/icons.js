/**
 * CGS MB Admin — Icon/Emoji banks (clean rewrite v4.10.94)
 * Self-contained: no dependency on admin.js closure (S/toast/iconTarget).
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  var iconTarget = null;

  function toast(msg) {
    var $t = $('#cgs-ma-toast');
    if (!$t.length) {
      $t = $('<div id="cgs-ma-toast"></div>').css({
        position: 'fixed', bottom: 24, left: 24, zIndex: 999999,
        background: '#0f172a', color: '#fff', padding: '10px 16px', borderRadius: 10
      }).appendTo('body');
    }
    $t.text(msg).stop(true, true).fadeIn(100);
    setTimeout(function () { $t.fadeOut(200); }, 2200);
  }

  function setIconTarget($el) {
    iconTarget = $el && $el.length ? $el : null;
  }

  /**
   * @param {string} [filterType='all'] category filter
   */
  function renderIconBank(filterType) {
    filterType = filterType || 'all';
    var cfg = w.cgsMenuBuilder || {};
    var bank = cfg.iconBank || {};
    var list = bank.icons || [];
    var $g = $('#cgs-ma-icon-grid');
    var $p = $('#cgs-ma-icon-providers');
    if ($g.length) $g.empty();
    if ($p.length) $p.empty();
    if (!$g.length) return;

    list.forEach(function (ic) {
      if (!ic) return;
      var t = ic.type || ic.category || 'static';
      if (filterType !== 'all' && t !== filterType && ic.category !== filterType) return;
      var val = ic.value || ic.emoji || ic.id || '';
      var $b = $('<button type="button" class="cgs-ma-icon-chip"></button>').css({
        margin: 4, padding: '6px 8px', borderRadius: 8, border: '1px solid #e2e8f0',
        background: '#fff', cursor: 'pointer'
      });
      $b.append($('<span></span>').text(ic.emoji || '◆').css({ marginLeft: 4 }));
      $b.append($('<span></span>').text(ic.label || ic.id || '').css({ fontSize: 11 }));
      $b.on('click', function (e) {
        e.preventDefault();
        if (iconTarget && iconTarget.length) {
          iconTarget.val(val).trigger('input');
          iconTarget = null;
          toast('آیکن اعمال شد');
        } else {
          var $open = $('#cgs-ma-items .cgs-ma-item.is-open .it-icon').first();
          if ($open.length) $open.val(val).trigger('input');
          toast('آیکن: ' + (ic.label || val));
        }
        $('#cgs-ma-icon-picked').text('آیکن انتخاب‌شده: ' + (ic.label || val));
      });
      $g.append($b);
    });

    (bank.providers || []).forEach(function (pr) {
      if (!pr || !$p.length) return;
      $p.append(
        $('<a target="_blank" rel="noopener"></a>')
          .attr('href', pr.url || '#')
          .text(pr.name || pr.url || '')
          .css({ marginLeft: 8, fontSize: 12 })
      );
    });
  }

  function pickEmoji(emoji) {
    var $t = $('#cgs-ma-items .it-icon').filter(function () {
      return $(this).closest('li').find('.cgs-ma-item-body').is(':visible');
    }).first();
    if (!$t.length) $t = $('#cgs-ma-items .it-icon').last();
    if ($t.length) {
      $t.val(emoji).trigger('input');
      toast('ایموجی روی آیکن آیتم: ' + emoji);
    } else if ($('#m-cta-icon').length) {
      $('#m-cta-icon').val(emoji).trigger('input');
      toast('ایموجی روی CTA: ' + emoji);
    } else {
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(emoji);
        }
      } catch (err) { /* ignore */ }
      toast(emoji);
    }
  }

  function renderEmojiBank() {
    var simple = ['🔥','✨','🎯','🚀','💼','🏠','📞','⭐','❤️','✅','🎁','🛒','📱','💳','🔒','📊','🌟','⚡','💎','🏆','📌','🔔','💡','🧩','🎨','🌐','👤','📁','📝','🎵','📷','🎬','💬','🧭','🛡️','⚙️','📦','🏷️'];
    var three = ['🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤','🔶','🔷','💥','🌈','🪩','🧿'];
    var $g = $('#cgs-ma-emoji-grid');
    if (!$g.length) return;
    $g.empty();
    $g.append($('<div class="cgs-em-sec"></div>').text('ایموجی ساده — کلیک برای درج در آیکن آیتم'));
    simple.forEach(function (em) {
      $g.append($('<button type="button" title="ساده"></button>').text(em).on('click', function () { pickEmoji(em); }));
    });
    $g.append($('<div class="cgs-em-sec"></div>').text('ایموجی سبک ۳D / شکل‌دار'));
    three.forEach(function (em) {
      $g.append($('<button type="button" class="is-3d" title="۳D"></button>').text(em).on('click', function () { pickEmoji(em); }));
    });
  }

  w.CGS_MB_Modules.icons = {
    renderIconBank: renderIconBank,
    renderEmojiBank: renderEmojiBank,
    pickEmoji: pickEmoji,
    setIconTarget: setIconTarget
  };
})(window, jQuery);
