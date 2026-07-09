<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChecklistCategory;
use Illuminate\Http\Request;

class ChecklistCategoryController extends Controller
{
    public function index()
    {
        return ChecklistCategory::with('items')
            ->orderBy('group')
            ->orderBy('order')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'in:quality,seo,media,launch'],
            'code' => ['required', 'string', 'max:20', 'unique:checklist_categories,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'rule_note' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['order'] ??= (ChecklistCategory::max('order') ?? 0) + 1;

        $category = ChecklistCategory::create($data);

        ActivityLog::log(null, $request->user()->id, 'checklist_category_created', "قسم جديد: {$category->name_ar}");

        return response()->json($category, 201);
    }

    public function update(Request $request, ChecklistCategory $checklistCategory)
    {
        $data = $request->validate([
            'group' => ['sometimes', 'in:quality,seo,media,launch'],
            'code' => ['sometimes', 'string', 'max:20', 'unique:checklist_categories,code,'.$checklistCategory->id],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'rule_note' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('order', $data) && $data['order'] === null) {
            unset($data['order']);
        }

        $checklistCategory->update($data);

        ActivityLog::log(null, $request->user()->id, 'checklist_category_updated', "تعديل قسم: {$checklistCategory->name_ar}");

        return $checklistCategory->fresh();
    }

    public function destroy(Request $request, ChecklistCategory $checklistCategory)
    {
        if ($checklistCategory->items()->exists()) {
            abort(422, 'لا يمكن حذف قسم يحتوي على بنود — احذف كل البنود منه أولاً.');
        }

        $name = $checklistCategory->name_ar;
        $checklistCategory->delete();

        ActivityLog::log(null, $request->user()->id, 'checklist_category_deleted', "حذف قسم: {$name}");

        return response()->json(['message' => 'تم حذف القسم.']);
    }
}
