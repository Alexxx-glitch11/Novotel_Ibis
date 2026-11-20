@extends('layouts.finance')

@section('title', 'Riwayat pembayaran')

@section('content')
<div>
    <h1 class="text-[26px] font-semibold leading-[34px] text-[#212b3b]">Riwayat verifikasi pembayaran</h1>
    <p class="mt-1 text-sm text-[#6e7a8f]">Telusuri status, petugas, waktu, dan catatan pada setiap keputusan Finance.</p>
</div>

{{-- 3 Metric Cards --}}
<section class="mt-5 grid gap-4 md:grid-cols-3">
    <div class="h-[104px] rounded-2xl border border-[#e0e5f0] bg-white px-5 py-4">
        <p class="text-xs text-[#6e7a8f]">Paid hari ini</p>
        <p class="mt-1 text-2xl font-semibold text-[#212b3b]">{{ $metrics['paid_today'] }}</p>
        <p class="mt-1 text-[11px] text-[#6e7a8f]">Transaksi terverifikasi</p>
    </div>

    <div class="h-[104px] rounded-2xl border border-[#e0e5f0] bg-white px-5 py-4">
        <p class="text-xs text-[#6e7a8f]">Total Paid</p>
        <p class="mt-1 text-2xl font-semibold text-[#212b3b]">{{ $metrics['paid_total'] ?? 0 }}</p>
        <p class="mt-1 text-[11px] text-[#6e7a8f]">Semua waktu</p>
    </div>

    <div class="h-[104px] rounded-2xl border border-[#e0e5f0] bg-white px-5 py-4">
        <p class="text-xs text-[#6e7a8f]">Rejected / follow-up</p>
        <p class="mt-1 text-2xl font-semibold text-[#212b3b]">{{ $metrics['rejected'] }}</p>
        <p class="mt-1 text-[11px] text-[#6e7a8f]">Memerlukan tindak lanjut</p>
    </div>
</section>

{{-- Payment History Table Card --}}
<section class="mt-5 rounded-2xl border border-[#e0e5f0] bg-white p-6">
    <h2 class="text-lg font-semibold text-[#212b3b]">Semua keputusan pembayaran</h2>

    <form method="GET" action="{{ route('finance.history.index') }}" class="mt-4 flex flex-wrap items-center gap-2.5">
        <div class="relative min-w-[220px] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-[18px] -translate-y-1/2 text-[#64748b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode reservasi atau nama tamu…"
                   class="h-[38px] w-full rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] pl-10 pr-3.5 text-xs text-[#212b3b] placeholder-[#6e7a8f] focus:border-[#21265d] focus:bg-white focus:outline-none">
        </div>

        <input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal"
               class="h-[38px] w-[140px] shrink-0 rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] px-3 text-xs text-[#212b3b] focus:border-[#21265d] focus:bg-white focus:outline-none">

        <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal"
               class="h-[38px] w-[140px] shrink-0 rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] px-3 text-xs text-[#212b3b] focus:border-[#21265d] focus:bg-white focus:outline-none">

        <select name="status" aria-label="Filter status"
                class="h-[38px] w-[155px] shrink-0 rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] px-3 text-xs text-[#212b3b] focus:border-[#21265d] focus:bg-white focus:outline-none">
            <option value="all" @selected($status === 'all')>Semua status</option>
            <option value="paid" @selected($status === 'paid')>Paid</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected / Follow-up</option>
        </select>

        <select name="outlet" aria-label="Filter outlet"
                class="h-[38px] w-[145px] shrink-0 rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] px-3 text-xs text-[#212b3b] focus:border-[#21265d] focus:bg-white focus:outline-none">
            <option value="">Semua outlet</option>
            @foreach ($outlets as $o)
                <option value="{{ $o->outlets_id }}" @selected((string) $outletId === (string) $o->outlets_id)>{{ $o->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="h-[38px] shrink-0 rounded-[9px] bg-[#21265d] px-4 text-xs font-semibold text-white hover:bg-[#171c4f]">
            Terapkan
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full min-w-[780px] border-collapse text-left">
            <thead>
                <tr class="border-b border-[#e0e5f0] text-[10px] font-semibold uppercase tracking-wide text-[#6e7a8f]">
                    <th class="py-2.5 pr-3">Kode / Tamu</th>
                    <th class="py-2.5 pr-3">Outlet</th>
                    <th class="py-2.5 pr-3">Nominal</th>
                    <th class="py-2.5 pr-3">Status</th>
                    <th class="py-2.5 pr-3">Diverifikasi Oleh</th>
                    <th class="py-2.5">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e0e5f0] text-[11px]">
                @forelse ($payments as $row)
                    @php
                        $statusInfo = match ($row->payment_status) {
                            'paid' => ['Paid', 'text-[#147a4f]'],
                            'rejected' => ['Rejected / Follow-up', 'text-[#ab3333]'],
                            'pending_verification' => ['Pending', 'text-[#9e5c14]'],
                            default => ['Unpaid', 'text-[#6e7a8f]'],
                        };
                    @endphp
                    <tr class="hover:bg-[#f6f8fc]">
                        <td class="py-4 pr-3 font-semibold text-[#212b3b]">
                            <a href="{{ route('finance.payments.show', $row) }}" class="hover:text-[#21265d] hover:underline">
                                {{ $row->reservation?->reservation_code ?? '—' }} • {{ $row->reservation?->customer_name ?? '—' }}
                            </a>
                            @if ($row->rejection_reason)
                                <span class="mt-0.5 block font-normal text-[#ab3333]">Catatan: {{ \Illuminate\Support\Str::limit($row->rejection_reason, 70) }}</span>
                            @endif
                        </td>
                        <td class="py-4 pr-3 text-[#6e7a8f]">{{ $row->reservation?->outlet?->name ?? '—' }}</td>
                        <td class="py-4 pr-3 font-semibold text-[#212b3b]">Rp{{ number_format((float) $row->amount, 0, ',', '.') }}</td>
                        <td class="py-4 pr-3 text-[10px] font-semibold {{ $statusInfo[1] }}">{{ $statusInfo[0] }}</td>
                        <td class="py-4 pr-3 text-[#6e7a8f]">{{ $row->verifiedBy?->name ?? '—' }}</td>
                        <td class="py-4 text-[#6e7a8f]">{{ ($row->verified_at ?? $row->proof_uploaded_at)?->format('d/m/Y H.i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-xs text-[#6e7a8f]">Belum ada riwayat pembayaran yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>
</section>

<p class="mt-5 text-xs text-[#6e7a8f]">Riwayat bersifat telusur; perubahan status menyimpan petugas, waktu, dan alasan.</p>
@endsection
