<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PluginRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class PluginRequestController extends Controller
{
    public function index(Request $request, Project $project)
    {
        return $project->pluginRequests()->with(['requester:id,name', 'decider:id,name'])->latest()->get();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'plugin_name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'environment' => ['required', 'in:staging,live'],
        ]);

        $pluginRequest = $project->pluginRequests()->create([
            ...$data,
            'requested_by' => $request->user()->id,
            'status' => PluginRequest::STATUS_PENDING,
        ]);

        ActivityLog::log($project->id, $request->user()->id, 'plugin_request_created', "طلب إضافة: {$data['plugin_name']}");

        return response()->json($pluginRequest, 201);
    }

    public function decide(Request $request, PluginRequest $pluginRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'decision_notes' => ['nullable', 'string'],
        ]);

        $pluginRequest->update([
            'status' => $data['status'],
            'decision_notes' => $data['decision_notes'] ?? null,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        ActivityLog::log($pluginRequest->project_id, $request->user()->id, 'plugin_request_decided', "{$pluginRequest->plugin_name}: {$data['status']}");

        return $pluginRequest->fresh(['requester:id,name', 'decider:id,name']);
    }
}
