<div id="cashier-qr-modal" class="cashier-modal-backdrop" hidden role="dialog" aria-modal="true" aria-labelledby="qr-modal-title">
    <div class="cashier-modal-card">
        <div class="cashier-modal-head">
            <div>
                <h2 id="qr-modal-title" class="cashier-modal-title">Pemindai QR Reservasi</h2>
                <p class="cashier-modal-sub">Arahkan kamera ke QR tiket tamu, potret gambar QR, atau masukkan kode reservasi.</p>
            </div>
            <button type="button" id="qr-modal-close" class="cashier-modal-close" aria-label="Tutup pemindai">×</button>
        </div>

        <div class="cashier-qr-modal-actions">
            <button type="button" id="qr-modal-start-cam" class="cashier-btn cashier-btn-primary cashier-btn-lg" style="flex:1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                </svg>
                <span>Kamera Live</span>
            </button>
            <button type="button" id="qr-modal-image-btn" class="cashier-btn cashier-btn-soft cashier-btn-lg" style="flex:1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                </svg>
                <span>Foto / Gambar QR</span>
            </button>
            <input id="qr-modal-file-input" type="file" accept="image/*" hidden>
        </div>

        <div id="qr-modal-camera-box" class="qr-camera-box" hidden>
            <div class="qr-camera-stage">
                <video id="qr-modal-video" playsinline muted autoplay></video>
                <div class="qr-frame-guide" aria-hidden="true"></div>
            </div>
            <div class="qr-camera-bar">
                <span id="qr-modal-camera-status">Arahkan kamera ke QR reservasi tamu...</span>
                <div class="qr-camera-controls">
                    <button type="button" id="qr-modal-switch-cam" class="qr-cam-ctrl-btn">Ganti Kamera</button>
                    <button type="button" id="qr-modal-stop-cam" class="qr-cam-ctrl-btn">Stop</button>
                </div>
            </div>
        </div>

        <div id="qr-modal-feedback" class="qr-feedback is-info" hidden role="status" aria-live="polite"></div>

        <form id="cashier-modal-scan-form" method="POST" action="{{ route('cashier.checkin.scan') }}" class="cashier-modal-form">
            @csrf
            <label for="qr-modal-token-input" class="cashier-field-label">Token QR atau Kode Reservasi</label>
            <div class="qr-manual-row">
                <input id="qr-modal-token-input" name="qr_token" maxlength="100" required autocomplete="off"
                       placeholder="Token 64 karakter atau kode (mis. RRMS-NOVFE-...)" class="cashier-input cashier-input-mono">
                <button type="button" id="qr-modal-paste" class="cashier-btn cashier-btn-outline">Tempel</button>
            </div>
            <button type="submit" class="cashier-btn cashier-btn-primary cashier-btn-lg" style="width:100%;margin-top:10px;">
                Periksa &amp; Buka Detail Check-In
            </button>
        </form>
    </div>
</div>
