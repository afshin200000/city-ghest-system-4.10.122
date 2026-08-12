
(function ($) {
  "use strict";
  function unlock() {
    try { document.body.classList.remove("cgs-is-sorting", "cgs-is-resizing"); } catch (e) {}
    $("#cgs-live-preview input, #cgs-live-preview select, #cgs-live-preview textarea")
      .prop("disabled", false).prop("readonly", false)
      .css({ pointerEvents: "auto", opacity: 1 });
  }
  function showStep(idx) {
    var $steps = $("#cgs-live-preview .cgs-form-step");
    if (!$steps.length) return;
    idx = Math.max(0, Math.min($steps.length - 1, idx));
    $steps.removeClass("active").hide();
    $steps.eq(idx).addClass("active").show();
    $("#cgs-live-preview .cgs-step-indicator").removeClass("active").eq(idx).addClass("active");
    var pct = Math.round(((idx + 1) / $steps.length) * 100);
    $("#cgs-live-preview .cgs-progress-fill").css("width", pct + "%");
    $("#cgs-lp-step-info").text("مرحله " + (idx + 1) + " از " + $steps.length);
    window._cgsLpStep = idx;
  }
  function cur() {
    var $s = $("#cgs-live-preview .cgs-form-step");
    var $a = $s.filter(".active:visible");
    return $a.length ? $s.index($a) : (window._cgsLpStep || 0);
  }
  $(document).on("click", "#cgs-lp-step-next", function (e) { e.preventDefault(); showStep(cur() + 1); });
  $(document).on("click", "#cgs-lp-step-prev", function (e) { e.preventDefault(); showStep(cur() - 1); });
  $(document).on("input change", ".cgs-step-name, .cgs-step-icon", function () {
    $("#cgs-step-meta-cards .cgs-step-card").each(function (i) {
      var name = $(this).find(".cgs-step-name").val() || ("مرحله " + (i + 1));
      var icon = $(this).find(".cgs-step-icon").val() || "";
      var $lab = $("#cgs-live-preview .cgs-step-indicator").eq(i).find(".cgs-step-label");
      if ($lab.length) $lab.text((icon ? icon + " " : "") + name);
    });
  });
  $(function () {
    unlock();
    setTimeout(function () {
      if (typeof window.initPreviewLayoutSortable === "function") window.initPreviewLayoutSortable();
    }, 300);
    setTimeout(function () {
      if (typeof window.initPreviewLayoutSortable === "function") window.initPreviewLayoutSortable();
    }, 1000);
    var $steps = $("#cgs-live-preview .cgs-form-step");
    if ($steps.length) $steps.hide().first().show().addClass("active");
  });
})(jQuery);
