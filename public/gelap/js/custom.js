/* Cube sidebar & UI helpers — vanilla JS, tanpa jQuery (#6118 3c).
   Perilaku dipertahankan 1:1 dari versi jQuery sebelumnya. */

(function () {
  "use strict";

  var ANIMATION_SPEED = 300;

  function onReady(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  function isVisible(el) {
    return !!el && getComputedStyle(el).display !== "none";
  }

  // Pengganti $.slideUp/$.slideDown — animasi height via CSS transition
  function slideUp(el, duration, done) {
    el.style.height = el.offsetHeight + "px";
    el.offsetHeight; // paksa reflow supaya transition jalan
    el.style.overflow = "hidden";
    el.style.transition = "height " + duration + "ms ease";
    el.style.height = "0px";
    setTimeout(function () {
      el.style.display = "none";
      el.style.removeProperty("height");
      el.style.removeProperty("overflow");
      el.style.removeProperty("transition");
      if (done) done();
    }, duration);
  }

  function slideDown(el, duration, done) {
    el.style.display = "block";
    var target = el.scrollHeight;
    el.style.overflow = "hidden";
    el.style.height = "0px";
    el.offsetHeight; // paksa reflow
    el.style.transition = "height " + duration + "ms ease";
    el.style.height = target + "px";
    setTimeout(function () {
      el.style.removeProperty("height");
      el.style.removeProperty("overflow");
      el.style.removeProperty("transition");
      if (done) done();
    }, duration);
  }

  function menuKeyOf(a) {
    var menuText = a.querySelector(".menu-text");
    return (menuText ? menuText.textContent : a.textContent).trim();
  }

  function sidebarMenu(menu) {
    // Restore saved submenu state across page navigations
    var savedMenu = localStorage.getItem("openSubmenu");
    if (savedMenu) {
      menu.querySelectorAll("li").forEach(function (li) {
        var a = li.querySelector(":scope > a");
        if (!a || menuKeyOf(a) !== savedMenu) return;
        var sub = a.nextElementSibling;
        if (sub && sub.classList.contains("treeview-menu")) {
          sub.style.display = "block";
          sub.classList.add("menu-open");
          li.classList.add("active");
        }
      });
    }

    menu.addEventListener("click", function (e) {
      var a = e.target.closest("li a");
      if (!a || !menu.contains(a)) return;

      var checkElement = a.nextElementSibling;
      var isTreeview =
        !!checkElement && checkElement.classList.contains("treeview-menu");

      if (isTreeview && isVisible(checkElement)) {
        slideUp(checkElement, ANIMATION_SPEED, function () {
          checkElement.classList.remove("menu-open");
        });
        var closeLi = a.closest("li");
        if (closeLi) closeLi.classList.remove("active");
        localStorage.removeItem("openSubmenu");
      }

      // If the menu is not visible
      else if (isTreeview && !isVisible(checkElement)) {
        // Get the parent menu
        var parent = a.closest("ul");
        // Close all open menus within the parent
        if (parent) {
          parent.querySelectorAll("ul").forEach(function (ul) {
            if (isVisible(ul)) slideUp(ul, ANIMATION_SPEED);
            ul.classList.remove("menu-open");
          });
        }
        // Get the parent li
        var parentLi = a.closest("li");

        // Open the target menu and add the menu-open class
        slideDown(checkElement, ANIMATION_SPEED, function () {
          checkElement.classList.add("menu-open");
          if (parent) {
            parent.querySelectorAll("li.active").forEach(function (li) {
              li.classList.remove("active");
            });
          }
          if (parentLi) parentLi.classList.add("active");
        });

        // Save which submenu was opened
        localStorage.setItem("openSubmenu", menuKeyOf(a));
      }

      // Hanya cegah navigate untuk parent toggle tanpa href asli (#! placeholder).
      // Item dengan href asli (mis. Penyambungan → /ingest/sambung) tetap navigate
      // sekaligus toggle submenu — submenu auto-buka via .active class server-rendered.
      var href = a.getAttribute("href") || "";
      if (isTreeview && (href === "" || href === "#" || href === "#!")) {
        e.preventDefault();
      }
    });
  }

  document.querySelectorAll(".sidebar-menu").forEach(sidebarMenu);

  // Custom Sidebar JS
  onReady(function () {
    var pageWrapper = document.querySelector(".page-wrapper");
    var sidebar = document.getElementById("sidebar");
    if (!pageWrapper) return;

    function addHovered() {
      pageWrapper.classList.add("sidebar-hovered");
    }
    function removeHovered() {
      pageWrapper.classList.remove("sidebar-hovered");
    }
    function bindSidebarHover() {
      if (!sidebar) return;
      sidebar.addEventListener("mouseenter", addHovered);
      sidebar.addEventListener("mouseleave", removeHovered);
    }
    function unbindSidebarHover() {
      if (!sidebar) return;
      sidebar.removeEventListener("mouseenter", addHovered);
      sidebar.removeEventListener("mouseleave", removeHovered);
    }

    // toggle sidebar
    document.querySelectorAll(".toggle-sidebar").forEach(function (el) {
      el.addEventListener("click", function () {
        pageWrapper.classList.toggle("toggled");
        localStorage.setItem(
          "sidebarToggled",
          pageWrapper.classList.contains("toggled") ? "true" : "false"
        );
      });
    });

    // Pin sidebar on click
    document.querySelectorAll(".pin-sidebar").forEach(function (el) {
      el.addEventListener("click", function () {
        if (pageWrapper.classList.contains("pinned")) {
          // unpin sidebar when hovered
          pageWrapper.classList.remove("pinned");
          unbindSidebarHover();
        } else {
          pageWrapper.classList.add("pinned");
          bindSidebarHover();
        }
      });
    });

    // Pinned sidebar (hover default — paritas versi jQuery)
    bindSidebarHover();

    // Toggle sidebar overlay
    var overlay = document.getElementById("overlay");
    if (overlay) {
      overlay.addEventListener("click", function () {
        pageWrapper.classList.toggle("toggled");
      });
    }

    // When the window is resized
    window.addEventListener("resize", function () {
      if (window.innerWidth <= 768) pageWrapper.classList.remove("pinned");
      if (window.innerWidth >= 768) pageWrapper.classList.remove("toggled");
    });
  });

  /***********
  ***********
  ***********
    Bootstrap JS
  ***********
  ***********
  ***********/

  onReady(function () {
    // Tooltip
    var tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popover
    var popoverTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="popover"]')
    );
    popoverTriggerList.map(function (popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl);
    });

    // Card Loading (pengganti fadeOut(2000))
    document.querySelectorAll(".card-loader").forEach(function (el) {
      el.style.transition = "opacity 2000ms ease";
      el.style.opacity = "0";
      setTimeout(function () {
        el.style.display = "none";
      }, 2000);
    });

    // Toasts
    // Helper function to set up toast button listeners
    function setupToastButton(btnId, toastId) {
      const btn = document.getElementById(btnId);
      if (btn) {
        btn.addEventListener("click", function () {
          const toastElement = document.getElementById(toastId);
          if (toastElement) {
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
          }
        });
      }
    }

    // Basic Toast
    setupToastButton("liveToastBtn", "liveToast");

    // Placement Toasts
    setupToastButton("topLeftToastBtn", "topLeftToast");
    setupToastButton("topRightToastBtn", "topRightToast");
    setupToastButton("bottomLeftToastBtn", "bottomLeftToast");
    setupToastButton("bottomRightToastBtn", "bottomRightToast");

    // Colored Toasts
    setupToastButton("primaryToastBtn", "primaryToast");
    setupToastButton("successToastBtn", "successToast");
    setupToastButton("dangerToastBtn", "dangerToast");
    setupToastButton("warningToastBtn", "warningToast");
    setupToastButton("infoToastBtn", "infoToast");

    // Icon Toasts
    setupToastButton("infoIconToastBtn", "infoIconToast");
    setupToastButton("successIconToastBtn", "successIconToast");
    setupToastButton("errorIconToastBtn", "errorIconToast");
    setupToastButton("warningIconToastBtn", "warningIconToast");
  });
})();
