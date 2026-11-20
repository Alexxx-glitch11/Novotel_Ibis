<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalRole
{
    public function handle(Request $request, Closure $next, string $guard, string $roles): Response
    {
        $authGuard = Auth::guard($guard);

        if (! $authGuard->check()) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse($guard);
        $user = $authGuard->user();
        $allowedRoles = array_filter(explode('|', $roles));

        abort_unless($user && $user->is_active && $user->hasAnyRole($allowedRoles), 403);
        if (! $user->hasRole('admin') && ! app(\App\Services\OutletAccess::class)->outletIds($user)) {
            $landingRoutes = [
                'finance.dashboard', 'finance.live-status', 'finance.logout',
                'manager.dashboard', 'manager.live-status', 'manager.logout',
                'cashier.dashboard', 'cashier.live-status', 'cashier.logout',
            ];
            abort_unless($request->routeIs(...$landingRoutes), 403, 'Akun portal belum ditetapkan ke outlet.');
        }

        return $next($request);
    }
}
