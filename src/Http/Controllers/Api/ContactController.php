<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreContactRequest;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\AddContactService;

/**
 * JSON sibling of the Workspace\ContactController. Reuses the same FormRequests
 * (authorization), action services (ServiceResult), and JsonResources as the web
 * surface — the only difference is the response shape.
 */
class ContactController extends Controller
{
    use RespondsWithServiceResult;

    public function store(StoreContactRequest $request, Task $task, AddContactService $service): JsonResponse
    {
        $result = $service->execute(
            task: $task,
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return $this->respondWithResult(
            $result,
            $result->data instanceof ProjectContact ? new ContactResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }
}
