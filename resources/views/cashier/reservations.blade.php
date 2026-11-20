@extends('layouts.cashier')

@section('title', 'Reservasi')

@section('content')
<div class="cashier-page-head-row">
    <div class="cashier-page-head">
        <h1>Reservasi</h1>
        <p>Lihat jadwal outlet dan catat kedatangan tamu secara bertahap.</p>
    </div>
    <span class="cashier-outlet-badge">{{ $activeOutlet?->name ?? 'Semua outlet' }}</span>
</div>

{{-- Filter Bar --}}
<section class="cashier-filter-card" aria-label="Filter daftar reservasi">
    <form method="GET" action="{{ route('cashier.reservations.index') }}" class="cashier-filter-row">
        @if ($outletId)
            <input type="hidden" name="outlet" value="{{ $outletId }}">
        @endif
        <div class="cashier-filter-date">
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" aria-label="Tanggal reservasi"
                   class="cashier-input">
            @if ($date !== '')
                <a href="{{ route('cashier.reservations.index', array_filter(['outlet' => $outletId, 'status' => $status !== 'all' ? $status : null, 'area' => $areaId > 0 ? $areaId : null, 'q' => $search !== '' ? $search : null])) }}"
                   title="Semua tanggal aktif" class="cashier-btn cashier-btn-outline" style="min-height:42px;padding:0 10px;font-size:12px;">
                    Semua
                </a>
            @endif
        </div>
        <div class="cashier-filter-select">
            <select name="status" onchange="this.form.submit()" aria-label="Filter status" class="cashier-select">
                <option value="all" @selected($status === 'all')>Semua status</option>
                <option value="confirmed" @selected($status === 'confirmed')>Belum check-in</option>
                <option value="partially_arrived" @selected($status === 'partially_arrived')>Sebagian hadir</option>
                <option value="checked_in" @selected($status === 'checked_in')>Sudah check-in</option>
                <option value="pending_payment" @selected($status === 'pending_payment')>Menunggu bayar</option>
                <option value="cancelled" @selected($status === 'cancelled')>Dibatalkan</option>
            </select>
        </div>
        <div class="cashier-filter-select">
            <select name="area" onchange="this.form.submit()" aria-label="Filter area" class="cashier-select">
                <option value="0">Semua area</option>
                @foreach ($areas as $a)
                    <option value="{{ $a->areas_id }}" @selected($areaId === (int) $a->areas_id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="cashier-search-box">
            <svg class="cashier-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau kode reservasi..."
                   aria-label="Cari nama atau kode reservasi" class="cashier-input">
        </div>
        <div class="cashier-filter-actions">
            <button type="submit" class="cashier-btn cashier-btn-primary" style="min-height:42px;min-width:96px;">Cari</button>
            @if ($date !== '' || $status !== 'all' || $areaId > 0 || $search !== '')
                <a href="{{ route('cashier.reservations.index', array_filter(['outlet' => $outletId])) }}"
                   class="cashier-btn cashier-btn-outline" style="min-height:42px;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</section>

{{-- 4 Metric Cards (Figma 433:267) --}}
<section class="cashier-metrics-4">
    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">R</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">{{ $isToday ? 'Reservasi aktif' : 'Reservasi ' . $dateLabel }}</p>
            <p class="cashier-metric-val">{{ $metrics['day_total'] }}</p>
            <p class="cashier-metric-note">{{ $metrics['upcoming_two_hours'] }} kedatangan berikutnya</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-amber">…</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Belum check-in</p>
            <p class="cashier-metric-val">{{ $metrics['not_checked_in'] }}</p>
            <p class="cashier-metric-note">Sesuai jadwal terpilih</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-green">✓</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Sudah check-in</p>
            <p class="cashier-metric-val">{{ $metrics['checked_in'] }}</p>
            <p class="cashier-metric-note">Kedatangan tercatat</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">→</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Kedatangan 2 jam</p>
            <p class="cashier-metric-val">{{ $metrics['upcoming_two_hours'] }}</p>
            <p class="cashier-metric-note">Perlu disiapkan outlet</p>
        </div>
    </div>
</section>

<div class="cashier-split-main">
    {{-- Left Card: Jadwal reservasi --}}
    <section class="cashier-card cashier-card-pad">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h2 class="cashier-card-title">Jadwal reservasi</h2>
                <p class="cashier-card-sub">{{ $reservations->total() }} reservasi • {{ $activeOutlet?->name ?? 'Semua outlet' }}</p>
            </div>
            <span style="font-size:11px;color:var(--text-muted);">Diurutkan dari terbaru</span>
        </div>

        <div class="cashier-table-wrap mt-4">
            <table class="cashier-table">
                <thead>
                    <tr style="background:var(--bg-page);">
                        <th>Jam</th>
                        <th>Tamu / Kode</th>
                        <th>Meja</th>
                        <th>Hadir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $row)
                        @php
                            $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                            $isSelected = $selectedReservation && $selectedReservation->reservations_id === $row->reservations_id;
                            $tableText = $row->tables->isNotEmpty()
                                ? $row->tables->map(fn ($t) => $t->table_code . ($t->area ? ' • ' . $t->area->name : ''))->implode(', ')
                                : 'Tanpa kursi';
                            $pillClass = $arrived >= $row->guest_count && $row->guest_count > 0
                                ? 'is-green'
                                : ($arrived > 0 ? 'is-amber' : 'is-neutral');
                        @endphp
                        <tr style="{{ $isSelected ? 'background:#f5f7ff;' : '' }}">
                            <td style="white-space:nowrap;">
                                <div style="font-weight:600;">{{ substr((string) $row->start_time, 0, 5) }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--text-ink);" translate="no">{{ $row->customer_name }}</div>
                                <div style="font-size:11px;color:var(--text-muted);" translate="no">{{ $row->reservation_code }}</div>
                            </td>
                            <td style="font-size:12px;">{{ $tableText }}</td>
                            <td>
                                <span class="cashier-pill {{ $pillClass }}" style="color:var(--brand-navy);">
                                    {{ $arrived }} / {{ $row->guest_count }} hadir
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                                   class="cashier-btn cashier-btn-soft cashier-btn-sm">
                                    Detail →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:36px 12px;text-align:center;color:var(--text-muted);font-size:13px;">
                                Belum ada reservasi pada tanggal dan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards (< 768px) --}}
        <div class="cashier-mobile-list mt-4">
            @forelse ($reservations as $row)
                @php
                    $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                    $isSelected = $selectedReservation && $selectedReservation->reservations_id === $row->reservations_id;
                    $pillClass = $arrived >= $row->guest_count && $row->guest_count > 0
                        ? 'is-green'
                        : ($arrived > 0 ? 'is-amber' : 'is-neutral');
                @endphp
                <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                   class="cashier-mobile-item {{ $isSelected ? 'is-selected' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div style="font-weight:600;color:var(--brand-navy);">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }} • {{ substr((string) $row->start_time, 0, 5) }} • {{ $row->customer_name }}</div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">{{ $row->reservation_code }}</div>
                        </div>
                        <span class="cashier-pill {{ $pillClass }}">{{ $arrived }} / {{ $row->guest_count }} hadir</span>
                    </div>
                </a>
            @empty
                <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:24px 0;">Belum ada reservasi pada tanggal ini.</p>
            @endforelse
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <span style="font-size:12px;color:var(--text-muted);">
                Menampilkan {{ $reservations->firstItem() ?? 0 }}–{{ $reservations->lastItem() ?? 0 }} dari {{ $reservations->total() }} reservasi
            </span>
            <div>{{ $reservations->onEachSide(1)->links() }}</div>
        </div>
    </section>

    {{-- Right Stack --}}
    <div class="cashier-side-stack">
        {{-- Card 1: Pindai QR reservasi --}}
        <section class="cashier-card cashier-card-pad">
            <div class="flex items-center gap-3.5">
                <div style="width:50px;height:50px;border-radius:10px;background:var(--brand-soft);color:var(--brand-navy);display:grid;place-items:center;font-size:15px;font-weight:600;flex-shrink:0;">
                    QR
                </div>
                <h2 class="cashier-card-title" style="font-size:16px;">Pindai QR reservasi</h2>
            </div>
            <p class="cashier-card-sub mt-3">Buka data reservasi dan cocokkan nama tamu.</p>
            <button type="button" data-open-qr-scanner class="cashier-btn cashier-btn-primary cashier-btn-lg mt-4" style="width:100%;">
                Buka pemindai QR
            </button>
        </section>

        {{-- Card 2: Catat kedatangan --}}
        <section class="cashier-card cashier-card-pad">
            <h2 class="cashier-card-title">Catat kedatangan</h2>
            @if ($selectedReservation)
                @php
                    $arrivedCount = (int) ($selectedReservation->checkIns->max('cumulative_guest_count') ?? 0);
                    $remaining = max(0, (int) $selectedReservation->guest_count - $arrivedCount);
                    $pct = $selectedReservation->guest_count > 0
                        ? min(100, (int) round(($arrivedCount / $selectedReservation->guest_count) * 100))
                        : 0;
                    $isPaid = $selectedReservation->latestPayment?->payment_status === 'paid';
                    $tableLabel = $selectedReservation->tables->isNotEmpty()
                        ? 'Meja ' . $selectedReservation->tables->pluck('table_code')->implode(', ') . ' • ' . ($selectedReservation->tables->first()?->area?->name ?? 'Area')
                        : 'Tanpa kursi khusus';
                @endphp
                <p class="cashier-card-sub mt-1" translate="no">{{ $selectedReservation->reservation_code }} • {{ $selectedReservation->reservation_date?->locale(app()->getLocale())->translatedFormat('d M') }} • {{ substr((string) $selectedReservation->start_time, 0, 5) }}</p>
                <p style="margin:10px 0 2px;font-size:15px;font-weight:600;" translate="no">{{ $selectedReservation->customer_name }}</p>
                <p style="margin:0;font-size:12px;color:var(--text-muted);">{{ $tableLabel }}</p>
                @if ($selectedReservation->notes)
                    <div class="mt-3 rounded-lg border border-indigo-200 bg-[#f5f7ff] px-3 py-2 text-xs text-[#21265d]">
                        <span class="font-semibold">Catatan pelanggan:</span> {{ $selectedReservation->notes }}
                    </div>
                @endif

                <div style="border-top:1px solid var(--border-line);margin:14px 0;padding-top:14px;display:flex;justify-content:space-between;align-items:flex-end;gap:10px;">
                    <div>
                        <p style="margin:0;font-size:11px;color:var(--text-muted);">Jumlah tamu</p>
                        <p style="margin:3px 0 0;font-size:13px;font-weight:600;">{{ $selectedReservation->guest_count }} orang</p>
                    </div>
                    <div style="text-align:right;">
                        <p style="margin:0;font-size:11px;color:var(--text-muted);">Pembayaran</p>
                        @if ($isPaid)
                            <span class="cashier-pill is-green" style="margin-top:4px;">Lunas</span>
                        @else
                            <span class="cashier-pill is-amber" style="margin-top:4px;">{{ ucfirst(str_replace('_', ' ', $selectedReservation->latestPayment?->payment_status ?? 'unpaid')) }}</span>
                        @endif
                    </div>
                </div>

                <div class="cashier-progress-track">
                    <div class="cashier-progress-bar" style="width:{{ $pct }}%;"></div>
                </div>
                <p style="margin:7px 0 0;font-size:11.5px;color:var(--text-muted);">{{ $arrivedCount }} dari {{ $selectedReservation->guest_count }} tamu sudah hadir</p>

                @if ($isPaid && in_array($selectedReservation->reservation_status, ['confirmed', 'partially_arrived'], true) && $remaining > 0)
                    <form method="POST" action="{{ route('cashier.checkin.record', $selectedReservation) }}" class="mt-4">
                        @csrf
                        <div class="flex flex-wrap items-center gap-2" data-stepper>
                            <button type="button" data-step-dec aria-label="Kurangi" class="cashier-step-btn is-minus" style="width:42px;height:38px;">−</button>
                            <input type="number" name="guest_count_arrived" min="1" max="{{ $remaining }}" value="{{ $remaining }}" required class="cashier-input cashier-step-input" style="min-height:38px !important;">
                            <button type="button" data-step-inc aria-label="Tambah" class="cashier-step-btn is-plus" style="width:42px;height:38px;">+</button>
                            <button type="submit" class="cashier-btn cashier-btn-primary" style="flex:1;min-height:38px;">Simpan check-in</button>
                        </div>
                        <input type="text" name="notes" maxlength="255" placeholder="Catatan opsional (mis. meja digabung)" class="cashier-input mt-2.5">
                    </form>
                @elseif ($selectedReservation->reservation_status === 'checked_in')
                    <span class="cashier-pill is-green" style="margin-top:14px;">Semua tamu sudah check-in</span>
                @elseif ($selectedReservation->reservation_status === 'completed')
                    <span class="cashier-pill is-green" style="margin-top:14px;">Kunjungan selesai</span>
                @endif

                @if (in_array($selectedReservation->reservation_status, ['checked_in', 'partially_arrived'], true) && $arrivedCount > 0)
                    <form method="POST" action="{{ route('cashier.checkin.complete', $selectedReservation) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="cashier-btn cashier-btn-outline" style="width:100%;">
                            Tandai kunjungan selesai ✓
                        </button>
                    </form>
                @endif

                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-line);">
                    <p class="cashier-eyebrow" style="margin:0 0 6px;">Riwayat kedatangan</p>
                    @forelse ($selectedReservation->checkIns->sortByDesc('checked_in_at') as $ci)
                        <div style="font-size:12px;color:var(--text-ink);padding:3px 0;">
                            {{ $ci->checked_in_at?->format('H.i') ?? '—' }} • +{{ $ci->guest_count_arrived }} tamu •
                            <span style="color:var(--text-muted);">Kasir: {{ $ci->checkedInBy?->name ?? 'Kasir' }}</span>
                        </div>
                    @empty
                        <p style="font-size:12px;color:var(--text-muted);margin:0;">Belum ada kedatangan tercatat.</p>
                    @endforelse
                </div>

                @if (! in_array($selectedReservation->reservation_status, ['cancelled', 'completed'], true))
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border-line);">
                        <a href="{{ route('cashier.cancellations.index', array_filter(['reservation' => $selectedReservation->reservations_id, 'outlet' => $outletId])) }}"
                           style="font-size:12px;font-weight:600;color:var(--brand-navy);text-decoration:none;">
                            Tamu batal hadir? Ajukan pembatalan ke Manager →
                        </a>
                    </div>
                @endif

                <p class="cashier-card-sub mt-3">Catat jumlah yang tiba sekarang; setiap perubahan masuk riwayat.</p>
            @else
                <p class="cashier-card-sub mt-2">Pilih reservasi dari tabel atau pindai QR untuk mencatat kedatangan.</p>
            @endif
        </section>
    </div>
</div>

<p class="cashier-footnote">Kasir memindai QR dan mencatat kehadiran bertahap. Verifikasi pembayaran hanya dilakukan Finance.</p>
@endsection
