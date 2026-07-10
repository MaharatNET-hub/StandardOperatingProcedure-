<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProjectReportController extends Controller
{
    public function pdf(Request $request, Project $project)
    {
        $user = $request->user();
        if ($user->role === \App\Models\User::ROLE_DEVELOPER
            && ! $project->developers()->where('users.id', $user->id)->exists()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع.');
        }

        $project->load([
            'creator:id,name',
            'developers:id,name,role',
            'currentPhase',
            'signoffs.user:id,name',
        ]);

        $categories = $project->checklistItems()
            ->with('checklistItem.category')
            ->get()
            ->groupBy(fn ($item) => $item->checklistItem->category->code)
            ->map(function ($items) {
                $category = $items->first()->checklistItem->category;

                return [
                    'code' => $category->code,
                    'name_ar' => $category->name_ar,
                    'total' => $items->count(),
                    'done' => $items->where('status', 'done')->count(),
                    'na' => $items->where('status', 'na')->count(),
                ];
            })
            ->sortBy('code')
            ->values();

        $totalItems = $categories->sum('total');
        $doneItems = $categories->sum('done');

        $performanceReports = $project->performanceReports()->orderBy('measured_at')->get();
        $originalReport = $performanceReports->firstWhere('stage', 'original_template');
        $finalReport = $performanceReports->where('stage', 'final_site')->last();

        $logoBase64 = base64_encode(file_get_contents(resource_path('images/logo.png')));

        $pdf = Pdf::loadView('reports.project', [
            'project' => $project,
            'categories' => $categories,
            'totalItems' => $totalItems,
            'doneItems' => $doneItems,
            'overallPercent' => $totalItems > 0 ? round(($doneItems / $totalItems) * 100) : 0,
            'originalReport' => $originalReport,
            'finalReport' => $finalReport,
            'logoBase64' => $logoBase64,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream('project-'.$project->id.'-report.pdf');
    }
}
