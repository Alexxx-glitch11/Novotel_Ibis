@extends('layouts.manager')

@section('title', __('manager.res_title'))
@section('active-outlet', $filters['outlet'] !== 'all' ? $outlets->firstWhere('outlets_id', (int) $filters['outlet'])?->name : __('manager.all_outlets'))

@section('content')
    @php
        $reservationTone = [
            'confirmed' => 'bg-emerald-50 text-emerald-700',
            'partially_arrived' => 'bg-sky-50 text-sky-700',
            'checked_in' => 'bg-indigo-50 text-indigo-700',
            'completed' => 'bg-indigo-50 text-indigo-700',
            'pending_payment' => 'bg-amber-50 text-amber-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
            'expired' => 'bg-slate-100 text-slate-600',
            'no_show' => 'bg-slate-100 text-slate-600',
            'draft' => 'bg-slate-100 text-slate-600',
        ];
        $paymentTone = [
            'paid' => 'text-emerald-700',
            'pending_verification' => 'text-amber-700',
            'rejected' => 'text-rose-700',
            'unpaid' => 'text-slate-500',
        ];
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-[#151442] md:text-3xl">{{ __('manager.res_title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('manager.res_sub') }}</p>
            </div>
            <a href="{{ route('manager.reservations.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('manager.res_export') }}
            </a>
        </div>

        <form method="GET" action="{{ route('manager.reservations.index') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.35fr_auto] xl:items-end">
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.res_filter_date') }}
                <input type="date" name="date" value="{{ $filters['date'] }}" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-[#fbfcfe] px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.outlet_label') }}
                <select name="outlet" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-[#fbfcfe] px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.all_outlets') }}</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->outlets_id }}" @selected($filters['outlet'] === (string) $outlet->outlets_id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.res_filter_status') }}
                <select name="status" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-[#fbfcfe] px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.res_all_status') }}</option>
                    @foreach ($reservationStatuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('manager.res_status_' . $status) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.res_search_label') }}
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('manager.res_search_placeholder') }}" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-[#fbfcfe] px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none">
            </label>
            <button class="rounded-lg bg-[#201f5c] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#151442]">{{ __('manager.apply_filters') }}</button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['R', 'blue', __('manager.res_total'), $stats['total'], $dateLabel],
                ['…', 'amber', __('manager.res_waiting'), $stats['waiting'], __('manager.res_waiting_note')],
                ['✓', 'green', __('manager.res_confirmed'), $stats['confirmed'], __('manager.res_confirmed_note')],
                ['↗', 'violet', __('manager.res_arrived'), $stats['arrived'], __('manager.res_arrived_note')],
            ] as [$letter, $tone, $label, $value, $note])
                <article class="flex min-h-24 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ ['blue' => 'bg-indigo-50 text-indigo-800', 'amber' => 'bg-amber-50 text-amber-700', 'green' => 'bg-emerald-50 text-emerald-700', 'violet' => 'bg-violet-50 text-violet-700'][$tone] }} text-sm font-bold">{{ $letter }}</span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                        <p class="mt-0.5 text-2xl font-bold leading-none text-[#151442]">{{ $value }}</p>
                        <p class="mt-2 truncate text-xs text-slate-400">{{ $note }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-[#151442]">{{ __('manager.res_list') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-400">{{ trans_choice('manager.res_count_note', $reservations->total(), ['count' => $reservations->total(), 'date' => $dateLabel]) }}</p>
                </div>
                <p class="text-xs text-slate-400">{{ __('manager.res_last_updated') }} {{ $lastUpdated ?? '—' }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-xs">
                    <thead class="bg-[#f6f7fb] text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">{{ __('manager.res_col_code_guest') }}</th>
                            <th class="px-4 py-3">{{ __('manager.res_col_outlet_table') }}</th>
                            <th class="px-4 py-3">{{ __('manager.res_col_schedule') }}</th>
                            <th class="px-4 py-3">{{ __('manager.res_col_guests') }}</th>
                            <th class="px-4 py-3">{{ __('manager.res_col_payment') }}</th>
                            <th class="px-4 py-3">{{ __('manager.res_col_status') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('manager.res_col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reservations as $reservation)
                            @php
                                $paymentStatus = $reservation->latestPayment?->payment_status;
                                $tables = $reservation->tables->pluck('table_code')->join(', ');
                            @endphp
                            <tr class="align-middle hover:bg-slate-50/70">
                                <td class="px-5 py-3.5">
                                    <p class="text-[10px] text-slate-400">{{ $reservation->reservation_code }}</p>
                                    <p class="mt-0.5 font-semibold text-slate-800">{{ $reservation->customer_name }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-medium text-slate-700">{{ $reservation->outlet?->name ?? '—' }}</p>
                                    <p class="mt-0.5 text-slate-400">{{ $tables !== '' ? $tables : '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-600">{{ $reservation->reservation_date->locale(app()->getLocale())->translatedFormat('d M') }} · {{ substr((string) $reservation->start_time, 0, 5) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-600">{{ $reservation->guest_count }} {{ __('manager.res_guest_unit') }}</td>
                                <td class="px-4 py-3.5 font-medium {{ $paymentTone[$paymentStatus] ?? 'text-slate-500' }}">{{ $paymentStatus ? __('manager.pay_status_' . $paymentStatus) : __('manager.res_no_payment') }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-[10px] font-semibold {{ $reservationTone[$reservation->reservation_status] ?? 'bg-slate-100 text-slate-600' }}">{{ __('manager.res_status_' . $reservation->reservation_status) }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ route('manager.reservations.show', $reservation) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 font-semibold text-[#201f5c] transition hover:bg-indigo-50">
                                        {{ __('manager.res_detail') }} <span aria-hidden="true">→</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">{{ __('manager.res_empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reservations->hasPages() || $reservations->total())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                    <span>{{ trans_choice('manager.pagination_showing', $reservations->count(), ['from' => $reservations->firstItem() ?? 0, 'to' => $reservations->lastItem() ?? 0, 'total' => $reservations->total()]) }}</span>
                    {{ $reservations->onEachSide(1)->links() }}
                </div>
            @endif
        </section>

        <p class="text-xs text-slate-400">{{ __('manager.res_footer') }}</p>
    </div>
@endsection
