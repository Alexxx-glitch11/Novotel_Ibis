@extends('layouts.manager')

@section('title', 'Permintaan pembatalan')

@section('content')
    <h1 class="text-2xl font-bold text-[#1e1b4b]">Permintaan pembatalan</h1>
    <p class="mt-1 text-sm text-slate-500">Tinjau konteks reservasi dan alasan sebelum mengambil keputusan.</p>

    @if (session('success'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ $errors->first() }}</div>
    @endif

    {{-- 2 kolom: daftar (kiri) + detail (kanan) --}}
    <div class="mt-4 flex flex-col items-start gap-10 md:flex-row">

        {{-- ===== KIRI: daftar menunggu keputusan ===== --}}
        <section class="w-full shrink-0 rounded-2xl border border-slate-200 bg-white p-4 md:w-[380px] xl:w-[420px] md:min-h-[600px] md:p-5">
            <h2 class="px-1 text-lg font-semibold text-[#1e1b4b]">Menunggu keputusan</h2>
            <p class="px-1 text-sm text-slate-500">{{ $cancellations->count() }} Permintaan Semua Outlet</p>

            <div class="mt-3">
                @forelse ($cancellations as $item)
                    @php
                        $active = $selectedRequest && $selectedRequest->cancellation_requests_id === $item->cancellation_requests_id;
                        $r = $item->reservation;
                    @endphp
                    @if (! $loop->first && ! $active && ! ($selectedRequest && $cancellations[$loop->index - 1]->cancellation_requests_id === $selectedRequest->cancellation_requests_id))
                        <div class="mx-3 border-t border-slate-200"></div>
                    @endif
                    <a href="{{ route('manager.cancellations.index', ['selected' => $item->cancellation_requests_id]) }}"
                       class="block rounded-xl px-4 py-4 {{ $active ? 'bg-[#e8ebfb]' : 'hover:bg-slate-50' }}">
                        <p class="text-base font-semibold text-[#1e1b4b]">{{ $r?->customer_name }}</p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $r?->outlet?->name }}  {{ str_replace(':', '.', substr($r?->start_time ?? '', 0, 5)) }}
                        </p>
                        <div class="mt-2.5 flex items-center gap-3">
                            <span class="rounded-full bg-[#fdebc8] px-4 py-1 text-xs font-medium text-[#8a5a00]">Menunggu</span>
                            <span class="truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($item->reason, 28) }}</span>
                        </div>
                    </a>
                @empty
                    <p class="px-1 py-12 text-center text-xs text-slate-400">Tidak ada permintaan pembatalan.</p>
                @endforelse
            </div>
        </section>

        {{-- ===== KANAN: detail permintaan ===== --}}
        <section class="w-full min-w-0 flex-1 md:pt-4">
            @if (! $selectedRequest)
                <p class="py-24 text-center text-sm text-slate-400">Pilih permintaan di sebelah kiri.</p>
            @else
                @php
                    $r = $selectedRequest->reservation;
                    $tables = $r?->tables ?? collect();
                    $pay = $r?->latestPayment;
                    $isPaid = $pay?->payment_status === 'paid';
                    $statusLabel = ['pending' => 'Menunggu keputusan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$selectedRequest->status];
                    $statusClass = ['pending' => 'bg-[#fdebc8] text-[#8a5a00]', 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-red-100 text-red-700'][$selectedRequest->status];
                    $rows = [
                        'Tamu' => $r?->customer_name,
                        'Kode reservasi' => $r?->reservation_code,
                        'Outlet & meja' => $r?->outlet?->name . '  ' . $tables->pluck('table_code')->join(', ') . ' ' . ($tables->first()?->area?->name ?? ''),
                        'Jadwal' => $r?->reservation_date?->locale('id')->translatedFormat('l, d M Y') . '  ' . str_replace(':', '.', substr($r?->start_time ?? '', 0, 5)),
                        'Pembayaran' => ucfirst($pay?->payment_status ?? 'belum ada') . '  Rp' . number_format($r?->grand_total ?? 0, 0, ',', '.'),
                    ];
                @endphp

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-[#1e1b4b]">Detail permintaan</h2>
                        <p class="text-sm text-slate-500">Informasi reservasi terpilih</p>
                    </div>
                    <span class="shrink-0 rounded-full px-6 py-1.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <dl class="mt-4">
                    @foreach ($rows as $label => $value)
                        <div class="flex items-center border-b border-slate-200 py-4">
                            <dt class="w-[180px] shrink-0 text-sm text-slate-500">{{ $label }}</dt>
                            <dd class="text-sm font-semibold text-[#1e1b4b]">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-8 text-sm text-slate-600">Alasan pembatalan</p>
                <div class="mt-2 rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700">
                    {{ $selectedRequest->reason }}
                </div>

                @if ($selectedRequest->status === 'pending')
                    <form method="POST" id="decision-form" action="{{ route('manager.cancellations.approve', $selectedRequest) }}">
                        @csrf
                        <label for="decision-note" class="mt-6 block text-sm font-semibold text-[#1e1b4b]">Catatan keputusan (wajib saat menolak)</label>
                        <textarea id="decision-note" name="decision_note" rows="2"
                                  placeholder="Tulis alasan yang dapat dilihat tim operasional..."
                                  class="mt-2 block w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm placeholder:text-slate-400 focus:border-[#1f1d5c] focus:outline-none">{{ old('decision_note') }}</textarea>

                        @if ($isPaid)
                            <div class="mt-4 rounded-lg bg-[#fdf3dd] px-4 py-3.5 text-sm font-medium text-[#8a5a00]">
                                Reservasi Paid: keputusan batal tidak memproses refund otomatis.
                            </div>
                        @endif

                        <div class="mt-6 flex items-center justify-between gap-3">
                            <button type="submit"
                                    formaction="{{ route('manager.cancellations.reject', $selectedRequest) }}"
                                    onclick="return confirmReject()"
                                    class="rounded-lg border border-slate-200 bg-white px-8 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                                Tolak permintaan
                            </button>
                            <button type="submit"
                                    onclick="return confirm('Setujui pembatalan ini? Reservasi akan dibatalkan dan meja dilepas.')"
                                    class="rounded-lg bg-[#1f1d5c] px-9 py-3 text-sm font-semibold text-white hover:bg-[#14123f]">
                                Setujui pembatalan
                            </button>
                        </div>
                    </form>
                @else
                    <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600 ring-1 ring-slate-200">
                        Diputuskan oleh <b>{{ $selectedRequest->reviewedBy?->name ?? 'Manager' }}</b>
                        pada {{ $selectedRequest->reviewed_at?->format('d/m/Y H.i') }}.
                        @if ($selectedRequest->decision_note)
                            <p class="mt-1 italic">"{{ $selectedRequest->decision_note }}"</p>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection

@push('scripts')
<script>
    function confirmReject() {
        const note = document.getElementById('decision-note');
        if (!note.value.trim()) {
            alert('Catatan keputusan wajib diisi saat menolak permintaan.');
            note.focus();
            return false;
        }
        return confirm('Tolak permintaan pembatalan ini? Reservasi tetap aktif.');
    }
</script>
@endpush
