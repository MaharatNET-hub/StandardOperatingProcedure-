<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\Request;

class PerformanceReportController extends Controller
{
    public function index(Request $request, Project $project)
    {
        return $project->performanceReports()->with('measurer:id,name')->orderBy('measured_at')->get();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'stage' => ['required', 'in:original_template,final_site'],
            'lighthouse_mobile' => ['nullable', 'integer', 'min:0', 'max:100'],
            'lighthouse_desktop' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pagespeed_url' => ['nullable', 'url'],
            'screaming_frog_report_url' => ['nullable', 'url'],
            'plugin_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['required', 'date'],
        ]);

        $report = $project->performanceReports()->create([
            ...$data,
            'measured_by' => $request->user()->id,
        ]);

        ActivityLog::log($project->id, $request->user()->id, 'performance_report_added', "تقرير أداء ({$data['stage']})");

        return response()->json($report->load('measurer:id,name'), 201);
    }

    public function destroy(Request $request, Project $project, \App\Models\PerformanceReport $performanceReport)
    {
        if ($performanceReport->project_id !== $project->id) {
            abort(404);
        }

        $performanceReport->delete();

        return response()->json(['message' => 'تم حذف التقرير.']);
    }
}
