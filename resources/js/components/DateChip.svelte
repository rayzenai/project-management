<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { formatDate, formatRelative } from '../lib/format';
    import type { Task } from '../lib/types';
    import Popover from './Popover.svelte';

    let {
        task,
        projectSlug,
        size = 'md',
        ghost = false,
        onUpdated,
    }: {
        task: Pick<Task, 'id' | 'slug' | 'deadline_at'>;
        projectSlug: string;
        size?: 'sm' | 'md';
        /** Render the empty state only on row hover (rows pass true; the Peek keeps it always visible). */
        ghost?: boolean;
        onUpdated?: (deadline_at: string | null) => void;
    } = $props();

    let open = $state(false);
    /** undefined = no pending override; null = optimistically cleared. */
    let optimistic = $state<string | null | undefined>(undefined);
    let failed = $state(false);

    const shown = $derived(optimistic !== undefined ? optimistic : (task.deadline_at ?? null));
    const relative = $derived(formatRelative(shown));
    const overdue = $derived(relative.includes('overdue'));
    const dueToday = $derived(relative === 'today');

    $effect(() => {
        if (optimistic !== undefined && (task.deadline_at ?? null) === optimistic) optimistic = undefined;
    });

    function setDeadline(value: string | null) {
        open = false;
        if (value === shown) return;
        const previous = shown;
        optimistic = value;
        onUpdated?.(value);

        router.patch(
            `/workspace/projects/${projectSlug}/tasks/${task.slug}`,
            { deadline_at: value },
            {
                preserveState: true,
                preserveScroll: true,
                onError: () => {
                    optimistic = undefined;
                    onUpdated?.(previous);
                    failed = true;
                    setTimeout(() => (failed = false), 2000);
                },
            },
        );
    }
</script>

<Popover bind:open role="dialog" align="right" triggerLabel={shown ? `Deadline ${formatDate(shown)}` : 'Set deadline'}>
    {#snippet trigger()}
        {#if shown}
            <span
                class={`inline-flex items-center gap-1 rounded-full font-mono whitespace-nowrap ring-1 ring-inset ${
                    size === 'sm' ? 'px-1.5 py-px text-[10px]' : 'px-2 py-0.5 text-[11px]'
                } ${
                    overdue
                        ? 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30'
                        : dueToday
                          ? 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30'
                          : 'bg-neutral-50 text-neutral-600 ring-neutral-200 dark:bg-neutral-800/60 dark:text-neutral-400 dark:ring-neutral-700'
                } ${failed ? 'ring-2 ring-red-400' : ''}`}
            >
                ⏱ {relative}
            </span>
        {:else}
            <span
                class={`inline-flex items-center rounded-full px-1.5 py-px font-mono text-[10px] text-neutral-400 ring-1 ring-neutral-200 ring-inset dark:text-neutral-500 dark:ring-neutral-700 ${
                    ghost ? 'opacity-0 transition group-hover:opacity-100' : ''
                }`}
            >
                + date
            </span>
        {/if}
    {/snippet}

    <div class="flex items-center gap-2 px-3 py-2">
        <input
            type="date"
            value={shown ?? ''}
            class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
            onchange={(e) => setDeadline((e.currentTarget as HTMLInputElement).value || null)}
        />
        {#if shown}
            <button
                type="button"
                data-popover-item
                class="font-mono text-xs text-neutral-500 hover:text-red-600 dark:hover:text-red-400"
                onclick={() => setDeadline(null)}
            >
                Clear
            </button>
        {/if}
    </div>
</Popover>
