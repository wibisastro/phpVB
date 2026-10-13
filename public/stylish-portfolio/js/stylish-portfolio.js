/*! Tema stylish-portfolio phpVB — port vanilla dari StartBootstrap Stylish Portfolio v6.0.6
 *  js/scripts.js (MIT, https://github.com/StartBootstrap/startbootstrap-stylish-portfolio).
 *  Perubahan: ikon fa-bars/fa-xmark (Font Awesome) → bi-list/bi-x-lg (bootstrap-icons);
 *  menu ditutup hanya bila sedang terbuka; tanpa jQuery (phpVB tidak memuatnya). */
window.addEventListener('DOMContentLoaded', function () {
    var sidebarWrapper = document.getElementById('sidebar-wrapper');
    var menuToggle = document.body.querySelector('.menu-toggle');
    var scrollToTop = document.body.querySelector('.scroll-to-top');
    var scrollToTopVisible = false;

    function toggleMenuIcon() {
        var i = menuToggle.querySelector('i');
        if (!i) return;
        i.classList.toggle('bi-list');
        i.classList.toggle('bi-x-lg');
    }
    if (sidebarWrapper && menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle('active');
            menuToggle.classList.toggle('active');
            toggleMenuIcon();
        });
        Array.prototype.forEach.call(sidebarWrapper.querySelectorAll('.js-scroll-trigger'), function (a) {
            a.addEventListener('click', function () {
                if (!sidebarWrapper.classList.contains('active')) return;
                sidebarWrapper.classList.remove('active');
                menuToggle.classList.remove('active');
                toggleMenuIcon();
            });
        });
    }
    if (scrollToTop) {
        document.addEventListener('scroll', function () {
            if (document.documentElement.scrollTop > 100) {
                if (!scrollToTopVisible) { fadeIn(scrollToTop); scrollToTopVisible = true; }
            } else if (scrollToTopVisible) { fadeOut(scrollToTop); scrollToTopVisible = false; }
        });
    }
    function fadeOut(el) {
        el.style.opacity = 1;
        (function fade() {
            if ((el.style.opacity -= .1) < 0) { el.style.display = 'none'; } else { requestAnimationFrame(fade); }
        })();
    }
    function fadeIn(el) {
        el.style.opacity = 0; el.style.display = 'block';
        (function fade() {
            var v = parseFloat(el.style.opacity);
            if (!((v += .1) > 1)) { el.style.opacity = v; requestAnimationFrame(fade); }
        })();
    }
});
