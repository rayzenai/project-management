<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Queries\TaskSearchQuery;
use RayzenAI\ProjectManagement\Support\ApiResponser;

/**
 * JSON-only fuzzy search used by the My Workspace command palette. Searches
 * tasks, notes, and contacts across every project and returns them grouped
 * so the UI can render three columns. Task-anchored note/contact hits navigate
 * to the parent task; the user's personal sticky notes share the Notes column
 * and open the notes board instead.
 */
class TaskSearchController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request): JsonResponse
    {
        return $this->dataResponse((new TaskSearchQuery)->get($request));
    }
}
