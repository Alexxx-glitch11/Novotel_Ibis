@php
    $activeOutletQuery = \App\Models\Outlet::where('is_active', true)->ordered();
    if (! auth()->user()?->hasRole('admin')) {
        app(\App\Services\OutletAccess::class)->scope($activeOutletQuery, auth()->user());
    }
    $activeOutlets = $activeOutletQuery->get(['outlets_id', 'name']);
    $currentOutlet = request('outlet');
    $hasValidSelection = is_scalar($currentOutlet)
        && ctype_digit((string) $currentOutlet)
        && $activeOutlets->contains('outlets_id', (int) $currentOutlet);
@endphp
<div class="mt-auto border-t border-[#e0e5f0] px-2.5 pt-4">
    <p class="text-[10px] font-semibold tracking-[0.035em] text-[#6e7a8f] uppercase">Outlet Aktif</p>
    @if ($activeOutlets->count() === 1)
        <p class="mt-1.5 text-sm font-semibold text-[#212b3b]">{{ $activeOutlets->first()->name }}</p>
    @elseif ($activeOutlets->count() > 1)
        <form method="GET" action="{{ url()->current() }}" class="mt-1.5">
            @foreach (request()->except('outlet', 'page') as $key => $val)
                @if (is_scalar($val))
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <select name="outlet" onchange="this.form.submit()" aria-label="Pilih outlet aktif"
                    class="w-full cursor-pointer bg-transparent text-sm font-semibold text-[#212b3b] focus:outline-none">
                <option value="">Semua outlet</option>
                @foreach ($activeOutlets as $navOutlet)
                    <option value="{{ $navOutlet->outlets_id }}" @selected($hasValidSelection && (string) $currentOutlet === (string) $navOutlet->outlets_id)>
                        {{ $navOutlet->name }}
                    </option>
                @endforeach
            </select>
        </form>
    @else
        <p class="mt-1.5 text-sm font-semibold text-slate-400">Tidak ada outlet aktif</p>
    @endif
</div>
