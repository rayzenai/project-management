<?php

namespace RayzenAI\ProjectManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RayzenAI\ProjectManagement\Http\Resources\WorkspaceNoteResource;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the authenticated user's personal workspace notes (newest-updated
 * first) as the `workspaceNotes` Inertia prop on every workspace page, so the
 * top-bar notes icon, its count badge, and the slide-over drawer render
 * instantly everywhere without a separate fetch.
 */
class ShareWorkspaceNotes
{
    public function handle(Request $request, Closure $next): Response
    {
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
