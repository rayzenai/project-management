<?php

namespace RayzenAI\ProjectManagement\Queries;

use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Http\Resources\TeamResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Team;

/**
 * Assembles the single-project payload (project, its tasks, and the team list)
 * shared by the Inertia web show page and the JSON API show endpoint.
 */
class ProjectShowQuery
{
    /**
     * @return array{project: array<string, mixed>, tasks: array<int, array<string, mixed>>, teams: array<int, array<string, mixed>>}
     */
    public function data(Project $project): array
    {
        $project->load(['teams:id', 'tasks' => fn ($q) => $q->with('assignments.member')->withCount(['notes', 'contacts'])]);

        return [
            'project' => (new ProjectResource($project))->resolve(),
            'tasks' => TaskResource::collection($project->tasks)->resolve(),
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get())->resolve(),
        ];
    }
}
