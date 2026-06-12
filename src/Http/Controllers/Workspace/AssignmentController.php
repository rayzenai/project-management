<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreAssignmentRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateAssignmentRequest;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AssignUserService;
use RayzenAI\ProjectManagement\Services\Workspace\UnassignUserService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateAssignmentService;

class AssignmentController extends Controller
{
    use RedirectsWithServiceResult;

    public function store(StoreAssignmentRequest $request, Task $task, AssignUserService $service): RedirectResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->integer('user_id'),
            attributes: $request->safe()->except('user_id'),
        );

        return $this->redirectWithResult($result);
    }

    public function update(UpdateAssignmentRequest $request, ProjectAssignment $assignment, UpdateAssignmentService $service): RedirectResponse
    {
        $result = $service->execute($assignment, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function destroy(ProjectAssignment $assignment, UnassignUserService $service): RedirectResponse
    {
        $result = $service->execute($assignment);

        return $this->redirectWithResult($result);
    }
}
