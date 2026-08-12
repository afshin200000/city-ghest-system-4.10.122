(function ($) {
  'use strict';

  var map = null;
  var marker = null;
  var circle = null;
  var coords = {};
  var provinceLayer = null;

  var IRAN_BOUNDS = L.latLngBounds([25.0, 44.0], [40.0, 63.5]);

  function getCoords() {
    if (typeof cgsPublic !== 'undefined' && cgsPublic.city_coords) {
      return cgsPublic.city_coords;
    }
    return coords;
  }

  function ensureMap() {
    var el = document.getElementById('cgs-iran-map');
    if (!el) return null;
    if (typeof L === 'undefined') {
      el.innerHTML = '<div class="cgs-map-fallback" style="display:flex;align-items:center;justify-content:center;height:100%;background:linear-gradient(180deg,#e8eef5,#f8fafc);color:#1a237e;font-weight:700;">نقشه بارگذاری نشد — اتصال اینترنت یا CDN را بررسی کنید</div>';
      return null;
    }
    if (map) {
      setTimeout(function () { map.invalidateSize(); }, 100);
      return map;
    }

    map = L.map('cgs-iran-map', {
      center: [32.4, 53.6],
      zoom: 5,
      minZoom: 4,
      maxZoom: 14,
      maxBounds: IRAN_BOUNDS.pad(0.15),
      maxBoundsViscosity: 0.85,
      scrollWheelZoom: false,
      zoomControl: false,
      attributionControl: true
    });

    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Soft vignette overlay via pane
    map.createPane('cgsLabel');
    map.getPane('cgsLabel').style.zIndex = 650;

    setTimeout(function () { map.invalidateSize(); }, 250);
    $(window).on('resize.cgsMap', function () {
      if (map) map.invalidateSize();
    });

    return map;
  }

  function findCoord(name) {
    var all = getCoords();
    if (!name) return null;
    if (all[name]) return all[name];
    var keys = Object.keys(all);
    for (var i = 0; i < keys.length; i++) {
      if (keys[i].indexOf(name) !== -1 || name.indexOf(keys[i]) !== -1) {
        return all[keys[i]];
      }
    }
    return null;
  }

  function customIcon() {
    return L.divIcon({
      className: 'cgs-map-pin',
      html: '<span class="cgs-map-pin-dot"></span><span class="cgs-map-pin-pulse"></span>',
      iconSize: [22, 22],
      iconAnchor: [11, 11]
    });
  }

  function showCity(name, province) {
    var m = ensureMap();
    if (!m) return;
    var c = findCoord(name);
    updateHud(name, province, !!c);

    if (!c) {
      m.flyTo([32.4, 53.6], 5, { duration: 0.8 });
      return;
    }

    if (marker) m.removeLayer(marker);
    if (circle) m.removeLayer(circle);

    circle = L.circle(c, {
      radius: 18000,
      color: '#1a237e',
      weight: 1,
      fillColor: '#3949ab',
      fillOpacity: 0.12
    }).addTo(m);

    marker = L.marker(c, { icon: customIcon() }).addTo(m);
    marker.bindPopup(
      '<div class="cgs-map-popup"><strong>' + escapeHtml(name) + '</strong>' +
      (province ? '<span>' + escapeHtml(province) + '</span>' : '') + '</div>',
      { closeButton: false, offset: [0, -8] }
    ).openPopup();

    m.flyTo(c, 9, { duration: 1.1 });
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function updateHud(city, province, found) {
    var $hud = $('#cgs-map-hud');
    if (!$hud.length) return;
    if (!city) {
      $hud.html('<span class="cgs-map-hud-muted">استان و شهر را انتخاب کنید</span>');
      return;
    }
    var html = '<span class="cgs-map-hud-city">' + escapeHtml(city) + '</span>';
    if (province) html += '<span class="cgs-map-hud-sep">·</span><span class="cgs-map-hud-prov">' + escapeHtml(province) + '</span>';
    if (!found) html += '<span class="cgs-map-hud-warn">موقعیت تقریبی در دسترس نیست</span>';
    $hud.html(html);
  }

  function currentProvince($el) {
    var $form = $el.closest('.cgs-form-wrapper, form, .cgs-preview-mode');
    return $form.find('select.cgs-province, select[data-role="province"], select[name="province"]').first().val() || '';
  }

  window.cgsIranMap = {
    init: function (cityCoords) {
      if (cityCoords) coords = cityCoords;
      ensureMap();
    },
    showCity: showCity,
    resize: function () { if (map) map.invalidateSize(); }
  };

  $(document).on('change', 'select.cgs-city, select[data-role="city"], select[name="city"]', function () {
    var name = $(this).val();
    if (name) showCity(name, currentProvince($(this)));
  });

  $(document).on('change', 'select.cgs-province, select[data-role="province"], select[name="province"]', function () {
    updateHud('', $(this).val(), true);
    var m = ensureMap();
    if (m) m.flyTo([32.4, 53.6], 5, { duration: 0.6 });
  });

  $(document).on('click', '.cgs-map-zoom-in', function (e) {
    e.preventDefault();
    var m = ensureMap();
    if (m) m.zoomIn();
  });
  $(document).on('click', '.cgs-map-zoom-out', function (e) {
    e.preventDefault();
    var m = ensureMap();
    if (m) m.zoomOut();
  });
  $(document).on('click', '.cgs-map-reset', function (e) {
    e.preventDefault();
    var m = ensureMap();
    if (m) m.flyTo([32.4, 53.6], 5, { duration: 0.7 });
    updateHud('', '', true);
  });
  $(document).on('click', '.cgs-map-toggle-scroll', function (e) {
    e.preventDefault();
    var m = ensureMap();
    if (!m) return;
    if (m.scrollWheelZoom.enabled()) {
      m.scrollWheelZoom.disable();
      $(this).removeClass('is-on').attr('title', 'فعال‌سازی زوم با اسکرول');
    } else {
      m.scrollWheelZoom.enable();
      $(this).addClass('is-on').attr('title', 'غیرفعال‌سازی زوم با اسکرول');
    }
  });

  // Show map when address step is visible
  $(document).on('click', '.cgs-next-step, .cgs-prev-step', function () {
    setTimeout(function () {
      if (map) map.invalidateSize();
    }, 350);
  });

  $(function () {
    coords = getCoords();
    if ($('#cgs-iran-map').length) {
      ensureMap();
    }
  });
})(jQuery);
