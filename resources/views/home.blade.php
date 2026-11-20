@extends('layouts.app')

@section('content')
    @php
        // Daftar gambar slider hero (folder: public/images/hero/)
        $heroSlides = ['FE1.jpg', 'FE2.jpg', 'FE3.jpg', 'FE4.jpg', 'IK1.jpg', 'IK2.jpg', 'IK3.jpg'];

        // Data kartu outlet
        $outlets = [
            [
                'slug' => 'novotel',
                'name' => 'NOVOTEL',
                'sub'  => 'Food Exchange',
                'desc' => __('ui.novotel_desc'),
                'btn'  => __('ui.novotel_btn'),
                'img'  => 'images/hero/FE1.jpg',
                'card' => 'before:bg-[linear-gradient(180deg,rgba(120,110,200,.55),rgba(60,40,130,.85))] hover:shadow-[0_18px_36px_rgba(60,40,130,.4)] focus-visible:shadow-[0_18px_36px_rgba(60,40,130,.4)]',
                'btnc' => 'text-novotel-dark group-hover:bg-novotel-dark group-hover:text-white group-focus-visible:bg-novotel-dark group-focus-visible:text-white',
            ],
            [
                'slug' => 'ibis',
                'name' => 'Ibis',
                'sub'  => 'Ibis Kitchen',
                'desc' => __('ui.ibis_desc'),
                'btn'  => __('ui.ibis_btn'),
                'img'  => 'images/hero/IK2.jpg',
                'card' => 'before:bg-[linear-gradient(180deg,rgba(255,120,130,.45),rgba(210,30,45,.85))] hover:shadow-[0_18px_36px_rgba(210,30,45,.4)] focus-visible:shadow-[0_18px_36px_rgba(210,30,45,.4)]',
                'btnc' => 'text-ibis group-hover:bg-ibis-dark group-hover:text-white group-focus-visible:bg-ibis-dark group-focus-visible:text-white',
            ],
        ];
    @endphp

    @push('styles')
        <link rel="preload" as="image" href="{{ asset('images/hero/' . $heroSlides[0]) }}" fetchpriority="high">
        <style>
            /* Desain asli Hero: tidak tertarik di desktop & proporsional di semua ukuran device */
            #hero {
                width: 100%;
                aspect-ratio: 4 / 3;
                min-height: 240px !important;
                max-height: 520px;
                padding: clamp(20px, 5vw, 48px) clamp(16px, 4vw, 32px) clamp(34px, 6vw, 48px);
            }
            @media (min-width: 1024px) {
                #hero {
                    aspect-ratio: auto;
                    min-height: clamp(460px, 65vh, 660px) !important;
                    max-height: 680px;
                }
            }
            #hero .hero-track > div {
                background-size: cover !important;
                background-position: center center !important;
                background-repeat: no-repeat !important;
            }
            #hero h1 {
                font-size: clamp(1.55rem, 5.2vw, 4.9rem);
                line-height: 1.08;
                margin-bottom: clamp(8px, 1.5vw, 16px);
            }
            #hero p {
                font-size: clamp(0.8125rem, 1.65vw, 1.35rem);
                line-height: 1.42;
                margin-bottom: clamp(14px, 2.5vw, 32px);
            }
            #hero #btn-reservasi {
                padding: clamp(9px, 1.4vw, 15px) clamp(18px, 2.8vw, 36px);
                font-size: clamp(0.875rem, 1.35vw, 1.125rem);
            }

            /* Desain asli Card Outlet: selalu kiri-kanan proporsional di semua device & gambar tidak tertarik */
            .outlet-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: clamp(10px, 2.8vw, 24px);
                align-items: stretch;
            }
            @media (min-width: 768px) {
                .outlet-row {
                    grid-template-columns: minmax(0, 1fr) 1px minmax(0, 1fr);
                    gap: clamp(20px, 4vw, 64px);
                }
            }
            #pilih-restoran .outlet-card {
                min-height: clamp(210px, 34vw, 360px);
                border-radius: clamp(14px, 1.8vw, 22px);
            }
            #pilih-restoran .outlet-card::after {
                background-size: cover !important;
                background-position: center !important;
                background-repeat: no-repeat !important;
            }
            #pilih-restoran .outlet-card > div {
                padding: clamp(12px, 2.8vw, 36px);
            }
            #pilih-restoran .outlet-card h3 {
                font-size: clamp(1.2rem, 3.6vw, 3rem);
                line-height: 1.1;
            }
            #pilih-restoran .outlet-card > div > span:first-of-type {
                font-size: clamp(0.85rem, 1.9vw, 1.5rem);
                margin-bottom: clamp(6px, 1.5vw, 20px);
            }
            #pilih-restoran .outlet-card p {
                font-size: clamp(0.72rem, 1.45vw, 1rem);
                line-height: 1.45;
                max-width: 100%;
                margin-bottom: clamp(10px, 2vw, 24px);
            }
            #pilih-restoran .outlet-card > div > span:last-of-type {
                font-size: clamp(0.75rem, 1.5vw, 1.25rem);
                padding: clamp(6px, 1vw, 10px) clamp(10px, 1.8vw, 20px);
                gap: clamp(6px, 1.2vw, 12px);
            }
            .ev-section {
                background: #ffffff;
                padding: 48px 0 52px;
            }
            @media (min-width: 768px) {
                .ev-section { padding: 64px 0 72px; }
            }
            .ev-wrap {
                max-width: 80rem;
                margin: 0 auto;
                padding: 0 16px;
            }
            .ev-carousel {
                position: relative;
                width: 100%;
            }
            .ev-track {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: stretch;
                gap: 20px;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding: 6px 2px 14px;
                cursor: grab;
            }
            .ev-track.ev-dragging {
                cursor: grabbing;
                scroll-behavior: auto;
                scroll-snap-type: none;
            }
            .ev-track::-webkit-scrollbar { display: none; }
            .ev-track.ev-centered { justify-content: center; }

            .ev-card {
                box-sizing: border-box;
                flex: 0 0 86%;
                max-width: 360px;
                min-width: 0;
                scroll-snap-align: start;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border-radius: 16px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            .ev-track.ev-single .ev-card {
                flex: 0 0 100%;
                max-width: 440px;
            }
            @media (min-width: 640px) {
                .ev-card {
                    flex: 0 0 calc((100% - 20px) / 2);
                    max-width: calc((100% - 20px) / 2);
                }
                .ev-track.ev-single .ev-card {
                    flex: 0 0 440px;
                    max-width: 440px;
                }
            }
            @media (min-width: 1024px) {
                .ev-card {
                    flex: 0 0 calc((100% - 40px) / 3);
                    max-width: calc((100% - 40px) / 3);
                }
                .ev-track.ev-single .ev-card {
                    flex: 0 0 440px;
                    max-width: 440px;
                }
            }
            .ev-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 26px rgba(36, 33, 95, 0.1);
            }

            .ev-media {
                position: relative;
                width: 100%;
                height: 220px;
                background: linear-gradient(180deg, #f4f6fb 0%, #eaedf7 100%);
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px;
                overflow: hidden;
                flex-shrink: 0;
            }
            @media (min-width: 640px) {
                .ev-media { height: 240px; }
            }
            .ev-media img {
                display: block;
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
                object-fit: contain;
                object-position: center;
                user-select: none;
                -webkit-user-drag: none;
            }
            .ev-media-empty {
                width: 100%;
                height: 100%;
                display: grid;
                place-items: center;
                border-radius: 10px;
                background: #eef2ff;
                color: #312e81;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.05em;
            }

            .ev-body {
                display: flex;
                flex: 1 1 auto;
                flex-direction: column;
                padding: 18px 20px 20px;
            }
            .ev-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 6px;
            }
            .ev-tag {
                font-size: 0.75rem;
                font-weight: 600;
                color: #3730a3;
            }
            .ev-price {
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                background: #eef2ff;
                padding: 2px 10px;
                font-size: 0.7rem;
                font-weight: 600;
                color: #24215f;
            }
            .ev-title {
                margin-top: 8px;
                font-size: 1.0625rem;
                font-weight: 600;
                line-height: 1.35;
                color: #0f172a;
            }
            .ev-desc {
                margin-top: 8px;
                font-size: 0.875rem;
                line-height: 1.55;
                color: #475569;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .ev-footer {
                margin-top: auto;
                padding-top: 14px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                border-top: 1px solid #f1f5f9;
            }
            .ev-dates {
                font-size: 0.75rem;
                color: #64748b;
            }
            .ev-cta {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 8px;
                background: #24215f;
                padding: 8px 14px;
                font-size: 0.75rem;
                font-weight: 600;
                color: #ffffff;
                text-decoration: none;
                transition: background-color 0.2s ease, transform 0.2s ease;
                margin-left: auto;
            }
            .ev-cta:hover {
                background: #181644;
                transform: translateY(-1px);
            }

            .ev-nav-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                z-index: 10;
                width: 40px;
                height: 40px;
                border-radius: 9999px;
                border: 1px solid #d5dce8;
                background: rgba(255, 255, 255, 0.96);
                color: #24215f;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.14);
                transition: background-color 0.2s ease, color 0.2s ease, opacity 0.2s ease, transform 0.2s ease;
            }
            .ev-nav-btn:hover:not(:disabled) {
                background: #24215f;
                color: #ffffff;
                border-color: #24215f;
            }
            .ev-nav-btn:disabled {
                opacity: 0.35;
                cursor: not-allowed;
            }
            .ev-nav-prev { left: 6px; }
            .ev-nav-next { right: 6px; }
            @media (min-width: 1280px) {
                .ev-nav-prev { left: -10px; }
                .ev-nav-next { right: -10px; }
            }
            .ev-nav-btn[hidden], .ev-dots[hidden] { display: none !important; }

            .ev-dots {
                margin-top: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            .ev-dots button {
                width: 9px;
                height: 9px;
                padding: 0;
                border: 0;
                border-radius: 9999px;
                background: #cbd5e1;
                cursor: pointer;
                transition: width 0.25s ease, background-color 0.25s ease;
            }
            .ev-dots button:hover { background: #94a3b8; }
            .ev-dots button.active {
                width: 26px;
                background: #24215f;
            }
        </style>
    @endpush

    {{-- HERO --}}
    <section id="hero" data-interval="5000"
             class="relative grid min-h-[380px] place-items-center overflow-hidden bg-neutral-400 px-5 py-12 text-center text-white sm:min-h-[460px] md:min-h-[clamp(480px,70vh,680px)] md:py-14">

        <div class="hero-track absolute inset-0 flex will-change-transform">
            @foreach ($heroSlides as $img)
                @php $url = asset('images/hero/' . $img); @endphp
                <div class="flex-none basis-full bg-neutral-400 bg-cover bg-center"
                     @if ($loop->first) style="background-image: url('{{ $url }}')" @else data-bg="{{ $url }}" @endif></div>
            @endforeach
        </div>

        <div class="absolute inset-0 z-[1] bg-black/30"></div>

        <div class="relative z-[2] max-w-[900px] [text-shadow:0_2px_12px_rgba(0,0,0,.3)]">
            <h1 class="mb-3 text-[2.4rem] leading-[1.1] font-bold sm:text-5xl md:mb-4 md:text-6xl lg:text-[5.2rem]">
                Good Food<br>Great Moments
            </h1>
            <p class="mb-6 text-sm leading-snug sm:text-base md:mb-9 md:text-xl lg:text-[1.4rem]">
                {{ __('ui.hero_text_1') }}<br class="max-md:hidden">
                {{ __('ui.hero_text_2') }}
            </p>
            <button type="button" id="btn-reservasi" data-target="#pilih-restoran"
                    class="group inline-flex cursor-pointer items-center gap-2 rounded-full bg-white px-6 py-3 text-base font-semibold text-neutral-900 shadow-md transition [text-shadow:none] hover:-translate-y-0.5 hover:shadow-xl md:gap-3 md:px-9 md:py-4 md:text-lg">
                {{ __('ui.reserve_now') }}
                <svg class="size-4 transition-transform group-hover:translate-y-[3px] md:size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
            </button>
        </div>

        <div class="hero-dots absolute inset-x-0 bottom-4 z-[3] flex justify-center gap-2.5 md:bottom-5" aria-label="{{ __('ui.choose_image') }}"></div>
    </section>

    {{-- EVENT & PROMOSI (CAROUSEL DI ATAS OUTLET) --}}
    @if (($activeContents ?? collect())->isNotEmpty())
        <section id="event-dan-promosi" class="ev-section">
            <div class="ev-wrap">
                <div class="mb-8 text-center md:mb-10">
                    <h2 class="text-2xl font-semibold text-slate-900 md:text-4xl">{{ __('ui.public_events_title') }}</h2>
                    <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500 md:text-base">{{ __('ui.public_events_sub') }}</p>
                </div>

                <div id="event-carousel" class="ev-carousel">
                    <button type="button" data-ev-prev hidden aria-label="Event sebelumnya" class="ev-nav-btn ev-nav-prev">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>

                    <div class="ev-track {{ $activeContents->count() === 1 ? 'ev-single' : '' }}" role="region" aria-label="{{ __('ui.public_events_title') }}">
                        @foreach ($activeContents as $content)
                            <article class="ev-card">
                                <div class="ev-media">
                                    @if ($content->image_url)
                                        <img src="{{ $content->image_url }}" alt="{{ $content->title }}" loading="lazy">
                                    @else
                                        <div class="ev-media-empty">{{ __('ui.public_events_image') }}</div>
                                    @endif
                                </div>
                                <div class="ev-body">
                                    <div class="ev-meta">
                                        <p class="ev-tag">{{ __('ui.public_content_' . $content->content_type) }} · {{ $content->outlet?->name }}</p>
                                        @if ((float) $content->price_per_pax > 0)
                                            <span class="ev-price">Rp{{ number_format((float) $content->price_per_pax, 0, ',', '.') }}{{ $content->content_type === 'event' ? '/pax' : '' }}</span>
                                        @endif
                                    </div>
                                    <h3 class="ev-title">{{ $content->title }}</h3>
                                    <p class="ev-desc">{{ $content->description }}</p>

                                    <div class="ev-footer">
                                        @if ($content->starts_on || $content->ends_on)
                                            <p class="ev-dates">
                                                {{ $content->starts_on?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '' }}
                                                @if ($content->ends_on)– {{ $content->ends_on->locale(app()->getLocale())->translatedFormat('d M Y') }}@endif
                                            </p>
                                        @endif

                                        @if ($content->outlet)
                                            <a href="{{ $content->content_type === 'event'
                                                ? route('reservasi.outlet', ['outlet' => $content->outlet->code, 'event_id' => $content->outlet_contents_id])
                                                : route('reservasi.outlet', ['outlet' => $content->outlet->code, 'promo_id' => $content->outlet_contents_id]) }}"
                                               class="ev-cta">
                                                {{ $content->content_type === 'event' ? (app()->getLocale() === 'en' ? 'Reserve Event' : 'Reservasi Event') : __('ui.reserve_now') }}
                                                <span aria-hidden="true">→</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <button type="button" data-ev-next hidden aria-label="Event berikutnya" class="ev-nav-btn ev-nav-next">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>

                    <div class="ev-dots" hidden aria-label="Navigasi carousel event"></div>
                </div>
            </div>
        </section>
    @endif

    {{-- PILIH OUTLET --}}
    <section id="pilih-restoran" class="outlets bg-white pt-14 pb-16 text-center md:min-h-[calc(100vh-5rem)] md:pt-[5.5rem] md:pb-28" style="background:#ffffff;">
        <div class="mx-auto w-full max-w-7xl px-4">
            <h2 class="mb-3 text-[1.75rem] font-semibold sm:text-3xl md:mb-5 md:text-5xl">{{ __('ui.outlets_title') }}</h2>
            <p class="mx-auto mb-8 max-w-md text-sm sm:text-base md:mb-[4.5rem] md:max-w-none md:text-xl">
                {{ __('ui.outlets_sub') }}
            </p>

            <div class="outlet-row mx-auto w-full max-w-[1200px]">
                @foreach ($outlets as $o)
                    @php
                        $open = (bool) $outletOpen->get($o['slug'], false);
                        $tag  = $open ? 'a' : 'div';
                    @endphp

                    <{{ $tag }}
                        @if ($open) href="{{ route('reservasi.outlet', $o['slug']) }}" @else aria-disabled="true" @endif
                        style="--card-img: url('{{ asset($o['img']) }}')"
                        class="outlet-card group relative isolate block min-h-[230px] overflow-hidden rounded-2xl bg-neutral-700 text-left text-white transition duration-300 hover:-translate-y-2 focus-visible:-translate-y-2 focus-visible:outline-none sm:min-h-[280px] md:min-h-[360px] md:rounded-[22px]
                               before:absolute before:inset-0 before:-z-10 before:transition-opacity before:duration-300 hover:before:opacity-90 focus-visible:before:opacity-90
                               after:absolute after:inset-0 after:-z-20 after:bg-[image:var(--card-img)] after:bg-cover after:bg-center after:transition-transform after:duration-700 hover:after:scale-110 focus-visible:after:scale-110
                               {{ $o['card'] }} {{ $open ? '' : 'pointer-events-none grayscale opacity-70' }}">
                        <div class="flex h-full flex-col p-5 sm:p-7 md:p-9">
                            <h3 class="text-3xl leading-[1.1] font-medium tracking-[.06em] sm:text-4xl md:text-5xl">{{ $o['name'] }}</h3>
                            <span class="mb-3 block text-lg font-light md:mb-5 md:text-2xl">{{ $o['sub'] }}</span>
                            <p class="mb-5 max-w-[92%] text-sm leading-relaxed md:mb-6 md:text-base">{{ $o['desc'] }}</p>
                            <span class="mt-auto inline-flex gap-3 self-start rounded-md bg-white px-4 py-2 text-base transition-all duration-200 group-hover:gap-5 group-focus-visible:gap-5 md:px-5 md:py-2.5 md:text-xl {{ $o['btnc'] }}">
                                {{ $open ? $o['btn'] : __('ui.outlet_closed') }}
                                @if ($open)<i class="not-italic">&gt;</i>@endif
                            </span>
                        </div>
                    </{{ $tag }}>

                    @if (! $loop->last)
                        {{-- garis pemisah hanya di desktop --}}
                        <div class="hidden bg-[#8d9397] md:block" aria-hidden="true"></div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endsection
