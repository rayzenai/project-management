<?php

namespace RayzenAI\ProjectManagement;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use RayzenAI\ProjectManagement\Console\Commands\SendProjectWeeklyDigest;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Observers\ProjectAssignmentObserver;
use RayzenAI\ProjectManagement\Observers\ProjectContactObserver;
use RayzenAI\ProjectManagement\Observers\ProjectNoteObserver;
use RayzenAI\ProjectManagement\Observers\SubtaskObserver;
use RayzenAI\ProjectManagement\Observers\TaskObserver;

class ProjectManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/project-management.php', 'project-management');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/workspace.php');

        $this->publishes([
            __DIR__.'/../config/project-management.php' => config_path('project-management.php'),
        ], 'project-management-config');

        // Morph map for the project-management entities. enforceMorphMap()
        // merges with whatever has already been registered, so this is safe
        // alongside the host-app's own morph map registrations.
        Relation::enforceMorphMap([
            'task' => Task::class,
            'project-note' => ProjectNote::class,
            'project-contact' => ProjectContact::class,
            'subtask' => Subtask::class,
            'project-assignment' => ProjectAssignment::class,
            'team' => Team::class,
            'member' => Member::class,
        ]);

        Task::observe(TaskObserver::class);
        ProjectNote::observe(ProjectNoteObserver::class);
        ProjectContact::observe(ProjectContactObserver::class);
        Subtask::observe(SubtaskObserver::class);
        ProjectAssignment::observe(ProjectAssignmentObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendProjectWeeklyDigest::class,
            ]);
        }
    }
}
