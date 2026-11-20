@extends('layouts.cashier')

@section('title', 'Ringkasan operasional')

@section('content')
<div class="cashier-page-head">
    <h1>Ringkasan operasional</h1>
    <p>Ringkasan kedatangan dan aksi cepat outlet hari ini.</p>
</div>

{{-- 4 Metric Cards (Figma 364:50) --}}
<section class="cashier-metrics-4">
    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">K</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Kedatangan hari ini</p>
            <p class="cashier-metric-val">{{ $metrics['today_total'] }}</p>
            <p class="cashier-metric-note">{{ $metrics['upcoming_two_hours'] }} berikutnya dalam 2 jam</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-amber">B</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Belum check-in</p>
            <p class="cashier-metric-val">{{ $metrics['not_checked_in'] }}</p>
            <p class="cashier-metric-note">Sesuai jadwal aktif</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-green">S</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Sudah check-in</p>
            <p class="cashier-metric-val">{{ $metrics['checked_in'] }}</p>
            <p class="cashier-metric-note">Kedatangan tercatat</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">P</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Permintaan batal</p>
            <p class="cashier-metric-val">{{ $metrics['pending_cancellations'] }}</p>
            <p class="cashier-metric-note">Menunggu Manager</p>
        </div>
    </div>
</section>

<div class="cashier-split-main cashier-split-main--stretch">
    {{-- Left Card: Kedatangan berikutnya --}}
    <section class="cashier-card cashier-card-pad cashier-card--stretch">
        <h2 class="cashier-card-title">Kedatangan berikutnya</h2>
        <p class="cashier-card-sub">{{ $upcomingReservations->count() }} jadwal terdekat • klik baris untuk memilih di panel Catat kedatangan, atau klik Buka.</p>

        <form method="GET" action="{{ route('cashier.dashboard') }}" class="mt-3.5 flex flex-wrap items-center gap-2.5">
            @if ($outletId)
                <input type="hidden" name="outlet" value="{{ $outletId }}">
            @endif
            <div class="cashier-search-box" style="flex: 1 1 220px;">
                <svg class="cashier-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau kode reservasi…" aria-label="Cari nama atau kode reservasi" class="cashier-input">
            </div>
            <div class="flex flex-wrap gap-2" style="flex: 0 1 auto;">
                <select name="status" onchange="this.form.submit()" aria-label="Filter status" class="cashier-select" style="width:auto;min-width:136px;">
                    <option value="all" @selected($status === 'all')>Semua status</option>
                    <option value="confirmed" @selected($status === 'confirmed')>Akan datang</option>
                    <option value="partially_arrived" @selected($status === 'partially_arrived')>Sebagian hadir</option>
                    <option value="checked_in" @selected($status === 'checked_in')>Sudah check-in</option>
                </select>
                <select name="area" onchange="this.form.submit()" aria-label="Filter area" class="cashier-select" style="width:auto;min-width:130px;">
                    <option value="0">Semua area</option>
                    @foreach ($areas as $a)
                        <option value="{{ $a->areas_id }}" @selected($areaId === (int) $a->areas_id)>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="cashier-table-wrap mt-4">
            <table class="cashier-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tamu / Kode</th>
                        <th>Meja</th>
                        <th>Status</th>
                        <th style="text-align:right;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($upcomingReservations as $row)
                        @php
                            $isSelected = $selectedReservation && $selectedReservation->reservations_id === $row->reservations_id;
                            $arrivedRowCount = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                            $remainingRowCount = max(0, (int) $row->guest_count - $arrivedRowCount);
                            $rowIsPaid = $row->latestPayment?->payment_status === 'paid';
                            $tableText = $row->tables->isNotEmpty()
                                ? $row->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
                                : 'Tanpa kursi';
                            $pill = match ($row->reservation_status) {
                                'partially_arrived' => ['Sebagian hadir', 'is-amber'],
                                'checked_in', 'completed' => ['Sudah check-in', 'is-green'],
                                default => ['Akan datang', 'is-neutral'],
                            };
                            $rowPayload = [
                                'id' => $row->reservations_id,
                                'code' => $row->reservation_code,
                                'customer_name' => $row->customer_name,
                                'guest_count' => (int) $row->guest_count,
                                'arrived_count' => $arrivedRowCount,
                                'remaining' => $remainingRowCount,
                                'status' => $row->reservation_status,
                                'is_paid' => $rowIsPaid,
                                'can_checkin' => $rowIsPaid && in_array($row->reservation_status, ['confirmed', 'partially_arrived'], true) && $remainingRowCount > 0,
                                'notes' => $row->notes ?? '',
                                'table_text' => $tableText,
                                'schedule' => ($row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') ?? '—') . ' • ' . substr((string) $row->start_time, 0, 5),
                                'checkin_url' => route('cashier.checkin.record', $row),
                                'complete_url' => route('cashier.checkin.complete', $row),
                                'cancel_url' => route('cashier.cancellations.index', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])),
                            ];
                        @endphp
                        <tr class="cashier-arrival-row {{ $isSelected ? 'is-selected-arrival' : '' }}"
                            data-arrival-row
                            data-reservation='@json($rowPayload)'
                            tabindex="0">
                            <td style="white-space:nowrap;">
                                <div style="font-weight:600;">{{ substr((string) $row->start_time, 0, 5) }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }}</div>
                            </td>
                            <td>
                                <span style="font-weight:500;" translate="no">{{ $row->customer_name }}</span>
                                <span style="color:var(--text-muted);" translate="no"> • {{ $row->reservation_code }}</span>
                            </td>
                            <td style="color:var(--text-muted);">{{ $tableText }}</td>
                            <td><span class="cashier-pill {{ $pill[1] }}">{{ $pill[0] }}</span></td>
                            <td style="text-align:right;">
                                <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                                   data-row-ignore-select
                                   class="cashier-btn cashier-btn-primary cashier-btn-sm">
                                    Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:36px 12px;text-align:center;color:var(--text-muted);font-size:13px;">
                                Belum ada jadwal kedatangan yang sesuai dengan filter hari ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Responsive Mobile Cards (< 768px) --}}
        <div class="cashier-mobile-list mt-4">
            @forelse ($upcomingReservations as $row)
                @php
                    $isSelected = $selectedReservation && $selectedReservation->reservations_id === $row->reservations_id;
                    $pill = match ($row->reservation_status) {
                        'partially_arrived' => ['Sebagian hadir', 'is-amber'],
                        'checked_in', 'completed' => ['Sudah check-in', 'is-green'],
                        default => ['Akan datang', 'is-neutral'],
                    };
                @endphp
                <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                   class="cashier-mobile-item {{ $isSelected ? 'is-selected' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div style="font-weight:600;color:var(--brand-navy);">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }} • {{ substr((string) $row->start_time, 0, 5) }} • {{ $row->customer_name }}</div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">{{ $row->reservation_code }}</div>
                        </div>
                        <span class="cashier-pill {{ $pill[1] }}">{{ $pill[0] }}</span>
                    </div>
                </a>
            @empty
                <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:24px 0;">Belum ada jadwal kedatangan hari ini.</p>
            @endforelse
        </div>

        <div class="mt-auto pt-5">
            <a href="{{ route('cashier.reservations.index', request()->only('outlet')) }}"
               style="font-size:12.5px;font-weight:600;color:var(--brand-navy);text-decoration:none;">
                Lihat semua reservasi →
            </a>
        </div>
    </section>

    {{-- Right Side Stack --}}
    <div class="cashier-side-stack">
        {{-- Card 1: Pindai QR reservasi --}}
        <section class="cashier-card cashier-card-pad">
            <div class="flex items-center gap-3.5">
                <div style="width:58px;height:58px;border-radius:14px;background:var(--brand-soft);color:var(--brand-navy);display:grid;place-items:center;font-size:20px;font-weight:600;flex-shrink:0;">
                    QR
                </div>
                <h2 class="cashier-card-title" style="font-size:18px;">Pindai QR reservasi</h2>
            </div>
            <p class="cashier-card-sub mt-3">Pindai kode atau cari nomor reservasi untuk membuka detail check-in.</p>
            <button type="button" data-open-qr-scanner class="cashier-btn cashier-btn-primary cashier-btn-lg mt-4" style="width:100%;">
                Buka pemindai QR
            </button>
        </section>

        {{-- Card 2: Catat kedatangan --}}
        <section id="cashier-quick-checkin-card" class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title">Catat kedatangan</h2>
            @if ($selectedReservation)
                @php
                    $arrivedCount = (int) ($selectedReservation->checkIns->max('cumulative_guest_count') ?? 0);
                    $remaining = max(0, (int) $selectedReservation->guest_count - $arrivedCount);
                    $canCheckIn = in_array($selectedReservation->reservation_status, ['confirmed', 'partially_arrived'], true)
                        && $selectedReservation->latestPayment?->payment_status === 'paid'
                        && $remaining > 0;
                    $selectedTable = $selectedReservation->tables->isNotEmpty()
                        ? $selectedReservation->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
                        : 'Tanpa kursi';
                @endphp
                <p id="qc-sub" class="cashier-card-sub"><span translate="no">{{ $selectedReservation->customer_name }} • {{ $selectedReservation->reservation_code }}</span> ({{ $arrivedCount }}/{{ $selectedReservation->guest_count }})</p>
                <p id="qc-table-line" class="mt-1 text-[11px] font-medium text-[#6e7a8f]">Meja {{ $selectedTable }} • {{ $selectedReservation->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }} {{ substr((string) $selectedReservation->start_time, 0, 5) }}</p>
                <div id="qc-notes-wrap" class="mt-2.5 rounded-lg border border-indigo-200 bg-[#f5f7ff] px-3 py-2 text-xs text-[#21265d]" @if (! $selectedReservation->notes) hidden @endif>
                    <span class="font-semibold">Catatan pelanggan:</span> <span id="qc-notes-text">{{ $selectedReservation->notes }}</span>
                </div>

                <div id="qc-body">
                    @if ($canCheckIn)
                        <form method="POST" action="{{ route('cashier.checkin.record', $selectedReservation) }}" class="mt-4">
                            @csrf
                            <div class="flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p style="margin:0;font-size:13px;color:var(--text-muted);">Tamu hadir</p>
                                    <p style="margin:2px 0 0;font-size:27px;line-height:1.15;font-weight:600;color:var(--text-ink);">{{ $arrivedCount }}</p>
                                </div>
                                <div class="flex items-center gap-2" data-stepper>
                                    <button type="button" data-step-dec aria-label="Kurangi" class="cashier-step-btn is-minus" style="width:38px;height:38px;">−</button>
                                    <input type="number" name="guest_count_arrived" min="1" max="{{ $remaining }}" value="{{ $remaining }}" required class="cashier-input cashier-step-input" style="width:52px !important;min-height:38px !important;">
                                    <button type="button" data-step-inc aria-label="Tambah" class="cashier-step-btn is-plus" style="width:38px;height:38px;">+</button>
                                    <button type="submit" class="cashier-btn cashier-btn-primary" style="min-height:38px;padding:0 16px;">Simpan</button>
                                </div>
                            </div>
                            <div class="cashier-quick-picks mt-2.5">
                                <button type="button" data-checkin-qty="1" class="cashier-quick-chip">+1</button>
                                @if ($remaining >= 2)
                                    <button type="button" data-checkin-qty="2" class="cashier-quick-chip">+2</button>
                                @endif
                                <button type="button" data-checkin-qty="{{ $remaining }}" class="cashier-quick-chip">Semua ({{ $remaining }})</button>
                            </div>
                        </form>
                    @elseif ($selectedReservation->reservation_status === 'checked_in')
                        <div class="mt-4 space-y-2.5">
                            <span class="cashier-pill is-green">Seluruh tamu sudah check-in ({{ $arrivedCount }}/{{ $selectedReservation->guest_count }})</span>
                            <form method="POST" action="{{ route('cashier.checkin.complete', $selectedReservation) }}">
                                @csrf
                                <button type="submit" class="cashier-btn cashier-btn-outline cashier-btn-sm" style="width:100%;">
                                    Tandai kunjungan selesai ✓
                                </button>
                            </form>
                        </div>
                    @elseif ($selectedReservation->reservation_status === 'completed')
                        <div class="mt-4">
                            <span class="cashier-pill is-green">Kunjungan selesai ({{ $arrivedCount }}/{{ $selectedReservation->guest_count }})</span>
                        </div>
                    @else
                        <p class="cashier-card-sub mt-3">Untuk reservasi rombongan atau menunggu verifikasi Finance.</p>
                    @endif
                </div>
            @else
                <p id="qc-sub" class="cashier-card-sub">Untuk reservasi rombongan.</p>
                <p id="qc-table-line" class="mt-1 text-[11px] font-medium text-[#6e7a8f]" hidden></p>
                <div id="qc-notes-wrap" class="mt-2.5 rounded-lg border border-indigo-200 bg-[#f5f7ff] px-3 py-2 text-xs text-[#21265d]" hidden>
                    <span class="font-semibold">Catatan pelanggan:</span> <span id="qc-notes-text"></span>
                </div>
                <div id="qc-body" class="mt-4 flex items-center justify-between gap-2">
                    <div>
                        <p style="margin:0;font-size:13px;color:var(--text-muted);">Tamu hadir</p>
                        <p style="margin:2px 0 0;font-size:27px;line-height:1.15;font-weight:600;">0</p>
                    </div>
                    <span class="cashier-pill is-neutral">Pilih reservasi</span>
                </div>
            @endif
            <p class="cashier-card-sub mt-4">Maksimal sesuai jumlah tamu pesanan. Setiap perubahan tercatat.</p>
        </section>

        {{-- Card 3: Permintaan pembatalan --}}
        <section class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title">Permintaan pembatalan</h2>
            <p class="cashier-card-sub">Kasir mencatat; Manager memutuskan.</p>
            <a id="qc-cancel-link" href="{{ route('cashier.cancellations.index', array_filter(['reservation' => $selectedReservation?->reservations_id, 'outlet' => $outletId])) }}"
               class="cashier-btn cashier-btn-subtle mt-4" style="width:100%;">
                Ajukan ke Manager →
            </a>
        </section>
    </div>
</div>

{{-- Riwayat Reservasi Terbaru --}}
<section class="cashier-card cashier-card-pad mt-5">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="cashier-card-title">Riwayat reservasi terbaru</h2>
            <p class="cashier-card-sub">{{ $recentHistory->count() }} aktivitas operasional terakhir • {{ $activeOutlet?->name ?? 'Semua outlet' }}</p>
        </div>
        <a href="{{ route('cashier.history.index', request()->only('outlet')) }}"
           style="font-size:12.5px;font-weight:600;color:var(--brand-navy);text-decoration:none;">
            Lihat semua riwayat →
        </a>
    </div>

    <div class="cashier-table-wrap mt-4">
        <table class="cashier-table cashier-table--compact">
            <thead>
                <tr style="background:var(--bg-page);">
                    <th>Jadwal</th>
                    <th>Tamu / Kode</th>
                    <th>Meja &amp; Kehadiran</th>
                    <th>Status</th>
                    <th>Dilakukan oleh (Petugas)</th>
                    <th style="text-align:right;">Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentHistory as $row)
                    @php
                        $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                        $checkInsSorted = $row->checkIns->sortByDesc('checked_in_at');
                        $lastCheckIn = $checkInsSorted->first();
                        $lastCancel = $row->cancellationRequests->sortByDesc('cancellation_requests_id')->first();
                        $payment = $row->latestPayment;
                        $statusLog = ($statusLogsByReservation->get($row->reservations_id) ?? collect())->first();
                        $histPill = match ($row->reservation_status) {
                            'checked_in' => ['Sudah check-in', 'is-green'],
                            'completed' => ['Selesai', 'is-green'],
                            'partially_arrived' => ['Sebagian hadir', 'is-amber'],
                            'no_show' => ['No-show', 'is-red'],
                            'cancelled' => ['Dibatalkan', 'is-red'],
                            'confirmed' => ['Terkonfirmasi', 'is-navy'],
                            default => [ucfirst(str_replace('_', ' ', $row->reservation_status)), 'is-neutral'],
                        };
                        $tableText = $row->tables->isNotEmpty()
                            ? 'Meja ' . $row->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
                            : 'Tanpa kursi';
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ substr((string) $row->start_time, 0, 5) }} WIB</div>
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--text-ink);" translate="no">{{ $row->customer_name }}</div>
                            <div style="font-size:11px;color:var(--brand-navy);font-weight:600;" translate="no">{{ $row->reservation_code }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ $row->outlet?->name ?? 'Outlet' }}</div>
                        </td>
                        <td style="font-size:12px;">
                            <div style="color:var(--text-ink);">{{ $tableText }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ $arrived }} / {{ $row->guest_count }} tamu hadir</div>
                        </td>
                        <td>
                            <span class="cashier-pill {{ $histPill[1] }}">{{ $histPill[0] }}</span>
                        </td>
                        <td style="font-size:12px;line-height:1.45;">
                            @if ($lastCheckIn)
                                <div>
                                    <span style="font-weight:600;color:var(--green-ink);">Kasir:</span>
                                    <span translate="no">{{ $lastCheckIn->checkedInBy?->name ?? 'Kasir' }}</span>
                                    <span style="color:var(--text-muted);">({{ $lastCheckIn->checked_in_at?->format('d M H:i') }})</span>
                                </div>
                            @endif
                            @if ($statusLog)
                                <div>
                                    <span style="font-weight:600;color:var(--brand-navy);">Kasir ({{ $row->reservation_status === 'no_show' ? 'No-show' : 'Selesai' }}):</span>
                                    <span translate="no">{{ $statusLog->user?->name ?? 'Kasir' }}</span>
                                </div>
                            @endif
                            @if ($payment?->verifiedBy)
                                <div>
                                    <span style="font-weight:600;color:var(--brand-navy);">Finance:</span>
                                    <span translate="no">{{ $payment->verifiedBy->name }}</span>
                                    <span style="color:var(--text-muted);">({{ $payment->payment_status === 'paid' ? 'Paid' : ucfirst($payment->payment_status) }})</span>
                                </div>
                            @endif
                            @if ($lastCancel)
                                <div>
                                    <span style="font-weight:600;color:var(--amber-ink);">Batal:</span>
                                    <span>{{ $lastCancel->requested_by === 'cashier' ? 'Kasir' : 'Pelanggan' }}</span>
                                    @if ($lastCancel->reviewedBy)
                                        <span style="color:var(--text-muted);">• Manager: <span translate="no">{{ $lastCancel->reviewedBy->name }}</span></span>
                                    @endif
                                </div>
                            @endif
                            @if (! $lastCheckIn && ! $statusLog && ! $payment?->verifiedBy && ! $lastCancel)
                                <span style="color:var(--text-muted);">Menunggu kedatangan tamu</span>
                            @endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if (in_array($row->reservation_status, ['checked_in', 'partially_arrived'], true))
                                <form method="POST" action="{{ route('cashier.checkin.complete', $row) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="cashier-btn cashier-btn-primary cashier-btn-sm">Selesai ✓</button>
                                </form>
                            @elseif ($row->reservation_status === 'confirmed')
                                <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                                   class="cashier-btn cashier-btn-soft cashier-btn-sm">
                                    Buka →
                                </a>
                            @else
                                <span style="font-size:11.5px;color:var(--text-muted);">Arsip</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:28px 12px;text-align:center;color:var(--text-muted);font-size:13px;">
                            Belum ada riwayat reservasi operasional.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="cashier-mobile-list mt-4">
        @forelse ($recentHistory as $row)
            @php
                $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                $lastCheckIn = $row->checkIns->sortByDesc('checked_in_at')->first();
                $payment = $row->latestPayment;
                $histPill = match ($row->reservation_status) {
                    'checked_in' => ['Sudah check-in', 'is-green'],
                    'completed' => ['Selesai', 'is-green'],
                    'partially_arrived' => ['Sebagian hadir', 'is-amber'],
                    'no_show' => ['No-show', 'is-red'],
                    'cancelled' => ['Dibatalkan', 'is-red'],
                    'confirmed' => ['Terkonfirmasi', 'is-navy'],
                    default => [ucfirst(str_replace('_', ' ', $row->reservation_status)), 'is-neutral'],
                };
            @endphp
            <div class="cashier-mobile-item">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div style="font-weight:600;color:var(--brand-navy);" translate="no">{{ $row->customer_name }}</div>
                        <div style="font-size:12px;color:var(--text-muted);"><span translate="no">{{ $row->reservation_code }}</span> • {{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }} {{ substr((string) $row->start_time, 0, 5) }}</div>
                    </div>
                    <span class="cashier-pill {{ $histPill[1] }}">{{ $histPill[0] }}</span>
                </div>
                <div style="margin-top:6px;font-size:12px;color:var(--text-muted);">
                    <div>Hadir: <strong style="color:var(--text-ink);">{{ $arrived }} / {{ $row->guest_count }}</strong></div>
                    @if ($lastCheckIn)
                        <div>Kasir: <strong style="color:var(--text-ink);" translate="no">{{ $lastCheckIn->checkedInBy?->name ?? 'Kasir' }}</strong></div>
                    @endif
                    @if ($payment?->verifiedBy)
                        <div>Finance: <strong style="color:var(--text-ink);" translate="no">{{ $payment->verifiedBy->name }}</strong></div>
                    @endif
                </div>
            </div>
        @empty
            <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:18px 0;">Belum ada riwayat reservasi operasional.</p>
        @endforelse
    </div>
</section>

<p class="cashier-footnote">Check-in dicatat bertahap; pemindaian ulang tidak menggandakan jumlah tamu.</p>
@endsection
