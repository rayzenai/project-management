<?php

namespace RayzenAI\ProjectManagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Notifications\TaskDeadlineDue;

class SendProjectDeadlineReminders extends Command
{
    protected $signature = 'workspace:send-deadline-reminders {--pretend}';

    protected $description = 'Notify assignees of upcoming and overdue task deadlines.';

    public function handle(): int
    {
        $pretend = (bool) $this->option('pretend');
        $today = today();
        $leadDays = array_map('intval', (array) config('project-management.reminders.reminder_lead_days', [2]));
        $repeat = max(1, (int) config('project-management.reminders.overdue_repeat_days', 3));

        $tasks = Task::query()
            ->whereNotIn('status', Task::completeStatuses())
            ->whereNotNull('deadline_at')
            ->with('assignments.member.user')
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $window = $this->windowFor($task->deadline_at, $today, $leadDays);
            if ($window === null) {
                continue;
            }

            $referenceDate = $this->referenceDate($window, $task->deadline_at, $today, $repeat);

            if (! $pretend && ! $this->claim($task->id, $window, $referenceDate)) {
                continue;
            }

            $users = $task->assignments->pluck('member.user')->filter()->unique('id');

            foreach ($users as $user) {
                $sent++;
                if (! $pretend) {
                    $user->notify(new TaskDeadlineDue($task, $window));
                }
            }
        }

        $this->info(($pretend ? '[pretend] ' : '')."Reminders dispatched: {$sent}.");

        return self::SUCCESS;
    }

    /** @param  list<int>  $leadDays */
    private function windowFor(Carbon $deadline, Carbon $today, array $leadDays): ?string
    {
        $deadline = $deadline->copy()->startOfDay();

        if ($deadline->lt($today)) {
            return 'overdue';
        }
        if ($deadline->equalTo($today)) {
            return 'due_today';
        }
        if (in_array((int) $today->diffInDays($deadline), $leadDays, true)) {
            return 'heads_up';
        }

        return null;
    }

    private function referenceDate(string $window, Carbon $deadline, Carbon $today, int $repeat): Carbon
    {
        if ($window !== 'overdue') {
            return $deadline->copy()->startOfDay();
        }

        $daysOverdue = (int) $deadline->copy()->startOfDay()->diffInDays($today);
        $bucket = intdiv($daysOverdue, $repeat);

        return $deadline->copy()->startOfDay()->addDays($bucket * $repeat);
    }

    private function claim(int $taskId, string $window, Carbon $referenceDate): bool
    {
        try {
            DB::table('task_reminder_logs')->insert([
                'task_id' => $taskId,
                'window' => $window,
                'reference_date' => $referenceDate->toDateString(),
                'sent_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
