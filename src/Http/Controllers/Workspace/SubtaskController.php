<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreSubtaskRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateSubtaskRequest;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\CreateSubtaskService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteSubtaskService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateSubtaskService;

class SubtaskController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreSubtaskRequest $request, Task $task, CreateSubtaskService $service): RedirectResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->redirectWithResult($result);
    }

    public function update(UpdateSubtaskRequest $request, Subtask $subtask, UpdateSubtaskService $service): RedirectResponse
    {
        abort_unless($subtask->user_id === $request->user()->id, 403);

        $result = $service->execute($subtask, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function destroy(Subtask $subtask, DeleteSubtaskService $service, Request $request): RedirectResponse
    {
        abort_unless($subtask->user_id === $request->user()->id, 403);

        $result = $service->execute($subtask);

        return $this->redirectWithResult($result, undo: [
            'label' => 'Undo',
            'url' => route('workspace.subtasks.restore', $subtask),
        ]);
    }

    public function restore(Subtask $subtask, RestoreWorkspaceModel $service, Request $request): RedirectResponse
    {
        abort_unless($subtask->user_id === $request->user()->id, 403);

        $service->execute($subtask);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
