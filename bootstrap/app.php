<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\AutoTranslate::class,
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'portal' => \App\Http\Middleware\EnsurePortalRole::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        //
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                // Form usang (token CSRF tidak cocok), mis. halaman dibiarkan terbuka lama.
                if ($request->routeIs('login.store')) {
                    return redirect()
                        ->route('login')
                        ->withInput($request->only('email'))
                        ->withErrors(['email' => 'Halaman login sudah kedaluwarsa. Silakan coba masuk sekali lagi.']);
                }

                return redirect()
                    ->back(fallback: route('login'))
                    ->withErrors(['session' => 'Halaman sudah kedaluwarsa dan telah dimuat ulang. Silakan ulangi tindakan Anda.']);
            }
        });
    })->create();
