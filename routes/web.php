<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController as AuthLoginController;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboardController;
use App\Http\Controllers\Cashier\CheckInController as CashierCheckInController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\ActivityController;
use App\Http\Controllers\Manager\CancellationApprovalController;
use App\Http\Controllers\Manager\FloorPlanController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\Manager\OutletController;
use App\Http\Controllers\Manager\OutletContentController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\ReservationController;
use App\Http\Controllers\Manager\TableController;
use App\Http\Controllers\Manager\TaxServiceRateController;
use App\Http\Controllers\FinanceLoginController;
use App\Http\Controllers\FinancePaymentController;
use App\Http\Controllers\TableSelectionController;
use App\Http\Controllers\GuestReservationController;
use App\Http\Controllers\LiveUpdatesController;
use App\Models\Outlet;
use App\Models\OutletContent;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


Route::get('/', function () {
    return view('home', [
        // [kode outlet => aktif?], dipakai untuk menutup kartu outlet yang nonaktif
        'outletOpen' => Outlet::pluck('is_active', 'code'),
        'activeContents' => OutletContent::query()
            ->with('outlet')
            ->publishedOn()
            ->whereHas('outlet', fn ($query) => $query->where('is_active', true))
            ->orderBy('starts_on')
            ->limit(20)
            ->get(),
    ]);
})->name('home');

Route::get('/media/outlet-content/{outletContent}', function (OutletContent $outletContent) {
    $path = ltrim((string) $outletContent->image_path, '/');
    abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path, null, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->whereNumber('outletContent')->name('outlet-content.image');

// Halaman pelanggan: pilih meja (nama route sama, dipakai tombol di beranda)
Route::get('/reservasi/{outlet}', [TableSelectionController::class, 'show'])->name('reservasi.outlet');
Route::get('/reservasi/{outlet}/meja-tersedia', [TableSelectionController::class, 'availableTables'])->name('reservasi.availability');
Route::post('/reservasi/{outlet}/meja/hold', [TableSelectionController::class, 'holdTable'])->name('reservasi.hold');
Route::post('/reservasi/{outlet}/menu', [TableSelectionController::class, 'saveMenu'])->name('reservasi.menu');
Route::post('/reservasi/{outlet}/konfirmasi', [TableSelectionController::class, 'confirm'])->name('reservasi.confirm');
Route::get('/reservasi/{outlet}/selesai/{code}', [TableSelectionController::class, 'confirmation'])->name('reservasi.confirmation');
Route::get('/reservasi/{outlet}/selesai/{code}/qr', [TableSelectionController::class, 'downloadQr'])->name('reservasi.qr-download');
Route::post('/reservasi/{outlet}/selesai/{code}/bukti-pembayaran', [TableSelectionController::class, 'uploadPaymentProof'])->name('reservasi.payment-proof');

// Riwayat reservasi tanpa akun + pembatalan
Route::prefix('reservasi-saya')->name('guest.')->group(function () {
    Route::get('/', [GuestReservationController::class, 'index'])->name('reservations');
    Route::post('/cari', [GuestReservationController::class, 'lookup'])->middleware('throttle:6,1')->name('lookup');
    Route::post('/kode', [GuestReservationController::class, 'sendOtp'])->middleware('throttle:5,1')->name('otp.send');
    Route::post('/verifikasi', [GuestReservationController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('otp.verify');
    Route::post('/keluar', [GuestReservationController::class, 'forget'])->name('forget');

    Route::get('/{code}/batal', [GuestReservationController::class, 'cancelForm'])->name('cancel.create');
    Route::post('/{code}/batal', [GuestReservationController::class, 'cancelStore'])->middleware('throttle:10,1')->name('cancel.store');
    Route::get('/{code}/pembatalan', [GuestReservationController::class, 'cancelStatus'])->name('cancel.status');
});

Route::get('/login', [AuthLoginController::class, 'create'])->name('login');
Route::post('/login', [AuthLoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
Route::post('/logout', [AuthLoginController::class, 'destroy'])->name('logout');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/finance/login', fn () => redirect()->route('login'))->name('finance.login');
Route::middleware(['portal:finance,finance|admin'])->prefix('finance')->name('finance.')->group(function () {
    Route::redirect('/', '/finance/ringkasan');
    Route::get('/live-status', [LiveUpdatesController::class, 'status'])->name('live-status');
    Route::get('/ringkasan', [FinancePaymentController::class, 'dashboard'])->name('dashboard');
    Route::get('/pembayaran', [FinancePaymentController::class, 'index'])->name('payments.index');
    Route::get('/pembayaran/{payment}', [FinancePaymentController::class, 'show'])->whereNumber('payment')->name('payments.show');
    Route::get('/pembayaran/{payment}/bukti', [FinancePaymentController::class, 'proof'])->name('payments.proof');
    Route::post('/pembayaran/{payment}/terima', [FinancePaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/pembayaran/{payment}/tolak', [FinancePaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/riwayat', [FinancePaymentController::class, 'history'])->name('history.index');
    Route::get('/laporan', [FinancePaymentController::class, 'reports'])->name('reports.index');
    Route::get('/laporan/ekspor', [FinancePaymentController::class, 'exportReports'])->name('reports.export');
    Route::post('/logout', [AuthLoginController::class, 'destroy'])->name('logout');
});

Route::get('/cashier/login', fn () => redirect()->route('login'))->name('cashier.login');
Route::middleware(['portal:cashier,cashier|kasir|admin'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/', [CashierDashboardController::class, 'index'])->name('dashboard');
    Route::get('/live-status', [LiveUpdatesController::class, 'status'])->name('live-status');
    Route::get('/reservasi', [CashierDashboardController::class, 'reservations'])->name('reservations.index');
    Route::get('/reservasi/{reservation}', [CashierDashboardController::class, 'showReservation'])->whereNumber('reservation')->name('reservations.show');
    Route::get('/riwayat', [CashierDashboardController::class, 'history'])->name('history.index');
    Route::get('/riwayat/ekspor', [CashierDashboardController::class, 'exportHistory'])->name('history.export');
    Route::get('/pindai-qr', [CashierDashboardController::class, 'history'])->name('qr.index');
    Route::get('/pembatalan', [CashierDashboardController::class, 'cancellations'])->name('cancellations.index');
    Route::post('/pembatalan', [CashierDashboardController::class, 'storeCancellation'])->name('cancellations.store');
    Route::post('/check-in/scan', [CashierCheckInController::class, 'scan'])->name('checkin.scan');
    Route::post('/check-in/{reservation}', [CashierCheckInController::class, 'record'])->whereNumber('reservation')->name('checkin.record');
    Route::post('/check-in/{reservation}/selesai', [CashierCheckInController::class, 'complete'])->whereNumber('reservation')->name('checkin.complete');
    Route::post('/check-in/{reservation}/no-show', [CashierCheckInController::class, 'markNoShow'])->whereNumber('reservation')->name('checkin.no-show');
    Route::post('/logout', [AuthLoginController::class, 'destroy'])->name('logout');
});

Route::get('/bahasa/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');

Route::get('/manager/login', fn () => redirect()->route('login'))->name('manager.login');

// Halaman manager.
Route::middleware(['portal:manager,manager|admin'])->prefix('manager')->name('manager.')->group(function () {
    Route::post('/logout', [AuthLoginController::class, 'destroy'])->name('logout');
    Route::redirect('/', '/manager/ringkasan');
    Route::get('/live-status', [LiveUpdatesController::class, 'status'])->name('live-status');
    Route::get('/ringkasan', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/reservasi/ekspor', [ReservationController::class, 'export'])->name('reservations.export');
    Route::get('/reservasi', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservasi/{reservation}', [ReservationController::class, 'show'])->whereNumber('reservation')->name('reservations.show');
    Route::get('/riwayat-aktivitas', [ActivityController::class, 'index'])->name('activities.index');

    Route::get('/event-promosi', [OutletContentController::class, 'index'])->name('events.index');
    Route::post('/event-promosi', [OutletContentController::class, 'store'])->name('events.store');
    Route::put('/event-promosi/{outletContent}', [OutletContentController::class, 'update'])->name('events.update');

    Route::get('/outlet', [OutletController::class, 'index'])->name('outlets.index');
    Route::put('/outlet/{outlet}/status', [OutletController::class, 'toggle'])->name('outlets.toggle');

    Route::get('/meja', [TableController::class, 'index'])->name('tables.index');
    Route::post('/meja', [TableController::class, 'store'])->name('tables.store');
    Route::put('/meja/{table}', [TableController::class, 'update'])->name('tables.update');
    Route::delete('/meja/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->name('menu.update');
    Route::post('/menu/kategori', [MenuController::class, 'storeCategory'])->name('menu-categories.store');

    Route::post('/denah/{area}', [FloorPlanController::class, 'store'])->name('floorplan.store');
    Route::delete('/denah/{area}', [FloorPlanController::class, 'destroy'])->name('floorplan.destroy');

    // Persetujuan pembatalan
    Route::get('/persetujuan-batal', [CancellationApprovalController::class, 'index'])->name('cancellations.index');
    Route::post('/persetujuan-batal/{cancellationRequest}/setujui', [CancellationApprovalController::class, 'approve'])->name('cancellations.approve');
    Route::post('/persetujuan-batal/{cancellationRequest}/tolak', [CancellationApprovalController::class, 'reject'])->name('cancellations.reject');

    // Tarif dan layanan
    Route::get('/tarif-layanan', [TaxServiceRateController::class, 'index'])->name('tax-service.index');
    Route::post('/tarif-layanan/{outlet}', [TaxServiceRateController::class, 'update'])->name('tax-service.update');

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/admin.php';
