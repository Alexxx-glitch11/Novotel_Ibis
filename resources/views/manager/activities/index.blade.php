@extends('layouts.manager')

@section('title', __('manager.activity_title'))

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#151442] md:text-3xl">{{ __('manager.activity_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('manager.activity_sub') }}</p>
        </div>

        <form method="GET" action="{{ route('manager.activities.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.5fr]">
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.activity_filter_range') }}
                <select name="range" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="7d" @selected($filters['range'] === '7d')>{{ __('manager.activity_range_7') }}</option>
                    <option value="30d" @selected($filters['range'] === '30d')>{{ __('manager.activity_range_30') }}</option>
                    <option value="all" @selected($filters['range'] === 'all')>{{ __('manager.activity_range_all') }}</option>
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.activity_filter_type') }}
                <select name="activity" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-400 focus:outline-none">
                    @foreach (['all', 'reservation', 'payment', 'cancellation', 'rates', 'content', 'other'] as $type)
                        <option value="{{ $type }}" @selected($filters['activity'] === $type)>{{ __('manager.activity_type_' . $type) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.activity_filter_actor') }}
                <select name="actor" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.activity_all_actors') }}</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->users_id }}" @selected($filters['actor'] === (string) $actor->users_id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.activity_search') }}
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('manager.activity_search_placeholder') }}" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none">
            </label>
            <button class="hidden">{{ __('manager.apply_filters') }}</button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['≡', 'blue', __('manager.activity_metric_total'), $summary['total'], __('manager.activity_range_caption_' . $filters['range'])],
                ['↻', 'amber', __('manager.activity_metric_cancellations'), $summary['cancellations'], __('manager.activity_metric_decisions')],
                ['%', 'violet', __('manager.activity_metric_rates'), $summary['rates'], __('manager.activity_metric_per_outlet')],
                ['U', 'green', __('manager.activity_metric_actors'), $summary['actors'], __('manager.activity_metric_managers_admins')],
            ] as [$icon, $tone, $label, $value, $note])
                <article class="flex min-h-24 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ ['blue' => 'bg-indigo-50 text-indigo-800', 'amber' => 'bg-amber-50 text-amber-700', 'violet' => 'bg-violet-50 text-violet-700', 'green' => 'bg-emerald-50 text-emerald-700'][$tone] }} text-sm font-bold">{{ $icon }}</span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                        <p class="mt-0.5 text-2xl font-bold leading-none text-[#151442]">{{ $value }}</p>
                        <p class="mt-2 truncate text-xs text-slate-400">{{ $note }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-[#151442]">{{ __('manager.activity_all_title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-400">{{ __('manager.activity_read_only') }}</p>
                </div>
                <span class="text-xs text-slate-400">{{ trans_choice('manager.activity_records', $activities->total(), ['count' => $activities->total()]) }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-xs">
                    <thead class="bg-[#f6f7fb] text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">{{ __('manager.activity_col_time') }}</th>
                            <th class="px-4 py-3">{{ __('manager.activity_col_actor') }}</th>
                            <th class="px-4 py-3">{{ __('manager.activity_col_action') }}</th>
                            <th class="px-4 py-3">{{ __('manager.activity_col_reference') }}</th>
                            <th class="px-4 py-3">{{ __('manager.activity_col_change') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('manager.activity_col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($activities as $activity)
                            <tr class="align-middle hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $activity->time_label }} <span class="text-slate-300">·</span> {{ $activity->created_at->locale(app()->getLocale())->translatedFormat('d M') }}</td>
                                <td class="px-4 py-4 font-medium text-slate-700">{{ $activity->actor_label }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-800">{{ $activity->activity_label }}</td>
                                <td class="px-4 py-4 font-medium text-[#201f5c]">{{ $activity->reference_label }}</td>
                                <td class="max-w-[300px] px-4 py-4 text-slate-500">{{ $activity->change_summary }}</td>
                                <td class="px-5 py-4 text-right"><span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-semibold text-emerald-700">{{ __('manager.activity_recorded') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">{{ __('manager.activity_empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages() || $activities->total())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                    <span>{{ trans_choice('manager.pagination_showing', $activities->count(), ['from' => $activities->firstItem() ?? 0, 'to' => $activities->lastItem() ?? 0, 'total' => $activities->total()]) }}</span>
                    {{ $activities->onEachSide(1)->links() }}
                </div>
            @endif
        </section>

        <p class="text-xs text-slate-400">{{ __('manager.activity_footer') }}</p>
    </div>

    <script>
        document.querySelectorAll('form[action="{{ route('manager.activities.index') }}"] select').forEach(select => {
            select.addEventListener('change', () => select.form.requestSubmit());
        });
        document.querySelector('form[action="{{ route('manager.activities.index') }}"] input[name="q"]')?.addEventListener('keydown', event => {
            if (event.key === 'Enter') event.currentTarget.form.requestSubmit();
        });
    </script>
@endsection
