<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');
        if (! $guard->check()) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse('admin');
        $user = $guard->user();
        abort_unless($user && $user->is_active && $user->hasRole('admin'), 403);

        $routeName = (string) $request->route()?->getName();
        $permission = match (true) {
            $routeName === 'admin.dashboard' => 'admin.dashboard.view',
            $routeName === 'admin.live-status' => 'admin.dashboard.view',
            in_array($routeName, ['admin.reservations.index', 'admin.reservations.show'], true) => 'admin.reservations.view',
            $routeName === 'admin.outlets.index' => 'admin.outlets.view',
            in_array($routeName, ['admin.outlets.store', 'admin.outlets.toggle', 'admin.areas.store'], true) => 'admin.outlets.manage',
            $routeName === 'admin.tables.index' => 'admin.tables.view',
            str_starts_with($routeName, 'admin.tables.') => 'admin.tables.manage',
            $routeName === 'admin.menu.index' => 'admin.menu.view',
            str_starts_with($routeName, 'admin.menu.') => 'admin.menu.manage',
            $routeName === 'admin.users.index' => 'admin.users.view',
            str_starts_with($routeName, 'admin.users.') => 'admin.users.manage',
            $routeName === 'admin.content.index' => 'admin.content.view',
            str_starts_with($routeName, 'admin.content.') => 'admin.content.manage',
            $routeName === 'admin.configuration.index' => 'admin.configuration.view',
            $routeName === 'admin.configuration.store' => 'admin.configuration.propose',
            $routeName === 'admin.audit.index' => 'admin.audit.view',
            default => null,
        };

        abort_unless($permission && $request->user()->can($permission), 403);

        return $next($request);
    }
}
