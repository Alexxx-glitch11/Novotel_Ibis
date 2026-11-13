<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CancellationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $selectedOutletId = $request->has('outlet') ? (int) $request->query('outlet') : 0;
        $statusFilter = $request->query('status', 'pending');

        $query = CancellationRequest::with([
            'reservation.outlet',
            'reservation.tables.area',
            'reservation.latestPayment',
            'reviewedBy',
        ]);

        if ($selectedOutletId > 0) {
            $query->whereHas('reservation', function ($q) use ($selectedOutletId) {
                $q->where('outlets_id', $selectedOutletId);
            });
        }

        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $cancellations = $query->latest('created_at')->get();

        // Cari item yang sedang dipilih (default item pertama jika tidak ditentukan)
        $selectedId = (int) $request->query('selected');
        $selectedRequest = $cancellations->firstWhere('cancellation_requests_id', $selectedId)
            ?? $cancellations->first();

        // Hitung badge total pending untuk notifikasi
        $pendingCount = CancellationRequest::whereHas('reservation', function ($q) use ($selectedOutletId) {
            if ($selectedOutletId) {
                $q->where('outlets_id', $selectedOutletId);
            }
        })->where('status', 'pending')->count();

        return view('manager.cancellations.index', compact(
            'outlets',
            'selectedOutletId',
            'statusFilter',
            'cancellations',
            'selectedRequest',
            'pendingCount'
        ));
    }

    public function approve(Request $request, CancellationRequest $cancellationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $cancellationRequest, $validated): void {
            /** @var CancellationRequest $locked */
            $locked = CancellationRequest::whereKey($cancellationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($locked->status === 'pending', 409, 'Permintaan pembatalan ini sudah diproses sebelumnya.');

            $reservation = $locked->reservation()->lockForUpdate()->firstOrFail();

            $oldValues = [
                'cancellation_status' => $locked->status,
                'reservation_status' => $reservation->reservation_status,
            ];

            // 1. Perbarui status pembatalan
            $locked->update([
                'status' => 'approved',
                'users_id_reviewed_by' => $request->user()?->users_id,
                'reviewed_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            // 2. Batalkan status reservasi agar meja otomatis dilepaskan
            $reservation->update([
                'reservation_status' => 'cancelled',
            ]);

            // 3. Nonaktifkan QR Code tiket jika ada
            $qrToken = $reservation->qrToken()->first();
            if ($qrToken) {
                $qrToken->update(['is_active' => false]);
            }

            // 4. Catat Audit Log
            AuditLog::create([
                'users_id' => $request->user()?->users_id,
                'action' => 'cancellation_approved',
                'entity_type' => 'cancellation_request',
                'entity_id' => $locked->cancellation_requests_id,
                'old_values' => $oldValues,
                'new_values' => [
                    'cancellation_status' => 'approved',
                    'reservation_status' => 'cancelled',
                    'decision_note' => $validated['decision_note'] ?? null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('manager.cancellations.index', [
            'selected' => $cancellationRequest->cancellation_requests_id,
        ])->with('success', 'Permintaan pembatalan berhasil disetujui. Reservasi telah dibatalkan dan meja telah dilepas.');
    }

    public function reject(Request $request, CancellationRequest $cancellationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'max:1000'],
        ], [
            'decision_note.required' => 'Mohon sertakan alasan penolakan pembatalan.',
        ]);

        DB::transaction(function () use ($request, $cancellationRequest, $validated): void {
            /** @var CancellationRequest $locked */
            $locked = CancellationRequest::whereKey($cancellationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($locked->status === 'pending', 409, 'Permintaan pembatalan ini sudah diproses sebelumnya.');

            $oldStatus = $locked->status;

            // 1. Perbarui status pembatalan menjadi ditolak
            $locked->update([
                'status' => 'rejected',
                'users_id_reviewed_by' => $request->user()?->users_id,
                'reviewed_at' => now(),
                'decision_note' => $validated['decision_note'],
            ]);

            // 2. Catat Audit Log
            AuditLog::create([
                'users_id' => $request->user()?->users_id,
                'action' => 'cancellation_rejected',
                'entity_type' => 'cancellation_request',
                'entity_id' => $locked->cancellation_requests_id,
                'old_values' => ['cancellation_status' => $oldStatus],
                'new_values' => [
                    'cancellation_status' => 'rejected',
                    'decision_note' => $validated['decision_note'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('manager.cancellations.index', [
            'selected' => $cancellationRequest->cancellation_requests_id,
        ])->with('success', 'Permintaan pembatalan telah ditolak. Reservasi tetap aktif.');
    }
}
