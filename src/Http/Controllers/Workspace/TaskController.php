<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreTaskRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTaskRequest;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\CreateTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteTaskService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateTaskService;

class TaskController extends Controller
{
    use RedirectsWithServiceResult;

    public function show(Project $project, Task $task): Response
    {
        abort_unless($task->project_id === $project->id, 404);

        $task->load(['assignments.member', 'notes.user', 'contacts', 'project']);

        $team = Member::assignableFor($project)->get(['id', 'name', 'email', 'user_id']);

        $mySubtasks = Subtask::query()
            ->where('task_id', $task->id)
            ->where('user_id', request()->user()->id)
            ->orderBy('is_done')
            ->orderBy('position')
            ->get();

        return Inertia::render('Tasks/Show', [
            'project' => (new ProjectResource($project))->resolve(),
            'task' => (new TaskResource($task))->resolve(),
            'notes' => NoteResource::collection($task->notes)->resolve(),
            'contacts' => ContactResource::collection($task->contacts)->resolve(),
            'subtasks' => SubtaskResource::collection($mySubtasks)->resolve(),
            'team' => $team->map(fn (Member $m) => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'user_id' => $m->user_id])->all(),
        ]);
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
                ->with('workspace_flash', ['success' => true, 'message' => $result->message]);
        }

        return $this->redirectWithResult($result);
    }
}
