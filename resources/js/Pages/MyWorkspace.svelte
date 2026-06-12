<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { SvelteMap } from 'svelte/reactivity';
    import AppShell from '../components/AppShell.svelte';
    import AssignmentRow from '../components/AssignmentRow.svelte';
    import ContactChips from '../components/ContactChips.svelte';
    import NotesStrip from '../components/NotesStrip.svelte';
    import OpenTodos from '../components/OpenTodos.svelte';
    import QuickAddBar from '../components/QuickAddBar.svelte';
    import { palette } from '../lib/palette.svelte';
    import { peek } from '../lib/peek.svelte';
    import type { Assignment, Contact, Note, ProjectSummary, SharedProps, Subtask, Task, User } from '../lib/types';

    let {
        assignments,
        snoozedCount,
        openTodos,
        recentNotes,
        recentContacts,
        projects,
        team,
    }: {
        assignments: Assignment[];
        snoozedCount: number;
        openTodos: Subtask[];
        recentNotes: Note[];
        recentContacts: Contact[];
        projects: ProjectSummary[];
        team: User[];
    } = $props();

    const DUE_WINDOW_DAYS = 7;

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const currentUser = $derived(shared.auth?.user ?? null);
    const stickyNotes = $derived(shared.workspaceNotes ?? []);

    const completeStatuses = $derived(new Set((shared.statuses ?? []).filter((s) => s.is_complete).map((s) => s.value)));
    const isComplete = (t: Task) => completeStatuses.has(t.status);

    // The controller already excludes completed tasks; the filter here keeps
    // rows correct mid-flight after an optimistic complete.
    const open = $derived(assignments.filter((a) => a.task && !isComplete(a.task)));

    const due = $derived.by(() => {
        const todayStart = new Date(new Date().toDateString());
        const horizon = new Date(todayStart.getTime() + DUE_WINDOW_DAYS * 86_400_000);

        return open
            .filter((a) => a.task!.deadline_at && new Date(a.task!.deadline_at) <= horizon)
            .toSorted((x, y) => x.task!.deadline_at!.localeCompare(y.task!.deadline_at!));
    });
    const dueTaskIds = $derived(new Set(due.map((a) => a.task_id)));

    const focused = $derived(open.filter((a) => a.is_focused && !dueTaskIds.has(a.task_id)));
    const others = $derived(open.filter((a) => !a.is_focused && !dueTaskIds.has(a.task_id)));

    const othersGrouped = $derived.by(() => {
        const map = new SvelteMap<number, { project: ProjectSummary; assignments: Assignment[] }>();
        for (const a of others) {
            if (!a.task?.project) continue;
            const p = a.task.project;
            if (!map.has(p.id)) {
                map.set(p.id, { project: { id: p.id, slug: p.slug, title: p.title }, assignments: [] });
            }
            map.get(p.id)!.assignments.push(a);
        }
        return Array.from(map.values());
    });

    const allClear = $derived(open.length === 0);

    let showOthers = $state(true);
    let dropZone = $state<'focused' | 'others' | null>(null);

    onMount(() => {
        peek.openFromUrl(assignments.flatMap((a) => (a.task ? [{ id: a.task.id, slug: a.task.slug }] : [])));
    });

    function readPayload(event: DragEvent): { assignmentId: number; isFocused: boolean } | null {
        const raw = event.dataTransfer?.getData('application/x-workspace-assignment');
        if (!raw) return null;
        try {
            return JSON.parse(raw);
        } catch {
            return null;
        }
    }

    function onZoneDragOver(zone: 'focused' | 'others', event: DragEvent) {
        if (!event.dataTransfer?.types.includes('application/x-workspace-assignment')) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        dropZone = zone;
    }

    function onZoneDragLeave(event: DragEvent) {
        const related = event.relatedTarget as Node | null;
        if (related && (event.currentTarget as HTMLElement).contains(related)) return;
        dropZone = null;
    }

    function onZoneDrop(zone: 'focused' | 'others', event: DragEvent) {
        event.preventDefault();
        const payload = readPayload(event);
        dropZone = null;
        if (!payload) return;
        const wantFocused = zone === 'focused';
        if (payload.isFocused === wantFocused) return;
        router.patch(`/workspace/assignments/${payload.assignmentId}`, { is_focused: wantFocused }, { preserveScroll: true, preserveState: true });
    }
</script>

<svelte:head><title>My Workspace</title></svelte:head>

<AppShell>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-6">
            <div class="space-y-2">
                <button
                    type="button"
                    onclick={() => palette.open()}
                    class="flex w-full items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-left text-sm text-neutral-400 hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700"
                >
                    <span>⌕</span>
                    <span class="flex-1">Search or jump to anything…</span>
                    <kbd
                        class="rounded border border-neutral-300 px-1.5 py-0.5 text-[10px] text-neutral-500 dark:border-neutral-700 dark:text-neutral-400"
                        >⌘K</kbd
                    >
                </button>
                <QuickAddBar {projects} {team} {currentUser} />
            </div>

            {#if allClear}
                <section
                    ondragover={(e) => onZoneDragOver('focused', e)}
                    ondragleave={onZoneDragLeave}
                    ondrop={(e) => onZoneDrop('focused', e)}
                    class={`rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-10 text-center transition dark:border-amber-500/30 dark:bg-amber-500/5 ${
                        dropZone === 'focused' ? 'ring-2 ring-amber-300 dark:ring-amber-500/40' : ''
                    }`}
                >
                    <p class="font-display text-2xl font-bold tracking-tight text-amber-800 dark:text-amber-400">All clear.</p>
                    <p class="mt-2 font-mono text-xs text-amber-800/70 dark:text-amber-400/70">
                        No open assignments. Press <kbd class="rounded border border-current/30 px-1">q</kbd> to add a task.
                    </p>
                </section>
            {:else}
                <section>
                    <header class="mb-2 flex items-baseline justify-between">
                        <h2 class={`ws-eyebrow ${due.length > 0 ? 'text-red-600 dark:text-red-400' : 'text-neutral-500 dark:text-neutral-400'}`}>
                            ⚠ Due
                        </h2>
                        <span class="font-mono text-[11px] text-neutral-500 dark:text-neutral-500">{due.length} · next {DUE_WINDOW_DAYS} days</span>
                    </header>

                    {#if due.length === 0}
                        <p class="font-mono text-xs text-neutral-500 dark:text-neutral-500">✓ Nothing due within {DUE_WINDOW_DAYS} days</p>
                    {:else}
                        <div class="space-y-2">
                            {#each due as a (a.id)}
                                <AssignmentRow assignment={a} lane="due" />
                            {/each}
                        </div>
                    {/if}
                </section>

                <section
                    ondragover={(e) => onZoneDragOver('focused', e)}
                    ondragleave={onZoneDragLeave}
                    ondrop={(e) => onZoneDrop('focused', e)}
                    class={`rounded-xl transition ${dropZone === 'focused' ? 'bg-amber-50/60 ring-2 ring-amber-300 dark:bg-amber-500/5 dark:ring-amber-500/40' : ''}`}
                >
                    <header class="mb-2 flex items-baseline justify-between">
                        <h2 class="ws-eyebrow text-amber-700 dark:text-amber-400">★ Focused</h2>
                        <span class="font-mono text-[11px] text-neutral-500 dark:text-neutral-500">{focused.length} pinned · drag here to pin</span>
                    </header>

                    {#if focused.length === 0}
                        <div
                            class="rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-4 text-center dark:border-amber-500/30 dark:bg-amber-500/5"
                        >
                            <p class="font-mono text-xs text-amber-800/80 dark:text-amber-400/80">
                                Nothing pinned — drag any task here, or hover and click ☆.
                            </p>
                        </div>
                    {:else}
                        <div class="space-y-2">
                            {#each focused as a (a.id)}
                                <AssignmentRow assignment={a} lane="focused" />
                            {/each}
                        </div>
                    {/if}
                </section>

                <OpenTodos todos={openTodos} />

                <section
                    ondragover={(e) => onZoneDragOver('others', e)}
                    ondragleave={onZoneDragLeave}
                    ondrop={(e) => onZoneDrop('others', e)}
                    class={`rounded-xl transition ${dropZone === 'others' ? 'bg-neutral-100/60 ring-2 ring-neutral-300 dark:bg-neutral-800/40 dark:ring-neutral-700' : ''}`}
                >
                    <header class="mb-2 flex items-baseline justify-between">
                        <button
                            type="button"
                            class="ws-eyebrow text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                            onclick={() => (showOthers = !showOthers)}
                        >
                            {showOthers ? '▾' : '▸'} Everything else
                        </button>
                        <span class="font-mono text-[11px] text-neutral-500 dark:text-neutral-500"
                            >{others.length} task{others.length === 1 ? '' : 's'} · drag here to unpin</span
                        >
                    </header>

                    {#if showOthers}
                        {#if othersGrouped.length === 0}
                            <p
                                class="rounded-xl border border-dashed border-neutral-300 bg-white p-4 text-center font-mono text-xs text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-400"
                            >
                                Everything assigned to you is due soon or pinned.
                            </p>
                        {/if}

                        <div class="space-y-5">
                            {#each othersGrouped as group (group.project.id)}
                                <div>
                                    <h3
                                        class="mb-2 font-mono text-[11px] font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400"
                                    >
                                        <a href={`/workspace/projects/${group.project.slug}`} class="hover:text-amber-600 dark:hover:text-amber-400"
                                            >{group.project.title}</a
                                        >
                                        <span class="ml-1 text-neutral-400 dark:text-neutral-600">· {group.assignments.length}</span>
                                    </h3>
                                    <div class="space-y-2">
                                        {#each group.assignments as a (a.id)}
                                            <AssignmentRow assignment={a} lane="other" />
                                        {/each}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    {/if}
                </section>
            {/if}
        </div>

        <aside class="space-y-6 xl:sticky xl:top-20">
            <section class="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900/60">
                <h2 class="ws-eyebrow mb-2 text-neutral-500 dark:text-neutral-400">Notes</h2>
                <NotesStrip {stickyNotes} taskNotes={recentNotes} />
            </section>

            <section class="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900/60">
                <ContactChips contacts={recentContacts} />
            </section>

            {#if snoozedCount > 0}
                <p class="px-1 font-mono text-[11px] text-neutral-500 dark:text-neutral-500">💤 {snoozedCount} snoozed</p>
            {/if}
        </aside>
    </div>
</AppShell>
