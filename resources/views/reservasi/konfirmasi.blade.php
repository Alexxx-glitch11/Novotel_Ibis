@extends('layouts.app')

@section('title', __('ui.confirmation_title') . ' · ' . $outlet->name)

@push('styles')
<style>
    .proof-upload-box {
        margin-top: 24px;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        background: #fbfcfe;
        min-height: 220px;
        overflow: hidden;
        position: relative;
        transition: border-color .2s ease, background-color .2s ease;
    }
    .proof-upload-box.is-dragover {
        border-color: #24215f;
        background: #eef2ff;
    }
    .proof-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 220px;
        width: 100%;
        padding: 24px 20px;
        cursor: pointer;
    }
    .proof-empty-state[hidden],
    .proof-preview-state[hidden],
    .proof-preview-media img[hidden],
    .proof-preview-media iframe[hidden] {
        display: none !important;
    }
    .proof-preview-state {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 220px;
        padding: 14px;
        gap: 12px;
        box-sizing: border-box;
    }
    .proof-preview-media {
        width: 100%;
        height: 185px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 6px;
        box-sizing: border-box;
    }
    .proof-preview-media img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        display: block;
        border-radius: 6px;
    }
    .proof-preview-media iframe {
        width: 100%;
        height: 100%;
        border: 0;
        background: #ffffff;
        border-radius: 6px;
        display: block;
    }
    .proof-preview-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .proof-preview-info {
        min-width: 0;
        flex: 1 1 200px;
    }
    .proof-preview-badge {
        display: block;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .proof-preview-filename {
        margin-top: 2px;
        font-size: 13px;
        font-weight: 600;
        color: #24215f;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .proof-preview-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .proof-btn-change,
    .proof-btn-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background: #ffffff;
    }
    .proof-btn-change { color: #24215f; }
    .proof-btn-change:hover { background: #eef2ff; border-color: #24215f; }
    .proof-btn-view {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #c7d2fe;
        background: #eef2ff;
        color: #24215f;
    }
    .proof-btn-view:hover { background: #dce3ff; border-color: #24215f; }
    .proof-btn-clear { color: #b91c1c; }
    .proof-btn-clear:hover { background: #fef2f2; border-color: #fecaca; }
    .proof-preview-media { cursor: zoom-in; position: relative; }
    .proof-zoom-hint {
        position: absolute;
        right: 8px;
        bottom: 8px;
        border-radius: 9999px;
        background: rgba(15, 23, 42, .72);
        color: #ffffff;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        pointer-events: auto;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,.2);
    }
    .cp-modal {
        position: fixed;
        inset: 0;
        z-index: 120;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, .75);
        padding: 16px;
        backdrop-filter: blur(4px);
    }
    .cp-modal[hidden] { display: none !important; }
    .cp-card {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 1024px;
        height: 88vh;
        overflow: hidden;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .4);
    }
    .cp-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 18px;
        background: #ffffff;
    }
    .cp-head-info { min-width: 0; flex: 1 1 200px; }
    .cp-title {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cp-subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
    .cp-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .cp-tool-btn {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
    }
    .cp-tool-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
    .cp-tool-label { min-width: 44px; text-align: center; font-size: 12px; font-weight: 600; color: #1e293b; }
    .cp-tool-sep { width: 1px; height: 20px; background: #e2e8f0; margin: 0 2px; }
    .cp-reset-btn {
        margin-left: 2px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #24215f;
        cursor: pointer;
    }
    .cp-reset-btn:hover { background: #eef2ff; }
    .cp-close-btn {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 0;
        background: #f1f5f9;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
    }
    .cp-close-btn:hover { background: #fee2e2; color: #b91c1c; }
    .cp-viewport {
        position: relative;
        flex: 1 1 auto;
        display: flex;
        overflow: auto;
        background: #f1f5f9;
        padding: 20px;
        user-select: none;
    }
    .cp-stage {
        position: relative;
        margin: auto;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #cp-modal-img[hidden], #cp-modal-frame[hidden] { display: none !important; }
    #cp-modal-img {
        max-width: none;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .15);
        cursor: grab;
        transform-origin: center center;
        display: block;
    }
    #cp-modal-img.is-grabbing { cursor: grabbing; }
    #cp-modal-frame {
        width: min(85vw, 900px);
        height: 74vh;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        transform-origin: center center;
        display: block;
    }
    #submit-proof:disabled { opacity: .5; cursor: not-allowed; }
</style>
@endpush

@section('content')
@php
    $money = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $loc = app()->getLocale();
    $payment = $reservation->latestPayment;
    $paymentStatus = $payment?->payment_status ?? 'unpaid';
    $settings = app(\App\Services\ReservationSettings::class);
    $bankName = $settings->bankName();
    $bankNumber = $settings->bankAccountNumber();
    $bankHolder = $settings->bankAccountHolder();
    $confirmed = $paymentStatus === 'paid' && $reservation->reservation_status === 'confirmed';
    $showStatusModal = $confirmed || $paymentStatus === 'pending_verification';
    $reservationTable = $reservation->tables->first();
    $statusText = $confirmed
        ? __('confirm.st_confirmed')
        : ($paymentStatus === 'pending_verification'
            ? __('confirm.st_verifying')
            : ($paymentStatus === 'rejected'
                ? (app()->getLocale() === 'en' ? 'Proof rejected — re-upload needed' : 'Bukti ditolak — unggah ulang')
                : __('confirm.st_unpaid')));
    $guestsText = trans_choice('confirm.guests_count', $reservation->guest_count, ['count' => $reservation->guest_count]);
    $jsText = [
    'sending' => __('confirm.sending'),
    'copy'    => __('confirm.copy_account'),
    'copied'  => __('confirm.account_copied'),
    ];
@endphp

<div class="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 md:py-10">
    <a href="{{ route('home') }}" aria-label="{{ __('confirm.back') }}" class="mb-5 inline-flex size-10 items-center justify-center rounded-full text-slate-600 hover:bg-slate-200/70">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 12H5m0 0 7 7m-7-7 7-7"/></svg>
    </a>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <header class="mx-4 mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-[#24215f] px-5 py-6 text-white md:mx-6 md:mt-6 md:px-10 md:py-8">
            <div>
                <h1 class="text-xl font-bold md:text-3xl">{{ __('confirm.c_title') }}</h1>
                <p class="mt-2 text-xs text-white/75 md:text-sm">{{ __('confirm.c_sub') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-semibold text-white/90">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                {{ $statusText }}
            </span>
        </header>

        <div class="space-y-6 px-4 py-6 md:space-y-8 md:px-10 md:py-8">
            @if (session('reservation_created'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ __('confirm.reservation_created_notice') }}</div>
            @elseif (session('payment_submitted'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ __('confirm.proof_sent_notice') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif
            @if ($paymentStatus === 'rejected')
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" role="alert">
                    <p class="font-bold">{{ $loc === 'en' ? 'Your previous payment proof was rejected by Finance' : 'Bukti pembayaran sebelumnya ditolak oleh Finance' }}</p>
                    @if ($payment?->rejection_reason)
                        <p class="mt-1"><span class="font-semibold">{{ $loc === 'en' ? 'Reason / Follow-up:' : 'Alasan / Tindak Lanjut:' }}</span> {{ $payment->rejection_reason }}</p>
                    @endif
                    <p class="mt-1 text-xs text-red-700">{{ $loc === 'en' ? 'Please verify your transfer details and re-upload a valid payment proof below so your reservation can be confirmed.' : 'Silakan periksa kembali bukti transfer Anda dan unggah ulang bukti pembayaran yang sesuai di bawah ini agar reservasi dapat dikonfirmasi.' }}</p>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <section class="rounded-xl bg-[#f5f6fb] p-5 ring-1 ring-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('confirm.customer') }}</h2>
                    <p class="mt-2 text-lg font-medium text-slate-900">{{ $reservation->customer_name }}</p>
                    <p class="mt-3 flex items-center gap-2 break-all text-sm text-slate-700"><span aria-hidden="true">✉</span>{{ $reservation->customer_email }}</p>
                    <p class="mt-2 flex items-center gap-2 text-sm text-slate-700"><span aria-hidden="true">☎</span>{{ $reservation->customer_phone }}</p>
                </section>
                <section class="rounded-xl bg-[#f5f6fb] p-5 ring-1 ring-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">{{ __('confirm.location_time') }}</h2>
                    <p class="mt-2 font-medium text-slate-900">{{ $outlet->name }}{{ $reservationTable?->area?->name ? ' - ' . $reservationTable->area->name : '' }}</p>
                    @if ($reservation->eventContent)
                        <p class="mt-1 text-sm font-semibold text-[#24215f]">Event: {{ $reservation->eventContent->title }}</p>
                    @endif
                    <p class="mt-1 text-sm text-slate-700">{{ $reservation->reservation_date->locale($loc)->translatedFormat('l, d F Y') }}</p>
                    <p class="mt-2 text-sm text-slate-700">{{ substr($reservation->start_time, 0, 5) }} WIB</p>
                    <p class="mt-2 text-sm text-slate-700">{{ $reservationTable ? (__('confirm.table_word') . ' ' . $reservationTable->table_code . ' · ' . $reservationTable->area?->name) : 'Tanpa pilih kursi (Mengikuti Event)' }} · {{ $guestsText }}</p>
                </section>
            </div>

            <section>
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900"><span aria-hidden="true">▤</span>{{ __('confirm.order_details') }}</h2>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead><tr class="text-slate-600"><th class="px-4 py-3 font-medium">{{ __('ui.menu') }}</th><th class="px-4 py-3 text-right font-medium">{{ __('ui.unit_price') }}</th><th class="px-4 py-3 text-center font-medium">{{ __('ui.quantity') }}</th><th class="px-4 py-3 text-right font-medium">{{ __('ui.subtotal') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $menuItemsTotal = (float) $reservation->items->sum('line_total');
                                $eventPkgTotal = max(0, round((float) $reservation->subtotal - $menuItemsTotal, 2));
                            @endphp
                            @if ($eventPkgTotal > 0)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-[#24215f]">Paket Event: {{ $reservation->eventContent?->title ?? 'Event' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $money($reservation->guest_count > 0 ? $eventPkgTotal / $reservation->guest_count : $eventPkgTotal) }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $reservation->guest_count }}</td>
                                    <td class="px-4 py-3 text-right text-slate-800">{{ $money($eventPkgTotal) }}</td>
                                </tr>
                            @endif
                            @forelse ($reservation->items as $item)
                                <tr><td class="px-4 py-3 font-medium text-slate-800">{{ $item->item_name_snapshot }}</td><td class="px-4 py-3 text-right text-slate-600">{{ $money($item->unit_price_snapshot) }}</td><td class="px-4 py-3 text-center text-slate-600">{{ $item->quantity }}</td><td class="px-4 py-3 text-right text-slate-800">{{ $money($item->line_total) }}</td></tr>
                            @empty
                                @if ($eventPkgTotal <= 0)
                                    <tr><td colspan="4" class="px-4 py-5 text-center text-slate-500">{{ __('ui.no_menu_ordered') }}</td></tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                    <div class="flex justify-between gap-4 bg-[#f0f2fa] px-4 py-3 text-sm font-medium text-slate-700"><span>{{ __('ui.menu_subtotal') }}</span><span>{{ $money($reservation->subtotal) }}</span></div>
                </div>
                <dl class="ml-auto mt-2 max-w-md space-y-2 px-2 text-sm text-slate-700">
                    <div class="flex justify-between gap-4"><dt>{{ __('ui.service_charge', ['rate' => number_format((float) $reservation->service_rate_percent, 0)]) }}</dt><dd>{{ $money($reservation->service_amount) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('ui.tax', ['rate' => number_format((float) $reservation->tax_rate_percent, 0)]) }}</dt><dd>{{ $money($reservation->tax_amount) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-bold text-[#24215f]"><dt>{{ __('ui.total_payment') }}</dt><dd>{{ $money($reservation->grand_total) }}</dd></div>
                </dl>
            </section>

            <section>
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900"><span aria-hidden="true">▧</span>{{ __('confirm.notes_title') }}</h2>
                <div class="mt-2 min-h-20 rounded-xl bg-[#f5f6fb] px-4 py-4 text-sm text-slate-500 ring-1 ring-slate-200">{{ $reservation->notes ?: __('confirm.no_notes') }}</div>
            </section>

            <form id="payment-proof-form" method="POST" action="{{ route('reservasi.payment-proof', ['outlet' => $outlet->code, 'code' => $reservation->reservation_code]) }}" enctype="multipart/form-data">
                @csrf
                <section class="rounded-xl border border-slate-200 p-5 md:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><h2 class="font-bold text-[#24215f]">{{ __('confirm.bank_title') }}</h2>
                            @if ($bankNumber && $bankName && $bankHolder)
                                <p class="mt-2 text-sm text-slate-700">{{ $bankName }} <span class="px-1 text-slate-300">·</span> <span class="font-mono font-medium">{{ $bankNumber }}</span> <span class="px-1 text-slate-300">·</span> {{ __('confirm.bank_holder', ['name' => $bankHolder]) }}</p>
                            @else
                                <p class="mt-2 text-sm text-amber-800">{{ __('confirm.bank_missing_confirm') }}</p>
                            @endif
                        </div>
                        @if ($bankNumber)<button type="button" id="copy-bank" class="rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-[#24215f] hover:bg-indigo-100" data-account="{{ $bankNumber }}">{{ __('confirm.copy_account') }}</button>@endif
                    </div>

                    @if ($paymentStatus === 'pending_verification' || $confirmed)
                        <div class="mt-6 flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-[#fbfcfe] px-5 py-8 text-center">
                            <svg class="size-12 {{ $confirmed ? 'text-emerald-600' : 'text-amber-600' }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="24" cy="24" r="18"/><path d="m15 24 6 6 13-14"/></svg>
                            <span class="mt-3 font-semibold text-slate-800">{{ $confirmed ? __('confirm.verified_title') : __('confirm.sent_title') }}</span>
                            <span class="mt-2 text-sm text-slate-500">{{ $confirmed ? __('confirm.verified_body') : __('confirm.sent_body') }}</span>
                        </div>
                    @else
                        <div id="proof-dropzone" class="proof-upload-box">
                            <input id="payment-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="sr-only">

                            <label for="payment-proof" id="proof-empty-state" class="proof-empty-state">
                                <svg class="size-12 text-black" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M13 38a9 9 0 0 1-1-18 13 13 0 0 1 25-3 10.5 10.5 0 0 1-1.5 21H27V25.4l3.3 3.3 2.1-2.1L24 18.2l-8.4 8.4 2.1 2.1 3.3-3.3V38h-8Z"/></svg>
                                <span class="mt-3 font-semibold text-slate-800">{{ __('confirm.upload_title') }}</span>
                                <span class="mt-2 text-sm text-slate-500">{{ __('confirm.upload_hint') }}</span>
                                <span class="mt-2 text-xs text-slate-400">{{ __('confirm.upload_limits') }}</span>
                            </label>

                            <div id="proof-preview-box" class="proof-preview-state" hidden>
                                <div class="proof-preview-media" id="proof-preview-media" role="button" tabindex="0" aria-label="Klik untuk memperbesar pratinjau bukti transfer">
                                    <img id="proof-preview-img" src="" alt="Pratinjau bukti transfer" hidden>
                                    <iframe id="proof-preview-pdf" src="" title="Pratinjau PDF bukti transfer" hidden></iframe>
                                    <span class="proof-zoom-hint">🔍 Klik untuk perbesar</span>
                                </div>
                                <div class="proof-preview-bar">
                                    <div class="proof-preview-info">
                                        <span class="proof-preview-badge">Pratinjau Bukti Transfer</span>
                                        <p id="proof-preview-meta" class="proof-preview-filename"></p>
                                    </div>
                                    <div class="proof-preview-actions">
                                        <button type="button" id="proof-preview-view" class="proof-btn-view">Lihat Preview</button>
                                        <label for="payment-proof" class="proof-btn-change">Ganti file</label>
                                        <button type="button" id="proof-preview-clear" class="proof-btn-clear">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('payment_proof')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    @endif
                </section>
                <footer class="mt-6 grid gap-4 border-t border-slate-400 py-4 md:grid-cols-[.8fr_1.2fr] md:items-center md:px-4">
                    <div><p class="text-xs text-slate-500">{{ __('confirm.total_label') }}</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ $money($reservation->grand_total) }}</p></div>
                    @if ($confirmed || $paymentStatus === 'pending_verification')
                        <button type="button" disabled class="flex min-h-14 cursor-not-allowed items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white opacity-70">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                            {{ $confirmed ? __('confirm.btn_verified') : __('confirm.st_verifying') }}
                        </button>
                    @else
                        <button id="submit-proof" type="submit" disabled class="flex min-h-14 items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:opacity-50">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                            {{ __('confirm.btn_submit') }}
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
                <h2 id="payment-status-title" class="mt-3 text-lg font-semibold">{{ $confirmed ? __('confirm.m_confirmed') : __('confirm.m_received') }}</h2>
                @if (! $confirmed)
                    <p class="mt-2 inline-flex rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">{{ __('confirm.st_verifying') }}</p>
                @endif
            </header>
            <div class="p-5 md:px-7 md:py-6">
                <div class="rounded-lg border border-slate-300 p-4 text-center">
                    <p class="text-xs text-slate-500">{{ __('confirm.m_code') }}</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-[#24215f]">{{ $reservation->reservation_code }}</p>
                </div>
                <dl class="mt-5 space-y-2.5 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('confirm.m_guest') }}</dt><dd class="text-right font-medium text-slate-800">{{ $reservation->customer_name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('confirm.m_outlet') }}</dt><dd class="text-right font-medium text-slate-800">{{ $outlet->name }}{{ $reservationTable?->area?->name ? ' - ' . $reservationTable->area->name : '' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('confirm.m_when') }}</dt><dd class="text-right font-medium text-slate-800">{{ $reservation->reservation_date->locale($loc)->translatedFormat('d M Y') }} · {{ substr($reservation->start_time, 0, 5) }} WIB</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('confirm.m_guests') }}</dt><dd class="text-right font-medium text-slate-800">{{ $guestsText }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('confirm.m_table') }}</dt><dd class="text-right font-medium text-slate-800">{{ $reservationTable ? ($reservationTable->table_code . ' · ' . $reservationTable->area?->name) : 'Tanpa pilih kursi (Event)' }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-slate-100 pt-2"><dt class="text-slate-500">{{ __('confirm.m_total') }}</dt><dd class="text-right font-semibold text-slate-900">{{ $money($reservation->grand_total) }}</dd></div>
                </dl>
                @if ($confirmed && $qrDataUri)
                    <div class="mx-auto mt-5 w-fit rounded-lg border border-slate-200 bg-white p-2"><img src="{{ $qrDataUri }}" alt="{{ __('confirm.qr_alt', ['code' => $reservation->reservation_code]) }}" width="144" height="144" class="size-36"></div>
                @elseif (! $confirmed)
                    <div class="mx-auto mt-5 flex size-40 flex-col items-center justify-center rounded-lg border border-dashed border-amber-300 bg-amber-50 text-center" role="status" aria-label="{{ __('confirm.qr_locked_aria') }}">
                        <svg class="size-10 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3"/></svg>
                        <span class="mt-2 text-xs font-semibold text-amber-900">{{ __('confirm.qr_locked') }}</span>
                        <span class="mt-1 max-w-32 text-[10px] leading-tight text-amber-800">{{ __('confirm.qr_locked_body') }}</span>
                    </div>
                @endif
                <p class="mt-2 text-center text-[10px] text-slate-500">{{ $confirmed ? __('confirm.show_code') : __('confirm.payment_pending_note') }}</p>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#24215f] px-3 text-xs font-semibold text-white hover:bg-[#191744]">{{ __('confirm.to_home') }}</a>
                    @if ($confirmed && $qrDataUri)
                        <a href="{{ route('reservasi.qr-download', ['outlet' => $outlet->code, 'code' => $reservation->reservation_code]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-50 px-3 text-xs font-semibold text-[#24215f] hover:bg-indigo-100">{{ __('confirm.dl_qr') }}</a>
                    @else
                        <span aria-disabled="true" class="inline-flex min-h-10 cursor-not-allowed items-center justify-center rounded-lg bg-slate-100 px-3 text-center text-[10px] font-semibold text-slate-400">{{ __('confirm.dl_after') }}</span>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endif

{{-- Modal Popup Preview Bukti Transfer (Zoom & Rotasi) --}}
<div id="customer-proof-modal" class="cp-modal" hidden role="dialog" aria-modal="true" aria-labelledby="cp-modal-title">
    <div class="cp-card">
        <div class="cp-head">
            <div class="cp-head-info">
                <h3 id="cp-modal-title" class="cp-title">Pratinjau Bukti Transfer</h3>
                <p id="cp-modal-subtitle" class="cp-subtitle"></p>
            </div>
            <div class="cp-tools">
                <button type="button" id="cp-zoom-out" class="cp-tool-btn" title="Perkecil">−</button>
                <span id="cp-zoom-label" class="cp-tool-label">100%</span>
                <button type="button" id="cp-zoom-in" class="cp-tool-btn" title="Perbesar">+</button>
                <span class="cp-tool-sep"></span>
                <button type="button" id="cp-rotate-left" class="cp-tool-btn" title="Putar kiri 90°">↺</button>
                <span id="cp-rotate-label" class="cp-tool-label" style="min-width:34px;">0°</span>
                <button type="button" id="cp-rotate-right" class="cp-tool-btn" title="Putar kanan 90°">↻</button>
                <button type="button" id="cp-zoom-reset" class="cp-reset-btn">Reset</button>
                <button type="button" id="cp-modal-close" class="cp-close-btn" aria-label="Tutup pratinjau">✕</button>
            </div>
        </div>
        <div id="cp-modal-viewport" class="cp-viewport">
            <div id="cp-modal-stage" class="cp-stage">
                <img id="cp-modal-img" src="" alt="Pratinjau penuh bukti transfer" draggable="false" hidden>
                <iframe id="cp-modal-frame" src="" title="Pratinjau penuh PDF bukti transfer" hidden></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const T = @json($jsText);
    const proofInput = document.getElementById('payment-proof');
    const submit = document.getElementById('submit-proof');
    const form = document.getElementById('payment-proof-form');
    const dropzone = document.getElementById('proof-dropzone');
    const emptyState = document.getElementById('proof-empty-state');
    const previewBox = document.getElementById('proof-preview-box');
    const previewMedia = document.getElementById('proof-preview-media');
    const previewMeta = document.getElementById('proof-preview-meta');
    const previewImg = document.getElementById('proof-preview-img');
    const previewPdf = document.getElementById('proof-preview-pdf');
    const previewViewBtn = document.getElementById('proof-preview-view');
    const previewClear = document.getElementById('proof-preview-clear');

    const cpModal = document.getElementById('customer-proof-modal');
    const cpTitle = document.getElementById('cp-modal-title');
    const cpSubtitle = document.getElementById('cp-modal-subtitle');
    const cpViewport = document.getElementById('cp-modal-viewport');
    const cpStage = document.getElementById('cp-modal-stage');
    const cpImg = document.getElementById('cp-modal-img');
    const cpFrame = document.getElementById('cp-modal-frame');
    const cpZoomLabel = document.getElementById('cp-zoom-label');
    const cpRotateLabel = document.getElementById('cp-rotate-label');

    let previewUrl = null;
    let currentFileName = '';
    let currentFileMeta = '';
    let currentIsImage = true;
    let hasProofFile = false;

    let cpZoom = 1;
    let cpRotation = 0;
    let cpBaseW = 0;
    let cpBaseH = 0;

    const applyModalTransform = () => {
        if (cpZoomLabel) cpZoomLabel.textContent = Math.round(cpZoom * 100) + '%';
        const normAngle = ((cpRotation % 360) + 360) % 360;
        if (cpRotateLabel) cpRotateLabel.textContent = normAngle + '°';

        if (currentIsImage) {
            if (cpBaseW > 0 && cpBaseH > 0) {
                const w = Math.round(cpBaseW * cpZoom);
                const h = Math.round(cpBaseH * cpZoom);
                const rad = (cpRotation * Math.PI) / 180;
                cpStage.style.width = Math.ceil(Math.abs(w * Math.cos(rad)) + Math.abs(h * Math.sin(rad))) + 'px';
                cpStage.style.height = Math.ceil(Math.abs(w * Math.sin(rad)) + Math.abs(h * Math.cos(rad))) + 'px';
                cpImg.style.width = w + 'px';
                cpImg.style.height = h + 'px';
            }
            cpImg.style.transform = `rotate(${cpRotation}deg)`;
        } else {
            cpStage.style.width = '';
            cpStage.style.height = '';
            cpFrame.style.transform = `scale(${cpZoom}) rotate(${cpRotation}deg)`;
        }
    };

    const closeProofModal = () => {
        if (!cpModal) return;
        cpModal.hidden = true;
        if (cpImg) { cpImg.hidden = true; cpImg.src = ''; }
        if (cpFrame) { cpFrame.hidden = true; cpFrame.src = ''; }
        document.body.style.overflow = '';
    };

    const openProofModal = () => {
        if (!cpModal || !previewUrl || !hasProofFile) return;
        cpZoom = 1;
        cpRotation = 0;
        cpBaseW = 0;
        cpBaseH = 0;
        cpStage.style.width = '';
        cpStage.style.height = '';
        cpTitle.textContent = currentFileName || 'Pratinjau Bukti Transfer';
        cpSubtitle.textContent = currentFileMeta || '';
        cpModal.hidden = false;
        document.body.style.overflow = 'hidden';

        if (currentIsImage) {
            cpFrame.hidden = true;
            cpFrame.src = '';
            cpImg.hidden = false;
            cpImg.style.width = '';
            cpImg.style.height = '';
            cpImg.style.maxHeight = '70vh';
            cpImg.style.maxWidth = '82vw';
            cpImg.style.transform = 'rotate(0deg)';
            cpImg.onload = () => {
                cpBaseW = cpImg.clientWidth || Math.min(cpImg.naturalWidth, 720);
                cpBaseH = cpImg.clientHeight || Math.round((cpBaseW * cpImg.naturalHeight) / Math.max(1, cpImg.naturalWidth));
                cpImg.style.maxHeight = 'none';
                cpImg.style.maxWidth = 'none';
                applyModalTransform();
            };
            cpImg.src = previewUrl;
        } else {
            cpImg.hidden = true;
            cpImg.src = '';
            cpFrame.hidden = false;
            cpFrame.style.transform = '';
            cpFrame.src = previewUrl;
        }
        applyModalTransform();
    };

    const clearPreview = () => {
        closeProofModal();
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        if (proofInput) proofInput.value = '';
        currentFileName = '';
        currentFileMeta = '';
        hasProofFile = false;
        if (emptyState) emptyState.hidden = false;
        if (previewBox) previewBox.hidden = true;
        if (previewImg) { previewImg.hidden = true; previewImg.src = ''; }
        if (previewPdf) { previewPdf.hidden = true; previewPdf.src = ''; }
        if (submit) submit.disabled = true;
    };
    proofInput?.addEventListener('change', () => {
        const file = proofInput.files?.[0];
        if (!file) { clearPreview(); return; }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        hasProofFile = true;
        currentIsImage = file.type !== 'application/pdf';
        const kb = Math.max(1, Math.round(file.size / 1024));
        const sizeLabel = kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
        const label = `${file.name} (${sizeLabel})`;
        currentFileName = file.name;
        currentFileMeta = `${currentIsImage ? 'Gambar' : 'PDF'} · ${sizeLabel}`;
        if (previewMeta) previewMeta.textContent = label;
        if (emptyState) emptyState.hidden = true;
        if (previewBox) previewBox.hidden = false;
        if (!currentIsImage) {
            if (previewImg) { previewImg.hidden = true; previewImg.src = ''; }
            if (previewPdf) { previewPdf.src = previewUrl; previewPdf.hidden = false; }
        } else {
            if (previewPdf) { previewPdf.hidden = true; previewPdf.src = ''; }
            if (previewImg) { previewImg.src = previewUrl; previewImg.hidden = false; }
        }
        if (submit) submit.disabled = false;
    });
    previewMedia?.addEventListener('click', openProofModal);
    previewMedia?.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openProofModal(); }
    });
    previewViewBtn?.addEventListener('click', openProofModal);
    previewClear?.addEventListener('click', clearPreview);

    document.getElementById('cp-zoom-in')?.addEventListener('click', () => { cpZoom = Math.min(4, +(cpZoom + 0.25).toFixed(2)); applyModalTransform(); });
    document.getElementById('cp-zoom-out')?.addEventListener('click', () => { cpZoom = Math.max(0.25, +(cpZoom - 0.25).toFixed(2)); applyModalTransform(); });
    document.getElementById('cp-rotate-left')?.addEventListener('click', () => { cpRotation -= 90; applyModalTransform(); });
    document.getElementById('cp-rotate-right')?.addEventListener('click', () => { cpRotation += 90; applyModalTransform(); });
    document.getElementById('cp-zoom-reset')?.addEventListener('click', () => { cpZoom = 1; cpRotation = 0; applyModalTransform(); });
    document.getElementById('cp-modal-close')?.addEventListener('click', closeProofModal);
    cpModal?.addEventListener('click', event => { if (event.target === cpModal) closeProofModal(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && cpModal && !cpModal.hidden) closeProofModal(); });

    let lastWheelAt = 0;
    cpViewport?.addEventListener('wheel', event => {
        if (!cpModal || cpModal.hidden) return;
        event.preventDefault();
        const now = Date.now();
        if (now - lastWheelAt < 20 || Math.abs(event.deltaY) < 2) return;
        lastWheelAt = now;
        cpZoom = Math.min(4, Math.max(0.25, +(cpZoom + (event.deltaY < 0 ? 0.15 : -0.15)).toFixed(2)));
        applyModalTransform();
    }, { passive: false });

    let cpDragging = false, cpStartX = 0, cpStartY = 0, cpScrollX = 0, cpScrollY = 0;
    cpViewport?.addEventListener('mousedown', event => {
        if (event.button !== 0 || event.target === cpFrame) return;
        cpDragging = true; cpStartX = event.pageX; cpStartY = event.pageY;
        cpScrollX = cpViewport.scrollLeft; cpScrollY = cpViewport.scrollTop;
        cpImg?.classList.add('is-grabbing');
    });
    window.addEventListener('mousemove', event => {
        if (!cpDragging) return;
        cpViewport.scrollLeft = cpScrollX - (event.pageX - cpStartX);
        cpViewport.scrollTop = cpScrollY - (event.pageY - cpStartY);
    });
    window.addEventListener('mouseup', () => {
        if (!cpDragging) return;
        cpDragging = false;
        cpImg?.classList.remove('is-grabbing');
    });
    dropzone?.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('is-dragover'); });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('is-dragover'));
    dropzone?.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragover');
        if (event.dataTransfer?.files?.length && proofInput) {
            proofInput.files = event.dataTransfer.files;
            proofInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    form?.addEventListener('submit', () => { if (submit) { submit.disabled = true; submit.textContent = T.sending; } });
    document.getElementById('copy-bank')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        try { await navigator.clipboard.writeText(button.dataset.account); button.textContent = T.copied; setTimeout(() => button.textContent = T.copy, 1800); }
        catch { button.textContent = button.dataset.account; }
    });
    @if ($paymentStatus === 'pending_verification')
        setTimeout(() => window.location.reload(), 30000);
    @endif
})();
</script>
@endpush
