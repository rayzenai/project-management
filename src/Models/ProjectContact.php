<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectContact extends Model
{
    use SoftDeletes;

    protected $table = 'project_contacts';

    protected $fillable = [
        'task_id',
        'user_id',
        'name',
        'organization',
        'role',
        'email',
        'phone',
        'notes',
    ];

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
