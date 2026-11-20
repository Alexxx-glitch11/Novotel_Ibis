@extends('layouts.finance')

@section('title', 'Verifikasi pembayaran')

@section('content')
<div>
    <h1 class="text-[26px] font-semibold leading-[34px] text-[#212b3b]">Periksa bukti dan transaksi</h1>
    <p class="mt-1 text-sm text-[#6e7a8f]">Unggahan hanya menjadi Pending Verification. Tandai Paid setelah transaksi aktual cocok.</p>
</div>

@if ($payment)
    @php
        $reservation = $payment->reservation;
        $table = $reservation?->tables->first();
        $badge = match ($payment->payment_status) {
            'paid' => ['Paid', 'bg-[#e3f5eb] text-[#147a4f]'],
            'rejected' => ['Rejected / Follow-up', 'bg-[#ffe8e8] text-[#ab3333]'],
            'pending_verification' => ['Pending Verification', 'bg-[#fff2db] text-[#9e5c14]'],
            default => ['Unpaid', 'bg-slate-100 text-slate-600'],
        };
    @endphp

    <section class="mt-5 grid gap-5 lg:items-start {{ $queue->isNotEmpty() ? 'lg:grid-cols-2 xl:grid-cols-[240px_minmax(0,1fr)_minmax(0,1fr)]' : 'lg:grid-cols-2' }}">
        {{-- Left Sidebar List: Antrean Kode Pembayaran --}}
        @if ($queue->isNotEmpty())
            <aside class="rounded-2xl border border-[#e0e5f0] bg-white p-4 lg:col-span-2 xl:col-span-1">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-[#e0e5f0]">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-[#6e7a8f]">Antrean ({{ $queue->count() }})</h2>
                </div>
                <div class="mt-3 flex max-h-[640px] flex-col gap-1.5 overflow-y-auto pr-1">
                    @foreach ($queue as $item)
                        @php
                            $active = $payment && $payment->payments_id === $item->payments_id;
                            $dotColor = $item->payment_status === 'rejected' ? 'bg-[#ab3333]' : 'bg-[#d97706]';
                        @endphp
                        <a href="{{ route('finance.payments.show', array_merge(['payment' => $item->payments_id], request()->only('outlet'))) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition
                                  {{ $active ? 'bg-[#21265d] text-white' : 'bg-[#f6f8fc] text-[#212b3b] hover:bg-[#e8edff]' }}">
                            <span class="size-2 shrink-0 rounded-full {{ $active ? 'bg-white' : $dotColor }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-mono text-xs font-semibold">{{ $item->reservation?->reservation_code ?? ('#' . $item->payments_id) }}</p>
                                <p class="truncate text-[11px] {{ $active ? 'text-white/80' : 'text-[#6e7a8f]' }}">{{ $item->reservation?->customer_name ?? '—' }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif

        {{-- Middle Column: Detail & Bukti --}}
        <div class="min-w-0 space-y-5">
            {{-- Selected Reservation Card --}}
            <div class="rounded-2xl border border-[#e0e5f0] bg-white p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="min-w-0 break-words text-lg font-semibold text-[#212b3b] sm:text-xl" translate="no">{{ $reservation?->reservation_code ?? '—' }}</h2>
                    <span class="shrink-0 rounded-full px-3.5 py-1 text-[11px] font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                </div>

                <dl class="mt-5 divide-y divide-[#e0e5f0] text-[13px]">
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Nama tamu</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]" translate="no">{{ $reservation?->customer_name ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Outlet</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">{{ $reservation?->outlet?->name ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Tanggal reservasi</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">
                            {{ $reservation?->reservation_date?->locale(app()->getLocale())->translatedFormat('l, d M Y') ?? '—' }}
                            • {{ substr((string) ($reservation?->start_time ?? ''), 0, 5) }}
                        </dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Meja</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">
                            {{ $table?->table_code ?? '—' }} • {{ $table?->area?->name ?? '—' }} • {{ $reservation?->guest_count ?? 0 }} tamu
                        </dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Jumlah tagihan</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Bukti diunggah</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">{{ $payment->proof_uploaded_at?->locale(app()->getLocale())->translatedFormat('d M Y • H.i') ?? '—' }} WIB</dd>
                    </div>
                    <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 pt-3.5">
                        <dt class="text-xs text-[#6e7a8f]">Catatan pelanggan</dt>
                        <dd class="min-w-0 break-words font-semibold text-[#212b3b]">{{ $reservation?->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Transfer Proof Card --}}
            <div class="rounded-2xl border border-[#e0e5f0] bg-white p-6">
                <h3 class="text-[17px] font-semibold text-[#212b3b]">Bukti transfer</h3>
                <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="grid h-[168px] w-full shrink-0 place-items-center overflow-hidden rounded-xl border border-[#e0e5f0] bg-[#f6f8fc] sm:w-[180px]">
                        @if ($proofMeta['exists'] && $proofMeta['is_image'])
                            <img src="{{ route('finance.payments.proof', $payment) }}" alt="Bukti transfer" class="h-full w-full object-cover">
                        @else
                            <div class="w-[100px] rounded-md border border-[#e0e5f0] bg-white px-2 py-4 text-center">
                                <p class="text-[11px] font-semibold text-[#6e7a8f]">BANK</p>
                                <p class="mt-2 text-xs font-semibold text-[#212b3b]">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-[9px] text-[#6e7a8f]">{{ $payment->bank_destination ?: 'Transfer' }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold text-[#212b3b]">{{ $proofMeta['filename'] }}</p>
                        <p class="mt-1 text-[11px] text-[#6e7a8f]">{{ $proofMeta['ext'] }} • {{ $proofMeta['size'] }} • diunggah oleh pelanggan</p>
                        @if ($proofMeta['exists'])
                            <button type="button"
                                    data-proof-preview
                                    data-proof-url="{{ route('finance.payments.proof', $payment) }}"
                                    data-proof-name="{{ $proofMeta['filename'] }}"
                                    data-proof-meta="{{ $proofMeta['ext'] }} • {{ $proofMeta['size'] }} • diunggah oleh pelanggan"
                                    data-proof-is-image="{{ $proofMeta['is_image'] ? '1' : '0' }}"
                                    class="mt-5 inline-flex h-[34px] items-center justify-center rounded-lg bg-[#e8edff] px-4 text-xs font-semibold text-[#21265d] hover:bg-[#dce3ff]">
                                Lihat bukti ukuran penuh
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Actual Transaction Verification --}}
        <div class="flex min-w-0 flex-col rounded-2xl border border-[#e0e5f0] bg-white p-6">
            <h2 class="text-lg font-semibold text-[#212b3b]">Pencocokan transaksi aktual</h2>
            <p class="mt-1 text-xs text-[#6e7a8f]">Periksa mutasi rekening atau kanal resmi hotel.</p>

            <dl class="mt-5 divide-y divide-[#e0e5f0] text-[13px]">
                <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                    <dt class="text-xs text-[#6e7a8f]">Nominal masuk</dt>
                    <dd class="min-w-0 break-words font-semibold text-[#212b3b]">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</dd>
                </div>
                <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                    <dt class="text-xs text-[#6e7a8f]">Tanggal transaksi</dt>
                    <dd class="min-w-0 break-words font-semibold text-[#212b3b]">{{ $payment->proof_uploaded_at?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                    <dt class="text-xs text-[#6e7a8f]">Rekening tujuan</dt>
                    <dd class="min-w-0 break-words font-semibold text-[#212b3b]" translate="no">{{ $payment->bank_destination ?: 'Rekening Resmi Hotel' }}</dd>
                </div>
                <div class="grid grid-cols-[minmax(120px,145px)_minmax(0,1fr)] items-baseline gap-3 py-3.5">
                    <dt class="text-xs text-[#6e7a8f]">Metode pembayaran</dt>
                    <dd class="min-w-0 break-words font-semibold text-[#212b3b]">{{ str_replace('_', ' ', ucfirst((string) $payment->payment_method)) }}</dd>
                </div>
            </dl>

            <div class="mt-6 rounded-[10px] bg-[#e3f5eb] px-4 py-4">
                <p class="text-[13px] font-semibold text-[#147a4f]">Pastikan nominal dan rekening tujuan cocok dengan mutasi aktual.</p>
            </div>

            @if ($payment->payment_status === 'pending_verification')
                <form id="finance-verify-form" method="POST" action="{{ route('finance.payments.approve', $payment) }}" class="mt-6 flex-1">
                    @csrf
                    <label for="verification_note" class="block text-[13px] font-semibold text-[#212b3b]">Catatan verifikasi / alasan tindak lanjut</label>
                    <textarea id="verification_note" name="verification_note" rows="3" placeholder="Tambahkan catatan jika bukti ditolak atau perlu tindak lanjut."
                              class="mt-2 w-full rounded-[10px] border border-[#e0e5f0] bg-white px-4 py-3 text-xs text-[#212b3b] placeholder-[#6e7a8f] focus:border-[#21265d] focus:outline-none"></textarea>

                    <p class="mt-3 text-xs text-[#6e7a8f]">Status berubah hanya setelah keputusan Finance dan tercatat di riwayat.</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-[220fr_284fr]">
                        <button type="submit"
                                formaction="{{ route('finance.payments.reject', $payment) }}"
                                onclick="var note = document.getElementById('verification_note'); var hidden = document.getElementById('hidden-rejection-reason'); hidden.value = note.value.trim(); if (!hidden.value) { alert('Isi catatan / alasan penolakan terlebih dahulu.'); note.focus(); return false; }"
                                class="h-[44px] rounded-[10px] border border-[#e0e5f0] bg-white text-sm font-semibold text-[#ab3333] hover:bg-[#fff0ef]">
                            Tolak / tindak lanjut
                        </button>
                        <input type="hidden" name="rejection_reason" id="hidden-rejection-reason">

                        <button type="submit" class="h-[44px] rounded-[10px] bg-[#21265d] text-sm font-semibold text-white hover:bg-[#171c4f]">
                            Verifikasi &amp; tandai Paid
                        </button>
                    </div>
                </form>
            @elseif ($payment->payment_status === 'rejected')
                <div class="mt-6 rounded-xl bg-[#fff0ef] p-4 text-sm text-[#ab3333]">
                    <p class="font-semibold">Bukti ditolak / perlu tindak lanjut</p>
                    <p class="mt-1 text-xs">{{ $payment->rejection_reason }}</p>
                </div>
            @else
                <div class="mt-6 rounded-xl bg-[#e8edff] p-4 text-sm text-[#21265d]">
                    <p class="font-semibold">Pembayaran telah diverifikasi Paid</p>
                    <p class="mt-1 text-xs">Petugas: {{ $payment->verifiedBy?->name ?? 'Finance' }} • {{ $payment->verified_at?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            @endif
        </div>
    </section>

    <p class="mt-5 text-xs text-[#6e7a8f]">Keputusan Finance tidak mengubah status check-in atau kehadiran.</p>
@else
    <div class="mt-6 rounded-2xl border border-[#e0e5f0] bg-white p-12 text-center">
        <div class="mx-auto grid size-12 place-items-center rounded-full bg-[#e3f5eb] text-xl text-[#147a4f]">✓</div>
        <h2 class="mt-3 text-base font-semibold text-[#212b3b]">Tidak ada bukti yang perlu diverifikasi</h2>
        <p class="mt-1 text-sm text-[#6e7a8f]">Bukti pembayaran yang masuk akan tampil di halaman ini.</p>
    </div>
@endif
@endsection
