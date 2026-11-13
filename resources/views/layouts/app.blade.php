<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth scroll-pt-20">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('ui.site_title'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-page font-sans text-neutral-900 antialiased">
        <header class="sticky top-0 z-50 h-20 bg-page">
        <div class="flex h-full w-full items-center justify-between px-3 md:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 md:gap-4">
                <img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel Hotels & Resorts" class="block h-6 w-auto shrink-0 md:h-11">
                <span class="h-8 w-px shrink-0 bg-[#cfd5d8] md:h-10"></span>
                <img src="{{ asset('images/IbisLogo.png') }}" alt="ibis" class="block h-7 w-auto shrink-0 md:h-12">
            </a>

            {{-- Tombol hamburger (hanya di HP) --}}
            <button id="nav-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="main-nav"
                    class="grid size-10 place-items-center rounded-md text-neutral-800 hover:bg-black/5 md:hidden">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

            <nav id="main-nav" aria-label="Navigasi utama"
                 class="absolute inset-x-0 top-20 hidden flex-col gap-1 border-t border-black/5 bg-page px-4 pt-2 pb-5 text-base text-[#555] shadow-md
                        md:static md:flex md:flex-row md:items-center md:gap-10 md:border-0 md:bg-transparent md:p-0 md:text-[1.05rem] md:shadow-none">
                 @php
                    $navLinks = [
                        ['label' => __('ui.nav_home'),            'href' => route('home'),                    'active' => request()->routeIs('home')],
                        ['label' => __('ui.nav_restaurant'),      'href' => route('home') . '#pilih-restoran', 'active' => false],
                        ['label' => __('ui.nav_my_reservations'), 'href' => route('guest.reservations'),      'active' => request()->routeIs('guest.*')],
                    ];
                    $locale = app()->getLocale();
                @endphp

                @foreach ($navLinks as $link)
                    <a href="{{ $link['href'] }}"
                       class="relative w-fit py-2 transition-colors hover:text-neutral-900 md:py-1
                              after:absolute after:-bottom-0.5 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-neutral-900 after:transition-transform hover:after:scale-x-100
                              {{ $link['active'] ? 'text-neutral-900 after:scale-x-100' : 'after:scale-x-0' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <div class="relative mt-2 md:mt-0" id="lang-switcher">
    <button type="button" id="lang-toggle" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('ui.language') }}"
            class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 text-neutral-800 hover:bg-black/5">
        @include('partials.flag', ['code' => $locale])
        <span class="text-sm font-medium uppercase">{{ $locale }}</span>
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
    </button>

    <ul id="lang-menu" class="absolute top-full right-0 z-50 mt-2 hidden min-w-44 overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
        @foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $name)
            <li>
                <a href="{{ route('lang.switch', $code) }}"
                   class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100 {{ $locale === $code ? 'font-semibold text-neutral-900' : '' }}">
                    @include('partials.flag', ['code' => $code])
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="border-t border-[#9aa0a4] py-9 text-center tracking-wide text-[#6b7378]">
        <div class="mx-auto w-full max-w-7xl px-4">
           &copy; {{ date('Y') }} {{ __('ui.footer') }}
        </div>
    </footer>

    <script src="{{ asset('js/home.js') }}" defer></script>
    @stack('scripts')
</body>
</html>