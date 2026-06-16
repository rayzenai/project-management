<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreWorkspaceNoteRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateWorkspaceNotePlacementRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateWorkspaceNoteRequest;
use RayzenAI\ProjectManagement\Http\Resources\WorkspaceNoteResource;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use RayzenAI\ProjectManagement\Services\Workspace\CreateWorkspaceNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteWorkspaceNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateWorkspaceNotePlacementService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateWorkspaceNoteService;

/**
 * JSON sibling of the Workspace\WorkspaceNoteController. Reuses the same
 * FormRequests (authorization), action services (ServiceResult), JsonResources,
 * and the per-note ownership guard as the web surface — the only difference is
 * the response shape.
 */
class WorkspaceNoteController extends Controller
{
    use RespondsWithServiceResult;

    public function store(StoreWorkspaceNoteRequest $request, CreateWorkspaceNoteService $service): JsonResponse
    {
        $result = $service->execute(
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof WorkspaceNote ? new WorkspaceNoteResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function update(UpdateWorkspaceNoteRequest $request, WorkspaceNote $workspaceNote, UpdateWorkspaceNoteService $service): JsonResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof WorkspaceNote ? new WorkspaceNoteResource($result->data) : null,
        );
    }

    public function placement(UpdateWorkspaceNotePlacementRequest $request, WorkspaceNote $workspaceNote, UpdateWorkspaceNotePlacementService $service): JsonResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof WorkspaceNote ? new WorkspaceNoteResource($result->data) : null,
        );
    }

    public function destroy(WorkspaceNote $workspaceNote, DeleteWorkspaceNoteService $service, Request $request): JsonResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote);

        return $this->respondWithResult($result);
    }

    public function restore(WorkspaceNote $workspaceNote, RestoreWorkspaceModel $service, Request $request): JsonResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote);

        return $this->respondWithResult(
            $result,
            $result->data instanceof WorkspaceNote ? new WorkspaceNoteResource($result->data) : null,
        );
    }

    private function authorizeOwnership(Request $request, WorkspaceNote $note): void
    {
        abort_unless($note->user_id === $request->user()?->id, 403);
    }
}
