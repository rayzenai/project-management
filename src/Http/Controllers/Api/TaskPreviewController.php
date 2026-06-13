<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Queries\TaskPreviewQuery;
use RayzenAI\ProjectManagement\Support\ApiResponser;

/**
 * JSON sibling of the Workspace\TaskPreviewController. Both endpoints are
 * JSON-only and share the same TaskPreviewQuery — the API mirrors the web
 * response envelope exactly.
 */
class TaskPreviewController extends Controller
{
    use ApiResponser;

    public function __invoke(Task $task, TaskPreviewQuery $query): JsonResponse
    {
        return $this->dataResponse($query->data($task));
    }
}
