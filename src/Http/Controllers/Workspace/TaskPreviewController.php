<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Resources\AssignmentResource;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\ProjectActivity;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ApiResponser;

/**
 * JSON-only endpoint backing the Task Peek slide-over. Returns the full
 * editable context for one task — fields, assignments (with ids so the Peek
 * can unassign), subtasks, notes, contacts, recent activity, and the assignee
 * candidate list — without a full-page Inertia visit.
 */
class TaskPreviewController extends Controller
{
    use ApiResponser;

    public function __invoke(Task $task): JsonResponse
    {
        $task->loadMissing(['project', 'notes.user', 'contacts', 'assignments.member', 'subtasks.user']);

        $activity = ProjectActivity::query()
            ->where('task_id', $task->id)
            ->public()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ProjectActivity $entry): array => [
                'id' => $entry->id,
                'description' => $entry->description,
                'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name] : null,
                'created_at' => $entry->created_at?->toIso8601String(),
            ]);

        $team = Member::assignableFor($task->project)
            ->get(['id', 'name', 'email', 'user_id'])
            ->map(fn (Member $m): array => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'user_id' => $m->user_id]);

        return $this->dataResponse([
            'task' => (new TaskResource($task))->resolve(),
            'assignments' => AssignmentResource::collection($task->assignments)->resolve(),
            'subtasks' => SubtaskResource::collection($task->subtasks->sortBy('position')->values())->resolve(),
            'notes' => NoteResource::collection($task->notes)->resolve(),
            'contacts' => ContactResource::collection($task->contacts)->resolve(),
            'activity' => $activity->all(),
            'team' => $team->all(),
        ]);
    }
}
