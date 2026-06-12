<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { formatDate } from '../lib/format';
    import { peek } from '../lib/peek.svelte';
    import type { Subtask } from '../lib/types';

    let { todos }: { todos: Subtask[] } = $props();

    let hidden = $state(false);

    function openTask(t: Subtask) {
        if (t.task) peek.open({ id: t.task.id, slug: t.task.slug });
    }

    type Group = { key: string; label: string; items: Subtask[] };

    const groups = $derived.by<Group[]>(() => {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const weekEnd = new Date(today.getTime() + 7 * 86_400_000);

        const overdue: Subtask[] = [];
        const thisWeek: Subtask[] = [];
        const later: Subtask[] = [];
        const noDate: Subtask[] = [];

        for (const t of todos) {
            if (!t.due_at) {
                noDate.push(t);
                continue;
            }
            const d = new Date(t.due_at);
            if (d < today) overdue.push(t);
            else if (d <= weekEnd) thisWeek.push(t);
            else later.push(t);
        }

        const out: Group[] = [];
        if (overdue.length) out.push({ key: 'overdue', label: 'Overdue', items: overdue });
        if (thisWeek.length) out.push({ key: 'this-week', label: 'This week', items: thisWeek });
        if (later.length) out.push({ key: 'later', label: 'Later', items: later });
        if (noDate.length) out.push({ key: 'no-date', label: 'No date', items: noDate });
        return out;
    });

    function toggle(t: Subtask) {
        router.patch(`/workspace/subtasks/${t.id}`, { is_done: !t.is_done }, { preserveScroll: true, preserveState: true });
    }

    function remove(t: Subtask) {
        router.delete(`/workspace/subtasks/${t.id}`, { preserveScroll: true, preserveState: true });
    }
</script>

<section class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/5">
    <header class="mb-3 flex items-baseline justify-between">
        <div class="flex items-baseline gap-3">
            <h2 class="ws-eyebrow text-emerald-800 dark:text-emerald-300">✓ My Open Todos</h2>
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                {todos.length}
            </span>
        </div>
        <button type="button" class="text-xs text-emerald-700 hover:underline dark:text-emerald-400" onclick={() => (hidden = !hidden)}
            >{hidden ? 'Show' : 'Hide'}</button
        >
    </header>

    {#if !hidden}
        {#if todos.length === 0}
            <p
                class="rounded-md border border-dashed border-emerald-300 bg-white px-4 py-6 text-center text-sm text-emerald-700 dark:border-emerald-500/40 dark:bg-neutral-900 dark:text-emerald-300"
            >
                No open todos. Add some from any task's <em>Todos</em> tab.
            </p>
        {:else}
            <div class="space-y-5">
                {#each groups as group (group.key)}
                    <div>
                        <h3 class="mb-2 text-xs font-semibold tracking-wider text-neutral-600 uppercase dark:text-neutral-400">
                            {group.label}
                            <span class="ml-1 text-neutral-400 dark:text-neutral-500">{group.items.length}</span>
                        </h3>
                        <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            {#each group.items as t (t.id)}
                                <li
                                    class="group rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 transition hover:border-emerald-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-emerald-500/40"
                                >
                                    <div class="flex items-start gap-2">
                                        <button
                                            type="button"
                                            class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 border-neutral-300 text-[10px] text-white transition hover:border-emerald-500 dark:border-neutral-600"
                                            class:bg-emerald-500={t.is_done}
                                            class:border-emerald-500={t.is_done}
                                            aria-pressed={t.is_done}
                                            onclick={(e) => {
                                                e.stopPropagation();
                                                toggle(t);
                                            }}
                                            title="Mark done">{t.is_done ? '✓' : ''}</button
                                        >
                                        <button
                                            type="button"
                                            class="min-w-0 flex-1 cursor-pointer text-left text-sm"
                                            onclick={() => openTask(t)}
                                            title="Open task"
                                        >
                                            <p
                                                class="line-clamp-1 leading-snug text-neutral-900 dark:text-neutral-100"
                                                class:line-through={t.is_done}
                                                class:text-neutral-400={t.is_done}
                                            >
                                                {t.body}
                                            </p>
                                            <div
                                                class="mt-0.5 flex flex-wrap items-baseline gap-x-2 text-[11px] text-neutral-500 dark:text-neutral-400"
                                            >
                                                {#if t.due_at}<span>{formatDate(t.due_at)}</span>{/if}
                                                {#if t.task}
                                                    <span class="truncate text-emerald-700 dark:text-emerald-400">
                                                        {t.task.short_title || t.task.title}
                                                    </span>
                                                {/if}
                                            </div>
                                        </button>
                                        <button
                                            type="button"
                                            class="invisible text-neutral-400 group-hover:visible hover:text-red-500"
                                            onclick={(e) => {
                                                e.stopPropagation();
                                                remove(t);
                                            }}
                                            title="Remove">×</button
                                        >
                                    </div>
                                </li>
                            {/each}
                        </ul>
                    </div>
                {/each}
            </div>
        {/if}
    {/if}
</section>
