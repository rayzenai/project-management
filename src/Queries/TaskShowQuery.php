<?php

namespace RayzenAI\ProjectManagement\Queries;

use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Http\Resources\TaskResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;

/**
 * Assembles the single-task payload (task, its notes/contacts, the current
 * user's subtasks, and the assignee candidate list) shared by the Inertia web
 * show page and the JSON API show endpoint.
 */
class TaskShowQuery
{
    /**
     * @return array{
     *     project: array<string, mixed>,
     *     task: array<string, mixed>,
     *     notes: array<int, array<string, mixed>>,
     *     contacts: array<int, array<string, mixed>>,
     *     subtasks: array<int, array<string, mixed>>,
     *     team: array<int, array{id: int, name: string, email: ?string, user_id: ?int}>
     * }
     */
    public function data(Project $project, Task $task, int $userId): array
    {
        $task->load(['assignments.member', 'notes.user', 'contacts', 'project']);

        $team = Member::assignableFor($project)->get(['id', 'name', 'email', 'user_id']);

        $mySubtasks = Subtask::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->orderBy('is_done')
            ->orderBy('position')
            ->get();

        return [
            'project' => (new ProjectResource($project))->resolve(),
            'task' => (new TaskResource($task))->resolve(),
            'notes' => NoteResource::collection($task->notes)->resolve(),
            'contacts' => ContactResource::collection($task->contacts)->resolve(),
            'subtasks' => SubtaskResource::collection($mySubtasks)->resolve(),
            'team' => $team->map(fn (Member $m): array => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'user_id' => $m->user_id])->all(),
        ];
    }
}
