@php $current = app()->getLocale(); @endphp
<div class="relative" data-lang-switcher>
    <button type="button" data-lang-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('manager.language') }}"
            class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 text-slate-700 hover:bg-black/5">
        @include('partials.flag', ['code' => $current])
        <span class="text-sm font-medium uppercase">{{ $current }}</span>
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
    </button>

    <ul data-lang-menu class="absolute top-full right-0 z-50 mt-2 hidden min-w-44 overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
        @foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $name)
            <li>
                <a href="{{ route('lang.switch', $code) }}"
                   class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 {{ $current === $code ? 'font-semibold text-slate-900' : '' }}">
                    @include('partials.flag', ['code' => $code])
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>