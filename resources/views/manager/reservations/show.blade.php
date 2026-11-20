@extends('layouts.manager')

@section('title', __('manager.res_detail_title'))
@section('active-outlet', $reservation->outlet?->name ?? __('manager.all_outlets'))

@section('content')
    @php
        $isEn = app()->getLocale() === 'en';
        $payment = $reservation->latestPayment;
        $tone = match ($reservation->reservation_status) {
            'confirmed', 'completed' => 'bg-emerald-50 text-emerald-700',
            'partially_arrived', 'checked_in' => 'bg-indigo-50 text-indigo-700',
            'pending_payment' => 'bg-amber-50 text-amber-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-600',
        };
        $arrived = (int) ($reservation->checkIns->max('cumulative_guest_count') ?? 0);
        $tableSummary = $reservation->tables->isNotEmpty()
            ? $reservation->tables->pluck('table_code')->implode(', ')
            : '—';
    @endphp

    <div class="mx-auto max-w-6xl space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('manager.reservations.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-[#21265d] shadow-xs transition hover:bg-slate-50">
                ← {{ __('manager.back_to_reservations') }}
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="mr-scan-btn" class="inline-flex items-center gap-2 rounded-lg bg-[#201f5c] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#151442]">
                    {{ $isEn ? 'Scan QR ▦' : 'Pindai QR ▦' }}
                </button>
                <button type="button" id="mr-qr-file-btn" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-[#21265d] transition hover:bg-slate-50">
                    {{ $isEn ? 'QR Photo' : 'Foto QR' }}
                </button>
            </div>
        </div>

        {{-- QR / Kode Lookup Card --}}
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ $isEn ? 'Scan QR or open another reservation code' : 'Pindai QR atau cari kode reservasi lain' }}
            </p>
            <form method="GET" action="{{ route('manager.reservations.index') }}" id="mr-lookup-form" class="flex flex-wrap items-center gap-2.5">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    <input type="text" name="lookup" id="mr-lookup" autocomplete="off" required
                           placeholder="{{ $isEn ? 'Paste QR token or reservation code (e.g. RRMS-NOVFE-...)' : 'Tempel token QR atau ketik kode reservasi (mis. RRMS-NOVFE-...)' }}"
                           class="w-full rounded-lg border border-slate-200 bg-[#f6f8fc] py-2 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#201f5c] focus:bg-white focus:outline-none">
                </div>
                <button type="submit" class="rounded-lg bg-[#201f5c] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#151442]">
                    {{ $isEn ? 'Open detail' : 'Buka detail' }}
                </button>
                <input type="file" id="mr-qr-file" accept="image/*" hidden>
            </form>
            <div id="mr-camera" class="mt-3 max-w-[420px] overflow-hidden rounded-xl bg-slate-900 text-white" hidden>
                <video id="mr-video" class="block aspect-[4/3] w-full bg-slate-950 object-cover" playsinline muted autoplay></video>
                <div class="flex items-center justify-between gap-2 bg-slate-900 px-3 py-2 text-xs">
                    <span id="mr-camera-status">{{ $isEn ? 'Point camera at reservation QR…' : 'Arahkan kamera ke QR reservasi…' }}</span>
                    <button type="button" id="mr-scan-stop" class="rounded-md border border-white/20 bg-white/10 px-2.5 py-1 text-xs font-semibold text-white">Stop</button>
                </div>
            </div>
        </section>

        {{-- Hero Card with 4-stat summary strip --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400" translate="no">{{ $reservation->reservation_code }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-[#151442] md:text-3xl" translate="no">{{ $reservation->customer_name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $reservation->outlet?->name }} · {{ $reservation->reservation_date->locale(app()->getLocale())->translatedFormat('d F Y') }} · {{ substr((string) $reservation->start_time, 0, 5) }} WIB</p>
                </div>
                <span class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $tone }}">{{ $reservationStatus }}</span>
            </div>
            <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-[11px] font-medium text-slate-400">{{ $isEn ? 'Schedule' : 'Jadwal' }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ $reservation->reservation_date->locale(app()->getLocale())->translatedFormat('d M Y') }} · {{ substr((string) $reservation->start_time, 0, 5) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-400">{{ $isEn ? 'Tables & Attendance' : 'Meja & Kehadiran' }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ $tableSummary }} · {{ $arrived }}/{{ $reservation->guest_count }} {{ __('manager.res_guest_unit') }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-400">{{ __('manager.res_col_payment') }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ $paymentStatus }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-400">{{ __('manager.res_grand_total') }}</p>
                    <p class="mt-0.5 text-sm font-bold text-[#151442]">Rp{{ number_format((float) $reservation->grand_total, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_guest_details') }}</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_guest_name') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_name }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_phone') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_phone }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-400">{{ __('manager.res_email') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_email }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_visit_details') }}</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.outlet_label') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->outlet?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_guest_count') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->guest_count }} {{ __('manager.res_guest_unit') }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_tables') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->tables->map(fn ($table) => $table->table_code . ($table->area ? ' · ' . $table->area->name : ''))->join(', ') ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_duration') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }}</dd></div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-400">{{ __('manager.res_notes') }}</dt>
                        @if ($reservation->notes)
                            <dd class="mt-1 rounded-lg border border-indigo-200 bg-indigo-50/50 px-3 py-2 text-xs font-medium text-[#21265d]">{{ $reservation->notes }}</dd>
                        @else
                            <dd class="mt-1 text-slate-400">—</dd>
                        @endif
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_payment_details') }}</h2>
                @if ($payment)
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-400">{{ __('manager.res_col_payment') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $paymentStatus }}</dd></div>
                        <div><dt class="text-xs text-slate-400">{{ __('manager.res_amount') }}</dt><dd class="mt-1 font-semibold text-[#151442]">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</dd></div>
                        @if ($payment->rejection_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-400">{{ __('manager.res_payment_note') }}</dt><dd class="mt-1 text-slate-700">{{ $payment->rejection_reason }}</dd></div>@endif
                    </dl>
                @else
                    <p class="mt-4 text-sm text-slate-400">{{ __('manager.res_no_payment') }}</p>
                @endif
                <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_subtotal') }}</dt><dd class="mt-1 text-slate-700">Rp{{ number_format((float) $reservation->subtotal, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_grand_total') }}</dt><dd class="mt-1 font-semibold text-[#151442]">Rp{{ number_format((float) $reservation->grand_total, 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_menu_details') }}</h2>
                @if ($reservation->items->isNotEmpty())
                    <ul class="mt-4 divide-y divide-slate-100 text-sm">
                        @foreach ($reservation->items as $item)
                            <li class="flex justify-between gap-3 py-2"><span class="text-slate-700">{{ $item->quantity }} × {{ $item->item_name_snapshot }}</span><span class="shrink-0 font-medium text-slate-700">Rp{{ number_format((float) $item->line_total, 0, ',', '.') }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-400">{{ __('manager.res_no_menu') }}</p>
                @endif
            </section>
        </div>

        @if ($cancellation)
            <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-[#151442]">{{ __('manager.res_cancellation_details') }}</h2>
                        <p class="mt-2 text-sm text-slate-700">{{ $cancellation->reason }}</p>
                        @if ($cancellation->decision_note)<p class="mt-1 text-xs text-slate-500">{{ __('manager.res_decision_note') }}: {{ $cancellation->decision_note }}</p>@endif
                    </div>
                    @if ($cancellation->status === 'pending')
                        <a href="{{ route('manager.cancellations.index', ['selected' => $cancellation->cancellation_requests_id]) }}" class="rounded-lg bg-[#201f5c] px-4 py-2 text-sm font-semibold text-white">{{ __('manager.review_cancellation') }}</a>
                    @else
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ __('manager.cancel_status_' . $cancellation->status) }}</span>
                    @endif
                </div>
            </section>
        @endif

        @if ($reservation->checkIns->isNotEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_checkin_history') }}</h2>
                <div class="mt-3 divide-y divide-slate-100 text-sm">
                    @foreach ($reservation->checkIns->sortByDesc('checked_in_at') as $checkIn)
                        <p class="flex flex-wrap justify-between gap-2 py-2 text-slate-600"><span>{{ $checkIn->checked_in_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }} · {{ $checkIn->checkedInBy?->name ?? __('manager.activity_system') }}</span><span>{{ $checkIn->guest_count_arrived }} {{ __('manager.res_guest_unit') }}</span></p>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
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
        const video = el('mr-video');
        const box = el('mr-camera');
        if (video) video.srcObject = null;
        if (box) box.hidden = true;
    };

    const submitCode = (value) => {
        const input = el('mr-lookup');
        const form = el('mr-lookup-form');
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
        if (e.target.closest?.('#mr-scan-stop')) {
            stop();
            return;
        }
        if (e.target.closest?.('#mr-qr-file-btn')) {
            el('mr-qr-file')?.click();
            return;
        }
        if (!e.target.closest?.('#mr-scan-btn')) return;

        const box = el('mr-camera');
        const video = el('mr-video');
        const status = el('mr-camera-status');
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
        if (!fileInput || fileInput.id !== 'mr-qr-file') return;
        const file = fileInput.files?.[0];
        if (!file) return;
        const box = el('mr-camera');
        const status = el('mr-camera-status');
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
