<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import KanbanBoard from '../../components/KanbanBoard.svelte';
    import TaskRow from '../../components/TaskRow.svelte';
    import type { Project, Task } from '../../lib/types';

    let { project, tasks }: { project: Project; tasks: Task[] } = $props();

    let view = $state<'list' | 'kanban'>(
        typeof window !== 'undefined' && window.localStorage.getItem(`workspace.view.${project.slug}`) === 'kanban' ? 'kanban' : 'list',
    );

    $effect(() => {
        if (typeof window === 'undefined') return;
        window.localStorage.setItem(`workspace.view.${project.slug}`, view);
    });

    let creating = $state(false);
    const form = useForm({ title: '', description: '', deadline_at: '' });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.post(`/workspace/projects/${project.slug}/tasks`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                creating = false;
            },
        });
    }

    const byStatus = $derived.by(() => {
        const groups: Record<string, Task[]> = {};
        for (const t of tasks) {
            const k = t.status || 'unclear';
            (groups[k] ||= []).push(t);
        }
        return groups;
    });
    const statusKeys = $derived(Object.keys(byStatus).sort());
</script>

<svelte:head><title>{project.title} · Workspace</title></svelte:head>

<AppShell>
    <header class="mb-6">
        <nav class="mb-2 text-xs text-neutral-500 dark:text-neutral-400">
            <a href="/workspace/projects" class="hover:underline">Projects</a> /
            <span>{project.title}</span>
        </nav>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{project.title}</h1>
                {#if project.title_np}
                    <div class="mt-1 text-base text-neutral-600 dark:text-neutral-400">{project.title_np}</div>
                {/if}
                {#if project.description}
                    <p class="mt-2 max-w-2xl text-sm text-neutral-600 dark:text-neutral-400">{project.description}</p>
                {/if}
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <div class="inline-flex overflow-hidden rounded-md border border-neutral-300 text-sm dark:border-neutral-700">
                    <button
                        type="button"
                        class={`px-3 py-1.5 transition ${view === 'list' ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800'}`}
                        onclick={() => (view = 'list')}
                        aria-pressed={view === 'list'}>☰ List</button
                    >
                    <button
                        type="button"
                        class={`px-3 py-1.5 transition ${view === 'kanban' ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800'}`}
                        onclick={() => (view = 'kanban')}
                        aria-pressed={view === 'kanban'}>▦ Kanban</button
                    >
                </div>
                <button
                    type="button"
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600"
                    onclick={() => (creating = !creating)}>{creating ? 'Cancel' : '+ Add task'}</button
                >
            </div>
        </div>
    </header>

    {#if creating}
        <form onsubmit={submit} class="mb-6 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Title</label>
                <input
                    type="text"
                    bind:value={form.title}
                    required
                    autofocus
                    class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                />
            </div>
            <div class="mt-3">
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Description</label>
                <textarea
                    bind:value={form.description}
                    rows="3"
                    class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                ></textarea>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Due date</label>
                    <input
                        type="date"
                        bind:value={form.deadline_at}
                        class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    />
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    disabled={form.processing}
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                    >Create task</button
                >
            </div>
            {#if form.errors.title}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{form.errors.title}</p>{/if}
        </form>
    {/if}

    {#if tasks.length === 0}
        <div class="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-base font-medium">No tasks in this project yet.</p>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Add the first one above.</p>
        </div>
    {:else if view === 'kanban'}
        <KanbanBoard {project} {tasks} />
    {:else}
        <div class="space-y-6">
            {#each statusKeys as status (status)}
                <section>
                    <h2 class="mb-2 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                        {byStatus[status][0]?.status_label || status} · {byStatus[status].length}
                    </h2>
                    <div class="space-y-2">
                        {#each byStatus[status] as task (task.id)}
                            <TaskRow {task} {project} />
                        {/each}
                    </div>
                </section>
            {/each}
        </div>
    {/if}
</AppShell>
