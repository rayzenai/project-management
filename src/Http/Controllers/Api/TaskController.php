<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreTaskRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTaskRequest;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Queries\TaskShowQuery;
use RayzenAI\ProjectManagement\Services\Workspace\CreateTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateTaskService;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * JSON sibling of the Workspace\TaskController. Reuses the same FormRequests
 * (authorization), action services (ServiceResult), JsonResources, and read
 * query as the web surface — the only difference is the response shape.
 */
class TaskController extends Controller
{
    use RespondsWithServiceResult;

    public function show(Request $request, Project $project, Task $task, TaskShowQuery $query): JsonResponse
    {
        abort_unless(WorkspaceAccess::canViewProject($request->user(), $project), 403);

        abort_unless($task->project_id === $project->id, 404);

        return response()->json($query->data($project, $task, $request->user()->id));
    }

    public function store(StoreTaskRequest $request, Project $project, CreateTaskService $service): JsonResponse
    {
        $result = $service->execute($project, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof Task ? new TaskResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task, UpdateTaskService $service): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $result = $service->execute($task, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof Task ? new TaskResource($result->data) : null,
        );
    }

    public function destroy(Project $project, Task $task, DeleteTaskService $service): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $result = $service->execute($task);

        return $this->respondWithResult($result);
    }

    public function restore(Project $project, Task $task, RestoreWorkspaceModel $service): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $result = $service->execute($task);

        return $this->respondWithResult(
            $result,
            $result->data instanceof Task ? new TaskResource($result->data) : null,
        );
    }
}
