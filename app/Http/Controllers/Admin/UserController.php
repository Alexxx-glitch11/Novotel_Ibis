<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['outlet', 'roles.permissions'])->orderBy('name')->paginate(8)->withQueryString();
        $roles = Role::query()->where('guard_name', 'web')->whereIn('name', ['admin', 'manager', 'finance', 'kasir', 'cashier'])->orderBy('name')->get();
        $outlets = Outlet::ordered()->get();
        $roleDescriptions = [
            'kasir' => 'Jadwal, QR, check-in, permintaan batal',
            'cashier' => 'Jadwal, QR, check-in, permintaan batal',
            'finance' => 'Verifikasi dan laporan pembayaran',
            'manager' => 'Persetujuan batal, tarif, laporan',
            'admin' => 'Master data, pengguna, konfigurasi',
        ];

        return view('admin.users', compact('users', 'roles', 'outlets', 'roleDescriptions'));
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'outlets_id' => ['nullable', 'integer', 'exists:outlets,outlets_id'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $role = Role::where('guard_name', 'web')->findOrFail($data['role_id']);
        abort_unless(in_array($role->name, ['admin', 'manager', 'finance', 'kasir', 'cashier'], true), 403);

        $user = DB::transaction(function () use ($data, $role, $request, $audit): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'outlets_id' => $data['outlets_id'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole($role);
            $audit->record($request, 'admin_user_created', 'user', $user->users_id, null, ['name' => $user->name, 'email' => $user->email, 'role' => $role->name, 'outlets_id' => $user->outlets_id, 'is_active' => true]);

            return $user;
        });

        return back()->with('success', 'Pengguna berhasil ditambahkan. Password tidak dicatat dalam audit log.');
    }

    public function toggle(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $user, $audit): string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->users_id);
            if ($request->user()->is($lockedUser)) return 'self';

            $old = (bool) $lockedUser->is_active;
            $new = ! $old;
            if ($old && $lockedUser->hasRole('admin')) {
                $activeAdmins = User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'admin')->where('guard_name', 'web'))
                    ->lockForUpdate()
                    ->get(['users_id'])
                    ->count();
                if ($activeAdmins <= 1) return 'last-admin';
            }

            $lockedUser->update(['is_active' => $new]);
            $audit->record($request, 'admin_user_status_changed', 'user', $lockedUser->users_id, ['is_active' => $old], ['is_active' => $new]);

            return $new ? 'activated' : 'deactivated';
        });

        return match ($result) {
            'self' => back()->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.'),
            'last-admin' => back()->with('error', 'Admin aktif terakhir tidak dapat dinonaktifkan.'),
            'activated' => back()->with('success', 'Akun diaktifkan.'),
            default => back()->with('success', 'Akun dinonaktifkan.'),
        };
    }
}
