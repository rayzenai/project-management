<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { peek } from '../../lib/peek.svelte';
    import type { SharedProps, Task } from '../../lib/types';
    import AssigneeStack from '../AssigneeStack.svelte';
    import DateChip from '../DateChip.svelte';
    import PriorityFlag from '../PriorityFlag.svelte';

    let {
        task,
        projectSlug,
        isDragging,
        ondragstart,
        ondragend,
        ondragover,
    }: {
        task: Task;
        projectSlug: string;
        isDragging: boolean;
        ondragstart: (e: DragEvent) => void;
        ondragend: () => void;
        ondragover: (e: DragEvent) => void;
    } = $props();

    const team = $derived(((page.props ?? {}) as unknown as SharedProps).quickAddContext?.team ?? []);
</script>

<div
    role="listitem"
    draggable="true"
    {ondragstart}
    {ondragend}
    {ondragover}
    onclick={() => peek.open({ id: task.id, slug: task.slug })}
    onkeydown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            peek.open({ id: task.id, slug: task.slug });
        }
    }}
    tabindex="0"
    class={`group cursor-grab rounded-lg border bg-white p-3 shadow-sm transition select-none hover:border-amber-300 active:cursor-grabbing dark:bg-neutral-900 dark:hover:border-amber-500/40 ${
        isDragging ? 'border-dashed border-neutral-400 opacity-40 dark:border-neutral-500' : 'border-neutral-200 dark:border-neutral-800'
    }`}
>
    <div class="flex items-start gap-2">
        {#if task.item_number}
            <span
                class="rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-[10px] text-neutral-500 select-none dark:bg-neutral-800 dark:text-neutral-400"
            >
                #{task.item_number}
            </span>
        {/if}
        <h4 class="flex-1 text-sm leading-snug font-medium text-neutral-900 dark:text-neutral-100">
            {task.short_title || task.title}
        </h4>
    </div>

    <div class="mt-2 flex items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-1.5">
            <PriorityFlag {task} {projectSlug} quiet />
            <DateChip {task} {projectSlug} size="sm" ghost />
        </div>
        <AssigneeStack {task} {team} size="sm" />
    </div>
</div>
