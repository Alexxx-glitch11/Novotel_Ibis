<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function create(): Response
    {
        // Jangan di-cache browser: form lama (tab/back button) membawa token CSRF yang sudah usang.
        return response()
            ->view('auth.portal-login', [
                'title' => 'Masuk Sistem',
                'subtitle' => 'Masuk dengan akun yang terdaftar; halaman akan diarahkan sesuai role Anda.',
                'action' => route('login.store'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($credentials['email']))])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], (string) $user->password)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        $portal = self::portalFor($user);

        if (! $portal) {
            return back()->withErrors(['email' => 'Akun ini belum memiliki role akses yang terdaftar.'])->onlyInput('email');
        }

        // Laravel mengganti token CSRF setiap login. Karena satu browser sering membuka beberapa portal
        // (Admin/Manager/Finance/Kasir) pada sesi yang sama, token dipertahankan agar form di tab lain
        // tidak ditolak (419) dan login tidak perlu diulang dua kali.
        $csrfToken = $request->session()->token();
        Auth::guard($portal['guard'])->login($user, $request->boolean('remember'));
        $request->session()->put('_token', $csrfToken);

        return redirect()->route($portal['route']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $guard = match (true) {
            $request->is('admin/*') => 'admin',
            $request->is('manager/*') => 'manager',
            $request->is('finance/*') => 'finance',
            $request->is('cashier/*') => 'cashier',
            default => null,
        };

        if ($guard) {
            Auth::guard($guard)->logout();
        } else {
            foreach (['admin', 'manager', 'finance', 'cashier', 'web'] as $g) {
                Auth::guard($g)->logout();
            }
        }

        return redirect()->route('login');
    }

    /**
     * @return array{guard: string, route: string}|null
     */
    public static function portalFor(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return match (true) {
            $user->hasRole('admin') => ['guard' => 'admin', 'route' => 'admin.dashboard'],
            $user->hasRole('manager') => ['guard' => 'manager', 'route' => 'manager.dashboard'],
            $user->hasRole('finance') => ['guard' => 'finance', 'route' => 'finance.dashboard'],
            $user->hasAnyRole(['cashier', 'kasir']) => ['guard' => 'cashier', 'route' => 'cashier.dashboard'],
            default => null,
        };
    }
}
