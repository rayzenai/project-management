<?php

namespace RayzenAI\ProjectManagement\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RayzenAI\ProjectManagement\Http\Resources\WorkspaceNoteResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares request-independent workspace data as Inertia props on every
 * workspace page:
 *
 * - `statuses` — the ordered task workflow from the package config, with
 *   completion flags, so status chips, board columns, and pickers all render
 *   from one definition.
 * - `workspaceNotes` — the authenticated user's personal sticky notes
 *   (newest-updated first) so the top-bar notes icon, its count badge, and
 *   the slide-over drawer render instantly everywhere without a fetch.
 */
class ShareWorkspaceData
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('statuses', fn (): array => collect((array) config('project-management.statuses'))
            ->map(fn (array $meta, string $value): array => [
                'value' => $value,
                'label' => $meta['label'] ?? ucfirst($value),
                'color' => $meta['color'] ?? '#9CA3AF',
                'is_complete' => (bool) ($meta['is_complete'] ?? false),
            ])
            ->values()
            ->all());

        Inertia::share('completeStatus', fn (): string => (string) config('project-management.complete_status', 'done'));

        Inertia::share('quickAddContext', function () use ($request): ?array {
            if (! $request->user()) {
                return null;
            }

            return [
                'projects' => Project::query()->orderBy('title')->get(['id', 'slug', 'title'])
                    ->map(fn (Project $p): array => ['id' => $p->id, 'slug' => $p->slug, 'title' => $p->title])
                    ->all(),
                'team' => config('project-management.user_model', User::class)::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                    ->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                    ->all(),
            ];
        });

        Inertia::share('workspaceNotes', function () use ($request): array {
            $user = $request->user();
            if (! $user) {
                return [];
            }

            return WorkspaceNoteResource::collection(
                WorkspaceNote::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->get()
            )->resolve();
        });

        return $next($request);
    }
}
