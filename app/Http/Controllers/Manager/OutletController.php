<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
{
    // Reservasi yang masih berjalan (dihitung sebagai "mendatang" bila tanggalnya belum lewat)
    private const LIVE = ['pending_payment', 'confirmed', 'partially_arrived', 'checked_in'];

    public function index()
    {
        $outlets = Outlet::ordered()
            ->withCount([
                'tables',
                'reservations as upcoming_count' => fn ($q) => $q
                    ->whereIn('reservation_status', self::LIVE)
                    ->whereDate('reservation_date', '>=', now()->toDateString()),
            ])
            ->get();

        return view('manager.outlets.index', compact('outlets'));
    }

    public function toggle(Request $request, Outlet $outlet): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        $new = $request->boolean('is_active');
        $old = (bool) $outlet->is_active;

        if ($new !== $old) {
            DB::transaction(function () use ($outlet, $new, $old, $request) {
                $outlet->update(['is_active' => $new]);

                // Perubahan status outlet tercatat (kewajiban audit PRD).
                DB::table('audit_logs')->insert([
                    'users_id'    => auth()->id(),
                    'action'      => $new ? 'outlet_activated' : 'outlet_deactivated',
                    'entity_type' => 'outlet',
                    'entity_id'   => $outlet->outlets_id,
                    'old_values'  => json_encode(['is_active' => $old]),
                    'new_values'  => json_encode(['is_active' => $new, 'code' => $outlet->code]),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => (string) $request->userAgent(),
                    'created_at'  => now(),
                ]);
            });
        }

        return redirect()
            ->route('manager.outlets.index')
            ->with('success', __($new ? 'manager.out_msg_on' : 'manager.out_msg_off', ['name' => $outlet->name]));
    }
}