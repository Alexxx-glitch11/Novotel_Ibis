<section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
    <h2 class="text-lg font-bold">{{ __('history.summary_title') }}</h2>

    @if (! empty($showBadge))
        <div class="mt-3">@include('guest._badge', ['status' => $reservation->reservation_status])</div>
    @endif

    <p class="mt-4 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ __('history.res_code') }}</p>
    <p class="text-lg font-bold text-[#14123f]">{{ $reservation->reservation_code }}</p>

    <dl class="mt-4 divide-y divide-slate-100 border-t border-slate-100 text-sm">
        @foreach ($rows as $label => $value)
            <div class="flex justify-between gap-4 py-3">
                <dt class="text-slate-500">{{ $label }}</dt>
                <dd class="text-right font-medium">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if (! empty($box))
        <div class="mt-4 rounded-xl bg-slate-50 p-4">
            <p class="text-sm font-semibold">{{ $box['title'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $box['body'] }}</p>
        </div>
    @endif
</section>
