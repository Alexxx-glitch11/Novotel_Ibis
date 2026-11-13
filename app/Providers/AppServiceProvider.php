<?php

namespace App\Providers;

use App\Events\DashboardUpdated;
use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $notify = function () {
            // Kirim setelah transaksi database selesai, supaya dashboard tidak membaca data setengah jadi.
            DB::afterCommit(function () {
                try {
                    event(new DashboardUpdated());
                } catch (\Throwable $e) {
                    // Reverb mati tidak boleh mengganggu pelanggan yang sedang reservasi.
                    report($e);
                }
            });
        };

        foreach ([Reservation::class, Payment::class, CancellationRequest::class, AuditLog::class] as $model) {
            $model::created($notify);
            $model::updated($notify);
            $model::deleted($notify);
        }
    }
}