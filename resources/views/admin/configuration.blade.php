@extends('layouts.admin')
@section('title', 'Konfigurasi sistem')
@section('content')
@php
    $fieldLabels = [
        'hold_minutes' => 'Temporary hold',
        'duration_minutes' => 'Durasi reservasi',
        'max_guests' => 'Maksimal tamu',
        'bank_name' => 'Nama bank',
        'bank_account_number' => 'Nomor rekening',
        'bank_account_holder' => 'Atas nama',
    ];
@endphp

<header class="admin-page-header">
    <h1>Konfigurasi sistem</h1>
    <p>Atur parameter operasional reservasi dan rekening bank transfer yang tampil kepada pelanggan.</p>
</header>

<form method="post" action="{{ route('admin.configuration.store') }}">
    @csrf
    <div class="admin-split admin-config-top">
        <section class="admin-card admin-panel">
            <h2>Aturan reservasi</h2>
            <p class="admin-panel-sub">Berlaku langsung untuk reservasi baru di seluruh outlet aktif.</p>

            <div class="admin-config-row">
                <label for="hold-minutes">Temporary hold meja</label>
                <div>
                    <input id="hold-minutes" type="number" min="1" max="1440" required name="hold_minutes" value="{{ old('hold_minutes', $holdMinutes) }}">
                    <span>menit</span>
                </div>
                <small>Lama meja ditahan sebelum checkout diselesaikan</small>
            </div>

            <div class="admin-config-row">
                <label for="max-guests">Maksimal tamu per reservasi</label>
                <div>
                    <input id="max-guests" type="number" min="1" max="100" required name="max_guests" value="{{ old('max_guests', $maxGuests) }}">
                    <span>orang</span>
                </div>
                <small>Batas maksimum total tamu dewasa + anak per transaksi</small>
            </div>

            <div class="admin-config-row">
                <label for="duration-minutes">Durasi reservasi</label>
                <div>
                    <input id="duration-minutes" type="number" min="15" max="720" required name="duration_minutes" value="{{ old('duration_minutes', $durationMinutes) }}">
                    <span>menit</span>
                </div>
                <small>Rentang waktu slot meja per reservasi</small>
            </div>
        </section>

        <section class="admin-card admin-panel">
            <h2>Rekening bank transfer (Pelanggan)</h2>
            <p class="admin-panel-sub">Tampil pada halaman tinjauan pembayaran dan konfirmasi pelanggan.</p>

            <div class="admin-form-box" style="margin:0;">
                <label>Nama bank
                    <input type="text" name="bank_name" maxlength="100" required value="{{ old('bank_name', $bankName) }}" placeholder="Contoh: Bank Mandiri">
                </label>
                <label style="margin-top:10px;">Nomor rekening
                    <input type="text" name="bank_account_number" maxlength="50" required value="{{ old('bank_account_number', $bankAccountNumber) }}" placeholder="Contoh: 1300012345678" style="font-family:ui-monospace,monospace;">
                </label>
                <label style="margin-top:10px;">Nama pemilik rekening (a.n.)
                    <input type="text" name="bank_account_holder" maxlength="100" required value="{{ old('bank_account_holder', $bankAccountHolder) }}" placeholder="Contoh: PT Novotel ibis YIA Hospitality">
                </label>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-[#f6f8fc] p-3.5 text-xs text-slate-600">
                <p class="font-semibold text-[#21265d]">Pratinjau di halaman pelanggan</p>
                @if ($bankName && $bankAccountNumber && $bankAccountHolder)
                    <p class="mt-1 text-slate-800">{{ $bankName }} · <span class="font-mono font-semibold">{{ $bankAccountNumber }}</span> · a.n. {{ $bankAccountHolder }}</p>
                @else
                    <p class="mt-1 text-amber-700">Lengkapi nama bank, nomor rekening, dan atas nama agar pelanggan melihat tujuan transfer resmi.</p>
                @endif
            </div>
        </section>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e0e5f0] bg-white px-5 py-4">
        <p class="text-xs text-[#6e7a8f]">Setiap perubahan disimpan oleh Admin dan langsung diterapkan pada alur reservasi.</p>
        <button type="submit" class="admin-button admin-primary" @disabled(!$available)>Simpan &amp; terapkan konfigurasi</button>
    </div>
</form>

<section class="admin-card admin-panel mt-5">
    <h2>Ketentuan unggah bukti transfer</h2>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <div class="admin-config-static" style="margin-top:0;">
            <strong>Format yang diterima</strong>
            <span>JPG • JPEG • PNG • WebP • PDF</span>
        </div>
        <div class="admin-config-static" style="margin-top:0;">
            <strong>Batas ukuran berkas</strong>
            <span>Maksimal 5 MB</span>
        </div>
    </div>
</section>

<section class="admin-card admin-panel mt-5">
    <h2>Riwayat perubahan konfigurasi</h2>
    <div class="admin-table-wrap mt-3">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>WAKTU</th>
                    <th>DIUBAH OLEH</th>
                    <th>RINGKASAN PERUBAHAN</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($history as $log)
                @php
                    $old = $log->old_values ?? [];
                    $new = $log->new_values ?? [];
                @endphp
                <tr>
                    <td class="admin-nowrap">{{ $log->created_at?->locale(app()->getLocale())->translatedFormat('d M Y H.i') }}</td>
                    <td translate="no">{{ $log->user?->name ?? 'Admin' }}</td>
                    <td>
                        @foreach ($new as $k => $v)
                            <div style="font-size:12.5px;">
                                <strong>{{ $fieldLabels[$k] ?? $k }}:</strong>
                                <span class="admin-muted">{{ ($old[$k] ?? '') !== '' ? $old[$k] : '—' }}</span> → <span>{{ $v }}</span>
                            </div>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="admin-empty">Belum ada perubahan konfigurasi tercatat.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
