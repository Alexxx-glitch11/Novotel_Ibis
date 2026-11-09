@extends('layouts.manager')

@section('title', 'Meja dan denah outlet')

@section('content')
    @php
        $input = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1f1d5c] focus:ring-2 focus:ring-[#1f1d5c]/20 focus:outline-none';
        $label = 'block text-xs font-semibold text-slate-600';
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#14123f]">Meja dan denah outlet</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola meja dan denah pada outlet yang ditugaskan. Posisi mengikuti konfigurasi data meja.</p>
        </div>
        @if ($area)
            <button type="button" id="btn-add-table" class="rounded-lg bg-[#1f1d5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#14123f]">+ Tambah meja</button>
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
        <select name="outlet" aria-label="Outlet" class="{{ $input }} !mt-0 !w-auto min-w-48"
                onchange="this.form.area.disabled = true; this.form.submit()">
            @foreach ($outlets as $o)
                <option value="{{ $o->outlets_id }}" @selected($o->outlets_id === $outlet->outlets_id)>{{ $o->name }}</option>
            @endforeach
        </select>
        <select name="area" aria-label="Area" class="{{ $input }} !mt-0 !w-auto min-w-36" onchange="this.form.submit()">
            @foreach ($areas as $a)
                <option value="{{ $a->areas_id }}" @selected($area && $a->areas_id === $area->areas_id)>{{ $a->name }}</option>
            @endforeach
        </select>
    </form>

    @if (! $area)
        <p class="mt-8 rounded-xl bg-white p-6 text-sm text-slate-500 ring-1 ring-slate-200">Outlet ini belum punya area.</p>
    @else
        <div class="mt-6 grid items-start gap-6 xl:grid-cols-2">

            {{-- ===== DENAH ===== --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <h2 class="text-xl font-bold">Denah area {{ $area->name }}</h2>
                <p class="text-sm text-slate-500">Pratinjau susunan meja yang dikelola.</p>

                <div id="denah-canvas"
                     class="relative mt-4 w-full overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200 {{ $area->floor_plan_url ? '' : 'aspect-[4/3]' }}">
                    @if ($area->floor_plan_url)
                        <img src="{{ $area->floor_plan_url }}" alt="Denah {{ $area->name }}" class="block h-auto w-full select-none" draggable="false">
                    @else
                        <div class="absolute inset-0 grid place-items-center px-6 text-center text-sm text-slate-400">Belum ada denah yang diunggah</div>
                    @endif

                    @foreach ($tables as $t)
                        <button type="button" data-marker="{{ $t->tables_id }}" data-code="{{ $t->table_code }}"
                                style="left: {{ $t->position_x }}%; top: {{ $t->position_y }}%"
                                title="Ubah meja {{ $t->table_code }}"
                                class="absolute -translate-x-1/2 -translate-y-1/2 rounded-lg border-2 px-2.5 py-1.5 text-sm font-semibold transition-[left,top]
                                       {{ $t->is_active ? 'border-[#1f1d5c] bg-white text-[#1f1d5c]' : 'border-slate-300 bg-slate-200 text-slate-400' }}">
                            {{ $t->table_code }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">Klik meja pada denah untuk mengubahnya.</p>

                {{-- Upload denah --}}
                <div class="mt-5 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-bold">Gambar denah</h3>
                    <form method="POST" action="{{ route('manager.floorplan.store', $area) }}" enctype="multipart/form-data"
                          class="mt-2 flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file" name="floor_plan" accept="image/png,image/jpeg,image/webp" required
                               class="min-w-0 flex-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold hover:file:bg-slate-200">
                        <button type="submit" class="rounded-lg bg-[#1f1d5c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14123f]">
                            {{ $area->floor_plan_path ? 'Ganti denah' : 'Unggah denah' }}
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau WebP, maksimal 5 MB. Denah ini akan tampil juga di halaman pelanggan.</p>

                    @if ($area->floor_plan_path)
                        <form method="POST" action="{{ route('manager.floorplan.destroy', $area) }}" class="mt-2"
                              onsubmit="return confirm('Hapus denah area ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Hapus denah</button>
                        </form>
                    @endif
                </div>
            </section>

            {{-- ===== DATA MEJA ===== --}}
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                <h2 class="text-xl font-bold">Data meja</h2>
                <p class="text-sm text-slate-500">Kapasitas dan ketersediaan per area.</p>

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
                                <label for="f-code" class="{{ $label }}">Kode meja</label>
                                <input id="f-code" name="table_code" maxlength="20" required class="{{ $input }}">
                            </div>
                            <div>
                                <label for="f-area" class="{{ $label }}">Area</label>
                                <select id="f-area" name="areas_id" data-current="{{ $area->areas_id }}" class="{{ $input }}">
                                    @foreach ($areas as $a)
                                        <option value="{{ $a->areas_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="f-cap" class="{{ $label }}">Kapasitas</label>
                                <input id="f-cap" type="number" name="capacity" min="1" max="50" required class="{{ $input }}">
                            </div>
                            <div>
                                <label for="f-min" class="{{ $label }}">Kapasitas minimal</label>
                                <input id="f-min" type="number" name="min_capacity" min="1" max="50" required class="{{ $input }}">
                            </div>
                            <div>
                                <label for="f-x" class="{{ $label }}">Posisi X (%), kiri ke kanan</label>
                                <input id="f-x" type="number" name="position_x" min="0" max="100" step="0.5" required class="{{ $input }}">
                                <input id="range-x" type="range" min="0" max="100" step="0.5" aria-label="Geser posisi X" class="mt-2 w-full accent-[#1f1d5c]">
                            </div>
                            <div>
                                <label for="f-y" class="{{ $label }}">Posisi Y (%), atas ke bawah</label>
                                <input id="f-y" type="number" name="position_y" min="0" max="100" step="0.5" required class="{{ $input }}">
                                <input id="range-y" type="range" min="0" max="100" step="0.5" aria-label="Geser posisi Y" class="mt-2 w-full accent-[#1f1d5c]">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Ubah posisi X dan Y, meja akan langsung berpindah di denah sebagai pratinjau.</p>

                        <label class="mt-3 flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" id="f-active" class="size-4 accent-[#1f1d5c]">
                            Meja aktif (dapat dipilih pelanggan)
                        </label>

                        <div class="mt-4 flex gap-2">
                            <button type="submit" id="f-submit" class="rounded-lg bg-[#1f1d5c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14123f]"></button>
                            <button type="button" id="f-cancel" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">Batal</button>
                        </div>
                    </form>
                </div>

                {{-- Tabel --}}
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                <th class="py-3 pr-3">Kode</th>
                                <th class="py-3 pr-3">Kapasitas</th>
                                <th class="py-3 pr-3">Area</th>
                                <th class="py-3 pr-3">Status</th>
                                <th class="py-3 text-right">Aksi</th>
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
                                            {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        <button type="button" class="js-edit font-medium text-[#1f1d5c] hover:underline"
                                                data-id="{{ $t->tables_id }}" data-table="{{ json_encode($payload) }}">Ubah</button>
                                        <form method="POST" action="{{ route('manager.tables.destroy', $t) }}" class="ml-3 inline"
                                              onsubmit="return confirm('Hapus meja {{ $t->table_code }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada meja di area ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <p class="mt-6 text-sm text-slate-500">Manager dapat mengelola kode, kapasitas, area, dan status meja untuk outlet yang ditugaskan. Ketersediaan mengikuti sistem reservasi.</p>
    @endif
@endsection

@push('scripts')
@if ($area)
<script>
(() => {
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
        m.style.left = clamp(f.x.value) + '%';
        m.style.top = clamp(f.y.value) + '%';
        m.textContent = f.code.value || '?';
    }

    function reset() {
        Object.values(markers).forEach((o) => {
            o.el.style.left = o.left;
            o.el.style.top = o.top;
            o.el.textContent = o.text;
            o.el.classList.remove(...hi);
        });
        ghost?.remove();
        ghost = null;
        editingId = null;
    }

    function open(data) {
        reset();
        editingId = data ? String(data.tables_id) : null;

        form.action = data ? form.dataset.updateUrl.replace('__ID__', data.tables_id) : form.dataset.storeUrl;
        document.getElementById('f-method').disabled = !data;
        document.getElementById('panel-title').textContent = data ? 'Ubah meja ' + data.table_code : 'Tambah meja';
        document.getElementById('f-submit').textContent = data ? 'Simpan perubahan' : 'Simpan meja';

        f.area.value = data ? data.areas_id : f.area.dataset.current;
        f.code.value = data ? data.table_code : '';
        f.cap.value = data ? data.capacity : 2;
        f.min.value = data ? data.min_capacity : 1;
        f.x.value = data ? data.position_x : 50;
        f.y.value = data ? data.position_y : 50;
        f.active.checked = data ? data.is_active : true;
        rx.value = f.x.value;
        ry.value = f.y.value;

        if (data) {
            markers[editingId]?.el.classList.add(...hi);
        } else {
            ghost = document.createElement('div');
            ghost.className = 'absolute -translate-x-1/2 -translate-y-1/2 rounded-lg border-2 border-dashed border-[#1f1d5c] bg-[#1f1d5c] px-2.5 py-1.5 text-sm font-semibold text-white ring-4 ring-[#1f1d5c]/20';
            canvas.appendChild(ghost);
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

    f.x.addEventListener('input', () => { rx.value = f.x.value; preview(); });
    f.y.addEventListener('input', () => { ry.value = f.y.value; preview(); });
    rx.addEventListener('input', () => { f.x.value = rx.value; preview(); });
    ry.addEventListener('input', () => { f.y.value = ry.value; preview(); });
    f.code.addEventListener('input', preview);
})();
</script>
@endif
@endpush