/**
 * CGS MB Admin — Sound preview (clean rewrite v4.10.103)
 * Same WebAudio model as front.js for parity.
 */
(function (w, $) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function playSound(kind, volArg) {
    try {
      var $ = w.jQuery;
      var type = kind || ($ && $('#m-sound').length ? $('#m-sound').val() : null) || 'none';
      if (!type || type === 'none') return;
      var volNum = volArg != null ? Number(volArg) : ($ && $('#m-sound-vol').length ? parseInt($('#m-sound-vol').val(), 10) : 35);
      if (isNaN(volNum)) volNum = 35;
      if (volNum > 1) volNum = volNum / 100;
      volNum = Math.max(0, Math.min(1, volNum)) * 0.55;

      var Ctx = w.AudioContext || w.webkitAudioContext;
      if (!Ctx) return;
      if (!playSound._ctx) playSound._ctx = new Ctx();
      var ctx = playSound._ctx;
      if (ctx.state === 'suspended') ctx.resume().catch(function () {});

      var freqs = {
        'button-click': [220, 140],
        click: [720],
        chime: [523, 659, 784],
        whoosh: [180, 320, 480],
        soft: [380],
        beep: [980, 720]
      }[type] || [500];
      var oscType = (type === 'button-click' || type === 'beep') ? 'square' : (type === 'whoosh' ? 'triangle' : 'sine');
      var dur = type === 'chime' ? 0.14 : 0.1;

      freqs.forEach(function (f, i) {
        var o = ctx.createOscillator();
        var g = ctx.createGain();
        o.type = oscType;
        o.frequency.value = f;
        g.gain.value = Math.max(0.001, volNum * (1 - i * 0.22));
        o.connect(g);
        g.connect(ctx.destination);
        var t0 = ctx.currentTime + i * 0.045;
        o.start(t0);
        try { g.gain.exponentialRampToValueAtTime(0.001, t0 + dur); } catch (e1) {}
        o.stop(t0 + dur + 0.03);
      });
    } catch (e) {
      console.warn('[CGS sound]', e);
    }
  }

  w.CGS_MB_Modules.sound = { playSound: playSound };
})(window, window.jQuery);
