<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Team;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'members_count' => $this->whenCounted('members'),
            'member_ids' => $this->whenLoaded('members', fn () => $this->members->map(fn (Member $m): int => $m->id)->all()),
        ];
    }
}
