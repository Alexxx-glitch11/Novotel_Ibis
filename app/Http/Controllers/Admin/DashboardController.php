<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Table;
use App\Models\User;
use Illuminate\View\View;
use App\Models\OutletContent;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'outletCount' => Outlet::where('is_active', true)->count(),
            'areaCount' => Area::count(),
            'tableCount' => Table::count(),
            'menuCount' => MenuItem::where('is_available', true)->whereHas('outlet', fn ($query) => $query->where('is_active', true))->count(),
            'userCount' => User::count(),
            'contentCount' => OutletContent::count(),
            'roleCount' => User::query()->with('roles')->get()->pluck('roles')->flatten()->pluck('id')->unique()->count(),
            'recentLogs' => AuditLog::with('user.roles')->latest('created_at')->latest('audit_logs_id')->limit(3)->get(),
        ]);
    }
}
