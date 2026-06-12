<?php

namespace RayzenAI\ProjectManagement\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RayzenAI\ProjectManagement\Models\Task;

class ProjectProgress
{
    public static function meta(): array
    {
        $oath = Carbon::parse(config('government.oath_date'));
        $total = (int) config('government.plan_total_days');
        $today = Carbon::today();
        $day = max(0, min($total, (int) $oath->diffInDays($today, false)));

        return [
            'pm_name' => config('government.pm_name'),
            'pm_name_np' => config('government.pm_name_np'),
            'cabinet_label' => config('government.cabinet_label'),
            'plan_short_name' => config('government.plan_short_name'),
            'oath_date' => $oath->toDateString(),
            'oath_date_display' => $oath->format('j M Y'),
            'today' => $today->toDateString(),
            'today_display' => $today->format('j M Y'),
            'plan_total_days' => $total,
            'day_number' => $day,
        ];
    }

    /**
     * Aggregated status buckets used by the UI. Each bucket maps to one or more raw statuses.
     *
     * @var array<string, list<string>>
     */
    public const STATUS_BUCKETS = [
        'done' => ['done', 'done_late'],
        'in_progress' => ['in_progress'],
        'late' => ['late', 'failed'],
        'unclear' => ['not_started', 'unclear'],
    ];

    /**
     * @param  Collection<int, Task>|null  $items
     * @return array<string, int>
     */
    public static function counts(?Collection $items = null): array
    {
        $items = $items ?? Task::all();

        return [
            'total' => $items->count(),
            'done' => $items->whereIn('status', self::STATUS_BUCKETS['done'])->count(),
            'in_progress' => $items->whereIn('status', self::STATUS_BUCKETS['in_progress'])->count(),
            'late' => $items->whereIn('status', self::STATUS_BUCKETS['late'])->count(),
            'unclear' => $items->whereIn('status', self::STATUS_BUCKETS['unclear'])->count(),
        ];
    }

    /**
     * @return array<int, array{slug: string, label: string, color: string, count: int}>
     */
    public static function categoryStats(): array
    {
        $counts = Task::query()
            ->selectRaw("metadata->>'category' as category, COUNT(*) as count")
            ->whereRaw("metadata->>'category' IS NOT NULL")
            ->groupBy(DB::raw("metadata->>'category'"))
            ->pluck('count', 'category');

        $configured = config('government.categories');

        return collect($configured)
            ->map(fn ($info, $slug) => [
                'slug' => $slug,
                'label' => $info['label'],
                'color' => $info['color'],
                'count' => (int) ($counts[$slug] ?? 0),
            ])
            ->filter(fn ($c) => $c['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
