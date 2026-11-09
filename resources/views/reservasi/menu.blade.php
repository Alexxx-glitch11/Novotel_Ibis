@extends('layouts.app')

@section('title', __('ui.order_menu') . ' · ' . $outlet->name)

@section('content')
@php
    $quantities = $selectedMenuItems ?? [];
    $itemsByCategory = $menuItems->groupBy(fn ($item) => $item->category->name);
    $menuCategories = $itemsByCategory->keys();
@endphp
<div class="mx-auto w-full max-w-6xl px-4 py-8 md:py-12">
    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">
            <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('reservasi.outlet', ['outlet' => $outlet->code, 'step' => 'tables']) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-[#24215f]">← {{ __('ui.back_to_table') }}</a>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[.18em] text-slate-400">{{ $outlet->name }}</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-[#252942]">{{ __('ui.order_menu') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('ui.order_menu_help') }}</p>
        </div>
        <div class="rounded-full bg-[#24215f] px-4 py-2 text-xs font-semibold text-white">{{ __('ui.hold_expires_in') }} <strong id="hold-countdown" data-expires="{{ $hold->expires_at->timestamp }}" class="ml-1 tabular-nums">--:--</strong></div>
    </div>

    <form method="POST" action="{{ route('reservasi.menu', $outlet->code) }}" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
        @csrf
        <div class="space-y-6">
            @if ($menuItems->isNotEmpty())
                <section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200 md:p-5">
                    <label for="menu-search" class="sr-only">Cari menu</label>
                    <div class="flex min-h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 focus-within:border-[#24215f] focus-within:ring-2 focus-within:ring-[#24215f]/10">
                        <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
                        <input id="menu-search" type="search" autocomplete="off" placeholder="Cari nama menu..." class="min-w-0 flex-1 bg-transparent py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400">
                        <button id="clear-menu-search" type="button" class="hidden rounded-md px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-200" aria-label="Hapus pencarian">Hapus</button>
                    </div>
                    <nav class="mt-4 flex gap-2 overflow-x-auto pb-1" aria-label="Filter kategori menu">
                        <button type="button" data-menu-category="*" aria-pressed="true" class="menu-category-filter shrink-0 rounded-full border border-[#24215f] bg-[#24215f] px-4 py-2 text-xs font-semibold text-white">Semua menu <span class="ml-1 opacity-75">{{ $menuItems->count() }}</span></button>
                        @foreach ($menuCategories as $category)
                            <button type="button" data-menu-category="{{ $category }}" aria-pressed="false" class="menu-category-filter shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-400 hover:bg-slate-50">{{ $category }} <span class="ml-1 text-slate-400">{{ $itemsByCategory[$category]->count() }}</span></button>
                        @endforeach
                    </nav>
                    <p id="menu-result-count" class="mt-2 text-xs text-slate-400" aria-live="polite">{{ $menuItems->count() }} menu tersedia</p>
                </section>
            @endif

            <div id="menu-list" class="space-y-6">
            @forelse ($itemsByCategory as $category => $categoryItems)
                <section data-menu-section data-category-name="{{ $category }}" class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                    <h2 class="text-lg font-bold text-[#252942]">{{ $category }}</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($categoryItems as $item)
                            <article data-menu-card data-search="{{ \Illuminate\Support\Str::lower($item->name . ' ' . $item->description . ' ' . $item->category->name) }}" class="flex min-h-28 gap-4 rounded-xl border border-slate-200 p-3">
                                @if ($item->image_path)
                                    <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}" class="size-20 shrink-0 rounded-lg object-cover">
                                @else
                                    <div class="grid size-20 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400" aria-hidden="true">🍽</div>
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <h3 class="font-semibold text-slate-800">{{ $item->name }}</h3>
                                    @if ($item->description)<p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $item->description }}</p>@endif
                                    <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                                        <span class="text-sm font-semibold text-[#24215f]">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</span>
                                        <label class="sr-only" for="quantity-{{ $item->menu_items_id }}">{{ __('ui.quantity_for', ['name' => $item->name]) }}</label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" data-quantity-step="-1" data-target="quantity-{{ $item->menu_items_id }}" aria-label="{{ __('ui.decrease_quantity') }}" class="grid size-8 place-items-center rounded-full border border-slate-300 text-lg text-slate-700 hover:bg-slate-50">−</button>
                                            <input id="quantity-{{ $item->menu_items_id }}" name="items[{{ $item->menu_items_id }}]" type="number" min="0" max="50" value="{{ old('items.' . $item->menu_items_id, $quantities[$item->menu_items_id] ?? 0) }}" data-price="{{ $item->price }}" class="w-10 appearance-none text-center text-sm font-semibold tabular-nums focus:outline-none">
                                            <button type="button" data-quantity-step="1" data-target="quantity-{{ $item->menu_items_id }}" aria-label="{{ __('ui.increase_quantity') }}" class="grid size-8 place-items-center rounded-full border border-slate-300 text-lg text-slate-700 hover:bg-slate-50">+</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="rounded-2xl bg-white p-8 text-center ring-1 ring-slate-200">
                    <h2 class="font-bold text-[#252942]">{{ __('ui.menu_empty_title') }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ __('ui.menu_empty_help') }}</p>
                </section>
            @endforelse
            </div>
            <section id="menu-no-results" hidden class="rounded-2xl bg-white p-8 text-center ring-1 ring-slate-200">
                <h2 class="font-bold text-[#252942]">Menu tidak ditemukan</h2>
                <p class="mt-2 text-sm text-slate-500">Coba ubah kata pencarian atau pilih kategori lain.</p>
            </section>
        </div>

        <aside class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 lg:sticky lg:top-24">
            <h2 class="border-b border-slate-100 pb-3 text-base font-bold text-[#252942]">{{ __('ui.order_summary') }}</h2>
            <p class="mt-4 text-xs text-slate-500">{{ __('ui.table_word') }} {{ $selectedTable?->table_code }} · {{ $selectedTable?->area?->name }}</p>
            <div id="menu-order-summary" class="mt-3 space-y-2 text-sm"></div>
            <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-sm font-bold text-[#252942]"><span>{{ __('ui.subtotal') }}</span><span id="menu-subtotal">Rp 0</span></div>
            <button type="submit" class="mt-5 min-h-11 w-full rounded-lg bg-[#24215f] px-4 text-sm font-semibold text-white transition hover:bg-[#181644]">{{ __('ui.continue_to_confirmation') }}</button>
            <p class="mt-2 text-center text-[11px] leading-5 text-slate-400">{{ __('ui.menu_optional_notice') }}</p>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const inputs = document.querySelectorAll('input[data-price]');
    const summary = document.getElementById('menu-order-summary');
    const subtotal = document.getElementById('menu-subtotal');
    const search = document.getElementById('menu-search');
    const clearSearch = document.getElementById('clear-menu-search');
    const noResults = document.getElementById('menu-no-results');
    const resultCount = document.getElementById('menu-result-count');
    const categoryButtons = document.querySelectorAll('[data-menu-category]');
    let selectedCategory = '*';
    const format = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    const update = () => {
        summary.replaceChildren();
        let total = 0;
        inputs.forEach(input => {
            const quantity = Math.max(0, Number(input.value || 0));
            if (!quantity) return;
            const item = input.closest('article');
            const lineTotal = Number(input.dataset.price) * quantity;
            total += lineTotal;
            const row = document.createElement('div');
            row.className = 'flex justify-between gap-3 text-slate-600';
            const name = document.createElement('span');
            name.textContent = `${item.querySelector('h3').textContent} × ${quantity}`;
            const price = document.createElement('span');
            price.className = 'shrink-0 font-medium text-slate-800';
            price.textContent = format(lineTotal);
            row.append(name, price);
            summary.append(row);
        });
        subtotal.textContent = format(total);
    };
    inputs.forEach(input => input.addEventListener('input', update));
    document.querySelectorAll('[data-quantity-step]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);
        input.value = Math.min(50, Math.max(0, Number(input.value || 0) + Number(button.dataset.quantityStep)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    update();

    const filterMenus = () => {
        const query = (search?.value || '').trim().toLocaleLowerCase('id');
        let visibleCount = 0;
        document.querySelectorAll('[data-menu-section]').forEach(section => {
            const categoryMatches = selectedCategory === '*' || section.dataset.categoryName === selectedCategory;
            let visibleInSection = 0;
            section.querySelectorAll('[data-menu-card]').forEach(card => {
                const matches = categoryMatches && (!query || card.dataset.search.includes(query));
                card.hidden = !matches;
                if (matches) visibleInSection += 1;
            });
            section.hidden = visibleInSection === 0;
            visibleCount += visibleInSection;
        });
        if (noResults) noResults.hidden = visibleCount > 0;
        if (resultCount) resultCount.textContent = `${visibleCount} menu ditemukan`;
        if (clearSearch) clearSearch.classList.toggle('hidden', !query);
    };

    categoryButtons.forEach(button => button.addEventListener('click', () => {
        selectedCategory = button.dataset.menuCategory;
        categoryButtons.forEach(option => {
            const active = option === button;
            option.setAttribute('aria-pressed', String(active));
            option.classList.toggle('border-[#24215f]', active);
            option.classList.toggle('bg-[#24215f]', active);
            option.classList.toggle('text-white', active);
            option.classList.toggle('border-slate-200', !active);
            option.classList.toggle('bg-white', !active);
            option.classList.toggle('text-slate-600', !active);
        });
        filterMenus();
    }));
    search?.addEventListener('input', filterMenus);
    clearSearch?.addEventListener('click', () => { search.value = ''; filterMenus(); search.focus(); });
    filterMenus();

    const countdown = document.getElementById('hold-countdown');
    const deadline = Number(countdown.dataset.expires) * 1000;
    const updateCountdown = () => {
        const seconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        if (!seconds) document.querySelector('form button[type="submit"]').disabled = true;
    };
    updateCountdown(); window.setInterval(updateCountdown, 1000);
})();
</script>
@endpush
