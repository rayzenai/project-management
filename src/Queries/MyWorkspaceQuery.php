<?php

namespace RayzenAI\ProjectManagement\Queries;

use Illuminate\Http\Request;
use RayzenAI\ProjectManagement\Http\Resources\AssignmentResource;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Subtask;

/**
 * Assembles the "My Workspace" payload shared by the Inertia web view and the
 * JSON API feed. Single source of truth for the acting member's open
 * assignments, snoozed count, todos, and their recent notes/contacts.
 */
class MyWorkspaceQuery
{
    /**
     * @return array<string, mixed>
     */
    public function get(Request $request): array
    {
        $user = $request->user();
        $member = Member::forUser($user);

        $now = now()->startOfDay();

        $assignments = ProjectAssignment::query()
            ->with(['task.project', 'task.assignments.member', 'member'])
            ->where('member_id', $member->id)
            ->whereHas('task', fn ($q) => $q->incomplete()->forActiveProjects()
                ->whereIn('project_id', Project::query()->visibleTo($user)->select('id')))
            ->where(function ($q) use ($now) {
                $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', $now);
            })
            ->orderByDesc('is_focused')
            ->orderByDesc('created_at')
            ->get();

        $snoozedCount = ProjectAssignment::query()
            ->where('member_id', $member->id)
            ->where('snoozed_until', '>', $now)
            ->whereHas('task', fn ($q) => $q->forActiveProjects())
            ->count();

        $openTaskIds = $assignments
            ->filter(fn ($a) => $a->task && ! $a->task->isComplete())
            ->pluck('task_id')
            ->unique()
            ->values()
            ->all();

        $recentNotes = $openTaskIds
            ? ProjectNote::query()
                ->with(['user', 'task.project'])
                ->whereIn('task_id', $openTaskIds)
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
            : collect();

        $recentContacts = $openTaskIds
            ? ProjectContact::query()
                ->with(['task.project'])
                ->whereIn('task_id', $openTaskIds)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get()
            : collect();

        $projects = Project::query()->visibleTo($user)->active()->orderBy('title')->get(['id', 'slug', 'title']);

        $teamMembers = Member::query()->active()->orderBy('name')->get(['id', 'name', 'email', 'user_id']);

        $openTodos = Subtask::query()
            ->with(['task.project'])
            ->where('user_id', $user->id)
            ->where('is_done', false)
            ->whereHas('task', fn ($q) => $q->forActiveProjects())
            ->orderByRaw('due_at IS NULL ASC')
            ->orderBy('due_at')
            ->orderBy('position')
            ->get();

        return [
            'assignments' => AssignmentResource::collection($assignments)->resolve(),
            'snoozedCount' => $snoozedCount,
            'openTodos' => SubtaskResource::collection($openTodos)->resolve(),
            'recentNotes' => NoteResource::collection($recentNotes)->resolve(),
            'recentContacts' => ContactResource::collection($recentContacts)->resolve(),
            'projects' => ProjectResource::collection($projects)->resolve(),
            'team' => $teamMembers->map(fn (Member $m) => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'user_id' => $m->user_id])->all(),
        ];
    }
}
