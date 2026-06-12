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

        return $this->redirectWithResult($result);
    }
}
