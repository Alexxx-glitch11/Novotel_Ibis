@extends('layouts.app')

@section('title', __('ui.reservation_title') . ' · ' . $outlet->name)

@section('content')
@php
    $dateValue = old('reservation_date', $flow['reservation_date'] ?? '');
    $timeValue = old('start_time', isset($flow['start_time']) ? substr($flow['start_time'], 0, 5) : '');
    $pickerCopy = [
        'table' => __('ui.table_word'), 'area' => __('ui.area'), 'capacity' => __('ui.capacity'),
        'guests' => __('ui.guests'), 'none' => __('ui.none_selected'), 'draft' => __('ui.draft'),
        'notSelected' => __('ui.not_selected'), 'event' => __('ui.event'),
    ];
@endphp

<div class="mx-auto w-full max-w-6xl px-4 py-7 md:py-10">
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-[#14123f]">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>{{ __('ui.back') }}
    </a>

    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">
            <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

        <div class="mt-5 flex flex-wrap items-start justify-between gap-3">
            <div><h1 class="text-2xl font-bold tracking-tight text-[#252942] md:text-[28px]">{{ __('ui.table_title') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('ui.table_sub') }}</p></div>
            <div class="inline-flex items-center gap-2 rounded-full bg-[#24215f] px-4 py-2 text-xs font-medium text-white"><span class="size-2 rounded-full bg-amber-300"></span>{{ __('ui.choose_table_to_hold') }}</div>
        </div>

        <form id="reservation-form" method="POST" action="{{ route('reservasi.hold', $outlet->code) }}" class="mt-5 grid items-start gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(230px,1fr)]">
            @csrf
            <div class="grid min-w-0 gap-3">
                <section class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <h2 class="text-base font-bold text-[#252942]">{{ __('ui.your_details') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.details_help') }}</p>
                    <div class="mt-3 grid gap-x-3 gap-y-2.5 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.customer_name') }}<input name="customer_name" required maxlength="100" autocomplete="name" value="{{ old('customer_name', $flow['customer_name'] ?? '') }}" placeholder="{{ __('ui.customer_name_placeholder') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.customer_email') }}<input name="customer_email" type="email" required maxlength="100" autocomplete="email" value="{{ old('customer_email', $flow['customer_email'] ?? '') }}" placeholder="nama@email.com" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.guest_count_adult') }}<input name="adult_count" type="number" min="1" max="50" required value="{{ old('adult_count', $flow['adult_count'] ?? 2) }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.guest_count_children') }}<input name="children_count" type="number" min="0" max="50" required value="{{ old('children_count', $flow['children_count'] ?? 0) }}" placeholder="{{ __('ui.choose_quantity') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600 sm:col-span-2">{{ __('ui.customer_phone') }}<input name="customer_phone" type="tel" required maxlength="20" autocomplete="tel" value="{{ old('customer_phone', $flow['customer_phone'] ?? '') }}" placeholder="{{ __('ui.customer_phone_placeholder') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">{{ __('ui.max_guest_count') }}</p>
                </section>

                <section class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <h2 class="text-base font-bold text-[#252942]">{{ __('ui.date_and_time') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.choose_visit_schedule') }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.reservation_date') }}<input name="reservation_date" type="date" min="{{ \Carbon\Carbon::now(config('reservation.timezone'))->toDateString() }}" required value="{{ $dateValue }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.start_time') }}<input name="start_time" type="time" required value="{{ $timeValue }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none"><span class="mt-1 block text-[11px] font-normal text-slate-400">{{ __('ui.reservation_duration_help', ['minutes' => config('reservation.duration_minutes', 120)]) }}</span></label>
                    </div>
                </section>

                <section id="table-picker" class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-base font-bold text-[#252942]">{{ __('ui.pick_table') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.event_and_table_category_help') }}</p></div><button type="button" id="toggle-view" class="min-h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-[#24215f]">{{ __('ui.view_list') }}</button></div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.event') }}<select name="event" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600 focus:border-[#24215f] focus:outline-none"><option value="">{{ __('ui.event_not_selected') }}</option></select></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.table_category') }}<select id="capacity-filter" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600 focus:border-[#24215f] focus:outline-none"><option value="">{{ __('ui.category_not_selected') }}</option>@foreach ($tablesByArea->flatten()->pluck('capacity')->unique()->sort() as $capacity)<option value="{{ $capacity }}">{{ __('ui.capacity_for_people', ['count' => $capacity]) }}</option>@endforeach</select></label>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex gap-2" role="tablist" aria-label="{{ __('ui.area') }}">@foreach ($areas as $area)<button type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-area-tab="{{ $area->areas_id }}" data-active="{{ $loop->first ? 'true' : 'false' }}" class="min-h-8 min-w-16 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600 data-[active=true]:border-[#24215f] data-[active=true]:bg-[#24215f] data-[active=true]:text-white">{{ $area->name }}</button>@endforeach</div>
                        <div class="flex gap-3 text-[10px] text-slate-500"><span>○ {{ __('ui.legend_available') }}</span><span>● {{ __('ui.legend_selected') }}</span><span>● {{ __('ui.legend_unavailable') }}</span></div>
                    </div>
                    @foreach ($areas as $area)
                        @php $areaTables = $tablesByArea[$area->areas_id] ?? collect(); @endphp
                        <div data-area-panel="{{ $area->areas_id }}" @if (! $loop->first) hidden @endif>
                            <div data-view-map class="relative mt-3 aspect-[4/3] w-full overflow-hidden rounded-lg bg-[#f8f9fc] ring-1 ring-slate-200">
                                @if ($area->floor_plan_url)<img src="{{ $area->floor_plan_url }}" alt="{{ __('ui.floor_plan') }} {{ $area->name }}" class="absolute inset-0 h-full w-full object-contain" draggable="false">@else<div class="absolute inset-0 grid place-items-center text-xs text-slate-400">{{ __('ui.no_floor_plan') }}</div>@endif
                                @forelse ($areaTables as $table)
                                    <button type="button" data-table-btn data-id="{{ $table->tables_id }}" data-code="{{ $table->table_code }}" data-area="{{ $area->name }}" data-capacity="{{ $table->capacity }}" data-available="{{ $table->available_for_slot ? 'true' : 'false' }}" data-selected="false" style="left:{{ $table->position_x }}%;top:{{ $table->position_y }}%" @disabled(! $table->available_for_slot) aria-label="{{ __('ui.table_word') }} {{ $table->table_code }}" class="absolute -translate-x-1/2 -translate-y-1/2 rounded-lg border border-[#24215f] bg-white px-3 py-1.5 text-[11px] font-semibold text-[#24215f] shadow-sm transition data-[selected=true]:bg-[#24215f] data-[selected=true]:text-white disabled:border-slate-300 disabled:bg-slate-200 disabled:text-slate-500">{{ $table->table_code }}</button>
                                @empty<p class="absolute inset-0 grid place-items-center text-xs text-slate-400">{{ __('ui.no_tables_in_area') }}</p>@endforelse
                            </div>
                            <ul data-view-list hidden class="mt-3 grid gap-2 sm:grid-cols-2">@forelse ($areaTables as $table)<li><button type="button" data-table-btn data-id="{{ $table->tables_id }}" data-code="{{ $table->table_code }}" data-area="{{ $area->name }}" data-capacity="{{ $table->capacity }}" data-available="{{ $table->available_for_slot ? 'true' : 'false' }}" data-selected="false" @disabled(! $table->available_for_slot) class="flex min-h-10 w-full items-center justify-between rounded-lg border border-slate-200 px-3 text-xs disabled:bg-slate-100"><span>{{ __('ui.table_word') }} {{ $table->table_code }}</span><span>{{ $table->capacity }} {{ __('ui.guests') }}</span></button></li>@empty<li class="text-xs text-slate-400">{{ __('ui.no_tables_in_area') }}</li>@endforelse</ul>
                        </div>
                    @endforeach
                    <input type="hidden" name="tables_id" id="selected-table-id">
                </section>
            </div>

            <aside class="rounded-xl bg-white p-4 ring-1 ring-slate-200 lg:sticky lg:top-24 md:p-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3"><h2 class="text-base font-bold text-[#252942]">{{ __('ui.reservation_summary') }}</h2><span class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-semibold text-[#24215f]">{{ __('ui.draft') }}</span></div>
                <dl class="mt-3 space-y-2.5 text-xs">
                    <div><dt class="text-slate-400">{{ __('ui.reservation_date') }}</dt><dd id="summary-date" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.start_time') }}</dt><dd id="summary-time" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.guest_count') }}</dt><dd id="summary-guests" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.event') }}</dt><dd class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.area') }} & {{ __('ui.table_category') }}</dt><dd id="summary-area" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div class="border-t border-slate-100 pt-2"><dt class="text-slate-400">{{ __('ui.your_selection') }}</dt><dd id="table-summary" class="mt-0.5 font-semibold text-slate-800">{{ __('ui.none_selected') }}</dd></div>
                </dl>
                <button id="hold-submit" type="submit" disabled class="mt-4 min-h-10 w-full rounded-lg bg-[#24215f] px-4 text-xs font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:bg-slate-300">{{ __('ui.continue_to_menu') }}</button>
                <p class="mt-2 text-center text-[10px] leading-4 text-slate-400">{{ __('ui.summary_updates_after_choice') }}</p>
            </aside>
        </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('table-picker');
    if (root) {
        const copy = @json($pickerCopy);
        const hidden = document.getElementById('selected-table-id');
        const submit = document.getElementById('hold-submit');
        const summary = document.getElementById('table-summary');
        const tabs = root.querySelectorAll('[data-area-tab]');
        const panels = root.querySelectorAll('[data-area-panel]');
        let currentArea = tabs[0]?.textContent.trim() || '';
        let listMode = false;
        const areaLabels = new Map(Array.from(tabs, tab => [tab.dataset.areaTab, tab.textContent.trim()]));

        function selectTable(button) {
            root.querySelectorAll('[data-table-btn]').forEach(item => item.dataset.selected = String(item.dataset.id === button.dataset.id));
            hidden.value = button.dataset.id;
            submit.disabled = false;
            summary.textContent = `${copy.table} ${button.dataset.code}`;
            document.getElementById('summary-area').textContent = `${button.dataset.area} · ${copy.capacity}: ${button.dataset.capacity}`;
        }

        tabs.forEach(tab => tab.addEventListener('click', () => {
            currentArea = areaLabels.get(tab.dataset.areaTab);
            tabs.forEach(item => { const active = item === tab; item.dataset.active = String(active); item.setAttribute('aria-selected', String(active)); });
            panels.forEach(panel => panel.hidden = panel.dataset.areaPanel !== tab.dataset.areaTab);
            document.getElementById('summary-area').textContent = currentArea;
        }));
        root.querySelectorAll('[data-table-btn]:not(:disabled)').forEach(button => button.addEventListener('click', () => selectTable(button)));
        document.getElementById('toggle-view').addEventListener('click', event => {
            listMode = !listMode;
            root.querySelectorAll('[data-view-map]').forEach(map => map.hidden = listMode);
            root.querySelectorAll('[data-view-list]').forEach(list => list.hidden = !listMode);
            event.currentTarget.textContent = listMode ? @json(__('ui.view_map')) : @json(__('ui.view_list'));
        });
        const capacityFilter = document.getElementById('capacity-filter');
        capacityFilter.addEventListener('change', () => root.querySelectorAll('[data-table-btn]').forEach(button => {
            const matches = !capacityFilter.value || button.dataset.capacity === capacityFilter.value;
            button.hidden = !matches;
        }));
    }

    const form = document.getElementById('reservation-form');
    if (form) {
        const date = form.elements.reservation_date;
        const time = form.elements.start_time;
        const adults = form.elements.adult_count;
        const children = form.elements.children_count;
        const placeholder = @json(__('ui.not_selected'));
        const updateSummary = () => {
            document.getElementById('summary-date').textContent = date.value || placeholder;
            document.getElementById('summary-time').textContent = time.value || placeholder;
            const count = Number(adults.value || 0) + Number(children.value || 0);
            document.getElementById('summary-guests').textContent = count ? `${count} ${@json(__('ui.guests'))}` : placeholder;
        };
        form.querySelectorAll('input').forEach(input => input.addEventListener('input', updateSummary));
        updateSummary();
    }

    const countdown = document.getElementById('hold-countdown');
    if (countdown) {
        const deadline = Number(countdown.dataset.expires) * 1000;
        const button = document.getElementById('confirm-reservation');
        const update = () => {
            const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
            countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
            if (!seconds) button.disabled = true;
        };
        update(); window.setInterval(update, 1000);
    }
})();
</script>
@endpush
