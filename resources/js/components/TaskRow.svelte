<script lang="ts">
    import { initials } from '../lib/format';
    import { peek } from '../lib/peek.svelte';
    import type { Task } from '../lib/types';
    import DateChip from './DateChip.svelte';
    import PriorityFlag from './PriorityFlag.svelte';
    import StatusBadge from './StatusBadge.svelte';
    import StatusChip from './StatusChip.svelte';

    let {
        task,
        project,
        showProject = false,
        compact = false,
    }: { task: Task; project?: { slug: string } | null; showProject?: boolean; compact?: boolean } = $props();

    // Chips PATCH /workspace/projects/{project:slug}/tasks/{task:slug}; prefer
    // an explicit `project` prop, then the nested resource. Without either the
    // row degrades to read-only badges.
    const projectSlug = $derived(project?.slug ?? task.project?.slug ?? null);

    const assignees = $derived(task.assignments ?? []);

    function openPeek() {
        peek.open({ id: task.id, slug: task.slug });
    }
</script>

<div
    role="button"
    tabindex="0"
    class="group block w-full cursor-pointer rounded-lg border border-neutral-200 bg-white p-3 text-left transition hover:border-amber-300 hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-amber-500/40"
    class:p-2={compact}
    onclick={openPeek}
    onkeydown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openPeek();
        }
    }}
>
    <div class="flex items-start gap-3">
        {#if task.item_number}
            <span
                class="mt-0.5 rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-xs text-neutral-500 select-none dark:bg-neutral-800 dark:text-neutral-400"
            >
                #{task.item_number}
            </span>
        {/if}
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-2">
                <h3
                    class="truncate text-sm font-medium text-neutral-900 group-hover:text-amber-700 dark:text-neutral-100 dark:group-hover:text-amber-400"
                >
                    {task.short_title || task.title}
                </h3>
                {#if showProject && task.project}
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">in {task.project.title}</span>
                {/if}
            </div>

            {#if !compact && task.description}
                <p class="mt-1 line-clamp-2 text-sm text-neutral-600 dark:text-neutral-400">{task.description}</p>
            {/if}

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                {#if projectSlug}
                    <StatusChip {task} {projectSlug} />
                    <PriorityFlag {task} {projectSlug} quiet />
                    <DateChip {task} {projectSlug} ghost />
                {:else}
                    <StatusBadge status={task.status} label={task.status_label} />
                {/if}
                {#if task.progress > 0}
                    <span class="text-neutral-500 dark:text-neutral-400">{task.progress}%</span>
                {/if}
                {#if task.category_label}
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                        style="background-color: {task.category_color}15; color: {task.category_color}; --tw-ring-color: {task.category_color}40;"
                        >{task.category_label}</span
                    >
                {/if}
                {#if task.responsible_ministry}
                    <span class="text-neutral-500 dark:text-neutral-400">{task.responsible_ministry}</span>
                {/if}
            </div>
        </div>

        {#if assignees.length > 0}
            <div class="flex -space-x-1.5">
                {#each assignees.slice(0, 3) as a (a.id)}
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-neutral-200 text-[10px] font-semibold text-neutral-700 dark:border-neutral-900 dark:bg-neutral-700 dark:text-neutral-200"
                        title={a.member?.name}>{initials(a.member?.name)}</span
                    >
                {/each}
                {#if assignees.length > 3}
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-neutral-100 text-[10px] font-semibold text-neutral-500 dark:border-neutral-900 dark:bg-neutral-800 dark:text-neutral-400"
                    >
                        +{assignees.length - 3}
                    </span>
                {/if}
            </div>
        {/if}
    </div>
</div>
