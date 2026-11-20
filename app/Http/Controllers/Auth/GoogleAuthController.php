<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = $this->resolveRedirectUri($request);

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Login Google belum dikonfigurasi. Tambahkan GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada file .env.']);
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Login dengan Google dibatalkan atau ditolak.']);
        }

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState) || ! $request->filled('code')) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Sesi login Google tidak valid atau kedaluwarsa. Silakan coba lagi.']);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->resolveRedirectUri($request),
            'grant_type' => 'authorization_code',
            'code' => $request->query('code'),
        ]);

        if (! $tokenResponse->successful() || ! $tokenResponse->json('access_token')) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Gagal memverifikasi otorisasi Google.']);
        }

        $userInfoResponse = Http::withToken($tokenResponse->json('access_token'))
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        $googleEmail = strtolower(trim((string) $userInfoResponse->json('email', '')));
        $emailVerified = (bool) $userInfoResponse->json('email_verified', false);

        if (! $userInfoResponse->successful() || $googleEmail === '' || ! $emailVerified) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Google tidak memiliki email terverifikasi.']);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$googleEmail])->first();

        if (! $user) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => "Email Google ({$googleEmail}) belum terdaftar di program. Hubungi Admin untuk mendaftarkan akun Anda."]);
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Anda sedang dinonaktifkan. Hubungi Admin.']);
        }

        $portal = LoginController::portalFor($user);
        if (! $portal) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => "Akun {$googleEmail} belum memiliki role akses yang terdaftar."]);
        }

        $csrfToken = $request->session()->token();
        Auth::guard($portal['guard'])->login($user, true);
        $request->session()->put('_token', $csrfToken);

        return redirect()->route($portal['route']);
    }

    private function resolveRedirectUri(Request $request): string
    {
        $configured = (string) config('services.google.redirect');
        if ($configured !== '' && str_starts_with($configured, 'http')) {
            return $configured;
        }

        return $request->getSchemeAndHttpHost() . '/auth/google/callback';
    }
}
