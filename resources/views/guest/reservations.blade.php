@extends('layouts.app')

@section('title', __('history.title'))

@section('content')
<div class="mx-auto w-full max-w-6xl px-4 py-8 md:py-12">
    <h1 class="text-3xl font-bold tracking-tight text-[#14123f] md:text-4xl">{{ __('history.title') }}</h1>
    <p class="mt-1 text-slate-600">{{ __('history.subtitle') }}</p>

    @if (session('success'))
        <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</div>
    @endif

    @if (! $verified)

        @if ($otpEmail)
            {{-- ===== Langkah 2: masukkan kode 6 digit ===== --}}
            <section class="mt-8 max-w-xl rounded-2xl bg-white p-6 ring-1 ring-slate-200 md:p-8">
                <h2 class="text-xl font-bold">{{ __('history.otp_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('history.otp_sent_to', ['email' => $otpEmail]) }}</p>

                <form method="POST" action="{{ route('guest.otp.verify') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="otp" class="block text-xs font-semibold text-slate-600">{{ __('history.otp_label') }}</label>
                        <input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-center text-2xl tracking-[.5em] focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                    </div>
                    @error('otp') <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

                    <button type="submit" class="w-full rounded-lg bg-[#1f1d5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">{{ __('history.otp_btn') }}</button>
                </form>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                    <form method="POST" action="{{ route('guest.otp.send') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ session('guest_otp_email') }}">
                        <button type="submit" class="font-medium text-[#1f1d5c] hover:underline">{{ __('history.otp_resend') }}</button>
                    </form>
                    <form method="POST" action="{{ route('guest.forget') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:underline">{{ __('history.otp_other') }}</button>
                    </form>
                </div>
                <p class="mt-4 text-xs text-slate-500">{{ __('history.otp_hint') }}</p>
            </section>
        @else
            {{-- ===== Langkah 1: masukkan email ===== --}}
            <section class="mt-8 max-w-xl rounded-2xl bg-white p-6 ring-1 ring-slate-200 md:p-8">
                <h2 class="text-xl font-bold">{{ __('history.lookup_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('history.lookup_sub') }}</p>

                <form method="POST" action="{{ route('guest.otp.send') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-600">{{ __('history.email_label') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="100" autocomplete="email"
                               placeholder="{{ __('history.email_ph') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                    </div>
                    @error('email') <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

                    <button type="submit" class="w-full rounded-lg bg-[#1f1d5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">{{ __('history.send_code_btn') }}</button>
                </form>

                {{-- Cara alternatif: kode reservasi + email atau nomor telepon --}}
                <details class="mt-6 border-t border-slate-100 pt-4" @if ($errors->has('code') || $errors->has('contact')) open @endif>
                    <summary class="cursor-pointer text-sm font-medium text-[#1f1d5c]">{{ __('history.use_code') }}</summary>

                    <form method="POST" action="{{ route('guest.lookup') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="block text-xs font-semibold text-slate-600">{{ __('history.code_label') }}</label>
                            <input id="code" name="code" value="{{ old('code') }}" required maxlength="32" autocomplete="off"
                                   placeholder="{{ __('history.code_ph') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm uppercase placeholder:normal-case focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                        </div>
                        <div>
                            <label for="contact" class="block text-xs font-semibold text-slate-600">{{ __('history.contact_label') }}</label>
                            <input id="contact" name="contact" value="{{ old('contact') }}" required maxlength="100" autocomplete="off"
                                   placeholder="{{ __('history.contact_ph') }}"
                                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none">
                        </div>
                        @error('code')    <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                        @error('contact') <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror

                        <button type="submit" class="w-full rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('history.lookup_btn') }}</button>
                    </form>
                    <p class="mt-3 text-xs text-slate-500">{{ __('history.lookup_hint') }}</p>
                </details>
            </section>
        @endif

    @else

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-50 text-[#1f1d5c]" aria-hidden="true">
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                </span>
                <p class="min-w-0 text-sm text-slate-500">
                    {{ __('history.viewing_as') }}
                    <span class="block truncate font-semibold text-slate-900 sm:inline">{{ $maskedEmail }}</span>
                </p>
            </div>

            <form method="POST" action="{{ route('guest.forget') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    {{ __('history.sign_out') }}
                </button>
            </form>
        </div>

        {{-- ===== Reservasi aktif ===== --}}
        <h2 class="mt-8 text-lg font-bold">{{ __('history.active_title') }}</h2>

        @forelse ($active as $r)
            @php
                $latest    = $r->cancellationRequests->first();
                $isPending = $latest?->status === 'pending';
                $canQr     = in_array($r->reservation_status, ['confirmed', 'partially_arrived', 'checked_in'], true);
                $detailUrl = route('reservasi.confirmation', [$r->outlet->code, $r->reservation_code]);
            @endphp

            <article class="mt-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $r->outlet->name }}</h3>
                        <p class="mt-1 text-sm font-medium text-[#1f1d5c]">{{ $r->reservation_code }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @include('guest._badge', ['status' => $r->reservation_status])
                        @if ($isPending)
                            @include('guest._badge', ['status' => 'pending_review'])
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_.8fr_1fr_auto] lg:items-start">
                    <div>
                        <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('history.when_label') }}</p>
                        <p class="mt-1 text-sm font-medium">{{ $r->when_label }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('history.table_area') }}</p>
                        <p class="mt-1 text-sm font-medium">{{ $r->tables_label }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('history.guests') }}</p>
                        <p class="mt-1 text-sm font-medium">{{ $r->guest_count }} {{ __('history.guests_unit') }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('history.total') }}</p>
                        <p class="mt-1 text-sm font-medium">{{ $r->total_label }}</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2 lg:col-span-1 lg:min-w-48">
                        @if ($canQr)
                            <a href="{{ $detailUrl }}" class="rounded-lg bg-indigo-50 px-4 py-2.5 text-center text-sm font-semibold text-[#1f1d5c] transition hover:bg-indigo-100">{{ __('history.btn_qr') }}</a>
                        @elseif ($r->reservation_status === 'pending_payment')
                            <a href="{{ $detailUrl }}" class="rounded-lg bg-indigo-50 px-4 py-2.5 text-center text-sm font-semibold text-[#1f1d5c] transition hover:bg-indigo-100">{{ __('history.btn_pay') }}</a>
                        @endif

                        @if ($isPending)
                            <a href="{{ route('guest.cancel.status', $r->reservation_code) }}" class="rounded-lg bg-amber-50 px-4 py-2.5 text-center text-sm font-semibold text-amber-800 transition hover:bg-amber-100">{{ __('history.btn_status') }}</a>
                        @elseif ($r->can_cancel)
                            <a href="{{ route('guest.cancel.create', $r->reservation_code) }}" class="rounded-lg bg-red-50 px-4 py-2.5 text-center text-sm font-semibold text-red-700 transition hover:bg-red-100">{{ __('history.btn_cancel') }}</a>
                        @endif

                        @if ($latest && ! $isPending)
                            <a href="{{ route('guest.cancel.status', $r->reservation_code) }}" class="text-center text-xs text-slate-500 underline-offset-2 hover:underline">{{ __('history.btn_last') }}</a>
                        @endif
                    </div>
                </div>

                @if ($isPending)
                    <p class="mt-4 text-xs text-slate-500">{{ __('history.pending_note') }}</p>
                @elseif ($r->can_cancel)
                    <p class="mt-4 text-xs text-slate-500">{{ __('history.cancel_note') }}</p>
                @endif
            </article>
        @empty
            <div class="mt-4 rounded-2xl bg-white p-6 text-sm text-slate-500 ring-1 ring-slate-200">
                {{ __('history.no_active') }}
                <a href="{{ route('home') }}#pilih-restoran" class="ml-1 font-semibold text-[#1f1d5c] hover:underline">{{ __('history.make_one') }}</a>
            </div>
        @endforelse

        {{-- ===== Riwayat ===== --}}
        <section class="mt-8 rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
            <h2 class="text-lg font-bold">{{ __('history.history_title') }}</h2>
            <p class="text-sm text-slate-500">{{ __('history.history_sub') }}</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                        <tr>
                            <th scope="col" class="rounded-l-lg px-4 py-3">{{ __('history.col_code') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('history.col_date') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('history.col_table') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('history.col_status') }}</th>
                            <th scope="col" class="rounded-r-lg px-4 py-3">{{ __('history.col_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($history as $h)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="text-xs font-semibold text-[#1f1d5c]">{{ $h->reservation_code }}</p>
                                    <p>{{ $h->outlet->name }}</p>
                                </td>
                                <td class="px-4 py-4">{{ $h->when_short }}</td>
                                <td class="px-4 py-4">{{ $h->tables_label }} • {{ $h->guest_count }} {{ __('history.guests_unit') }}</td>
                                <td class="px-4 py-4">@include('guest._badge', ['status' => $h->reservation_status])</td>
                                <td class="px-4 py-4">{{ $h->reservation_status === 'completed' ? $h->total_label : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">{{ __('history.no_history') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="mt-6 text-xs text-slate-500">{{ __('history.footer_note') }}</p>

    @endif
</div>
@endsection