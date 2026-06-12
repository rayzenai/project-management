<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use RayzenAI\ProjectManagement\Models\Project;
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
            return $this->dataResponse(['tasks' => [], 'projects' => [], 'notes' => [], 'contacts' => []]);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $like = '%'.$escaped.'%';
        $pgsql = DB::connection()->getDriverName() === 'pgsql';
        $likeOp = $pgsql ? 'ILIKE' : 'LIKE';

        $tasks = Task::query()
            ->with('project:id,slug,title')
            ->where(function ($query) use ($like, $likeOp, $q) {
                $query->where('title', $likeOp, $like)
                    ->orWhere('short_title', $likeOp, $like)
                    ->orWhereRaw("metadata->>'title_np' {$likeOp} ?", [$like]);

                if (ctype_digit($q)) {
                    $query->orWhereRaw("metadata->>'item_number' = ?", [(int) $q]);
                }
            })
            ->when($pgsql, fn ($query) => $query
                ->orderByRaw("CASE WHEN CAST(metadata->>'item_number' AS INTEGER) = ? THEN 0 ELSE 1 END", [(int) $q])
                ->orderByRaw('similarity(title, ?) DESC', [$q]))
            ->limit(15)
            ->get();

        $matchedProjects = Project::query()
            ->withCount('tasks')
            ->where(function ($query) use ($likeOp, $like) {
                $query->where('title', $likeOp, $like)->orWhere('slug', $likeOp, $like);
            })
            ->orderBy('title')
            ->limit(5)
            ->get();

        $notes = ProjectNote::query()
            ->with(['task:id,slug,title,project_id', 'task.project:id,slug,title'])
            ->where('body', $likeOp, $like)
            ->latest()
            ->limit(15)
            ->get();

        $stickies = WorkspaceNote::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($like, $likeOp) {
                $query->where('title', $likeOp, $like)
                    ->orWhere('body', $likeOp, $like);
            })
            ->latest()
            ->limit(15)
            ->get();

        $contacts = ProjectContact::query()
            ->with(['task:id,slug,title,project_id', 'task.project:id,slug,title'])
            ->where(function ($query) use ($like, $likeOp) {
                $query->where('name', $likeOp, $like)
                    ->orWhere('organization', $likeOp, $like)
                    ->orWhere('role', $likeOp, $like)
                    ->orWhere('email', $likeOp, $like)
                    ->orWhere('phone', $likeOp, $like);
            })
            ->latest()
            ->limit(15)
            ->get();

        return $this->dataResponse([
            'projects' => $matchedProjects->map(fn (Project $project): array => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'tasks_count' => (int) $project->tasks_count,
            ])->all(),
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
            'id' => $task->id,
            'slug' => $task->slug,
            'title' => $task->title,
            'project' => $task->project ? [
                'slug' => $task->project->slug,
                'title' => $task->project->title,
            ] : null,
        ];
    }
}
