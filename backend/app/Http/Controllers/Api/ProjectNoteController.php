<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Http\Request;

/**
 * قاعدة بيانات الملاحظات (SRS §19–22): سجل تاريخي مستقل لكل ما يخص المشروع
 * من ملاحظات العميل، التحديثات، الاجتماعات، القرارات، والملاحظات الداخلية.
 * كل ملاحظة سجل جديد — لا يتم استبدال ملاحظة قديمة عند إضافة ملاحظة أحدث.
 */
class ProjectNoteController extends Controller
{
    /**
     * All Notes / Notes by Project (SRS §21): كل الملاحظات مرتبة من الأحدث
     * للأقدم، مع إمكانية الفلترة حسب المشروع أو النوع أو الحالة.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProjectNote::query()
            ->with(['author:id,name', 'project:id,name,client_name']);

        if (! $user->hasPermission('view_all_projects')) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('primary_developer_id', $user->id)
                    ->orWhereHas('developers', fn ($qq) => $qq->where('users.id', $user->id));
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $search)->orWhere('body', 'like', $search));
        }

        $perPage = min($request->integer('per_page', 30), 100);

        // ترتيب ثابت: الأحدث أولاً، ثم حسب المعرّف حتى لا تختلط الملاحظات
        // المضافة في نفس الثانية.
        return $query->latest()->latest('id')->paginate($perPage)->withQueryString();
    }

    /** ملاحظات مشروع واحد بترتيب زمني (الأحدث أولاً). */
    public function forProject(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        return response()->json([
            'data' => $project->notes()->with('author:id,name')->latest()->latest('id')->get(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', ProjectNote::TYPES)],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'in:'.implode(',', ProjectNote::STATUSES)],
        ]);

        $note = $project->notes()->create([
            ...$data,
            'status' => $data['status'] ?? 'new',
            'created_by' => $request->user()->id,
        ]);

        // ملاحظات العميل تحدّث حالة الـ Feedback على المشروع نفسه حتى تنعكس
        // مباشرة على الأولوية ولوحة متابعة العملاء.
        if ($note->type === 'client_feedback' && $note->status !== 'done') {
            $project->update([
                'client_feedback_status' => 'new',
                'client_feedback_at' => now(),
            ]);
        }

        ActivityLog::log($project->id, $request->user()->id, 'project_note_added', "ملاحظة جديدة: {$note->title}");

        return response()->json($note->load('author:id,name'), 201);
    }

    public function update(Request $request, ProjectNote $projectNote)
    {
        $this->authorizeProjectAccess($request, $projectNote->project);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:'.implode(',', ProjectNote::TYPES)],
            'body' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:'.implode(',', ProjectNote::STATUSES)],
        ]);

        $projectNote->update($data);

        return $projectNote->fresh('author:id,name');
    }

    public function destroy(Request $request, ProjectNote $projectNote)
    {
        if (! $request->user()->hasPermission('manage_projects')) {
            abort(403, 'حذف الملاحظات متاح لمن يملك صلاحية إدارة المشاريع فقط.');
        }

        $projectNote->delete();

        return response()->json(['message' => 'تم حذف الملاحظة.']);
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        $user = $request->user();
        if (! $user->hasPermission('view_all_projects')
            && ! $project->developers()->where('users.id', $user->id)->exists()
            && $project->primary_developer_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع.');
        }
    }
}
