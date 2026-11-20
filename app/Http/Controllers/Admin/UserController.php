<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['outlet', 'outlets', 'roles.permissions'])->orderBy('name')->paginate(8)->withQueryString();
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
            'outlet_scope' => ['nullable', 'string'],
            'outlets_id' => ['nullable', 'array', 'max:2'],
            'outlets_id.*' => ['required', 'integer', 'distinct', 'exists:outlets,outlets_id'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);
        $role = Role::where('guard_name', 'web')->findOrFail($data['role_id']);
        abort_unless(in_array($role->name, ['admin', 'manager', 'finance', 'kasir', 'cashier'], true), 403);
        $outletIds = $this->resolveOutletIds($data['outlet_scope'] ?? null, $data['outlets_id'] ?? []);
        if ($role->name !== 'admin' && ! $outletIds) {
            return back()->withErrors(['outlet_scope' => 'Pilih satu outlet atau Semua outlet untuk akun non-admin.'])->withInput();
        }

        $plainPassword = filled($data['password'] ?? null) ? $data['password'] : Str::random(32);

        $user = DB::transaction(function () use ($data, $role, $request, $audit, $outletIds, $plainPassword): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'phone_number' => $data['phone_number'] ?? null,
                'outlets_id' => $role->name === 'admin' ? null : ($outletIds[0] ?? null),
                'password' => $plainPassword,
                'is_active' => true,
            ]);
            $user->assignRole($role);
            $user->outlets()->sync($role->name === 'admin' ? [] : $outletIds);
            $audit->record($request, 'admin_user_created', 'user', $user->users_id, null, ['name' => $user->name, 'email' => $user->email, 'role' => $role->name, 'outlets_ids' => $role->name === 'admin' ? [] : $outletIds, 'is_active' => true]);

            return $user;
        });

        return back()->with('success', 'Pengguna berhasil ditambahkan. Password tidak dicatat dalam audit log.');
    }

    public function updateOutlet(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'outlet_scope' => ['nullable', 'string'],
            'outlets_id' => ['nullable', 'array', 'max:2'],
            'outlets_id.*' => ['required', 'integer', 'distinct', 'exists:outlets,outlets_id'],
        ]);
        $outletIds = $this->resolveOutletIds($data['outlet_scope'] ?? null, $data['outlets_id'] ?? []);
        abort_if(! $user->hasRole('admin') && ! $outletIds, 422, 'Pilih satu outlet atau Semua outlet untuk akun non-admin.');

        DB::transaction(function () use ($request, $user, $outletIds, $audit): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->users_id);
            $oldOutletIds = app(\App\Services\OutletAccess::class)->outletIds($locked);
            $newOutletIds = $locked->hasRole('admin') ? [] : $outletIds;
            $locked->outlets()->sync($newOutletIds);
            $locked->update(['outlets_id' => $newOutletIds[0] ?? null]);
            $audit->record($request, 'admin_user_outlet_changed', 'user', $locked->users_id,
                ['outlets_ids' => $oldOutletIds], ['outlets_ids' => $newOutletIds]);
        });

        return back()->with('success', 'Outlet akun berhasil diperbarui.');
    }

    /** @param array<int, int|string> $ids @return list<int> */
    private function resolveOutletIds(?string $scope, array $ids): array
    {
        if ($scope === 'all') {
            return Outlet::ordered()->pluck('outlets_id')->map(fn ($id) => (int) $id)->all();
        }

        if ($scope !== null && is_numeric($scope) && (int) $scope > 0) {
            return $this->orderedOutletIds([(int) $scope]);
        }

        return $this->orderedOutletIds($ids);
    }

    /** @param array<int, int|string> $ids @return list<int> */
    private function orderedOutletIds(array $ids): array
    {
        $selected = array_values(array_unique(array_map('intval', $ids)));

        return Outlet::ordered()->whereIn('outlets_id', $selected)->pluck('outlets_id')->map(fn ($id) => (int) $id)->all();
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
