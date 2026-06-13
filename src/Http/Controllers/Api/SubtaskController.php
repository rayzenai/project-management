<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreSubtaskRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateSubtaskRequest;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\CreateSubtaskService;
use RayzenAI\ProjectManagement\Services\Workspace\DeleteSubtaskService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateSubtaskService;

/**
 * JSON sibling of the Workspace\SubtaskController. Reuses the same FormRequests
 * (authorization), action services (ServiceResult), and JsonResources as the web
 * surface — the only difference is the response shape.
 */
class SubtaskController extends Controller
{
    use RespondsWithServiceResult;

    public function store(StoreSubtaskRequest $request, Task $task, CreateSubtaskService $service): JsonResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof Subtask ? new SubtaskResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function update(UpdateSubtaskRequest $request, Subtask $subtask, UpdateSubtaskService $service): JsonResponse
    {
        abort_unless($subtask->user_id === $request->user()->id, 403);

        $result = $service->execute($subtask, $request->validated());

        return $this->respondWithResult(
            $result,
            $result->data instanceof Subtask ? new SubtaskResource($result->data) : null,
        );
    }

    public function destroy(Subtask $subtask, DeleteSubtaskService $service, Request $request): JsonResponse
    {
        abort_unless($subtask->user_id === $request->user()->id, 403);

        $result = $service->execute($subtask);

        return $this->respondWithResult($result);
    }
}
