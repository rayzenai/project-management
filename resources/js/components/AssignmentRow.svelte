<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { peek } from '../lib/peek.svelte';
    import type { Assignment, SharedProps } from '../lib/types';
    import AssigneeStack from './AssigneeStack.svelte';
    import CompleteCheckbox from './CompleteCheckbox.svelte';
    import DateChip from './DateChip.svelte';
    import Popover from './Popover.svelte';
    import PriorityFlag from './PriorityFlag.svelte';
    import StatusChip from './StatusChip.svelte';

    let { assignment, lane }: { assignment: Assignment; lane: 'due' | 'focused' | 'other' } = $props();

    let snoozeOpen = $state(false);

    const task = $derived(assignment.task);
    const projectSlug = $derived(task?.project?.slug ?? '');
    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const team = $derived(shared.quickAddContext?.team ?? []);

    const overdue = $derived.by(() => {
        if (!task?.deadline_at) return false;
        return new Date(task.deadline_at) < new Date(new Date().toDateString());
    });

    function patch(payload: Record<string, string | number | boolean | null>) {
        router.patch(`/workspace/assignments/${assignment.id}`, payload, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function toggleFocus() {
        patch({ is_focused: !assignment.is_focused });
    }

    function snooze(days: number | null) {
        if (days === null) {
            patch({ snoozed_until: null });
        } else {
            const target = new Date(Date.now() + days * 86_400_000);
            patch({ snoozed_until: target.toISOString().slice(0, 10) });
        }
        snoozeOpen = false;
    }

    function onDragStart(event: DragEvent) {
        if (!event.dataTransfer) return;
        event.dataTransfer.setData(
            'application/x-workspace-assignment',
            JSON.stringify({ assignmentId: assignment.id, isFocused: assignment.is_focused }),
        );
        event.dataTransfer.effectAllowed = 'move';
    }

    function openPeek() {
        if (task) peek.open({ id: task.id, slug: task.slug });
    }
</script>

{#if task}
    <div
        role="button"
        tabindex="0"
        draggable="true"
        ondragstart={onDragStart}
        onclick={openPeek}
        onkeydown={(e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openPeek();
            }
        }}
        class={`group flex w-full cursor-pointer flex-wrap items-center gap-2 rounded-lg border bg-white px-2.5 py-2 text-left transition hover:border-amber-300 dark:bg-neutral-900 dark:hover:border-amber-500/40 ${
            overdue && lane === 'due'
                ? 'border-l-2 border-neutral-200 border-l-red-500 dark:border-neutral-800 dark:border-l-red-400'
                : 'border-neutral-200 dark:border-neutral-800'
        }`}
    >
        <span
            aria-hidden="true"
            class="hidden w-3 shrink-0 cursor-grab font-mono text-xs text-neutral-400 opacity-0 group-hover:opacity-100 sm:block dark:text-neutral-600"
        >
            ⋮⋮
        </span>

        <CompleteCheckbox {task} {projectSlug} />
        <PriorityFlag {task} {projectSlug} quiet />

        <span class="flex min-w-0 flex-1 items-baseline gap-1.5">
            {#if task.item_number}
                <span class="shrink-0 font-mono text-xs text-neutral-500 dark:text-neutral-400">#{task.item_number}</span>
            {/if}
            <span
                class="truncate text-sm font-medium text-neutral-900 group-hover:text-amber-700 dark:text-neutral-100 dark:group-hover:text-amber-400"
            >
                {task.short_title || task.title}
            </span>
            {#if lane === 'due' && assignment.is_focused}
                <span aria-label="Pinned" class="shrink-0 text-xs text-amber-500 dark:text-amber-400">★</span>
            {/if}
        </span>

        <span class="flex shrink-0 items-center gap-1.5 max-sm:basis-full max-sm:pl-10" onclick={(e) => e.stopPropagation()} role="none">
            {#if lane !== 'other' && task.project}
                <span class="max-w-32 truncate font-mono text-[11px] text-neutral-500 dark:text-neutral-400">{task.project.title}</span>
            {/if}
            <StatusChip {task} {projectSlug} size="sm" />
            <DateChip {task} {projectSlug} size="sm" ghost />
            {#if (task.assignments?.length ?? 0) > 1}
                <AssigneeStack {task} {team} size="sm" />
            {/if}
        </span>

        <span
            class="flex w-12 shrink-0 items-center justify-end gap-0.5 opacity-60 sm:opacity-0 sm:group-hover:opacity-100"
            role="none"
            onclick={(e) => e.stopPropagation()}
        >
            <button
                type="button"
                class={`rounded-full px-1 text-sm leading-none transition ${
                    assignment.is_focused
                        ? 'text-amber-600 dark:text-amber-400'
                        : 'text-neutral-400 hover:text-amber-500 dark:text-neutral-500 dark:hover:text-amber-400'
                }`}
                title={assignment.is_focused ? 'Unpin from focus' : 'Pin to focus'}
                aria-pressed={assignment.is_focused}
                onclick={toggleFocus}
            >
                {assignment.is_focused ? '★' : '☆'}
            </button>

            <Popover bind:open={snoozeOpen} align="right" triggerLabel="Snooze">
                {#snippet trigger()}
                    <span class="text-sm leading-none text-neutral-400 transition hover:text-sky-600 dark:text-neutral-500 dark:hover:text-sky-400"
                        >☾</span
                    >
                {/snippet}
                <button
                    type="button"
                    data-popover-item
                    class="block w-full px-3 py-1.5 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    onclick={() => snooze(1)}
                >
                    Until tomorrow
                </button>
                <button
                    type="button"
                    data-popover-item
                    class="block w-full px-3 py-1.5 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    onclick={() => snooze(3)}
                >
                    3 days
                </button>
                <button
                    type="button"
                    data-popover-item
                    class="block w-full px-3 py-1.5 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    onclick={() => snooze(7)}
                >
                    1 week
                </button>
                <button
                    type="button"
                    data-popover-item
                    class="block w-full px-3 py-1.5 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    onclick={() => snooze(30)}
                >
                    1 month
                </button>
                {#if assignment.is_snoozed}
                    <button
                        type="button"
                        data-popover-item
                        class="block w-full border-t border-neutral-200 px-3 py-1.5 text-left text-sm text-amber-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-amber-400 dark:hover:bg-neutral-800"
                        onclick={() => snooze(null)}
                    >
                        Unsnooze
                    </button>
                {/if}
            </Popover>
        </span>
    </div>
{/if}
