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

document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.getElementById('event-carousel');
    if (!carousel) return;

    const track = carousel.querySelector('.ev-track');
    const prevBtn = carousel.querySelector('[data-ev-prev]');
    const nextBtn = carousel.querySelector('[data-ev-next]');
    const dotsBox = carousel.querySelector('.ev-dots');
    const cards = Array.from(carousel.querySelectorAll('.ev-card'));
    if (!track || !cards.length) return;

    const gapPx = 20;
    let dots = [];
    let rafId = null;

    function metrics() {
        const cardWidth = cards[0].getBoundingClientRect().width || 300;
        const step = cardWidth + gapPx;
        const visibleCount = Math.max(1, Math.round((track.clientWidth + gapPx) / step));
        const maxStep = Math.max(0, cards.length - visibleCount);
        const canScroll = track.scrollWidth > track.clientWidth + 4;
        return { step, maxStep, canScroll };
    }

    function buildDots(maxStep, canScroll) {
        if (!dotsBox) return;
        const count = canScroll ? maxStep + 1 : 0;
        if (dots.length === count) return;
        dotsBox.innerHTML = '';
        dots = [];
        if (count <= 1) {
            dotsBox.hidden = true;
            return;
        }
        for (let i = 0; i < count; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Slide event ' + (i + 1));
            btn.addEventListener('click', () => {
                const { step } = metrics();
                const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                const targetLeft = i === count - 1 ? maxScroll : Math.min(maxScroll, i * step);
                track.scrollTo({ left: targetLeft, behavior: 'smooth' });
            });
            dotsBox.appendChild(btn);
            dots.push(btn);
        }
        dotsBox.hidden = false;
    }

    function syncControls() {
        const { step, maxStep, canScroll } = metrics();
        track.classList.toggle('ev-centered', !canScroll);
        if (prevBtn) prevBtn.hidden = !canScroll;
        if (nextBtn) nextBtn.hidden = !canScroll;

        buildDots(maxStep, canScroll);

        const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
        if (prevBtn) prevBtn.disabled = track.scrollLeft <= 4;
        if (nextBtn) nextBtn.disabled = track.scrollLeft >= maxScroll - 4;

        if (dots.length > 0) {
            const active = track.scrollLeft >= maxScroll - 8
                ? dots.length - 1
                : Math.min(dots.length - 1, Math.max(0, Math.round(track.scrollLeft / step)));
            dots.forEach((dot, idx) => dot.classList.toggle('active', idx === active));
        }
    }

    prevBtn?.addEventListener('click', () => {
        const { step } = metrics();
        track.scrollBy({ left: -step, behavior: 'smooth' });
    });

    nextBtn?.addEventListener('click', () => {
        const { step } = metrics();
        track.scrollBy({ left: step, behavior: 'smooth' });
    });

    track.addEventListener('scroll', () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(syncControls);
    }, { passive: true });

    // Dukungan geser pakai mouse di desktop tanpa mengganggu klik tautan
    let dragging = false;
    let dragged = false;
    let startX = 0;
    let startScroll = 0;

    track.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse' || e.button !== 0) return;
        if (e.target.closest('a, button')) return;
        dragging = true;
        dragged = false;
        startX = e.clientX;
        startScroll = track.scrollLeft;
        track.classList.add('ev-dragging');
    });

    window.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        const dx = e.clientX - startX;
        if (Math.abs(dx) > 6) dragged = true;
        track.scrollLeft = startScroll - dx;
    });

    const stopDrag = () => {
        if (!dragging) return;
        dragging = false;
        track.classList.remove('ev-dragging');
    };
    window.addEventListener('pointerup', stopDrag);
    window.addEventListener('pointercancel', stopDrag);

    track.addEventListener('click', (e) => {
        if (dragged) {
            e.preventDefault();
            dragged = false;
        }
    }, true);

    window.addEventListener('resize', syncControls);
    syncControls();
});