<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manager') · RRMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="bg-[#f6f7fb] font-sans text-slate-900 antialiased">
    @php
        $nav = [
            'Manager' => [
                ['Ringkasan', '#', false],
                ['Reservasi', '#', false],
                ['Persetujuan batal', route('manager.cancellations.index'), request()->routeIs('manager.cancellations.*')],
                ['Tarif & layanan', route('manager.tax-service.index'), request()->routeIs('manager.tax-service.*')],
                ['Laporan', route('manager.reports.index'), request()->routeIs('manager.reports.*')],
                ['Riwayat aktivitas', '#', false],
            ],
            'Pengelolaan outlet' => [
                ['Meja & denah', route('manager.tables.index'), request()->routeIs('manager.tables.*')],
                ['Menu & kategori', route('manager.menu.index'), request()->routeIs('manager.menu.*')],
                ['Event & promosi', '#', false],
            ],
        ];
        if (auth()->user()?->hasAnyRole(['finance', 'admin'])) {
            $nav['Finance'] = [
                ['Verifikasi pembayaran', route('finance.payments.index'), request()->routeIs('finance.payments.*')],
            ];
        }
    @endphp

    {{-- Bar atas (HP) --}}
    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:hidden">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel" class="h-6 w-auto">
            <img src="{{ asset('images/IbisLogo.png') }}" alt="ibis" class="h-6 w-auto">
        </div>
        <button id="sidebar-toggle" type="button" aria-label="Buka menu" class="grid size-10 place-items-center rounded-md hover:bg-slate-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>

    <div class="md:grid md:min-h-screen md:grid-cols-[250px_1fr]">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col overflow-y-auto border-r border-slate-200 bg-white p-5 shadow-xl md:sticky md:top-0 md:flex md:h-screen md:w-auto md:shadow-none">
            <div class="hidden items-center gap-3 md:flex">
                <img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel" class="h-7 w-auto">
                <img src="{{ asset('images/IbisLogo.png') }}" alt="ibis" class="h-7 w-auto">
            </div>
            <p class="mt-4 text-[10px] font-semibold tracking-widest text-slate-400 uppercase md:mt-6">Reservation Management</p>

            @foreach ($nav as $group => $items)
                <p class="mt-6 text-[11px] font-bold tracking-widest text-slate-500 uppercase">{{ $group }}</p>
                <nav class="mt-2 flex flex-col gap-1">
                    @foreach ($items as [$label, $href, $active])
                        <a href="{{ $href }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                                  {{ $active ? 'bg-[#eceffc] font-semibold text-[#14123f]' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span class="size-2 shrink-0 rounded-full {{ $active ? 'bg-[#14123f]' : 'border border-slate-400' }}"></span>
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            @endforeach

            <div class="mt-auto border-t border-slate-200 pt-5 pb-2">
                <p class="text-[10px] font-bold tracking-wider text-slate-400 uppercase">Outlet Aktif</p>
                <div class="mt-1 flex items-center justify-between text-xs font-semibold text-slate-700">
                    <span>Semua outlet</span>
                    <svg class="size-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </div>
            </div>
        </aside>

        <main class="min-w-0 bg-[#f6f8fb] px-6 py-6 md:px-10 md:py-8">
            {{-- Top bar: tanggal & avatar --}}
            <div class="flex items-center justify-end gap-3 mb-6">
                <span class="text-xs font-medium text-slate-400">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                <div class="grid size-9 place-items-center rounded-full bg-[#14123f] text-xs font-bold text-white shadow-xs">MG</div>
            </div>

            @yield('content')
        </main>
    </div>

    <script>
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            const s = document.getElementById('sidebar');
            s.classList.toggle('hidden');
            s.classList.toggle('flex');
        });
    </script>
    @stack('scripts')
</body>
</html>
