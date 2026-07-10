<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::select('id', 'name', 'email', 'role')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,key'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'exists:roles,key'],
        ]);

        if (isset($data['role']) && $data['role'] !== $user->role
            && $user->id === $request->user()->id
            && $user->hasPermission('manage_users')
            && $this->usersWithPermission('manage_users', excludeUserId: $user->id) === 0) {
            abort(422, 'لا يمكنك سحب صلاحية إدارة الأعضاء عن حسابك الخاص وأنت آخر من يملكها.');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user->fresh()->only(['id', 'name', 'email', 'role']);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'لا يمكنك حذف حسابك الخاص.');
        }

        if ($user->hasPermission('manage_users') && $this->usersWithPermission('manage_users', excludeUserId: $user->id) === 0) {
            abort(422, 'لا يمكن حذف آخر عضو يملك صلاحية إدارة الأعضاء في النظام.');
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف العضو.']);
    }

    /**
     * Count users (excluding the given one) whose role grants the given permission.
     */
    private function usersWithPermission(string $permission, ?int $excludeUserId = null): int
    {
        $roleKeys = Role::query()
            ->get(['key', 'permissions'])
            ->filter(fn (Role $role) => in_array($permission, $role->permissions ?? [], true))
            ->pluck('key');

        return User::whereIn('role', $roleKeys)
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->count();
    }
}
