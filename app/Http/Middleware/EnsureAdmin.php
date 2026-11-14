<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('admin.login'));
        }

        abort_unless($request->user()->is_active && $request->user()->hasRole('admin'), 403);

        $routeName = (string) $request->route()?->getName();
        $permission = match (true) {
            $routeName === 'admin.dashboard' => 'admin.dashboard.view',
            $routeName === 'admin.outlets.index' => 'admin.outlets.view',
            in_array($routeName, ['admin.outlets.store', 'admin.areas.store'], true) => 'admin.outlets.manage',
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
