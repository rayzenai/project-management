<?php

namespace RayzenAI\ProjectManagement\Services\Workspace\Concerns;

use App\Models\User;
use Illuminate\Support\Str;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Notifications\MentionedInComment;
use RayzenAI\ProjectManagement\Support\MentionParser;

trait NotifiesMentions
{
    /**
     * Notify each mentioned member that has a linked login, excluding the author.
     *
     * @param  list<int>  $memberIds
     */
    private function notifyMentions(Task $task, User $author, array $memberIds, string $body): void
    {
        if ($memberIds === []) {
            return;
        }

        $excerpt = Str::limit(MentionParser::toDisplayText($body), 80);

        Member::with('user')->whereIn('id', $memberIds)->get()
            ->pluck('user')->filter()
            ->reject(fn ($user): bool => $user->id === $author->id)
            ->unique('id')
            ->each(fn ($user) => $user->notify(
                new MentionedInComment($task, $author->name, $excerpt)
            ));
    }
}
