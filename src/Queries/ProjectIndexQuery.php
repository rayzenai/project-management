<?php

namespace RayzenAI\ProjectManagement\Queries;

use Illuminate\Http\Request;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\TeamResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * Assembles the project list payload shared by the Inertia web index and the
 * JSON API index. Single source of truth for visibility scoping, active/archived
 * filtering, the archived-count badge, and the create-form context.
 */
class ProjectIndexQuery
{
    /**
     * @return array<string, mixed>
     */
    public function data(Request $request): array
    {
        $user = $request->user();
        $archivedView = $request->boolean('archived');

        $projects = Project::query()
            ->visibleTo($user)
            ->when($archivedView, fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->withCount('tasks')
            ->orderBy('title')
            ->get();

        $assignableTeams = WorkspaceAccess::isSuperAdmin($user)
            ? Team::query()->orderBy('name')->get()
            : Team::query()->whereIn('id', WorkspaceAccess::ledTeamIds($user))->orderBy('name')->get();

        return [
            'projects' => ProjectResource::collection($projects)->resolve(),
            'archivedView' => $archivedView,
            'archivedCount' => Project::query()->visibleTo($user)->archived()->count(),
            'assignableTeams' => TeamResource::collection($assignableTeams)->resolve(),
            'canCreate' => WorkspaceAccess::canCreateProject($user),
            'isSuperAdmin' => WorkspaceAccess::isSuperAdmin($user),
        ];
    }
}
