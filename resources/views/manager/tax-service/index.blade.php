@extends('layouts.manager')

@section('title', 'Tarif pajak dan biaya layanan')

@section('content')
    {{-- Header Halaman Sesuai Foto --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#14123f]">Tarif pajak dan biaya layanan</h1>
            <p class="mt-1 text-xs text-slate-400">Atur nilai untuk reservasi baru; transaksi yang sudah dibuat tetap memakai snapshot tarif.</p>
        </div>

        {{-- Dropdown Outlet di Kanan Atas --}}
        <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
            <span>Semua outlet</span>
            <svg class="size-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>

    {{-- Pesan Flash Feedback --}}
    @if (session('success'))
        <div class="mt-4 flex items-center gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">
            <svg class="size-4 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-red-50 p-4 text-xs text-red-800 ring-1 ring-red-200">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Grid 2 Kolom: Kartu Novotel & ibis --}}
    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        @foreach ($displayOutlets as $outlet)
            @php
                $rate = $outletRates[$outlet->outlets_id] ?? null;
                $outletDisplayName = str_contains(strtolower($outlet->name), 'ibis') ? 'ibis' : 'Novotel';
            @endphp
            <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                {{-- Header Kartu: Nama Outlet & Badge --}}
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">{{ $outletDisplayName }}</h2>
                    <span class="rounded-full bg-[#fef3e2] px-3 py-1 text-[11px] font-medium text-[#c28422]">
                        Perlu validasi
                    </span>
                </div>

                <form method="POST" action="{{ route('manager.tax-service.update', $outlet) }}" class="mt-5 space-y-4">
                    @csrf
                    {{-- Input Tarif Pajak & Biaya Layanan --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Tarif pajak (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate"
                                   value="{{ old('tax_rate', $rate ? (float) $rate->tax_rate : '') }}"
                                   placeholder="Masukkan tarif" required
                                   class="mt-1.5 w-full rounded-xl border border-slate-200 bg-[#fbfcfe] px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Biaya layanan (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="service_rate"
                                   value="{{ old('service_rate', $rate ? (float) $rate->service_rate : '') }}"
                                   placeholder="Masukkan tarif" required
                                   class="mt-1.5 w-full rounded-xl border border-slate-200 bg-[#fbfcfe] px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                    </div>

                    {{-- Tanggal Berlaku Mulai & Tombol Simpan --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_auto] items-end pt-1">
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Berlaku mulai</label>
                            <input type="date" name="effective_from"
                                   value="{{ old('effective_from', $rate ? $rate->effective_from?->toDateString() : now()->toDateString()) }}"
                                   required
                                   class="mt-1.5 w-full rounded-xl border border-slate-200 bg-[#fbfcfe] px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                        <button type="submit"
                                class="rounded-xl bg-[#14123f] px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0c0a2a] focus:outline-none">
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>
        @endforeach
    </div>

    {{-- Banner Catatan Transaksi Baru --}}
    <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Perubahan tarif berlaku untuk transaksi baru</h3>
        <p class="mt-0.5 text-xs text-slate-400">Reservasi lama mempertahankan harga menu, pajak, dan biaya layanan saat dibuat.</p>
    </div>

    {{-- Card Riwayat Perubahan Tarif --}}
    <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900">Riwayat perubahan tarif</h2>
        <p class="mt-0.5 text-xs text-slate-400">Setiap perubahan menyimpan pengguna, waktu, nilai lama, dan nilai baru.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                        <th class="py-3 pr-4">TANGGAL</th>
                        <th class="py-3 px-4">OUTLET</th>
                        <th class="py-3 px-4">PERUBAHAN</th>
                        <th class="py-3 px-4">NILAI LAMA → BARU</th>
                        <th class="py-3 pl-4">DIUBAH OLEH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($history as $log)
                        @php
                            $old = $log->old_values ?? [];
                            $new = $log->new_values ?? [];
                            $outletLabel = $new['outlet_name'] ?? ($old['outlet_name'] ?? 'Novotel');
                            $cleanOutlet = str_contains(strtolower($outletLabel), 'ibis') ? 'ibis' : 'Novotel';
                            $field = $new['field'] ?? ($old['field'] ?? 'Pajak');
                            $oldVal = $old['tax_rate'] ?? ($old['service_rate'] ?? '—');
                            $newVal = $new['tax_rate'] ?? ($new['service_rate'] ?? '—');
                            $operator = $new['operator'] ?? ($log->user?->email ?? ($log->user?->name ?? 'manager@' . strtolower($cleanOutlet)));
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 pr-4 text-slate-800 font-medium">
                                {{ $log->created_at->locale('id')->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $cleanOutlet }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $field }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-mono">
                                {{ $oldVal }} → {{ $newVal }}
                            </td>
                            <td class="py-3.5 pl-4 text-slate-400">
                                {{ $operator }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-slate-400">
                                Belum ada riwayat perubahan tarif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
