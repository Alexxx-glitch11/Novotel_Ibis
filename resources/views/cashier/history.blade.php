@extends('layouts.cashier')

@section('title', 'Riwayat reservasi')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="cashier-page-head">
        <h1>Riwayat reservasi</h1>
        <p>Jejak check-in kedatangan tamu, penyelesaian kunjungan, serta petugas Kasir, Finance, dan Manager yang menangani.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <span class="cashier-outlet-badge">{{ $activeOutlet?->name ?? 'Semua outlet' }}</span>
        <a href="{{ route('cashier.history.export', request()->query()) }}"
           class="cashier-btn cashier-btn-outline" style="min-height:42px;">
            Ekspor CSV
        </a>
        <button type="button" data-open-qr-scanner class="cashier-btn cashier-btn-primary" style="min-height:42px;">
            Pindai QR ▦
        </button>
    </div>
</div>

{{-- Filter Bar --}}
<section class="cashier-filter-card" aria-label="Filter riwayat reservasi">
    <form method="GET" action="{{ route('cashier.history.index') }}" class="cashier-filter-row">
        @if ($outletId)
            <input type="hidden" name="outlet" value="{{ $outletId }}">
        @endif
        <div class="cashier-filter-date">
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" aria-label="Tanggal reservasi"
                   class="cashier-input">
            @if ($date !== '')
                <a href="{{ route('cashier.history.index', array_filter(['outlet' => $outletId, 'status' => $status !== 'all' ? $status : null, 'q' => $search !== '' ? $search : null])) }}"
                   title="Semua tanggal" class="cashier-btn cashier-btn-outline" style="min-height:42px;padding:0 10px;font-size:12px;">
                    Semua
                </a>
            @endif
        </div>
        <div class="cashier-filter-select" style="flex:0 1 190px;">
            <select name="status" onchange="this.form.submit()" aria-label="Filter status" class="cashier-select">
                <option value="all" @selected($status === 'all')>Semua status</option>
                <option value="checked_in" @selected($status === 'checked_in')>Sudah check-in</option>
                <option value="completed" @selected($status === 'completed')>Selesai</option>
                <option value="partially_arrived" @selected($status === 'partially_arrived')>Sebagian hadir</option>
                <option value="confirmed" @selected($status === 'confirmed')>Terkonfirmasi</option>
                <option value="no_show" @selected($status === 'no_show')>Tidak hadir (No-show)</option>
                <option value="cancelled" @selected($status === 'cancelled')>Dibatalkan</option>
            </select>
        </div>
        <div class="cashier-search-box">
            <svg class="cashier-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari kode, nama tamu, atau nama petugas..."
                   aria-label="Cari riwayat reservasi" class="cashier-input">
        </div>
        <div class="cashier-filter-actions">
            <button type="submit" class="cashier-btn cashier-btn-primary" style="min-height:42px;min-width:96px;">Cari</button>
            @if ($date !== '' || $status !== 'all' || $search !== '')
                <a href="{{ route('cashier.history.index', array_filter(['outlet' => $outletId])) }}"
                   class="cashier-btn cashier-btn-outline" style="min-height:42px;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</section>

{{-- 4 Metric Cards --}}
<section class="cashier-metrics-4">
    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-green">✓</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Tamu check-in</p>
            <p class="cashier-metric-val">{{ $metrics['checked_in_total'] }}</p>
            <p class="cashier-metric-note">Hadir / sebagian hadir</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">R</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Aktivitas check-in</p>
            <p class="cashier-metric-val">{{ $metrics['checkin_logs_total'] }}</p>
            <p class="cashier-metric-note">{{ $metrics['checkin_logs_today'] }} tercatat hari ini</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-blue">S</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Kunjungan selesai</p>
            <p class="cashier-metric-val">{{ $metrics['completed_total'] }}</p>
            <p class="cashier-metric-note">Ditandai selesai oleh Kasir</p>
        </div>
    </div>

    <div class="cashier-metric">
        <span translate="no" class="cashier-metric-icon is-amber">B</span>
        <div class="min-w-0">
            <p class="cashier-metric-label">Batal &amp; No-show</p>
            <p class="cashier-metric-val">{{ $metrics['cancellations_total'] }}</p>
            <p class="cashier-metric-note">Pembatalan &amp; tidak hadir</p>
        </div>
    </div>
</section>

{{-- Full-width History Table Card --}}
<section class="cashier-card cashier-card-pad mt-5">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 class="cashier-card-title">Daftar riwayat reservasi &amp; petugas</h2>
            <p class="cashier-card-sub">{{ $reservations->total() }} reservasi • {{ $dateLabel }} • {{ $activeOutlet?->name ?? 'Semua outlet' }}</p>
        </div>
        <span style="font-size:11px;color:var(--text-muted);">Menampilkan reservasi operasional Kasir &amp; jejak petugas</span>
    </div>

    <div class="cashier-table-wrap mt-4">
        <table class="cashier-table cashier-table--compact">
            <thead>
                <tr style="background:var(--bg-page);">
                    <th>Jadwal</th>
                    <th>Tamu / Kode &amp; Meja</th>
                    <th>Kehadiran &amp; Catatan</th>
                    <th>Status</th>
                    <th>Dilakukan oleh (Petugas)</th>
                    <th style="text-align:right;">Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $row)
                    @php
                        $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                        $checkInsSorted = $row->checkIns->sortByDesc('checked_in_at');
                        $lastCheckIn = $checkInsSorted->first();
                        $lastCancel = $row->cancellationRequests->sortByDesc('cancellation_requests_id')->first();
                        $payment = $row->latestPayment;
                        $statusLog = ($statusLogsByReservation->get($row->reservations_id) ?? collect())->first();
                        $statusPill = match ($row->reservation_status) {
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
                        $cashierNotes = $checkInsSorted->pluck('notes')->filter()->values();
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;">{{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M Y') }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ substr((string) $row->start_time, 0, 5) }} WIB</div>
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--text-ink);" translate="no">{{ $row->customer_name }}</div>
                            <div style="font-size:11px;color:var(--brand-navy);font-weight:600;" translate="no">{{ $row->reservation_code }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ $row->outlet?->name ?? 'Outlet' }} • {{ $tableText }} ({{ $row->guest_count }} org)</div>
                        </td>
                        <td style="font-size:12px;">
                            <div style="font-weight:600;color:var(--text-ink);">{{ $arrived }} / {{ $row->guest_count }} tamu hadir</div>
                            @if ($lastCheckIn)
                                <div style="font-size:11px;color:var(--text-muted);">
                                    Terakhir {{ $lastCheckIn->checked_in_at?->format('d M H:i') }}
                                </div>
                            @endif
                            @if ($row->notes)
                                <div style="font-size:11px;color:var(--brand-navy);margin-top:2px;">
                                    Catatan pelanggan: {{ \Illuminate\Support\Str::limit($row->notes, 45) }}
                                </div>
                            @endif
                            @if ($cashierNotes->isNotEmpty())
                                <div style="font-size:11px;color:var(--brand-navy);margin-top:2px;">
                                    Catatan: {{ \Illuminate\Support\Str::limit($cashierNotes->first(), 40) }}
                                </div>
                            @elseif ($lastCancel)
                                <div style="font-size:11px;color:var(--amber-ink);margin-top:2px;">
                                    Batal: {{ \Illuminate\Support\Str::limit($lastCancel->reason, 40) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="cashier-pill {{ $statusPill[1] }}">{{ $statusPill[0] }}</span>
                        </td>
                        <td style="font-size:12px;line-height:1.5;">
                            @if ($checkInsSorted->isNotEmpty())
                                @foreach ($checkInsSorted->take(2) as $ci)
                                    <div>
                                        <span style="font-weight:600;color:var(--green-ink);">Kasir (Check-in):</span>
                                        <span translate="no">{{ $ci->checkedInBy?->name ?? 'Kasir' }}</span>
                                        <span style="color:var(--text-muted);">({{ $ci->guest_count_arrived }} tamu • {{ $ci->checked_in_at?->format('d M H:i') }})</span>
                                    </div>
                                @endforeach
                            @endif
                            @if ($statusLog)
                                <div>
                                    <span style="font-weight:600;color:var(--brand-navy);">Kasir ({{ $row->reservation_status === 'no_show' ? 'No-show' : 'Selesai' }}):</span>
                                    <span translate="no">{{ $statusLog->user?->name ?? 'Kasir' }}</span>
                                    <span style="color:var(--text-muted);">({{ $statusLog->created_at?->format('d M H:i') }})</span>
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
                            @if ($checkInsSorted->isEmpty() && ! $statusLog && ! $payment?->verifiedBy && ! $lastCancel)
                                <span style="color:var(--text-muted);">Menunggu kedatangan tamu</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            @if (in_array($row->reservation_status, ['checked_in', 'partially_arrived'], true))
                                <form method="POST" action="{{ route('cashier.checkin.complete', $row) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="cashier-btn cashier-btn-primary cashier-btn-sm">
                                        Tandai Selesai ✓
                                    </button>
                                </form>
                            @elseif ($row->reservation_status === 'confirmed')
                                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                                    <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                                       class="cashier-btn cashier-btn-soft cashier-btn-sm">
                                        Catat Hadir →
                                    </a>
                                    <form method="POST" action="{{ route('cashier.checkin.no-show', $row) }}"
                                          onsubmit="return confirm('Tandai {{ $row->reservation_code }} sebagai No-show (tidak hadir)?')">
                                        @csrf
                                        <button type="submit" class="cashier-btn cashier-btn-outline cashier-btn-sm" style="color:var(--red-ink);border-color:#fecaca;">
                                            No-Show
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size:11.5px;color:var(--text-muted);">Arsip selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:36px 12px;text-align:center;color:var(--text-muted);font-size:13px;">
                            Belum ada riwayat reservasi operasional yang cocok dengan filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Responsive Mobile Cards (< 768px) --}}
    <div class="cashier-mobile-list mt-4">
        @forelse ($reservations as $row)
            @php
                $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                $lastCheckIn = $row->checkIns->sortByDesc('checked_in_at')->first();
                $payment = $row->latestPayment;
                $statusPill = match ($row->reservation_status) {
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
                        <div style="font-weight:600;color:var(--brand-navy);">{{ $row->customer_name }}</div>
                        <div style="font-size:12px;color:var(--text-muted);">{{ $row->reservation_code }} • {{ $row->reservation_date?->locale(app()->getLocale())->translatedFormat('d M Y') }} {{ substr((string) $row->start_time, 0, 5) }}</div>
                    </div>
                    <span class="cashier-pill {{ $statusPill[1] }}">{{ $statusPill[0] }}</span>
                </div>
                <div style="margin-top:8px;font-size:12px;color:var(--text-muted);">
                    <div>Kehadiran: <strong style="color:var(--text-ink);">{{ $arrived }} / {{ $row->guest_count }} tamu</strong></div>
                    @if ($lastCheckIn)
                        <div>Kasir: <strong style="color:var(--text-ink);">{{ $lastCheckIn->checkedInBy?->name ?? 'Kasir' }}</strong> ({{ $lastCheckIn->checked_in_at?->format('d M H:i') }})</div>
                    @endif
                    @if ($payment?->verifiedBy)
                        <div>Finance: <strong style="color:var(--text-ink);">{{ $payment->verifiedBy->name }}</strong></div>
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if (in_array($row->reservation_status, ['checked_in', 'partially_arrived'], true))
                        <form method="POST" action="{{ route('cashier.checkin.complete', $row) }}">
                            @csrf
                            <button type="submit" class="cashier-btn cashier-btn-primary cashier-btn-sm">Tandai Selesai ✓</button>
                        </form>
                    @elseif ($row->reservation_status === 'confirmed')
                        <a href="{{ route('cashier.reservations.show', array_filter(['reservation' => $row->reservations_id, 'outlet' => $outletId])) }}"
                           class="cashier-btn cashier-btn-soft cashier-btn-sm">Catat Hadir →</a>
                    @endif
                </div>
            </div>
        @empty
            <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:24px 0;">Belum ada riwayat reservasi operasional.</p>
        @endforelse
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <span style="font-size:12px;color:var(--text-muted);">
            Menampilkan {{ $reservations->firstItem() ?? 0 }}–{{ $reservations->lastItem() ?? 0 }} dari {{ $reservations->total() }} reservasi
        </span>
        <div>{{ $reservations->onEachSide(1)->links() }}</div>
    </div>
</section>

<p class="cashier-footnote">Riwayat ini menampilkan reservasi yang sudah masuk operasional Kasir, lengkap dengan nama petugas Kasir, Finance, dan Manager, serta tombol penyelesaian kunjungan.</p>
@endsection
