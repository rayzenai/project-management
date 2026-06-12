<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import TaskRow from './TaskRow.svelte';
    import type { Assignment } from '../lib/types';

    let { assignment, compact = false }: { assignment: Assignment; compact?: boolean } = $props();

    let snoozeOpen = $state(false);

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
            JSON.stringify({
                assignmentId: assignment.id,
                isFocused: assignment.is_focused,
            }),
        );
        event.dataTransfer.effectAllowed = 'move';
    }
</script>

<div class="group relative" draggable="true" ondragstart={onDragStart}>
    {#if assignment.task}
        <TaskRow task={assignment.task} {compact} />
    {/if}

    <div
        class="absolute top-3 right-3 flex items-center gap-1 rounded-full bg-white/95 px-1 py-0.5 opacity-0 shadow-sm ring-1 ring-neutral-200 transition group-hover:opacity-100 dark:bg-neutral-900/95 dark:ring-neutral-700"
    >
        <button
            type="button"
            class={`rounded-full px-2 py-1 text-sm leading-none transition ${
                assignment.is_focused
                    ? 'text-amber-600 dark:text-amber-400'
                    : 'text-neutral-400 hover:text-amber-500 dark:text-neutral-500 dark:hover:text-amber-400'
            }`}
            title={assignment.is_focused ? 'Unpin from focus' : 'Pin to focus'}
            aria-pressed={assignment.is_focused}
            onclick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleFocus();
            }}>{assignment.is_focused ? '★' : '☆'}</button
        >

        <div class="relative">
            <button
                type="button"
                class="rounded-full px-2 py-1 text-sm leading-none text-neutral-400 transition hover:text-sky-600 dark:text-neutral-500 dark:hover:text-sky-400"
                title="Snooze"
                onclick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    snoozeOpen = !snoozeOpen;
                }}>☾</button
            >

            {#if snoozeOpen}
                <div
                    class="absolute right-0 z-30 mt-1 w-44 overflow-hidden rounded-md border border-neutral-200 bg-white text-sm shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
                >
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                        onclick={() => snooze(1)}>Until tomorrow</button
                    >
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                        onclick={() => snooze(3)}>3 days</button
                    >
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                        onclick={() => snooze(7)}>1 week</button
                    >
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                        onclick={() => snooze(30)}>1 month</button
                    >
                    {#if assignment.is_snoozed}
                        <button
                            type="button"
                            class="block w-full border-t border-neutral-200 px-3 py-2 text-left text-amber-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-amber-400 dark:hover:bg-neutral-800"
                            onclick={() => snooze(null)}>Unsnooze</button
                        >
                    {/if}
                </div>
            {/if}
        </div>
    </div>
</div>
