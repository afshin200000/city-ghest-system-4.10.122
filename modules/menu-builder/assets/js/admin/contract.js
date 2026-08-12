/**
 * CGS MB — Module Contract + Permanent Governance (v4.10.98)
 * Includes Preview = End User permanent rule.
 */
(function (w) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  var REQUIRED = {
    form: ['fillForm'],
    icons: ['renderIconBank', 'renderEmojiBank'],
    colors: ['bindColorPair'],
    tabs: ['setTab'],
    items: ['render'],
    preview: ['loadFromServer', 'buildSrcdoc', 'injectIframe'],
    save: ['saveMenu'],
    defaults: ['defMenu', 'defItem', 'fixtureMega2x2'],
    diagnostics: ['runPreviewMonitor', 'runAcceptanceGate'],
    tree: ['flatten', 'unflatten', 'removeById'],
    cta: ['syncCtaPreviews'],
    sound: ['playSound']
  };

  /** Permanent governance flags (always on) */
  var GOVERNANCE = {
    noPatches: true,
    tenPassBeforeZip: true,
    proposalsNeedApproval: true,
    previewEqualsEndUser: true,
    previewParityTargets: [
      'Theme',
      'Elementor',
      'WooCommerce',
      'CSS',
      'Font',
      'Container',
      'Z-index',
      'Position',
      'Responsive'
    ]
  };

  function smokeBoot() {
    var missing = [];
    var present = [];
    var root = w.CGS_MB_Modules || {};
    Object.keys(REQUIRED).forEach(function (mod) {
      var api = root[mod];
      if (!api) {
        missing.push(mod + ' (module)');
        return;
      }
      REQUIRED[mod].forEach(function (fn) {
        if (typeof api[fn] !== 'function') missing.push(mod + '.' + fn);
        else present.push(mod + '.' + fn);
      });
    });

    // Preview = End User surface checks
    if (root.preview) {
      if (typeof root.preview.parity !== 'function' && typeof root.preview.buildSrcdoc !== 'function') {
        missing.push('preview.parity|buildSrcdoc');
      } else {
        present.push('preview.endUserContract');
      }
    }

    return { ok: missing.length === 0, missing: missing, present: present, governance: GOVERNANCE };
  }

  function assertOrReport() {
    var r = smokeBoot();
    if (!r.ok) {
      try { console.error('[CGS MB Contract] missing APIs:', r.missing); } catch (e) {}
    } else {
      try {
        console.info('[CGS MB Contract] OK · Preview=EndUser targets:', GOVERNANCE.previewParityTargets.join(', '));
      } catch (e2) {}
    }
    return r;
  }

  w.CGS_MB_Modules.contract = {
    REQUIRED: REQUIRED,
    GOVERNANCE: GOVERNANCE,
    smokeBoot: smokeBoot,
    assertOrReport: assertOrReport,
    maxDepth: 5,
    version: '4.10.98'
  };
  w.CGS_MB_Modules.version = '4.10.98';
  w.CGS_MB_Modules.maxDepth = 5;
})(window);
