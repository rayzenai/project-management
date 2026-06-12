<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class DeleteTaskService
{
    public function execute(Task $task): ServiceResult
    {
        try {
            $task->delete();

            return ServiceResult::success(message: 'Task deleted.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
