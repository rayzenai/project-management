<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\ProjectActivity;
use RayzenAI\ProjectManagement\Models\Task;

class DashboardController extends Controller
{
    /**
     * Statuses that count a task as finished.
     *
     * @var list<string>
     */
    private const COMPLETE = ['done', 'done_late'];

    public function __invoke(Request $request): Response
    {
        $statuses = collect((array) config('government.statuses'));
        $today = now()->startOfDay();
        $weekEnd = $today->copy()->addDays(7);
        $stalledBefore = now()->subDays(14);

        $tasks = Task::query()->get(['id', 'project_id', 'status', 'progress', 'status_updated_at', 'deadline_at']);
        $projects = Project::query()->orderBy('title')->get(['id', 'slug', 'title']);
        $byProject = $tasks->groupBy('project_id');

        $isComplete = fn (Task $t): bool => in_array($t->status, self::COMPLETE, true);
        $isStalled = fn (Task $t): bool => ! $isComplete($t)
            && $t->status_updated_at !== null
            && $t->status_updated_at->lt($stalledBefore);
        $isDueThisWeek = fn (Task $t): bool => ! $isComplete($t)
            && $t->deadline_at !== null
            && $t->deadline_at->gte($today)
            && $t->deadline_at->lte($weekEnd);

        $percent = function (Collection $items) use ($isComplete): int {
            $total = $items->count();
            if ($total === 0) {
                return 0;
            }

            return (int) round($items->filter($isComplete)->count() / $total * 100);
        };

        /** @var callable(Collection<int, Task>): list<array<string, mixed>> $breakdown */
        $breakdown = function (Collection $items) use ($statuses): array {
            $counts = $items->countBy('status');

            return $statuses->map(fn (array $meta, string $value): array => [
                'value' => $value,
                'label' => $meta['label'] ?? $value,
                'color' => $meta['color'] ?? '#9CA3AF',
                'count' => (int) ($counts[$value] ?? 0),
            ])->values()->all();
        };

        $projectRows = $projects->map(function (Project $project) use ($byProject, $percent, $isStalled, $isDueThisWeek, $breakdown): array {
            $items = $byProject->get($project->id, new Collection);

            return [
                'slug' => $project->slug,
                'title' => $project->title,
                'tasks_count' => $items->count(),
                'percent_complete' => $percent($items),
                'stalled' => $items->filter($isStalled)->count(),
                'due_this_week' => $items->filter($isDueThisWeek)->count(),
                'status_breakdown' => $breakdown($items),
            ];
        })->all();

        $recentActivity = ProjectActivity::query()
            ->public()
            ->recent(14)
            ->with(['user', 'task.project'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (ProjectActivity $activity): array => [
                'id' => $activity->id,
                'description' => $activity->description,
                'user_name' => $activity->user?->name,
                'task_title' => $activity->task?->title,
                'task_slug' => $activity->task?->slug,
                'project_slug' => $activity->task?->project?->slug,
                'happened_at' => $activity->created_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => [
                'projects' => $projects->count(),
                'tasks' => $tasks->count(),
                'percent_complete' => $percent($tasks),
                'due_this_week' => $tasks->filter($isDueThisWeek)->count(),
                'stalled' => $tasks->filter($isStalled)->count(),
            ],
            'status_breakdown' => $breakdown($tasks),
            'projects' => $projectRows,
            'recent_activity' => $recentActivity,
        ]);
    }
}
