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

    {{-- PILIH OUTLET --}}
        {{-- PILIH OUTLET --}}
    <section id="pilih-restoran" class="outlets pt-14 pb-16 text-center md:min-h-[calc(100vh-5rem)] md:pt-[5.5rem] md:pb-28">
        <div class="mx-auto w-full max-w-7xl px-4">
            <h2 class="mb-3 text-[1.75rem] font-semibold sm:text-3xl md:mb-5 md:text-5xl">{{ __('ui.outlets_title') }}</h2>
            <p class="mx-auto mb-8 max-w-md text-sm sm:text-base md:mb-[4.5rem] md:max-w-none md:text-xl">
                {{ __('ui.outlets_sub') }}
            </p>

            <div class="mx-auto grid w-full max-w-[1200px] gap-5 md:grid-cols-[1fr_1px_1fr] md:gap-16">
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

    @if (($activeContents ?? collect())->isNotEmpty())
        <section class="border-t border-slate-200 bg-white py-14 md:py-20">
            <div class="mx-auto max-w-7xl px-4">
                <div class="mb-8 text-center md:mb-10">
                    <h2 class="text-2xl font-semibold text-slate-900 md:text-4xl">{{ __('ui.public_events_title') }}</h2>
                    <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500 md:text-base">{{ __('ui.public_events_sub') }}</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($activeContents as $content)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            @if ($content->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content->image_path) }}" alt="{{ $content->title }}" class="h-48 w-full object-cover">
                            @else
                                <div class="grid h-48 place-items-center bg-indigo-50 text-xs font-semibold tracking-wide text-indigo-900">{{ __('ui.public_events_image') }}</div>
                            @endif
                            <div class="p-5">
                                <p class="text-xs font-semibold text-indigo-800">{{ __('ui.public_content_' . $content->content_type) }} · {{ $content->outlet?->name }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $content->title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $content->description }}</p>
                                @if ($content->starts_on || $content->ends_on)
                                    <p class="mt-4 text-xs text-slate-400">
                                        {{ $content->starts_on?->locale(app()->getLocale())->translatedFormat('d F Y') ?? '' }}
                                        @if ($content->ends_on)–{{ $content->ends_on->locale(app()->getLocale())->translatedFormat('d F Y') }}@endif
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
