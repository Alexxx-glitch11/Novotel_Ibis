<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ReservationQrToken;
use App\Services\ReservationAvailability;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancePaymentController extends Controller
{
    public function __construct(private readonly ReservationAvailability $availability)
    {
    }

    public function index(): View
    {
        $payments = Payment::with(['reservation.outlet', 'reservation.tables.area'])
            ->whereIn('payment_status', ['pending_verification', 'rejected'])
            ->latest('proof_uploaded_at')
            ->paginate(20);

        return view('finance.payments.index', compact('payments'));
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_file_path && Storage::disk('local')->exists($payment->proof_file_path), 404);

        return Storage::disk('local')->response($payment->proof_file_path);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($request, $payment): void {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($locked->payment_status === 'pending_verification', 409);
            $reservation = $locked->reservation()->lockForUpdate()->firstOrFail();
            $table = $reservation->tables()->with('area')->first();
            if (! $table || ! $this->availability->availableTables(
                $reservation->outlets_id,
                $table->areas_id,
                $reservation->reservation_date->toDateString(),
                $reservation->start_time,
                $reservation->end_time,
                (int) $reservation->guest_count,
                (int) $reservation->reservations_id,
            )->contains('tables_id', (int) $table->tables_id)) {
                throw ValidationException::withMessages(['payment' => 'Meja ini sudah tidak tersedia pada jadwal tersebut. Hubungi pelanggan sebelum menyetujui pembayaran.']);
            }

            $locked->update([
                'payment_status' => 'paid',
                'users_id_verified_by' => $request->user()->getAuthIdentifier(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);
            $reservation->update(['reservation_status' => 'confirmed']);

            $qrToken = $reservation->qrToken()->first();
            if ($qrToken) {
                $qrToken->update(['is_active' => true]);
            } else {
                ReservationQrToken::create([
                    'reservations_id' => $reservation->reservations_id,
                    'token' => Str::random(64),
                    'valid_from' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->start_time, config('reservation.timezone')),
                    'valid_until' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->end_time, config('reservation.timezone')),
                    'is_active' => true,
                ]);
            }
        });

        return back()->with('success', 'Pembayaran diterima dan reservasi dikonfirmasi.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $payment, $data): void {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($locked->payment_status === 'pending_verification', 409);
            $locked->update([
                'payment_status' => 'rejected',
                'users_id_verified_by' => $request->user()->getAuthIdentifier(),
                'verified_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        });

        return back()->with('success', 'Bukti ditolak. Pelanggan dapat mengunggah ulang bukti pembayaran.');
    }
}
