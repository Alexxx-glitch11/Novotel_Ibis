@extends('layouts.app')

@section('title', __('ui.review_order') . ' · ' . $outlet->name)

@section('content')
@php
    $itemsById = $menuItems->keyBy('menu_items_id');
    $lines = collect($selectedMenuItems)->map(function ($quantity, $id) use ($itemsById) {
        $item = $itemsById->get($id);
        return $item ? ['item' => $item, 'quantity' => (int) $quantity, 'total' => (float) $item->price * (int) $quantity] : null;
    })->filter();
    $subtotal = $lines->sum('total');
    $serviceRate = (float) ($outlet->taxServiceRates()->whereDate('effective_from', '<=', $flow['reservation_date'])->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $flow['reservation_date']))->orderByDesc('effective_from')->value('service_rate') ?? 5);
    $taxRate = (float) ($outlet->taxServiceRates()->whereDate('effective_from', '<=', $flow['reservation_date'])->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $flow['reservation_date']))->orderByDesc('effective_from')->value('tax_rate') ?? 10);
    $serviceAmount = round($subtotal * $serviceRate / 100, 2);
    $taxAmount = round(($subtotal + $serviceAmount) * $taxRate / 100, 2);
    $grandTotal = $subtotal + $serviceAmount + $taxAmount;
    $money = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $bankName = config('reservation.bank.name');
    $bankNumber = config('reservation.bank.account_number');
    $bankHolder = config('reservation.bank.account_holder');
    $jsText = [
    'sending' => __('confirm.sending'),
    'copy'    => __('confirm.copy_account'),
    'copied'  => __('confirm.account_copied'),
    ];
@endphp
<div class="mx-auto w-full max-w-6xl px-4 py-8 md:py-12">
    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">
            <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
        <header class="flex flex-wrap items-center justify-between gap-4 bg-[#24215f] px-6 py-7 text-white md:px-10 md:py-9">
            <div><h1 class="text-2xl font-bold md:text-3xl">{{ __('ui.review_order') }}</h1><p class="mt-2 text-sm text-white/75">{{ __('ui.review_order_help') }}</p></div>
            <a href="{{ route('reservasi.outlet', ['outlet' => $outlet->code, 'step' => 'menu']) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white/15 px-4 text-sm font-semibold text-white transition hover:bg-white/25">✎ {{ __('ui.edit_order') }}</a>
        </header>

        <div class="p-5 md:p-10">
            <div class="grid gap-4 md:grid-cols-2">
                <section class="rounded-2xl bg-slate-50 p-5 md:p-6">
                    <h2 class="text-base font-semibold text-slate-600">{{ __('ui.customer_details') }}</h2>
                    <p class="mt-3 text-xl font-medium text-slate-900">{{ $flow['customer_name'] }}</p>
                    <p class="mt-3 break-all text-sm text-slate-600">✉ {{ $flow['customer_email'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">☎ {{ $flow['customer_phone'] }}</p>
                </section>
                <section class="rounded-2xl bg-slate-50 p-5 md:p-6">
                    <h2 class="text-base font-semibold text-slate-600">{{ __('ui.location_and_time') }}</h2>
                    <p class="mt-3 text-xl font-medium text-slate-900">{{ $outlet->name }} · {{ $selectedTable?->area?->name }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ \Carbon\Carbon::parse($flow['reservation_date'])->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ substr($flow['start_time'], 0, 5) }} WIB · {{ __('ui.table_word') }} {{ $selectedTable?->table_code }} · {{ $flow['guest_count'] }} {{ __('ui.guests') }}</p>
                </section>
            </div>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('ui.menu_order_details') }}</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 font-semibold">{{ __('ui.menu') }}</th><th class="px-4 py-3 text-right font-semibold">{{ __('ui.unit_price') }}</th><th class="px-4 py-3 text-center font-semibold">{{ __('ui.quantity') }}</th><th class="px-4 py-3 text-right font-semibold">{{ __('ui.subtotal') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($lines as $line)
                                <tr><td class="px-4 py-3 font-medium text-slate-800">{{ $line['item']->name }}</td><td class="px-4 py-3 text-right text-slate-600">{{ $money($line['item']->price) }}</td><td class="px-4 py-3 text-center text-slate-600">{{ $line['quantity'] }}</td><td class="px-4 py-3 text-right font-medium text-slate-800">{{ $money($line['total']) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">{{ __('ui.no_menu_ordered') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <dl class="ml-auto mt-4 max-w-md space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('ui.menu_subtotal') }}</dt><dd class="font-medium text-slate-800">{{ $money($subtotal) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('ui.service_charge', ['rate' => number_format($serviceRate, 0)]) }}</dt><dd class="font-medium text-slate-800">{{ $money($serviceAmount) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('ui.tax', ['rate' => number_format($taxRate, 0)]) }}</dt><dd class="font-medium text-slate-800">{{ $money($taxAmount) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base font-bold"><dt>{{ __('ui.total_payment') }}</dt><dd class="text-[#24215f]">{{ $money($grandTotal) }}</dd></div>
                </dl>
            </section>

            <section class="mt-6">
                <label for="notes" class="text-sm font-bold text-slate-900">{{ __('confirm.notes_title') }} <span class="font-normal text-slate-500">({{ __('confirm.optional') }})</span></label>
                <textarea id="notes" name="notes" form="reservation-confirm-form" rows="3" maxlength="1000"
                          placeholder="{{ __('confirm.notes_ph') }}"
                          class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:ring-2 focus:ring-[#24215f]/20 focus:outline-none">{{ old('notes', $flow['notes'] ?? '') }}</textarea>
                @error('notes')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <section class="mt-7 rounded-xl border border-slate-200 p-5 md:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-[#24215f]">{{ __('confirm.bank_title') }}</h2>
                        @if ($bankName && $bankNumber && $bankHolder)
                            <p class="mt-2 text-sm text-slate-700">{{ $bankName }} <span class="px-1 text-slate-300">·</span> <span class="font-mono font-medium">{{ $bankNumber }}</span> <span class="px-1 text-slate-300">·</span> {{ __('confirm.bank_holder', ['name' => $bankHolder]) }}</p>
                        @else
                            <p class="mt-2 text-sm text-amber-800">{{ __('confirm.bank_missing_review') }}</p>
                        @endif
                    </div>
                    @if ($bankNumber)
                        <button type="button" id="copy-bank" data-account="{{ $bankNumber }}" class="rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-[#24215f] hover:bg-indigo-100">{{ __('confirm.copy_account') }}</button>
                    @endif
                </div>

                <label for="payment-proof" id="proof-dropzone" class="mt-6 flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-[#fbfcfe] px-5 py-8 text-center transition hover:border-[#24215f] hover:bg-indigo-50/30">
                    <svg class="size-12 text-black" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M13 38a9 9 0 0 1-1-18 13 13 0 0 1 25-3 10.5 10.5 0 0 1-1.5 21H27V25.4l3.3 3.3 2.1-2.1L24 18.2l-8.4 8.4 2.1 2.1 3.3-3.3V38h-8Z"/></svg>
                    <span class="mt-3 font-semibold text-slate-800">{{ __('confirm.upload_title') }}</span>
                    <span class="mt-2 text-sm text-slate-500">{{ __('confirm.upload_hint') }}</span>
                    <span class="mt-2 text-xs text-slate-400">{{ __('confirm.upload_limits') }}</span>
                    <span id="proof-file-name" class="mt-3 hidden max-w-full truncate rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-[#24215f]"></span>
                    <input id="payment-proof" name="payment_proof" type="file" form="reservation-confirm-form" accept="image/jpeg,image/png,image/webp,application/pdf" required class="sr-only">
                </label>
                @error('payment_proof')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <form id="reservation-confirm-form" method="POST" action="{{ route('reservasi.confirm', $outlet->code) }}" enctype="multipart/form-data" class="mt-6">
                @csrf
                <div class="grid gap-4 border-t border-slate-400 py-4 md:grid-cols-[.8fr_1.2fr] md:items-center">
                    <div><p class="text-xs text-slate-500">{{ __('confirm.total_label') }}</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ $money($grandTotal) }}</p></div>
                    <div>
                        <button id="confirm-reservation" disabled class="flex min-h-14 w-full items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:opacity-50">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                            {{ __('confirm.btn_submit') }}
                        </button>
                        <p class="mt-2 text-right text-xs text-slate-500">{{ __('confirm.hold_label') }} <strong id="hold-countdown" data-expires="{{ $hold->expires_at->timestamp }}" class="tabular-nums">--:--</strong></p>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const T = @json($jsText);
    const proofInput = document.getElementById('payment-proof');
    const proofName = document.getElementById('proof-file-name');
    const dropzone = document.getElementById('proof-dropzone');
    const form = document.getElementById('reservation-confirm-form');
    const countdown = document.getElementById('hold-countdown');
    const deadline = Number(countdown.dataset.expires) * 1000;
    const button = document.getElementById('confirm-reservation');
    const updateButton = () => {
        const hasFile = Boolean(proofInput.files?.length);
        const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        button.disabled = !hasFile || !seconds;
    };
    proofInput.addEventListener('change', () => {
        const file = proofInput.files?.[0];
        if (file) {
            proofName.textContent = file.name;
            proofName.classList.remove('hidden');
        }
        updateButton();
    });
    dropzone.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('border-[#24215f]', 'bg-indigo-50'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-[#24215f]', 'bg-indigo-50'));
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('border-[#24215f]', 'bg-indigo-50');
        if (event.dataTransfer?.files?.length) {
            proofInput.files = event.dataTransfer.files;
            proofInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    document.getElementById('copy-bank')?.addEventListener('click', async event => {
        const copyBtn = event.currentTarget;
        try { await navigator.clipboard.writeText(copyBtn.dataset.account); copyBtn.textContent = T.copied; setTimeout(() => copyBtn.textContent = T.copy, 1800); }
        catch { copyBtn.textContent = copyBtn.dataset.account; }
    });
    form.addEventListener('submit', () => { button.disabled = true; button.textContent = T.sending; });
    const update = () => {
        const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        updateButton();
    };
    update(); window.setInterval(update, 1000);
})();
</script>
@endpush