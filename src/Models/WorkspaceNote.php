<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceNote extends Model
{
    use SoftDeletes;

    public const COLORS = ['amber', 'rose', 'sky', 'emerald', 'violet'];

    protected $table = 'workspace_notes';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'position_x',
        'position_y',
        'color',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_x' => 'integer',
            'position_y' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('project-management.user_model'));
    }
}
