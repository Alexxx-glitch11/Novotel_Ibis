<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir') · RRMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dinea-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/jsQR.min.js') }}"></script>
    <style>
        :root {
            --bg-page: #f6f8fc;
            --border-line: #e0e5f0;
            --text-ink: #212b3b;
            --text-muted: #6e7a8f;
            --brand-navy: #21265d;
            --brand-navy-hover: #171c4f;
            --brand-soft: #e8edff;
            --green-bg: #e3f5eb;
            --green-ink: #147a4f;
            --amber-bg: #fff2db;
            --amber-ink: #9e5c14;
            --red-bg: #fff0ef;
            --red-ink: #ab3333;
        }
        html { font-size: 16.5px; }
        body { font-size: 0.95rem; line-height: 1.5; background: var(--bg-page); color: var(--text-ink); }
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

        /* Shared Figma Design System Components for Cashier */
        .cashier-page-head h1 { margin: 0; font-size: 26px; line-height: 34px; font-weight: 600; color: var(--text-ink); letter-spacing: -0.02em; }
        .cashier-page-head p { margin: 4px 0 0; font-size: 14px; color: var(--text-muted); }

        .cashier-card { background: #fff; border: 1px solid var(--border-line); border-radius: 16px; }
        .cashier-card-pad { padding: 20px 22px; }
        .cashier-card-title { margin: 0; font-size: 17px; font-weight: 600; color: var(--text-ink); line-height: 1.35; }
        .cashier-card-sub { margin: 3px 0 0; font-size: 12px; color: var(--text-muted); }

        .cashier-metrics-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-top: 18px; }
        .cashier-metric {
            background: #fff; border: 1px solid var(--border-line); border-radius: 16px;
            padding: 18px; display: flex; align-items: flex-start; gap: 14px; min-height: 110px;
        }
        .cashier-metric-icon {
            width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center;
            font-size: 15px; font-weight: 600; color: var(--brand-navy); flex-shrink: 0;
        }
        .cashier-metric-icon.is-blue { background: var(--brand-soft); }
        .cashier-metric-icon.is-amber { background: var(--amber-bg); }
        .cashier-metric-icon.is-green { background: var(--green-bg); }
        .cashier-metric-label { margin: 0; font-size: 13px; color: var(--text-muted); }
        .cashier-metric-val { margin: 2px 0; font-size: 25px; line-height: 1.2; font-weight: 600; color: var(--text-ink); }
        .cashier-metric-note { margin: 0; font-size: 11px; color: var(--text-muted); }

        .cashier-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 38px; padding: 8px 14px; border-radius: 9px; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, border-color .15s;
            white-space: nowrap;
        }
        .cashier-btn-lg { min-height: 44px; border-radius: 10px; font-size: 14px; padding: 10px 18px; }
        .cashier-btn-sm { min-height: 30px; padding: 5px 12px; border-radius: 8px; font-size: 12px; }
        .cashier-btn-primary { background: var(--brand-navy); color: #fff; border-color: var(--brand-navy); }
        .cashier-btn-primary:hover { background: var(--brand-navy-hover); }
        .cashier-btn-soft { background: var(--brand-soft); color: var(--brand-navy); border-color: transparent; }
        .cashier-btn-soft:hover { background: #dce3ff; }
        .cashier-btn-outline { background: #fff; color: var(--brand-navy); border-color: var(--border-line); }
        .cashier-btn-outline:hover { background: var(--bg-page); }
        .cashier-btn-subtle { background: var(--bg-page); color: var(--brand-navy); border-color: var(--border-line); }
        .cashier-btn-subtle:hover { background: var(--brand-soft); }

        .cashier-pill {
            display: inline-flex; align-items: center; justify-content: center; gap: 4px;
            padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .cashier-pill.is-green { background: var(--green-bg); color: var(--green-ink); }
        .cashier-pill.is-amber { background: var(--amber-bg); color: var(--amber-ink); }
        .cashier-pill.is-neutral { background: var(--bg-page); color: var(--text-muted); }
        .cashier-pill.is-red { background: var(--red-bg); color: var(--red-ink); }
        .cashier-pill.is-navy { background: var(--brand-soft); color: var(--brand-navy); }

        .cashier-input, .cashier-select, .cashier-textarea {
            width: 100%; border: 1px solid var(--border-line); border-radius: 9px;
            background: var(--bg-page); color: var(--text-ink); font-size: 13px; padding: 8px 12px;
            outline: none; transition: border-color .15s, background .15s;
        }
        .cashier-input, .cashier-select { min-height: 40px; }
        .cashier-textarea { background: #fff; min-height: 92px; resize: vertical; }
        .cashier-input:focus, .cashier-select:focus, .cashier-textarea:focus {
            border-color: var(--brand-navy); background: #fff;
        }
        .cashier-input-mono { font-family: ui-monospace, monospace; }
        .cashier-field-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-ink); margin-bottom: 6px; }
        .cashier-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); }

        .cashier-table-wrap { width: 100%; overflow-x: auto; }
        .cashier-table { width: 100%; border-collapse: collapse; text-align: left; }
        .cashier-table th {
            padding: 10px 10px; border-bottom: 1px solid var(--border-line);
            font-size: 11px; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; color: var(--text-muted);
            white-space: nowrap;
        }
        .cashier-table td { padding: 15px 10px; border-bottom: 1px solid var(--border-line); font-size: 13px; vertical-align: middle; }
        .cashier-table tbody tr:last-child td { border-bottom: 0; }

        .cashier-mobile-list { display: none; gap: 10px; }
        .cashier-mobile-item {
            display: block; text-decoration: none; color: inherit; border: 1px solid var(--border-line);
            border-radius: 12px; padding: 14px; background: #fbfcfe;
        }
        .cashier-mobile-item.is-selected { border-color: var(--brand-navy); background: #f5f7ff; }

        .cashier-split-main { display: grid; grid-template-columns: minmax(0, 760fr) minmax(300px, 376fr); gap: 20px; align-items: start; margin-top: 20px; }
        .cashier-split-main--stretch { align-items: stretch; }
        .cashier-card--stretch { display: flex; flex-direction: column; height: 100%; }
        .cashier-split-main--qr { grid-template-columns: minmax(0, 1.45fr) minmax(300px, 1fr); }
        .cashier-split-main--cancel { grid-template-columns: minmax(0, 520fr) minmax(300px, 580fr); }
        .cashier-filter-grid-5 { display: grid; grid-template-columns: 185px 155px 145px minmax(180px, 1fr) auto; gap: 12px; margin-top: 16px; }
        .cashier-table--compact th { padding: 10px 6px; font-size: 10.5px; white-space: normal; line-height: 1.3; }
        .cashier-table--compact td { padding: 11px 6px; font-size: 12px; }
        .cashier-btn-pair { display: grid; grid-template-columns: minmax(0, 220fr) minmax(0, 288fr); gap: 12px; padding-top: 4px; }
        .cashier-side-stack { display: grid; gap: 16px; }
        .cashier-detail-list { margin: 14px 0 0; }
        .cashier-detail-row {
            display: grid; grid-template-columns: 155px 1fr; gap: 10px;
            padding: 13px 0; border-bottom: 1px solid var(--border-line); font-size: 13px;
        }
        .cashier-detail-row:last-child { border-bottom: 0; }
        .cashier-detail-row dt { color: var(--text-muted); font-size: 12px; }
        .cashier-detail-row dd { margin: 0; font-weight: 600; color: var(--text-ink); }

        .cashier-stepper { display: flex; align-items: center; gap: 8px; }
        .cashier-step-btn {
            width: 42px; height: 42px; border-radius: 9px; border: 1px solid var(--border-line);
            display: grid; place-items: center; font-size: 18px; font-weight: 600; cursor: pointer;
            flex-shrink: 0;
        }
        .cashier-step-btn.is-minus { background: var(--bg-page); color: var(--text-ink); }
        .cashier-step-btn.is-plus { background: var(--brand-soft); color: var(--brand-navy); border-color: transparent; }
        .cashier-step-input {
            width: 68px !important; min-height: 42px !important; text-align: center;
            font-size: 15px !important; font-weight: 600; padding: 6px !important;
        }

        .cashier-progress-track { width: 100%; height: 9px; border-radius: 5px; background: var(--bg-page); overflow: hidden; }
        .cashier-progress-bar { height: 100%; border-radius: 5px; background: var(--brand-navy); }
        .cashier-footnote { margin: 18px 0 0; font-size: 12px; color: var(--text-muted); }

        /* Modal & Camera QR Scanner */
        .cashier-modal-backdrop {
            position: fixed; inset: 0; z-index: 60; background: rgba(15, 23, 42, .55);
            display: flex; align-items: center; justify-content: center; padding: 16px; overflow-y: auto;
        }
        .cashier-modal-card {
            width: 100%; max-width: 520px; background: #fff; border-radius: 18px;
            padding: 22px; box-shadow: 0 24px 60px rgba(15, 23, 42, .25); margin: auto;
        }
        .cashier-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .cashier-modal-title { margin: 0; font-size: 18px; font-weight: 600; color: var(--text-ink); }
        .cashier-modal-sub { margin: 3px 0 0; font-size: 12px; color: var(--text-muted); }
        .cashier-modal-close {
            width: 36px; height: 36px; border-radius: 999px; border: 0; background: #f1f5f9;
            color: var(--text-ink); font-size: 20px; line-height: 1; cursor: pointer; flex-shrink: 0;
        }
        .cashier-qr-modal-actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        .qr-camera-box { margin-top: 14px; border-radius: 14px; overflow: hidden; background: #0f172a; border: 1px solid #1e293b; color: #fff; }
        .qr-camera-stage { position: relative; width: 100%; aspect-ratio: 4 / 3; max-height: 320px; background: #020617; overflow: hidden; display: grid; place-items: center; }
        .qr-camera-stage video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .qr-frame-guide {
            position: absolute; width: min(55vw, 200px); height: min(55vw, 200px);
            border: 2px solid rgba(255, 255, 255, .55); border-radius: 14px;
            box-shadow: 0 0 0 9999px rgba(2, 6, 23, .45); pointer-events: none;
        }
        .qr-frame-guide::before {
            content: ""; position: absolute; left: 8px; right: 8px; height: 2px;
            background: linear-gradient(90deg, transparent, #34d399, transparent);
            animation: qrScanBeam 2s ease-in-out infinite;
        }
        @keyframes qrScanBeam {
            0%, 100% { top: 14px; opacity: .45; }
            50% { top: calc(100% - 16px); opacity: 1; }
        }
        .qr-camera-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 14px; background: #111827; font-size: 12px; }
        .qr-camera-controls { display: flex; gap: 8px; }
        .qr-cam-ctrl-btn {
            min-height: 34px; padding: 4px 10px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, .25);
            background: rgba(255, 255, 255, .1); color: #fff; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .qr-feedback { margin-top: 12px; padding: 10px 12px; border-radius: 10px; font-size: 12.5px; line-height: 1.4; }
        .qr-feedback.is-info { background: var(--brand-soft); color: var(--brand-navy); }
        .qr-feedback.is-error { background: var(--red-bg); color: var(--red-ink); border: 1px solid #fecaca; }
        .cashier-modal-form { margin-top: 16px; border-top: 1px solid var(--border-line); padding-top: 14px; }
        .qr-manual-row { display: flex; gap: 8px; }

        /* Clean Filter Toolbar & Search Input */
        .cashier-page-head-row { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .cashier-outlet-badge {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 14px; border-radius: 10px;
            background: #fff; border: 1px solid var(--border-line);
            font-size: 12.5px; font-weight: 600; color: var(--brand-navy); white-space: nowrap;
        }
        .cashier-outlet-badge::before {
            content: ""; width: 7px; height: 7px; border-radius: 999px; background: var(--brand-navy); flex-shrink: 0;
        }
        .cashier-filter-card {
            background: #fff; border: 1px solid var(--border-line); border-radius: 14px;
            padding: 12px 14px; margin-top: 16px;
        }
        .cashier-filter-row {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
        }
        .cashier-filter-row .cashier-input,
        .cashier-filter-row .cashier-select {
            min-height: 42px; background: var(--bg-page); font-weight: 500;
        }
        .cashier-filter-row .cashier-input:focus,
        .cashier-filter-row .cashier-select:focus { background: #fff; }
        .cashier-filter-date { display: flex; align-items: center; gap: 6px; flex: 0 1 155px; min-width: 140px; }
        .cashier-filter-select { flex: 0 1 140px; min-width: 128px; }
        .cashier-search-box { position: relative; flex: 1 1 200px; min-width: 180px; }
        .cashier-search-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #64748b; pointer-events: none;
        }
        .cashier-search-box .cashier-input { padding-left: 40px !important; }
        .cashier-filter-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

        /* === START: Row Hover Highlights (easily revertible) === */
        .cashier-table tbody tr:not(:has(td[colspan])) td {
            transition: background-color 0.14s ease, box-shadow 0.14s ease;
        }
        .cashier-table tbody tr:not(:has(td[colspan])):hover td {
            background-color: #e8eeff !important;
        }
        .cashier-table tbody tr:not(:has(td[colspan])):hover td:first-child {
            box-shadow: inset 3px 0 0 var(--brand-navy);
        }
        .cashier-mobile-item {
            transition: background-color 0.14s ease, border-color 0.14s ease;
        }
        .cashier-mobile-item:hover {
            background-color: #eef2ff;
            border-color: #c7d2fe;
        }
        /* === END: Row Hover Highlights === */

        .cashier-arrival-row { transition: background-color 0.14s ease; cursor: pointer; }
        .cashier-table tbody tr.is-selected-arrival td { background-color: #eef2ff !important; }
        .cashier-table tbody tr.is-selected-arrival td:first-child { box-shadow: inset 3px 0 0 var(--brand-navy); }
        .cashier-quick-picks { display: flex; flex-wrap: wrap; gap: 6px; }
        .cashier-quick-chip {
            padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border-line);
            background: #fff; font-size: 11.5px; font-weight: 600; color: var(--brand-navy);
            cursor: pointer; transition: background .14s, border-color .14s;
        }
        .cashier-quick-chip:hover { background: var(--brand-soft); border-color: #c7d2fe; }

        @media (min-width: 768px) {
            .cashier-app-grid {
                display: grid !important;
                min-height: 100vh;
                grid-template-columns: 244px minmax(0, 1fr) !important;
                width: 100%;
            }
            .cashier-app-main-col {
                min-width: 0;
                width: 100%;
            }
        }
        @media (max-width: 960px) {
            .cashier-metrics-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cashier-split-main { grid-template-columns: 1fr; }
            .cashier-filter-grid-5 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 767px) {
            .cashier-metrics-4 { grid-template-columns: 1fr; }
            .cashier-filter-grid-5 { grid-template-columns: 1fr; }
            .cashier-filter-date, .cashier-filter-select, .cashier-search-box, .cashier-filter-actions { flex: 1 1 100%; min-width: 0; }
            .cashier-filter-actions .cashier-btn { flex: 1; }
            .cashier-btn-pair { grid-template-columns: 1fr; }
            .cashier-table-wrap { display: none; }
            .cashier-mobile-list { display: grid; }
            .cashier-detail-row { grid-template-columns: 115px 1fr; }
            .cashier-card-pad { padding: 16px; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#f6f8fc] font-sans text-[#212b3b] antialiased">
    @php
        $outletQuery = request('outlet');
        $outletParams = is_scalar($outletQuery) && ctype_digit((string) $outletQuery) ? ['outlet' => $outletQuery] : [];
        $nav = [
            ['Ringkasan', route('cashier.dashboard', $outletParams), request()->routeIs('cashier.dashboard'), 'dashboard', null],
            ['Reservasi', route('cashier.reservations.index', $outletParams), request()->routeIs('cashier.reservations.*'), 'reservations', null],
            ['Riwayat reservasi', route('cashier.history.index', $outletParams), request()->routeIs('cashier.history.*', 'cashier.qr.*'), 'checkins', null],
            ['Permintaan pembatalan', route('cashier.cancellations.index', $outletParams), request()->routeIs('cashier.cancellations.*'), 'cancellations', 'cancellations'],
        ];
        $liveActiveChannel = match (true) {
            request()->routeIs('cashier.reservations.*') => 'reservations',
            request()->routeIs('cashier.history.*', 'cashier.qr.*') => 'checkins',
            request()->routeIs('cashier.cancellations.*') => 'cancellations',
            default => 'dashboard',
        };
        $liveAutoRefresh = ! request()->routeIs('cashier.cancellations.*');
        $userName = auth()->user()?->name ?? 'Kasir';
        $initials = mb_strtoupper(mb_substr($userName, 0, 2));
        $today = now('Asia/Jakarta')->locale(app()->getLocale())->translatedFormat('l, d F Y');
    @endphp

    {{-- Top bar (Mobile) --}}
    <div class="backend-mobile-topbar flex items-center justify-between bg-white px-4 py-3 md:hidden">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/dinea-logo.png') }}" alt="DINEA" style="height:44px;width:auto;">
        </div>
        <div class="flex items-center gap-1">
            @include('partials.lang-switcher')
            <button id="cashier-sidebar-toggle" type="button" aria-label="Buka navigasi" class="grid size-10 place-items-center rounded-md text-[#21265d] hover:bg-slate-100">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div class="cashier-app-grid md:grid md:min-h-screen md:grid-cols-[244px_minmax(0,1fr)]">
        <aside id="cashier-sidebar" class="backend-sidebar fixed inset-y-0 left-0 z-40 hidden w-[244px] flex-col overflow-y-auto bg-white px-[14px] pb-5 pt-0 shadow-xl md:sticky md:top-0 md:flex md:h-screen md:shadow-none">
            <div class="hidden items-center justify-center px-2.5 md:flex" style="height:70px;">
                <img src="{{ asset('images/dinea-logo.png') }}" alt="DINEA" style="height:58px;width:auto;" class="object-contain">
            </div>

            <p class="mt-2 px-2.5 text-[10px] font-semibold tracking-[0.035em] text-[#6e7a8f] uppercase">Reservation Management</p>
            <p class="mt-3 px-2.5 text-xs font-semibold tracking-wide text-[#6e7a8f] uppercase">Kasir</p>

            <nav class="mt-2 flex flex-col gap-1.5" aria-label="Navigasi Kasir">
                @foreach ($nav as [$label, $href, $active, $liveChannel, $livePending])
                    <a href="{{ $href }}" data-live-nav="{{ $liveChannel }}"
                       class="flex min-h-10 items-center gap-2.5 rounded-[10px] px-3 py-2 text-[13.5px] transition-colors
                              {{ $active ? 'bg-[#e8edff] font-semibold text-[#21265d]' : 'text-[#6e7a8f] hover:bg-[#f6f8fc] hover:text-[#21265d]' }}">
                        <span class="w-3.5 shrink-0 text-xs leading-none">{{ $active ? '●' : '○' }}</span>
                        <span class="leading-tight">{{ $label }}</span>
                        @if ($livePending)
                            <span class="live-nav-badge" data-live-pending="{{ $livePending }}" hidden></span>
                        @endif
                        <span class="live-nav-dot" data-live-dot="{{ $liveChannel }}" hidden title="Data baru masuk"></span>
                    </a>
                @endforeach
            </nav>

            @include('partials.backend-sidebar-outlet')
        </aside>

        <div class="cashier-app-main-col min-w-0">
            <header class="backend-topbar hidden h-[70px] items-center justify-end gap-5 bg-white px-10 md:flex">
                <time class="text-sm text-slate-500">{{ $today }}</time>
                @include('partials.lang-switcher')
                <form method="POST" action="{{ route('cashier.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Keluar
                    </button>
                </form>
                <span translate="no" class="grid size-10 place-items-center rounded-full bg-[#1f1d5c] text-xs font-bold text-white" title="{{ $userName }}">
                    {{ $initials }}
                </span>
            </header>

            <main class="w-full px-4 py-6 md:px-10 md:py-8">
                @if (!auth()->user()->hasRole('admin') && !app(\App\Services\OutletAccess::class)->outletIds(auth()->user()))
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                        Akun berhasil masuk, tetapi belum ditetapkan ke outlet. Minta Admin memilih outlet pada menu Pengguna.
                    </div>
                @endif
                @if (session('checkin_scan_success') || session('success'))
                    <div class="mb-5 rounded-xl bg-[#e3f5eb] px-4 py-3 text-sm font-medium text-[#147a4f]" role="status">
                        {{ session('checkin_scan_success') ?? session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-[#fff0ef] px-4 py-3 text-sm font-medium text-[#ab3333]" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('cashier.partials.qr-modal')

    <script>
        document.getElementById('cashier-sidebar-toggle')?.addEventListener('click', function () {
            var sidebar = document.getElementById('cashier-sidebar');
            sidebar?.classList.toggle('hidden');
            sidebar?.classList.toggle('flex');
        });

        (() => {
            const switchers = document.querySelectorAll('[data-lang-switcher]');
            const closeAll = () => switchers.forEach(s => {
                s.querySelector('[data-lang-menu]')?.classList.add('hidden');
                s.querySelector('[data-lang-toggle]')?.setAttribute('aria-expanded', 'false');
            });
            switchers.forEach(s => {
                const toggle = s.querySelector('[data-lang-toggle]');
                const menu = s.querySelector('[data-lang-menu]');
                toggle?.addEventListener('click', e => {
                    e.stopPropagation();
                    const open = toggle.getAttribute('aria-expanded') !== 'true';
                    closeAll();
                    menu?.classList.toggle('hidden', !open);
                    toggle.setAttribute('aria-expanded', String(open));
                });
            });
            document.addEventListener('click', closeAll);
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });
        })();

        // Stepper +/- and Quick-Pick guest chips (event-delegated so live DOM refresh preserves behavior)
        (() => {
            document.addEventListener('click', (e) => {
                const decBtn = e.target.closest?.('[data-step-dec]');
                const incBtn = e.target.closest?.('[data-step-inc]');
                if (decBtn || incBtn) {
                    const wrap = (decBtn || incBtn).closest('[data-stepper]');
                    const input = wrap?.querySelector('input[type="number"]');
                    if (!input) return;
                    const min = Number(input.min || 1);
                    const max = Number(input.max || 50);
                    const next = Number(input.value || min) + (incBtn ? 1 : -1);
                    input.value = String(Math.min(max, Math.max(min, next)));
                    return;
                }

                const quickBtn = e.target.closest?.('[data-checkin-qty]');
                if (quickBtn) {
                    const form = quickBtn.closest('form');
                    const input = form?.querySelector('input[name="guest_count_arrived"]');
                    if (!input) return;
                    const min = Number(input.min || 1);
                    const max = Number(input.max || 50);
                    const val = Number(quickBtn.getAttribute('data-checkin-qty') || min);
                    input.value = String(Math.min(max, Math.max(min, val)));
                }
            });
        })();

        // Interactive Kedatangan berikutnya -> Catat kedatangan sync on Cashier Dashboard
        (() => {
            const esc = (str) => String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const getCsrfToken = () => {
                const existing = document.querySelector('#qc-body input[name="_token"]')?.value
                    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '';
                return existing;
            };

            const selectArrivalRow = (row) => {
                if (!row || !row.dataset.reservation) return;
                let data;
                try { data = JSON.parse(row.dataset.reservation); }
                catch { return; }

                document.querySelectorAll('[data-arrival-row]').forEach(el => {
                    el.classList.toggle('is-selected-arrival', el === row);
                });

                const card = document.getElementById('cashier-quick-checkin-card');
                if (!card) return;

                const isEn = document.documentElement.lang === 'en';
                const sub = document.getElementById('qc-sub');
                const tableLine = document.getElementById('qc-table-line');
                const notesWrap = document.getElementById('qc-notes-wrap');
                const notesText = document.getElementById('qc-notes-text');
                const body = document.getElementById('qc-body');
                const cancelLink = document.getElementById('qc-cancel-link');

                if (sub) {
                    sub.textContent = `${data.customer_name} • ${data.code} (${data.arrived_count}/${data.guest_count})`;
                }
                if (tableLine) {
                    tableLine.textContent = `${data.table_text} • ${data.schedule}`;
                }
                if (notesWrap && notesText) {
                    if (data.notes) {
                        notesText.textContent = data.notes;
                        notesWrap.hidden = false;
                    } else {
                        notesText.textContent = '';
                        notesWrap.hidden = true;
                    }
                }
                if (cancelLink && data.cancel_url) {
                    cancelLink.href = data.cancel_url;
                }

                if (body) {
                    const csrf = esc(getCsrfToken());
                    if (data.can_checkin) {
                        const rem = Number(data.remaining || 1);
                        const quickTwo = rem >= 2
                            ? `<button type="button" data-checkin-qty="2" class="cashier-quick-chip">+2</button>`
                            : '';
                        body.innerHTML = `
                            <form method="POST" action="${esc(data.checkin_url)}" class="mt-4">
                                <input type="hidden" name="_token" value="${csrf}">
                                <div class="flex flex-wrap items-end justify-between gap-3">
                                    <div>
                                        <p style="margin:0;font-size:13px;color:var(--text-muted);">${isEn ? 'Guests arrived' : 'Tamu hadir'}</p>
                                        <p style="margin:2px 0 0;font-size:27px;line-height:1.15;font-weight:600;color:var(--text-ink);">${Number(data.arrived_count || 0)}</p>
                                    </div>
                                    <div class="flex items-center gap-2" data-stepper>
                                        <button type="button" data-step-dec aria-label="${isEn ? 'Decrease' : 'Kurangi'}" class="cashier-step-btn is-minus" style="width:38px;height:38px;">−</button>
                                        <input type="number" name="guest_count_arrived" min="1" max="${rem}" value="${rem}" required class="cashier-input cashier-step-input" style="width:52px !important;min-height:38px !important;">
                                        <button type="button" data-step-inc aria-label="${isEn ? 'Increase' : 'Tambah'}" class="cashier-step-btn is-plus" style="width:38px;height:38px;">+</button>
                                        <button type="submit" class="cashier-btn cashier-btn-primary" style="min-height:38px;padding:0 16px;">${isEn ? 'Save' : 'Simpan'}</button>
                                    </div>
                                </div>
                                <div class="cashier-quick-picks mt-2.5">
                                    <button type="button" data-checkin-qty="1" class="cashier-quick-chip">+1</button>
                                    ${quickTwo}
                                    <button type="button" data-checkin-qty="${rem}" class="cashier-quick-chip">${isEn ? 'All' : 'Semua'} (${rem})</button>
                                </div>
                            </form>
                        `;
                    } else if (data.status === 'checked_in') {
                        body.innerHTML = `
                            <div class="mt-4 space-y-2.5">
                                <span class="cashier-pill is-green">${isEn ? 'All guests checked in' : 'Seluruh tamu sudah check-in'} (${Number(data.arrived_count)}/${Number(data.guest_count)})</span>
                                <form method="POST" action="${esc(data.complete_url)}">
                                    <input type="hidden" name="_token" value="${csrf}">
                                    <button type="submit" class="cashier-btn cashier-btn-outline cashier-btn-sm" style="width:100%;">
                                        ${isEn ? 'Mark visit completed ✓' : 'Tandai kunjungan selesai ✓'}
                                    </button>
                                </form>
                            </div>
                        `;
                    } else if (data.status === 'completed') {
                        body.innerHTML = `
                            <div class="mt-4">
                                <span class="cashier-pill is-green">${isEn ? 'Visit completed' : 'Kunjungan selesai'} (${Number(data.arrived_count)}/${Number(data.guest_count)})</span>
                            </div>
                        `;
                    } else {
                        body.innerHTML = `
                            <p class="cashier-card-sub mt-3">${isEn ? 'Waiting for Finance verification or not eligible for check-in yet.' : 'Untuk reservasi rombongan atau menunggu verifikasi Finance.'}</p>
                        `;
                    }
                }

                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('reservation', String(data.id));
                    window.history.replaceState(null, '', url.toString());
                } catch { /* ignore history error */ }
            };

            document.addEventListener('click', (e) => {
                if (e.target.closest?.('[data-row-ignore-select], a, button, form')) return;
                const row = e.target.closest?.('[data-arrival-row]');
                if (row) selectArrivalRow(row);
            });

            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                const row = e.target.closest?.('[data-arrival-row]');
                if (!row || e.target.closest?.('a, button')) return;
                e.preventDefault();
                selectArrivalRow(row);
            });
        })();

        // Modal QR Scanner (Tablet & HP Live Camera + Photo Capture + Code Input)
        (() => {
            const modal = document.getElementById('cashier-qr-modal');
            if (!modal) return;
            const closeBtn = document.getElementById('qr-modal-close');
            const startBtn = document.getElementById('qr-modal-start-cam');
            const stopBtn = document.getElementById('qr-modal-stop-cam');
            const switchBtn = document.getElementById('qr-modal-switch-cam');
            const imageBtn = document.getElementById('qr-modal-image-btn');
            const fileInput = document.getElementById('qr-modal-file-input');
            const cameraBox = document.getElementById('qr-modal-camera-box');
            const video = document.getElementById('qr-modal-video');
            const statusText = document.getElementById('qr-modal-camera-status');
            const feedback = document.getElementById('qr-modal-feedback');
            const tokenInput = document.getElementById('qr-modal-token-input');
            const scanForm = document.getElementById('cashier-modal-scan-form');
            const pasteBtn = document.getElementById('qr-modal-paste');

            let mediaStream = null;
            let rafId = null;
            let lastScanAt = 0;
            let facingMode = 'environment';
            let barcodeDetector = null;
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d', { willReadFrequently: true });

            if ('BarcodeDetector' in window) {
                try { barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] }); }
                catch { barcodeDetector = null; }
            }

            const showFeedback = (msg, type = 'info') => {
                if (!feedback) return;
                feedback.textContent = msg;
                feedback.className = `qr-feedback ${type === 'error' ? 'is-error' : 'is-info'}`;
                feedback.hidden = false;
            };
            const hideFeedback = () => { if (feedback) feedback.hidden = true; };

            const stopCamera = () => {
                if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
                if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); mediaStream = null; }
                if (video) video.srcObject = null;
                if (cameraBox) cameraBox.hidden = true;
            };

            const closeModal = () => {
                stopCamera();
                modal.hidden = true;
            };

            const ensureDecoderAvailable = () => {
                if (barcodeDetector || typeof window.jsQR === 'function') return true;
                showFeedback('Decoder QR belum termuat. Gunakan Foto/Gambar jika offline atau masukkan kode secara manual.', 'error');
                return false;
            };

            const submitValue = (raw) => {
                const value = String(raw || '').trim();
                if (!value) return false;
                navigator.vibrate?.(120);
                stopCamera();
                showFeedback('QR terbaca! Membuka detail reservasi...', 'info');
                if (tokenInput && scanForm) {
                    tokenInput.value = value;
                    scanForm.requestSubmit ? scanForm.requestSubmit() : scanForm.submit();
                }
                return true;
            };

            const decodeCanvas = async (sourceCanvas) => {
                if (barcodeDetector) {
                    try {
                        const codes = await barcodeDetector.detect(sourceCanvas);
                        if (codes.length && codes[0].rawValue) return codes[0].rawValue;
                    } catch { /* fallback jsQR */ }
                }
                if (typeof window.jsQR === 'function') {
                    const c = sourceCanvas.getContext('2d', { willReadFrequently: true });
                    const imgData = c.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height);
                    const result = window.jsQR(imgData.data, sourceCanvas.width, sourceCanvas.height, {
                        inversionAttempts: 'attemptBoth',
                    });
                    if (result && result.data) return result.data;
                }
                return null;
            };

            const scanVideoLoop = async (ts) => {
                if (!mediaStream || !video || video.paused || video.ended) return;
                if (ts - lastScanAt >= 180 && video.readyState >= 2 && video.videoWidth > 0) {
                    lastScanAt = ts;
                    const maxDim = 640;
                    const scale = Math.min(1, maxDim / Math.max(video.videoWidth, video.videoHeight));
                    canvas.width = Math.round(video.videoWidth * scale);
                    canvas.height = Math.round(video.videoHeight * scale);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const decoded = await decodeCanvas(canvas);
                    if (decoded && submitValue(decoded)) return;
                }
                rafId = requestAnimationFrame(scanVideoLoop);
            };

            const startCamera = async () => {
                hideFeedback();
                if (!ensureDecoderAvailable()) return;
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showFeedback('Kamera live diblokir pada koneksi HTTP non-localhost. Gunakan tombol "Foto / Gambar QR" untuk memotret QR langsung dengan kamera HP/tablet.', 'error');
                    return;
                }
                stopCamera();
                if (cameraBox) cameraBox.hidden = false;
                if (statusText) statusText.textContent = 'Mengaktifkan kamera...';

                try {
                    mediaStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: facingMode }, width: { ideal: 1280 }, height: { ideal: 720 } },
                        audio: false,
                    });
                    video.srcObject = mediaStream;
                    await video.play();
                    if (statusText) statusText.textContent = 'Arahkan kamera ke QR reservasi tamu...';
                    rafId = requestAnimationFrame(scanVideoLoop);
                } catch {
                    stopCamera();
                    showFeedback('Tidak dapat membuka kamera live. Pastikan izin kamera diberikan atau gunakan tombol "Foto / Gambar QR".', 'error');
                }
            };

            document.addEventListener('click', (e) => {
                if (!e.target.closest?.('[data-open-qr-scanner]')) return;
                hideFeedback();
                modal.hidden = false;
                startCamera();
            });

            closeBtn?.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

            startBtn?.addEventListener('click', startCamera);
            stopBtn?.addEventListener('click', stopCamera);
            switchBtn?.addEventListener('click', () => {
                facingMode = facingMode === 'environment' ? 'user' : 'environment';
                startCamera();
            });

            imageBtn?.addEventListener('click', () => fileInput?.click());
            fileInput?.addEventListener('change', async () => {
                const file = fileInput.files?.[0];
                fileInput.value = '';
                if (!file) return;
                hideFeedback();
                if (!ensureDecoderAvailable()) return;
                showFeedback('Membaca QR dari gambar...', 'info');

                const img = new Image();
                const url = URL.createObjectURL(file);
                try {
                    await new Promise((resolve, reject) => {
                        img.onload = resolve; img.onerror = reject; img.src = url;
                    });
                    const maxDim = 1200;
                    const scale = Math.min(1, maxDim / Math.max(img.naturalWidth, img.naturalHeight));
                    canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.imageSmoothingEnabled = false;
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const decoded = await decodeCanvas(canvas);
                    if (!decoded) {
                        showFeedback('QR code tidak terbaca dari foto. Pastikan QR terlihat jelas dan tidak terpotong.', 'error');
                        return;
                    }
                    submitValue(decoded);
                } catch {
                    showFeedback('Gagal memproses file gambar.', 'error');
                } finally {
                    URL.revokeObjectURL(url);
                }
            });

            pasteBtn?.addEventListener('click', async () => {
                try {
                    const text = (await navigator.clipboard.readText()).trim();
                    if (tokenInput && text) { tokenInput.value = text; tokenInput.focus(); }
                } catch { tokenInput?.focus(); }
            });

            window.addEventListener('beforeunload', stopCamera);
        })();
    </script>
    @include('partials.backend-live-sync', [
        'liveRole' => 'cashier',
        'liveEndpoint' => route('cashier.live-status'),
        'liveActiveChannel' => $liveActiveChannel,
        'liveAutoRefresh' => $liveAutoRefresh,
    ])
    @stack('scripts')
</body>
</html>
