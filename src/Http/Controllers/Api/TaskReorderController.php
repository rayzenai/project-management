<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Services\Workspace\ReorderTasksService;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * JSON sibling of the Workspace\TaskReorderController. Reuses the same inline
 * validation and the ReorderTasksService — the only difference is the response
 * shape.
 */
class TaskReorderController extends Controller
{
    use RespondsWithServiceResult;

    public function __invoke(Request $request, Project $project, ReorderTasksService $service): JsonResponse
    {
        abort_unless(WorkspaceAccess::canViewProject($request->user(), $project), 403);

        $data = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer'],
            'status' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $service->execute($project, $data['task_ids'], $data['status'] ?? null);

        return $this->respondWithResult($result);
    }
}
