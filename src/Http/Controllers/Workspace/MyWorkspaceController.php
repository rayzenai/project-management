<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use RayzenAI\ProjectManagement\Http\Resources\AssignmentResource;
use RayzenAI\ProjectManagement\Http\Resources\ContactResource;
use RayzenAI\ProjectManagement\Http\Resources\NoteResource;
use RayzenAI\ProjectManagement\Http\Resources\ProjectResource;
use RayzenAI\ProjectManagement\Http\Resources\SubtaskResource;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Subtask;

class MyWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $now = now()->startOfDay();

        $assignments = ProjectAssignment::query()
            ->with(['task.project', 'task.assignments.user', 'user'])
            ->where('user_id', $user->id)
            ->whereHas('task', fn ($q) => $q->incomplete())
            ->where(function ($q) use ($now) {
                $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', $now);
            })
            ->orderByDesc('is_focused')
            ->orderByDesc('created_at')
            ->get();

        $snoozedCount = ProjectAssignment::query()
            ->where('user_id', $user->id)
            ->where('snoozed_until', '>', $now)
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

        $projects = Project::query()->orderBy('title')->get(['id', 'slug', 'title']);

        $teamUsers = config('project-management.user_model', User::class)::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $openTodos = Subtask::query()
            ->with(['task.project'])
            ->where('user_id', $user->id)
            ->where('is_done', false)
            ->orderByRaw('due_at IS NULL ASC')
            ->orderBy('due_at')
            ->orderBy('position')
            ->get();

        return Inertia::render('MyWorkspace', [
            'assignments' => AssignmentResource::collection($assignments)->resolve(),
            'snoozedCount' => $snoozedCount,
            'openTodos' => SubtaskResource::collection($openTodos)->resolve(),
            'recentNotes' => NoteResource::collection($recentNotes)->resolve(),
            'recentContacts' => ContactResource::collection($recentContacts)->resolve(),
            'projects' => ProjectResource::collection($projects)->resolve(),
            'team' => $teamUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all(),
        ]);
    }
}
