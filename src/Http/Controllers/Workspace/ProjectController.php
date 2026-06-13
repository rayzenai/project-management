<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\StoreProjectRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateProjectRequest;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Queries\ProjectIndexQuery;
use RayzenAI\ProjectManagement\Queries\ProjectShowQuery;
use RayzenAI\ProjectManagement\Services\Workspace\CreateProjectService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateProjectService;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

class ProjectController extends Controller
{
    use RedirectsWithServiceResult;

    public function index(Request $request, ProjectIndexQuery $query): Response
    {
        return Inertia::render('Projects/Index', $query->data($request));
    }

    public function show(Project $project, ProjectShowQuery $query): Response
    {
        return Inertia::render('Projects/Show', $query->data($project));
    }

    public function store(StoreProjectRequest $request, CreateProjectService $service): RedirectResponse
    {
        $result = $service->execute($request->validated());

        if ($result->success && $result->data instanceof Project) {
            return redirect()
                ->route('workspace.projects.show', ['project' => $result->data->slug])
                ->with('workspace_flash', ['success' => true, 'message' => $result->message]);
        }

        return $this->redirectWithResult($result);
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectService $service): RedirectResponse
    {
        $result = $service->execute($project, $request->validated());

        return $this->redirectWithResult($result);
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        abort_unless(WorkspaceAccess::canArchiveProject($request->user(), $project), 403);

        $project->archive();

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Project archived.']);
    }

    public function restore(Request $request, Project $project): RedirectResponse
    {
        abort_unless(WorkspaceAccess::canArchiveProject($request->user(), $project), 403);

        $project->restore();

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Project restored.']);
    }
}
