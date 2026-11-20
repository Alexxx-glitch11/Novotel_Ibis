{{-- Modal Pratinjau Bukti Pembayaran dengan Zoom In/Out & Rotasi Bebas --}}
<div id="finance-proof-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/75 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="proof-modal-title">
    <div class="flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e0e5f0] px-5 py-3.5">
            <div class="min-w-0">
                <h3 id="proof-modal-title" class="truncate text-sm font-semibold text-[#212b3b]">Bukti transfer</h3>
                <p id="proof-modal-subtitle" class="text-[11px] text-[#6e7a8f]"></p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button" id="proof-zoom-out" class="grid size-8 place-items-center rounded-lg border border-[#e0e5f0] bg-white text-sm font-semibold text-[#212b3b] hover:bg-[#f6f8fc]" title="Perkecil">−</button>
                <span id="proof-zoom-label" class="min-w-[48px] text-center text-xs font-semibold text-[#212b3b]">100%</span>
                <button type="button" id="proof-zoom-in" class="grid size-8 place-items-center rounded-lg border border-[#e0e5f0] bg-white text-sm font-semibold text-[#212b3b] hover:bg-[#f6f8fc]" title="Perbesar">+</button>

                <span class="mx-1 h-5 w-px bg-[#e0e5f0]"></span>

                <button type="button" id="proof-rotate-left" class="grid size-8 place-items-center rounded-lg border border-[#e0e5f0] bg-white text-sm font-semibold text-[#212b3b] hover:bg-[#f6f8fc]" title="Putar kiri 90°">↺</button>
                <span id="proof-rotate-label" class="min-w-[36px] text-center text-xs font-semibold text-[#212b3b]">0°</span>
                <button type="button" id="proof-rotate-right" class="grid size-8 place-items-center rounded-lg border border-[#e0e5f0] bg-white text-sm font-semibold text-[#212b3b] hover:bg-[#f6f8fc]" title="Putar kanan 90°">↻</button>

                <button type="button" id="proof-zoom-reset" class="ml-1 rounded-lg border border-[#e0e5f0] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#21265d] hover:bg-[#e8edff]">Reset</button>
                <button type="button" id="proof-modal-close" class="ml-1 grid size-8 place-items-center rounded-lg bg-[#f6f8fc] text-sm font-semibold text-[#6e7a8f] hover:bg-[#ffe8e8] hover:text-[#ab3333]" aria-label="Tutup pratinjau">✕</button>
            </div>
        </div>

        <div id="proof-modal-viewport" class="relative flex flex-1 overflow-auto bg-[#f1f4fa] p-6 select-none">
            <div id="proof-modal-stage" class="relative m-auto flex shrink-0 items-center justify-center">
                <img id="proof-modal-img" src="" alt="Bukti transfer" draggable="false" class="hidden max-w-none cursor-grab rounded-lg shadow-md" style="transform-origin: center center;">
                <iframe id="proof-modal-frame" src="" title="Pratinjau dokumen bukti" class="hidden h-[75vh] w-[85vw] max-w-4xl rounded-lg border border-[#e0e5f0] bg-white"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('finance-proof-modal');
    if (!modal) return;

    const titleEl = document.getElementById('proof-modal-title');
    const subtitleEl = document.getElementById('proof-modal-subtitle');
    const stageEl = document.getElementById('proof-modal-stage');
    const imgEl = document.getElementById('proof-modal-img');
    const frameEl = document.getElementById('proof-modal-frame');
    const zoomLabel = document.getElementById('proof-zoom-label');
    const rotateLabel = document.getElementById('proof-rotate-label');
    const viewport = document.getElementById('proof-modal-viewport');

    let zoom = 1;
    let rotation = 0;
    let isImage = true;
    let baseW = 0;
    let baseH = 0;

    const applyTransform = () => {
        zoomLabel.textContent = Math.round(zoom * 100) + '%';
        const normalizedAngle = ((rotation % 360) + 360) % 360;
        rotateLabel.textContent = normalizedAngle + '°';

        if (isImage) {
            if (baseW > 0 && baseH > 0) {
                const w = Math.round(baseW * zoom);
                const h = Math.round(baseH * zoom);
                const rad = (rotation * Math.PI) / 180;
                const boxW = Math.ceil(Math.abs(w * Math.cos(rad)) + Math.abs(h * Math.sin(rad)));
                const boxH = Math.ceil(Math.abs(w * Math.sin(rad)) + Math.abs(h * Math.cos(rad)));

                stageEl.style.width = boxW + 'px';
                stageEl.style.height = boxH + 'px';
                imgEl.style.width = w + 'px';
                imgEl.style.height = h + 'px';
            }
            imgEl.style.transform = `rotate(${rotation}deg)`;
        } else {
            stageEl.style.width = '';
            stageEl.style.height = '';
            frameEl.style.transform = `scale(${zoom}) rotate(${rotation}deg)`;
            frameEl.style.transformOrigin = 'center center';
        }
    };

    const openModal = (url, name, meta, imageFlag) => {
        zoom = 1;
        rotation = 0;
        isImage = imageFlag;
        baseW = 0;
        baseH = 0;
        stageEl.style.width = '';
        stageEl.style.height = '';
        titleEl.textContent = name || 'Bukti transfer';
        subtitleEl.textContent = meta || '';

        if (isImage) {
            frameEl.classList.add('hidden');
            frameEl.src = '';
            imgEl.classList.remove('hidden');
            imgEl.style.width = '';
            imgEl.style.height = '';
            imgEl.style.maxHeight = '72vh';
            imgEl.style.maxWidth = '78vw';
            imgEl.style.transform = 'rotate(0deg)';
            imgEl.onload = () => {
                baseW = imgEl.clientWidth || Math.min(imgEl.naturalWidth, 720);
                baseH = imgEl.clientHeight || Math.round((baseW * imgEl.naturalHeight) / Math.max(1, imgEl.naturalWidth));
                imgEl.style.maxHeight = 'none';
                imgEl.style.maxWidth = 'none';
                applyTransform();
            };
            imgEl.src = url;
        } else {
            imgEl.classList.add('hidden');
            imgEl.src = '';
            frameEl.classList.remove('hidden');
            frameEl.style.transform = '';
            frameEl.src = url;
        }

        applyTransform();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        imgEl.src = '';
        frameEl.src = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest?.('[data-proof-preview]');
        if (!btn) return;
        e.preventDefault();
        openModal(
            btn.dataset.proofUrl,
            btn.dataset.proofName,
            btn.dataset.proofMeta,
            btn.dataset.proofIsImage === '1'
        );
    });

    document.getElementById('proof-zoom-in')?.addEventListener('click', () => {
        zoom = Math.min(5, +(zoom + 0.25).toFixed(2));
        applyTransform();
    });

    document.getElementById('proof-zoom-out')?.addEventListener('click', () => {
        zoom = Math.max(0.25, +(zoom - 0.25).toFixed(2));
        applyTransform();
    });

    document.getElementById('proof-rotate-left')?.addEventListener('click', () => {
        rotation -= 90;
        applyTransform();
    });

    document.getElementById('proof-rotate-right')?.addEventListener('click', () => {
        rotation += 90;
        applyTransform();
    });

    document.getElementById('proof-zoom-reset')?.addEventListener('click', () => {
        zoom = 1;
        rotation = 0;
        applyTransform();
    });

    let lastWheelAt = 0;
    viewport?.addEventListener('wheel', (e) => {
        if (!modal.classList.contains('flex')) return;
        e.preventDefault();
        const now = Date.now();
        if (now - lastWheelAt < 20 || Math.abs(e.deltaY) < 2) return;
        lastWheelAt = now;
        const delta = e.deltaY < 0 ? 0.15 : -0.15;
        zoom = Math.min(5, Math.max(0.25, +(zoom + delta).toFixed(2)));
        applyTransform();
    }, { passive: false });

    // Drag-to-pan inside viewport
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let scrollStartX = 0;
    let scrollStartY = 0;

    viewport?.addEventListener('mousedown', (e) => {
        if (e.button !== 0 || e.target === frameEl) return;
        isDragging = true;
        startX = e.pageX;
        startY = e.pageY;
        scrollStartX = viewport.scrollLeft;
        scrollStartY = viewport.scrollTop;
        imgEl.classList.replace('cursor-grab', 'cursor-grabbing');
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        viewport.scrollLeft = scrollStartX - (e.pageX - startX);
        viewport.scrollTop = scrollStartY - (e.pageY - startY);
    });

    window.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        imgEl.classList.replace('cursor-grabbing', 'cursor-grab');
    });

    document.getElementById('proof-modal-close')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('flex')) closeModal();
    });
})();
</script>
