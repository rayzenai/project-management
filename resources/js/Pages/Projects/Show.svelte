<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { SvelteMap, SvelteSet } from 'svelte/reactivity';
    import AppShell from '../../components/AppShell.svelte';
    import BoardView from '../../components/project/BoardView.svelte';
    import ListView from '../../components/project/ListView.svelte';
    import PeopleView from '../../components/project/PeopleView.svelte';
    import ProjectFilters, { type CategoryOption, type ProjectFiltersState } from '../../components/project/ProjectFilters.svelte';
    import ProjectSummaryStrip from '../../components/project/ProjectSummaryStrip.svelte';
    import { peek } from '../../lib/peek.svelte';
    import { quickAdd } from '../../lib/quickAdd.svelte';
    import type { Member, Project, SharedProps, Task } from '../../lib/types';

    let { project, tasks }: { project: Project; tasks: Task[] } = $props();

    type Tab = 'board' | 'list' | 'people';

    const TABS: { value: Tab; label: string }[] = [
        { value: 'board', label: 'Board' },
        { value: 'list', label: 'List' },
        { value: 'people', label: 'People' },
    ];

    function initialTab(): Tab {
        if (typeof window === 'undefined') return 'list';
        const raw = window.localStorage.getItem(`workspace.view.${project.slug}`);
        if (raw === 'kanban') return 'board'; // legacy value migration
        return raw === 'board' || raw === 'people' ? raw : 'list'; // default + garbage guard
    }

    let activeTab = $state<Tab>(initialTab());

    $effect(() => {
        if (typeof window !== 'undefined') window.localStorage.setItem(`workspace.view.${project.slug}`, activeTab);
    });

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const statuses = $derived(shared.statuses ?? []);

    let filters = $state<ProjectFiltersState>({ assigneeIds: [], overdueOnly: false, category: null });

    const completeSet = $derived(new SvelteSet(statuses.filter((s) => s.is_complete).map((s) => s.value)));
    const isComplete = (t: Task) => completeSet.has(t.status);
    const isOverdue = (t: Task) => !!t.deadline_at && !isComplete(t) && new Date(t.deadline_at) < new Date(new Date().toDateString()); // date-only compare

    const teammates = $derived.by(() => {
        // Unique, name-sorted, from assignments.
        const m = new SvelteMap<number, Member>();
        for (const t of tasks) {
            for (const a of t.assignments ?? []) {
                if (a.member) m.set(a.member.id, a.member);
            }
        }
        return [...m.values()].sort((a, b) => a.name.localeCompare(b.name));
    });

    const categories = $derived.by(() => {
        // Unique {value,label,color}; [] hides the select.
        const m = new SvelteMap<string, CategoryOption>();
        for (const t of tasks) {
            if (t.category) m.set(t.category, { value: t.category, label: t.category_label ?? t.category, color: t.category_color ?? null });
        }
        return [...m.values()].sort((a, b) => a.label.localeCompare(b.label));
    });

    const filteredTasks = $derived(
        tasks.filter(
            (t) =>
                (filters.assigneeIds.length === 0 || (t.assignments ?? []).some((a) => filters.assigneeIds.includes(a.member_id))) &&
                (!filters.overdueOnly || isOverdue(t)) &&
                (filters.category === null || t.category === filters.category),
        ),
    );

    const filtersActive = $derived(filters.assigneeIds.length > 0 || filters.overdueOnly || filters.category !== null);

    function clearFilters() {
        filters = { assigneeIds: [], overdueOnly: false, category: null };
    }

    onMount(() => {
        peek.openFromUrl(tasks.map((t) => ({ id: t.id, slug: t.slug })));
    });
</script>

<svelte:head><title>{project.title} · Workspace</title></svelte:head>

<AppShell>
    <header class="mb-6">
        <nav class="mb-2 text-xs text-neutral-500 dark:text-neutral-400">
            <a href="/workspace/projects" class="hover:underline">Projects</a> /
            <span>{project.title}</span>
        </nav>
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{project.title}</h1>
            {#if project.title_np}
                <div class="mt-1 text-base text-neutral-600 dark:text-neutral-400">{project.title_np}</div>
            {/if}
            {#if project.description}
                <p class="mt-2 max-w-2xl text-sm text-neutral-600 dark:text-neutral-400">{project.description}</p>
            {/if}
        </div>
    </header>

    {#if tasks.length === 0}
        <div class="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-base font-medium">No tasks yet.</p>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Press <strong>q</strong> anywhere, or:</p>
            <button
                type="button"
                class="mt-3 rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 dark:text-neutral-950"
                onclick={() => quickAdd.open({ projectId: project.id })}
            >
                + Add task
            </button>
        </div>
    {:else}
        <div class="mb-4 flex items-center justify-between gap-2">
            <div class="inline-flex overflow-hidden rounded-md border border-neutral-300 text-sm dark:border-neutral-700">
                {#each TABS as tab (tab.value)}
                    <button
                        type="button"
                        class={`px-3 py-1.5 transition ${
                            activeTab === tab.value
                                ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900'
                                : 'bg-white text-neutral-600 hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800'
                        }`}
                        onclick={() => (activeTab = tab.value)}
                        aria-pressed={activeTab === tab.value}
                    >
                        {tab.label}
                    </button>
                {/each}
            </div>
            <button
                type="button"
                class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 dark:text-neutral-950"
                onclick={() => quickAdd.open({ projectId: project.id })}
            >
                + Add task
            </button>
        </div>

        <ProjectSummaryStrip {tasks} {statuses} />
        <ProjectFilters bind:filters {teammates} {categories} shownCount={filteredTasks.length} totalCount={tasks.length} />

        {#if filteredTasks.length === 0}
            <div class="mb-4 rounded-xl border border-dashed border-neutral-300 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No tasks match the current filters.</p>
                <button
                    type="button"
                    class="mt-3 rounded-md border border-amber-400 px-3 py-1.5 text-sm font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-500/60 dark:text-amber-400 dark:hover:bg-amber-500/10"
                    onclick={clearFilters}
                >
                    Clear filters
                </button>
            </div>
        {/if}

        {#if activeTab === 'board'}
            <BoardView {project} tasks={filteredTasks} {statuses} {filtersActive} />
        {:else if activeTab === 'list'}
            {#if filteredTasks.length > 0}
                <ListView {project} tasks={filteredTasks} {statuses} />
            {/if}
        {:else if filteredTasks.length > 0}
            <PeopleView {project} tasks={filteredTasks} {statuses} />
        {/if}
    {/if}
</AppShell>
