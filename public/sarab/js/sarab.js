/*! Tema sarab phpVB — port vanilla dari Sarab v1.0.0 js/main.js (Bestwpware via ThemeWagon, MIT).
 *  Dibuang: jQuery + magnific-popup (video → tautan biasa), overlay pencarian, keranjang/qty/rating, pengiriman form
 *  palsu, hitung mundur palsu (→ nyata, data-sampai), ikon Font Awesome (→ bootstrap-icons). Setiap handler dijaga:
 *  section tema opsional, elemennya bisa tidak ada. */
(function () {
    'use strict';
    function $(id) { return document.getElementById(id); }
    function all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    if (window.AOS) { AOS.init({ duration: 680, once: true, offset: 55 }); }

    /* NAVBAR SCROLL + TAUTAN AKTIF + TOMBOL KE ATAS */
    var nav = $('nav'), btt = $('btt');
    window.addEventListener('scroll', function () {
        if (nav) { nav.classList.toggle('scrolled', window.scrollY > 60); }
        if (btt) { btt.classList.toggle('show', window.scrollY > 300); }
        all('section[id]').forEach(function (sec) {
            var top = sec.offsetTop - 110, bot = top + sec.offsetHeight;
            if (window.scrollY >= top && window.scrollY < bot) {
                all('.nav-link').forEach(function (l) { l.classList.remove('active'); });
                var lnk = document.querySelector('.nav-link[href="#' + sec.id + '"]');
                if (lnk) { lnk.classList.add('active'); }
            }
        });
    });
    if (btt) { btt.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); }); }

    /* GULIR HALUS + tutup navbar ponsel */
    all('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var href = this.getAttribute('href'), t;
            if (href === '#') { e.preventDefault(); return; }
            try { t = document.querySelector(href); } catch (err) { return; }
            if (!t) { return; }
            e.preventDefault();
            var navCollapse = $('navmenu');
            if (navCollapse && navCollapse.classList.contains('show')) {
                var bs = window.bootstrap && bootstrap.Collapse.getInstance(navCollapse);
                if (bs) { bs.hide(); } else { navCollapse.classList.remove('show'); }
            }
            setTimeout(function () { window.scrollTo({ top: t.offsetTop - 78, behavior: 'smooth' }); }, 50);
        });
    });

    /* FILTER PRODUK (tombol filter + kartu kategori) */
    function filterMenu(cat) {
        all('.filtbtn').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-f') === cat); });
        all('.catcard').forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-filter') === cat); });
        all('.mwrap').forEach(function (w) {
            var c = w.getAttribute('data-c');
            if (cat === 'all' || c === cat) {
                w.classList.remove('gone');
                w.style.opacity = '0'; w.style.transform = 'translateY(16px)';
                setTimeout(function () {
                    w.style.transition = 'opacity .38s,transform .38s'; w.style.opacity = '1'; w.style.transform = 'translateY(0)';
                }, 60);
            } else { w.classList.add('gone'); }
        });
    }
    all('.filtbtn').forEach(function (btn) { btn.addEventListener('click', function () { filterMenu(this.getAttribute('data-f')); }); });
    all('.catcard').forEach(function (card) {
        card.addEventListener('click', function () {
            var f = this.getAttribute('data-filter'), menu = $('menu');
            if (menu) { window.scrollTo({ top: menu.offsetTop - 80, behavior: 'smooth' }); }
            setTimeout(function () { filterMenu(f); }, 480);
        });
    });

    /* POPUP DETAIL PRODUK (tanpa keranjang) */
    var menuPop = $('menuPop');
    function openMenuPop(card) {
        if (!menuPop || !card) { return; }
        var img = card.getAttribute('data-img') || '', mpImg = $('mpImg'), mpPanel = $('mpPanel'), old = card.getAttribute('data-old');
        if (mpImg) { mpImg.setAttribute('src', img); mpImg.style.display = img ? '' : 'none'; }
        if (mpPanel) {
            mpPanel.style.display = img ? 'none' : 'flex';
            mpPanel.innerHTML = '';
            var ico = document.createElement('i'); ico.className = card.getAttribute('data-ikon') || 'bi-box-seam'; mpPanel.appendChild(ico);
        }
        $('mpCat').textContent = card.getAttribute('data-cat') || '';
        $('mpTitle').textContent = card.getAttribute('data-title') || '';
        $('mpDesc').textContent = card.getAttribute('data-desc') || '';
        $('mpPrice').textContent = card.getAttribute('data-price') || '';
        if (old) { var s = document.createElement('small'); s.textContent = ' ' + old; $('mpPrice').appendChild(s); }
        $('mpTags').innerHTML = '';
        (card.getAttribute('data-tags') || '').split(',').filter(Boolean).forEach(function (t) {
            var sp = document.createElement('span'); sp.className = 'mptag'; sp.textContent = t.trim(); $('mpTags').appendChild(sp);
        });
        menuPop.classList.add('open'); document.body.style.overflow = 'hidden';
    }
    function closeMenuPop() { if (menuPop) { menuPop.classList.remove('open'); document.body.style.overflow = ''; } }
    all('.mcard').forEach(function (card) { card.addEventListener('click', function () { openMenuPop(this); }); });
    all('.madd').forEach(function (btn) { btn.addEventListener('click', function (e) { e.stopPropagation(); openMenuPop(this.closest('.mcard')); }); });
    all('.mhrt').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var ico = this.querySelector('i'); if (!ico) { return; }
            ico.classList.toggle('bi-heart'); ico.classList.toggle('bi-heart-fill');
            this.style.color = ico.classList.contains('bi-heart-fill') ? 'var(--primary)' : '#ccc';
        });
    });
    if ($('mpClose')) { $('mpClose').addEventListener('click', closeMenuPop); }
    if (menuPop) { menuPop.addEventListener('click', function (e) { if (e.target === this) { closeMenuPop(); } }); }

    /* POPUP GALERI */
    var galPop = $('galPop'), galData = [], galIdx = 0;
    all('.gitem').forEach(function (item) {
        galData.push({ img: item.getAttribute('data-gimg') || '', title: item.getAttribute('data-gtitle') || '', desc: item.getAttribute('data-gdesc') || '' });
        item.addEventListener('click', function () { openGal(parseInt(this.getAttribute('data-gi'), 10)); });
    });
    function openGal(i) {
        if (!galPop || !galData.length) { return; }
        galIdx = i; var g = galData[i], gpImg = $('gpImg');
        if (gpImg) { gpImg.setAttribute('src', g.img); gpImg.style.display = g.img ? '' : 'none'; }
        $('gpTitle').textContent = g.title; $('gpDesc').textContent = g.desc;
        galPop.classList.add('open'); document.body.style.overflow = 'hidden';
    }
    function closeGal() { if (galPop) { galPop.classList.remove('open'); document.body.style.overflow = ''; } }
    if ($('gpClose')) { $('gpClose').addEventListener('click', closeGal); }
    if (galPop) { galPop.addEventListener('click', function (e) { if (e.target === this) { closeGal(); } }); }
    if ($('gpPrev')) { $('gpPrev').addEventListener('click', function () { openGal((galIdx - 1 + galData.length) % galData.length); }); }
    if ($('gpNext')) { $('gpNext').addEventListener('click', function () { openGal((galIdx + 1) % galData.length); }); }

    /* POPUP TEKNOLOGI (section#teknologi) — isi dari atribut data-* chip; rincian dipisah '|' */
    var techPop = $('techPop');
    function openTechPop(chip) {
        if (!techPop || !chip) { return; }
        var gambar = chip.getAttribute('data-tkgambar') || '', tkImg = $('tkIkon'), tkFb = $('tkIkonFb');
        if (tkImg) { tkImg.setAttribute('src', gambar); tkImg.style.display = gambar ? '' : 'none'; }
        if (tkFb) {
            tkFb.className = gambar ? '' : (chip.getAttribute('data-tkikon') || 'bi-hdd-network');
            tkFb.style.display = gambar ? 'none' : '';
        }
        if ($('tkTitle')) { $('tkTitle').textContent = chip.getAttribute('data-tknama') || ''; }
        if ($('tkHost')) { $('tkHost').textContent = chip.getAttribute('data-tkhost') || ''; }
        if ($('tkDesc')) { $('tkDesc').textContent = chip.getAttribute('data-tkdesc') || ''; }
        if ($('tkRinci')) {
            $('tkRinci').innerHTML = '';
            (chip.getAttribute('data-tkrinci') || '').split('|').filter(Boolean).forEach(function (r) {
                var sp = document.createElement('span'); sp.className = 'mptag'; sp.textContent = r.trim(); $('tkRinci').appendChild(sp);
            });
        }
        var tkLink = $('tkLink'), href = chip.getAttribute('data-tkurl') || '';
        if (tkLink) {
            tkLink.style.display = href ? '' : 'none';
            if (href) { tkLink.setAttribute('href', href); tkLink.textContent = chip.getAttribute('data-tklabel') || ''; }
        }
        techPop.classList.add('open'); document.body.style.overflow = 'hidden';
    }
    function closeTechPop() { if (techPop) { techPop.classList.remove('open'); document.body.style.overflow = ''; } }
    all('.techchip').forEach(function (chip) { chip.addEventListener('click', function () { openTechPop(this); }); });
    if ($('tkClose')) { $('tkClose').addEventListener('click', closeTechPop); }
    if (techPop) { techPop.addEventListener('click', function (e) { if (e.target === this) { closeTechPop(); } }); }

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeMenuPop(); closeGal(); closeTechPop(); } });

    /* TESTIMONI — Swiper hanya bila dimuat layout */
    if (window.Swiper && document.querySelector('.tesSwiper')) {
        new Swiper('.tesSwiper', {
            slidesPerView: 1, spaceBetween: 22, loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
        });
    }

    /* HITUNG MUNDUR PROMO — nyata, ke data-sampai (ISO 8601) */
    var cd = $('cdwrap');
    if (cd) {
        var sampai = new Date(cd.getAttribute('data-sampai')).getTime();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var tick = function () {
            var s = Math.max(0, Math.floor((sampai - Date.now()) / 1000));
            if ($('cdD')) { $('cdD').textContent = pad(Math.floor(s / 86400)); }
            if ($('cdH')) { $('cdH').textContent = pad(Math.floor(s % 86400 / 3600)); }
            if ($('cdM')) { $('cdM').textContent = pad(Math.floor(s % 3600 / 60)); }
            if ($('cdS')) { $('cdS').textContent = pad(s % 60); }
        };
        if (!isNaN(sampai)) { tick(); setInterval(tick, 1000); }
    }

    /* ANIMASI ANGKA STATISTIK HERO */
    var numAnimated = false;
    window.addEventListener('scroll', function () {
        var hero = $('hero');
        if (!numAnimated && hero && window.scrollY > hero.offsetHeight - 300) {
            numAnimated = true;
            all('.snum').forEach(function (el) {
                var txt = el.textContent, num = parseInt(txt, 10), suf = txt.replace(/[0-9]/g, '');
                if (isNaN(num)) { return; }
                var start = 0, step = Math.ceil(num / 55);
                var iv = setInterval(function () {
                    start += step; if (start >= num) { start = num; clearInterval(iv); }
                    el.textContent = start + suf;
                }, 1400 / 55);
            });
        }
    });
})();
