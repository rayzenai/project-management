<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class AssignUserService
{
    /**
     * @param  array<string, mixed>  $attributes  optional: role, priority, personal_due_at, personal_status_note
     */
    public function execute(Task $task, int $userId, array $attributes = []): ServiceResult
    {
        if (ProjectAssignment::query()->where('task_id', $task->id)->where('user_id', $userId)->exists()) {
            return ServiceResult::failure('That user is already assigned to this task.', 409);
        }

        try {
            $assignment = ProjectAssignment::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'role' => $attributes['role'] ?? null,
                'priority' => $attributes['priority'] ?? 'medium',
                'personal_progress' => (int) ($attributes['personal_progress'] ?? 0),
                'personal_due_at' => $attributes['personal_due_at'] ?? null,
                'personal_status_note' => $attributes['personal_status_note'] ?? null,
            ]);

            return ServiceResult::success($assignment->fresh('user'), 'User assigned.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
