<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { initials } from '../lib/format';
    import type { Project, Task } from '../lib/types';

    let { project, tasks }: { project: Project; tasks: Task[] } = $props();

    type ColumnKey = 'todo' | 'doing' | 'done' | 'stuck';

    const columns: { key: ColumnKey; label: string; defaultStatus: string; accent: string }[] = [
        { key: 'todo', label: 'To do', defaultStatus: 'unclear', accent: 'border-neutral-300 dark:border-neutral-700' },
        { key: 'doing', label: 'Doing', defaultStatus: 'in_progress', accent: 'border-sky-300 dark:border-sky-500/40' },
        { key: 'done', label: 'Done', defaultStatus: 'done', accent: 'border-emerald-300 dark:border-emerald-500/40' },
        { key: 'stuck', label: 'Stuck', defaultStatus: 'blocked', accent: 'border-red-300 dark:border-red-500/40' },
    ];

    function bucketOf(status: string | null | undefined): ColumnKey {
        switch (status) {
            case 'done':
            case 'done_late':
                return 'done';
            case 'in_progress':
            case 'started':
                return 'doing';
            case 'late':
            case 'failed':
            case 'blocked':
                return 'stuck';
            default:
                return 'todo';
        }
    }

    const byBucket = $derived.by(() => {
        const out: Record<ColumnKey, Task[]> = { todo: [], doing: [], done: [], stuck: [] };
        for (const t of tasks) out[bucketOf(t.status)].push(t);
        for (const k of Object.keys(out) as ColumnKey[]) out[k].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
        return out;
    });

    let draggedId = $state<number | null>(null);
    let hoverTarget = $state<{ bucket: ColumnKey; index: number } | null>(null);
    let addingTo = $state<ColumnKey | null>(null);

    const quickForm = useForm({ title: '', status: 'unclear' });

    function onDragStart(task: Task, event: DragEvent) {
        draggedId = task.id;
        event.dataTransfer?.setData('text/plain', String(task.id));
        if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    }

    function onDragEnd() {
        draggedId = null;
        hoverTarget = null;
    }

    function onCardDragOver(bucket: ColumnKey, index: number, event: DragEvent) {
        event.preventDefault();
        event.stopPropagation();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';

        // Decide above-or-below based on cursor position within the target card.
        const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
        const above = event.clientY < rect.top + rect.height / 2;
        hoverTarget = { bucket, index: above ? index : index + 1 };
    }

    function onColumnDragOver(bucket: ColumnKey, event: DragEvent) {
        event.preventDefault();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
        if (!hoverTarget || hoverTarget.bucket !== bucket) {
            hoverTarget = { bucket, index: byBucket[bucket].length };
        }
    }

    function onColumnDragLeave(event: DragEvent) {
        const related = event.relatedTarget as Node | null;
        if (related && (event.currentTarget as HTMLElement).contains(related)) return;
        hoverTarget = null;
    }

    function onDrop(bucket: ColumnKey, event: DragEvent) {
        event.preventDefault();
        const id = Number(event.dataTransfer?.getData('text/plain') || draggedId);
        const target = hoverTarget;
        hoverTarget = null;
        draggedId = null;
        if (!id) return;

        const task = tasks.find((t) => t.id === id);
        if (!task) return;

        const targetIndex = target?.bucket === bucket ? target.index : byBucket[bucket].length;
        const orderedIds = byBucket[bucket].filter((t) => t.id !== id).map((t) => t.id);
        orderedIds.splice(targetIndex, 0, id);

        const status = columns.find((c) => c.key === bucket)!.defaultStatus;
        router.post(
            `/workspace/projects/${project.slug}/tasks/reorder`,
            { task_ids: orderedIds, status },
            { preserveScroll: true, preserveState: true },
        );
    }

    function submitQuickAdd(bucket: ColumnKey, event: SubmitEvent) {
        event.preventDefault();
        if (!quickForm.title.trim()) return;
        quickForm.status = columns.find((c) => c.key === bucket)!.defaultStatus;
        quickForm.post(`/workspace/projects/${project.slug}/tasks`, {
            preserveScroll: true,
            onSuccess: () => {
                quickForm.reset();
                addingTo = null;
            },
        });
    }

    function href(task: Task): string {
        return `/workspace/projects/${project.slug}/tasks/${task.slug}`;
    }
</script>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
    {#each columns as col (col.key)}
        {@const colTasks = byBucket[col.key]}
        {@const isHover = hoverTarget?.bucket === col.key}
        <div
            class={`flex min-h-[200px] flex-col rounded-xl border-2 ${col.accent} bg-neutral-50/60 transition dark:bg-neutral-900/40 ${isHover ? 'border-amber-400 bg-amber-50/40 dark:border-amber-500/60 dark:bg-amber-500/5' : ''}`}
            ondragover={(e) => onColumnDragOver(col.key, e)}
            ondragleave={onColumnDragLeave}
            ondrop={(e) => onDrop(col.key, e)}
            role="list"
            aria-label={col.label}
        >
            <header class="flex items-baseline justify-between px-3 pt-3 pb-2">
                <h3 class="text-sm font-semibold tracking-wider text-neutral-700 uppercase dark:text-neutral-200">
                    {col.label}
                </h3>
                <span class="text-xs text-neutral-500 dark:text-neutral-400">{colTasks.length}</span>
            </header>

            <div class="flex-1 space-y-2 px-2 pb-2">
                {#each colTasks as task, index (task.id)}
                    {#if hoverTarget?.bucket === col.key && hoverTarget.index === index}
                        <div class="-mb-1 h-1 rounded-full bg-amber-400/80"></div>
                    {/if}
                    <a
                        href={href(task)}
                        draggable="true"
                        ondragstart={(e) => onDragStart(task, e)}
                        ondragend={onDragEnd}
                        ondragover={(e) => onCardDragOver(col.key, index, e)}
                        class={`block cursor-grab rounded-lg border border-neutral-200 bg-white p-3 shadow-sm transition hover:border-amber-300 active:cursor-grabbing dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-amber-500/40 ${draggedId === task.id ? 'opacity-50' : ''}`}
                        role="listitem"
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

                        <div class="mt-2 flex items-center justify-between gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                            <div class="flex flex-wrap items-center gap-1.5">
                                {#if task.deadline_at}
                                    <span>⏱ {task.days_relative_label}</span>
                                {/if}
                                {#if task.progress > 0}
                                    <span>· {task.progress}%</span>
                                {/if}
                            </div>
                            {#if task.assignments && task.assignments.length > 0}
                                <div class="flex -space-x-1.5">
                                    {#each task.assignments.slice(0, 3) as a (a.id)}
                                        <span
                                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-neutral-200 text-[9px] font-semibold text-neutral-700 dark:border-neutral-900 dark:bg-neutral-700 dark:text-neutral-200"
                                            title={a.user?.name}>{initials(a.user?.name)}</span
                                        >
                                    {/each}
                                </div>
                            {/if}
                        </div>
                    </a>
                {/each}

                {#if hoverTarget?.bucket === col.key && hoverTarget.index === colTasks.length}
                    <div class="-mt-1 h-1 rounded-full bg-amber-400/80"></div>
                {/if}
            </div>

            <div class="px-2 pb-3">
                {#if addingTo === col.key}
                    <form
                        onsubmit={(e) => submitQuickAdd(col.key, e)}
                        class="rounded-lg border border-amber-300 bg-white p-2 dark:border-amber-500/40 dark:bg-neutral-900"
                    >
                        <input
                            type="text"
                            bind:value={quickForm.title}
                            autofocus
                            placeholder={`Add to ${col.label}...`}
                            class="w-full bg-transparent text-sm outline-none placeholder:text-neutral-400 dark:placeholder:text-neutral-500"
                            onkeydown={(e) => {
                                if (e.key === 'Escape') {
                                    addingTo = null;
                                    quickForm.reset();
                                }
                            }}
                        />
                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100"
                                onclick={() => {
                                    addingTo = null;
                                    quickForm.reset();
                                }}>Cancel</button
                            >
                            <button
                                type="submit"
                                disabled={quickForm.processing || !quickForm.title.trim()}
                                class="rounded-md bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                                >Add</button
                            >
                        </div>
                    </form>
                {:else}
                    <button
                        type="button"
                        class="w-full rounded-lg border border-dashed border-neutral-300 bg-transparent px-3 py-2 text-left text-sm text-neutral-500 transition hover:border-amber-400 hover:bg-amber-50/50 hover:text-amber-700 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-amber-500/50 dark:hover:bg-amber-500/5 dark:hover:text-amber-300"
                        onclick={() => (addingTo = col.key)}>+ Add task</button
                    >
                {/if}
            </div>
        </div>
    {/each}
</div>
