<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Project;

/**
 * Specialized 100-Day-Plan tracker view. Filters the workspace down to tasks
 * that carry plan-specific metadata (item_number) and surfaces the government
 * fields the public site cares about.
 */
class PlanTrackerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $project = Project::query()->where('slug', '100-day-plan')->firstOrFail();

        $tasks = $project->tasks()
            ->with(['assignments.user', 'project'])
            ->orderByRaw("CAST(metadata->>'item_number' AS INTEGER) ASC NULLS LAST")
            ->get();

        return Inertia::render('PlanTracker', [
            'project' => (new ProjectResource($project))->resolve(),
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'categories' => config('government.categories', []),
            'statuses' => config('project-management.statuses', []),
            'deadlineTypes' => config('government.deadline_types', []),
        ]);
    }
}
