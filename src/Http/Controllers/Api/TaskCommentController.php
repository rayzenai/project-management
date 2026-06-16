<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Api\Concerns\RespondsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreTaskCommentRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTaskCommentRequest;
use RayzenAI\ProjectManagement\Http\Resources\TaskCommentResource;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\TaskComment;
use RayzenAI\ProjectManagement\Services\Workspace\CreateTaskCommentService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateTaskCommentService;

/**
 * JSON sibling of the Workspace\TaskCommentController. Reuses the same
 * FormRequests, action services, and JsonResource as the web surface — the
 * index batches author/mention member lookups via TaskCommentResource::preload.
 */
class TaskCommentController extends Controller
{
    use RespondsWithServiceResult;

    public function index(Task $task): AnonymousResourceCollection
    {
        $comments = $task->comments()->with('user')->latest()->paginate(30);

        TaskCommentResource::preload($comments->getCollection());

        return TaskCommentResource::collection($comments)->additional(['message' => 'Comments retrieved.']);
    }

    public function store(StoreTaskCommentRequest $request, Task $task, CreateTaskCommentService $service): JsonResponse
    {
        $result = $service->execute($task, $request->user(), $request->validated('body'));

        return $this->respondWithResult(
            $result,
            $result->data instanceof TaskComment ? new TaskCommentResource($result->data) : null,
            $result->success ? 201 : null,
        );
    }

    public function update(UpdateTaskCommentRequest $request, TaskComment $comment, UpdateTaskCommentService $service): JsonResponse
    {
        $result = $service->execute($comment, $request->validated('body'));

        return $this->respondWithResult(
            $result,
            $result->data instanceof TaskComment ? new TaskCommentResource($result->data) : null,
        );
    }

    public function destroy(TaskComment $comment, Request $request): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    public function restore(TaskComment $comment, RestoreWorkspaceModel $service, Request $request): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $result = $service->execute($comment);

        return $this->respondWithResult(
            $result,
            $result->data instanceof TaskComment ? new TaskCommentResource($result->data) : null,
        );
    }
}
