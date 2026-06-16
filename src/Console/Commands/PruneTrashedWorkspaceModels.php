<?php

namespace RayzenAI\ProjectManagement\Console\Commands;

use Illuminate\Console\Command;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\ProjectAssignment;
use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\ProjectNote;
use RayzenAI\ProjectManagement\Models\Subtask;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;

class PruneTrashedWorkspaceModels extends Command
{
    protected $signature = 'workspace:prune-trashed';

    protected $description = 'Force-delete workspace rows trashed longer than the configured TTL.';

    /** @var list<class-string> */
    private array $models = [
        Task::class, Subtask::class, ProjectNote::class, ProjectContact::class,
        ProjectAssignment::class, WorkspaceNote::class, Team::class, Member::class,
    ];

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('project-management.trash_ttl_days', 30));
        $total = 0;

        foreach ($this->models as $model) {
            $rows = $model::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();
            foreach ($rows as $row) {
                $row->forceDelete();
                $total++;
            }
        }

        $this->info("Pruned {$total} trashed row(s).");

        return self::SUCCESS;
    }
}
