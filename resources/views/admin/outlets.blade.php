@extends('layouts.admin')
@section('title', 'Outlet dan area')
@section('content')
<header class="admin-page-header"><h1>Outlet dan area restoran</h1><p>Kelola lokasi restoran dan area yang dapat dipilih pelanggan.</p></header>
<div class="admin-split admin-outlet-layout">
    <section class="admin-card admin-panel">
        <div class="admin-section-heading"><h2>Outlet terdaftar</h2><button class="admin-button admin-primary" type="button" data-toggle="outlet-form">+ Tambah outlet</button></div>
        <form id="outlet-form" class="admin-form-box" method="post" action="{{ route('admin.outlets.store') }}" @if(!old('code') && !$errors->has('name') && !$errors->has('code') && !$errors->has('address') && !$errors->has('phone')) hidden @endif>@csrf
            <div class="admin-form-grid"><label>Nama outlet<input name="name" maxlength="100" required value="{{ old('name') }}" placeholder="Contoh: Novotel Food Exchange"></label><label>Kode outlet<input name="code" maxlength="20" required value="{{ old('code') }}" placeholder="NOV"></label><label>Telepon (opsional)<input name="phone" maxlength="20" value="{{ old('phone') }}"></label><label class="admin-span-2">Alamat (opsional)<input name="address" maxlength="2000" value="{{ old('address') }}"></label></div>
            <p class="admin-help">Jam operasional belum tersedia pada skema database saat ini.</p><div class="admin-form-actions"><button class="admin-button admin-primary">Simpan outlet</button><button class="admin-button" type="button" data-toggle="outlet-form">Batal</button></div>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>OUTLET</th><th>KODE</th><th>JAM OPERASIONAL</th><th>STATUS</th></tr></thead><tbody>
        @forelse ($outlets as $outlet)<tr><td><strong>{{ $outlet->name }}</strong><small class="admin-cell-note">{{ $outlet->areas->count() }} area</small></td><td>{{ $outlet->code }}</td><td class="admin-muted">Belum dikonfigurasi</td><td><span class="admin-badge {{ $outlet->is_active ? 'is-active' : 'is-inactive' }}">{{ $outlet->is_active ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
        @empty<tr><td colspan="4" class="admin-empty">Belum ada outlet.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="admin-card admin-panel">
        <div class="admin-section-heading"><div><h2>Area {{ $outlets->first()?->name ?? '' }}</h2><p>Kelola area per outlet.</p></div><button class="admin-button admin-outline" type="button" data-toggle="area-form" @disabled($outlets->isEmpty())>+ Tambah area</button></div>
        <form id="area-form" class="admin-form-box" method="post" action="{{ route('admin.areas.store') }}" @if(!old('outlets_id') && !$errors->has('outlets_id') && !$errors->has('name') && !$errors->has('description')) hidden @endif>@csrf
            <label>Outlet<select name="outlets_id" required>@foreach ($outlets as $outlet)<option value="{{ $outlet->outlets_id }}">{{ $outlet->name }}</option>@endforeach</select></label>
            <label>Nama area<input name="name" maxlength="50" required value="{{ old('name') }}" placeholder="Indoor"></label>
            <label>Deskripsi<input name="description" maxlength="255" value="{{ old('description') }}" placeholder="Opsional"></label>
            <div class="admin-form-actions"><button class="admin-button admin-primary">Simpan area</button><button class="admin-button" type="button" data-toggle="area-form">Batal</button></div>
        </form>
        @forelse ($outlets as $outlet)
            <div class="admin-area-group"><h3>{{ $outlet->name }} <span>{{ $outlet->areas->count() }} area</span></h3>
                @forelse ($outlet->areas as $area)<div class="admin-area-row"><strong>{{ $area->name }}</strong><span>{{ $area->tables_count }} meja</span></div>
                @empty<div class="admin-empty-inline">Belum ada area.</div>@endforelse
            </div>
        @empty<div class="admin-empty-inline">Tambahkan outlet sebelum membuat area.</div>@endforelse
    </section>
</div>
<div class="admin-note">Outlet dan area ditampilkan dalam alur reservasi setelah data operasional dikonfigurasi.</div>
@endsection
