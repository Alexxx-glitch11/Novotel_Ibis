@extends('layouts.app')

@section('title', __('ui.reservation_title') . ' · ' . $outlet->name)

@section('content')
@php
    $resSettings = app(\App\Services\ReservationSettings::class);
    $maxGuests = $resSettings->maxGuests();
    $durationMinutes = $resSettings->durationMinutes();
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

        <form id="reservation-form" method="POST" action="{{ route('reservasi.hold', $outlet->code) }}" data-availability-url="{{ route('reservasi.availability', $outlet->code) }}" class="mt-5 grid items-start gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(230px,1fr)]">
            @csrf
            <div class="grid min-w-0 gap-3">
                @if (! empty($flow['promo_title']) && empty($flow['event_id']))
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/75 px-4 py-3 text-xs text-[#24215f]" role="status">
                        <p class="font-semibold">Promosi dipilih: {{ $flow['promo_title'] }}</p>
                        <p class="mt-0.5 text-slate-600">Setelah memilih kursi/meja dan jadwal, kategori <strong>Promosi</strong> akan langsung terbuka pada tahap <strong>Pilih Menu</strong>.</p>
                    </div>
                @endif
                <section class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <h2 class="text-base font-bold text-[#252942]">{{ __('ui.your_details') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.details_help') }}</p>
                    <div class="mt-3 grid gap-x-3 gap-y-2.5 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.customer_name') }}<input name="customer_name" required maxlength="100" autocomplete="name" value="{{ old('customer_name', $flow['customer_name'] ?? '') }}" placeholder="{{ __('ui.customer_name_placeholder') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.customer_email') }}<input name="customer_email" type="email" required maxlength="100" autocomplete="email" value="{{ old('customer_email', $flow['customer_email'] ?? '') }}" placeholder="nama@email.com" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.guest_count_adult') }}<input name="adult_count" type="number" min="1" max="{{ $maxGuests }}" required value="{{ old('adult_count', $flow['adult_count'] ?? 2) }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.guest_count_children') }}<input name="children_count" type="number" min="0" max="{{ $maxGuests }}" required value="{{ old('children_count', $flow['children_count'] ?? 0) }}" placeholder="{{ __('ui.choose_quantity') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                        <label class="text-xs font-medium text-slate-600 sm:col-span-2">{{ __('ui.customer_phone') }}<input name="customer_phone" type="tel" required maxlength="20" autocomplete="tel" value="{{ old('customer_phone', $flow['customer_phone'] ?? '') }}" placeholder="{{ __('ui.customer_phone_placeholder') }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10"></label>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">Maksimal {{ $maxGuests }} orang per satu reservasi rombongan.</p>
                </section>

                <section class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <h2 class="text-base font-bold text-[#252942]">{{ __('ui.date_and_time') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.choose_visit_schedule') }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.reservation_date') }}<input name="reservation_date" type="date" min="{{ \Carbon\Carbon::now(config('reservation.timezone'))->toDateString() }}" required value="{{ $dateValue }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none"></label>
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.start_time') }}<input name="start_time" type="time" required value="{{ $timeValue }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-[#24215f] focus:outline-none"><span class="mt-1 block text-[11px] font-normal text-slate-400">{{ __('ui.reservation_duration_help', ['minutes' => $durationMinutes]) }}</span></label>
                    </div>
                </section>

                <section id="table-picker" class="rounded-xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-base font-bold text-[#252942]">{{ __('ui.pick_table') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('ui.event_and_table_category_help') }}</p></div><button type="button" id="toggle-view" class="min-h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-[#24215f]">{{ __('ui.view_list') }}</button></div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium text-slate-600">{{ __('ui.event') }}
                            <select name="event_id" id="event-select" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 focus:border-[#24215f] focus:outline-none">
                                <option value="" data-requires-table="1" data-requires-menu="1" data-price-per-pax="0" data-title="">{{ __('ui.event_not_selected') }}</option>
                                @foreach ($activeEvents as $ev)
                                    <option value="{{ $ev->outlet_contents_id }}"
                                            data-requires-table="{{ $ev->requires_table ? '1' : '0' }}"
                                            data-requires-menu="{{ $ev->requires_menu ? '1' : '0' }}"
                                            data-price-per-pax="{{ (float) $ev->price_per_pax }}"
                                            data-title="{{ $ev->title }}"
                                            @selected((string) old('event_id', $flow['event_id'] ?? '') === (string) $ev->outlet_contents_id)>
                                        {{ $ev->title }}@if((float) $ev->price_per_pax > 0) (Rp{{ number_format((float) $ev->price_per_pax, 0, ',', '.') }}/pax)@endif
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label id="capacity-filter-wrap" class="text-xs font-medium text-slate-600">{{ __('ui.table_category') }}<select id="capacity-filter" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600 focus:border-[#24215f] focus:outline-none"><option value="">{{ __('ui.category_not_selected') }}</option>@foreach ($tablesByArea->flatten()->pluck('capacity')->unique()->sort() as $capacity)<option value="{{ $capacity }}">{{ __('ui.capacity_for_people', ['count' => $capacity]) }}</option>@endforeach</select></label>
                    </div>
                    <div id="no-table-event-notice" hidden class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 text-xs text-[#24215f]">
                        <p class="font-semibold">Event ini tidak memerlukan pemilihan kursi spesifik.</p>
                        <p id="no-table-event-desc" class="mt-1 text-slate-600">Anda dapat langsung melanjutkan ke tahap berikutnya tanpa memilih nomor kursi.</p>
                    </div>
                    <div id="seat-map-wrapper">
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex gap-2" role="tablist" aria-label="{{ __('ui.area') }}">@foreach ($areas as $area)<button type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-area-tab="{{ $area->areas_id }}" data-active="{{ $loop->first ? 'true' : 'false' }}" class="min-h-8 min-w-16 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600 data-[active=true]:border-[#24215f] data-[active=true]:bg-[#24215f] data-[active=true]:text-white">{{ $area->name }}</button>@endforeach</div>
                        <div class="flex gap-3 text-[10px] text-slate-500"><span>○ {{ __('ui.legend_available') }}</span><span>● {{ __('ui.legend_selected') }}</span><span>● {{ __('ui.legend_unavailable') }}</span></div>
                    </div>
                    <p id="availability-status" class="mt-3 text-xs text-slate-500" role="status" aria-live="polite">Pilih tanggal, waktu, dan jumlah tamu untuk melihat meja yang tersedia.</p>
                    @foreach ($areas as $area)
                        @php
                            $areaTables = ($tablesByArea[$area->areas_id] ?? collect())->sortBy('table_code', SORT_NATURAL | SORT_FLAG_CASE);
                            $groupedRows = $areaTables->groupBy(function ($t) {
                                if (preg_match('/^([A-Za-z]+)/', (string) $t->table_code, $m)) {
                                    return strtoupper($m[1]);
                                }
                                return 'Meja';
                            });
                        @endphp
                        <div data-area-panel="{{ $area->areas_id }}" @if (! $loop->first) hidden @endif>
                            <div data-view-map class="mt-3 rounded-xl border border-slate-200 bg-[#f8f9fc] p-4 md:p-5">
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-2 w-8 rounded-full bg-[#24215f]"></span>
                                        <span class="text-xs font-semibold tracking-wide text-slate-600 uppercase">Tata Letak Kursi · {{ $area->name }}</span>
                                    </div>
                                    @if ($area->floor_plan_url)
                                        <button type="button"
                                                data-open-floorplan
                                                data-floorplan-url="{{ $area->floor_plan_url }}"
                                                data-floorplan-title="Denah {{ $area->name }} · {{ $outlet->name }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#e8edff] px-3 py-1.5 text-xs font-semibold text-[#24215f] transition hover:bg-[#dce3ff]">
                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                                            Lihat Denah {{ $area->name }}
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400">{{ __('ui.no_floor_plan') }}</span>
                                    @endif
                                </div>

                                @if ($areaTables->isEmpty())
                                    <p class="py-10 text-center text-xs text-slate-400">{{ __('ui.no_tables_in_area') }}</p>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($groupedRows as $rowLabel => $rowTables)
                                            <div class="flex items-start gap-3">
                                                <span class="mt-2 grid size-7 shrink-0 place-items-center rounded-md bg-slate-200/80 text-xs font-bold text-slate-700">{{ $rowLabel }}</span>
                                                <div class="flex flex-wrap gap-2.5">
                                                    @foreach ($rowTables as $table)
                                                        <button type="button"
                                                                data-table-btn
                                                                data-id="{{ $table->tables_id }}"
                                                                data-code="{{ $table->table_code }}"
                                                                data-area="{{ $area->name }}"
                                                                data-capacity="{{ $table->capacity }}"
                                                                data-available="{{ $table->available_for_slot ? 'true' : 'false' }}"
                                                                data-selected="false"
                                                                @disabled(! $table->available_for_slot)
                                                                aria-label="{{ __('ui.table_word') }} {{ $table->table_code }} ({{ $table->capacity }} {{ __('ui.guests') }})"
                                                                class="group relative flex h-14 min-w-[68px] flex-col items-center justify-center rounded-xl border-2 border-[#24215f] bg-white px-2.5 text-[#24215f] shadow-xs transition hover:bg-[#e8edff] data-[selected=true]:border-[#24215f] data-[selected=true]:bg-[#24215f] data-[selected=true]:text-white disabled:cursor-not-allowed disabled:border-slate-300 disabled:bg-slate-200 disabled:text-slate-400">
                                                            <span class="text-xs font-bold leading-none">{{ $table->table_code }}</span>
                                                            <span class="mt-1 text-[10px] opacity-80">{{ $table->capacity }} org</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <ul data-view-list hidden class="mt-3 grid gap-2 sm:grid-cols-2">@forelse ($areaTables as $table)<li><button type="button" data-table-btn data-id="{{ $table->tables_id }}" data-code="{{ $table->table_code }}" data-area="{{ $area->name }}" data-capacity="{{ $table->capacity }}" data-available="{{ $table->available_for_slot ? 'true' : 'false' }}" data-selected="false" @disabled(! $table->available_for_slot) class="flex min-h-10 w-full items-center justify-between rounded-lg border border-slate-200 px-3 text-xs data-[selected=true]:border-[#24215f] data-[selected=true]:bg-[#24215f] data-[selected=true]:text-white disabled:bg-slate-100 disabled:text-slate-400"><span>{{ __('ui.table_word') }} {{ $table->table_code }}</span><span>{{ $table->capacity }} {{ __('ui.guests') }}</span></button></li>@empty<li class="text-xs text-slate-400">{{ __('ui.no_tables_in_area') }}</li>@endforelse</ul>
                        </div>
                    @endforeach
                    </div>
                    <input type="hidden" name="tables_id" id="selected-table-id">
                </section>
            </div>

            <aside class="rounded-xl bg-white p-4 ring-1 ring-slate-200 lg:sticky lg:top-24 md:p-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3"><h2 class="text-base font-bold text-[#252942]">{{ __('ui.reservation_summary') }}</h2><span class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-semibold text-[#24215f]">{{ __('ui.draft') }}</span></div>
                <dl class="mt-3 space-y-2.5 text-xs">
                    <div><dt class="text-slate-400">{{ __('ui.reservation_date') }}</dt><dd id="summary-date" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.start_time') }}</dt><dd id="summary-time" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.guest_count') }}</dt><dd id="summary-guests" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.event') }}</dt><dd id="summary-event" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div><dt class="text-slate-400">{{ __('ui.area') }} & {{ __('ui.table_category') }}</dt><dd id="summary-area" class="mt-0.5 font-medium text-slate-700">{{ __('ui.not_selected') }}</dd></div>
                    <div class="border-t border-slate-100 pt-2"><dt class="text-slate-400">{{ __('ui.your_selection') }}</dt><dd id="table-summary" class="mt-0.5 font-semibold text-slate-800">{{ __('ui.none_selected') }}</dd></div>
                </dl>
                <button id="hold-submit" type="submit" disabled class="mt-4 min-h-10 w-full rounded-lg bg-[#24215f] px-4 text-xs font-semibold text-white transition hover:bg-[#181644] disabled:cursor-not-allowed disabled:bg-slate-300">{{ __('ui.continue_to_menu') }}</button>
                <p class="mt-2 text-center text-[10px] leading-4 text-slate-400">{{ __('ui.summary_updates_after_choice') }}</p>
            </aside>
        </form>
</div>

{{-- Modal Denah Restoran untuk Pelanggan --}}
<div id="customer-floorplan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/75 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="customer-floorplan-title">
    <div class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
            <h3 id="customer-floorplan-title" class="text-sm font-bold text-[#24215f]">Denah Restoran</h3>
            <button type="button" id="customer-floorplan-close" class="grid size-8 place-items-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600" aria-label="Tutup denah">✕</button>
        </div>
        <div class="overflow-auto bg-slate-100 p-4">
            <img id="customer-floorplan-img" src="" alt="Denah Restoran" class="mx-auto max-h-[72vh] w-auto rounded-lg shadow-sm">
        </div>
    </div>
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
        let requiresTable = true;
        let requiresMenu = true;
        const areaLabels = new Map(Array.from(tabs, tab => [tab.dataset.areaTab, tab.textContent.trim()]));
        const eventSelect = document.getElementById('event-select');
        const seatWrapper = document.getElementById('seat-map-wrapper');
        const noTableNotice = document.getElementById('no-table-event-notice');
        const noTableDesc = document.getElementById('no-table-event-desc');
        const toggleViewBtn = document.getElementById('toggle-view');
        const capacityFilterWrap = document.getElementById('capacity-filter-wrap');
        const summaryEvent = document.getElementById('summary-event');
        const summaryArea = document.getElementById('summary-area');
        const defaultSubmitText = @json(__('ui.continue_to_menu'));

        function syncEventFlow() {
            const opt = eventSelect?.selectedOptions?.[0];
            requiresTable = !opt || opt.dataset.requiresTable !== '0';
            requiresMenu = !opt || opt.dataset.requiresMenu !== '0';
            const title = opt?.dataset.title || '';
            const price = Number(opt?.dataset.pricePerPax || 0);

            if (summaryEvent) {
                summaryEvent.textContent = title
                    ? (price > 0 ? `${title} (Rp${price.toLocaleString('id-ID')}/pax)` : title)
                    : copy.notSelected;
            }

            seatWrapper.hidden = !requiresTable;
            noTableNotice.hidden = requiresTable;
            if (toggleViewBtn) toggleViewBtn.hidden = !requiresTable;
            if (capacityFilterWrap) capacityFilterWrap.classList.toggle('hidden', !requiresTable);

            submit.textContent = requiresMenu ? defaultSubmitText : 'Lanjut ke Konfirmasi';

            if (!requiresTable) {
                hidden.value = '';
                root.querySelectorAll('[data-table-btn]').forEach(item => item.dataset.selected = 'false');
                summary.textContent = 'Tanpa pilih kursi (Mengikuti Event)';
                summaryArea.textContent = title || copy.notSelected;
                if (noTableDesc) {
                    noTableDesc.textContent = requiresMenu
                        ? 'Anda dapat langsung melanjutkan ke pemilihan menu tanpa memilih kursi.'
                        : 'Event ini juga tidak memerlukan pemilihan menu. Klik tombol di kanan untuk langsung ke konfirmasi.';
                }
                submit.disabled = false;
            } else {
                if (!hidden.value) {
                    summary.textContent = copy.none;
                    submit.disabled = true;
                }
            }
        }

        eventSelect?.addEventListener('change', syncEventFlow);
        syncEventFlow();

        function selectTable(button) {
            if (button.disabled) return;
            root.querySelectorAll('[data-table-btn]').forEach(item => item.dataset.selected = String(item.dataset.id === button.dataset.id));
            hidden.value = button.dataset.id;
            submit.disabled = false;
            summary.textContent = `${copy.table} ${button.dataset.code}`;
            summaryArea.textContent = `${button.dataset.area} · ${copy.capacity}: ${button.dataset.capacity}`;
        }

        tabs.forEach(tab => tab.addEventListener('click', () => {
            currentArea = areaLabels.get(tab.dataset.areaTab);
            hidden.value = '';
            submit.disabled = requiresTable;
            summary.textContent = requiresTable ? copy.none : 'Tanpa pilih kursi (Mengikuti Event)';
            tabs.forEach(item => { const active = item === tab; item.dataset.active = String(active); item.setAttribute('aria-selected', String(active)); });
            panels.forEach(panel => panel.hidden = panel.dataset.areaPanel !== tab.dataset.areaTab);
            summaryArea.textContent = currentArea;
            root.querySelectorAll('[data-table-btn]').forEach(item => item.dataset.selected = 'false');
        }));
        root.querySelectorAll('[data-table-btn]').forEach(button => button.addEventListener('click', () => selectTable(button)));
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
        const availabilityStatus = document.getElementById('availability-status');
        let availabilityRequest = 0;
        let availabilityTimer;
        const updateSummary = () => {
            document.getElementById('summary-date').textContent = date.value || placeholder;
            document.getElementById('summary-time').textContent = time.value || placeholder;
            const count = Number(adults.value || 0) + Number(children.value || 0);
            document.getElementById('summary-guests').textContent = count ? `${count} ${@json(__('ui.guests'))}` : placeholder;
        };
        form.querySelectorAll('input').forEach(input => input.addEventListener('input', updateSummary));
        updateSummary();

        const clearSelection = () => {
            const opt = document.getElementById('event-select')?.selectedOptions?.[0];
            const needsTable = !opt || opt.dataset.requiresTable !== '0';
            document.getElementById('selected-table-id').value = '';
            document.getElementById('hold-submit').disabled = needsTable;
            document.getElementById('table-summary').textContent = needsTable ? @json(__('ui.none_selected')) : 'Tanpa pilih kursi (Mengikuti Event)';
            document.querySelectorAll('[data-table-btn]').forEach(button => button.dataset.selected = 'false');
        };
        const updateAvailability = async () => {
            const count = Number(adults.value || 0) + Number(children.value || 0);
            const maxGuests = {{ $maxGuests }};
            const buttons = Array.from(document.querySelectorAll('[data-table-btn]'));
            clearSelection();
            buttons.forEach(button => { button.disabled = true; button.dataset.available = 'false'; });
            if (!date.value || !time.value || count < 1 || count > maxGuests) {
                availabilityStatus.textContent = `Pilih tanggal, waktu, dan jumlah tamu (maksimal ${maxGuests}) untuk melihat meja yang tersedia.`;
                return;
            }

            const requestId = ++availabilityRequest;
            availabilityStatus.textContent = 'Memeriksa ketersediaan meja…';
            const query = new URLSearchParams({ reservation_date: date.value, start_time: time.value, guest_count: String(count) });
            try {
                const response = await fetch(`${form.dataset.availabilityUrl}?${query}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('availability');
                const data = await response.json();
                if (requestId !== availabilityRequest) return;
                const available = new Set(data.table_ids.map(String));
                buttons.forEach(button => {
                    const enabled = available.has(button.dataset.id);
                    button.dataset.available = String(enabled);
                    button.disabled = !enabled;
                });
                availabilityStatus.textContent = available.size
                    ? `${available.size} meja tersedia untuk jadwal ini.`
                    : 'Tidak ada meja yang tersedia untuk jadwal ini.';
            } catch {
                if (requestId !== availabilityRequest) return;
                availabilityStatus.textContent = 'Ketersediaan meja belum dapat diperiksa. Silakan coba lagi.';
            }
        };
        [date, time, adults, children].forEach(input => input.addEventListener('change', () => {
            availabilityRequest++;
            window.clearTimeout(availabilityTimer);
            availabilityTimer = window.setTimeout(updateAvailability, 200);
        }));
        if (date.value && time.value) updateAvailability();
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
    const fpModal = document.getElementById('customer-floorplan-modal');
    const fpImg = document.getElementById('customer-floorplan-img');
    const fpTitle = document.getElementById('customer-floorplan-title');
    if (fpModal) {
        const closeFp = () => {
            fpModal.classList.add('hidden');
            fpModal.classList.remove('flex');
            fpImg.src = '';
        };
        document.querySelectorAll('[data-open-floorplan]').forEach(btn => {
            btn.addEventListener('click', () => {
                fpImg.src = btn.dataset.floorplanUrl;
                fpTitle.textContent = btn.dataset.floorplanTitle || 'Denah Restoran';
                fpModal.classList.remove('hidden');
                fpModal.classList.add('flex');
            });
        });
        document.getElementById('customer-floorplan-close')?.addEventListener('click', closeFp);
        fpModal.addEventListener('click', (e) => { if (e.target === fpModal) closeFp(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && fpModal.classList.contains('flex')) closeFp(); });
    }
})();
</script>
@endpush
