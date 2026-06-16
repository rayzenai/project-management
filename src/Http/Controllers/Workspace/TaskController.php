<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreTaskRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTaskRequest;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Queries\TaskShowQuery;
use RayzenAI\ProjectManagement\Services\Workspace\CreateTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateTaskService;

class TaskController extends Controller
{
    use RedirectsWithServiceResult;

    public function show(Project $project, Task $task, TaskShowQuery $query): Response
    {
        abort_unless($task->project_id === $project->id, 404);

        return Inertia::render('Tasks/Show', $query->data($project, $task, request()->user()->id));
    }

    public function store(StoreTaskRequest $request, Project $project, CreateTaskService $service): RedirectResponse
    {
        $result = $service->execute($project, $request->validated());

        if ($result->success && $result->data instanceof Task) {
            return redirect()
                ->route('workspace.tasks.show', ['project' => $project->slug, 'task' => $result->data->slug])
                ->with('workspace_flash', ['success' => true, 'message' => $result->message]);
        }

        return $this->redirectWithResult($result);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task, UpdateTaskService $service): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $result = $service->execute($task, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function destroy(Project $project, Task $task, DeleteTaskService $service): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $result = $service->execute($task);

        if ($result->success) {
            return redirect()
                ->route('workspace.projects.show', ['project' => $project->slug])
                ->with('workspace_flash', [
                    'success' => true,
                    'message' => $result->message,
                    'undo' => [
                        'label' => 'Undo',
                        'url' => route('workspace.tasks.restore', [$project, $task]),
                    ],
                ]);
        }

        return $this->redirectWithResult($result);
    }

    public function restore(Project $project, Task $task, RestoreWorkspaceModel $service): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $service->execute($task);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
