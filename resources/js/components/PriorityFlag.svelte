<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import type { Priority, Task } from '../lib/types';
    import Popover from './Popover.svelte';

    let {
        task,
        projectSlug,
        quiet = false,
        onUpdated,
    }: {
        task: Pick<Task, 'id' | 'slug' | 'priority'>;
        projectSlug: string;
        /** Rows pass true: medium (the default) renders invisibly until hover to cut noise. */
        quiet?: boolean;
        onUpdated?: (priority: Priority) => void;
    } = $props();

    const OPTIONS: { value: Priority; label: string; flag: string }[] = [
        { value: 'urgent', label: 'Urgent', flag: 'text-red-600 dark:text-red-400' },
        { value: 'high', label: 'High', flag: 'text-orange-500 dark:text-orange-400' },
        { value: 'medium', label: 'Medium', flag: 'text-neutral-400 dark:text-neutral-500' },
        { value: 'low', label: 'Low', flag: 'text-neutral-300 dark:text-neutral-600' },
    ];

    let open = $state(false);
    let optimistic = $state<Priority | null>(null);
    let failed = $state(false);

    const shown = $derived(optimistic ?? task.priority ?? 'medium');
    const meta = $derived(OPTIONS.find((o) => o.value === shown) ?? OPTIONS[2]);
    const hidden = $derived(quiet && shown === 'medium');

    $effect(() => {
        if (optimistic !== null && task.priority === optimistic) optimistic = null;
    });

    function setPriority(value: Priority) {
        open = false;
        if (value === shown) return;
        const previous = shown;
        optimistic = value;
        onUpdated?.(value);

        router.patch(
            `/workspace/projects/${projectSlug}/tasks/${task.slug}`,
            { priority: value },
            {
                preserveState: true,
                preserveScroll: true,
                onError: () => {
                    optimistic = null;
                    onUpdated?.(previous);
                    failed = true;
                    setTimeout(() => (failed = false), 2000);
                },
            },
        );
    }
</script>

<Popover
    bind:open
    role="listbox"
    triggerLabel={`Priority: ${meta.label}`}
    triggerClass={hidden ? 'opacity-0 transition group-hover:opacity-100' : ''}
>
    {#snippet trigger()}
        <span class={`text-sm leading-none ${meta.flag} ${failed ? 'rounded ring-2 ring-red-400' : ''}`}>⚑</span>
    {/snippet}

    {#each OPTIONS as option (option.value)}
        <button
            type="button"
            data-popover-item
            role="option"
            aria-selected={option.value === shown}
            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800"
            onclick={() => setPriority(option.value)}
        >
            <span class={option.flag}>⚑</span>
            <span class="flex-1">{option.label}</span>
            {#if option.value === shown}<span class="text-amber-600 dark:text-amber-400">✓</span>{/if}
        </button>
    {/each}
</Popover>
