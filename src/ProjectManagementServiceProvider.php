<?php

namespace RayzenAI\ProjectManagement;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RayzenAI\ProjectManagement\Console\Commands\PruneTrashedWorkspaceModels;
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
        if (! Gate::has('manage-workspace')) {
            Gate::define('manage-workspace', function ($user): bool {
                if (! empty($user->is_super_admin)) {
                    return true;
                }

                return in_array(
                    $user->email,
                    (array) config('project-management.super_admins', []),
                    true,
                );
            });
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/workspace.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->publishes([
            __DIR__.'/../config/project-management.php' => config_path('project-management.php'),
        ], 'project-management-config');

        // Morph map for the project-management entities. enforceMorphMap()
        // merges with whatever has already been registered, so this is safe
        // alongside the host-app's own morph map registrations.
        //
        // enforceMorphMap makes the map STRICT app-wide: any model used as a
        // morph target must have an entry. The configured user model is itself a
        // morph target (Sanctum's personal_access_tokens.tokenable), so it must
        // be mapped here or `createToken()` throws ClassMorphViolationException.
        Relation::enforceMorphMap([
            'user' => config('project-management.user_model'),
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
                PruneTrashedWorkspaceModels::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('workspace:prune-trashed')->daily();
        });
    }
}
