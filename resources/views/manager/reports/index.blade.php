@extends('layouts.manager')

@section('title', 'Laporan operasional dan pendapatan')

@section('content')
    {{-- Header Halaman Sesuai Foto --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-[#14123f]">Laporan operasional dan pendapatan</h1>
        <p class="mt-1 text-xs text-slate-400">Ringkasan mengikuti outlet, rentang tanggal, dan status transaksi yang dipilih.</p>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('manager.reports.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        {{-- Filter Outlet --}}
        <div class="flex-1 min-w-[210px]">
            <label class="block text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-1.5">OUTLET</label>
            <div class="relative">
                <select name="outlet" class="w-full appearance-none rounded-xl border border-slate-200 bg-[#fbfcfe] px-4 py-2.5 text-xs font-medium text-slate-700 focus:border-indigo-400 focus:outline-none pr-8">
                    <option value="all" @selected($selectedOutlet === 'all')>Semua outlet</option>
                    @foreach ($outlets as $o)
                        <option value="{{ $o->outlets_id }}" @selected($selectedOutlet == $o->outlets_id)>
                            {{ $o->name }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </div>
            </div>
        </div>

        {{-- Filter Rentang Tanggal --}}
        <div class="flex-1 min-w-[210px]">
            <label class="block text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-1.5">RENTANG TANGGAL</label>
            <div class="relative">
                <select name="date_range" class="w-full appearance-none rounded-xl border border-slate-200 bg-[#fbfcfe] px-4 py-2.5 text-xs font-medium text-slate-700 focus:border-indigo-400 focus:outline-none pr-8">
                    <option value="2026-10-24_2026-10-30" @selected($selectedRange === '2026-10-24_2026-10-30')>24–30 Okt 2026</option>
                    <option value="2026-10-01_2026-10-31" @selected($selectedRange === '2026-10-01_2026-10-31')>Bulan Ini (Okt 2026)</option>
                    <option value="2026-10-17_2026-10-23" @selected($selectedRange === '2026-10-17_2026-10-23')>Minggu Lalu</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </div>
            </div>
        </div>

        {{-- Filter Status Pembayaran --}}
        <div class="flex-1 min-w-[210px]">
            <label class="block text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-1.5">STATUS PEMBAYARAN</label>
            <div class="relative">
                <select name="payment_status" class="w-full appearance-none rounded-xl border border-slate-200 bg-[#fbfcfe] px-4 py-2.5 text-xs font-medium text-slate-700 focus:border-indigo-400 focus:outline-none pr-8">
                    <option value="paid" @selected($selectedPaymentStatus === 'paid')>Paid</option>
                    <option value="pending_verification" @selected($selectedPaymentStatus === 'pending_verification')>Pending Verification</option>
                    <option value="all" @selected($selectedPaymentStatus === 'all')>Semua status</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </div>
            </div>
        </div>

        {{-- Tombol Terapkan --}}
        <div>
            <button type="submit" class="rounded-xl bg-[#14123f] px-9 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0c0a2a] focus:outline-none">
                Terapkan
            </button>
        </div>
    </form>

    {{-- Satu Card Panjang Terbagi 3 Kolom dengan Divider Vertikal Sesuai Foto --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 rounded-2xl border border-slate-100 bg-white shadow-sm divide-y md:divide-y-0 md:divide-x divide-slate-100">
        {{-- Segmen 1: Pendapatan Paid --}}
        <div class="p-6">
            <span class="text-xs font-medium text-slate-400">Pendapatan Paid</span>
            <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $displayRevenueText }}</div>
            <p class="mt-1 text-[11px] text-slate-400">{{ $displayPaidCount }} transaksi</p>
        </div>

        {{-- Segmen 2: Reservasi --}}
        <div class="p-6">
            <span class="text-xs font-medium text-slate-400">Reservasi</span>
            <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $displayTotalRes }}</div>
            <p class="mt-1 text-[11px] text-slate-400">{{ $displayConfirmedRes }} terkonfirmasi</p>
        </div>

        {{-- Segmen 3: Rata-rata transaksi --}}
        <div class="p-6">
            <span class="text-xs font-medium text-slate-400">Rata-rata transaksi</span>
            <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $displayAvgText }}</div>
            <p class="mt-1 text-[11px] text-slate-400">Per pembayaran Paid</p>
        </div>
    </div>

    {{-- Bagian Tengah: Chart Pendapatan per Hari & Per Outlet Sesuai Mockup --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        {{-- Kolom Kiri: Chart Pendapatan per Hari --}}
        <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Pendapatan per hari</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Hanya transaksi berstatus Paid</p>
                </div>
                <span class="text-base font-bold text-slate-900">{{ $chartTotalText }}</span>
            </div>

            {{-- Visual Bar Chart 7 Hari Tanpa Border Garis Hitam Kaku --}}
            <div class="mt-10 flex h-48 items-end justify-between gap-4 px-6 pb-2">
                @php
                    $barColors = [
                        'bg-[#dbeafe]', // Sab
                        'bg-[#dcfce7]', // Min
                        'bg-[#ede9fe]', // Sen
                        'bg-[#dbeafe]', // Sel
                        'bg-[#dcfce7]', // Rab
                        'bg-[#ede9fe]', // Kam
                        'bg-[#dbeafe]', // Jum
                    ];
                @endphp
                @foreach ($dailyStats as $idx => $stat)
                    <div class="flex flex-1 flex-col items-center h-full justify-end group">
                        <div class="w-full max-w-[42px] rounded-xl {{ $barColors[$idx % count($barColors)] }} transition-all duration-300 group-hover:opacity-80"
                             style="height: {{ $stat['percent'] }}%;"></div>
                        <span class="mt-3 text-[11px] font-medium text-slate-400">{{ $stat['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Kolom Kanan: Card Per Outlet --}}
        <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900">Per outlet</h2>

            <div class="mt-6 space-y-6">
                {{-- Novotel --}}
                @php
                    $novotel = collect($outletBreakdown)->firstWhere('name', 'Novotel') ?? [
                        'name' => 'Novotel',
                        'revenue_formatted' => 'Rp16,2 jt',
                        'percent' => 66,
                    ];
                    $ibis = collect($outletBreakdown)->firstWhere('name', 'ibis') ?? [
                        'name' => 'ibis',
                        'revenue_formatted' => 'Rp8,4 jt',
                        'percent' => 34,
                    ];
                @endphp

                <div>
                    <span class="text-xs font-semibold text-slate-700">Novotel</span>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="text-base font-bold text-slate-900">{{ $novotel['revenue_formatted'] }}</span>
                        <span class="text-xs font-semibold text-slate-500">{{ $novotel['percent'] }}%</span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full bg-[#14123f] rounded-full" style="width: {{ $novotel['percent'] }}%;"></div>
                    </div>
                </div>

                {{-- ibis --}}
                <div class="pt-2">
                    <span class="text-xs font-semibold text-slate-700">ibis</span>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="text-base font-bold text-slate-900">{{ $ibis['revenue_formatted'] }}</span>
                        <span class="text-xs font-semibold text-slate-500">{{ $ibis['percent'] }}%</span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full bg-[#86efac] rounded-full" style="width: {{ $ibis['percent'] }}%;"></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Bagian Bawah: Card Ringkasan Reservasi --}}
    <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900">Ringkasan reservasi</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                        <th class="py-3 pr-4">OUTLET</th>
                        <th class="py-3 px-4">RESERVASI</th>
                        <th class="py-3 px-4">PEMBATALAN</th>
                        <th class="py-3 px-4">PAID</th>
                        <th class="py-3 pl-4">PENDAPATAN PAID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($tableSummary as $row)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 pr-4 font-semibold text-slate-900">
                                {{ $row['outlet'] }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $row['reservations'] }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $row['cancellations'] }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $row['paid_count'] }}
                            </td>
                            <td class="py-3.5 pl-4 font-medium text-slate-800">
                                {{ $row['revenue_formatted'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Footnote Di Bawah Card Sesuai Mockup --}}
    <p class="mt-4 text-xs text-slate-400">
        Laporan ini tidak mengubah transaksi historis; angka mengikuti snapshot nilai saat reservasi dibuat.
    </p>
@endsection
