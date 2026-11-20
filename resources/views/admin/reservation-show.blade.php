@extends('layouts.admin')

@section('title', 'Detail reservasi · ' . $selected->reservation_code)

@section('content')
@php
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $statusMeta = match ($selected->reservation_status) {
        'confirmed' => ['Terkonfirmasi', 'is-green'],
        'partially_arrived' => ['Sebagian hadir', 'is-amber'],
        'checked_in' => ['Sudah check-in', 'is-green'],
        'completed' => ['Selesai', 'is-green'],
        'pending_payment' => ['Menunggu pembayaran', 'is-amber'],
        'cancelled' => ['Dibatalkan', 'is-red'],
        'no_show' => ['Tidak hadir', 'is-red'],
        'expired' => ['Kedaluwarsa', 'is-neutral'],
        default => [ucfirst(str_replace('_', ' ', (string) $selected->reservation_status)), 'is-neutral'],
    };
    $payment = $selected->latestPayment;
    $payMeta = match ($payment?->payment_status) {
        'paid' => ['Lunas (Paid)', 'is-green'],
        'pending_verification' => ['Menunggu verifikasi Finance', 'is-amber'],
        'rejected' => ['Bukti ditolak', 'is-red'],
        'unpaid' => ['Belum dibayar', 'is-neutral'],
        default => ['Belum ada pembayaran', 'is-neutral'],
    };
    $menuTotal = (float) $selected->items->sum('line_total');
    $arrived = (int) ($selected->checkIns->max('cumulative_guest_count') ?? 0);
    $qr = $selected->qrToken;
@endphp

<style>
    .ar-pill{display:inline-flex;align-items:center;padding:3px 11px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
    .ar-pill.is-green{background:#e3f5eb;color:#147a4f}.ar-pill.is-amber{background:#fff2db;color:#9e5c14}
    .ar-pill.is-red{background:#fff0ef;color:#ab3333}.ar-pill.is-neutral{background:#f1f4fa;color:#6e7a8f}
    .ar-eyebrow{margin:0 0 6px;font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#6e7a8f}
    .ar-topbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
    .ar-topbar-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
    .ar-scan-bar{padding:16px 20px;margin-bottom:16px}
    .ar-bar-grid{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    .ar-search-box{position:relative;flex:1 1 280px;min-width:220px}
    .ar-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:#64748b;pointer-events:none}
    .ar-input{min-height:40px;width:100%;border:1px solid #e0e5f0;border-radius:9px;background:#f6f8fc;padding:7px 12px 7px 38px;font-size:13px;color:#212b3b;outline:none}
    .ar-input:focus{border-color:#21265d;background:#fff}
    .ar-camera{margin-top:12px;border-radius:14px;overflow:hidden;background:#0f172a;color:#fff;max-width:420px}
    .ar-camera video{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;background:#020617}
    .ar-camera-bar{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:9px 12px;font-size:12px;background:#111827}
    .ar-detail-head{padding:20px 22px;margin-bottom:16px}
    .ar-detail-head-top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:12px}
    .ar-detail-head h1{margin:2px 0 0;font-size:24px;line-height:1.25}
    .ar-summary-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;width:100%;margin-top:16px;padding-top:14px;border-top:1px solid #e0e5f0}
    .ar-summary-item span{display:block;font-size:11px;color:#6e7a8f;font-weight:500}
    .ar-summary-item strong{display:block;margin-top:2px;font-size:13.5px;color:#212b3b;font-weight:600}
    .ar-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
    .ar-box{padding:18px 22px}
    .ar-box h3{margin:0 0 12px;font-size:16px}
    .ar-dl{display:grid;grid-template-columns:150px minmax(0,1fr);gap:10px 12px;margin:0;font-size:13px}
    .ar-dl dt{color:#6e7a8f}.ar-dl dd{margin:0;font-weight:600;color:#212b3b;word-break:break-word}
    .ar-lines{list-style:none;margin:0;padding:0;font-size:13px}
    .ar-lines li{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #eef1f7}
    .ar-lines li:last-child{border-bottom:0}
    .ar-total{display:flex;justify-content:space-between;align-items:baseline;margin-top:10px;padding-top:12px;border-top:2px solid #e0e5f0;font-size:15px;font-weight:700}
    .ar-total span:last-child{font-size:20px;color:#21265d}
    .ar-qr{display:flex;flex-wrap:wrap;gap:18px;align-items:center}
    .ar-qr img{width:168px;height:168px;border:1px solid #e0e5f0;border-radius:12px;padding:6px;background:#fff}
    .ar-event{padding:12px 14px;border-radius:12px;background:#f5f7ff;border:1px solid #dfe5fb;margin-bottom:12px;font-size:13px}
    .ar-log{display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px dashed #e0e5f0;font-size:12.5px}
    .ar-muted{color:#6e7a8f;font-size:12.5px}
    .ar-wide{grid-column:1 / -1}
    @media(max-width:1100px){.ar-grid{grid-template-columns:1fr}.ar-dl{grid-template-columns:125px minmax(0,1fr)}.ar-summary-strip{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="ar-topbar">
    <a href="{{ route('admin.reservations.index', array_filter(['outlet' => $outletId])) }}" class="admin-button" style="min-height:36px;padding:6px 12px;font-size:13px;">
        ← Kembali ke daftar reservasi
    </a>
    <div class="ar-topbar-actions">
        <button type="button" id="ar-scan-btn" class="admin-button admin-primary">Pindai QR ▦</button>
        <button type="button" id="ar-qr-file-btn" class="admin-button">Foto QR</button>
    </div>
</div>

{{-- Pindai QR / Cari Kode Langsung di Halaman Detail --}}
<section class="admin-card ar-scan-bar">
    <p class="ar-eyebrow">Pindai QR atau kode reservasi lain</p>
    <form method="GET" action="{{ route('admin.reservations.index') }}" id="ar-lookup-form" class="ar-bar-grid">
        @if ($outletId)<input type="hidden" name="outlet" value="{{ $outletId }}">@endif
        <div class="ar-search-box">
            <svg class="ar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="text" name="lookup" id="ar-lookup" class="ar-input" placeholder="Tempel token QR atau ketik kode reservasi (mis. RRMS-NOVFE-...)" autocomplete="off" required>
        </div>
        <button type="submit" class="admin-button admin-primary">Buka detail</button>
        <input type="file" id="ar-qr-file" accept="image/*" hidden>
    </form>
    <div id="ar-camera" class="ar-camera" hidden>
        <video id="ar-video" playsinline muted autoplay></video>
        <div class="ar-camera-bar">
            <span id="ar-camera-status">Arahkan kamera ke QR reservasi…</span>
            <button type="button" id="ar-scan-stop" class="admin-button" style="min-height:28px;padding:3px 10px">Stop</button>
        </div>
    </div>
</section>

<section class="admin-card ar-detail-head">
    <div class="ar-detail-head-top">
        <div>
            <p class="ar-eyebrow" style="margin:0">Detail reservasi</p>
            <h1 translate="no">{{ $selected->customer_name }}</h1>
            <div class="ar-muted"><span translate="no">{{ $selected->reservation_code }}</span> • {{ $selected->outlet?->name }} • dibuat {{ $selected->created_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }}</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <span class="ar-pill {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
            <span class="ar-pill {{ $payMeta[1] }}">{{ $payMeta[0] }}</span>
        </div>
    </div>

    <div class="ar-summary-strip">
        <div class="ar-summary-item">
            <span>Jadwal kedatangan</span>
            <strong>{{ $selected->reservation_date?->locale(app()->getLocale())->translatedFormat('d M Y') }} • {{ substr((string) $selected->start_time, 0, 5) }} WIB</strong>
        </div>
        <div class="ar-summary-item">
            <span>Meja &amp; kehadiran</span>
            <strong>{{ $selected->tables->isNotEmpty() ? 'Meja ' . $selected->tables->pluck('table_code')->implode(', ') : 'Tanpa meja' }} • {{ $arrived }}/{{ $selected->guest_count }} tamu</strong>
        </div>
        <div class="ar-summary-item">
            <span>Pembayaran</span>
            <strong>{{ $payMeta[0] }}</strong>
        </div>
        <div class="ar-summary-item">
            <span>Total tagihan</span>
            <strong style="color:#21265d;font-size:15px;">{{ $rp($selected->grand_total) }}</strong>
        </div>
    </div>
</section>

<div class="ar-grid">
    <section class="admin-card ar-box">
        <h3>Tamu &amp; catatan pelanggan</h3>
        <dl class="ar-dl">
            <dt>Nama</dt><dd translate="no">{{ $selected->customer_name }}</dd>
            <dt>Telepon</dt><dd translate="no">{{ $selected->customer_phone ?: '—' }}</dd>
            <dt>Email</dt><dd translate="no">{{ $selected->customer_email ?: '—' }}</dd>
            <dt>Catatan pelanggan</dt>
            <dd>
                @if ($selected->notes)
                    <div class="rounded-lg border border-indigo-200 bg-[#f5f7ff] px-3 py-2 text-xs font-medium text-[#21265d]">
                        {{ $selected->notes }}
                    </div>
                @else
                    <span class="ar-muted">—</span>
                @endif
            </dd>
        </dl>
    </section>

    <section class="admin-card ar-box">
        <h3>Jadwal &amp; meja</h3>
        <dl class="ar-dl">
            <dt>Outlet</dt><dd>{{ $selected->outlet?->name ?? '—' }}</dd>
            <dt>Tanggal</dt><dd>{{ $selected->reservation_date?->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</dd>
            <dt>Jam</dt><dd>{{ substr((string) $selected->start_time, 0, 5) }}–{{ substr((string) $selected->end_time, 0, 5) }} WIB</dd>
            <dt>Jumlah tamu</dt><dd>{{ $selected->guest_count }} orang ({{ $arrived }} sudah hadir)</dd>
            <dt>Meja &amp; area</dt>
            <dd>
                @forelse ($selected->tables as $t)
                    Meja {{ $t->table_code }} • {{ $t->area?->name ?? 'Area' }} • kapasitas {{ $t->capacity }}@if (! $loop->last)<br>@endif
                @empty
                    Tanpa meja khusus
                @endforelse
            </dd>
        </dl>
    </section>

    <section class="admin-card ar-box">
        <h3>Menu &amp; event</h3>
        @if ($selected->eventContent)
            <div class="ar-event">
                <strong>{{ $selected->eventContent->content_type === 'event' ? 'Event' : 'Promosi' }}: {{ $selected->eventContent->title }}</strong>
                <div class="ar-muted">
                    @if ((float) $selected->eventContent->price_per_pax > 0)
                        {{ $rp($selected->eventContent->price_per_pax) }}/pax × {{ $selected->guest_count }} tamu = {{ $rp($eventTotal) }}
                    @else
                        Tanpa biaya per pax
                    @endif
                </div>
            </div>
        @endif
        @if ($selected->items->isNotEmpty())
            <ul class="ar-lines">
                @foreach ($selected->items as $item)
                    <li><span>{{ $item->quantity }} × {{ $item->item_name_snapshot }}<span class="ar-muted"> • {{ $rp($item->unit_price_snapshot) }}</span></span><strong>{{ $rp($item->line_total) }}</strong></li>
                @endforeach
            </ul>
        @elseif (! $selected->eventContent)
            <p class="ar-muted" style="margin:0">Belum ada menu atau event yang dipilih pada reservasi ini.</p>
        @endif
    </section>

    <section class="admin-card ar-box">
        <h3>Rincian harga &amp; pembayaran</h3>
        <ul class="ar-lines">
            <li><span>Menu</span><span>{{ $rp($menuTotal) }}</span></li>
            @if ($eventTotal > 0)<li><span>Event / paket</span><span>{{ $rp($eventTotal) }}</span></li>@endif
            <li><span>Subtotal</span><span>{{ $rp($selected->subtotal) }}</span></li>
            <li><span>Biaya layanan ({{ rtrim(rtrim(number_format((float) $selected->service_rate_percent, 2, ',', ''), '0'), ',') }}%)</span><span>{{ $rp($selected->service_amount) }}</span></li>
            <li><span>Pajak ({{ rtrim(rtrim(number_format((float) $selected->tax_rate_percent, 2, ',', ''), '0'), ',') }}%)</span><span>{{ $rp($selected->tax_amount) }}</span></li>
        </ul>
        <div class="ar-total"><span>Total</span><span>{{ $rp($selected->grand_total) }}</span></div>
        <dl class="ar-dl" style="margin-top:14px">
            <dt>Status bayar</dt><dd><span class="ar-pill {{ $payMeta[1] }}">{{ $payMeta[0] }}</span></dd>
            <dt>Metode</dt><dd>{{ $payment ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : '—' }}@if ($payment?->bank_destination) • {{ $payment->bank_destination }}@endif</dd>
            <dt>Diverifikasi oleh</dt><dd>{{ $payment?->verifiedBy?->name ?? '—' }}@if ($payment?->verified_at) <span class="ar-muted">({{ $payment->verified_at->locale(app()->getLocale())->translatedFormat('d M Y H.i') }})</span>@endif</dd>
            @if ($payment?->rejection_reason)<dt>Catatan Finance</dt><dd>{{ $payment->rejection_reason }}</dd>@endif
        </dl>
    </section>

    <section class="admin-card ar-box">
        <h3>QR reservasi</h3>
        @if ($qrDataUri && $qr)
            <div class="ar-qr">
                <img src="{{ $qrDataUri }}" alt="QR reservasi {{ $selected->reservation_code }}">
                <dl class="ar-dl" style="grid-template-columns:110px minmax(0,1fr)">
                    <dt>Status QR</dt><dd><span class="ar-pill {{ $qr->is_active ? 'is-green' : 'is-neutral' }}">{{ $qr->is_active ? 'Aktif' : 'Belum aktif / nonaktif' }}</span></dd>
                    <dt>Berlaku dari</dt><dd>{{ $qr->valid_from?->locale(app()->getLocale())->translatedFormat('d M Y H.i') ?? '—' }}</dd>
                    <dt>Berlaku sampai</dt><dd>{{ $qr->valid_until?->locale(app()->getLocale())->translatedFormat('d M Y H.i') ?? '—' }}</dd>
                    <dt>Token</dt><dd><code>{{ substr($qr->token, 0, 8) }}…{{ substr($qr->token, -4) }}</code></dd>
                </dl>
            </div>
            <p class="ar-muted" style="margin:12px 0 0">QR aktif setelah pembayaran disetujui Finance dan dipindai Kasir saat tamu tiba.</p>
        @else
            <p class="ar-muted" style="margin:0">Reservasi ini belum memiliki QR.</p>
        @endif
    </section>

    <section class="admin-card ar-box">
        <h3>Kedatangan &amp; pembatalan</h3>
        <p class="ar-eyebrow">Riwayat kedatangan</p>
        @forelse ($selected->checkIns->sortByDesc('checked_in_at') as $ci)
            <div class="ar-log"><span>{{ $ci->checked_in_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }} • +{{ $ci->guest_count_arrived }} tamu @if ($ci->notes)<span class="ar-muted">({{ $ci->notes }})</span>@endif</span><span class="ar-muted" translate="no">Kasir: {{ $ci->checkedInBy?->name ?? '—' }}</span></div>
        @empty
            <p class="ar-muted" style="margin:0 0 12px">Belum ada kedatangan tercatat.</p>
        @endforelse

        <p class="ar-eyebrow" style="margin-top:14px">Permintaan pembatalan</p>
        @forelse ($selected->cancellationRequests->sortByDesc('cancellation_requests_id') as $cr)
            @php [$crLabel, $crTone] = match ($cr->status) { 'approved' => ['Disetujui', 'is-green'], 'rejected' => ['Ditolak', 'is-red'], default => ['Menunggu Manager', 'is-amber'] }; @endphp
            <div style="padding:8px 0;border-bottom:1px dashed #e0e5f0;font-size:12.5px">
                <div style="display:flex;justify-content:space-between;gap:8px"><strong>Diajukan {{ $cr->requested_by === 'cashier' ? 'Kasir' : 'Pelanggan' }}</strong><span class="ar-pill {{ $crTone }}">{{ $crLabel }}</span></div>
                <div>{{ $cr->reason }}</div>
                <div class="ar-muted">Manager: {{ $cr->reviewedBy?->name ?? 'belum diputuskan' }}@if ($cr->decision_note) • {{ $cr->decision_note }}@endif</div>
            </div>
        @empty
            <p class="ar-muted" style="margin:0">Tidak ada permintaan pembatalan.</p>
        @endforelse
    </section>

    <section class="admin-card ar-box ar-wide">
        <h3>Jejak audit reservasi</h3>
        @forelse ($auditLogs as $log)
            <div class="ar-log"><span><strong>{{ $log->user?->name ?? 'Pelanggan / Sistem' }}</strong> — {{ str_replace('_', ' ', $log->action) }}</span><span class="ar-muted">{{ $log->created_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }}</span></div>
        @empty
            <p class="ar-muted" style="margin:0">Belum ada jejak audit untuk reservasi ini.</p>
        @endforelse
    </section>
</div>

<p class="admin-footnote">Halaman ini hanya untuk melihat. Verifikasi pembayaran dilakukan Finance, check-in oleh Kasir, dan keputusan pembatalan oleh Manager.</p>
@endsection

@push('scripts')
<script src="{{ asset('js/jsQR.min.js') }}"></script>
<script>
(() => {
    const el = (id) => document.getElementById(id);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    let stream = null, timer = null;

    const stop = () => {
        clearInterval(timer); timer = null;
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        const video = el('ar-video');
        const box = el('ar-camera');
        if (video) video.srcObject = null;
        if (box) box.hidden = true;
    };

    const submitCode = (value) => {
        const input = el('ar-lookup');
        const form = el('ar-lookup-form');
        if (!value || !input || !form) return;
        input.value = String(value).trim();
        stop();
        form.submit();
    };

    const decodeImageSource = async (source, width, height) => {
        if (!width || !height) return null;
        if ('BarcodeDetector' in window) {
            try {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                const codes = await detector.detect(source);
                if (codes.length && codes[0].rawValue) return codes[0].rawValue;
            } catch (e) { /* fallback ke jsQR */ }
        }
        if (typeof window.jsQR === 'function' && ctx) {
            const maxDim = 900;
            const scale = Math.min(1, maxDim / Math.max(width, height));
            canvas.width = Math.max(1, Math.round(width * scale));
            canvas.height = Math.max(1, Math.round(height * scale));
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.imageSmoothingEnabled = false;
            ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = window.jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'attemptBoth' });
            if (code && code.data) return code.data;
        }
        return null;
    };

    document.addEventListener('click', async (e) => {
        if (e.target.closest?.('#ar-scan-stop')) {
            stop();
            return;
        }
        if (e.target.closest?.('#ar-qr-file-btn')) {
            el('ar-qr-file')?.click();
            return;
        }
        if (!e.target.closest?.('#ar-scan-btn')) return;

        const box = el('ar-camera');
        const video = el('ar-video');
        const status = el('ar-camera-status');
        if (!box || !video || !status) return;

        box.hidden = false;
        if (!navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Kamera live tidak didukung browser ini. Gunakan Foto QR atau masukkan kode manual.';
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream; await video.play();
        } catch (err) {
            status.textContent = 'Kamera tidak dapat diakses (' + (err.message || err.name) + '). Gunakan Foto QR atau masukkan kode manual.';
            return;
        }
        if (!('BarcodeDetector' in window) && typeof window.jsQR !== 'function') {
            status.textContent = 'Pemindai otomatis tidak tersedia di browser ini. Gunakan Foto QR atau kode manual.';
            return;
        }
        status.textContent = 'Arahkan kamera ke QR reservasi…';
        timer = setInterval(async () => {
            if (!video.isConnected || video.readyState < 2) return;
            const decoded = await decodeImageSource(video, video.videoWidth, video.videoHeight);
            if (decoded) submitCode(decoded);
        }, 350);
    });

    document.addEventListener('change', async (e) => {
        const fileInput = e.target;
        if (!fileInput || fileInput.id !== 'ar-qr-file') return;
        const file = fileInput.files?.[0];
        if (!file) return;
        const box = el('ar-camera');
        const status = el('ar-camera-status');
        if (box) box.hidden = false;
        if (status) status.textContent = 'Membaca gambar QR…';
        try {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = async () => {
                const decoded = await decodeImageSource(img, img.naturalWidth, img.naturalHeight);
                URL.revokeObjectURL(url);
                if (decoded) {
                    submitCode(decoded);
                } else if (status) {
                    status.textContent = 'QR tidak terbaca pada foto ini. Pastikan QR jelas atau masukkan kode manual.';
                }
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                if (status) status.textContent = 'File gambar tidak dapat dibaca.';
            };
            img.src = url;
        } catch (err) {
            if (status) status.textContent = 'Gagal memproses gambar QR.';
        } finally {
            fileInput.value = '';
        }
    });
    window.addEventListener('pagehide', stop);
})();
</script>
@endpush
