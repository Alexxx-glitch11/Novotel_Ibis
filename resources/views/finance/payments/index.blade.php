@extends('layouts.manager')

@section('title', 'Verifikasi pembayaran')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><p class="text-xs font-bold uppercase tracking-[.16em] text-[#5b568b]">Finance</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[#252942]">Verifikasi pembayaran</h1><p class="mt-1 text-sm text-slate-500">Periksa bukti transfer sebelum mengonfirmasi reservasi pelanggan.</p></div>
    <form method="POST" action="{{ route('finance.logout') }}">@csrf<button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Keluar</button></form>
</div>
@if(session('success'))<div class="mt-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $errors->first() }}</div>@endif

<section class="mt-6 space-y-4">
    @forelse($payments as $payment)
        @php $reservation = $payment->reservation; $table = $reservation?->tables->first(); @endphp
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold text-slate-900">{{ $reservation?->customer_name }}</h2><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $payment->payment_status === 'pending verification' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800' }}">{{ $payment->payment_status === 'pending verification' ? 'Menunggu verifikasi' : 'Bukti ditolak' }}</span></div><p class="mt-1 font-mono text-sm text-[#24215f]">{{ $reservation?->reservation_code }}</p><p class="mt-2 text-sm text-slate-600">{{ $reservation?->outlet?->name }} · {{ $table?->area?->name }} · Meja {{ $table?->table_code }}</p><p class="mt-1 text-xs text-slate-500">Bukti dikirim {{ $payment->proof_uploaded_at?->format('d/m/Y H:i') }}</p></div>
                <div class="text-right"><p class="text-xs text-slate-500">Jumlah transfer</p><p class="text-xl font-bold text-[#24215f]">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</p><a target="_blank" rel="noopener" href="{{ route('finance.payments.proof', $payment) }}" class="mt-2 inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Lihat bukti</a></div>
            </div>
            @if($payment->payment_status === 'pending verification')
                <div class="mt-5 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-[1fr_auto_auto] md:items-end">
                    <form method="POST" action="{{ route('finance.payments.reject', $payment) }}">@csrf<label for="reason-{{ $payment->payments_id }}" class="block text-xs font-semibold text-slate-600">Alasan penolakan jika bukti tidak sesuai</label><input id="reason-{{ $payment->payments_id }}" name="rejection_reason" required maxlength="1000" placeholder="Contoh: nominal transfer tidak sesuai" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"><button class="mt-2 rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Tolak bukti</button></form>
                    <form method="POST" action="{{ route('finance.payments.approve', $payment) }}" class="md:col-start-3">@csrf<button class="min-h-11 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Terima & konfirmasi</button></form>
                </div>
            @else
                <p class="mt-4 border-t border-slate-100 pt-4 text-sm text-red-800"><span class="font-semibold">Alasan ditolak:</span> {{ $payment->rejection_reason }}</p>
            @endif
        </article>
    @empty
        <div class="rounded-2xl bg-white p-10 text-center ring-1 ring-slate-200"><div class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-50 text-xl text-emerald-700">✓</div><h2 class="mt-3 font-bold text-slate-900">Tidak ada bukti yang perlu diverifikasi</h2><p class="mt-1 text-sm text-slate-500">Bukti pembayaran yang masuk akan tampil di halaman ini.</p></div>
    @endforelse
    <div>{{ $payments->links() }}</div>
</section>
@endsection
