<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreWorkspaceNoteRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateWorkspaceNotePlacementRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateWorkspaceNoteRequest;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use RayzenAI\ProjectManagement\Services\Workspace\CreateWorkspaceNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteWorkspaceNoteService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateWorkspaceNotePlacementService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateWorkspaceNoteService;

class WorkspaceNoteController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreWorkspaceNoteRequest $request, CreateWorkspaceNoteService $service): RedirectResponse
    {
        $result = $service->execute(
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->redirectWithResult($result);
    }

    public function update(UpdateWorkspaceNoteRequest $request, WorkspaceNote $workspaceNote, UpdateWorkspaceNoteService $service): RedirectResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function placement(UpdateWorkspaceNotePlacementRequest $request, WorkspaceNote $workspaceNote, UpdateWorkspaceNotePlacementService $service): RedirectResponse
    {
        $this->authorizeOwnership($request, $workspaceNote);

        $result = $service->execute($workspaceNote, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function destroy(WorkspaceNote $workspaceNote, DeleteWorkspaceNoteService $service): RedirectResponse
    {
        $this->authorizeOwnership(request(), $workspaceNote);

        $result = $service->execute($workspaceNote);

        return $this->redirectWithResult($result);
    }

    private function authorizeOwnership(Request $request, WorkspaceNote $note): void
    {
        abort_unless($note->user_id === $request->user()?->id, 403);
    }
}
