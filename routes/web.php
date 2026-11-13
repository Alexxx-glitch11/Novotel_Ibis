<?php

use App\Http\Controllers\Manager\CancellationApprovalController;
use App\Http\Controllers\Manager\FloorPlanController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\TableController;
use App\Http\Controllers\Manager\TaxServiceRateController;
use App\Http\Controllers\FinanceLoginController;
use App\Http\Controllers\FinancePaymentController;
use App\Http\Controllers\TableSelectionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Halaman pelanggan: pilih meja (nama route sama, dipakai tombol di beranda)
Route::get('/reservasi/{outlet}', [TableSelectionController::class, 'show'])->name('reservasi.outlet');
Route::post('/reservasi/{outlet}/jadwal', [TableSelectionController::class, 'findTables'])->name('reservasi.schedule');
Route::post('/reservasi/{outlet}/meja/hold', [TableSelectionController::class, 'holdTable'])->name('reservasi.hold');
Route::post('/reservasi/{outlet}/menu', [TableSelectionController::class, 'saveMenu'])->name('reservasi.menu');
Route::post('/reservasi/{outlet}/konfirmasi', [TableSelectionController::class, 'confirm'])->name('reservasi.confirm');
Route::get('/reservasi/{outlet}/selesai/{code}', [TableSelectionController::class, 'confirmation'])->name('reservasi.confirmation');
Route::get('/reservasi/{outlet}/selesai/{code}/qr', [TableSelectionController::class, 'downloadQr'])->name('reservasi.qr-download');
Route::post('/reservasi/{outlet}/selesai/{code}/bukti-pembayaran', [TableSelectionController::class, 'uploadPaymentProof'])->name('reservasi.payment-proof');

Route::get('/finance/login', [FinanceLoginController::class, 'create'])->name('finance.login');
Route::get('/login', fn () => redirect()->route('finance.login'))->name('login');
Route::post('/finance/login', [FinanceLoginController::class, 'store'])->middleware('throttle:6,1')->name('finance.login.store');
Route::middleware(['auth', 'role:finance|admin'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/pembayaran', [FinancePaymentController::class, 'index'])->name('payments.index');
    Route::get('/pembayaran/{payment}/bukti', [FinancePaymentController::class, 'proof'])->name('payments.proof');
    Route::post('/pembayaran/{payment}/terima', [FinancePaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/pembayaran/{payment}/tolak', [FinancePaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/logout', [FinanceLoginController::class, 'destroy'])->name('logout');
});

Route::get('/bahasa/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');

// Halaman manager.
// TODO: setelah login siap, tambahkan ->middleware(['auth', 'role:manager|admin'])
Route::prefix('manager')->name('manager.')->group(function () {
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

    Route::get('/persetujuan-batal', [CancellationApprovalController::class, 'index'])->name('cancellations.index');
    Route::post('/persetujuan-batal/{cancellationRequest}/setujui', [CancellationApprovalController::class, 'approve'])->name('cancellations.approve');
    Route::post('/persetujuan-batal/{cancellationRequest}/tolak', [CancellationApprovalController::class, 'reject'])->name('cancellations.reject');

    Route::get('/tarif-layanan', [TaxServiceRateController::class, 'index'])->name('tax-service.index');
    Route::post('/tarif-layanan/{outlet}', [TaxServiceRateController::class, 'update'])->name('tax-service.update');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
});
