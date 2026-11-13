document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btn-reservasi');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const target = document.querySelector(btn.dataset.target);
        if (!target) return;

        // slide halus ke section "Pilih Restoran Anda"
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // animasi kecil pada kartu setelah slide (CSS .pulse dari langkah 3)
        target.classList.remove('pulse');
        void target.offsetWidth; // paksa reflow agar animasi bisa diulang
        target.classList.add('pulse');
    });
});

document.addEventListener('DOMContentLoaded', () => {
        /* ===== Menu hamburger (HP) ===== */
    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');
    if (navToggle && mainNav) {
        const setNav = (open) => {
            mainNav.classList.toggle('hidden', !open);
            mainNav.classList.toggle('flex', open);
            navToggle.setAttribute('aria-expanded', String(open));
        };
        navToggle.addEventListener('click', () => {
            setNav(navToggle.getAttribute('aria-expanded') !== 'true');
        });
        // tutup menu setelah salah satu link diklik
        mainNav.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setNav(false)));
    }
        /* ===== Dropdown bahasa ===== */
    const langToggle = document.getElementById('lang-toggle');
    const langMenu = document.getElementById('lang-menu');
    if (langToggle && langMenu) {
        const setLang = (open) => {
            langMenu.classList.toggle('hidden', !open);
            langToggle.setAttribute('aria-expanded', String(open));
        };
        langToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            setLang(langToggle.getAttribute('aria-expanded') !== 'true');
        });
        document.addEventListener('click', (e) => {
            if (!langMenu.contains(e.target)) setLang(false);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') setLang(false);
        });
    }
    /* ===== Tombol "Reservasi Sekarang" -> slide ke outlet ===== */
    const btn = document.getElementById('btn-reservasi');
    if (btn) {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.target);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            target.classList.remove('pulse');
            void target.offsetWidth;
            target.classList.add('pulse');
        });
    }

    /* ===== Slider hero ===== */
    const hero  = document.getElementById('hero');
    const track = hero ? hero.querySelector('.hero-track') : null;
    const dotsBox = hero ? hero.querySelector('.hero-dots') : null;
    if (!track) return;

    const count = track.children.length;
    if (count < 2) return;

    // duplikat slide pertama di akhir supaya putaran dari terakhir ke pertama mulus
    track.appendChild(track.children[0].cloneNode(true));
        // Gambar slide ke-2 dst. dimuat satu per satu setelah halaman selesai dimuat
    function loadLazySlides() {
        const pending = Array.from(track.querySelectorAll('[data-bg]'));
        const next = () => {
            const el = pending.shift();
            if (!el) return;
            const img = new Image();
            img.onload = img.onerror = () => {
                el.style.backgroundImage = "url('" + el.dataset.bg + "')";
                next();
            };
            img.src = el.dataset.bg;
        };
        next();
    }
    if (document.readyState === 'complete') loadLazySlides();
    else window.addEventListener('load', loadLazySlides);

    const interval = parseInt(hero.dataset.interval, 10) || 5000;
    let index = 0;
    let timer = null;

    // titik penanda
    const dots = [];
    for (let n = 0; n < count; n++) {
        const d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Gambar ' + (n + 1));
        d.addEventListener('click', () => { go(n); restart(); });
        dotsBox.appendChild(d);
        dots.push(d);
    }

    function updateDots() {
        dots.forEach((d, n) => d.classList.toggle('active', n === index % count));
    }

    function go(n) {
        index = n;
        track.style.transition = 'transform .9s ease';
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        updateDots();
    }

    // setelah sampai slide duplikat, loncat diam-diam ke slide pertama
    track.addEventListener('transitionend', () => {
        if (index === count) {
            track.style.transition = 'none';
            index = 0;
            track.style.transform = 'translateX(0)';
            updateDots();
        }
    });

    function next() { go(index + 1); }
    function start() { timer = setInterval(next, interval); }
    function stop()  { clearInterval(timer); }
    function restart() { stop(); start(); }

    updateDots();

    // pengguna yang mematikan animasi: slider tidak jalan otomatis
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    start();
    hero.addEventListener('mouseenter', stop);   // berhenti saat kursor di hero
    hero.addEventListener('mouseleave', start);
});