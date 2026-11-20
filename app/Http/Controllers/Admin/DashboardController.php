<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\OutletContent;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Services\OperationalDashboardService;
use App\Services\ReservationSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        OperationalDashboardService $dashboard,
        ReservationSettings $settings,
    ): View {
        $ops = $dashboard->build($request, true);
        $ids = $ops['effectiveOutletIds'];
        $selectedOutletId = $ops['selected']?->outlets_id;

        return view('admin.dashboard', array_merge($ops, [
            'outletParams' => $selectedOutletId ? ['outlet' => $selectedOutletId] : [],
            'outletCount' => $ops['selected'] ? 1 : $ops['outlets']->count(),
            'areaCount' => Area::whereIn('outlets_id', $ids)->count(),
            'tableCount' => Table::whereIn('outlets_id', $ids)->count(),
            'menuCount' => MenuItem::where('is_available', true)->whereIn('outlets_id', $ids)->count(),
            'userCount' => User::count(),
            'reservationCount' => Reservation::whereIn('outlets_id', $ids)->count(),
            'contentCount' => OutletContent::whereIn('outlets_id', $ids)->count(),
            'holdMinutes' => $settings->holdMinutes(),
            'bankName' => $settings->bankName(),
            'recentLogs' => AuditLog::with('user.roles')->latest('created_at')->latest('audit_logs_id')->limit(5)->get(),
        ]));
    }
}
