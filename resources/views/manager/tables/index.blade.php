@extends('layouts.manager')

@section('title', __('manager.tbl_title'))
@section('active-outlet', $outlet->name)

@section('content')
    @php
        $input = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none';
        $label = 'block text-xs font-semibold text-slate-600';
        $jsText = [
            'add'      => __('manager.panel_add'),
            'edit'     => __('manager.panel_edit', ['code' => ':code']),
            'saveNew'  => __('manager.f_save_new'),
            'saveEdit' => __('manager.f_save_edit'),
        ];
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#14123f]">{{ __('manager.tbl_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('manager.tbl_sub') }}</p>
        </div>
        @if ($area)
            <button type="button" id="btn-add-table" class="rounded-lg bg-[#1f1d5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">{{ __('manager.add_table') }}</button>
        @endif
    </div>

    {{-- Pesan --}}
    @if (session('success'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Filter outlet dan area --}}
    <form method="GET" action="{{ route('manager.tables.index') }}" class="mt-6 flex flex-wrap gap-3">
        <select name="outlet" aria-label="{{ __('manager.outlet_label') }}" class="{{ $input }} !mt-0 !w-auto min-w-48"
                onchange="this.form.area.disabled = true; this.form.submit()">
            @foreach ($outlets as $o)
                <option value="{{ $o->outlets_id }}" @selected($o->outlets_id === $outlet->outlets_id)>{{ $o->name }}{{ $o->is_active ? '' : ' ' . __('manager.inactive_tag') }}</option>
            @endforeach
        </select>
        <select name="area" aria-label="{{ __('manager.area_label') }}" class="{{ $input }} !mt-0 !w-auto min-w-36" onchange="this.form.submit()">
            @foreach ($areas as $a)
                <option value="{{ $a->areas_id }}" @selected($area && $a->areas_id === $area->areas_id)>{{ $a->name }}</option>
            @endforeach
        </select>
    </form>

    @if (! $area)
        <p class="mt-8 rounded-xl bg-white p-6 text-sm text-slate-500 ring-1 ring-slate-200">{{ __('manager.no_area') }}</p>
    @else
        <div class="mt-6 grid items-start gap-6 xl:grid-cols-2">

            {{-- ===== DENAH & KURSI ALA BIOSKOP / BUS ===== --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                @php
                    $sortedTables = $tables->sortBy('table_code', SORT_NATURAL | SORT_FLAG_CASE);
                    $groupedRows = $sortedTables->groupBy(function ($t) {
                        if (preg_match('/^([A-Za-z]+)/', (string) $t->table_code, $m)) {
                            return strtoupper($m[1]);
                        }
                        return 'Meja';
                    });
                @endphp

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-xl font-bold">{{ __('manager.plan_title', ['area' => $area->name]) }}</h2>
                        <p class="text-sm text-slate-500">Pilih kursi/meja pada tata letak di bawah untuk mengubahnya.</p>
                    </div>
                    @if ($area->floor_plan_url)
                        <button type="button" onclick="document.getElementById('manager-floorplan-modal').classList.remove('hidden'); document.getElementById('manager-floorplan-modal').classList.add('flex');"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#e8edff] px-3 py-2 text-xs font-semibold text-[#1f1d5c] hover:bg-[#dce3ff]">
                            Lihat Denah Penuh
                        </button>
                    @endif
                </div>

                {{-- Grid Kursi ala Bioskop / Bus --}}
                <div id="denah-canvas" class="mt-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    @if ($sortedTables->isEmpty())
                        <p class="py-10 text-center text-sm text-slate-400">{{ __('manager.no_tables') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($groupedRows as $rowLabel => $rowTables)
                                <div class="flex items-start gap-3">
                                    <span class="mt-2 grid size-7 shrink-0 place-items-center rounded-md bg-slate-200 text-xs font-bold text-slate-700">{{ $rowLabel }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($rowTables as $t)
                                            <button type="button"
                                                    data-marker="{{ $t->tables_id }}"
                                                    data-code="{{ $t->table_code }}"
                                                    title="{{ __('manager.panel_edit', ['code' => $t->table_code]) }}"
                                                    class="flex h-14 min-w-[68px] flex-col items-center justify-center rounded-xl border-2 px-2.5 text-sm font-semibold transition
                                                           {{ $t->is_active ? 'border-[#1f1d5c] bg-white text-[#1f1d5c] hover:bg-[#e8edff]' : 'border-slate-300 bg-slate-200 text-slate-400' }}">
                                                <span class="text-xs font-bold leading-none">{{ $t->table_code }}</span>
                                                <span class="mt-1 text-[10px] opacity-75">{{ $t->capacity }} org</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Upload & Pratinjau Denah Referensi --}}
                <div class="mt-5 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-bold">{{ __('manager.plan_image') }}</h3>
                    @if ($area->floor_plan_url)
                        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2">
                            <img src="{{ $area->floor_plan_url }}" alt="{{ __('manager.plan_alt', ['area' => $area->name]) }}" class="mx-auto max-h-56 w-auto rounded-lg object-contain">
                        </div>
                    @else
                        <p class="mt-2 text-xs text-slate-400">{{ __('manager.no_plan') }}</p>
                    @endif

                    <form method="POST" action="{{ route('manager.floorplan.store', $area) }}" enctype="multipart/form-data"
                          class="mt-3 flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file" name="floor_plan" accept="image/png,image/jpeg,image/webp" required
                               class="min-w-0 flex-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold hover:file:bg-slate-200">
                        <button type="submit" class="rounded-lg bg-[#1f1d5c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14123f]">
                            {{ $area->floor_plan_path ? __('manager.plan_replace') : __('manager.plan_upload') }}
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">Denah ini dapat diakses pelanggan sebagai panduan letak nomor meja/kursi (misal A12).</p>

                    @if ($area->floor_plan_path)
                        <form method="POST" action="{{ route('manager.floorplan.destroy', $area) }}" class="mt-2"
                              data-confirm="{{ __('manager.plan_delete_confirm') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:underline">{{ __('manager.plan_delete') }}</button>
                        </form>
                    @endif
                </div>
            </section>

            {{-- ===== DATA MEJA ===== --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <h2 class="text-xl font-bold">{{ __('manager.data_title') }}</h2>
                <p class="text-sm text-slate-500">{{ __('manager.data_sub') }}</p>

                {{-- Form tambah / ubah --}}
                <div id="table-panel" hidden class="mt-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    <h3 id="panel-title" class="text-base font-bold"></h3>

                    <form id="table-form" method="POST" action="{{ route('manager.tables.store') }}"
                          data-store-url="{{ route('manager.tables.store') }}"
                          data-update-url="{{ route('manager.tables.update', ['table' => '__ID__']) }}">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="f-method" disabled>
                        <input type="hidden" name="outlets_id" value="{{ $outlet->outlets_id }}">

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="f-code" class="{{ $label }}">Kode meja / rentang</label>
                                <input id="f-code" name="table_code" maxlength="40" required placeholder="Contoh: A1 atau A1-10" class="{{ $input }}">
                            </div>
                            <div>
                                <label for="f-area" class="{{ $label }}">{{ __('manager.f_area') }}</label>
                                <select id="f-area" name="areas_id" data-current="{{ $area->areas_id }}" class="{{ $input }}">
                                    @foreach ($areas as $a)
                                        <option value="{{ $a->areas_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="f-cap" class="{{ $label }}">{{ __('manager.f_cap') }}</label>
                                <input id="f-cap" type="number" name="capacity" min="1" max="50" required class="{{ $input }}">
                            </div>
                            <div>
                                <label for="f-min" class="{{ $label }}">{{ __('manager.f_min') }}</label>
                                <input id="f-min" type="number" name="min_capacity" min="1" max="50" required class="{{ $input }}">
                            </div>
                        </div>
                        <input id="f-x" type="hidden" name="position_x" value="50">
                        <input id="f-y" type="hidden" name="position_y" value="50">
                        <p id="f-code-hint" class="mt-2 text-xs text-slate-500">Isi satu kode (misal <strong>A1</strong>) atau rentang (misal <strong>A1-10</strong> atau <strong>A1-A10</strong>) untuk menambah banyak meja dalam sekali klik.</p>

                        <label class="mt-3 flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" id="f-active" class="size-4 accent-[#1f1d5c]">
                            {{ __('manager.f_active') }}
                        </label>

                        <div class="mt-4 flex gap-2">
                            <button type="submit" id="f-submit" class="rounded-lg bg-[#1f1d5c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14123f]"></button>
                            <button type="button" id="f-cancel" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">{{ __('manager.cancel') }}</button>
                        </div>
                    </form>
                </div>

                {{-- Tabel --}}
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                <th class="py-3 pr-3">{{ __('manager.col_code') }}</th>
                                <th class="py-3 pr-3">{{ __('manager.col_capacity') }}</th>
                                <th class="py-3 pr-3">{{ __('manager.col_area') }}</th>
                                <th class="py-3 pr-3">{{ __('manager.col_status') }}</th>
                                <th class="py-3 text-right">{{ __('manager.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tables as $t)
                                @php
                                    $payload = [
                                        'tables_id' => $t->tables_id, 'areas_id' => $t->areas_id, 'table_code' => $t->table_code,
                                        'capacity' => $t->capacity, 'min_capacity' => $t->min_capacity,
                                        'position_x' => (float) $t->position_x, 'position_y' => (float) $t->position_y,
                                        'is_active' => $t->is_active,
                                    ];
                                @endphp
                                <tr>
                                    <td class="py-3 pr-3 font-semibold">{{ $t->table_code }}</td>
                                    <td class="py-3 pr-3">{{ $t->capacity }}</td>
                                    <td class="py-3 pr-3">{{ $area->name }}</td>
                                    <td class="py-3 pr-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $t->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $t->is_active ? __('manager.st_active') : __('manager.st_inactive') }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        <button type="button" class="js-edit font-medium text-[#1f1d5c] hover:underline"
                                                data-id="{{ $t->tables_id }}" data-table="{{ json_encode($payload) }}">{{ __('manager.edit') }}</button>
                                        <form method="POST" action="{{ route('manager.tables.destroy', $t) }}" class="ml-3 inline"
                                              data-confirm="{{ __('manager.delete_table_confirm', ['code' => $t->table_code]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 hover:underline">{{ __('manager.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">{{ __('manager.no_tables') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <p class="mt-6 text-sm text-slate-500">{{ __('manager.tbl_footer') }}</p>

        @if ($area->floor_plan_url)
            <div id="manager-floorplan-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/75 p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) { this.classList.add('hidden'); this.classList.remove('flex'); }">
                <div class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                        <h3 class="text-sm font-bold text-[#1f1d5c]">Denah {{ $area->name }} · {{ $outlet->name }}</h3>
                        <button type="button" onclick="var m = document.getElementById('manager-floorplan-modal'); m.classList.add('hidden'); m.classList.remove('flex');" class="grid size-8 place-items-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600">✕</button>
                    </div>
                    <div class="overflow-auto bg-slate-100 p-4">
                        <img src="{{ $area->floor_plan_url }}" alt="Denah {{ $area->name }}" class="mx-auto max-h-[72vh] w-auto rounded-lg shadow-sm">
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
@if ($area)
<script>
(() => {
    const T = @json($jsText);
    const panel = document.getElementById('table-panel');
    const form = document.getElementById('table-form');
    const canvas = document.getElementById('denah-canvas');
    const f = {
        area: form.elements.areas_id, code: form.elements.table_code, cap: form.elements.capacity,
        min: form.elements.min_capacity, x: form.elements.position_x, y: form.elements.position_y,
        active: form.elements.is_active,
    };
    const rx = document.getElementById('range-x');
    const ry = document.getElementById('range-y');
    const hi = ['bg-[#1f1d5c]', 'text-white', 'ring-4', 'ring-[#1f1d5c]/20'];

    const markers = {};
    canvas.querySelectorAll('[data-marker]').forEach((m) => {
        markers[m.dataset.marker] = { el: m, left: m.style.left, top: m.style.top, text: m.dataset.code };
    });

    let editingId = null;
    let ghost = null;

    const clamp = (v) => Math.min(100, Math.max(0, parseFloat(v) || 0));

    function current() {
        return editingId ? markers[editingId]?.el : ghost;
    }

    function preview() {
        const m = current();
        if (!m) return;
        const codeSpan = m.querySelector('span');
        if (codeSpan) {
            codeSpan.textContent = f.code.value || '?';
        }
    }

    function reset() {
        Object.values(markers).forEach((o) => {
            const codeSpan = o.el.querySelector('span');
            if (codeSpan) codeSpan.textContent = o.text;
            o.el.classList.remove(...hi);
        });
        ghost = null;
        editingId = null;
    }

    function open(data) {
        reset();
        editingId = data ? String(data.tables_id) : null;

        form.action = data ? form.dataset.updateUrl.replace('__ID__', data.tables_id) : form.dataset.storeUrl;
        document.getElementById('f-method').disabled = !data;
        document.getElementById('panel-title').textContent = data ? T.edit.replace(':code', data.table_code) : T.add;
        document.getElementById('f-submit').textContent = data ? T.saveEdit : T.saveNew;

        f.area.value = data ? data.areas_id : f.area.dataset.current;
        f.code.value = data ? data.table_code : '';
        f.cap.value = data ? data.capacity : 2;
        f.min.value = data ? data.min_capacity : 1;
        f.x.value = data ? data.position_x : 50;
        f.y.value = data ? data.position_y : 50;
        f.active.checked = data ? data.is_active : true;
        document.getElementById('f-code-hint').hidden = Boolean(data);

        if (data) {
            markers[editingId]?.el.classList.add(...hi);
        }

        panel.hidden = false;
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        f.code.focus({ preventScroll: true });
        preview();
    }

    function close() {
        reset();
        panel.hidden = true;
    }

    document.getElementById('btn-add-table').addEventListener('click', () => open(null));
    document.getElementById('f-cancel').addEventListener('click', close);

    document.querySelectorAll('.js-edit').forEach((b) =>
        b.addEventListener('click', () => open(JSON.parse(b.dataset.table))));

    // klik meja di denah = ubah meja tersebut
    Object.entries(markers).forEach(([id, o]) =>
        o.el.addEventListener('click', () => document.querySelector(`.js-edit[data-id="${id}"]`)?.click()));

    f.code.addEventListener('input', preview);
})();
</script>
@endif
@endpush