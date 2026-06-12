<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\Concerns\RedirectsWithServiceResult;
use RayzenAI\ProjectManagement\Http\Requests\QuickAddTaskRequest;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Services\Workspace\QuickAddTaskService;
use RayzenAI\ProjectManagement\Support\QuickAddParser;

/**
 * Creates a task from a single line of natural language. The title is parsed
 * for #project / @assignee / !priority / date tokens; explicit picker fields
 * win over parsed values, resolved tokens are stripped from the title, and
 * unresolvable tokens stay in it so nothing typed is silently lost.
 */
class QuickAddController extends Controller
{
    use RedirectsWithServiceResult;

    public function __invoke(QuickAddTaskRequest $request, QuickAddTaskService $service): RedirectResponse
    {
        $rawTitle = $request->string('title')->toString();
        $tokens = QuickAddParser::parse($rawTitle);
        $consumed = [];

        $project = $this->resolveProject($tokens, $consumed)
            ?? Project::query()->findOrFail($request->integer('project_id'));

        $assignees = array_map('intval', (array) ($request->input('assignee_user_ids') ?: []));
        if ($assignees === []) {
            $assignees = $this->resolveAssignees($tokens, $consumed);
        }
        if ($assignees === []) {
            $assignees = [$request->user()->id];
        }

        $priority = $request->input('priority');
        $deadline = $request->date('deadline_at')?->toDateString();

        foreach ($tokens as $token) {
            if ($token['type'] === 'priority') {
                $priority ??= $token['value'];
                $consumed[] = $token;
            }

            if ($token['type'] === 'date') {
                $deadline ??= $token['value'];
                $consumed[] = $token;
            }
        }

        $result = $service->execute(
            project: $project,
            title: QuickAddParser::strip($rawTitle, $consumed),
            assigneeUserIds: $assignees,
            deadline: $deadline,
            priority: $priority ?? 'medium',
            authorUserId: $request->user()->id,
        );

        return $this->redirectWithResult($result);
    }

    /**
     * @param  list<array{type: string, raw: string, value: string, offset: int}>  $tokens
     * @param  list<array{type: string, raw: string, value: string, offset: int}>  $consumed
     */
    private function resolveProject(array $tokens, array &$consumed): ?Project
    {
        foreach ($tokens as $token) {
            if ($token['type'] !== 'project') {
                continue;
            }

            $needle = mb_strtolower($token['value']);

            $project = Project::query()->whereRaw('LOWER(slug) = ?', [$needle])->first()
                ?? Project::query()->whereRaw('LOWER(slug) LIKE ?', [$needle.'%'])->orderBy('title')->first()
                ?? Project::query()->whereRaw('LOWER(title) LIKE ?', [$needle.'%'])->orderBy('title')->first()
                ?? Project::query()->whereRaw('LOWER(slug) LIKE ?', ['%'.$needle.'%'])->orderBy('title')->first()
                ?? Project::query()->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%'])->orderBy('title')->first();

            if ($project) {
                $consumed[] = $token;

                return $project;
            }
        }

        return null;
    }

    /**
     * @param  list<array{type: string, raw: string, value: string, offset: int}>  $tokens
     * @param  list<array{type: string, raw: string, value: string, offset: int}>  $consumed
     * @return list<int>
     */
    private function resolveAssignees(array $tokens, array &$consumed): array
    {
        $userModel = config('project-management.user_model', User::class);
        $ids = [];

        foreach ($tokens as $token) {
            if ($token['type'] !== 'assignee') {
                continue;
            }

            $needle = mb_strtolower($token['value']);

            /** @var Model|null $user */
            $user = $userModel::query()
                ->whereRaw('LOWER(name) LIKE ?', [$needle.'%'])
                ->orderBy('name')
                ->first();

            if ($user) {
                $ids[] = (int) $user->getKey();
                $consumed[] = $token;
            }
        }

        return array_values(array_unique($ids));
    }
}
