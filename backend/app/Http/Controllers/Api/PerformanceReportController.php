<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Services\PageSpeedService;
use Illuminate\Http\Request;
use RuntimeException;

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

    public function runPageSpeed(Request $request, Project $project, PageSpeedService $pageSpeed)
    {
        $data = $request->validate([
            'url' => ['nullable', 'url'],
            'stage' => ['required', 'in:original_template,final_site'],
        ]);

        $url = $data['url'] ?? $project->site_url;
        if (! $url) {
            abort(422, 'لا يوجد رابط موقع محدد — أضف رابط الموقع للمشروع أو أدخله يدوياً.');
        }

        try {
            $mobile = $pageSpeed->analyze($url, 'mobile');
            $desktop = $pageSpeed->analyze($url, 'desktop');
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        $report = $project->performanceReports()->create([
            'stage' => $data['stage'],
            'source_url' => $url,
            'is_automated' => true,
            'lighthouse_mobile' => $mobile['score'],
            'lighthouse_desktop' => $desktop['score'],
            'issues' => ['mobile' => $mobile['issues'], 'desktop' => $desktop['issues']],
            'measured_by' => $request->user()->id,
            'measured_at' => now(),
        ]);

        ActivityLog::log($project->id, $request->user()->id, 'performance_report_added', "فحص PageSpeed تلقائي ({$data['stage']})");

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
