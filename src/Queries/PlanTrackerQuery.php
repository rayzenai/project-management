<?php

namespace RayzenAI\ProjectManagement\Queries;

use Illuminate\Http\Request;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Project;

/**
 * Assembles the 100-Day-Plan tracker payload shared by the Inertia web view and
 * the JSON API feed. Single source of truth for the plan project, its
 * item-number-ordered tasks, and the government metadata maps the view needs.
 */
class PlanTrackerQuery
{
    /**
     * @return array<string, mixed>
     */
    public function get(Request $request): array
    {
        $project = Project::query()->where('slug', '100-day-plan')->firstOrFail();

        $tasks = $project->tasks()
            ->with(['assignments.member', 'project'])
            ->orderByRaw("CAST(metadata->>'item_number' AS INTEGER) ASC NULLS LAST")
            ->get();

        return [
            'project' => (new ProjectResource($project))->resolve(),
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'categories' => config('government.categories', []),
            'statusMap' => config('project-management.statuses', []),
            'deadlineTypes' => config('government.deadline_types', []),
        ];
    }
}
