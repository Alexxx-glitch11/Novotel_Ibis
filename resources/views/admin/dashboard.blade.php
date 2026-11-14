@extends('layouts.admin')
@section('title', 'Administrasi sistem')
@section('content')
<header class="admin-page-header"><h1>Administrasi sistem</h1><p>Kelola data master dan akses operasional RRMS.</p></header>
<div class="admin-metrics">
    @foreach ([['O','Outlet aktif',$outletCount,'Outlet yang dapat digunakan'],['M','Meja terdaftar',$tableCount,'Di seluruh area'],['M','Menu aktif',$menuCount,'Tersedia di outlet'],['P','Pengguna internal',$userCount,'Akun dengan role']] as [$icon,$label,$value,$note])
        <article class="admin-card admin-metric"><span class="admin-metric-icon">{{ $icon }}</span><div><div class="admin-muted">{{ $label }}</div><strong>{{ number_format($value) }}</strong><small>{{ $note }}</small></div></article>
    @endforeach
</div>
<div class="admin-shortcuts">
    @foreach ([['01','Outlet & area','Kelola outlet, area, jam operasional, dan informasi restoran.',$outletCount.' outlet • '.$areaCount.' area','admin.outlets.index'],['02','Meja & denah','Atur kode meja, kapasitas, area, dan tata letak denah.',$tableCount.' meja','admin.tables.index'],['03','Menu & kategori','Kelola kategori, harga, outlet, dan ketersediaan menu.',$menuCount.' menu aktif','admin.menu.index'],['04','Pengguna & role','Buat akun, tetapkan role, dan kelola status akses.',$userCount.' akun','admin.users.index'],['05','Promosi & acara','Perbarui konten promosi dan acara pada halaman publik.',$contentCount.' konten','admin.content.index'],['06','Konfigurasi sistem','Atur parameter aplikasi dan preferensi operasional.','Kebijakan pending','admin.configuration.index']] as [$number,$title,$description,$count,$route])
           <a class="admin-card admin-shortcut" href="{{ route($route) }}"><span class="admin-number">{{ $number }}</span><strong>{{ $title }}</strong><p>{{ $description }}</p><b>{{ $count }}</b><span class="admin-manage">Kelola →</span></a>
    @endforeach
</div>
<section class="admin-card admin-recent">
    <div class="admin-section-heading"><div><h2>Log aktivitas terbaru</h2><p>Telusuri perubahan data dan tindakan penting.</p></div><a class="admin-text-link" href="{{ route('admin.audit.index') }}">Lihat semua →</a></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>WAKTU</th><th>PENGGUNA</th><th>AKTIVITAS</th><th>OBJEK</th></tr></thead><tbody>
    @forelse ($recentLogs as $log)
        <tr><td>{{ $log->created_at?->format('H.i') }}</td><td>{{ $log->user?->email ?? 'Sistem' }}</td><td>{{ str($log->action)->replace('_',' ')->headline() }}</td><td>{{ str($log->entity_type)->headline() }} • {{ $log->entity_id }}</td></tr>
    @empty<tr><td colspan="4" class="admin-empty">Belum ada aktivitas tercatat.</td></tr>@endforelse
    </tbody></table></div>
</section>
<p class="admin-footnote">Admin mengelola akun dan data master. Keputusan tarif dan pembatalan tetap berada pada Manager.</p>
@endsection
