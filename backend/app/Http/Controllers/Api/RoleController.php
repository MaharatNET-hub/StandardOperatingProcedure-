<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return Role::query()
            ->orderByDesc('is_system')
            ->orderBy('label_ar')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'key' => $role->key,
                'label_ar' => $role->label_ar,
                'permissions' => $role->permissions,
                'is_system' => $role->is_system,
                'users_count' => User::where('role', $role->key)->count(),
            ]);
    }

    public function permissions()
    {
        return collect(Role::PERMISSIONS)
            ->map(fn ($label, $key) => ['key' => $key, 'label_ar' => $label])
            ->values();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,key'],
            'label_ar' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(Role::PERMISSIONS))],
        ]);

        $role = Role::create([
            ...$data,
            'permissions' => $data['permissions'] ?? [],
            'is_system' => false,
        ]);

        ActivityLog::log(null, $request->user()->id, 'role_created', "تم إنشاء دور جديد: {$role->label_ar}");

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'label_ar' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [Rule::in(array_keys(Role::PERMISSIONS))],
        ]);

        if (isset($data['permissions'])) {
            foreach (['manage_users', 'manage_roles'] as $lockoutPermission) {
                $losesPermission = in_array($lockoutPermission, $role->permissions ?? [], true)
                    && ! in_array($lockoutPermission, $data['permissions'], true);

                if ($losesPermission && $this->usersWithPermissionElsewhere($lockoutPermission, $role->key) === 0) {
                    abort(422, 'لا يمكن سحب هذه الصلاحية — سيصبح لا أحد قادراً على إدارة الأعضاء أو الأدوار في النظام.');
                }
            }
        }

        $role->update($data);

        ActivityLog::log(null, $request->user()->id, 'role_updated', "تم تعديل الدور: {$role->label_ar}");

        return $role->fresh();
    }

    public function destroy(Request $request, Role $role)
    {
        if ($role->is_system) {
            abort(422, 'لا يمكن حذف الأدوار الأساسية في النظام.');
        }

        if (User::where('role', $role->key)->exists()) {
            abort(422, 'لا يمكن حذف دور مرتبط بأعضاء حالياً. غيّر دور هؤلاء الأعضاء أولاً.');
        }

        $label = $role->label_ar;
        $role->delete();

        ActivityLog::log(null, $request->user()->id, 'role_deleted', "تم حذف الدور: {$label}");

        return response()->json(['message' => 'تم حذف الدور.']);
    }

    /**
     * Count users who'd still hold the given permission via a role other than $excludeRoleKey.
     */
    private function usersWithPermissionElsewhere(string $permission, string $excludeRoleKey): int
    {
        $roleKeys = Role::query()
            ->where('key', '!=', $excludeRoleKey)
            ->get(['key', 'permissions'])
            ->filter(fn (Role $role) => in_array($permission, $role->permissions ?? [], true))
            ->pluck('key');

        return User::whereIn('role', $roleKeys)->count();
    }
}
