<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChecklistController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $items = $project->checklistItems()
            ->with(['checklistItem.category', 'checker:id,name', 'evidence.uploader:id,name'])
            ->get()
            ->groupBy(fn ($item) => $item->checklistItem->category->code)
            ->map(function ($items, $code) {
                $category = $items->first()->checklistItem->category;

                return [
                    'code' => $category->code,
                    'group' => $category->group,
                    'name_ar' => $category->name_ar,
                    'rule_note' => $category->rule_note,
                    'items' => $items->sortBy(fn ($i) => $i->checklistItem->order)->values(),
                    'progress' => [
                        'total' => $items->count(),
                        'done' => $items->where('status', 'done')->count(),
                    ],
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function update(Request $request, Project $project, ProjectChecklistItem $item)
    {
        if ($item->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:pending,done,na'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->status = $data['status'];
        $item->notes = $data['notes'] ?? $item->notes;

        if ($data['status'] === 'pending') {
            $item->checked_by = null;
            $item->checked_at = null;
        } else {
            $item->checked_by = $request->user()->id;
            $item->checked_at = now();
        }
        $item->save();

        ActivityLog::log($project->id, $request->user()->id, 'checklist_item_updated', "بند #{$item->checklist_item_id}: {$data['status']}");

        return $item->fresh(['checklistItem.category', 'checker:id,name', 'evidence']);
    }

    public function storeEvidence(Request $request, Project $project, ProjectChecklistItem $item)
    {
        if ($item->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validate([
            'type' => ['required', 'in:file,link'],
            'file' => ['required_if:type,file', 'file', 'max:10240'],
            'url' => ['required_if:type,link', 'url'],
        ]);

        $evidence = new Evidence([
            'type' => $data['type'],
            'uploaded_by' => $request->user()->id,
        ]);

        if ($data['type'] === 'file') {
            $path = $request->file('file')->store('evidence', 'public');
            $evidence->path = $path;
            $evidence->original_name = $request->file('file')->getClientOriginalName();
        } else {
            $evidence->url = $data['url'];
        }

        $item->evidence()->save($evidence);

        ActivityLog::log($project->id, $request->user()->id, 'evidence_added', "دليل جديد على بند #{$item->checklist_item_id}");

        return response()->json($evidence, 201);
    }

    public function destroyEvidence(Request $request, Evidence $evidence)
    {
        if ($evidence->path) {
            Storage::disk('public')->delete($evidence->path);
        }
        $evidence->delete();

        return response()->json(['message' => 'تم حذف الدليل.']);
    }
}
