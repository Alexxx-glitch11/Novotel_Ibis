@extends('layouts.finance')

@section('title', 'Ringkasan Finance')

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
    <h1 class="text-[26px] font-semibold leading-[34px] text-[#212b3b]">Verifikasi pembayaran</h1>
    <p class="mt-1 text-sm text-[#6e7a8f]">Cocokkan bukti dengan transaksi aktual sebelum menetapkan status Paid.</p>
</div>
@if (!auth()->user()->hasRole('admin') && !app(\App\Services\OutletAccess::class)->outletIds(auth()->user()))
    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">Akun berhasil masuk, tetapi belum ditetapkan ke outlet. Minta Admin memilih outlet pada menu Pengguna agar antrean pembayaran dapat diakses.</div>
@endif

{{-- 4 Metric Cards --}}
<section class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="flex h-[112px] items-start gap-3.5 rounded-2xl border border-[#e0e5f0] bg-white p-[18px]">
        <span translate="no" class="grid size-[38px] shrink-0 place-items-center rounded-xl bg-[#fff2db] text-base font-semibold text-[#21265d]">M</span>
        <div class="min-w-0">
            <p class="text-[13px] text-[#6e7a8f]">Menunggu verifikasi</p>
            <p class="mt-0.5 text-[25px] font-semibold leading-tight text-[#212b3b]">{{ $metrics['pending'] }}</p>
            <p class="mt-1 text-[11px] text-[#6e7a8f]">Perlu pemeriksaan</p>
        </div>
    </div>

    <div class="flex h-[112px] items-start gap-3.5 rounded-2xl border border-[#e0e5f0] bg-white p-[18px]">
        <span translate="no" class="grid size-[38px] shrink-0 place-items-center rounded-xl bg-[#e3f5eb] text-base font-semibold text-[#21265d]">T</span>
        <div class="min-w-0">
            <p class="text-[13px] text-[#6e7a8f]">Terverifikasi hari ini</p>
            <p class="mt-0.5 text-[25px] font-semibold leading-tight text-[#212b3b]">{{ $metrics['verified_today'] }}</p>
            <p class="mt-1 text-[11px] text-[#6e7a8f]">Transaksi cocok</p>
        </div>
    </div>

    <div class="flex h-[112px] items-start gap-3.5 rounded-2xl border border-[#e0e5f0] bg-white p-[18px]">
        <span translate="no" class="grid size-[38px] shrink-0 place-items-center rounded-xl bg-[#ffe8e8] text-base font-semibold text-[#21265d]">P</span>
        <div class="min-w-0">
            <p class="text-[13px] text-[#6e7a8f]">Perlu tindak lanjut</p>
            <p class="mt-0.5 text-[25px] font-semibold leading-tight text-[#212b3b]">{{ $metrics['rejected'] }}</p>
            <p class="mt-1 text-[11px] text-[#6e7a8f]">Bukti tidak sesuai</p>
        </div>
    </div>

    <div class="flex h-[112px] items-start gap-3.5 rounded-2xl border border-[#e0e5f0] bg-white p-[18px]">
        <span translate="no" class="grid size-[38px] shrink-0 place-items-center rounded-xl bg-[#e8edff] text-base font-semibold text-[#21265d]">T</span>
        <div class="min-w-0">
            <p class="text-[13px] text-[#6e7a8f]">Total Paid hari ini</p>
            <p class="mt-0.5 text-[25px] font-semibold leading-tight text-[#212b3b]">{{ $formatShort($metrics['paid_today_total']) }}</p>
            <p class="mt-1 text-[11px] text-[#6e7a8f]">Setelah diverifikasi</p>
        </div>
    </div>
</section>

{{-- Queue + Detail & Bukti Transfer --}}
<section class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,700fr)_minmax(320px,406fr)]">
    {{-- Left: Antrean pembayaran --}}
    <div class="rounded-2xl border border-[#e0e5f0] bg-white p-5 md:p-[22px]">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 class="text-[17px] font-semibold text-[#212b3b]">Antrean pembayaran</h2>
                <p class="mt-1 text-xs text-[#6e7a8f]">Unggahan bukti tidak otomatis berarti pembayaran diterima.</p>
            </div>
            <a href="{{ route('finance.history.index', request()->only('outlet')) }}" class="text-xs font-semibold text-[#21265d] hover:underline">
                Lihat riwayat pembayaran →
            </a>
        </div>

        <form method="GET" action="{{ route('finance.dashboard') }}" class="mt-3.5 flex flex-wrap items-center gap-2 rounded-[9px] border border-[#e0e5f0] bg-[#f6f8fc] px-3 py-1.5">
            <select name="outlet" onchange="this.form.submit()" aria-label="Filter outlet" class="bg-transparent text-xs text-[#212b3b] focus:outline-none">
                <option value="">Semua outlet</option>
                @foreach ($outlets as $o)
                    <option value="{{ $o->outlets_id }}" @selected((string) $outletId === (string) $o->outlets_id)>{{ $o->name }}</option>
                @endforeach
            </select>
            <span class="text-[#e0e5f0]">|</span>
            <svg class="size-[18px] shrink-0 text-[#64748b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari reservasi…" class="min-w-[140px] flex-1 bg-transparent text-xs text-[#212b3b] placeholder-[#6e7a8f] focus:outline-none">
            <button type="submit" class="rounded-md bg-white px-2.5 py-1 text-[11px] font-semibold text-[#21265d] ring-1 ring-[#e0e5f0] hover:bg-[#e8edff]">Cari</button>
        </form>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-[#e0e5f0] text-[10px] font-semibold uppercase tracking-wide text-[#6e7a8f]">
                        <th class="py-2.5 pr-3">Reservasi / Tamu</th>
                        <th class="py-2.5 pr-3">Outlet</th>
                        <th class="py-2.5 pr-3">Nominal</th>
                        <th class="py-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e0e5f0] text-[11px]">
                    @forelse ($payments as $row)
                        @php
                            $isSelected = $selectedPayment && $selectedPayment->payments_id === $row->payments_id;
                            $badge = match ($row->payment_status) {
                                'paid' => ['Paid', 'bg-[#e3f5eb] text-[#147a4f]'],
                                'rejected' => ['Follow-up', 'bg-[#ffe8e8] text-[#ab3333]'],
                                'pending_verification' => ['Pending', 'bg-[#fff2db] text-[#9e5c14]'],
                                default => ['Unpaid', 'bg-slate-100 text-slate-600'],
                            };
                        @endphp
                        <tr class="transition-colors {{ $isSelected ? 'bg-[#f5f7ff]' : 'hover:bg-[#f6f8fc]' }}">
                            <td class="py-4 pr-3 font-semibold text-[#212b3b]">
                                <a href="{{ route('finance.dashboard', array_merge(request()->query(), ['selected' => $row->payments_id])) }}" class="hover:text-[#21265d] hover:underline">
                                    {{ $row->reservation?->reservation_code ?? '—' }} • {{ $row->reservation?->customer_name ?? '—' }}
                                </a>
                            </td>
                            <td class="py-4 pr-3 text-[#6e7a8f]">{{ $row->reservation?->outlet?->name ?? '—' }}</td>
                            <td class="py-4 pr-3 font-semibold text-[#212b3b]">Rp{{ number_format((float) $row->amount, 0, ',', '.') }}</td>
                            <td class="py-4 text-center">
                                <span class="inline-flex min-w-[90px] items-center justify-center rounded-full px-3 py-1 text-[10px] font-semibold {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-xs text-[#6e7a8f]">
                                Tidak ada pembayaran yang sesuai dengan filter saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </div>

    {{-- Right: Detail & bukti transfer --}}
    <div class="rounded-2xl border border-[#e0e5f0] bg-white p-5 md:p-[22px]">
        @if ($selectedPayment)
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="text-[17px] font-semibold text-[#212b3b]">Detail &amp; bukti transfer</h2>
                    <p class="mt-0.5 text-xs text-[#6e7a8f]">Reservasi {{ $selectedPayment->reservation?->reservation_code ?? '—' }}</p>
                </div>
                <a href="{{ route('finance.payments.show', $selectedPayment) }}" class="text-xs font-semibold text-[#21265d] hover:underline">Buka detail ↗</a>
            </div>

            <div class="mt-4 flex flex-col items-center justify-center rounded-xl border border-[#e0e5f0] bg-[#f6f8fc] p-4">
                <div class="w-[164px] rounded-lg border border-[#e0e5f0] bg-white px-3 py-4 text-center shadow-xs">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-[#6e7a8f]">Bukti Transfer</p>
                    <p class="mt-2 text-base font-semibold text-[#212b3b]">Rp{{ number_format((float) $selectedPayment->amount, 0, ',', '.') }}</p>
                    <p class="mt-1.5 text-[10px] text-[#6e7a8f]">{{ $selectedPayment->proof_uploaded_at?->format('d M • H.i') ?? '—' }} WIB</p>
                    <p class="mt-0.5 text-[10px] text-[#6e7a8f]">{{ $selectedPayment->bank_destination ?: 'Transfer Bank' }}</p>
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between text-[11px] text-[#6e7a8f]">
                <span class="truncate">{{ $selectedProofMeta['filename'] }} • {{ $selectedProofMeta['size'] }}</span>
                @if ($selectedProofMeta['exists'])
                    <button type="button"
                            data-proof-preview
                            data-proof-url="{{ route('finance.payments.proof', $selectedPayment) }}"
                            data-proof-name="{{ $selectedProofMeta['filename'] }}"
                            data-proof-meta="{{ $selectedProofMeta['ext'] }} • {{ $selectedProofMeta['size'] }}"
                            data-proof-is-image="{{ $selectedProofMeta['is_image'] ? '1' : '0' }}"
                            class="shrink-0 font-semibold text-[#21265d] hover:underline">
                        Lihat berkas
                    </button>
                @endif
            </div>

            <div class="mt-4 rounded-xl bg-[#e3f5eb] px-4 py-3.5">
                <p class="text-sm font-semibold text-[#147a4f]">Cocokkan transaksi aktual</p>
                <p class="mt-0.5 text-xs text-[#212b3b]">Periksa mutasi rekening / kanal resmi hotel.</p>
            </div>

            @if ($selectedPayment->reservation?->notes)
                <div class="mt-3 rounded-xl border border-indigo-200 bg-[#f5f7ff] px-3.5 py-2.5 text-xs text-[#21265d]">
                    <span class="font-semibold">Catatan pelanggan:</span> {{ $selectedPayment->reservation->notes }}
                </div>
            @endif

            @if ($selectedPayment->payment_status === 'pending_verification')
                <div class="mt-4 space-y-2.5">
                    <form method="POST" action="{{ route('finance.payments.approve', $selectedPayment) }}">
                        @csrf
                        <button type="submit" class="h-[42px] w-full rounded-[10px] bg-[#21265d] text-[15px] font-semibold text-white transition hover:bg-[#171c4f]">
                            Tandai Paid
                        </button>
                    </form>

                    <button type="button" onclick="document.getElementById('dashboard-reject-box').classList.toggle('hidden')"
                            class="h-[42px] w-full rounded-[10px] border border-[#e0e5f0] bg-white text-sm font-semibold text-[#ab3333] transition hover:bg-[#fff0ef]">
                        Tolak / minta tindak lanjut
                    </button>

                    <form id="dashboard-reject-box" method="POST" action="{{ route('finance.payments.reject', $selectedPayment) }}" class="hidden space-y-2 rounded-xl border border-[#e0e5f0] bg-[#f6f8fc] p-3">
                        @csrf
                        <label for="dash-reject-reason" class="block text-xs font-semibold text-[#212b3b]">Alasan penolakan / tindak lanjut</label>
                        <textarea id="dash-reject-reason" name="rejection_reason" rows="2" required maxlength="1000" placeholder="Contoh: nominal transfer tidak sesuai" class="w-full rounded-lg border border-[#e0e5f0] bg-white px-3 py-2 text-xs text-[#212b3b] focus:border-[#21265d] focus:outline-none"></textarea>
                        <button type="submit" class="w-full rounded-lg bg-[#ab3333] py-2 text-xs font-semibold text-white hover:bg-[#8e2727]">Kirim penolakan</button>
                    </form>
                </div>
            @elseif ($selectedPayment->payment_status === 'rejected')
                <div class="mt-4 rounded-xl bg-[#fff0ef] px-4 py-3 text-xs text-[#ab3333]">
                    <span class="font-semibold">Alasan ditolak:</span> {{ $selectedPayment->rejection_reason ?: '—' }}
                </div>
            @else
                <div class="mt-4 rounded-xl bg-[#e8edff] px-4 py-3 text-xs font-medium text-[#21265d]">
                    Sudah diverifikasi Paid oleh {{ $selectedPayment->verifiedBy?->name ?? 'Finance' }} pada {{ $selectedPayment->verified_at?->format('d/m/Y H:i') ?? '—' }}.
                </div>
            @endif

            <p class="mt-4 text-[11px] text-[#6e7a8f]">Petugas, waktu, status, dan alasan tersimpan di riwayat verifikasi.</p>
        @else
            <h2 class="text-[17px] font-semibold text-[#212b3b]">Detail &amp; bukti transfer</h2>
            <p class="mt-2 text-xs text-[#6e7a8f]">Pilih salah satu antrean pembayaran di sebelah kiri untuk melihat detail dan bukti transfer.</p>
        @endif
    </div>
</section>

{{-- Bottom Audit Strip --}}
<div class="mt-6 rounded-2xl border border-[#e0e5f0] bg-white px-5 py-4 text-xs text-[#6e7a8f]">
    Riwayat verifikasi dapat ditelusuri berdasarkan petugas, tanggal, outlet, dan status pembayaran.
</div>
@endsection
