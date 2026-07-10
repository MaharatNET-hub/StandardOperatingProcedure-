<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Services\ScreamingFrogImportService;
use Illuminate\Http\Request;
use RuntimeException;

class SeoAuditController extends Controller
{
    public function index(Request $request, Project $project)
    {
        return $project->seoAudits()->with('importer:id,name')->latest()->get();
    }

    public function store(Request $request, Project $project, ScreamingFrogImportService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        try {
            $parsed = $service->parse($request->file('file')->getRealPath());
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        $linkedCount = 0;
        foreach ($parsed['checks'] as &$check) {
            $checklistItem = ChecklistItem::whereHas(
                'category',
                fn ($q) => $q->where('code', $check['category_code'])
            )->where('order', $check['order'])->first();

            if (! $checklistItem) {
                continue;
            }

            $projectItem = $project->checklistItems()
                ->firstOrCreate(['checklist_item_id' => $checklistItem->id]);

            $note = '[فحص آلي - Screaming Frog] '.$check['summary'];

            if ($check['status'] === 'pass') {
                $projectItem->update([
                    'status' => $projectItem->status === 'na' ? 'na' : 'done',
                    'checked_by' => $projectItem->checked_by ?? $request->user()->id,
                    'checked_at' => $projectItem->checked_at ?? now(),
                    'notes' => $note,
                ]);
            } else {
                $projectItem->update(['notes' => $note]);
            }

            $check['checklist_item_id'] = $checklistItem->id;
            $linkedCount++;
        }
        unset($check);

        $audit = $project->seoAudits()->create([
            'source_filename' => $request->file('file')->getClientOriginalName(),
            'total_urls' => $parsed['total_urls'],
            'results' => $parsed['checks'],
            'imported_by' => $request->user()->id,
        ]);

        ActivityLog::log(
            $project->id,
            $request->user()->id,
            'seo_audit_imported',
            "استيراد Screaming Frog: {$parsed['total_urls']} رابط، {$linkedCount} بند مرتبط بقائمة التحقق"
        );

        return response()->json($audit->load('importer:id,name'), 201);
    }
}
