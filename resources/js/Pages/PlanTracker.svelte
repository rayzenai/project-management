<script lang="ts">
    import AppShell from '../components/AppShell.svelte';
    import TaskRow from '../components/TaskRow.svelte';
    import type { Project, Task } from '../lib/types';

    let {
        project,
        tasks,
        categories,
        statuses,
    }: {
        project: Project;
        tasks: Task[];
        categories: Record<string, { label: string; color: string }>;
        statuses: Record<string, { label: string; color: string }>;
        deadlineTypes: Record<string, { label: string; days?: number }>;
    } = $props();

    let categoryFilter = $state<string | null>(null);
    let statusFilter = $state<string | null>(null);
    let query = $state('');

    const filtered = $derived(
        tasks.filter((t) => {
            if (categoryFilter && t.category !== categoryFilter) return false;
            if (statusFilter && t.status !== statusFilter) return false;
            if (query.trim()) {
                const q = query.toLowerCase();
                const hay = `${t.title} ${t.title_np ?? ''} ${t.responsible_ministry ?? ''}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        }),
    );

    const counts = $derived.by(() => {
        const out: Record<string, number> = {};
        for (const t of tasks) {
            const c = t.category ?? 'uncategorized';
            out[c] = (out[c] ?? 0) + 1;
        }
        return out;
    });
</script>

<svelte:head><title>100-Point Tracker · Workspace</title></svelte:head>

<AppShell>
    <header class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">100-Point Tracker</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            The full Government 100-Day Plan, filterable by category, status, and ministry.
        </p>
    </header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <input
            type="text"
            bind:value={query}
            placeholder="Search tasks, ministries..."
            class="w-full max-w-xs rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        />
        <select
            bind:value={categoryFilter}
            class="rounded-md border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        >
            <option value={null}>All categories</option>
            {#each Object.entries(categories) as [slug, info] (slug)}
                <option value={slug}>{info.label} ({counts[slug] ?? 0})</option>
            {/each}
        </select>
        <select
            bind:value={statusFilter}
            class="rounded-md border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        >
            <option value={null}>All statuses</option>
            {#each Object.entries(statuses) as [slug, info] (slug)}
                <option value={slug}>{info.label}</option>
            {/each}
        </select>
        <div class="flex-1"></div>
        <span class="text-xs text-neutral-500 dark:text-neutral-400">{filtered.length} / {tasks.length} tasks</span>
    </div>

    <div class="space-y-2">
        {#each filtered as task (task.id)}
            <TaskRow {task} {project} />
        {:else}
            <p
                class="rounded-xl border border-dashed border-neutral-300 bg-white p-6 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400"
            >
                No tasks match those filters.
            </p>
        {/each}
    </div>
</AppShell>
