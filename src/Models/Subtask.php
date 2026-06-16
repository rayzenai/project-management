<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subtask extends Model
{
    use SoftDeletes;

    protected $table = 'subtasks';

    protected $fillable = [
        'task_id',
        'user_id',
        'body',
        'is_done',
        'done_at',
        'due_at',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
            'due_at' => 'date',
            'position' => 'integer',
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
