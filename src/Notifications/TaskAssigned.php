<?php

namespace RayzenAI\ProjectManagement\Notifications;

use Illuminate\Notifications\Notification;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Notifications\Concerns\BuildsWorkspaceNotification;

// in-app (database) only — synchronous; add ShouldQueue when email/push channels are introduced
class TaskAssigned extends Notification
{
    use BuildsWorkspaceNotification;

    public function __construct(public Task $task, public string $actorName) {}

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'task_assigned',
            'title' => 'You were assigned a task',
            'body' => "{$this->actorName} assigned you “{$this->task->title}”.",
            'task' => $this->taskRef($this->task),
            'actor' => ['name' => $this->actorName],
            'url' => $this->taskUrl($this->task),
        ];
    }
}
