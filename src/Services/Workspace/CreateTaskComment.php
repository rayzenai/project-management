<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use App\Models\User;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Services\Workspace\Concerns\NotifiesMentions;
use RayzenAI\ProjectManagement\Support\MentionParser;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class CreateTaskComment
{
    use NotifiesMentions;

    public function execute(Task $task, User $author, string $body): ServiceResult
    {
        try {
            $ids = MentionParser::memberIds($body);

            $comment = $task->comments()->create([
                'user_id' => $author->id,
                'body' => $body,
                'mentioned_member_ids' => $ids,
            ]);

            $this->notifyMentions($task, $author, $ids, $body);

            return ServiceResult::success($comment, 'Comment added.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
