<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · RRMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="{{ asset('js/admin.js') }}"></script>
</head>
<body class="admin-app">
    <button class="admin-mobile-toggle" type="button" data-sidebar-toggle aria-label="Buka navigasi">☰</button>
    <div class="admin-shell">
        <aside class="admin-sidebar" data-sidebar>
            <div class="admin-brand">
                <img class="admin-brand-novotel" src="{{ asset('images/NovoLogo.png') }}" alt="Novotel">
                <img class="admin-brand-ibis" src="{{ asset('images/IbisLogo.png') }}" alt="ibis">
            </div>
            <div class="admin-product-label">RESERVATION MANAGEMENT</div>
            <div class="admin-role-label">ADMIN</div>
            <nav class="admin-nav" aria-label="Navigasi Admin">
                @php($navItems = [
                    ['Ringkasan', 'admin.dashboard', request()->routeIs('admin.dashboard')],
                    ['Outlet & area', 'admin.outlets.index', request()->routeIs('admin.outlets.*', 'admin.areas.*')],
                    ['Meja & denah', 'admin.tables.index', request()->routeIs('admin.tables.*')],
                    ['Menu & kategori', 'admin.menu.index', request()->routeIs('admin.menu.*')],
                    ['Pengguna & role', 'admin.users.index', request()->routeIs('admin.users.*')],
                    ['Promosi & acara', 'admin.content.index', request()->routeIs('admin.content.*')],
                    ['Konfigurasi', 'admin.configuration.index', request()->routeIs('admin.configuration.*')],
                    ['Audit log', 'admin.audit.index', request()->routeIs('admin.audit.*')],
                ])
                @foreach ($navItems as [$label, $routeName, $active])
                    <a class="admin-nav-link {{ $active ? 'is-active' : '' }}" href="{{ route($routeName) }}"><span class="admin-nav-dot">{{ $active ? '●' : '○' }}</span>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="admin-sidebar-footer">
                <div class="admin-eyebrow">OUTLET AKTIF</div>
                <div class="admin-outlet-current">Semua outlet <span>▾</span></div>
            </div>
        </aside>
        <div class="admin-content-column">
            <header class="admin-topbar">
                <time>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</time>
                <span class="admin-avatar" aria-label="Admin {{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span>
            </header>
            <main class="admin-main">
                @if (session('success'))<div class="admin-alert is-success" role="status">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="admin-alert is-error" role="alert">{{ session('error') }}</div>@endif
                @if ($errors->any())<div class="admin-alert is-error" role="alert"><strong>Periksa kembali input.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
