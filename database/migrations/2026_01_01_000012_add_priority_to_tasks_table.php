<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Priority moves from the per-person assignment to the task itself so boards
 * and manager views can show it for any task, assigned or not. Existing tasks
 * are backfilled with the most urgent priority among their assignments;
 * `project_assignments.priority` is deprecated (kept for now, no longer
 * written by the UI).
 */
return new class extends Migration
{
    private const RANK = ['low' => 0, 'medium' => 1, 'high' => 2, 'urgent' => 3];

    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priority', 16)->default('medium')->index();
        });

        DB::table('project_assignments')
            ->whereNotNull('priority')
            ->get(['task_id', 'priority'])
            ->groupBy('task_id')
            ->each(function ($rows, int $taskId) {
                $top = $rows->sortByDesc(fn ($row) => self::RANK[$row->priority] ?? 1)->first();

                if (($top->priority ?? 'medium') !== 'medium' && isset(self::RANK[$top->priority])) {
                    DB::table('tasks')->where('id', $taskId)->update(['priority' => $top->priority]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
