<?php

/*
|--------------------------------------------------------------------------
| Terjemahan otomatis (Bahasa Indonesia -> bahasa lain)
|--------------------------------------------------------------------------
|
| Teks di file Blade cukup ditulis dalam Bahasa Indonesia. Saat pengunjung memilih
| bahasa lain (mis. English), middleware App\Http\Middleware\AutoTranslate akan
| menerjemahkan teks halaman secara otomatis, lalu menyimpan hasilnya di
| storage/app/translations/{locale}.json sehingga halaman berikutnya langsung cepat.
|
| Fitur/menu baru TIDAK perlu lagi ditambahkan ke lang/en atau lang/id.
| File lang/* yang sudah ada tetap dipakai dan diutamakan.
|
| Menandai teks agar tidak diterjemahkan: tambahkan atribut translate="no"
| atau data-notranslate pada elemen HTML-nya.
|
| Mengoreksi terjemahan: edit langsung storage/app/translations/en.json
| (hapus barisnya untuk menerjemahkan ulang).
|
*/

return [
    // Bahasa asli teks di file Blade.
    'source' => 'id',

    // Bahasa tujuan yang diterjemahkan otomatis.
    'locales' => ['en'],

    // mymemory (gratis tanpa API key) | google_free | google_cloud (butuh GOOGLE_TRANSLATE_KEY) | libretranslate | none
    'driver' => env('TRANSLATE_DRIVER', 'mymemory'),

    'mymemory_email' => env('MYMEMORY_EMAIL'),
    'google_key' => env('GOOGLE_TRANSLATE_KEY'),
    'libretranslate_url' => env('LIBRETRANSLATE_URL', 'https://libretranslate.com'),
    'libretranslate_key' => env('LIBRETRANSLATE_KEY'),

    // Batas waktu per permintaan (detik) dan jumlah teks baru yang diterjemahkan per halaman.
    'timeout' => 5,
    'max_per_request' => 60,
    'concurrency' => 4,
];
