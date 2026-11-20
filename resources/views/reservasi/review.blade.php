@extends('layouts.app')

@section('title', __('ui.review_order') . ' · ' . $outlet->name)

@push('styles')
<style>
    .proof-upload-box {
        margin-top: 20px;
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
    #confirm-reservation:disabled { opacity: .5; cursor: not-allowed; }
</style>
@endpush

@section('content')
@php
    $itemsById = $menuItems->keyBy('menu_items_id');
    $lines = collect($selectedMenuItems)->map(function ($quantity, $id) use ($itemsById) {
        $item = $itemsById->get($id);
        return $item ? ['item' => $item, 'quantity' => (int) $quantity, 'total' => (float) $item->price * (int) $quantity] : null;
    })->filter();
    $menuSubtotal = $lines->sum('total');
    $eventPricePerPax = (float) ($flow['price_per_pax'] ?? 0);
    $eventSubtotal = round($eventPricePerPax * (int) ($flow['guest_count'] ?? 0), 2);
    $subtotal = round($menuSubtotal + $eventSubtotal, 2);
    $requiresMenu = (bool) ($flow['requires_menu'] ?? true);
    $requiresTable = (bool) ($flow['requires_table'] ?? true);
    $activeRate = \App\Models\TaxServiceRate::activeFor((int) $outlet->outlets_id, $flow['reservation_date']);
    $serviceRate = (float) ($activeRate?->service_rate ?? 5);
    $taxRate = (float) ($activeRate?->tax_rate ?? 10);
    $serviceAmount = round($subtotal * $serviceRate / 100, 2);
    $taxAmount = round(($subtotal + $serviceAmount) * $taxRate / 100, 2);
    $grandTotal = $subtotal + $serviceAmount + $taxAmount;
    $money = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $settings = app(\App\Services\ReservationSettings::class);
    $bankName = $settings->bankName();
    $bankNumber = $settings->bankAccountNumber();
    $bankHolder = $settings->bankAccountHolder();
    $jsText = ['copy' => __('confirm.copy_account'), 'copied' => __('confirm.account_copied')];
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
            <a href="{{ route('reservasi.outlet', ['outlet' => $outlet->code, 'step' => $requiresMenu ? 'menu' : 'tables']) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white/15 px-4 text-sm font-semibold text-white transition hover:bg-white/25">✎ {{ __('ui.edit_order') }}</a>
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
                    <p class="mt-3 text-xl font-medium text-slate-900">{{ $outlet->name }}@if($selectedTable?->area?->name) · {{ $selectedTable->area->name }}@endif</p>
                    @if (! empty($flow['event_title']))
                        <p class="mt-1 text-sm font-semibold text-[#24215f]">Event: {{ $flow['event_title'] }}</p>
                    @endif
                    <p class="mt-2 text-sm text-slate-600">{{ \Carbon\Carbon::parse($flow['reservation_date'])->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ substr($flow['start_time'], 0, 5) }} WIB · {{ $selectedTable ? __('ui.table_word') . ' ' . $selectedTable->table_code : 'Tanpa pilih kursi (Mengikuti Event)' }} · {{ $flow['guest_count'] }} {{ __('ui.guests') }}</p>
                </section>
            </div>

            <section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('ui.menu_order_details') }}</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 font-semibold">{{ __('ui.menu') }}</th><th class="px-4 py-3 text-right font-semibold">{{ __('ui.unit_price') }}</th><th class="px-4 py-3 text-center font-semibold">{{ __('ui.quantity') }}</th><th class="px-4 py-3 text-right font-semibold">{{ __('ui.subtotal') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @if ($eventSubtotal > 0)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-[#24215f]">Paket Event: {{ $flow['event_title'] ?? 'Event' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $money($eventPricePerPax) }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $flow['guest_count'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $money($eventSubtotal) }}</td>
                                </tr>
                            @endif
                            @forelse ($lines as $line)
                                <tr><td class="px-4 py-3 font-medium text-slate-800">{{ $line['item']->name }}</td><td class="px-4 py-3 text-right text-slate-600">{{ $money($line['item']->price) }}</td><td class="px-4 py-3 text-center text-slate-600">{{ $line['quantity'] }}</td><td class="px-4 py-3 text-right font-medium text-slate-800">{{ $money($line['total']) }}</td></tr>
                            @empty
                                @if ($eventSubtotal <= 0)
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">{{ $requiresMenu ? __('ui.no_menu_ordered') : 'Event ini tidak memerlukan pemilihan menu.' }}</td></tr>
                                @endif
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

                <div id="proof-upload-box" class="proof-upload-box">
                    <input id="review-payment-proof" name="payment_proof" form="reservation-confirm-form" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="sr-only">

                    <label for="review-payment-proof" id="review-proof-empty" class="proof-empty-state">
                        <svg class="size-10 text-slate-700" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true"><path d="M13 38a9 9 0 0 1-1-18 13 13 0 0 1 25-3 10.5 10.5 0 0 1-1.5 21H27V25.4l3.3 3.3 2.1-2.1L24 18.2l-8.4 8.4 2.1 2.1 3.3-3.3V38h-8Z"/></svg>
                        <span class="mt-2.5 text-sm font-semibold text-slate-800">{{ __('confirm.upload_title') }}</span>
                        <span class="mt-1 text-xs text-slate-500">{{ __('confirm.upload_hint') }}</span>
                        <span class="mt-1 text-[11px] text-slate-400">{{ __('confirm.upload_limits') }}</span>
                    </label>

                    <div id="review-proof-preview" class="proof-preview-state" hidden>
                        <div class="proof-preview-media" id="review-proof-media" role="button" tabindex="0" aria-label="Klik untuk memperbesar pratinjau bukti transfer">
                            <img id="review-proof-img" src="" alt="Pratinjau bukti transfer" hidden>
                            <iframe id="review-proof-pdf" src="" title="Pratinjau PDF bukti transfer" hidden></iframe>
                            <span class="proof-zoom-hint">🔍 Klik untuk perbesar</span>
                        </div>
                        <div class="proof-preview-bar">
                            <div class="proof-preview-info">
                                <span class="proof-preview-badge">Pratinjau Bukti Transfer</span>
                                <p id="review-proof-name" class="proof-preview-filename"></p>
                            </div>
                            <div class="proof-preview-actions">
                                <button type="button" id="review-proof-view" class="proof-btn-view">Lihat Preview</button>
                                <label for="review-payment-proof" class="proof-btn-change">Ganti file</label>
                                <button type="button" id="review-proof-clear" class="proof-btn-clear">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
                @error('payment_proof')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <form id="reservation-confirm-form" method="POST" action="{{ route('reservasi.confirm', $outlet->code) }}" enctype="multipart/form-data" class="mt-6">
                @csrf
                <div class="grid gap-4 border-t border-slate-400 py-4 md:grid-cols-[.8fr_1.2fr] md:items-center">
                    <div><p class="text-xs text-slate-500">{{ __('confirm.total_label') }}</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ $money($grandTotal) }}</p></div>
                    <div>
                        <button id="confirm-reservation" type="submit" disabled class="flex min-h-14 w-full items-center justify-center gap-3 rounded-xl bg-[#24215f] px-6 py-4 text-sm font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:opacity-50">
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
    const form = document.getElementById('reservation-confirm-form');
    const countdown = document.getElementById('hold-countdown');
    const button = document.getElementById('confirm-reservation');
    const uploadBox = document.getElementById('proof-upload-box');
    const proofInput = document.getElementById('review-payment-proof');
    const emptyState = document.getElementById('review-proof-empty');
    const previewState = document.getElementById('review-proof-preview');
    const previewMedia = document.getElementById('review-proof-media');
    const proofName = document.getElementById('review-proof-name');
    const proofImg = document.getElementById('review-proof-img');
    const proofPdf = document.getElementById('review-proof-pdf');
    const proofViewBtn = document.getElementById('review-proof-view');
    const proofClear = document.getElementById('review-proof-clear');

    const cpModal = document.getElementById('customer-proof-modal');
    const cpTitle = document.getElementById('cp-modal-title');
    const cpSubtitle = document.getElementById('cp-modal-subtitle');
    const cpViewport = document.getElementById('cp-modal-viewport');
    const cpStage = document.getElementById('cp-modal-stage');
    const cpImg = document.getElementById('cp-modal-img');
    const cpFrame = document.getElementById('cp-modal-frame');
    const cpZoomLabel = document.getElementById('cp-zoom-label');
    const cpRotateLabel = document.getElementById('cp-rotate-label');

    const deadline = Number(countdown.dataset.expires) * 1000;
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

    const syncSubmitState = () => {
        const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        button.disabled = seconds <= 0 || !hasProofFile;
    };

    const clearProofPreview = () => {
        closeProofModal();
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        if (proofInput) proofInput.value = '';
        currentFileName = '';
        currentFileMeta = '';
        hasProofFile = false;
        if (emptyState) emptyState.hidden = false;
        if (previewState) previewState.hidden = true;
        if (proofImg) { proofImg.hidden = true; proofImg.src = ''; }
        if (proofPdf) { proofPdf.hidden = true; proofPdf.src = ''; }
        syncSubmitState();
    };

    proofInput?.addEventListener('change', () => {
        const file = proofInput.files?.[0];
        if (!file) { clearProofPreview(); return; }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        hasProofFile = true;
        currentIsImage = file.type !== 'application/pdf';
        const kb = Math.max(1, Math.round(file.size / 1024));
        const sizeLabel = kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
        currentFileName = file.name;
        currentFileMeta = `${currentIsImage ? 'Gambar' : 'PDF'} · ${sizeLabel}`;
        proofName.textContent = `${file.name} (${sizeLabel})`;
        if (emptyState) emptyState.hidden = true;
        if (previewState) previewState.hidden = false;
        if (!currentIsImage) {
            if (proofImg) { proofImg.hidden = true; proofImg.src = ''; }
            if (proofPdf) { proofPdf.src = previewUrl; proofPdf.hidden = false; }
        } else {
            if (proofPdf) { proofPdf.hidden = true; proofPdf.src = ''; }
            if (proofImg) { proofImg.src = previewUrl; proofImg.hidden = false; }
        }
        syncSubmitState();
    });

    previewMedia?.addEventListener('click', openProofModal);
    previewMedia?.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openProofModal(); }
    });
    proofViewBtn?.addEventListener('click', openProofModal);
    proofClear?.addEventListener('click', clearProofPreview);

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

    uploadBox?.addEventListener('dragover', event => {
        event.preventDefault();
        uploadBox.classList.add('is-dragover');
    });
    uploadBox?.addEventListener('dragleave', () => uploadBox.classList.remove('is-dragover'));
    uploadBox?.addEventListener('drop', event => {
        event.preventDefault();
        uploadBox.classList.remove('is-dragover');
        if (event.dataTransfer?.files?.length && proofInput) {
            proofInput.files = event.dataTransfer.files;
            proofInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    document.getElementById('copy-bank')?.addEventListener('click', async event => {
        const copyBtn = event.currentTarget;
        try { await navigator.clipboard.writeText(copyBtn.dataset.account); copyBtn.textContent = T.copied; setTimeout(() => copyBtn.textContent = T.copy, 1800); }
        catch { copyBtn.textContent = copyBtn.dataset.account; }
    });
    form.addEventListener('submit', () => { button.disabled = true; });
    syncSubmitState();
    window.setInterval(syncSubmitState, 1000);
})();
</script>
@endpush
