<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChecklistCategory;
use App\Models\ChecklistItem;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChecklistItemController extends Controller
{
    public function store(Request $request, ChecklistCategory $checklistCategory)
    {
        $data = $request->validate([
            'text_ar' => ['required', 'string'],
            'is_critical' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = DB::transaction(function () use ($data, $checklistCategory) {
            $item = $checklistCategory->items()->create([
                'text_ar' => $data['text_ar'],
                'is_critical' => $data['is_critical'] ?? false,
                'order' => $data['order'] ?? (($checklistCategory->items()->max('order') ?? 0) + 1),
            ]);

            // Backfill this new item onto every existing project so it shows
            // up immediately without needing to reseed or recreate projects.
            $rows = Project::pluck('id')->map(fn ($projectId) => [
                'project_id' => $projectId,
                'checklist_item_id' => $item->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($rows) {
                DB::table('project_checklist_items')->insert($rows);
            }

            return $item;
        });

        ActivityLog::log(null, $request->user()->id, 'checklist_item_created', "بند جديد في \"{$checklistCategory->name_ar}\": {$item->text_ar}");

        return response()->json($item->load('category'), 201);
    }

    public function update(Request $request, ChecklistItem $checklistItem)
    {
        $data = $request->validate([
            'checklist_category_id' => ['sometimes', 'integer', 'exists:checklist_categories,id'],
            'text_ar' => ['sometimes', 'string'],
            'is_critical' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('order', $data) && $data['order'] === null) {
            unset($data['order']);
        }

        $checklistItem->update($data);

        ActivityLog::log(null, $request->user()->id, 'checklist_item_updated', "تعديل بند: {$checklistItem->text_ar}");

        return $checklistItem->fresh('category');
    }

    public function destroy(Request $request, ChecklistItem $checklistItem)
    {
        $text = $checklistItem->text_ar;
        $checklistItem->delete();

        ActivityLog::log(null, $request->user()->id, 'checklist_item_deleted', "حذف بند: {$text}");

        return response()->json(['message' => 'تم حذف البند من كل المشاريع.']);
    }
}
