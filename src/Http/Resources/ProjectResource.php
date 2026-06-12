<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RayzenAI\ProjectManagement\Models\Project;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'title_np' => $this->title_np,
            'description' => $this->description,
            'description_np' => $this->description_np,
            'is_public' => (bool) $this->is_public,
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
