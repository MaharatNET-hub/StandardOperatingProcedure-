<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReport;
use App\Models\PluginRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();
        $user = $request->user();
        if (! $user->hasPermission('view_all_projects')) {
            $query->whereHas('developers', fn ($q) => $q->where('users.id', $user->id));
        }

        $projectsByStatus = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $avgLighthouseMobile = PerformanceReport::where('stage', PerformanceReport::STAGE_FINAL_SITE)
            ->whereNotNull('lighthouse_mobile')
            ->avg('lighthouse_mobile');

        $avgPluginCount = PerformanceReport::where('stage', PerformanceReport::STAGE_FINAL_SITE)
            ->whereNotNull('plugin_count')
            ->avg('plugin_count');

        return response()->json([
            'projects_total' => (clone $query)->count(),
            'projects_by_status' => $projectsByStatus,
            'avg_lighthouse_mobile' => $avgLighthouseMobile ? round($avgLighthouseMobile, 1) : null,
            'lighthouse_target' => 85,
            'avg_plugin_count' => $avgPluginCount ? round($avgPluginCount, 1) : null,
            'plugin_count_target' => 10,
            'pending_plugin_requests' => PluginRequest::where('status', PluginRequest::STATUS_PENDING)->count(),
            'projects_in_review' => (clone $query)->where('status', Project::STATUS_IN_REVIEW)->count(),
            'projects_changes_requested' => (clone $query)->where('status', Project::STATUS_CHANGES_REQUESTED)->count(),
            'recent_activity' => \App\Models\ActivityLog::with(['user:id,name', 'project:id,name'])
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }
}
