@extends('layouts.manager')

@section('title', __('manager.out_title'))

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-[#14123f]">{{ __('manager.out_title') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ __('manager.out_sub') }}</p>

    @if (session('success'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</div>
    @endif

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        @foreach ($outlets as $o)
            @php
                $active  = (bool) $o->is_active;
                $primary = $o->code === \App\Models\Outlet::PRIMARY_CODE;
                $confirm = $active
                    ? ($o->upcoming_count > 0
                        ? __('manager.out_confirm_off_warn', ['name' => $o->name, 'count' => $o->upcoming_count])
                        : __('manager.out_confirm_off', ['name' => $o->name]))
                    : null;
            @endphp

            <article class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold text-[#14123f]">{{ $o->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('manager.out_code_label') }}: <span class="font-mono">{{ $o->code }}</span></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($primary)
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-[#1f1d5c]">{{ __('manager.out_primary') }}</span>
                        @endif
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $active ? __('manager.st_active') : __('manager.st_inactive') }}
                        </span>
                    </div>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 pt-5 text-sm">
                    <div>
                        <dt class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('manager.out_tables_label') }}</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $o->tables_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('manager.out_upcoming_label') }}</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $o->upcoming_count }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('manager.outlets.toggle', $o) }}" class="mt-5"
                      @if ($confirm) data-confirm="{{ $confirm }}" @endif>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_active" value="{{ $active ? 0 : 1 }}">
                    <button type="submit"
                            class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition sm:w-auto
                                   {{ $active ? 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'bg-[#1f1d5c] text-white hover:bg-[#14123f]' }}">
                        {{ $active ? __('manager.out_deactivate') : __('manager.out_activate') }}
                    </button>
                </form>
            </article>
        @endforeach
    </div>

    <p class="mt-6 text-xs text-slate-500">{{ __('manager.out_note') }}</p>
@endsection