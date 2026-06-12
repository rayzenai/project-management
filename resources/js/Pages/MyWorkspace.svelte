<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { SvelteMap } from 'svelte/reactivity';
    import AppShell from '../components/AppShell.svelte';
    import AssignmentRow from '../components/AssignmentRow.svelte';
    import ContactChips from '../components/ContactChips.svelte';
    import NotesStrip from '../components/NotesStrip.svelte';
    import OpenTodos from '../components/OpenTodos.svelte';
    import QuickAddBar from '../components/QuickAddBar.svelte';
    import TaskSearch from '../components/TaskSearch.svelte';
    import type { Assignment, Contact, Note, ProjectSummary, SharedProps, Subtask, User } from '../lib/types';

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

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const currentUser = $derived(shared.auth?.user ?? null);
    const stickyNotes = $derived(shared.workspaceNotes ?? []);

    const focused = $derived(assignments.filter((a) => a.is_focused));
    const others = $derived(assignments.filter((a) => !a.is_focused));

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

    let showOthers = $state(true);
    let dropZone = $state<'focused' | 'others' | null>(null);

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
                <TaskSearch />
                <QuickAddBar {projects} {team} {currentUser} />
            </div>

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
                        <p class="font-mono text-xs text-amber-800/80 dark:text-amber-400/80">Nothing pinned — drag any task here, or hover and click ☆.</p>
                    </div>
                {:else}
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {#each focused as a (a.id)}
                            <AssignmentRow assignment={a} compact />
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
                        {showOthers ? '▾' : '▸'} Other assignments
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
                            Everything assigned to you is pinned. Nice.
                        </p>
                    {/if}

                    <div class="space-y-5">
                        {#each othersGrouped as group (group.project.id)}
                            <div>
                                <h3 class="mb-2 font-mono text-[11px] font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                    <a href={`/workspace/projects/${group.project.slug}`} class="hover:text-amber-600 dark:hover:text-amber-400"
                                        >{group.project.title}</a
                                    >
                                    <span class="ml-1 text-neutral-400 dark:text-neutral-600">· {group.assignments.length}</span>
                                </h3>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {#each group.assignments as a (a.id)}
                                        <AssignmentRow assignment={a} compact />
                                    {/each}
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </section>
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
