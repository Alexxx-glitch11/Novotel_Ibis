@extends('layouts.cashier')

@section('title', 'Ajukan permintaan pembatalan')

@section('content')
<div class="cashier-page-head">
    <h1>Ajukan permintaan pembatalan</h1>
    <p>Catat alasan tamu; Manager meninjau dan memutuskan permintaan.</p>
</div>

{{-- Search Card (Figma 373:122) --}}
<section class="cashier-card cashier-card-pad mt-4">
    <p class="cashier-eyebrow" style="margin:0 0 6px;">Cari reservasi</p>
    <form method="GET" action="{{ route('cashier.cancellations.index') }}" class="flex flex-wrap items-center gap-3">
        @if ($outletId)
            <input type="hidden" name="outlet" value="{{ $outletId }}">
        @endif
        <div class="cashier-search-box" style="flex: 1 1 320px;">
            <svg class="cashier-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="text" name="q" value="{{ $searchInput }}" placeholder="Cari kode reservasi atau nama tamu..."
                   aria-label="Cari kode reservasi atau nama tamu" class="cashier-input">
        </div>
        <button type="submit" class="cashier-btn cashier-btn-primary" style="min-height:40px;min-width:155px;">
            Tampilkan
        </button>
        @if ($selectedReservation)
            <span class="cashier-pill is-green" style="min-width:126px;">Ditemukan</span>
        @elseif ($queryText !== '')
            <span class="cashier-pill is-amber" style="min-width:126px;">Tidak ditemukan</span>
        @endif
    </form>
</section>

<div class="cashier-split-main cashier-split-main--cancel">
    {{-- Left Column Stack --}}
    <div class="cashier-side-stack">
        <section class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title" style="font-size:18px;">Ringkasan reservasi</h2>
            @if ($selectedReservation)
                @php
                    $isPaid = $selectedReservation->latestPayment?->payment_status === 'paid';
                    $paymentText = ($isPaid ? 'Paid' : ucfirst(str_replace('_', ' ', $selectedReservation->latestPayment?->payment_status ?? 'Unpaid')))
                        . ' • Rp' . number_format((float) $selectedReservation->grand_total, 0, ',', '.');
                    $tableText = $selectedReservation->tables->isNotEmpty()
                        ? $selectedReservation->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
                        : 'Tanpa kursi khusus';
                @endphp
                <dl class="cashier-detail-list">
                    <div class="cashier-detail-row">
                        <dt>Tamu</dt>
                        <dd>{{ $selectedReservation->customer_name }}</dd>
                    </div>
                    <div class="cashier-detail-row">
                        <dt>Outlet</dt>
                        <dd>{{ $selectedReservation->outlet?->name ?? '—' }}</dd>
                    </div>
                    <div class="cashier-detail-row">
                        <dt>Jadwal</dt>
                        <dd>{{ $selectedReservation->reservation_date?->locale(app()->getLocale())->translatedFormat('l, d M') ?? '—' }} • {{ substr((string) $selectedReservation->start_time, 0, 5) }}</dd>
                    </div>
                    <div class="cashier-detail-row">
                        <dt>Meja</dt>
                        <dd>{{ $tableText }}</dd>
                    </div>
                    <div class="cashier-detail-row">
                        <dt>Pembayaran</dt>
                        <dd>{{ $paymentText }}</dd>
                    </div>
                    <div class="cashier-detail-row">
                        <dt>Catatan pelanggan</dt>
                        <dd>{{ $selectedReservation->notes ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($isPaid)
                    <div class="mt-4 rounded-lg px-3.5 py-2" style="background:var(--amber-bg);color:var(--amber-ink);font-size:11.5px;font-weight:600;">
                        Keputusan batal tidak memproses refund otomatis.
                    </div>
                @endif
            @else
                <p class="cashier-card-sub mt-3">Cari kode atau nama tamu untuk menampilkan ringkasan reservasi.</p>
            @endif
        </section>

        <section class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title" style="font-size:16px;">Jejak permintaan</h2>
            <p class="cashier-card-sub">Sistem menyimpan pengaju, waktu, alasan, dan keputusan Manager.</p>
            @if ($selectedReservation && $selectedReservation->cancellationRequests->isNotEmpty())
                <div class="mt-3 space-y-2 border-t pt-3" style="border-color:var(--border-line);font-size:12px;">
                    @foreach ($selectedReservation->cancellationRequests->sortByDesc('created_at') as $cr)
                        @php
                            $crBadge = match ($cr->status) {
                                'approved' => ['Disetujui', 'is-green'],
                                'rejected' => ['Ditolak', 'is-red'],
                                default => ['Menunggu Manager', 'is-amber'],
                            };
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span>{{ $cr->created_at?->format('d/m H.i') }} • Oleh {{ ucfirst($cr->requested_by) }}</span>
                            <span class="cashier-pill {{ $crBadge[1] }}">{{ $crBadge[0] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Right Column: Form Detail Permintaan --}}
    <section class="cashier-card cashier-card-pad">
        <h2 class="cashier-card-title" style="font-size:18px;">Detail permintaan</h2>

        <form method="POST" action="{{ route('cashier.cancellations.store') }}" class="mt-5 space-y-5">
            @csrf
            <input type="hidden" name="reservations_id" value="{{ $selectedReservation?->reservations_id }}">
            @if ($outletId)
                <input type="hidden" name="outlet" value="{{ $outletId }}">
            @endif

            <div>
                <label for="cancel-reason" class="cashier-field-label" style="font-size:13px;">Alasan pembatalan</label>
                <textarea id="cancel-reason" name="reason" required maxlength="1000"
                          placeholder="Catat alasan dari tamu secara ringkas…"
                          class="cashier-textarea">{{ old('reason') }}</textarea>
            </div>

            <div>
                <label for="cancel-note" class="cashier-field-label" style="font-size:13px;">Catatan untuk Manager (opsional)</label>
                <textarea id="cancel-note" name="manager_note" maxlength="1000"
                          placeholder="Informasi tambahan yang perlu ditinjau…"
                          class="cashier-textarea">{{ old('manager_note') }}</textarea>
            </div>

            <div class="rounded-[10px] px-4 py-3.5" style="background:var(--brand-soft);color:var(--brand-navy);font-size:12px;">
                Status menjadi Menunggu keputusan Manager. Reservasi tetap mengikuti statusnya.
            </div>

            <div class="cashier-btn-pair">
                <a href="{{ route('cashier.dashboard', request()->only('outlet')) }}" class="cashier-btn cashier-btn-outline cashier-btn-lg">
                    Batal
                </a>
                <button type="submit" @disabled(! $selectedReservation) class="cashier-btn cashier-btn-primary cashier-btn-lg">
                    Kirim permintaan ke Manager
                </button>
            </div>
        </form>
    </section>
</div>

<p class="cashier-footnote">Permintaan tidak mengubah status reservasi sebelum keputusan Manager.</p>
@endsection
