@extends('layouts.manager')

@section('title', __('manager.res_detail_title'))
@section('active-outlet', $reservation->outlet?->name ?? __('manager.all_outlets'))

@section('content')
    @php
        $payment = $reservation->latestPayment;
        $tone = match ($reservation->reservation_status) {
            'confirmed', 'completed' => 'bg-emerald-50 text-emerald-700',
            'partially_arrived', 'checked_in' => 'bg-indigo-50 text-indigo-700',
            'pending_payment' => 'bg-amber-50 text-amber-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-600',
        };
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <a href="{{ route('manager.reservations.index', ['date' => $reservation->reservation_date->toDateString(), 'outlet' => $reservation->outlets_id]) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-[#201f5c]">← {{ __('manager.back_to_reservations') }}</a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $reservation->reservation_code }}</p>
                <h1 class="mt-1 text-2xl font-bold text-[#151442] md:text-3xl">{{ $reservation->customer_name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $reservation->outlet?->name }} · {{ $reservation->reservation_date->locale(app()->getLocale())->translatedFormat('d F Y') }} · {{ substr((string) $reservation->start_time, 0, 5) }}</p>
            </div>
            <span class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $tone }}">{{ $reservationStatus }}</span>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_guest_details') }}</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_guest_name') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_name }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_phone') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_phone }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-400">{{ __('manager.res_email') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->customer_email }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_visit_details') }}</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.outlet_label') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->outlet?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_guest_count') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->guest_count }} {{ __('manager.res_guest_unit') }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_tables') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $reservation->tables->map(fn ($table) => $table->table_code . ($table->area ? ' · ' . $table->area->name : ''))->join(', ') ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_duration') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }}</dd></div>
                    @if ($reservation->notes)<div class="sm:col-span-2"><dt class="text-xs text-slate-400">{{ __('manager.res_notes') }}</dt><dd class="mt-1 text-slate-700">{{ $reservation->notes }}</dd></div>@endif
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_payment_details') }}</h2>
                @if ($payment)
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-400">{{ __('manager.res_col_payment') }}</dt><dd class="mt-1 font-medium text-slate-700">{{ $paymentStatus }}</dd></div>
                        <div><dt class="text-xs text-slate-400">{{ __('manager.res_amount') }}</dt><dd class="mt-1 font-semibold text-[#151442]">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</dd></div>
                        @if ($payment->rejection_reason)<div class="sm:col-span-2"><dt class="text-xs text-slate-400">{{ __('manager.res_payment_note') }}</dt><dd class="mt-1 text-slate-700">{{ $payment->rejection_reason }}</dd></div>@endif
                    </dl>
                @else
                    <p class="mt-4 text-sm text-slate-400">{{ __('manager.res_no_payment') }}</p>
                @endif
                <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm">
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_subtotal') }}</dt><dd class="mt-1 text-slate-700">Rp{{ number_format((float) $reservation->subtotal, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ __('manager.res_grand_total') }}</dt><dd class="mt-1 font-semibold text-[#151442]">Rp{{ number_format((float) $reservation->grand_total, 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_menu_details') }}</h2>
                @if ($reservation->items->isNotEmpty())
                    <ul class="mt-4 divide-y divide-slate-100 text-sm">
                        @foreach ($reservation->items as $item)
                            <li class="flex justify-between gap-3 py-2"><span class="text-slate-700">{{ $item->quantity }} × {{ $item->item_name_snapshot }}</span><span class="shrink-0 font-medium text-slate-700">Rp{{ number_format((float) $item->line_total, 0, ',', '.') }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-400">{{ __('manager.res_no_menu') }}</p>
                @endif
            </section>
        </div>

        @if ($cancellation)
            <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-[#151442]">{{ __('manager.res_cancellation_details') }}</h2>
                        <p class="mt-2 text-sm text-slate-700">{{ $cancellation->reason }}</p>
                        @if ($cancellation->decision_note)<p class="mt-1 text-xs text-slate-500">{{ __('manager.res_decision_note') }}: {{ $cancellation->decision_note }}</p>@endif
                    </div>
                    @if ($cancellation->status === 'pending')
                        <a href="{{ route('manager.cancellations.index', ['selected' => $cancellation->cancellation_requests_id]) }}" class="rounded-lg bg-[#201f5c] px-4 py-2 text-sm font-semibold text-white">{{ __('manager.review_cancellation') }}</a>
                    @else
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ __('manager.cancel_status_' . $cancellation->status) }}</span>
                    @endif
                </div>
            </section>
        @endif

        @if ($reservation->checkIns->isNotEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#151442]">{{ __('manager.res_checkin_history') }}</h2>
                <div class="mt-3 divide-y divide-slate-100 text-sm">
                    @foreach ($reservation->checkIns->sortBy('checked_in_at') as $checkIn)
                        <p class="flex flex-wrap justify-between gap-2 py-2 text-slate-600"><span>{{ $checkIn->checked_in_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }} · {{ $checkIn->checkedInBy?->name ?? __('manager.activity_system') }}</span><span>{{ $checkIn->guest_count_arrived }} {{ __('manager.res_guest_unit') }}</span></p>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
