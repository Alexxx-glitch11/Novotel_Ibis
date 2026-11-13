@extends('layouts.app')

@section('title', __('history.cf_title'))

@section('content')
@php
    $field = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none';
@endphp
<div class="mx-auto w-full max-w-6xl px-4 py-8 md:py-12">
    <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
        <a href="{{ route('guest.reservations') }}" class="hover:underline">{{ __('history.title') }}</a>
        / <span>{{ __('history.bc_active') }}</span>
        / <span>{{ __('history.bc_cancel') }}</span>
    </nav>

    <h1 class="mt-3 text-3xl font-bold tracking-tight text-[#14123f] md:text-4xl">{{ __('history.cf_title') }}</h1>
    <p class="mt-1 text-slate-600">{{ __('history.cf_sub') }}</p>

    <div class="mt-5 rounded-xl bg-amber-50 px-5 py-4 text-amber-900 ring-1 ring-amber-100">
        <p class="text-sm font-semibold">{{ __('history.cf_warn_title') }}</p>
        <p class="mt-1 text-sm">{{ __('history.cf_warn_body') }}</p>
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-2">
        @include('guest._summary', [
            'reservation' => $reservation,
            'showBadge'   => true,
            'rows'        => [
                __('history.guest_name') => $reservation->customer_name,
                __('history.outlet')     => $reservation->outlet->name,
                __('history.when_label') => $reservation->when_label,
                __('history.table_area') => $reservation->tables_label,
                __('history.guests')     => $reservation->guest_count . ' ' . __('history.guests_unit'),
            ],
            'box' => ['title' => __('history.pay_box_title'), 'body' => __('history.pay_box_body')],
        ])

        <form method="POST" action="{{ route('guest.cancel.store', $reservation->reservation_code) }}"
              class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
            @csrf
            <h2 class="text-lg font-bold">{{ __('history.detail_title') }}</h2>

            <label for="reason" class="mt-5 block text-sm font-semibold">{{ __('history.reason_label') }} <span class="text-red-600">*</span></label>
            <select id="reason" name="reason" required class="{{ $field }}">
                <option value="">{{ __('history.reason_ph') }}</option>
                @foreach ($reasons as $key => $label)
                    <option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('reason') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

            <label for="note" class="mt-5 block text-sm font-semibold">{{ __('history.note_label') }}</label>
            <textarea id="note" name="note" rows="4" maxlength="500" placeholder="{{ __('history.note_ph') }}"
                      class="{{ $field }}">{{ old('note') }}</textarea>
            @error('note') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

            <label class="mt-5 flex items-start gap-3 text-sm">
                <input type="checkbox" name="confirm" value="1" required @checked(old('confirm')) class="mt-0.5 size-4 shrink-0 accent-[#1f1d5c]">
                <span>{{ __('history.confirm_label') }}</span>
            </label>
            @error('confirm') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

            <div class="mt-5 rounded-xl bg-indigo-50 px-4 py-3 text-sm text-[#1f1d5c]">{{ __('history.after_submit') }}</div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('guest.reservations') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('history.back_btn') }}</a>
                <button type="submit" class="flex-1 rounded-lg bg-[#1f1d5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">{{ __('history.submit_btn') }}</button>
            </div>
        </form>
    </div>

    <p class="mt-6 text-xs text-slate-500">{{ __('history.cf_footer') }}</p>
</div>
@endsection