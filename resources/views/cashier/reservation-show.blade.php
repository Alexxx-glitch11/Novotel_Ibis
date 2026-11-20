@extends('layouts.cashier')

@section('title', 'Detail reservasi · ' . $reservation->reservation_code)

@section('content')
@php
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $statusMeta = match ($reservation->reservation_status) {
        'confirmed' => ['Terkonfirmasi', 'is-navy'],
        'partially_arrived' => ['Sebagian hadir', 'is-amber'],
        'checked_in' => ['Sudah check-in', 'is-green'],
        'completed' => ['Selesai', 'is-green'],
        'pending_payment' => ['Menunggu pembayaran', 'is-amber'],
        'cancelled' => ['Dibatalkan', 'is-red'],
        'no_show' => ['No-show', 'is-red'],
        'expired' => ['Kedaluwarsa', 'is-neutral'],
        default => [ucfirst(str_replace('_', ' ', (string) $reservation->reservation_status)), 'is-neutral'],
    };
    $payment = $reservation->latestPayment;
    $isPaid = $payment?->payment_status === 'paid';
    $payMeta = match ($payment?->payment_status) {
        'paid' => ['Lunas', 'is-green'],
        'pending_verification' => ['Menunggu verifikasi Finance', 'is-amber'],
        'rejected' => ['Bukti ditolak', 'is-red'],
        default => ['Belum dibayar', 'is-neutral'],
    };
    $arrivedCount = (int) ($reservation->checkIns->max('cumulative_guest_count') ?? 0);
    $remaining = max(0, (int) $reservation->guest_count - $arrivedCount);
    $pct = $reservation->guest_count > 0
        ? min(100, (int) round(($arrivedCount / $reservation->guest_count) * 100))
        : 0;
    $canCheckIn = $isPaid && in_array($reservation->reservation_status, ['confirmed', 'partially_arrived'], true) && $remaining > 0;
    $isCompleted = $reservation->reservation_status === 'completed';
    $isCheckedIn = $reservation->reservation_status === 'checked_in';
    $isClosed = in_array($reservation->reservation_status, ['cancelled', 'no_show', 'expired'], true);
    $tableLabel = $reservation->tables->isNotEmpty()
        ? 'Meja ' . $reservation->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
        : 'Tanpa kursi khusus';
    $menuTotal = (float) $reservation->items->sum('line_total');
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('cashier.reservations.index', array_filter(['outlet' => $outletId])) }}"
       class="cashier-btn cashier-btn-outline cashier-btn-sm" style="min-height:36px;padding:6px 12px;">
        ← Kembali ke daftar reservasi
    </a>
    <button type="button" data-open-qr-scanner class="cashier-btn cashier-btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M17 17h4v4h-4z"/><path d="M21 14v.01"/><path d="M14 21v.01"/>
        </svg>
        <span>Pindai QR</span>
    </button>
</div>

<div class="grid gap-5 lg:grid-cols-[minmax(0,1.22fr)_minmax(350px,0.92fr)] lg:items-start">
    {{-- Kolom Kiri: Informasi Reservasi & Tamu + Pesanan & Pembayaran --}}
    <div class="space-y-4 min-w-0">
        <section class="cashier-card cashier-card-pad">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="cashier-eyebrow" style="margin:0 0 4px;">
                        Reservasi • <span translate="no" style="color:var(--brand-navy);">{{ $reservation->reservation_code }}</span>
                    </p>
                    <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:700;color:var(--text-ink);" translate="no">
                        {{ $reservation->customer_name }}
                    </h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <span class="cashier-pill {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                    <span class="cashier-pill {{ $payMeta[1] }}">{{ $payMeta[0] }}</span>
                </div>
            </div>

            <div class="mt-4 grid gap-x-6 gap-y-3.5 border-t border-[#e0e5f0] pt-4 text-xs sm:grid-cols-2">
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Outlet</p>
                    <p class="mt-0.5 font-semibold text-[#212b3b]">{{ $reservation->outlet?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Jadwal kedatangan</p>
                    <p class="mt-0.5 font-semibold text-[#212b3b]">
                        {{ $reservation->reservation_date?->locale(app()->getLocale())->translatedFormat('l, d M Y') ?? '—' }}
                        • {{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }} WIB
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Meja &amp; area</p>
                    <p class="mt-0.5 font-semibold text-[#212b3b]">{{ $tableLabel }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Jumlah tamu</p>
                    <p class="mt-0.5 font-semibold text-[#212b3b]">{{ $reservation->guest_count }} orang ({{ $arrivedCount }} sudah hadir)</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Telepon</p>
                    <p class="mt-0.5 font-semibold text-[#212b3b]" translate="no">{{ $reservation->customer_phone ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-[#6e7a8f]">Email</p>
                    <p class="mt-0.5 break-all font-semibold text-[#212b3b]" translate="no">{{ $reservation->customer_email ?: '—' }}</p>
                </div>
            </div>

            <div class="mt-4 border-t border-[#e0e5f0] pt-3.5">
                <p class="text-[11px] font-medium text-[#6e7a8f]">Catatan pelanggan</p>
                @if ($reservation->notes)
                    <div class="mt-1.5 rounded-lg border border-indigo-200 bg-[#f5f7ff] px-3 py-2 text-xs font-medium text-[#21265d]">
                        {{ $reservation->notes }}
                    </div>
                @else
                    <p class="mt-1 text-xs text-[#6e7a8f]">Tidak ada catatan khusus.</p>
                @endif
            </div>
        </section>

        <section class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title">Pesanan &amp; rincian pembayaran</h2>

            @if ($reservation->eventContent)
                <div class="mt-3 rounded-xl border border-indigo-200 bg-[#f5f7ff] p-3 text-xs">
                    <strong class="text-[#21265d]">{{ $reservation->eventContent->content_type === 'event' ? 'Event' : 'Promosi' }}: {{ $reservation->eventContent->title }}</strong>
                    <div class="mt-0.5 text-slate-500">
                        @if ((float) $reservation->eventContent->price_per_pax > 0)
                            {{ $rp($reservation->eventContent->price_per_pax) }}/pax × {{ $reservation->guest_count }} tamu = {{ $rp($eventTotal) }}
                        @else
                            Tanpa biaya per pax
                        @endif
                    </div>
                </div>
            @endif

            @if ($reservation->items->isNotEmpty())
                <ul class="mt-3 divide-y divide-[#e0e5f0] text-xs">
                    @foreach ($reservation->items as $item)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span>{{ $item->quantity }} × {{ $item->item_name_snapshot }} <span style="color:var(--text-muted);">• {{ $rp($item->unit_price_snapshot) }}</span></span>
                            <strong>{{ $rp($item->line_total) }}</strong>
                        </li>
                    @endforeach
                </ul>
            @elseif (! $reservation->eventContent)
                <p class="cashier-card-sub mt-3">Belum ada menu atau event pada reservasi ini.</p>
            @endif

            <dl class="mt-3 space-y-2 border-t border-[#e0e5f0] pt-3 text-xs">
                <div class="flex justify-between gap-3"><dt style="color:var(--text-muted);">Menu</dt><dd>{{ $rp($menuTotal) }}</dd></div>
                @if ($eventTotal > 0)
                    <div class="flex justify-between gap-3"><dt style="color:var(--text-muted);">Event / paket</dt><dd>{{ $rp($eventTotal) }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt style="color:var(--text-muted);">Subtotal</dt><dd>{{ $rp($reservation->subtotal) }}</dd></div>
                <div class="flex justify-between gap-3"><dt style="color:var(--text-muted);">Biaya layanan ({{ rtrim(rtrim(number_format((float) $reservation->service_rate_percent, 2, ',', ''), '0'), ',') }}%)</dt><dd>{{ $rp($reservation->service_amount) }}</dd></div>
                <div class="flex justify-between gap-3"><dt style="color:var(--text-muted);">Pajak ({{ rtrim(rtrim(number_format((float) $reservation->tax_rate_percent, 2, ',', ''), '0'), ',') }}%)</dt><dd>{{ $rp($reservation->tax_amount) }}</dd></div>
                <div class="flex justify-between gap-3 border-t border-[#e0e5f0] pt-2.5 text-sm font-bold text-[#21265d]"><dt>Total tagihan</dt><dd>{{ $rp($reservation->grand_total) }}</dd></div>
            </dl>

            <div class="mt-3 border-t border-[#e0e5f0] pt-3 text-xs">
                <span style="color:var(--text-muted);">Diverifikasi Finance:</span>
                <strong>{{ $payment?->verifiedBy?->name ?? '—' }}</strong>
                @if ($payment?->verified_at)
                    <span style="color:var(--text-muted);">({{ $payment->verified_at->locale(app()->getLocale())->translatedFormat('d M Y H.i') }})</span>
                @endif
            </div>
        </section>
    </div>

    {{-- Kolom Kanan: Langkah & Catat Kedatangan Tamu + Pindai QR Lain --}}
    <div class="cashier-side-stack min-w-0">
        <section class="cashier-card cashier-card-pad">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="cashier-card-title">Catat kedatangan (Check-in)</h2>
                    <p class="cashier-card-sub mt-0.5">
                        @if ($remaining > 0 && ! $isClosed && ! $isCompleted)
                            Sisa {{ $remaining }} dari {{ $reservation->guest_count }} tamu belum hadir
                        @else
                            {{ $arrivedCount }} dari {{ $reservation->guest_count }} tamu hadir
                        @endif
                    </p>
                </div>
                <span class="cashier-pill {{ $arrivedCount >= $reservation->guest_count && $reservation->guest_count > 0 ? 'is-green' : ($arrivedCount > 0 ? 'is-amber' : 'is-navy') }}">
                    {{ $arrivedCount }} / {{ $reservation->guest_count }} hadir
                </span>
            </div>

            <div class="cashier-progress-track mt-3" style="height:8px;">
                <div class="cashier-progress-bar" style="width:{{ $pct }}%;"></div>
            </div>

            {{-- Panduan Langkah Mencatat Tamu Hadir --}}
            <div class="mt-4 rounded-xl border border-[#e0e5f0] bg-[#f8fafc] p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-[#6e7a8f]">Langkah mencatat tamu hadir</p>
                <ol class="mt-2.5 space-y-2.5 text-xs">
                    <li class="flex items-start gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-bold {{ $isPaid ? 'bg-[#e3f5eb] text-[#147a4f]' : 'bg-[#fff2db] text-[#9e5c14]' }}">
                            {{ $isPaid ? '✓' : '1' }}
                        </span>
                        <div>
                            <p class="font-semibold text-[#212b3b]">Verifikasi identitas &amp; status lunas</p>
                            <p class="text-[11px] text-[#6e7a8f]">Pastikan nama tamu, kode, dan status pembayaran <strong>{{ $payMeta[0] }}</strong>.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-bold {{ ($isCheckedIn || $isCompleted) ? 'bg-[#e3f5eb] text-[#147a4f]' : ($canCheckIn ? 'bg-[#e8edff] text-[#21265d]' : 'bg-slate-200 text-slate-500') }}">
                            {{ ($isCheckedIn || $isCompleted) ? '✓' : '2' }}
                        </span>
                        <div>
                            <p class="font-semibold text-[#212b3b]">Masukkan jumlah tamu yang tiba</p>
                            <p class="text-[11px] text-[#6e7a8f]">Pilih tombol cepat atau atur angka <strong>− / +</strong> sesuai tamu yang sudah datang (bisa bertahap).</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-bold {{ $isCompleted ? 'bg-[#e3f5eb] text-[#147a4f]' : ($isCheckedIn ? 'bg-[#e8edff] text-[#21265d]' : 'bg-slate-200 text-slate-500') }}">
                            {{ $isCompleted ? '✓' : '3' }}
                        </span>
                        <div>
                            <p class="font-semibold text-[#212b3b]">Simpan check-in &amp; selesaikan kunjungan</p>
                            <p class="text-[11px] text-[#6e7a8f]">Tekan <strong>Simpan Check-in</strong>, lalu tandai selesai setelah tamu selesai bersantap.</p>
                        </div>
                    </li>
                </ol>
            </div>

            {{-- Form Catat Tamu atau Status Penutup --}}
            @if ($canCheckIn)
                <form method="POST" action="{{ route('cashier.checkin.record', $reservation) }}" class="mt-4">
                    @csrf
                    <label for="checkin-guest-input" class="block text-xs font-semibold text-[#212b3b]">
                        Jumlah tamu yang baru tiba (sisa {{ $remaining }} orang)
                    </label>

                    <div class="cashier-quick-picks mt-2">
                        <button type="button" data-checkin-qty="1" class="cashier-quick-chip">+1 tamu</button>
                        @if ($remaining >= 2)
                            <button type="button" data-checkin-qty="2" class="cashier-quick-chip">+2 tamu</button>
                        @endif
                        <button type="button" data-checkin-qty="{{ $remaining }}" class="cashier-quick-chip">Semua ({{ $remaining }} tamu)</button>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-2" data-stepper>
                        <button type="button" data-step-dec aria-label="Kurangi" class="cashier-step-btn is-minus" style="width:42px;height:40px;">−</button>
                        <input id="checkin-guest-input" type="number" name="guest_count_arrived" min="1" max="{{ $remaining }}" value="{{ $remaining }}" required class="cashier-input cashier-step-input" style="min-height:40px !important;">
                        <button type="button" data-step-inc aria-label="Tambah" class="cashier-step-btn is-plus" style="width:42px;height:40px;">+</button>
                        <button type="submit" class="cashier-btn cashier-btn-primary" style="flex:1;min-height:40px;">Simpan Check-in</button>
                    </div>
                    <input type="text" name="notes" maxlength="255" placeholder="Catatan opsional (mis. meja digabung)" class="cashier-input mt-2.5">
                </form>
            @elseif ($reservation->reservation_status === 'cancelled')
                <div class="mt-4 rounded-xl border border-red-200 bg-[#fff0ef] px-3.5 py-2.5 text-xs font-medium text-[#ab3333]">
                    Reservasi ini sudah dibatalkan. Pencatatan check-in ditutup.
                </div>
            @elseif ($reservation->reservation_status === 'no_show')
                <div class="mt-4 rounded-xl border border-red-200 bg-[#fff0ef] px-3.5 py-2.5 text-xs font-medium text-[#ab3333]">
                    Reservasi ditandai tidak hadir (No-show).
                </div>
            @elseif ($isCompleted)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-[#e3f5eb] px-3.5 py-2.5 text-xs font-medium text-[#147a4f]">
                    Kunjungan selesai ({{ $arrivedCount }}/{{ $reservation->guest_count }} tamu tercatat hadir).
                </div>
            @elseif ($isCheckedIn)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-[#e3f5eb] px-3.5 py-2.5 text-xs font-medium text-[#147a4f]">
                    Seluruh tamu ({{ $arrivedCount }}/{{ $reservation->guest_count }}) sudah check-in.
                </div>
            @elseif (! $isPaid)
                <div class="mt-4 rounded-xl border border-amber-200 bg-[#fff2db] px-3.5 py-2.5 text-xs font-medium text-[#9e5c14]">
                    Pembayaran belum diverifikasi Lunas oleh Finance. Check-in otomatis terbuka setelah pembayaran disetujui.
                </div>
            @endif

            @if (in_array($reservation->reservation_status, ['checked_in', 'partially_arrived'], true) && $arrivedCount > 0)
                <form method="POST" action="{{ route('cashier.checkin.complete', $reservation) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="cashier-btn cashier-btn-primary" style="width:100%;">
                        Tandai kunjungan selesai ✓
                    </button>
                </form>
            @endif

            @if ($reservation->reservation_status === 'confirmed')
                <form method="POST" action="{{ route('cashier.checkin.no-show', $reservation) }}" class="mt-2.5"
                      onsubmit="return confirm('Tandai {{ $reservation->reservation_code }} sebagai No-show (tidak hadir)?')">
                    @csrf
                    <button type="submit" class="cashier-btn cashier-btn-outline" style="width:100%;color:var(--red-ink);border-color:#fecaca;">
                        Tandai No-show
                    </button>
                </form>
            @endif

            @if (! in_array($reservation->reservation_status, ['cancelled', 'completed', 'no_show'], true))
                <div class="mt-4 border-t border-[#e0e5f0] pt-3">
                    <a href="{{ route('cashier.cancellations.index', array_filter(['reservation' => $reservation->reservations_id, 'outlet' => $outletId])) }}"
                       style="font-size:12px;font-weight:600;color:var(--brand-navy);text-decoration:none;">
                        Tamu batal hadir? Ajukan pembatalan ke Manager →
                    </a>
                </div>
            @endif
        </section>

        <section class="cashier-card cashier-card-pad" style="padding:14px 18px;">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div style="width:38px;height:38px;border-radius:10px;background:var(--brand-soft);color:var(--brand-navy);display:grid;place-items:center;font-size:13px;font-weight:700;flex-shrink:0;">
                        QR
                    </div>
                    <div class="min-w-0">
                        <h2 class="cashier-card-title" style="font-size:14.5px;">Pindai QR reservasi lain</h2>
                        <p class="cashier-card-sub" style="margin-top:1px;">Buka langsung detail tamu berikutnya.</p>
                    </div>
                </div>
                <button type="button" data-open-qr-scanner class="cashier-btn cashier-btn-soft cashier-btn-sm shrink-0">
                    Pindai
                </button>
            </div>
        </section>
    </div>
</div>
@endsection
