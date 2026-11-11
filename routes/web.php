<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Sementara, nanti diganti controller form reservasi
Route::get('/reservasi/{outlet}', function (string $outlet) {
    abort_unless(in_array($outlet, ['novotel', 'ibis']), 404);
    return 'Form reservasi untuk outlet: ' . ucfirst($outlet);
})->name('reservasi.outlet');

Route::get('/bahasa/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['id', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');