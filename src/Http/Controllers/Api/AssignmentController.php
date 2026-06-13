<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreAssignmentRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateAssignmentRequest;
use RayzenAI\ProjectManagement\Http\Resources\AssignmentResource;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AssignMemberService;
use RayzenAI\ProjectManagement\Services\Workspace\UnassignMemberService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateAssignmentService;

/**
 * JSON sibling of the Workspace\AssignmentController. Reuses the same FormRequests
 * (authorization), action services (ServiceResult), and JsonResources as the web
 * surface — the only difference is the response shape.
 */
class AssignmentController extends Controller
{
    use RespondsWithServiceResult;

    public function store(StoreAssignmentRequest $request, Task $task, AssignMemberService $service): JsonResponse
    {
        $result = $service->execute(
            task: $task,
            memberId: $request->integer('member_id'),
            attributes: $request->safe()->except('member_id'),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof ProjectAssignment ? new AssignmentResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function update(UpdateAssignmentRequest $request, ProjectAssignment $assignment, UpdateAssignmentService $service): JsonResponse
    {
        $result = $service->execute($assignment, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof ProjectAssignment ? new AssignmentResource($result->data) : null,
        );
    }

    public function destroy(ProjectAssignment $assignment, UnassignMemberService $service): JsonResponse
    {
        $result = $service->execute($assignment);

        return $this->respondWithResult($result);
    }
}
