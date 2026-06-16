<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreContactRequest;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AddContactService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;

class ContactController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreContactRequest $request, Task $task, AddContactService $service): RedirectResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->redirectWithResult($result);
    }

    /**
     * Contacts have no destroy on either surface, so there is no delete
     * authorization to mirror. Restore reuses StoreContactRequest's rule —
     * any authenticated workspace user — as the closest existing contact gate.
     */
    public function restore(Request $request, ProjectContact $contact, RestoreWorkspaceModel $service): RedirectResponse
    {
        abort_unless((bool) $request->user(), 403);

        $service->execute($contact);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
