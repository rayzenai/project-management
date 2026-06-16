<?php

use Illuminate\Support\Facades\Route;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\AssignmentController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\ContactController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\DashboardController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\MemberController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\MyWorkspaceController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\NoteController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\PlanTrackerController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\ProjectController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\QuickAddController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\SubtaskController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TaskController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TaskPreviewController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TaskReorderController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TaskSearchController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TeamController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\TeamMemberController;
use RayzenAI\ProjectManagement\Http\Controllers\Workspace\WorkspaceNoteController;
use RayzenAI\ProjectManagement\Http\Middleware\ShareWorkspaceData;

Route::middleware([...config('project-management.middleware', ['web', 'auth']), ShareWorkspaceData::class])
    ->prefix('workspace')
    ->name('workspace.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('home');

        Route::get('/my', MyWorkspaceController::class)->name('my');
        Route::post('/quick-add', QuickAddController::class)->name('quick-add');
        Route::get('/search', TaskSearchController::class)->name('search');

        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('/teams/{team}/restore', [TeamController::class, 'restore'])->name('teams.restore')->withTrashed();
        Route::post('/teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
        Route::delete('/teams/{team}/members/{member}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
        Route::patch('/teams/{team}/members/{member}', [TeamMemberController::class, 'updateRole'])->name('teams.members.role');

        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
        Route::post('/members/{member}/restore', [MemberController::class, 'restore'])->name('members.restore')->withTrashed();

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('/projects/{project:slug}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
        Route::patch('/projects/{project:slug}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

        Route::post('/projects/{project:slug}/tasks/reorder', TaskReorderController::class)
            ->name('tasks.reorder');

        Route::get('/tasks/{task}/preview', TaskPreviewController::class)
            ->name('tasks.preview');

        Route::post('/projects/{project:slug}/tasks', [TaskController::class, 'store'])
            ->scopeBindings()
            ->name('tasks.store');
        Route::get('/projects/{project:slug}/tasks/{task:slug}', [TaskController::class, 'show'])
            ->scopeBindings()
            ->name('tasks.show');
        Route::patch('/projects/{project:slug}/tasks/{task:slug}', [TaskController::class, 'update'])
            ->scopeBindings()
            ->name('tasks.update');
        Route::delete('/projects/{project:slug}/tasks/{task:slug}', [TaskController::class, 'destroy'])
            ->scopeBindings()
            ->name('tasks.destroy');
        Route::post('/projects/{project:slug}/tasks/{task:slug}/restore', [TaskController::class, 'restore'])
            ->scopeBindings()
            ->withTrashed()
            ->name('tasks.restore');

        Route::post('/tasks/{task}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::patch('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/assignments/{assignment}/restore', [AssignmentController::class, 'restore'])->name('assignments.restore')->withTrashed();

        Route::post('/tasks/{task}/notes', [NoteController::class, 'store'])->name('notes.store');
        Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        Route::post('/notes/{note}/restore', [NoteController::class, 'restore'])->name('notes.restore')->withTrashed();

        Route::post('/my-notes', [WorkspaceNoteController::class, 'store'])->name('my-notes.store');
        Route::patch('/my-notes/{workspaceNote}', [WorkspaceNoteController::class, 'update'])->name('my-notes.update');
        Route::patch('/my-notes/{workspaceNote}/placement', [WorkspaceNoteController::class, 'placement'])->name('my-notes.placement');
        Route::delete('/my-notes/{workspaceNote}', [WorkspaceNoteController::class, 'destroy'])->name('my-notes.destroy');
        Route::post('/my-notes/{workspaceNote}/restore', [WorkspaceNoteController::class, 'restore'])->name('my-notes.restore')->withTrashed();

        Route::post('/tasks/{task}/contacts', [ContactController::class, 'store'])->name('contacts.store');

        Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('subtasks.store');
        Route::patch('/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('subtasks.update');
        Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('subtasks.destroy');
        Route::post('/subtasks/{subtask}/restore', [SubtaskController::class, 'restore'])->name('subtasks.restore')->withTrashed();

        Route::get('/100-point-tracker', PlanTrackerController::class)->name('plan-tracker');
    });
