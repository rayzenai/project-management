<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;

/**
 * @mixin ProjectAssignment
 */
class AssignmentResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'role' => $this->role,
            'priority' => $this->priority,
            'is_focused' => (bool) $this->is_focused,
            'snoozed_until' => $this->snoozed_until?->toDateString(),
            'is_snoozed' => $this->isSnoozed(),
            'personal_progress' => (int) $this->personal_progress,
            'personal_due_at' => $this->personal_due_at?->toDateString(),
            'personal_status_note' => $this->personal_status_note,
            'task' => $this->whenLoaded('task', fn () => new TaskResource($this->task)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
