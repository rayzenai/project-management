<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectNote extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'general' => 'General note',
        'action_taken' => 'Action taken',
        'meeting' => 'Meeting',
        'blocker' => 'Blocker',
        'milestone' => 'Milestone',
        'decision' => 'Decision',
    ];

    protected $table = 'project_notes';

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'body',
        'happened_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'happened_at' => 'date',
        ];
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
