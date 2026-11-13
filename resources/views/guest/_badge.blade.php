@php
    $tones = [
        'pending_payment'   => 'bg-amber-50 text-amber-700',
        'confirmed'         => 'bg-emerald-50 text-emerald-700',
        'partially_arrived' => 'bg-sky-50 text-sky-700',
        'checked_in'        => 'bg-sky-50 text-sky-700',
        'completed'         => 'bg-emerald-50 text-emerald-700',
        'cancelled'         => 'bg-slate-100 text-slate-500',
        'expired'           => 'bg-slate-100 text-slate-500',
        'no_show'           => 'bg-red-50 text-red-700',
        'pending_review'    => 'bg-amber-50 text-amber-700',
    ];
@endphp
<span class="inline-block rounded-full px-3 py-1 text-xs font-medium {{ $tones[$status] ?? 'bg-slate-100 text-slate-500' }}">{{ __('history.st_' . $status) }}</span>