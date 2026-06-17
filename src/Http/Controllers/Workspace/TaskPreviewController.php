<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Queries\TaskPreviewQuery;
use RayzenAI\ProjectManagement\Support\ApiResponser;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * JSON-only endpoint backing the Task Peek slide-over. Returns the full
 * editable context for one task — fields, assignments (with ids so the Peek
 * can unassign), subtasks, notes, contacts, recent activity, and the assignee
 * candidate list — without a full-page Inertia visit.
 */
class TaskPreviewController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request, Task $task, TaskPreviewQuery $query): JsonResponse
    {
        abort_unless(WorkspaceAccess::canViewProject($request->user(), $task->loadMissing('project')->project), 403);

        return $this->dataResponse($query->data($task));
    }
}
