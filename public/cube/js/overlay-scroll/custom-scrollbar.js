/* Init OverlayScrollbars — vanilla API v1 (jQuery plugin dihapus, #6118 3c). */

(function () {
  "use strict";

  var OPTIONS = {
    scrollbars: {
      visibility: "auto",
      autoHide: "leave",
      autoHideDelay: 200,
      dragScrolling: true,
      clickScrolling: false,
      touchSupport: true,
      snapHandle: false,
    },
  };

  function init() {
    if (typeof OverlayScrollbars === "undefined") return;
    [
      ".sidebarMenuScroll",
      ".scroll250",
      ".scroll290",
      ".scroll300",
      ".scroll350",
    ].forEach(function (selector) {
      var els = document.querySelectorAll(selector);
      if (els.length) OverlayScrollbars(els, OPTIONS);
    });
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
