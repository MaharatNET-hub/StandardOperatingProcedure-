<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\QaReview;
use App\Models\QaReviewItem;
use App\Models\Signoff;
use Illuminate\Http\Request;

class QaReviewController extends Controller
{
    public function index(Request $request, Project $project)
    {
        return $project->qaReviews()->with('reviewer:id,name')->latest()->get();
    }

    public function latest(Request $request, Project $project)
    {
        $review = $project->qaReviews()
            ->with(['reviewer:id,name', 'items.projectChecklistItem.checklistItem.category'])
            ->latest()
            ->first();

        return response()->json($review);
    }

    public function store(Request $request, Project $project)
    {
        if ($project->qaReviews()->where('status', QaReview::STATUS_IN_PROGRESS)->exists()) {
            abort(422, 'يوجد مراجعة جودة قيد التنفيذ بالفعل لهذا المشروع.');
        }

        $review = $project->qaReviews()->create([
            'reviewer_id' => $request->user()->id,
            'status' => QaReview::STATUS_IN_PROGRESS,
        ]);

        foreach ($project->checklistItems()->pluck('id') as $checklistItemId) {
            $review->items()->create(['project_checklist_item_id' => $checklistItemId]);
        }

        $project->update(['status' => Project::STATUS_IN_REVIEW]);

        ActivityLog::log($project->id, $request->user()->id, 'qa_review_started');

        return response()->json($review->load(['items.projectChecklistItem.checklistItem.category']), 201);
    }

    public function updateItem(Request $request, QaReview $qaReview, QaReviewItem $qaReviewItem)
    {
        if ($qaReviewItem->qa_review_id !== $qaReview->id) {
            abort(404);
        }

        $data = $request->validate([
            'verdict' => ['required', 'in:pass,fail'],
            'comment' => ['nullable', 'string'],
        ]);

        $qaReviewItem->update($data);

        return $qaReviewItem->fresh();
    }

    public function submit(Request $request, QaReview $qaReview)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,changes_requested,rejected'],
            'overall_notes' => ['nullable', 'string'],
        ]);

        $qaReview->update([
            'status' => $data['status'],
            'overall_notes' => $data['overall_notes'] ?? null,
            'submitted_at' => now(),
        ]);

        $project = $qaReview->project;

        if ($data['status'] === QaReview::STATUS_APPROVED) {
            $project->update(['status' => Project::STATUS_APPROVED]);

            Signoff::updateOrCreate(
                ['project_id' => $project->id, 'role' => Signoff::ROLE_REVIEWED],
                [
                    'user_id' => $request->user()->id,
                    'signature_name' => $request->user()->name,
                    'signed_at' => now(),
                ]
            );
        } else {
            $project->update(['status' => Project::STATUS_CHANGES_REQUESTED]);
        }

        ActivityLog::log($project->id, $request->user()->id, 'qa_review_submitted', "نتيجة المراجعة: {$data['status']}");

        return $qaReview->fresh(['items', 'reviewer:id,name']);
    }
}
