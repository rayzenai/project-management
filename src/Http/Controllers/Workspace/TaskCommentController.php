<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreTaskCommentRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTaskCommentRequest;
use RayzenAI\ProjectManagement\Http\Resources\TaskCommentResource;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\TaskComment;
use RayzenAI\ProjectManagement\Services\Workspace\CreateTaskCommentService;
use RayzenAI\ProjectManagement\Services\Workspace\RestoreWorkspaceModel;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateTaskCommentService;

class TaskCommentController extends Controller
{
    use RedirectsWithServiceResult;

    public function index(Task $task): AnonymousResourceCollection
    {
        $comments = $task->comments()->with('user')->latest()->paginate(30);

        TaskCommentResource::preload($comments->getCollection());

        return TaskCommentResource::collection($comments);
    }

    public function store(StoreTaskCommentRequest $request, Task $task, CreateTaskCommentService $service): RedirectResponse
    {
        $result = $service->execute($task, $request->user(), $request->validated('body'));

        return $this->redirectWithResult($result);
    }

    public function update(UpdateTaskCommentRequest $request, TaskComment $comment, UpdateTaskCommentService $service): RedirectResponse
    {
        $result = $service->execute($comment, $request->validated('body'));

        return $this->redirectWithResult($result);
    }

    public function destroy(TaskComment $comment, Request $request): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('workspace_flash', [
            'success' => true,
            'message' => 'Comment deleted.',
            'undo' => [
                'label' => 'Undo',
                'url' => route('workspace.comments.restore', $comment),
            ],
        ]);
    }

    public function restore(TaskComment $comment, RestoreWorkspaceModel $service, Request $request): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $service->execute($comment);

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Restored.']);
    }
}
