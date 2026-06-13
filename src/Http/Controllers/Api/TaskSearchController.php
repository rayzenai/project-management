<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Queries\TaskSearchQuery;

/**
 * JSON sibling of the Workspace\TaskSearchController. Delegates to the shared
 * {@see TaskSearchQuery} so the fuzzy command-palette search logic is the single
 * source of truth across the web and API surfaces. Reads the same `q` query
 * param and returns the grouped tasks/projects/notes/contacts payload.
 */
class TaskSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json((new TaskSearchQuery)->get($request));
    }
}
