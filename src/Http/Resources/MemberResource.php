<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Team;

/**
 * @mixin Member
 */
class MemberResource extends JsonResource
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
            'email' => $this->email,
            'title' => $this->title,
            'user_id' => $this->user_id,
            'is_active' => (bool) $this->is_active,
            'team_ids' => $this->whenLoaded('teams', fn () => $this->teams->map(fn (Team $t): int => $t->id)->all()),
        ];
    }
}
