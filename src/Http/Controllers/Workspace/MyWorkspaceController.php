<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Queries\MyWorkspaceQuery;

class MyWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('MyWorkspace', (new MyWorkspaceQuery)->get($request));
    }
}
