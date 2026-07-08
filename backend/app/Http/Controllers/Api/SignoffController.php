<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Signoff;
use Illuminate\Http\Request;

class SignoffController extends Controller
{
    public function store(Request $request, Project $project)
    {
        if ($project->status !== Project::STATUS_APPROVED) {
            abort(422, 'لا يمكن الاعتماد النهائي قبل موافقة مراجعة الجودة (QA).');
        }

        $signoff = Signoff::updateOrCreate(
            ['project_id' => $project->id, 'role' => Signoff::ROLE_FINAL_APPROVAL],
            [
                'user_id' => $request->user()->id,
                'signature_name' => $request->user()->name,
                'signed_at' => now(),
            ]
        );

        $project->update(['status' => Project::STATUS_DELIVERED]);

        ActivityLog::log($project->id, $request->user()->id, 'project_final_signoff', 'اعتماد نهائي وتسليم المشروع');

        return response()->json($signoff);
    }
}
