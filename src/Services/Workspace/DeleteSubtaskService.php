<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class DeleteSubtaskService
{
    public function execute(Subtask $subtask): ServiceResult
    {
        try {
            $subtask->delete();

            return ServiceResult::success(message: 'Todo removed.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
