@extends('layouts.manager')

@section('title', 'Ringkasan operasional')
@section('active-outlet', $selected?->name ?? 'Semua outlet')

@section('content')
    @php
        $tones = [
            'blue'  => 'bg-indigo-50 text-[#1f1d5c]',
            'amber' => 'bg-amber-50 text-amber-700',
            'green' => 'bg-emerald-50 text-emerald-700',
        ];
        $barColors = ['bg-indigo-100', 'bg-emerald-100', 'bg-violet-100'];
        $max = max(array_column($revenue, 'value') ?: [1]) ?: 1;
    @endphp

    {{-- Judul + filter outlet --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#14123f]">Ringkasan operasional</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau reservasi, keputusan, dan performa outlet.</p>
        </div>
        <form method="GET" action="{{ route('manager.dashboard') }}">
            <select name="outlet" aria-label="Filter outlet" onchange="this.form.submit()"
                    class="cursor-pointer rounded-lg border-0 bg-transparent py-2 pr-2 text-sm font-semibold text-[#14123f] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                <option value="">Semua outlet</option>
                @foreach ($outlets as $o)
                    <option value="{{ $o->outlets_id }}" @selected($selected && $selected->outlets_id === $o->outlets_id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $s)
            <div class="flex items-start gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl text-sm font-bold {{ $tones[$s['tone']] }}">{{ $s['letter'] }}</span>
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">{{ $s['label'] }}</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-[#14123f]">{{ $s['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $s['note'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

        {{-- ===== KIRI ===== --}}
        <div class="space-y-6">

            {{-- Permintaan pembatalan --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <h2 class="text-lg font-bold">Permintaan pembatalan</h2>
                <p class="text-sm text-slate-500">Tinjau alasan sebelum menyetujui atau menolak.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead>
                            <tr class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                                <th scope="col" class="py-2 pr-3">Tamu</th>
                                <th scope="col" class="py-2 pr-3">Outlet / jadwal</th>
                                <th scope="col" class="py-2 pr-3">Alasan</th>
                                <th scope="col" class="py-2 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($cancellations as $c)
                                <tr>
                                    <td class="py-4 pr-3 font-semibold">{{ $c['guest'] }}</td>
                                    <td class="py-4 pr-3 text-slate-500">{{ $c['slot'] }}</td>
                                    <td class="py-4 pr-3 text-slate-500">{{ $c['reason'] }}</td>
                                    <td class="py-4 text-right">
                                        <a href="#" class="inline-block rounded-lg bg-indigo-50 px-4 py-2 text-xs font-semibold text-[#1f1d5c] transition hover:bg-indigo-100">Tinjau →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-slate-400">Tidak ada permintaan pembatalan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Pendapatan terverifikasi --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold">Pendapatan terverifikasi</h2>
                        <p class="text-sm text-slate-500">Perbandingan 7 hari terakhir</p>
                    </div>
                    <p class="text-2xl font-bold text-[#14123f]">{{ $revenueTotal }}</p>
                </div>

                <div class="mt-6" role="img" aria-label="Grafik batang pendapatan terverifikasi 7 hari terakhir">
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

        {{-- ===== KANAN ===== --}}
        <div class="space-y-6">

            {{-- Tarif per outlet --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold">Tarif per outlet</h2>
                    <a href="#" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-[#14123f] transition hover:bg-slate-200">Ubah tarif</a>
                </div>
                <p class="mt-1 text-xs text-slate-400">Contoh tarif; nilai resmi menunggu validasi hotel.</p>

                <div class="mt-4 space-y-4">
                    @foreach ($rates as $r)
                        <div>
                            <p class="text-sm font-bold">{{ $r['outlet'] }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#14123f]">
                                Pajak {{ $r['tax'] }} <span class="ml-3">Layanan {{ $r['service'] }}</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Aktivitas terbaru --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <h2 class="text-lg font-bold">Aktivitas terbaru</h2>
                <p class="text-xs text-slate-400">Keputusan sensitif tercatat otomatis.</p>

                 <ol class="mt-4 space-y-4">
                    @forelse ($activities as $a)
                        <li class="flex gap-4 text-sm">
                        <time class="w-14 shrink-0 text-xs text-slate-400">{{ $a['time'] }}</time>
                        <span>{{ $a['text'] }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Belum ada aktivitas.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>

    <p class="mt-6 text-xs text-slate-500">Laporan hanya menghitung pembayaran Paid; transaksi lama tetap memakai snapshot tarif.</p>
@endsection


@push('scripts')
<script>
window.addEventListener('load', () => {
    const main = () => document.querySelector('main');
    let timer = null;

    async function refresh() {
        try {
            const res = await fetch(location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
            if (!res.ok) return;
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const fresh = doc.querySelector('main');
            if (fresh && main()) main().replaceChildren(...fresh.childNodes);
        } catch (e) { /* abaikan, akan dicoba lagi pada sinyal berikutnya */ }
    }

    // Satu reservasi bisa memicu beberapa sinyal beruntun: gabungkan jadi satu pembaruan.
    function scheduleRefresh() {
        clearTimeout(timer);
        timer = setTimeout(refresh, 400);
    }

    if (window.Echo) {
        window.Echo.channel('manager-dashboard').listen('.updated', scheduleRefresh);
    }

    // Cadangan: kalau koneksi WebSocket sempat putus, tetap segarkan tiap 60 detik.
    setInterval(() => { if (!document.hidden) refresh(); }, 60000);
});
</script>
@endpush