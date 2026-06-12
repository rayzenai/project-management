<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\QuickAddTaskRequest;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Services\Workspace\QuickAddTaskService;

class QuickAddController extends Controller
{
    use RedirectsWithServiceResult;

    public function __invoke(QuickAddTaskRequest $request, QuickAddTaskService $service): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));

        $assignees = $request->input('assignee_user_ids') ?: [$request->user()->id];

        $result = $service->execute(
            project: $project,
            title: $request->string('title')->toString(),
            assigneeUserIds: array_map('intval', (array) $assignees),
            deadline: $request->date('deadline_at')?->toDateString(),
            priority: $request->input('priority', 'medium'),
            authorUserId: $request->user()->id,
        );

        return $this->redirectWithResult($result);
    }
}
