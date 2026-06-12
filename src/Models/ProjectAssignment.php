<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    protected $table = 'project_assignments';

    protected $fillable = [
        'user_id',
        'task_id',
        'role',
        'priority',
        'is_focused',
        'snoozed_until',
        'personal_progress',
        'personal_due_at',
        'personal_status_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_due_at' => 'date',
            'snoozed_until' => 'date',
            'is_focused' => 'boolean',
            'personal_progress' => 'integer',
        ];
    }

    /**
     * Snoozed assignments are those with a future snoozed_until date.
     */
    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('project-management.user_model'));
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
