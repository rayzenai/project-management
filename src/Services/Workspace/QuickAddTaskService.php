<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

/**
 * The killer feature: create a task and assign it to one or more users in a
 * single atomic call. Designed for inline quick-add boxes on Today / My
 * Workspace where typing a title + selecting an assignee should result in a
 * fully-formed assigned task with no follow-up forms.
 */
class QuickAddTaskService
{
    /**
     * @param  array<int, int>  $assigneeUserIds  list of user IDs to assign; defaults to [authUser->id] in the controller
     */
    public function execute(
        Project $project,
        string $title,
        array $assigneeUserIds,
        ?string $deadline = null,
        ?string $priority = 'medium',
        ?int $authorUserId = null,
    ): ServiceResult {
        $title = trim($title);
        if ($title === '') {
            return ServiceResult::failure('Title is required.', 422);
        }

        if (empty($assigneeUserIds)) {
            return ServiceResult::failure('At least one assignee is required.', 422);
        }

        try {
            return DB::transaction(function () use ($project, $title, $assigneeUserIds, $deadline, $priority): ServiceResult {
                $itemNumber = $this->nextItemNumber($project->id);
                $sortOrder = ((int) Task::query()->where('project_id', $project->id)->max('sort_order')) + 100;

                $task = Task::create([
                    'project_id' => $project->id,
                    'title' => $title,
                    'slug' => $this->uniqueSlug($title, $itemNumber),
                    'description' => '',
                    'status' => 'unclear',
                    'progress' => 0,
                    'deadline_at' => $deadline,
                    'item_number' => $itemNumber,
                    'sort_order' => $sortOrder,
                ]);

                foreach (array_unique($assigneeUserIds) as $userId) {
                    ProjectAssignment::create([
                        'user_id' => (int) $userId,
                        'task_id' => $task->id,
                        'priority' => $priority ?: 'medium',
                        'personal_progress' => 0,
                    ]);
                }

                return ServiceResult::success(
                    data: $task->fresh(['assignments.user']),
                    message: 'Task created and assigned.',
                );
            });
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }

    private function nextItemNumber(int $projectId): int
    {
        $max = (int) (Task::query()
            ->where('project_id', $projectId)
            ->max(DB::raw("(metadata->>'item_number')::int")) ?? 0);

        return $max + 1;
    }

    private function uniqueSlug(string $title, int $itemNumber): string
    {
        $base = $itemNumber.'-'.Str::slug(Str::limit($title, 60, ''));
        if (Str::endsWith($base, '-')) {
            $base .= 'task';
        }

        $slug = $base;
        $i = 2;
        while (Task::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
