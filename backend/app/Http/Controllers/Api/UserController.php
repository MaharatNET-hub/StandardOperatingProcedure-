<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'role' => ['required', 'in:admin,developer,qa_reviewer,it_specialist'],
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
            'role' => ['sometimes', 'in:admin,developer,qa_reviewer,it_specialist'],
        ]);

        if (isset($data['role']) && $data['role'] !== User::ROLE_ADMIN
            && $user->isAdmin() && $user->id === $request->user()->id) {
            abort(422, 'لا يمكنك سحب صلاحية المدير عن حسابك الخاص.');
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

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            abort(422, 'لا يمكن حذف آخر حساب مدير في النظام.');
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف العضو.']);
    }
}
