<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreAssignmentRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateAssignmentRequest;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AssignMemberService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UnassignMemberService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateAssignmentService;

class AssignmentController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreAssignmentRequest $request, Task $task, AssignMemberService $service): RedirectResponse
    {
        $result = $service->execute(
            task: $task,
            memberId: $request->integer('member_id'),
            attributes: $request->safe()->except('member_id'),
        );

        return $this->redirectWithResult($result);
    }

    public function update(UpdateAssignmentRequest $request, ProjectAssignment $assignment, UpdateAssignmentService $service): RedirectResponse
    {
        $result = $service->execute($assignment, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function destroy(ProjectAssignment $assignment, UnassignMemberService $service): RedirectResponse
    {
        $result = $service->execute($assignment);

        return $this->redirectWithResult($result, undo: [
            'label' => 'Undo',
            'url' => route('workspace.assignments.restore', $assignment),
        ]);
    }

    public function restore(ProjectAssignment $assignment, RestoreWorkspaceModel $service): RedirectResponse
    {
        $service->execute($assignment);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
