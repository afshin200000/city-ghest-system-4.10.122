/**
 * CGS Menu Builder — Front Runtime v4.10.82
 * Delegation · Keyboard · Focus return · prefers-reduced-motion · class breakpoint
 */
(function () {
  'use strict';

  var FX_ALIAS = {
    none: 'fade', slide: 'slide', 'slide-down': 'slide', 'slide-up': 'slide-up',
    'slide-left': 'slide-h', 'slide-right': 'slide-h', fade: 'fade', grow: 'grow',
    scale: 'grow', flip: 'flip', 'flip-x': 'flip-x', bounce: 'bounce', swing: 'swing',
    glow: 'glow', blur: 'blur', neon: 'neon', elastic: 'elastic', rotate: 'rotate-in',
    'rotate-in': 'rotate-in'
  };

  function reduceMotion() {
    try {
      return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) { return false; }
  }

  function resolveFx(name) {
    if (!name || name === 'none') return 'fade';
    return FX_ALIAS[String(name).toLowerCase()] || 'fade';
  }

  function playSound(type, vol) {
    if (!type || type === 'none') return;
    if (reduceMotion() && type !== 'button-click' && type !== 'click') return;
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = playSound._ctx || (playSound._ctx = new Ctx());
      if (ctx.state === 'suspended') {
        ctx.resume().catch(function () {});
      }
      var v = Number(vol);
      if (isNaN(v)) v = 35;
      if (v > 1) v = v / 100;
      v = Math.max(0, Math.min(1, v)) * 0.55;
      var seqMap = {
        'button-click': [220, 140],
        click: [720],
        chime: [523, 659, 784],
        whoosh: [180, 320, 480],
        soft: [380],
        beep: [980, 720]
      };
      var seq = seqMap[type] || seqMap.click;
      var oscType = (type === 'button-click' || type === 'beep') ? 'square' : (type === 'whoosh' ? 'triangle' : 'sine');
      var dur = type === 'chime' ? 0.14 : (type === 'whoosh' ? 0.12 : 0.09);
      seq.forEach(function (f, i) {
        var o = ctx.createOscillator();
        var g = ctx.createGain();
        o.type = oscType;
        o.frequency.value = f;
        g.gain.value = Math.max(0.001, v * (1 - i * 0.22));
        o.connect(g); g.connect(ctx.destination);
        var t0 = ctx.currentTime + i * 0.045;
        o.start(t0);
        try { g.gain.exponentialRampToValueAtTime(0.001, t0 + dur); } catch (e1) { g.gain.value = 0; }
        o.stop(t0 + dur + 0.03);
      });
    } catch (e) {}
  }

  /* Unlock AudioContext on first user gesture (required by browsers, including preview iframe) */
  function unlockAudioOnce() {
    if (unlockAudioOnce._done) return;
    unlockAudioOnce._done = true;
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = playSound._ctx || (playSound._ctx = new Ctx());
      if (ctx.state === 'suspended') ctx.resume().catch(function () {});
      /* silent blip */
      var o = ctx.createOscillator();
      var g = ctx.createGain();
      g.gain.value = 0.0001;
      o.connect(g); g.connect(ctx.destination);
      o.start();
      o.stop(ctx.currentTime + 0.01);
    } catch (e) {}
  }
  ['pointerdown', 'touchstart', 'keydown'].forEach(function (ev) {
    document.addEventListener(ev, unlockAudioOnce, { once: true, passive: true, capture: true });
  });

  function restartAnim(el, fx, ms) {
    if (!el) return;
    if (reduceMotion()) {
      el.classList.add('is-animating');
      el.style.opacity = '1';
      el.style.transform = 'none';
      return;
    }
    var resolved = resolveFx(fx);
    var msN = parseInt(ms, 10);
    if (isNaN(msN) || msN < 40) msN = 220;
    if (msN > 2000) msN = 2000;
    var prev = el.className.toString().split(/\s+/).filter(function (c) {
      return c && c.indexOf('cgs-anim-') !== 0 && c !== 'is-animating';
    });
    el.className = prev.join(' ');
    el.style.animation = 'none';
    el.style.opacity = '';
    el.style.transform = '';
    el.style.filter = '';
    el.style.setProperty('--cgs-fx-ms', msN + 'ms');
    var animName = {
      fade: 'cgsFxFade', slide: 'cgsFxSlide', 'slide-up': 'cgsFxSlideUp',
      'slide-h': 'cgsFxSlideH', grow: 'cgsFxGrow', flip: 'cgsFxFlip',
      'flip-x': 'cgsFxFlipX', bounce: 'cgsFxBounce', swing: 'cgsFxSwing',
      glow: 'cgsFxGlow', blur: 'cgsFxBlur', neon: 'cgsFxNeon',
      elastic: 'cgsFxElastic', 'rotate-in': 'cgsFxRotate'
    }[resolved] || 'cgsFxFade';
    /* rAF x2: ensure display:block from .is-open is applied before animation */
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        void el.offsetWidth;
        el.classList.add('cgs-anim-' + resolved, 'is-animating');
        el.style.animation = animName + ' ' + msN + 'ms ease both';
      });
    });
  }

  function closeItem(li, opts) {
    if (!li) return;
    li.classList.remove('is-open');
    var link = li.querySelector(':scope > .cgs-nav-link, :scope > a');
    if (link) {
      link.setAttribute('aria-expanded', 'false');
      if (opts && opts.returnFocus) {
        try { link.focus({ preventScroll: true }); } catch (e) { try { link.focus(); } catch (e2) {} }
      }
    }
  }

  function openItem(li, nav, force, opts) {
    if (!li) return;
    if (li.classList.contains('is-open') && !force) return;
    var parent = li.parentElement;
    if (parent) {
      [].forEach.call(parent.children, function (sib) {
        if (sib !== li && sib.classList && sib.classList.contains('is-open')) closeItem(sib);
      });
    }
    li.classList.add('is-open');
    var link = li.querySelector(':scope > .cgs-nav-link, :scope > a');
    if (link) link.setAttribute('aria-expanded', 'true');
    var wrap = li.querySelector(':scope > .cgs-nav-sub-wrap');
    var panel = wrap ? (wrap.querySelector('.cgs-nav-sub') || wrap) : null;
    var fx = (li.getAttribute('data-effect') || (nav && nav.getAttribute('data-effect')) || 'fade');
    var ms = (li.getAttribute('data-speed') || (nav && nav.getAttribute('data-speed')) || '220');
    if (panel) restartAnim(panel, fx, ms);
    if (wrap && wrap !== panel) restartAnim(wrap, fx, ms);
    else if (!panel && wrap) restartAnim(wrap, fx, ms);
    /* Focus first actionable in submenu (a11y) */
    if (opts && opts.focusChild) {
      var target = (panel || wrap);
      if (target) {
        var first = target.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
        if (first) {
          try { first.focus({ preventScroll: true }); } catch (e) { try { first.focus(); } catch (e2) {} }
        }
      }
    }
    var vol = (nav && (nav.getAttribute('data-vol') || nav.getAttribute('data-volume'))) || '35';
    var sound = (nav && nav.getAttribute('data-sound')) || 'none';
    if (sound && sound !== 'none') playSound(sound, vol);
    /* Focus first actionable in submenu */
    if (wrap) {
      var first = wrap.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
      if (first) {
        try { first.focus({ preventScroll: true }); } catch (e) {}
      }
    }
  }

  function applyBreakpoint(nav) {
    var bp = parseInt(nav.getAttribute('data-breakpoint') || '768', 10) || 768;
    if ((window.innerWidth || 0) <= bp) nav.classList.add('cgs-is-mobile');
    else nav.classList.remove('cgs-is-mobile');
  }

  function applySubDir(nav) {
    /* v4.10.85 nested side: top-level uses nav data-sub-dir; nested defaults to side */
    var subDir = nav.getAttribute('data-sub-dir') || 'bottom';
    nav.querySelectorAll('.cgs-nav-sub-wrap').forEach(function (w) {
      w.classList.remove('cgs-dir-bottom', 'cgs-dir-top', 'cgs-dir-left', 'cgs-dir-right');
      var isNested = !!(w.parentElement && w.parentElement.closest && w.parentElement.closest('.cgs-nav-sub'));
      var local = w.getAttribute('data-open-dir');
      if (!local) {
        local = isNested ? 'right' : (subDir || 'bottom');
        /* RTL: nested default to left (opens inward) */
        if (isNested && nav.getAttribute('dir') === 'rtl') local = 'left';
      }
      w.setAttribute('data-open-dir', local);
      w.classList.add('cgs-dir-' + local);
    });
  }

  function visibleItems(scope) {
    return [].slice.call((scope || document).querySelectorAll('.cgs-nav-item > .cgs-nav-link, .cgs-nav-item > a, .cgs-nav-cta')).filter(function (a) {
      return !!(a.offsetWidth || a.offsetHeight || a.getClientRects().length);
    });
  }

  function bindNav(nav) {
    if (!nav) return;
    if (nav._cgsDelegBound) {
      applyBreakpoint(nav);
      applySubDir(nav);
      return;
    }
    nav._cgsDelegBound = true;
    nav._cgsBound = true;

    nav.style.setProperty('--cgs-mega-cols', nav.getAttribute('data-mega-cols') || '3');
    var intentMs = parseInt(nav.getAttribute('data-intent') || '180', 10) || 180;
    var trigger = nav.getAttribute('data-trigger') || 'hover';
    var secondTap = nav.getAttribute('data-second-tap') || 'open';
    var bp = parseInt(nav.getAttribute('data-breakpoint') || '768', 10) || 768;
    nav.setAttribute('data-speed', String(parseInt(nav.getAttribute('data-speed') || '220', 10) || 220));
    if (!nav.getAttribute('data-vol') && nav.getAttribute('data-volume')) {
      nav.setAttribute('data-vol', nav.getAttribute('data-volume'));
    }

    applyBreakpoint(nav);
    applySubDir(nav);
    if (!nav._cgsBpBound) {
      nav._cgsBpBound = true;
      window.addEventListener('resize', function () { applyBreakpoint(nav); }, { passive: true });
    }

    var timers = { open: null, close: null };
    function cancelTimers() {
      clearTimeout(timers.open);
      clearTimeout(timers.close);
    }

    nav.addEventListener('click', function (e) {
      var toggle = e.target.closest && e.target.closest('.cgs-nav-toggle');
      if (toggle && nav.contains(toggle)) {
        e.preventDefault();
        var open = nav.classList.toggle('is-mobile-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        playSound(nav.getAttribute('data-sound') || 'click', parseInt(nav.getAttribute('data-vol') || '35', 10));
        return;
      }
      var parentLink = e.target.closest && e.target.closest('.cgs-nav-item.has-children > .cgs-nav-link, .cgs-nav-item.has-children > a');
      if (parentLink && nav.contains(parentLink)) {
        var li = parentLink.closest('.cgs-nav-item');
        var isMobile = nav.classList.contains('cgs-is-mobile') || window.innerWidth <= bp;
        if (!isMobile && trigger !== 'click') return;
        if (!li.classList.contains('is-open')) {
          e.preventDefault();
          openItem(li, nav, true);
        } else if (secondTap === 'open' || secondTap === 'close') {
          e.preventDefault();
          closeItem(li, { returnFocus: true });
        }
        return;
      }
      var leaf = e.target.closest && e.target.closest('.cgs-nav-cta, .cgs-nav-item:not(.has-children) > .cgs-nav-link');
      if (leaf && nav.contains(leaf)) {
        playSound(nav.getAttribute('data-sound') || 'click', parseInt(nav.getAttribute('data-vol') || '35', 10));
      }
    });

    if (trigger !== 'click') {
      nav.addEventListener('mouseover', function (e) {
        var li = e.target.closest && e.target.closest('.cgs-nav-item.has-children');
        if (!li || !nav.contains(li) || nav.classList.contains('cgs-is-mobile')) return;
        cancelTimers();
        timers.open = setTimeout(function () { if (!li.classList.contains('is-open')) openItem(li, nav, false); }, intentMs);
      });
      nav.addEventListener('mouseout', function (e) {
        var li = e.target.closest && e.target.closest('.cgs-nav-item.has-children');
        if (!li || !nav.contains(li)) return;
        if (e.relatedTarget && li.contains(e.relatedTarget)) return;
        /* still inside same nav mega panel */
        if (e.relatedTarget && nav.contains(e.relatedTarget)) {
          var still = e.relatedTarget.closest && e.relatedTarget.closest('.cgs-nav-item.has-children.is-open, .cgs-nav-sub-wrap');
          if (still && (li.contains(still) || still === li || li.contains(e.relatedTarget))) return;
        }
        cancelTimers();
        var isMega = /cgs-nav--mega/.test(nav.className || '');
        var closeMs = isMega ? Math.max(320, intentMs + 120) : Math.max(220, intentMs + 40);
        timers.close = setTimeout(function () { closeItem(li); }, closeMs);
      });
    }

    /* Keyboard model */
    nav.addEventListener('keydown', function (e) {
      var key = e.key;
      if (key === 'Escape') {
        var open = nav.querySelector('.cgs-nav-item.is-open');
        if (open) {
          e.preventDefault();
          closeItem(open, { returnFocus: true });
        }
        return;
      }
      var active = document.activeElement;
      if (!active || !nav.contains(active)) return;
      var items = visibleItems(nav);
      var idx = items.indexOf(active);
      if (idx < 0) {
        var inItem = active.closest && active.closest('.cgs-nav-item');
        if (inItem) {
          var l = inItem.querySelector(':scope > .cgs-nav-link, :scope > a');
          idx = items.indexOf(l);
        }
      }

      if (key === 'ArrowRight' || key === 'ArrowLeft') {
        e.preventDefault();
        if (!items.length) return;
        var dir = (key === 'ArrowRight') ? (nav.getAttribute('dir') === 'rtl' ? -1 : 1) : (nav.getAttribute('dir') === 'rtl' ? 1 : -1);
        var next = items[(idx + dir + items.length) % items.length];
        if (next) next.focus();
        return;
      }
      if (key === 'ArrowDown') {
        e.preventDefault();
        var li = active.closest && active.closest('.cgs-nav-item.has-children');
        if (li) {
          openItem(li, nav, true, { focusChild: true });
          return;
        }
        if (items[idx + 1]) items[idx + 1].focus();
        return;
      }
      if (key === 'ArrowUp') {
        e.preventDefault();
        var openLi = active.closest && active.closest('.cgs-nav-item.is-open, .cgs-nav-sub-wrap .cgs-nav-item');
        var parentOpen = active.closest && active.closest('.cgs-nav-sub-wrap') && active.closest('.cgs-nav-sub-wrap').parentElement;
        if (parentOpen && parentOpen.classList.contains('cgs-nav-item')) {
          closeItem(parentOpen, { returnFocus: true });
          return;
        }
        if (items[idx - 1]) items[idx - 1].focus();
        return;
      }
      if (key === 'Home') {
        e.preventDefault();
        if (items[0]) items[0].focus();
        return;
      }
      if (key === 'End') {
        e.preventDefault();
        if (items.length) items[items.length - 1].focus();
      }
    });

    nav.querySelectorAll('.cgs-nav-item.has-children > .cgs-nav-link, .cgs-nav-item.has-children > a').forEach(function (link) {
      link.setAttribute('aria-haspopup', 'true');
      if (!link.getAttribute('aria-expanded')) link.setAttribute('aria-expanded', 'false');
    });
  }

  function bindAll(root) {
    root = root || document;
    /* inherit menu-level open direction onto wraps without explicit dir */
    root.querySelectorAll('.cgs-nav').forEach(function (nav) {
      var dir = nav.getAttribute('data-sub-dir') || 'bottom';
      nav.querySelectorAll('.cgs-nav-sub-wrap').forEach(function (w) {
        var d = w.getAttribute('data-open-dir');
        if (!d || d === '') {
          w.setAttribute('data-open-dir', dir);
          w.classList.add('cgs-dir-' + dir);
        }
      });
      var cols = parseInt(nav.getAttribute('data-mega-cols') || '3', 10) || 3;
      nav.style.setProperty('--cgs-mega-cols', String(cols));
      var ms = parseInt(nav.getAttribute('data-speed') || '220', 10) || 220;
      nav.style.setProperty('--cgs-fx-ms', ms + 'ms');
    });


    /* Digikala sidebar: hover/focus switches content pane */
    (root || document).querySelectorAll('.cgs-dk-panel').forEach(function (panel) {
      var sides = panel.querySelectorAll('.cgs-dk-side-item');
      var panes = panel.querySelectorAll('.cgs-dk-pane');
      function activate(id) {
        sides.forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-dk-panel') === id); });
        panes.forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-dk-panel') === id); });
      }
      sides.forEach(function (s) {
        s.addEventListener('mouseenter', function () { activate(s.getAttribute('data-dk-panel')); });
        s.addEventListener('focus', function () { activate(s.getAttribute('data-dk-panel')); });
        s.addEventListener('click', function (e) { e.preventDefault(); activate(s.getAttribute('data-dk-panel')); });
      });
    });
    (root || document).querySelectorAll('.cgs-nav').forEach(bindNav);
  }

  window.CGSMenuFront = {
    bindNav: bindNav,
    bindAll: bindAll,
    openItem: openItem,
    closeItem: closeItem,
    playSound: playSound,
    restartAnim: restartAnim,
    unlockAudio: function () { try { unlockAudioOnce(); } catch (e) {} }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { bindAll(document); });
  } else {
    bindAll(document);
  }
})();
