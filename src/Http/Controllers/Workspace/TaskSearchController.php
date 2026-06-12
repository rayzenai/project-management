<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use RayzenAI\ProjectManagement\Support\ApiResponser;

/**
 * JSON-only fuzzy search used by the My Workspace command palette. Searches
 * tasks, notes, and contacts across every project and returns them grouped
 * so the UI can render three columns. Task-anchored note/contact hits navigate
 * to the parent task; the user's personal sticky notes share the Notes column
 * and open the notes board instead.
 */
class TaskSearchController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->dataResponse(['tasks' => [], 'notes' => [], 'contacts' => []]);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $like = '%'.$escaped.'%';

        $tasks = Task::query()
            ->with('project:id,slug,title')
            ->where(function ($query) use ($like, $q) {
                $query->where('title', 'ILIKE', $like)
                    ->orWhere('short_title', 'ILIKE', $like)
                    ->orWhereRaw("metadata->>'title_np' ILIKE ?", [$like]);

                if (ctype_digit($q)) {
                    $query->orWhereRaw("metadata->>'item_number' = ?", [(int) $q]);
                }
            })
            ->orderByRaw("CASE WHEN (metadata->>'item_number')::int = ? THEN 0 ELSE 1 END", [(int) $q])
            ->orderByRaw('similarity(title, ?) DESC', [$q])
            ->limit(15)
            ->get();

        $notes = ProjectNote::query()
            ->with(['task:id,slug,title,project_id', 'task.project:id,slug,title'])
            ->where('body', 'ILIKE', $like)
            ->latest()
            ->limit(15)
            ->get();

        $stickies = WorkspaceNote::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($like) {
                $query->where('title', 'ILIKE', $like)
                    ->orWhere('body', 'ILIKE', $like);
            })
            ->latest()
            ->limit(15)
            ->get();

        $contacts = ProjectContact::query()
            ->with(['task:id,slug,title,project_id', 'task.project:id,slug,title'])
            ->where(function ($query) use ($like) {
                $query->where('name', 'ILIKE', $like)
                    ->orWhere('organization', 'ILIKE', $like)
                    ->orWhere('role', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like);
            })
            ->latest()
            ->limit(15)
            ->get();

        return $this->dataResponse([
            'tasks' => $tasks->map(fn (Task $task): array => [
                'id' => $task->id,
                'slug' => $task->slug,
                'item_number' => $task->item_number,
                'title' => $task->title,
                'short_title' => $task->short_title,
                'status_label' => $task->status_label,
                'project' => $task->project ? [
                    'slug' => $task->project->slug,
                    'title' => $task->project->title,
                ] : null,
            ])->all(),
            'notes' => array_merge(
                $notes->map(fn (ProjectNote $note): array => [
                    'kind' => 'task',
                    'id' => $note->id,
                    'title' => null,
                    'body' => $note->body,
                    'task' => $this->taskRef($note->task),
                ])->all(),
                $stickies->map(fn (WorkspaceNote $sticky): array => [
                    'kind' => 'sticky',
                    'id' => $sticky->id,
                    'title' => $sticky->title,
                    'body' => $sticky->body,
                    'task' => null,
                ])->all(),
            ),
            'contacts' => $contacts->map(fn (ProjectContact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'role' => $contact->role,
                'organization' => $contact->organization,
                'task' => $this->taskRef($contact->task),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function taskRef(?Task $task): ?array
    {
        if (! $task) {
            return null;
        }

        return [
            'slug' => $task->slug,
            'title' => $task->title,
            'project' => $task->project ? [
                'slug' => $task->project->slug,
                'title' => $task->project->title,
            ] : null,
        ];
    }
}
