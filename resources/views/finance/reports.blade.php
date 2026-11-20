@extends('layouts.finance')

@section('title', 'Laporan keuangan')

@section('content')
@php
    $formatShort = function (float $amount): string {
        if ($amount >= 1_000_000_000) {
            return 'Rp' . number_format($amount / 1_000_000_000, 1, ',', '.') . ' M';
        }
        if ($amount >= 1_000_000) {
            return 'Rp' . number_format($amount / 1_000_000, 1, ',', '.') . ' jt';
        }
        return 'Rp' . number_format($amount, 0, ',', '.');
    };
@endphp

<div>
    <h1 class="text-[26px] font-semibold leading-[34px] text-[#212b3b]">Laporan keuangan</h1>
    <p class="mt-1 text-sm text-[#6e7a8f]">Pantau transaksi terverifikasi dan ringkasan pembayaran per outlet.</p>
</div>

{{-- Filter + Export Bar --}}
<div class="mt-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" action="{{ route('finance.reports.index') }}" class="flex flex-wrap items-center gap-3">
        <select name="period" onchange="this.form.submit()" aria-label="Periode laporan"
                class="h-[44px] min-w-[200px] rounded-lg border border-[#e0e5f0] bg-white px-3.5 text-[13px] font-medium text-[#212b3b] focus:border-[#21265d] focus:outline-none">
            <option value="7" @selected($period === '7')>7 hari terakhir</option>
            <option value="30" @selected($period === '30')>30 hari terakhir</option>
            <option value="today" @selected($period === 'today')>Hari ini</option>
        </select>

        <select name="outlet" onchange="this.form.submit()" aria-label="Outlet laporan"
                class="h-[44px] min-w-[200px] rounded-lg border border-[#e0e5f0] bg-white px-3.5 text-[13px] font-medium text-[#212b3b] focus:border-[#21265d] focus:outline-none">
            <option value="">Semua outlet</option>
            @foreach ($outlets as $o)
                <option value="{{ $o->outlets_id }}" @selected((string) $outletId === (string) $o->outlets_id)>{{ $o->name }}</option>
            @endforeach
        </select>
    </form>

    <a href="{{ route('finance.reports.export', request()->only('period', 'outlet')) }}"
       class="inline-flex h-[44px] items-center justify-center gap-2 rounded-lg bg-[#21265d] px-5 text-xs font-medium text-white hover:bg-[#171c4f]">
        <span>↓</span>
        <span>Ekspor laporan</span>
    </a>
</div>

{{-- 4 Metric Cards --}}
<section class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-[10px] border border-[#e0e5f0] bg-white p-4">
        <div class="flex items-start gap-3">
            <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] bg-[#e3f5eb] text-xs font-semibold text-[#21265d]">Rp</span>
            <div>
                <p class="text-xs text-[#6e7a8f]">Pendapatan terverifikasi</p>
                <p class="mt-0.5 text-[22px] font-semibold text-[#212b3b]">{{ $formatShort($verifiedRevenue) }}</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-[#6e7a8f]">Hanya pembayaran Paid</p>
    </div>

    <div class="rounded-[10px] border border-[#e0e5f0] bg-white p-4">
        <div class="flex items-start gap-3">
            <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] bg-[#e8edff] text-xs font-semibold text-[#21265d]">✓</span>
            <div>
                <p class="text-xs text-[#6e7a8f]">Transaksi Paid</p>
                <p class="mt-0.5 text-[22px] font-semibold text-[#212b3b]">{{ $paidCount }}</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-[#6e7a8f]">{{ $period === 'today' ? 'Hari ini' : ($period === '30' ? 'Dalam 30 hari terakhir' : 'Dalam 7 hari terakhir') }}</p>
    </div>

    <div class="rounded-[10px] border border-[#e0e5f0] bg-white p-4">
        <div class="flex items-start gap-3">
            <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] bg-[#fff2db] text-xs font-semibold text-[#21265d]">…</span>
            <div>
                <p class="text-xs text-[#6e7a8f]">Menunggu verifikasi</p>
                <p class="mt-0.5 text-[22px] font-semibold text-[#212b3b]">{{ $pendingCount }}</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-[#6e7a8f]">Bukti menunggu Finance</p>
    </div>

    <div class="rounded-[10px] border border-[#e0e5f0] bg-white p-4">
        <div class="flex items-start gap-3">
            <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] bg-[#ffe8e5] text-xs font-semibold text-[#21265d]">!</span>
            <div>
                <p class="text-xs text-[#6e7a8f]">Perlu tindak lanjut</p>
                <p class="mt-0.5 text-[22px] font-semibold text-[#212b3b]">{{ $rejectedCount }}</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-[#6e7a8f]">Perlu catatan / pemeriksaan</p>
    </div>
</section>

{{-- Chart + Outlet Summary --}}
<section class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,730fr)_minmax(300px,376fr)]">
    {{-- Revenue Trend Bar Chart --}}
    <div class="rounded-[10px] border border-[#e0e5f0] bg-white p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-[17px] font-semibold text-[#212b3b]">Pendapatan terverifikasi</h2>
                <p class="mt-0.5 text-xs text-[#6e7a8f]">Pergerakan 7 hari • transaksi Paid</p>
            </div>
            <p class="text-lg font-semibold text-[#21265d]">{{ $formatShort($verifiedRevenue) }}</p>
        </div>

        <div class="mt-6 grid h-[170px] grid-cols-7 items-end gap-3 border-b border-[#e0e5f0] pb-3 pt-4">
            @foreach ($bars as $bar)
                <div class="flex h-full flex-col items-center justify-end gap-1.5">
                    <span class="text-[9px] text-[#6e7a8f]">{{ $bar['short'] }}</span>
                    <div class="w-8 rounded-[5px] {{ $bar['is_today'] ? 'bg-[#21265d]' : 'bg-[#e8edff]' }}"
                         style="height: {{ $bar['height_pct'] }}%"
                         title="Rp{{ number_format($bar['value'], 0, ',', '.') }}"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 grid grid-cols-7 gap-3 text-center text-[10px] text-[#6e7a8f]">
            @foreach ($bars as $bar)
                <span>{{ $bar['day'] }}</span>
            @endforeach
        </div>
    </div>

    {{-- Ringkasan per outlet --}}
    <div class="flex flex-col rounded-[10px] border border-[#e0e5f0] bg-white p-5">
        <h2 class="text-[17px] font-semibold text-[#212b3b]">Ringkasan per outlet</h2>
        <p class="mt-0.5 text-xs text-[#6e7a8f]">Pembayaran terverifikasi</p>

        <div class="mt-5 divide-y divide-[#e0e5f0]">
            @foreach ($outletSummary as $item)
                <div class="flex items-center justify-between gap-3 py-3.5 first:pt-0">
                    <div class="flex items-center gap-3">
                        <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] text-sm font-semibold text-[#21265d] {{ $item['is_ibis'] ? 'bg-[#e3f5eb]' : 'bg-[#e8edff]' }}">
                            {{ $item['initial'] }}
                        </span>
                        <div>
                            <p class="text-[13px] font-semibold text-[#212b3b]">{{ $item['name'] }}</p>
                            <p class="text-[11px] text-[#6e7a8f]">{{ $item['count'] }} transaksi</p>
                        </div>
                    </div>
                    <p class="text-[13px] font-semibold text-[#212b3b]">{{ $formatShort($item['total']) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-auto border-t border-[#e0e5f0] pt-4">
            <p class="text-[11px] text-[#6e7a8f]">Total Paid tercatat</p>
            <p class="mt-1 text-xs font-semibold text-[#21265d]">{{ $paidCount }} transaksi • {{ $formatShort($verifiedRevenue) }}</p>
        </div>
    </div>
</section>

{{-- Recent Transactions --}}
<section class="mt-5 rounded-[10px] border border-[#e0e5f0] bg-white p-5">
    <h2 class="text-[17px] font-semibold text-[#212b3b]">Transaksi terbaru</h2>
    <p class="mt-0.5 text-xs text-[#6e7a8f]">Status pembayaran dan nominal dari transaksi aktual.</p>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[760px] border-collapse text-left">
            <thead>
                <tr class="rounded bg-[#f6f8fc] text-[10px] font-semibold uppercase tracking-wide text-[#6e7a8f]">
                    <th class="px-3 py-2.5">Reservasi / Tamu</th>
                    <th class="px-3 py-2.5">Outlet</th>
                    <th class="px-3 py-2.5">Metode</th>
                    <th class="px-3 py-2.5">Nominal</th>
                    <th class="px-3 py-2.5">Waktu</th>
                    <th class="px-3 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e0e5f0] text-[11px]">
                @forelse ($recentTransactions as $tx)
                    @php
                        $badge = match ($tx->payment_status) {
                            'paid' => ['Paid', 'bg-[#e3f5eb] text-[#147a4f]'],
                            'rejected' => ['Ditolak', 'bg-[#ffe8e8] text-[#ab3333]'],
                            'pending_verification' => ['Menunggu', 'bg-[#fff2db] text-[#9e5c14]'],
                            default => ['Unpaid', 'bg-slate-100 text-slate-600'],
                        };
                    @endphp
                    <tr class="hover:bg-[#f6f8fc]">
                        <td class="px-3 py-3.5 font-medium text-[#212b3b]">
                            {{ $tx->reservation?->reservation_code ?? '—' }} • {{ $tx->reservation?->customer_name ?? '—' }}
                        </td>
                        <td class="px-3 py-3.5 text-[#212b3b]">{{ $tx->reservation?->outlet?->name ?? '—' }}</td>
                        <td class="px-3 py-3.5 text-[#6e7a8f]">{{ str_replace('_', ' ', ucfirst((string) $tx->payment_method)) }}</td>
                        <td class="px-3 py-3.5 font-medium text-[#212b3b]">Rp{{ number_format((float) $tx->amount, 0, ',', '.') }}</td>
                        <td class="px-3 py-3.5 text-[#6e7a8f]">{{ ($tx->verified_at ?? $tx->proof_uploaded_at ?? $tx->created_at)?->format('H.i') ?? '—' }} WIB</td>
                        <td class="px-3 py-3.5">
                            <span class="inline-flex rounded-full px-3 py-0.5 text-[10px] font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs text-[#6e7a8f]">Belum ada transaksi tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
