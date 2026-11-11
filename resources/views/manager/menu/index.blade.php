@extends('layouts.manager')

@section('title', 'Menu & kategori outlet')

@section('content')
@php
    $input = 'mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#24215f] focus:outline-none focus:ring-2 focus:ring-[#24215f]/10';
    $label = 'block text-xs font-semibold text-slate-600';
    $activeCategory = (int) request()->query('category');
    $oldMenuId = old('_form') === 'menu' ? old('menu_item_id') : null;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-[#252942]">Menu dan kategori outlet</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola nama, foto, kategori, harga, dan ketersediaan menu.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" id="open-category-form" class="min-h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-[#24215f] hover:bg-slate-50">+ Tambah kategori</button>
        <button type="button" id="open-menu-form" class="min-h-11 rounded-lg bg-[#24215f] px-5 text-sm font-semibold text-white transition hover:bg-[#191744]">+ Tambah menu</button>
    </div>
</div>

@if (session('success'))<div class="mt-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">{{ session('success') }}</div>@endif
@if (session('error'))<div class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ session('error') }}</div>@endif
@if ($errors->any())
    <div class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">
        <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form id="category-form" method="POST" action="{{ route('manager.menu-categories.store') }}" @if(old('_form') !== 'category') hidden @endif class="mt-5 rounded-xl bg-white p-5 ring-1 ring-slate-200">
    @csrf
    <input type="hidden" name="_form" value="category">
    <input type="hidden" name="outlets_id" value="{{ $outlet->outlets_id }}">
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-56 flex-1"><label for="category-name" class="{{ $label }}">Nama kategori</label><input id="category-name" name="name" value="{{ old('_form') === 'category' ? old('name') : '' }}" maxlength="50" required placeholder="Contoh: Makanan" class="{{ $input }}"></div>
        <button type="submit" class="min-h-11 rounded-lg bg-[#24215f] px-5 text-sm font-semibold text-white">Simpan kategori</button>
        <button type="button" id="close-category-form" class="min-h-11 rounded-lg px-4 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
    </div>
</form>

<section class="mt-6 rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-lg font-bold text-[#252942]">Daftar menu</h2>
        <form method="GET" action="{{ route('manager.menu.index') }}" class="flex flex-wrap gap-2">
            <select name="outlet" aria-label="Outlet" class="min-h-10 min-w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" onchange="this.form.submit()">
                @foreach ($outlets as $choice)<option value="{{ $choice->outlets_id }}" @selected($choice->outlets_id === $outlet->outlets_id)>{{ $choice->name }}</option>@endforeach
            </select>
            <input type="hidden" name="category" value="{{ $activeCategory ?: '' }}">
            <div class="flex min-w-56 overflow-hidden rounded-lg border border-slate-200">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama menu..." class="min-w-0 flex-1 bg-slate-50 px-3 text-sm outline-none">
                <button type="submit" aria-label="Cari menu" class="px-3 text-slate-500 hover:bg-slate-100">⌕</button>
            </div>
        </form>
    </div>

    <nav aria-label="Filter kategori menu" class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('manager.menu.index', ['outlet' => $outlet->outlets_id, 'q' => request('q')]) }}" class="rounded-lg border px-4 py-2 text-xs font-semibold {{ ! $activeCategory ? 'border-[#24215f] bg-[#24215f] text-white' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Semua menu</a>
        @foreach ($categories as $category)
            <a href="{{ route('manager.menu.index', ['outlet' => $outlet->outlets_id, 'category' => $category->menu_categories_id, 'q' => request('q')]) }}" class="rounded-lg border px-4 py-2 text-xs font-semibold {{ $activeCategory === $category->menu_categories_id ? 'border-[#24215f] bg-[#24215f] text-white' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">{{ $category->name }}</a>
        @endforeach
        @if ($categories->isEmpty())<span class="self-center text-xs text-slate-400">Tambahkan kategori sebelum membuat menu pertama.</span>@endif
    </nav>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead><tr class="border-b border-slate-200 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><th class="py-3 pr-4">Menu</th><th class="px-3 py-3">Kategori</th><th class="px-3 py-3">Harga</th><th class="px-3 py-3">Outlet</th><th class="px-3 py-3">Ketersediaan</th><th class="py-3 pl-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($menuItems as $item)
                    @php $payload = ['menu_items_id' => $item->menu_items_id, 'menu_categories_id' => $item->menu_categories_id, 'name' => $item->name, 'description' => $item->description, 'price' => (float) $item->price, 'image_path' => $item->image_path, 'image_url' => $item->image_path ? asset($item->image_path) : null, 'is_available' => $item->is_available]; @endphp
                    <tr>
                        <td class="py-3 pr-4"><div class="flex items-center gap-3">
                            @if ($item->image_path)<img src="{{ asset($item->image_path) }}" alt="" class="size-11 rounded-lg object-cover ring-1 ring-slate-200">@else<div class="grid size-11 place-items-center rounded-lg bg-slate-100 text-slate-400" aria-hidden="true">🍽</div>@endif
                            <div class="min-w-0"><p class="font-semibold text-slate-800">{{ $item->name }}</p>@if($item->description)<p class="mt-0.5 max-w-sm truncate text-xs text-slate-400">{{ $item->description }}</p>@endif</div>
                        </div></td>
                        <td class="px-3 py-3 text-slate-500">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-800">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-slate-500">{{ $outlet->name }}</td>
                        <td class="px-3 py-3"><span class="text-xs font-semibold {{ $item->is_available ? 'text-emerald-700' : 'text-amber-700' }}">{{ $item->is_available ? 'Tersedia' : 'Tidak tersedia' }}</span></td>
                        <td class="py-3 pl-3 text-right"><button type="button" class="js-edit-menu font-semibold text-[#24215f] hover:underline" data-item="{{ json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT) }}">Edit</button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-sm text-slate-400">Belum ada menu untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<p class="mt-5 text-xs leading-5 text-slate-500">Reservasi yang sudah dibuat tetap menggunakan snapshot harga saat transaksi. Perubahan pada menu hanya berlaku untuk pesanan baru.</p>

<div id="menu-modal" @if(old('_form') !== 'menu') hidden @endif class="fixed inset-0 z-[70] items-center justify-center overflow-y-auto bg-slate-950/50 p-4 {{ old('_form') === 'menu' ? 'flex' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="menu-modal-title">
    <div class="my-auto w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 md:px-7">
            <div><h2 id="menu-modal-title" class="text-xl font-bold text-[#252942]">{{ $oldMenuId ? 'Ubah menu' : 'Tambah menu' }}</h2><p class="mt-1 text-xs text-slate-500">Lengkapi informasi menu outlet.</p></div>
            <button type="button" id="close-menu-form" aria-label="Tutup" class="grid size-9 place-items-center rounded-full text-xl text-slate-500 hover:bg-slate-100">×</button>
        </div>
        <form id="menu-form" method="POST" action="{{ $oldMenuId ? route('manager.menu.update', $oldMenuId) : route('manager.menu.store') }}" enctype="multipart/form-data" data-store-url="{{ route('manager.menu.store') }}" data-update-url="{{ route('manager.menu.update', ['menuItem' => '__ID__']) }}" class="p-5 md:p-7">
            @csrf
            <input id="menu-method" type="hidden" name="_method" value="PUT" @disabled(! $oldMenuId)>
            <input id="menu-item-id" type="hidden" name="menu_item_id" value="{{ $oldMenuId }}">
            <input type="hidden" name="_form" value="menu">
            <input type="hidden" name="outlets_id" value="{{ $outlet->outlets_id }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="menu-image" class="{{ $label }}">Foto menu <span class="font-normal text-slate-400">(JPG, PNG, WebP, maks. 5 MB)</span></label>
                    <label for="menu-image" class="mt-1 flex cursor-pointer items-center gap-4 rounded-xl border border-dashed border-slate-300 p-3 hover:border-[#24215f]">
                        <img id="menu-image-preview" src="" alt="Pratinjau foto menu" class="hidden size-20 rounded-lg object-cover ring-1 ring-slate-200">
                        <span id="menu-image-placeholder" class="grid size-20 shrink-0 place-items-center rounded-lg bg-slate-100 text-2xl text-slate-400" aria-hidden="true">＋</span>
                        <span><strong class="block text-sm text-slate-700">Pilih gambar menu</strong><span class="mt-1 block text-xs text-slate-400">Unggah foto agar tampil di daftar menu pelanggan.</span></span>
                        <input id="menu-image" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="sr-only">
                    </label>
                </div>
                <div><label for="menu-name" class="{{ $label }}">Nama menu</label><input id="menu-name" name="name" value="{{ old('_form') === 'menu' ? old('name') : '' }}" maxlength="100" required placeholder="Contoh: Nasi Goreng Kampung" class="{{ $input }}"></div>
                <div><label for="menu-price" class="{{ $label }}">Harga (Rp)</label><input id="menu-price" name="price" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9.,]*" value="{{ old('_form') === 'menu' ? old('price') : '' }}" required placeholder="95000" aria-describedby="menu-price-help" class="{{ $input }}"><p id="menu-price-help" class="mt-1 text-[11px] text-slate-400">Masukkan nominal Rupiah, misalnya 958600.</p></div>
                <div class="sm:col-span-2"><label for="menu-category" class="{{ $label }}">Kategori</label><select id="menu-category" name="menu_categories_id" required class="{{ $input }}"><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->menu_categories_id }}" @selected((string) old('menu_categories_id', '') === (string) $category->menu_categories_id)>{{ $category->name }}</option>@endforeach</select>
                    @if ($categories->isEmpty())<p class="mt-1 text-xs text-amber-700">Tambahkan kategori terlebih dahulu dengan tombol “Tambah kategori”.</p>@endif
                </div>
                <div class="sm:col-span-2"><label for="menu-description" class="{{ $label }}">Deskripsi</label><textarea id="menu-description" name="description" rows="3" maxlength="5000" placeholder="Jelaskan bahan atau cita rasa menu ini..." class="{{ $input }}">{{ old('_form') === 'menu' ? old('description') : '' }}</textarea></div>
                <label class="sm:col-span-2 flex items-center gap-2 text-sm text-slate-700"><input id="menu-available" type="checkbox" name="is_available" value="1" @checked(old('_form') === 'menu' ? old('is_available') : true) class="size-4 accent-[#24215f]">Menu tersedia untuk dipesan pelanggan</label>
            </div>
            <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <button type="button" id="cancel-menu-form" class="min-h-11 rounded-lg px-5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button id="submit-menu-form" type="submit" @disabled($categories->isEmpty()) class="min-h-11 rounded-lg bg-[#24215f] px-5 text-sm font-semibold text-white hover:bg-[#191744] disabled:cursor-not-allowed disabled:bg-slate-300">{{ $oldMenuId ? 'Simpan perubahan' : 'Simpan menu' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('menu-modal');
    const form = document.getElementById('menu-form');
    const method = document.getElementById('menu-method');
    const preview = document.getElementById('menu-image-preview');
    const placeholder = document.getElementById('menu-image-placeholder');
    const imageInput = document.getElementById('menu-image');
    const priceInput = document.getElementById('menu-price');
    const categoryForm = document.getElementById('category-form');
    const defaults = {
        name: @json(old('_form') === 'menu' ? old('name') : ''),
        description: @json(old('_form') === 'menu' ? old('description') : ''),
        price: @json(old('_form') === 'menu' ? old('price') : ''),
        category: @json(old('_form') === 'menu' ? old('menu_categories_id') : ''),
        available: @json(old('_form') === 'menu' ? (bool) old('is_available') : true),
        id: @json($oldMenuId),
    };

    function resetForm() {
        form.reset();
        form.action = form.dataset.storeUrl;
        method.disabled = true;
        document.getElementById('menu-item-id').value = '';
        document.getElementById('menu-modal-title').textContent = 'Tambah menu';
        document.getElementById('submit-menu-form').textContent = 'Simpan menu';
        preview.removeAttribute('src');
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
    function openModal(item = null) {
        resetForm();
        if (item) {
            form.action = form.dataset.updateUrl.replace('__ID__', item.menu_items_id);
            method.disabled = false;
            document.getElementById('menu-item-id').value = item.menu_items_id;
            document.getElementById('menu-modal-title').textContent = 'Ubah menu';
            document.getElementById('submit-menu-form').textContent = 'Simpan perubahan';
            form.elements.name.value = item.name;
            form.elements.description.value = item.description || '';
        form.elements.price.value = Math.round(Number(item.price));
            form.elements.menu_categories_id.value = item.menu_categories_id;
            form.elements.is_available.checked = item.is_available;
            if (item.image_path) {
                preview.src = item.image_url;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
        }
        modal.hidden = false;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        form.elements.name.focus();
    }
    function closeModal() { modal.hidden = true; modal.classList.add('hidden'); modal.classList.remove('flex'); }

    document.getElementById('open-menu-form')?.addEventListener('click', () => openModal());
    document.getElementById('close-menu-form')?.addEventListener('click', closeModal);
    document.getElementById('cancel-menu-form')?.addEventListener('click', closeModal);
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    document.querySelectorAll('.js-edit-menu').forEach(button => button.addEventListener('click', () => openModal(JSON.parse(button.dataset.item))));
    imageInput.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    });
    priceInput.addEventListener('input', () => {
        const cleaned = priceInput.value.replace(/\D/g, '');
        if (priceInput.value !== cleaned) priceInput.value = cleaned;
    });
    document.getElementById('open-category-form')?.addEventListener('click', () => { categoryForm.hidden = false; categoryForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); categoryForm.elements.name.focus(); });
    document.getElementById('close-category-form')?.addEventListener('click', () => { categoryForm.hidden = true; });

    if (defaults.id) {
        form.elements.name.value = defaults.name;
        form.elements.description.value = defaults.description || '';
        form.elements.price.value = defaults.price.replace(/\D/g, '');
        form.elements.menu_categories_id.value = defaults.category;
        form.elements.is_available.checked = defaults.available;
        document.getElementById('menu-item-id').value = defaults.id;
        method.disabled = false;
        modal.hidden = false;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
})();
</script>
@endpush
