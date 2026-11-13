@extends('layouts.app')

@section('title', __('history.st_title_' . $cancellation->status))

@section('content')
@php
    $s   = $cancellation->status;   // pending | approved | rejected
    $loc = app()->getLocale();
    $fmt = fn ($d) => $d->copy()->locale($loc)->translatedFormat('j M Y') . ', ' . $d->format('H.i') . ' WIB';

    $reqCode  = 'CXL-' . $cancellation->created_at->format('Ymd') . '-' . str_pad((string) $cancellation->cancellation_requests_id, 3, '0', STR_PAD_LEFT);
    $reviewed = $cancellation->reviewed_at ? $fmt($cancellation->reviewed_at) : '—';
    $current  = __('history.st_' . $reservation->reservation_status);

    $banner = match ($s) {
        'approved' => ['bg' => 'bg-emerald-50 ring-emerald-100 text-emerald-800', 't' => __('history.banner_approved_t'),
                       'b' => __('history.banner_approved_b', ['date' => $cancellation->reviewed_at ? $cancellation->reviewed_at->copy()->locale($loc)->translatedFormat('j F Y') : '—'])],
        'rejected' => ['bg' => 'bg-red-50 ring-red-100 text-red-800', 't' => __('history.banner_rejected_t'), 'b' => __('history.banner_rejected_b')],
        default    => ['bg' => 'bg-amber-50 ring-amber-100 text-amber-900', 't' => __('history.banner_pending_t'), 'b' => __('history.banner_pending_b')],
    };

    $steps = match ($s) {
        'approved' => [
            ['done' => true, 'text' => __('history.tl_sent', ['time' => $fmt($cancellation->created_at)])],
            ['done' => true, 'text' => __('history.tl_approved', ['time' => $reviewed])],
            ['done' => true, 'text' => __('history.tl_status_cancelled')],
        ],
        'rejected' => [
            ['done' => true, 'text' => __('history.tl_sent', ['time' => $fmt($cancellation->created_at)])],
            ['done' => true, 'text' => __('history.tl_reviewed', ['time' => $reviewed])],
            ['done' => true, 'text' => __('history.tl_status_kept', ['status' => $current])],
        ],
        default => [
            ['done' => true,  'text' => __('history.tl_sent', ['time' => $fmt($cancellation->created_at)])],
            ['done' => false, 'text' => __('history.tl_waiting')],
            ['done' => false, 'text' => __('history.tl_result_later')],
        ],
    };

    $result = match ($s) {
        'approved' => ['cls' => 'bg-emerald-50 text-emerald-800', 't' => __('history.box_approved_t'), 'b' => __('history.box_approved_b')],
        'rejected' => ['cls' => 'bg-red-50 text-red-800', 't' => __('history.box_rejected_t'), 'b' => $cancellation->decision_note ?: __('history.box_rejected_b')],
        default    => ['cls' => 'bg-indigo-50 text-[#1f1d5c]', 't' => __('history.box_none_t'), 'b' => __('history.box_none_b')],
    };
@endphp

<div class="mx-auto w-full max-w-6xl px-4 py-8 md:py-12">
    <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
        <a href="{{ route('guest.reservations') }}" class="hover:underline">{{ __('history.title') }}</a>
        / <span>{{ __('history.bc_request') }}</span>
    </nav>

    <h1 class="mt-3 text-3xl font-bold tracking-tight text-[#14123f] md:text-4xl">{{ __('history.st_title_' . $s) }}</h1>

    @if (session('success'))
        <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif

    <div class="mt-5 rounded-xl px-5 py-4 ring-1 {{ $banner['bg'] }}">
        <p class="text-base font-semibold">{{ $banner['t'] }}</p>
        <p class="mt-1 text-sm">{{ $banner['b'] }}</p>
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-2">
        @include('guest._summary', [
            'reservation' => $reservation,
            'rows'        => [
                __('history.guest_short') => $reservation->customer_name,
                __('history.outlet')      => $reservation->outlet->name,
                __('history.when_label')  => $reservation->when_label,
                __('history.table_only')  => $reservation->tables_label,
                __('history.res_status')  => $current,
            ],
        ])

        <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
            <h2 class="text-lg font-bold">{{ __('history.progress_title') }}</h2>
            <p class="text-xs text-slate-500">{{ __('history.request_word') }} • {{ $reqCode }}</p>

            <ol class="mt-5">
                @foreach ($steps as $i => $step)
                    <li class="relative flex items-start gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                        @unless ($loop->last)
                            <span class="absolute top-6 bottom-0 left-3 w-px -translate-x-1/2 bg-slate-200" aria-hidden="true"></span>
                        @endunless
                        <span class="relative grid size-6 shrink-0 place-items-center rounded-full text-[11px] font-semibold {{ $step['done'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }}">
                            @if ($step['done'])
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-11"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="pt-0.5 text-sm {{ $step['done'] ? 'font-medium' : 'text-slate-500' }}">{{ $step['text'] }}</span>
                    </li>
                @endforeach
            </ol>

                        <div class="mt-6 rounded-xl px-4 py-3 {{ $result['cls'] }}">
                <p class="text-sm font-semibold">{{ $result['t'] }}</p>
                <p class="mt-1 text-xs">{{ $result['b'] }}</p>
            </div>
        </section>
    </div>

    <div class="mt-6 flex justify-end">
        <a href="{{ route('guest.reservations') }}" class="rounded-lg bg-[#1f1d5c] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">{{ __('history.back_to_mine') }}</a>
    </div>
</div>
@endsection