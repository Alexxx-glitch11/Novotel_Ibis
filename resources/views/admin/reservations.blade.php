@extends('layouts.admin')

@section('title', 'Reservasi')

@section('content')
@php
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $statusMeta = fn (?string $s) => match ($s) {
        'confirmed' => ['Terkonfirmasi', 'is-green'],
        'partially_arrived' => ['Sebagian hadir', 'is-amber'],
        'checked_in' => ['Sudah check-in', 'is-green'],
        'completed' => ['Selesai', 'is-green'],
        'pending_payment' => ['Menunggu pembayaran', 'is-amber'],
        'cancelled' => ['Dibatalkan', 'is-red'],
        'no_show' => ['Tidak hadir', 'is-red'],
        'expired' => ['Kedaluwarsa', 'is-neutral'],
        default => [ucfirst(str_replace('_', ' ', (string) $s)), 'is-neutral'],
    };
    $payMeta = fn (?string $s) => match ($s) {
        'paid' => ['Lunas (Paid)', 'is-green'],
        'pending_verification' => ['Menunggu verifikasi Finance', 'is-amber'],
        'rejected' => ['Bukti ditolak', 'is-red'],
        'unpaid' => ['Belum dibayar', 'is-neutral'],
        default => ['Belum ada pembayaran', 'is-neutral'],
    };
    $listQuery = fn (array $extra = []) => array_filter(array_merge(request()->except('page', 'lookup'), $extra), fn ($v) => $v !== null && $v !== '');
@endphp

<style>
    .ar-pill{display:inline-flex;align-items:center;padding:3px 11px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
    .ar-pill.is-green{background:#e3f5eb;color:#147a4f}.ar-pill.is-amber{background:#fff2db;color:#9e5c14}
    .ar-pill.is-red{background:#fff0ef;color:#ab3333}.ar-pill.is-neutral{background:#f1f4fa;color:#6e7a8f}
    .ar-bar{padding:18px 22px;margin-bottom:16px}
    .ar-bar-grid{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    .ar-input,.ar-select{min-height:40px;border:1px solid #e0e5f0;border-radius:9px;background:#f6f8fc;padding:7px 12px;font-size:13px;color:#212b3b;outline:none}
    .ar-input:focus,.ar-select:focus{border-color:#21265d;background:#fff}
    .ar-search-box{position:relative;flex:1 1 260px;min-width:210px}
    .ar-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:#64748b;pointer-events:none}
    .ar-search-box .ar-input{width:100%;padding-left:38px}
    .ar-eyebrow{margin:0 0 6px;font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#6e7a8f}
    .ar-camera{margin-top:14px;border-radius:14px;overflow:hidden;background:#0f172a;color:#fff;max-width:420px}
    .ar-camera video{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;background:#020617}
    .ar-camera-bar{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:9px 12px;font-size:12px;background:#111827}
    .ar-list{padding:18px 22px 14px;margin-bottom:16px}
    .ar-row-selected td{background:#f5f7ff}
    .ar-detail-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 22px;margin-bottom:16px}
    .ar-detail-head h2{margin:2px 0 0;font-size:22px}
    .ar-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
    .ar-box{padding:18px 22px}
    .ar-box h3{margin:0 0 12px;font-size:16px}
    .ar-dl{display:grid;grid-template-columns:140px minmax(0,1fr);gap:9px 12px;margin:0;font-size:13px}
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
    @media(max-width:1100px){.ar-grid{grid-template-columns:1fr}.ar-dl{grid-template-columns:120px minmax(0,1fr)}}
</style>

<header class="admin-page-header">
    <h1>Reservasi</h1>
    <p>Satu halaman untuk mencari reservasi, memindai QR, dan melihat detail meja, menu/event, total harga, serta pendukung lainnya.</p>
</header>

<div class="admin-metrics">
    @foreach ([['R', 'Total reservasi', $stats['total'], 'Sesuai filter aktif'], ['M', 'Menunggu pembayaran', $stats['waiting'], 'Belum diverifikasi Finance'], ['T', 'Terkonfirmasi', $stats['confirmed'], 'Paid, check-in, atau selesai'], ['B', 'Batal / tidak hadir', $stats['cancelled'], 'Dibatalkan, no-show, kedaluwarsa']] as [$icon, $label, $value, $note])
        <article class="admin-card admin-metric"><span class="admin-metric-icon" translate="no">{{ $icon }}</span><div><div class="admin-muted">{{ $label }}</div><strong>{{ number_format($value) }}</strong><small>{{ $note }}</small></div></article>
    @endforeach
</div>

{{-- Pindai QR / cari kode + filter --}}
<section class="admin-card ar-bar">
    <p class="ar-eyebrow">Pindai QR atau kode reservasi</p>
    <form method="GET" action="{{ route('admin.reservations.index') }}" id="ar-lookup-form" class="ar-bar-grid">
        @if ($outletId)<input type="hidden" name="outlet" value="{{ $outletId }}">@endif
        <input type="text" name="lookup" id="ar-lookup" class="ar-input" style="flex:1 1 320px" placeholder="Tempel token QR atau ketik kode reservasi (mis. RRMS-NOVFE-2011-7K9M4X)" autocomplete="off" required>
        <button type="submit" class="admin-button admin-primary">Buka detail</button>
        <button type="button" id="ar-scan-btn" class="admin-button">Pindai QR ▦</button>
        <button type="button" id="ar-qr-file-btn" class="admin-button">Foto QR</button>
        <input type="file" id="ar-qr-file" accept="image/*" hidden>
    </form>
    <div id="ar-camera" class="ar-camera" hidden>
        <video id="ar-video" playsinline muted autoplay></video>
        <div class="ar-camera-bar"><span id="ar-camera-status">Arahkan kamera ke QR reservasi…</span><button type="button" id="ar-scan-stop" class="admin-button" style="min-height:28px;padding:3px 10px">Stop</button></div>
    </div>

    <form method="GET" action="{{ route('admin.reservations.index') }}" class="ar-bar-grid" style="margin-top:16px;padding-top:14px;border-top:1px solid #e0e5f0">
        @if ($outletId)<input type="hidden" name="outlet" value="{{ $outletId }}">@endif
        <div class="ar-search-box">
            <svg class="ar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" class="ar-input" placeholder="Cari kode, nama, telepon, atau email tamu" aria-label="Cari kode, nama, telepon, atau email tamu">
        </div>
        <input type="date" name="date" value="{{ $date }}" class="ar-input" aria-label="Tanggal reservasi">
        <select name="status" class="ar-select" aria-label="Filter status">
            <option value="all">Semua status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $statusMeta($s)[0] }}</option>
            @endforeach
        </select>
        <button type="submit" class="admin-button admin-primary">Terapkan</button>
        @if ($search !== '' || $date !== '' || $status !== 'all')
            <a href="{{ route('admin.reservations.index', array_filter(['outlet' => $outletId])) }}" class="admin-text-link">Reset</a>
        @endif
    </form>
</section>

{{-- Daftar reservasi --}}
<section class="admin-card ar-list">
    <div class="admin-section-heading">
        <div><h2>Daftar reservasi</h2><p>{{ $reservations->total() }} reservasi • diurutkan dari terbaru • klik <strong>Detail</strong> untuk membuka rincian lengkap.</p></div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table" style="min-width:860px">
            <thead><tr><th>KODE / TAMU</th><th>JADWAL</th><th>OUTLET &amp; MEJA</th><th>TAMU</th><th>STATUS</th><th>PEMBAYARAN</th><th>TOTAL</th><th></th></tr></thead>
            <tbody>
            @forelse ($reservations as $row)
                @php
                    [$stLabel, $stTone] = $statusMeta($row->reservation_status);
                    [$pyLabel, $pyTone] = $payMeta($row->latestPayment?->payment_status);
                @endphp
                <tr>
                    <td><strong translate="no">{{ $row->customer_name }}</strong><div class="ar-muted" translate="no">{{ $row->reservation_code }}</div></td>
                    <td class="admin-nowrap">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M Y') }}<div class="ar-muted">{{ substr((string) $row->start_time, 0, 5) }}–{{ substr((string) $row->end_time, 0, 5) }}</div></td>
                    <td>{{ $row->outlet?->name ?? '—' }}<div class="ar-muted">{{ $row->tables->isNotEmpty() ? 'Meja ' . $row->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ') : 'Tanpa meja' }}</div></td>
                    <td>{{ $row->guest_count }} orang</td>
                    <td><span class="ar-pill {{ $stTone }}">{{ $stLabel }}</span></td>
                    <td><span class="ar-pill {{ $pyTone }}">{{ $pyLabel }}</span></td>
                    <td class="admin-nowrap"><strong>{{ $rp($row->grand_total) }}</strong></td>
                    <td><a class="admin-text-link" href="{{ route('admin.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}">Detail →</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="admin-empty">Belum ada reservasi yang cocok dengan filter ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $reservations->onEachSide(1)->links() }}</div>
</section>

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
