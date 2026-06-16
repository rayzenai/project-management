<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreNoteRequest;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AddNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;

class NoteController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreNoteRequest $request, Task $task, AddNoteService $service): RedirectResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->redirectWithResult($result);
    }

    public function destroy(ProjectNote $note, DeleteNoteService $service): RedirectResponse
    {
        $result = $service->execute($note);

        return $this->redirectWithResult($result, undo: [
            'label' => 'Undo',
            'url' => route('workspace.notes.restore', $note),
        ]);
    }

    public function restore(ProjectNote $note, RestoreWorkspaceModel $service): RedirectResponse
    {
        $service->execute($note);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
