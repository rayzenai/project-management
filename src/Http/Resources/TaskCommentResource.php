<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\TaskComment;

/**
 * @mixin TaskComment
 */
class TaskCommentResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authorMember = Member::query()->where('user_id', $this->user_id)->first();

        $mentionedIds = $this->mentioned_member_ids ?? [];
        $mentionMembers = Member::query()
            ->whereIn('id', $mentionedIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $mentions = collect($mentionedIds)
            ->map(fn ($id) => $mentionMembers->get($id))
            ->filter()
            ->map(fn (Member $m) => ['member_id' => $m->id, 'name' => $m->name])
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'mentions' => $mentions,
            'author' => [
                'member_id' => $authorMember?->id,
                'name' => $authorMember?->name ?? $this->user?->name,
            ],
            'can_edit' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
