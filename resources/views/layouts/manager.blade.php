<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manager') · RRMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dinea-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { font-size: 16.5px; }
        body { font-size: 0.95rem; line-height: 1.5; }
        .text-\[10px\] { font-size: 0.75rem !important; }
        .text-\[11px\] { font-size: 0.8125rem !important; }
        .text-xs { font-size: 0.84rem !important; line-height: 1.35 !important; }
        .backend-mobile-topbar { position: sticky; top: 0; z-index: 30; border-bottom: 1px solid #eef1f7; }
        .backend-topbar { position: sticky; top: 0; z-index: 30; border-bottom: 1px solid #eef1f7; }
        .backend-sidebar { border-right: 1px solid #eef1f7; }
        @media (min-width: 768px) {
            .backend-sidebar { position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        }
        @media (max-width: 767px) {
            .backend-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 40; height: 100vh; overflow-y: auto; }
        }

        /* === START: Row Hover Highlights (easily revertible) === */
        main table tbody tr:not(:has(td[colspan])) td {
            transition: background-color 0.14s ease, box-shadow 0.14s ease;
        }
        main table tbody tr:not(:has(td[colspan])):hover td {
            background-color: #e8eeff !important;
        }
        main table tbody tr:not(:has(td[colspan])):hover td:first-child {
            box-shadow: inset 3px 0 0 #21265d;
        }
        main a.block.rounded-xl:hover {
            background-color: #e8eeff !important;
        }
        /* === END: Row Hover Highlights === */
    </style>
</head>
<body class="bg-[#f6f8fc] font-sans text-[#212b3b] antialiased">
    @php
        $outletQuery = request('outlet');
        $outletParams = is_scalar($outletQuery) && ctype_digit((string) $outletQuery) ? ['outlet' => $outletQuery] : [];
        $nav = [
            __('manager.nav_manager') => [
                [__('manager.nav_summary'),       route('manager.dashboard', $outletParams), request()->routeIs('manager.dashboard'), 'dashboard', null],
                [__('manager.nav_reservations'),  route('manager.reservations.index', $outletParams), request()->routeIs('manager.reservations.*'), 'reservations', null],
                [__('manager.nav_cancellations'), route('manager.cancellations.index', $outletParams), request()->routeIs('manager.cancellations.*'), 'cancellations', 'cancellations'],
                [__('manager.nav_rates'),         route('manager.tax-service.index', $outletParams), request()->routeIs('manager.tax-service.*'), null, null],
                [__('manager.nav_reports'),       route('manager.reports.index', $outletParams), request()->routeIs('manager.reports.*'), 'reports', null],
                [__('manager.nav_activity'),      route('manager.activities.index', $outletParams), request()->routeIs('manager.activities.*'), 'audit', null],
            ],
            __('manager.nav_outlet_mgmt') => [
                [__('manager.nav_tables'),  route('manager.tables.index', $outletParams),  request()->routeIs('manager.tables.*'), null, null],
                [__('manager.nav_menu'),    route('manager.menu.index', $outletParams),    request()->routeIs('manager.menu.*', 'manager.menu-categories.*'), null, null],
                [__('manager.nav_outlets'), route('manager.outlets.index', $outletParams), request()->routeIs('manager.outlets.*'), null, null],
                [__('manager.nav_events'),  route('manager.events.index', $outletParams), request()->routeIs('manager.events.*'), null, null],
            ],
        ];
        $liveActiveChannel = match (true) {
            request()->routeIs('manager.dashboard') => 'dashboard',
            request()->routeIs('manager.reservations.*') => 'reservations',
            request()->routeIs('manager.cancellations.*') => 'cancellations',
            request()->routeIs('manager.reports.*') => 'reports',
            request()->routeIs('manager.activities.*') => 'audit',
            default => '',
        };
        $liveAutoRefresh = request()->routeIs('manager.dashboard', 'manager.reservations.*', 'manager.cancellations.*', 'manager.reports.*', 'manager.activities.*');
        $userName = auth()->user()?->name ?? 'Manager';
        $initials = mb_strtoupper(mb_substr($userName, 0, 2));
        $today = now('Asia/Jakarta')->locale(app()->getLocale())->translatedFormat('l, d F Y');
    @endphp

    {{-- Bar atas (HP) --}}
    <div class="backend-mobile-topbar flex items-center justify-between bg-white px-4 py-3 md:hidden">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/dinea-logo.png') }}" alt="DINEA" style="height:44px;width:auto;">
        </div>
        <div class="flex items-center gap-1">
            @include('partials.lang-switcher')
            <button id="sidebar-toggle" type="button" aria-label="{{ __('manager.open_menu') }}" class="grid size-10 place-items-center rounded-md text-[#21265d] hover:bg-slate-100">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div class="md:grid md:min-h-screen md:grid-cols-[244px_minmax(0,1fr)]">
        <aside id="sidebar" class="backend-sidebar fixed inset-y-0 left-0 z-40 hidden w-[244px] flex-col overflow-y-auto bg-white px-[14px] pb-5 pt-0 shadow-xl md:sticky md:top-0 md:flex md:h-screen md:w-auto md:shadow-none">
            <div class="hidden items-center justify-center px-2.5 md:flex" style="height:70px;">
                <img src="{{ asset('images/dinea-logo.png') }}" alt="DINEA" style="height:58px;width:auto;" class="object-contain">
            </div>
            <p class="mt-2 px-2.5 text-[10px] font-semibold tracking-[0.035em] text-[#6e7a8f] uppercase">Reservation Management</p>

            @foreach ($nav as $group => $items)
                <p class="mt-3 px-2.5 text-xs font-semibold tracking-wide text-[#6e7a8f] uppercase">{{ $group }}</p>
                <nav class="mt-2 flex flex-col gap-1.5">
                    @foreach ($items as [$label, $href, $active, $liveChannel, $livePending])
                        <a href="{{ $href }}" @if($liveChannel) data-live-nav="{{ $liveChannel }}" @endif
                           class="flex min-h-10 items-center gap-2.5 rounded-[10px] px-3 py-2 text-[13.5px] transition-colors
                                  {{ $active ? 'bg-[#e8edff] font-semibold text-[#21265d]' : 'text-[#6e7a8f] hover:bg-[#f6f8fc] hover:text-[#21265d]' }}">
                            <span class="w-3.5 shrink-0 text-xs leading-none">{{ $active ? '●' : '○' }}</span>
                            <span class="leading-tight">{{ $label }}</span>
                            @if ($livePending)
                                <span class="live-nav-badge" data-live-pending="{{ $livePending }}" hidden></span>
                            @endif
                            @if ($liveChannel)
                                <span class="live-nav-dot" data-live-dot="{{ $liveChannel }}" hidden title="Data baru masuk"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endforeach

            @include('partials.backend-sidebar-outlet')
        </aside>

        <div class="min-w-0">
            {{-- Bar atas (desktop): tanggal, bahasa, avatar --}}
            <header class="backend-topbar hidden h-[70px] items-center justify-end gap-5 bg-white px-10 md:flex">
                <time class="text-sm text-slate-500">{{ $today }}</time>
                @include('partials.lang-switcher')
                <form method="POST" action="{{ route('manager.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Keluar</button>
                </form>
                <span translate="no" class="grid size-10 place-items-center rounded-full bg-[#1f1d5c] text-xs font-bold text-white" title="{{ $userName }}">{{ $initials }}</span>
            </header>

            <main class="w-full px-4 py-6 md:px-10 md:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    (() => {
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            const s = document.getElementById('sidebar');
            s.classList.toggle('hidden');
            s.classList.toggle('flex');
        });

        // Saklar bahasa (bisa ada lebih dari satu di halaman)
        const switchers = document.querySelectorAll('[data-lang-switcher]');
        const closeAll = () => switchers.forEach(s => {
            s.querySelector('[data-lang-menu]').classList.add('hidden');
            s.querySelector('[data-lang-toggle]').setAttribute('aria-expanded', 'false');
        });
        switchers.forEach(s => {
            const toggle = s.querySelector('[data-lang-toggle]');
            const menu = s.querySelector('[data-lang-menu]');
            toggle.addEventListener('click', e => {
                e.stopPropagation();
                const open = toggle.getAttribute('aria-expanded') !== 'true';
                closeAll();
                menu.classList.toggle('hidden', !open);
                toggle.setAttribute('aria-expanded', String(open));
            });
        });
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });

        // Form dengan data-confirm meminta konfirmasi sebelum dikirim (delegated)
        document.addEventListener('submit', e => {
            const f = e.target.closest?.('form[data-confirm]');
            if (f && !confirm(f.dataset.confirm)) e.preventDefault();
        });
    })();
    </script>
    @include('partials.backend-live-sync', [
        'liveRole' => 'manager',
        'liveEndpoint' => route('manager.live-status'),
        'liveActiveChannel' => $liveActiveChannel,
        'liveAutoRefresh' => $liveAutoRefresh,
    ])
    @stack('scripts')
</body>
</html>
