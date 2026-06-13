<?php

namespace RayzenAI\ProjectManagement\Queries;

use Illuminate\Http\Request;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Models\Project;

/**
 * Assembles the project list payload shared by the Inertia web index and the
 * JSON API index. Single source of truth for active/archived filtering and the
 * archived-count badge.
 */
class ProjectIndexQuery
{
    /**
     * @return array{projects: array<int, array<string, mixed>>, archivedView: bool, archivedCount: int}
     */
    public function data(Request $request): array
    {
        $archivedView = $request->boolean('archived');

        $projects = Project::query()
            ->when($archivedView, fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->withCount('tasks')
            ->orderBy('title')
            ->get();

        return [
            'projects' => ProjectResource::collection($projects)->resolve(),
            'archivedView' => $archivedView,
            'archivedCount' => Project::query()->archived()->count(),
        ];
    }
}
