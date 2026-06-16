<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreNoteRequest;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AddNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;

/**
 * JSON sibling of the Workspace\NoteController. Reuses the same FormRequests
 * (authorization), action services (ServiceResult), and JsonResources as the web
 * surface — the only difference is the response shape.
 */
class NoteController extends Controller
{
    use RespondsWithServiceResult;

    public function store(StoreNoteRequest $request, Task $task, AddNoteService $service): JsonResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof ProjectNote ? new NoteResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function destroy(ProjectNote $note, DeleteNoteService $service): JsonResponse
    {
        $result = $service->execute($note);

        return $this->respondWithResult($result);
    }

    public function restore(ProjectNote $note, RestoreWorkspaceModel $service): JsonResponse
    {
        $result = $service->execute($note);

        return $this->respondWithResult(
            $result,
            $result->data instanceof ProjectNote ? new NoteResource($result->data) : null,
        );
    }
}
