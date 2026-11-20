@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="rrms-pagination">
        <style>
            .rrms-pagination{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}
            .rrms-page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:9px;border:1px solid #e0e5f0;background:#fff;color:#212b3b;font-size:12px;font-weight:600;text-decoration:none;transition:background-color .15s,border-color .15s,color .15s}
            .rrms-page-btn:hover{background:#f6f8fc;border-color:#c8d1e4;color:#21265d}
            .rrms-page-btn.is-active{background:#21265d;border-color:#21265d;color:#fff}
            .rrms-page-btn.is-disabled{opacity:.45;pointer-events:none;background:#f8f9fc}
            .rrms-page-dots{display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:34px;color:#6e7a8f;font-size:12px;font-weight:600}
        </style>

        {{-- Previous Arrow --}}
        @if ($paginator->onFirstPage())
            <span class="rrms-page-btn is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rrms-page-btn" aria-label="Halaman sebelumnya">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="rrms-page-dots" aria-disabled="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="rrms-page-btn is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="rrms-page-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Arrow --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rrms-page-btn" aria-label="Halaman berikutnya">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        @else
            <span class="rrms-page-btn is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        @endif
    </nav>
@endif
