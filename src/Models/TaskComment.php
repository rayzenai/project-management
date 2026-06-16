<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RayzenAI\ProjectManagement\Database\Factories\TaskCommentFactory;

class TaskComment extends Model
{
    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['task_id', 'user_id', 'body', 'mentioned_member_ids'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['mentioned_member_ids' => 'array'];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('project-management.user_model'));
    }

    protected static function newFactory(): TaskCommentFactory
    {
        return TaskCommentFactory::new();
    }
}
