<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\CheckIn;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\OutletAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveUpdatesController extends Controller
{
    public function status(Request $request, OutletAccess $outletAccess): JsonResponse
    {
        $user = $request->user();
        $isGlobal = $outletAccess->isGlobal($user);
        $allowedOutletIds = $isGlobal ? null : $outletAccess->outletIds($user);

        $requestedOutlet = $request->query('outlet');
        $filterOutletId = null;
        if (is_numeric($requestedOutlet) && (int) $requestedOutlet > 0) {
            $candidate = (int) $requestedOutlet;
            if ($isGlobal || in_array($candidate, $allowedOutletIds ?? [], true)) {
                $filterOutletId = $candidate;
            }
        }

        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $today = now($tz)->toDateString();

        $scopeReservation = function (Builder $query) use ($outletAccess, $user, $filterOutletId): void {
            $outletAccess->scope($query, $user);
            if ($filterOutletId !== null) {
                $query->where('outlets_id', $filterOutletId);
            }
        };

        $resBase = Reservation::query()->where('reservation_status', '!=', 'draft');
        $scopeReservation($resBase);

        $resAgg = (clone $resBase)
            ->selectRaw('COALESCE(MAX(reservations_id), 0) as max_id, MAX(updated_at) as max_updated, COUNT(*) as total')
            ->first();

        $resReadyCount = (clone $resBase)
            ->whereDate('reservation_date', '>=', $today)
            ->whereIn('reservation_status', ['confirmed', 'partially_arrived'])
            ->count();

        $resSig = sprintf('%s:%s:%s:%s', $resAgg->max_id ?? 0, $resAgg->max_updated ?? '', $resAgg->total ?? 0, $resReadyCount);

        $payBase = Payment::query();
        if ($filterOutletId !== null) {
            $payBase->whereHas('reservation', fn (Builder $q) => $q->where('outlets_id', $filterOutletId));
        } else {
            $outletAccess->scope($payBase, $user, 'reservation');
        }

        $payAgg = (clone $payBase)
            ->selectRaw('COALESCE(MAX(payments_id), 0) as max_id, MAX(updated_at) as max_updated, COUNT(*) as total')
            ->first();

        $payPendingCount = (clone $payBase)
            ->where('payment_status', 'pending_verification')
            ->count();

        $payVerifiedCount = (clone $payBase)
            ->whereIn('payment_status', ['paid', 'rejected'])
            ->count();

        $paySig = sprintf('%s:%s:%s:%s', $payAgg->max_id ?? 0, $payAgg->max_updated ?? '', $payPendingCount, $payVerifiedCount);

        $cancelBase = CancellationRequest::query();
        if ($filterOutletId !== null) {
            $cancelBase->whereHas('reservation', fn (Builder $q) => $q->where('outlets_id', $filterOutletId));
        } else {
            $outletAccess->scope($cancelBase, $user, 'reservation');
        }

        $cancelAgg = (clone $cancelBase)
            ->selectRaw('COALESCE(MAX(cancellation_requests_id), 0) as max_id, MAX(reviewed_at) as max_reviewed, COUNT(*) as total')
            ->first();

        $cancelPendingCount = (clone $cancelBase)
            ->where('status', 'pending')
            ->count();

        $cancelSig = sprintf('%s:%s:%s:%s', $cancelAgg->max_id ?? 0, $cancelAgg->max_reviewed ?? '', $cancelPendingCount, $cancelAgg->total ?? 0);

        $checkinBase = CheckIn::query();
        if ($filterOutletId !== null) {
            $checkinBase->whereHas('reservation', fn (Builder $q) => $q->where('outlets_id', $filterOutletId));
        } else {
            $outletAccess->scope($checkinBase, $user, 'reservation');
        }

        $checkinAgg = (clone $checkinBase)
            ->selectRaw('COALESCE(MAX(check_ins_id), 0) as max_id, COUNT(*) as total')
            ->first();

        $checkinSig = sprintf('%s:%s', $checkinAgg->max_id ?? 0, $checkinAgg->total ?? 0);

        $auditBase = AuditLog::query();
        $outletAccess->scopeAudit($auditBase, $user);

        $auditAgg = (clone $auditBase)
            ->selectRaw('COALESCE(MAX(audit_logs_id), 0) as max_id, COUNT(*) as total')
            ->first();

        $auditSig = sprintf('%s:%s', $auditAgg->max_id ?? 0, $auditAgg->total ?? 0);

        $dashboardSig = md5(implode('|', [$resSig, $paySig, $cancelSig, $checkinSig]));

        $channels = [
            'dashboard' => [
                'signature' => $dashboardSig,
                'pending_count' => $payPendingCount + $cancelPendingCount,
            ],
            'reservations' => [
                'signature' => $resSig,
                'pending_count' => $resReadyCount,
                'total' => (int) ($resAgg->total ?? 0),
            ],
            'payments' => [
                'signature' => $paySig,
                'pending_count' => $payPendingCount,
                'total' => (int) ($payAgg->total ?? 0),
            ],
            'payments_history' => [
                'signature' => sprintf('%s:%s', $payVerifiedCount, $payAgg->max_updated ?? ''),
                'pending_count' => 0,
            ],
            'reports' => [
                'signature' => md5($resSig . '|' . $paySig),
                'pending_count' => 0,
            ],
            'cancellations' => [
                'signature' => $cancelSig,
                'pending_count' => $cancelPendingCount,
                'total' => (int) ($cancelAgg->total ?? 0),
            ],
            'checkins' => [
                'signature' => md5($checkinSig . '|' . $resSig),
                'pending_count' => 0,
                'total' => (int) ($checkinAgg->total ?? 0),
            ],
            'audit' => [
                'signature' => $auditSig,
                'pending_count' => 0,
                'total' => (int) ($auditAgg->total ?? 0),
            ],
        ];

        return response()->json([
            'overall_signature' => md5(json_encode($channels)),
            'channels' => $channels,
            'server_time' => now($tz)->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
