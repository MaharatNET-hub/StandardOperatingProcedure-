<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with(['user:id,name', 'project:id,name']);

        $user = $request->user();
        if ($user->role === User::ROLE_DEVELOPER) {
            $query->whereHas('project.developers', fn ($q) => $q->where('users.id', $user->id));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        return $query->latest()->paginate(25);
    }
}
