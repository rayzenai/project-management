<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\QuickAddTaskRequest;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\QuickAddDispatcher;

/**
 * JSON sibling of the Workspace\QuickAddController. All parsing/resolution lives in
 * {@see QuickAddDispatcher}, shared with the web controller; this controller only
 * adapts the request to the dispatcher and the result to a JSON response.
 */
class QuickAddController extends Controller
{
    use RespondsWithServiceResult;

    public function __invoke(QuickAddTaskRequest $request, QuickAddDispatcher $dispatcher): JsonResponse
    {
        $result = $dispatcher->dispatch(
            rawTitle: $request->string('title')->toString(),
            projectId: $request->integer('project_id'),
            explicitAssigneeIds: array_map('intval', (array) ($request->input('assignee_member_ids') ?: [])),
            priority: $request->input('priority'),
            deadline: $request->date('deadline_at')?->toDateString(),
            user: $request->user(),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof Task ? new TaskResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }
}
