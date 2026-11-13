@extends('layouts.manager')

@section('title', __('manager.event_title'))
@section('active-outlet', $filters['outlet'] !== 'all' ? $outlets->firstWhere('outlets_id', (int) $filters['outlet'])?->name : __('manager.all_outlets'))

@section('content')
    @php
        $contentPreview = $editingContent ?? $selectedContent;
        $formOutletId = $editingContent?->outlets_id ?? ($filters['outlet'] !== 'all' ? (int) $filters['outlet'] : $outlets->first()?->outlets_id);
        $statusTone = [
            'active' => 'bg-emerald-50 text-emerald-700',
            'scheduled' => 'bg-amber-50 text-amber-700',
            'draft' => 'bg-slate-100 text-slate-600',
            'ended' => 'bg-slate-100 text-slate-500',
            'inactive' => 'bg-rose-50 text-rose-700',
        ];
        $filtersForSelection = ['outlet' => $filters['outlet'], 'type' => $filters['type'], 'status' => $filters['status']];
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#151442] md:text-3xl">{{ __('manager.event_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('manager.event_sub') }}</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('manager.events.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end">
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.outlet_label') }}
                <select name="outlet" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.all_outlets') }}</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->outlets_id }}" @selected($filters['outlet'] === (string) $outlet->outlets_id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.event_filter_type') }}
                <select name="type" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.activity_type_all') }}</option>
                    <option value="promotion" @selected($filters['type'] === 'promotion')>{{ __('manager.content_type_promotion') }}</option>
                    <option value="event" @selected($filters['type'] === 'event')>{{ __('manager.content_type_event') }}</option>
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-500">
                {{ __('manager.res_filter_status') }}
                <select name="status" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none">
                    <option value="all">{{ __('manager.activity_type_all') }}</option>
                    @foreach (['active', 'scheduled', 'draft', 'ended', 'inactive'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('manager.content_status_' . $status) }}</option>
                    @endforeach
                </select>
            </label>
            <a href="{{ route('manager.events.index', ['outlet' => $filters['outlet'], 'type' => $filters['type'], 'status' => $filters['status'], 'create' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#201f5c] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#151442]">
                <span class="text-lg leading-none" aria-hidden="true">+</span>{{ __('manager.event_add') }}
            </a>
        </form>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)]">
            <section class="min-h-[520px] rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-[#151442]">{{ __('manager.event_active_content') }}</h2>
                    @if ($filters['outlet'] !== 'all')
                        <span class="rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-500">{{ $outlets->firstWhere('outlets_id', (int) $filters['outlet'])?->name }}</span>
                    @endif
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($contents as $content)
                        @php
                            $displayStatus = $content->display_status;
                            $selected = $contentPreview?->outlet_contents_id === $content->outlet_contents_id;
                        @endphp
                        <a href="{{ route('manager.events.index', [...$filtersForSelection, 'selected' => $content->outlet_contents_id]) }}" class="block rounded-xl border px-4 py-4 transition {{ $selected ? 'border-indigo-200 bg-indigo-50/50 ring-1 ring-indigo-100' : 'border-slate-100 bg-[#f8f9fc] hover:border-slate-200 hover:bg-white' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-800">{{ $content->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('manager.content_type_' . $content->content_type) }} · {{ $content->outlet?->name }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <span class="whitespace-nowrap text-[10px] text-slate-400">{{ $content->starts_on?->locale(app()->getLocale())->translatedFormat('d M') ?? '—' }}@if($content->ends_on)–{{ $content->ends_on->locale(app()->getLocale())->translatedFormat('d M') }}@endif</span>
                                    <span class="rounded-full px-3 py-1 text-[10px] font-semibold {{ $statusTone[$displayStatus] ?? 'bg-slate-100 text-slate-600' }}">{{ __('manager.content_status_' . $displayStatus) }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-200 px-6 text-center">
                            <div>
                                <p class="font-semibold text-slate-700">{{ __('manager.event_empty_title') }}</p>
                                <p class="mt-1 max-w-sm text-sm text-slate-400">{{ __('manager.event_empty_sub') }}</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($contents->hasPages())
                    <div class="mt-4 border-t border-slate-100 pt-3">{{ $contents->onEachSide(1)->links() }}</div>
                @endif
            </section>

            <div class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-[#151442]">{{ __('manager.event_preview') }}</h2>
                    @if ($contentPreview)
                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                            @if ($contentPreview->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentPreview->image_path) }}" alt="{{ $contentPreview->title }}" class="h-40 w-full object-cover">
                            @else
                                <div class="grid h-40 place-items-center bg-indigo-50 text-xs font-semibold tracking-wide text-indigo-800">{{ __('manager.event_image_placeholder') }}</div>
                            @endif
                            <div class="p-4">
                                <p class="text-lg font-bold text-[#151442]">{{ $contentPreview->title }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $contentPreview->description }}</p>
                                <p class="mt-3 text-xs font-medium text-slate-600">{{ $contentPreview->outlet?->name }} · {{ $contentPreview->starts_on?->locale(app()->getLocale())->translatedFormat('d F Y') ?? __('manager.event_no_start') }}@if($contentPreview->ends_on)–{{ $contentPreview->ends_on->locale(app()->getLocale())->translatedFormat('d F Y') }}@endif</p>
                                <a href="{{ route('manager.events.index', [...$filtersForSelection, 'edit' => $contentPreview->outlet_contents_id]) }}" class="mt-4 flex w-full items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-[#201f5c] hover:bg-indigo-50">{{ __('manager.event_edit') }}</a>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 grid h-64 place-items-center rounded-xl border border-dashed border-slate-200 text-center text-sm text-slate-400">{{ __('manager.event_preview_empty') }}</div>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-[#151442]">{{ __('manager.event_before_publish') }}</h2>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ __('manager.event_publish_help') }}</p>
                </section>
            </div>
        </div>

        @if ($formOpen)
            <div id="event-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4">
                <section class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl md:p-7" role="dialog" aria-modal="true" aria-labelledby="event-form-title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 id="event-form-title" class="text-xl font-bold text-[#151442]">{{ $editingContent ? __('manager.event_edit_title') : __('manager.event_add_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('manager.event_form_sub') }}</p>
                        </div>
                        <a href="{{ route('manager.events.index', $filtersForSelection) }}" class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="{{ __('manager.cancel') }}">×</a>
                    </div>

                    <form method="POST" enctype="multipart/form-data" action="{{ $editingContent ? route('manager.events.update', $editingContent) : route('manager.events.store') }}" class="mt-5 space-y-4">
                        @csrf
                        @if ($editingContent) @method('PUT') @endif
                        <label class="block text-sm font-medium text-slate-700">{{ __('manager.outlet_label') }}
                            <select name="outlets_id" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->outlets_id }}" @selected((string) old('outlets_id', $editingContent?->outlets_id ?? $formOutletId) === (string) $outlet->outlets_id)>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                            @error('outlets_id')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_type') }}
                                <select name="content_type" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                    <option value="promotion" @selected(old('content_type', $editingContent?->content_type ?? 'promotion') === 'promotion')>{{ __('manager.content_type_promotion') }}</option>
                                    <option value="event" @selected(old('content_type', $editingContent?->content_type) === 'event')>{{ __('manager.content_type_event') }}</option>
                                </select>
                                @error('content_type')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_visibility') }}
                                <select name="status" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                    @foreach (['draft', 'published', 'inactive'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $editingContent?->status ?? 'draft') === $status)>{{ __('manager.event_visibility_' . $status) }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_name') }}
                            <input name="title" required maxlength="120" value="{{ old('title', $editingContent?->title) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-400 focus:outline-none">
                            @error('title')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_description') }}
                            <textarea name="description" rows="4" required maxlength="5000" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-400 focus:outline-none">{{ old('description', $editingContent?->description) }}</textarea>
                            @error('description')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_start') }}
                                <input type="date" name="starts_on" value="{{ old('starts_on', $editingContent?->starts_on?->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-400 focus:outline-none">
                                @error('starts_on')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_end') }}
                                <input type="date" name="ends_on" value="{{ old('ends_on', $editingContent?->ends_on?->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-400 focus:outline-none">
                                @error('ends_on')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('manager.event_image') }}
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-[#201f5c]">
                            <span class="mt-1 block text-xs font-normal text-slate-400">{{ __('manager.event_image_help') }}</span>
                            @error('image')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>
                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                            <a href="{{ route('manager.events.index', $filtersForSelection) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">{{ __('manager.cancel') }}</a>
                            <button class="rounded-lg bg-[#201f5c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#151442]">{{ __('manager.event_save') }}</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
    </div>

    <script>
        const eventFilters = document.querySelectorAll('form[action="{{ route('manager.events.index') }}"] select');
        eventFilters.forEach(select => select.addEventListener('change', () => select.form.requestSubmit()));
        document.getElementById('event-modal')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) window.location.href = @js(route('manager.events.index', $filtersForSelection));
        });
    </script>
@endsection
