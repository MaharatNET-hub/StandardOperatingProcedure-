<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    public function update(Request $request, Project $project, ProjectPhase $phase)
    {
        if ($phase->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed'],
        ]);

        $phase->status = $data['status'];
        if ($data['status'] === 'in_progress' && ! $phase->started_at) {
            $phase->started_at = now();
        }
        if ($data['status'] === 'completed') {
            $phase->completed_at = now();

            // Auto-advance: move the project's current phase pointer + start the next phase.
            $next = $project->projectPhases()->with('phase')->get()
                ->sortBy(fn ($p) => $p->phase->order)
                ->skipUntil(fn ($p) => $p->id === $phase->id)
                ->skip(1)
                ->first();

            if ($next) {
                $next->update(['status' => 'in_progress', 'started_at' => now()]);
                $project->update(['current_phase_id' => $next->phase_id]);
            }
        }
        $phase->save();

        ActivityLog::log($project->id, $request->user()->id, 'phase_status_changed', "المرحلة #{$phase->phase_id}: {$data['status']}");

        return $phase->fresh('phase');
    }
}
