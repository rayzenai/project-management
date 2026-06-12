<?php

namespace RayzenAI\ProjectManagement\Observers;

use RayzenAI\ProjectManagement\Models\ProjectActivity;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Services\ProjectActivityRecorder;

class ProjectNoteObserver
{
    public function created(ProjectNote $note): void
    {
        ProjectActivityRecorder::record(
            taskId: $note->task_id,
            subject: $note,
            action: ProjectActivity::ACTION_CREATED,
            description: 'Note added: '.$note->type.' — "'.ProjectActivityRecorder::truncate($note->body, 60).'"',
        );
    }

    public function updated(ProjectNote $note): void
    {
        ProjectActivityRecorder::record(
            taskId: $note->task_id,
            subject: $note,
            action: ProjectActivity::ACTION_UPDATED,
            description: 'Note edited',
        );
    }

    public function deleted(ProjectNote $note): void
    {
        ProjectActivityRecorder::record(
            taskId: $note->task_id,
            subject: $note,
            action: ProjectActivity::ACTION_DELETED,
            description: 'Note deleted',
        );
    }
}
