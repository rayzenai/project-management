<?php

namespace RayzenAI\ProjectManagement\Console\Commands;

use Illuminate\Console\Command;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\TaskComment;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;

class PruneTrashedWorkspaceModels extends Command
{
    protected $signature = 'workspace:prune-trashed {--pretend : Count what would be pruned without deleting}';

    protected $description = 'Force-delete workspace rows trashed longer than the configured TTL.';

    /** @var list<class-string> */
    private array $models = [
        Task::class, Subtask::class, ProjectNote::class, ProjectContact::class,
        ProjectAssignment::class, WorkspaceNote::class, Team::class, Member::class,
        TaskComment::class,
    ];

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('project-management.trash_ttl_days', 30));
        $pretend = (bool) $this->option('pretend');
        $total = 0;

        foreach ($this->models as $model) {
            $count = 0;

            foreach ($model::onlyTrashed()->where('deleted_at', '<', $cutoff)->cursor() as $row) {
                if (! $pretend) {
                    $row->forceDelete();
                }

                $count++;
            }

            if ($count > 0) {
                $this->line(class_basename($model).": {$count}");
            }

            $total += $count;
        }

        if ($pretend) {
            $this->info("[pretend] Would prune {$total} trashed row(s).");
        } else {
            $this->info("Pruned {$total} trashed row(s).");
        }

        return self::SUCCESS;
    }
}
