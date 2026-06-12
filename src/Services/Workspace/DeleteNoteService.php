<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class DeleteNoteService
{
    public function execute(ProjectNote $note): ServiceResult
    {
        try {
            $note->delete();

            return ServiceResult::success(message: 'Note deleted.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
