<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Queries\PlanTrackerQuery;

/**
 * JSON sibling of the Workspace\PlanTrackerController. Delegates to the shared
 * {@see PlanTrackerQuery} so the 100-Day-Plan tracker aggregation is the single
 * source of truth across the Inertia web view and this API feed.
 */
class PlanTrackerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json((new PlanTrackerQuery)->get($request));
    }
}
