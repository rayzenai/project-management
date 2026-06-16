<?php

namespace RayzenAI\ProjectManagement\Observers;

use RayzenAI\ProjectManagement\Models\ProjectActivity;
use RayzenAI\ProjectManagement\Models\TaskComment;
use RayzenAI\ProjectManagement\Services\ProjectActivityRecorder;

class TaskCommentObserver
{
    public function created(TaskComment $comment): void
    {
        ProjectActivityRecorder::record(
            taskId: $comment->task_id,
            subject: $comment,
            action: ProjectActivity::ACTION_COMMENTED,
            description: 'Commented: "'.ProjectActivityRecorder::truncate($comment->body, 60).'"',
        );
    }
}
