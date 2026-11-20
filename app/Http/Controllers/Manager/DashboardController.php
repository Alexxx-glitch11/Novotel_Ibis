<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\OperationalDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, OperationalDashboardService $dashboard): View
    {
        return view('manager.dashboard', $dashboard->build($request, false));
    }
}
