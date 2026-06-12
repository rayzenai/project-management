<script lang="ts">
    import AppShell from '../components/AppShell.svelte';
    import { formatRelative } from '../lib/format';

    type StatusSlice = { value: string; label: string; color: string; count: number };
    type ProjectRow = {
        slug: string;
        title: string;
        tasks_count: number;
        percent_complete: number;
        stalled: number;
        due_this_week: number;
        status_breakdown: StatusSlice[];
    };
    type Activity = {
        id: number;
        description: string | null;
        user_name: string | null;
        task_title: string | null;
        task_slug: string | null;
        project_slug: string | null;
        happened_at: string | null;
    };
    type Stats = {
        projects: number;
        tasks: number;
        percent_complete: number;
        due_this_week: number;
        stalled: number;
    };

    let {
        stats,
        status_breakdown,
        projects,
        recent_activity,
    }: {
        stats: Stats;
        status_breakdown: StatusSlice[];
        projects: ProjectRow[];
        recent_activity: Activity[];
    } = $props();

    function nonZero(slices: StatusSlice[]): StatusSlice[] {
        return slices.filter((s) => s.count > 0);
    }

    function pct(slice: StatusSlice, slices: StatusSlice[]): number {
        const total = slices.reduce((sum, s) => sum + s.count, 0);
        return total === 0 ? 0 : (slice.count / total) * 100;
    }

    const statCards = $derived([
        { label: 'Projects', value: stats.projects, tone: 'text-neutral-900 dark:text-neutral-100' },
        { label: 'Tasks', value: stats.tasks, tone: 'text-neutral-900 dark:text-neutral-100' },
        { label: '% Complete', value: `${stats.percent_complete}%`, tone: 'text-emerald-600 dark:text-emerald-400' },
        { label: 'Due This Week', value: stats.due_this_week, tone: 'text-amber-600 dark:text-amber-500' },
        { label: 'Stalled', value: stats.stalled, tone: 'text-red-600 dark:text-red-400' },
    ]);
</script>

<svelte:head><title>Overview · Workspace</title></svelte:head>

<AppShell>
    <header class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Workspace Overview</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Portfolio health across all projects.</p>
    </header>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {#each statCards as card (card.label)}
            <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <div class="text-2xl font-bold {card.tone}">{card.value}</div>
                <div class="mt-1 text-xs font-medium tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                    {card.label}
                </div>
            </div>
        {/each}
    </div>

    <section class="mb-6 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">Status breakdown</h2>
        {#if nonZero(status_breakdown).length > 0}
            <div class="flex h-3 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                {#each nonZero(status_breakdown) as slice (slice.value)}
                    <div
                        class="h-full"
                        style="width: {pct(slice, status_breakdown)}%; background-color: {slice.color};"
                        title={`${slice.label}: ${slice.count}`}
                    ></div>
                {/each}
            </div>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5">
                {#each nonZero(status_breakdown) as slice (slice.value)}
                    <span class="inline-flex items-center gap-1.5 text-xs text-neutral-600 dark:text-neutral-400">
                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {slice.color};"></span>
                        {slice.label} · {slice.count}
                    </span>
                {/each}
            </div>
        {:else}
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No tasks yet.</p>
        {/if}
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <h2 class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">Projects</h2>
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                {#each projects as project (project.slug)}
                    <a
                        href={`/workspace/projects/${project.slug}`}
                        class="flex items-center gap-4 border-b border-neutral-100 px-4 py-3 last:border-0 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800/50"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">{project.title}</div>
                            <div class="mt-1.5 flex h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                {#each nonZero(project.status_breakdown) as slice (slice.value)}
                                    <div
                                        class="h-full"
                                        style="width: {pct(slice, project.status_breakdown)}%; background-color: {slice.color};"
                                    ></div>
                                {/each}
                            </div>
                            <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {project.tasks_count} tasks
                                {#if project.stalled > 0}· <span class="text-red-600 dark:text-red-400">{project.stalled} stalled</span>{/if}
                                {#if project.due_this_week > 0}· <span class="text-amber-600 dark:text-amber-500"
                                        >{project.due_this_week} due this week</span
                                    >{/if}
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{project.percent_complete}%</div>
                            <div class="text-[10px] tracking-wider text-neutral-400 uppercase">complete</div>
                        </div>
                    </a>
                {:else}
                    <p class="px-4 py-6 text-sm text-neutral-500 dark:text-neutral-400">No projects yet.</p>
                {/each}
            </div>
        </section>

        <aside>
            <h2 class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">Recent activity</h2>
            <div class="rounded-xl border border-neutral-200 bg-white p-2 dark:border-neutral-800 dark:bg-neutral-900">
                {#each recent_activity as item (item.id)}
                    <div class="border-b border-neutral-100 px-2 py-2.5 last:border-0 dark:border-neutral-800">
                        <p class="text-sm text-neutral-700 dark:text-neutral-300">{item.description}</p>
                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                            {#if item.user_name}<span>{item.user_name}</span>{/if}
                            {#if item.task_title && item.task_slug && item.project_slug}
                                · <a href={`/workspace/projects/${item.project_slug}/tasks/${item.task_slug}`} class="truncate hover:underline"
                                    >{item.task_title}</a
                                >
                            {/if}
                            {#if item.happened_at}· <span>{formatRelative(item.happened_at)}</span>{/if}
                        </div>
                    </div>
                {:else}
                    <p class="px-2 py-6 text-sm text-neutral-500 dark:text-neutral-400">No recent activity.</p>
                {/each}
            </div>
        </aside>
    </div>
</AppShell>
