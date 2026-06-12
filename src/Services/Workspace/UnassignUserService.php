<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class UnassignUserService
{
    public function execute(ProjectAssignment $assignment): ServiceResult
    {
        try {
            $assignment->delete();

            return ServiceResult::success(message: 'Assignment removed.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
