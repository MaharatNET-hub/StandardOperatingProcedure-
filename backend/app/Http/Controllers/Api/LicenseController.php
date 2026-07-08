<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request, Project $project)
    {
        return $project->licenses()->with('renewalResponsible:id,name')->get();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'type' => ['required', 'in:elementor_pro,astra_pro,other'],
            'registered_email' => ['required', 'email'],
            'expiry_date' => ['nullable', 'date'],
            'renewal_responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $license = $project->licenses()->create($data);

        ActivityLog::log($project->id, $request->user()->id, 'license_added', "ترخيص: {$data['type']}");

        return response()->json($license->load('renewalResponsible:id,name'), 201);
    }

    public function update(Request $request, Project $project, \App\Models\License $license)
    {
        if ($license->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validate([
            'type' => ['sometimes', 'in:elementor_pro,astra_pro,other'],
            'registered_email' => ['sometimes', 'email'],
            'expiry_date' => ['nullable', 'date'],
            'renewal_responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $license->update($data);

        return $license->fresh('renewalResponsible:id,name');
    }

    public function destroy(Project $project, \App\Models\License $license)
    {
        if ($license->project_id !== $project->id) {
            abort(404);
        }
        $license->delete();

        return response()->json(['message' => 'تم الحذف.']);
    }
}
