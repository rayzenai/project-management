<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ApiResponser;

/**
 * Lightweight JSON-only endpoint used by the workspace focus modal. Returns
 * just the parent task's surrounding context (notes + contacts) so the modal
 * can show full information without a full-page Inertia visit.
 */
class TaskPreviewController extends Controller
{
    use ApiResponser;

    public function __invoke(Task $task): JsonResponse
    {
        $task->loadMissing(['project', 'notes.user', 'contacts', 'assignments.user']);

        return $this->dataResponse([
            'task' => [
                'id' => $task->id,
                'slug' => $task->slug,
                'title' => $task->title,
                'short_title' => $task->short_title,
                'title_np' => $task->title_np,
                'description' => $task->description,
                'item_number' => $task->item_number,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'progress' => (int) $task->progress,
                'category' => $task->category,
                'category_label' => $task->category_label,
                'category_color' => $task->category_color,
                'deadline_at' => $task->deadline_at?->toDateString(),
                'days_relative_label' => $task->days_relative_label,
                'responsible_ministry' => $task->responsible_ministry,
                'project' => $task->project ? [
                    'slug' => $task->project->slug,
                    'title' => $task->project->title,
                ] : null,
                'assignees' => $task->assignments->map(fn ($a) => [
                    'id' => $a->user?->id,
                    'name' => $a->user?->name,
                ])->all(),
            ],
            'notes' => NoteResource::collection($task->notes)->resolve(),
            'contacts' => ContactResource::collection($task->contacts)->resolve(),
        ]);
    }
}
