<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Services\Workspace\ReorderTasksService;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

class TaskReorderController extends Controller
{
    use RedirectsWithServiceResult;

    public function __invoke(Request $request, Project $project, ReorderTasksService $service): RedirectResponse
    {
        abort_unless(WorkspaceAccess::canViewProject($request->user(), $project), 403);

        $data = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer'],
            'status' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $service->execute($project, $data['task_ids'], $data['status'] ?? null);

        return $this->redirectWithResult($result);
    }
}
