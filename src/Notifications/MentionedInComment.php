<?php

namespace RayzenAI\ProjectManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Notifications\Concerns\BuildsWorkspaceNotification;

class MentionedInComment extends Notification implements ShouldQueue
{
    use BuildsWorkspaceNotification, Queueable;

    public function __construct(
        public Task $task,
        public string $actorName,
        public string $excerpt,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'mentioned_in_comment',
            'title' => 'You were mentioned',
            'body' => "{$this->actorName} mentioned you: “{$this->excerpt}”.",
            'task' => $this->taskRef($this->task),
            'actor' => ['name' => $this->actorName],
            'url' => $this->taskUrl($this->task),
        ];
    }
}
