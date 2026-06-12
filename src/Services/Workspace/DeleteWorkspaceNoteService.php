<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use Illuminate\Support\Facades\DB;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class DeleteWorkspaceNoteService
{
    public function execute(WorkspaceNote $note): ServiceResult
    {
        try {
            DB::transaction(fn () => $note->delete());

            return ServiceResult::success(null, 'Note deleted.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
