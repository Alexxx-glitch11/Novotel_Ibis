
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manager') · RRMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f6f7fb] font-sans text-slate-900 antialiased">

    @php
        $nav = [
            'Manager' => [
                ['Ringkasan', route('manager.dashboard'), request()->routeIs('manager.dashboard')],
                ['Reservasi', '#', false],
                ['Persetujuan batal', route('manager.cancellations.index'), request()->routeIs('manager.cancellations.*')],
                ['Tarif & layanan', route('manager.tax-service.index'), request()->routeIs('manager.tax-service.*')],
                ['Laporan', route('manager.reports.index'), request()->routeIs('manager.reports.*')],
                ['Riwayat aktivitas', '#', false],
            ],
            'Pengelolaan outlet' => [
                ['Meja & denah', route('manager.tables.index'), request()->routeIs('manager.tables.*')],
                ['Menu & kategori', route('manager.menu.index'), request()->routeIs('manager.menu', 'manager.menu-categories.*')],
                ['Event & promosi', '#', false],
            ],
        ];

        $initials = auth()->check()
            ? strtoupper(mb_substr(auth()->user()->name, 0, 2))
            : 'AD';

        $today = now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y');
    @endphp

    {{-- Header mobile --}}
    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:hidden">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel" class="h-6 w-auto">
            <img src="{{ asset('images/IbisLogo.png') }}" alt="ibis" class="h-6 w-auto">
        </div>

        <button
            id="sidebar-toggle"
            type="button"
            aria-label="Buka menu"
            class="grid size-10 place-items-center rounded-md hover:bg-slate-100"
        >
            <svg class="size-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>

    <div class="md:grid md:min-h-screen md:grid-cols-[250px_minmax(0,1fr)]">

        {{-- Sidebar --}}
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col overflow-y-auto border-r border-slate-200 bg-white p-5 shadow-xl md:sticky md:top-0 md:flex md:h-screen md:w-auto md:shadow-none"
        >
            <div class="hidden items-center gap-3 md:flex">
                <img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel" class="h-7 w-auto">
                <img src="{{ asset('images/IbisLogo.png') }}" alt="ibis" class="h-7 w-auto">
            </div>

            <p class="mt-4 text-[10px] font-semibold tracking-widest text-slate-400 uppercase md:mt-6">
                Reservation Management
            </p>

            @foreach ($nav as $group => $items)
                <p class="mt-6 text-[11px] font-bold tracking-widest text-slate-500 uppercase">
                    {{ $group }}
                </p>

                <nav class="mt-2 flex flex-col gap-1">
                    @foreach ($items as [$label, $href, $active])
                        <a
                            href="{{ $href }}"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                            {{ $active ? 'bg-[#eceffc] font-semibold text-[#14123f]' : 'text-slate-600 hover:bg-slate-50' }}"
                        >
                            <span class="size-2 shrink-0 rounded-full
                                {{ $active ? 'bg-[#14123f]' : 'border border-slate-400' }}">
                            </span>

                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            @endforeach

            {{-- Outlet aktif --}}
            <div class="mt-auto border-t border-slate-200 pt-4">
                <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                    Outlet aktif
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    @yield('active-outlet', 'Semua outlet')
                </p>
            </div>
        </aside>

        {{-- Konten utama --}}
        <div class="min-w-0 bg-[#f6f8fb]">

            {{-- Topbar desktop --}}
            <header class="hidden h-[70px] items-center justify-end gap-5 border-b border-slate-200 bg-white px-10 md:flex">
                <span class="text-sm text-slate-500">
                    {{ $today }}
                </span>

                <span
                    class="grid size-10 place-items-center rounded-full bg-[#1f1d5c] text-xs font-bold text-white"
                    title="{{ auth()->user()->name ?? 'Admin' }}"
                >
                    {{ $initials }}
                </span>
            </header>

            <main class="px-4 py-6 md:px-10 md:py-8">
                @yield('content')
            </main>

        </div>
    </div>

    <script>
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('flex');
        });
    </script>

    @stack('scripts')

</body>
</html>
