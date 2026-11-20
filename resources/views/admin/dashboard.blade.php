@extends('layouts.admin')
@section('title', 'Ringkasan operasional')
@section('content')
@php
    $tones = [
        'blue'  => 'bg-indigo-50 text-[#1f1d5c]',
        'amber' => 'bg-amber-50 text-amber-700',
        'green' => 'bg-emerald-50 text-emerald-700',
    ];
    $barColors = ['bg-indigo-100', 'bg-emerald-100', 'bg-violet-100'];
    $max = max(array_column($revenue, 'value') ?: [1]) ?: 1;
    $configBadge = 'Hold ' . $holdMinutes . ' mnt' . ($bankName ? ' • ' . $bankName : '');
@endphp

<div class="flex flex-wrap items-start justify-between gap-3">
    <header class="admin-page-header" style="margin-bottom:0;">
        <h1>Ringkasan operasional</h1>
        <p>Pantau reservasi, pendapatan, dan kelola data master serta konfigurasi RRMS.</p>
    </header>
    @if ($outlets->count() > 1)
        <form method="GET" action="{{ route('admin.dashboard') }}">
            <select name="outlet" aria-label="{{ __('manager.filter_outlet') }}" onchange="this.form.submit()"
                    class="cursor-pointer rounded-lg border-0 bg-transparent py-2 pr-2 text-sm font-semibold text-[#14123f] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                <option value="">{{ __('manager.all_outlets') }}</option>
                @foreach ($outlets as $o)
                    <option value="{{ $o->outlets_id }}" @selected($selected && $selected->outlets_id === $o->outlets_id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </form>
    @elseif ($selected)
        <span class="rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-[#14123f] ring-1 ring-slate-200">{{ $selected->name }}</span>
    @endif
</div>

{{-- 4 KPI Operasional (logika sama dengan Dashboard Manager) --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as $s)
        <div class="flex items-start gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200">
            <span translate="no" class="grid size-10 shrink-0 place-items-center rounded-xl text-sm font-bold {{ $tones[$s['tone']] }}">{{ $s['letter'] }}</span>
            <div class="min-w-0">
                <p class="text-sm text-slate-500">{{ $s['label'] }}</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-[#14123f]">{{ $s['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $s['note'] }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- Panel Operasional: Permintaan Pembatalan + Grafik Pendapatan & Tarif/Aktivitas --}}
<div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
            <h2 class="text-lg font-bold">{{ __('manager.cancel_title') }}</h2>
            <p class="text-sm text-slate-500">{{ __('manager.cancel_sub') }}</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead>
                        <tr class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                            <th scope="col" class="py-2 pr-3">{{ __('manager.col_guest') }}</th>
                            <th scope="col" class="py-2 pr-3">{{ __('manager.col_slot') }}</th>
                            <th scope="col" class="py-2 pr-3">{{ __('manager.col_reason') }}</th>
                            <th scope="col" class="py-2 text-right">{{ __('manager.col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cancellations as $c)
                            <tr>
                                <td class="py-4 pr-3 font-semibold" translate="no">{{ $c['guest'] }}</td>
                                <td class="py-4 pr-3 text-slate-500">{{ $c['slot'] }}</td>
                                <td class="py-4 pr-3 text-slate-500">{{ $c['reason'] }}</td>
                                <td class="py-4 text-right">
                                    <a href="{{ route('admin.reservations.show', array_filter(['reservation' => $c['reservation_id'], 'outlet' => $selected?->outlets_id])) }}"
                                       class="inline-block rounded-lg bg-indigo-50 px-4 py-2 text-xs font-semibold text-[#1f1d5c] transition hover:bg-indigo-100">{{ __('manager.review') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-slate-400">{{ __('manager.no_cancel') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">{{ __('manager.rev_title') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('manager.rev_sub') }}</p>
                </div>
                <p class="text-2xl font-bold text-[#14123f]">{{ $revenueTotal }}</p>
            </div>

            <div class="mt-6" role="img" aria-label="{{ __('manager.rev_aria') }}">
                <div class="flex h-44 items-end gap-3 sm:gap-5">
                    @foreach ($revenue as $i => $r)
                        <div class="flex-1 rounded-t-lg {{ $barColors[$i % 3] }}"
                             style="height: {{ max(4, round($r['value'] / $max * 100)) }}%"
                             title="{{ $r['day'] }}: {{ $r['label'] }}"></div>
                    @endforeach
                </div>
                <div class="mt-2 flex gap-3 sm:gap-5">
                    @foreach ($revenue as $r)
                        <span class="flex-1 text-center text-[11px] text-slate-400">{{ $r['day'] }}</span>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-lg font-bold">{{ __('manager.rates_title') }}</h2>
            </div>
            <p class="mt-1 text-xs text-slate-400">{{ __('manager.rates_note') }}</p>

            <div class="mt-4 space-y-4">
                @forelse ($rates as $r)
                    <div>
                        <p class="text-sm font-bold">{{ $r['outlet'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-[#14123f]">
                            {{ __('manager.tax_word') }} {{ $r['tax'] }} <span class="ml-3">{{ __('manager.service_word') }} {{ $r['service'] }}</span>
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Tidak ada outlet aktif.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">{{ __('manager.act_title') }}</h2>
                    <p class="text-xs text-slate-400">{{ __('manager.act_sub') }}</p>
                </div>
                <a class="text-xs font-semibold text-[#21265d] hover:underline" href="{{ route('admin.audit.index', $outletParams) }}">Lihat semua →</a>
            </div>

            <ol class="mt-4 space-y-3.5">
                @forelse ($activities as $a)
                    <li class="flex gap-3 text-sm">
                        <time class="w-14 shrink-0 text-xs text-slate-400">{{ $a['time'] }}</time>
                        <span>{{ $a['text'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">{{ __('manager.no_activity') }}</li>
                @endforelse
            </ol>
        </section>
    </div>
</div>

{{-- Shortcut Administrasi Master Data & Konfigurasi --}}
<div class="admin-shortcuts mt-6">
    @foreach ([
        ['01','Outlet & area','Kelola outlet, area, status aktif, dan informasi restoran.',$outletCount.' outlet • '.$areaCount.' area','admin.outlets.index'],
        ['02','Meja & denah','Atur kode meja, kapasitas, area, dan tata letak denah.',$tableCount.' meja','admin.tables.index'],
        ['03','Menu & kategori','Kelola kategori, harga, outlet, dan ketersediaan menu.',$menuCount.' menu aktif','admin.menu.index'],
        ['04','Reservasi','Cari reservasi, pindai QR, dan lihat detail meja, menu/event, serta total harga.',$reservationCount.' reservasi','admin.reservations.index'],
        ['05','Pengguna & role','Buat akun, tetapkan role, dan kelola status akses.',$userCount.' akun','admin.users.index'],
        ['06','Promosi & acara','Perbarui konten promosi dan acara pada halaman publik.',$contentCount.' konten','admin.content.index'],
        ['07','Konfigurasi sistem','Atur parameter reservasi dan rekening bank pelanggan.',$configBadge,'admin.configuration.index'],
    ] as [$number,$title,$description,$count,$route])
        <a class="admin-card admin-shortcut" href="{{ route($route, $outletParams) }}"><span class="admin-number" translate="no">{{ $number }}</span><strong>{{ $title }}</strong><p>{{ $description }}</p><b>{{ $count }}</b><span class="admin-manage">Kelola →</span></a>
    @endforeach
</div>

<p class="admin-footnote">Ringkasan operasional selaras antara Admin dan Manager. Admin mengelola akun, data master, serta konfigurasi &amp; rekening bank pelanggan; Manager memutuskan tarif pajak/layanan dan pembatalan.</p>
@endsection
