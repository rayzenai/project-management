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
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Http\Resources\TeamResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Services\Workspace\CreateProjectService;
use RayzenAI\ProjectManagement\Services\Workspace\UpdateProjectService;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

class ProjectController extends Controller
{
    use RedirectsWithServiceResult;

    public function index(Request $request): Response
    {
        $projects = Project::query()
            ->withCount('tasks')
            ->orderBy('title')
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => ProjectResource::collection($projects)->resolve(),
        ]);
    }

    public function show(Project $project): Response
    {
        $project->load(['teams:id', 'tasks' => fn ($q) => $q->with('assignments.member')->withCount(['notes', 'contacts'])]);

        return Inertia::render('Projects/Show', [
            'project' => (new ProjectResource($project))->resolve(),
            'tasks' => TaskResource::collection($project->tasks)->resolve(),
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get())->resolve(),
        ]);
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
