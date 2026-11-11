@extends('layouts.app')

@section('title', __('ui.confirmation_title') . ' · ' . $outlet->name)

@section('content')
@php
    $money = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $payment = $reservation->latestPayment;
    $paymentStatus = $payment?->payment_status ?? 'unpaid';
    $bankName = config('reservation.bank.name');
    $bankNumber = config('reservation.bank.account_number');
    $bankHolder = config('reservation.bank.account_holder');
    $confirmed = $paymentStatus === 'paid' && $reservation->reservation_status === 'confirmed';
    $showStatusModal = $confirmed || $paymentStatus === 'pending_verification';
    $reservationTable = $reservation->tables->first();
    $holdRemainingSeconds = $payment?->proof_uploaded_at
        ? max(0, $payment->proof_uploaded_at->timestamp + ((int) config('reservation.payment_proof_hold_minutes', 30) * 60) - now()->timestamp)
        : 0;
@endphp

<div class="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 md:py-10">
    <a href="{{ route('home') }}" aria-label="Kembali" class="mb-5 inline-flex size-10 items-center justify-center rounded-full text-slate-600 hover:bg-slate-200/70">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 12H5m0 0 7 7m-7-7 7-7"/></svg>
    </a>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <header class="mx-4 mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-[#24215f] px-5 py-6 text-white md:mx-6 md:mt-6 md:px-10 md:py-8">
            <div>
                <h1 class="text-xl font-bold md:text-3xl">Konfirmasi reservasi &amp; pesanan</h1>
                <p class="mt-2 text-xs text-white/75 md:text-sm">Periksa detailnya, lalu selesaikan pembayaran untuk mendapatkan QR code.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-semibold text-white/90">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                {{ $confirmed ? 'Reservasi terkonfirmasi' : ($paymentStatus === 'pending_verification' ? 'Menunggu verifikasi finance' : 'Menunggu pembayaran') }}
            </span>
        </header>

        <div class="space-y-6 px-4 py-6 md:space-y-8 md:px-10 md:py-8">
            @if (session('payment_submitted'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi finance.</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <section class="rounded-xl bg-[#f5f6fb] p-5 ring-1 ring-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">Data Pemesan</h2>
                    <p class="mt-2 text-lg font-medium text-slate-900">{{ $reservation->customer_name }}</p>
                    <p class="mt-3 flex items-center gap-2 break-all text-sm text-slate-700"><span aria-hidden="true">✉</span>{{ $reservation->customer_email }}</p>
                    <p class="mt-2 flex items-center gap-2 text-sm text-slate-700"><span aria-hidden="true">☎</span>{{ $reservation->customer_phone }}</p>
                </section>
                <section class="rounded-xl bg-[#f5f6fb] p-5 ring-1 ring-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">Detail Lokasi &amp; Waktu</h2>
                    <p class="mt-2 font-medium text-slate-900">{{ $outlet->name }}{{ $reservationTable?->area?->name ? ' - ' . $reservationTable->area->name : '' }}</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $reservation->reservation_date->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</p>
                    <p class="mt-2 text-sm text-slate-700">{{ substr($reservation->start_time, 0, 5) }} WIB</p>
                    <p class="mt-2 text-sm text-slate-700">Meja {{ $reservationTable?->table_code }} · {{ $reservationTable?->area?->name }} · {{ $reservation->guest_count }} orang</p>
                </section>
            </div>

            <section>
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900"><span aria-hidden="true">▤</span>Rincian Pesanan Menu</h2>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead><tr class="text-slate-600"><th class="px-4 py-3 font-medium">Menu</th><th class="px-4 py-3 text-right font-medium">Harga Satuan</th><th class="px-4 py-3 text-center font-medium">Jumlah</th><th class="px-4 py-3 text-right font-medium">Subtotal</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($reservation->items as $item)
                                <tr><td class="px-4 py-3 font-medium text-slate-800">{{ $item->item_name_snapshot }}</td><td class="px-4 py-3 text-right text-slate-600">{{ $money($item->unit_price_snapshot) }}</td><td class="px-4 py-3 text-center text-slate-600">{{ $item->quantity }}</td><td class="px-4 py-3 text-right text-slate-800">{{ $money($item->line_total) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-5 text-center text-slate-500">{{ __('ui.no_menu_ordered') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="flex justify-between gap-4 bg-[#f0f2fa] px-4 py-3 text-sm font-medium text-slate-700"><span>Subtotal Menu</span><span>{{ $money($reservation->subtotal) }}</span></div>
                </div>
                <dl class="ml-auto mt-2 max-w-md space-y-2 px-2 text-sm text-slate-700">
                    <div class="flex justify-between gap-4"><dt>Biaya Layanan / Service Charge ({{ number_format((float) $reservation->service_rate_percent, 0) }}%)</dt><dd>{{ $money($reservation->service_amount) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Pajak Restoran PB1 ({{ number_format((float) $reservation->tax_rate_percent, 0) }}%)</dt><dd>{{ $money($reservation->tax_amount) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-bold text-[#24215f]"><dt>Total Pembayaran Akhir</dt><dd>{{ $money($reservation->grand_total) }}</dd></div>
                </dl>
            </section>

            <section>
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900"><span aria-hidden="true">▧</span>Catatan khusus</h2>
                <div class="mt-2 min-h-20 rounded-xl bg-[#f5f6fb] px-4 py-4 text-sm text-slate-500 ring-1 ring-slate-200">{{ $reservation->notes ?: 'Tidak ada catatan khusus.' }}</div>
            </section>

            <form id="payment-proof-form" method="POST" action="{{ route('reservasi.payment-proof', ['outlet' => $outlet->code, 'code' => $reservation->reservation_code]) }}" enctype="multipart/form-data">
                @csrf
                <section class="rounded-xl border border-slate-200 p-5 md:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><h2 class="font-bold text-[#24215f]">Rekening resmi pembayaran hotel</h2>
                            @if ($bankNumber && $bankName && $bankHolder)
                                <p class="mt-2 text-sm text-slate-700">{{ $bankName }} <span class="px-1 text-slate-300">·</span> <span class="font-mono font-medium">{{ $bankNumber }}</span> <span class="px-1 text-slate-300">·</span> a.n. {{ $bankHolder }}</p>
                            @else
                                <p class="mt-2 text-sm text-amber-800">Rekening pembayaran belum dikonfigurasi. Jangan transfer sebelum memperoleh nomor rekening resmi; jika sudah transfer, Anda tetap dapat mengirim bukti di bawah.</p>
                            @endif
                        </div>
                        @if ($bankNumber)<button type="button" id="copy-bank" class="rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-[#24215f] hover:bg-indigo-100" data-account="{{ $bankNumber }}">Salin rekening</button>@endif
                    </div>

                    @if ($paymentStatus === 'pending_verification' || $confirmed)
                        <div class="mt-6 flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-[#fbfcfe] px-5 py-8 text-center">
                            <svg class="size-12 {{ $confirmed ? 'text-emerald-600' : 'text-amber-600' }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="24" cy="24" r="18"/><path d="m15 24 6 6 13-14"/></svg>
                            <span class="mt-3 font-semibold text-slate-800">{{ $confirmed ? 'Pembayaran telah diverifikasi finance' : 'Bukti pembayaran sudah dikirim' }}</span>
                            <span class="mt-2 text-sm text-slate-500">{{ $confirmed ? 'Reservasi Anda telah dikonfirmasi.' : 'Finance sedang memeriksa bukti transfer Anda.' }}</span>
                        </div>
                    @else
                        <label for="payment-proof" id="proof-dropzone" class="mt-6 flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-[#fbfcfe] px-5 py-8 text-center transition hover:border-[#24215f] hover:bg-indigo-50/30">
                            <svg class="size-12 text-black" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M13 38a9 9 0 0 1-1-18 13 13 0 0 1 25-3 10.5 10.5 0 0 1-1.5 21H27V25.4l3.3 3.3 2.1-2.1L24 18.2l-8.4 8.4 2.1 2.1 3.3-3.3V38h-8Z"/></svg>
                            <span class="mt-3 font-semibold text-slate-800">Unggah bukti pembayaran</span>
                            <span class="mt-2 text-sm text-slate-500">Struk ATM, screenshot mobile banking, atau resi transfer</span>
                            <span class="mt-2 text-xs text-slate-400">Setelah dikirim, bukti menunggu verifikasi. Meja ditahan selama {{ (int) config('reservation.payment_proof_hold_minutes', 30) }} menit.</span>
                            <span id="proof-file-name" class="mt-3 hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-[#24215f]"></span>
                            <input id="payment-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="sr-only">
                        </label>
                        @error('payment_proof')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    @endif
                </section>
                <footer class="mt-6 grid gap-4 border-t border-slate-400 py-4 md:grid-cols-[.8fr_1.2fr] md:items-center md:px-4">
                    <div><p class="text-xs text-slate-500">Total pembayaran</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ $money($reservation->grand_total) }}</p></div>
                    @if ($confirmed || $paymentStatus === 'pending_verification')
                        <button type="button" disabled class="flex min-h-14 cursor-not-allowed items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white opacity-70">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                            {{ $confirmed ? 'Pembayaran terverifikasi' : 'Menunggu verifikasi finance' }}
                        </button>
                    @else
                        <button id="submit-proof" type="submit" disabled class="flex min-h-14 items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:opacity-50">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                            Kirim Bukti Pembayaran
                        </button>
                    @endif
                </footer>
            </form>
        </div>
    </section>
</div>

@if ($showStatusModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/45 px-4 py-6 backdrop-blur-[4px]" role="presentation">
        <section class="my-auto w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/10" role="dialog" aria-modal="true" aria-labelledby="payment-status-title">
            <header class="px-6 py-6 text-center text-white {{ $confirmed ? 'bg-emerald-700' : 'bg-amber-600' }}">
                <svg class="mx-auto size-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><circle cx="24" cy="24" r="18"/><path d="m15 24 6 6 13-14"/></svg>
                <h2 id="payment-status-title" class="mt-3 text-lg font-semibold">{{ $confirmed ? 'Reservasi Terkonfirmasi!' : 'Bukti Pembayaran Diterima!' }}</h2>
                @if (! $confirmed)
                    <p class="mt-2 inline-flex rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">Menunggu Verifikasi Finance</p>
                @endif
            </header>
            <div class="p-5 md:px-7 md:py-6">
                <div class="rounded-lg border border-slate-300 p-4 text-center">
                    <p class="text-xs text-slate-500">Kode Reservasi</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-[#24215f]">{{ $reservation->reservation_code }}</p>
                </div>
                <dl class="mt-5 space-y-2.5 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Nama tamu</dt><dd class="text-right font-medium text-slate-800">{{ $reservation->customer_name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Lokasi outlet</dt><dd class="text-right font-medium text-slate-800">{{ $outlet->name }}{{ $reservationTable?->area?->name ? ' - ' . $reservationTable->area->name : '' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal &amp; waktu</dt><dd class="text-right font-medium text-slate-800">{{ $reservation->reservation_date->format('d M Y') }} · {{ substr($reservation->start_time, 0, 5) }} WIB</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Jumlah tamu</dt><dd class="text-right font-medium text-slate-800">{{ $reservation->guest_count }} orang</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Meja</dt><dd class="text-right font-medium text-slate-800">{{ $reservationTable?->table_code }} · {{ $reservationTable?->area?->name }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-slate-100 pt-2"><dt class="text-slate-500">Total tagihan</dt><dd class="text-right font-semibold text-slate-900">{{ $money($reservation->grand_total) }}</dd></div>
                </dl>
                @if ($confirmed && $qrDataUri)
                    <div class="mx-auto mt-5 w-fit rounded-lg border border-slate-200 bg-white p-2"><img src="{{ $qrDataUri }}" alt="QR reservasi {{ $reservation->reservation_code }}" width="144" height="144" class="size-36"></div>
                @elseif (! $confirmed)
                    <div class="mx-auto mt-5 flex size-40 flex-col items-center justify-center rounded-lg border border-dashed border-amber-300 bg-amber-50 text-center" role="status" aria-label="QR reservasi terkunci">
                        <svg class="size-10 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3"/></svg>
                        <span class="mt-2 text-xs font-semibold text-amber-900">QR terkunci</span>
                        <span class="mt-1 max-w-32 text-[10px] leading-tight text-amber-800">Tersedia setelah finance mengonfirmasi pembayaran</span>
                    </div>
                @endif
                <p class="mt-2 text-center text-[10px] text-slate-500">{{ $confirmed ? 'Tunjukkan kode reservasi ini kepada staf saat datang.' : 'Meja ditahan selama ' . (int) config('reservation.payment_proof_hold_minutes', 30) . ' menit setelah bukti dikirim.' }}</p>
                @if (! $confirmed && $holdRemainingSeconds > 0)
                    <p class="mt-1 text-center text-[10px] font-semibold text-amber-700">Sisa penahanan meja: <span id="hold-countdown">--:--</span></p>
                @endif
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#24215f] px-3 text-xs font-semibold text-white hover:bg-[#191744]">Kembali ke beranda</a>
                    @if ($confirmed && $qrDataUri)
                        <a href="{{ route('reservasi.qr-download', ['outlet' => $outlet->code, 'code' => $reservation->reservation_code]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-50 px-3 text-xs font-semibold text-[#24215f] hover:bg-indigo-100">Unduh QR</a>
                    @else
                        <span aria-disabled="true" class="inline-flex min-h-10 cursor-not-allowed items-center justify-center rounded-lg bg-slate-100 px-3 text-center text-[10px] font-semibold text-slate-400">Unduh setelah terkonfirmasi</span>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const proofInput = document.getElementById('payment-proof');
    const fileName = document.getElementById('proof-file-name');
    const submit = document.getElementById('submit-proof');
    const form = document.getElementById('payment-proof-form');
    const dropzone = document.getElementById('proof-dropzone');
    proofInput?.addEventListener('change', () => {
        const file = proofInput.files?.[0];
        if (!file) return;
        fileName.textContent = file.name;
        fileName.classList.remove('hidden');
        submit.disabled = false;
    });
    dropzone?.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('border-[#24215f]', 'bg-indigo-50'); });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('border-[#24215f]', 'bg-indigo-50'));
    dropzone?.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('border-[#24215f]', 'bg-indigo-50');
        if (event.dataTransfer?.files?.length && proofInput) {
            proofInput.files = event.dataTransfer.files;
            proofInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    form?.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Mengirim bukti...'; });
    document.getElementById('copy-bank')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        try { await navigator.clipboard.writeText(button.dataset.account); button.textContent = 'Rekening tersalin'; setTimeout(() => button.textContent = 'Salin rekening', 1800); }
        catch { button.textContent = button.dataset.account; }
    });
    const countdown = document.getElementById('hold-countdown');
    if (countdown) {
        let seconds = @json($holdRemainingSeconds);
        const update = () => {
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = Math.max(0, seconds % 60).toString().padStart(2, '0');
            countdown.textContent = `${mins}:${secs}`;
            if (seconds > 0) seconds -= 1;
        };
        update();
        setInterval(update, 1000);
    }
    @if ($paymentStatus === 'pending_verification')
        setTimeout(() => window.location.reload(), 30000);
    @endif
})();
</script>
@endpush
