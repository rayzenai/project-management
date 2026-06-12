<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import type { Project } from '../../lib/types';

    let { projects }: { projects: Project[] } = $props();

    let creating = $state(false);
    const form = useForm({ title: '', title_np: '', description: '', is_public: false });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.post('/workspace/projects', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                creating = false;
            },
        });
    }
</script>

<svelte:head><title>Projects · Workspace</title></svelte:head>

<AppShell>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Projects</h1>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Every initiative the office is tracking.</p>
        </div>
        <button
            type="button"
            class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 dark:text-neutral-950"
            onclick={() => (creating = !creating)}>{creating ? 'Cancel' : '+ New project'}</button
        >
    </div>

    {#if creating}
        <form onsubmit={submit} class="mb-6 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Title</label>
                    <input
                        type="text"
                        bind:value={form.title}
                        required
                        class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Title (Nepali)</label>
                    <input
                        type="text"
                        bind:value={form.title_np}
                        class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    />
                </div>
            </div>
            <div class="mt-3">
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Description</label>
                <textarea
                    bind:value={form.description}
                    rows="2"
                    class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                ></textarea>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" bind:checked={form.is_public} />
                    Public (visible on /plans)
                </label>
                <div class="flex-1"></div>
                <button
                    type="submit"
                    disabled={form.processing}
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50 dark:text-neutral-950">Create</button
                >
            </div>
            {#if form.errors.title}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{form.errors.title}</p>{/if}
        </form>
    {/if}

    {#if projects.length === 0}
        <div class="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-base font-medium">No projects yet.</p>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Click "+ New project" to start one.</p>
        </div>
    {/if}

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {#each projects as project (project.id)}
            <a
                href={`/workspace/projects/${project.slug}`}
                class="group rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-amber-300 hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-amber-500/40"
            >
                <div class="flex items-start justify-between">
                    <h3
                        class="text-base font-semibold text-neutral-900 group-hover:text-amber-700 dark:text-neutral-100 dark:group-hover:text-amber-400"
                    >
                        {project.title}
                    </h3>
                    {#if project.is_public}
                        <span
                            class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400"
                            >public</span
                        >
                    {/if}
                </div>
                {#if project.title_np}
                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{project.title_np}</div>
                {/if}
                {#if project.description}
                    <p class="mt-2 line-clamp-2 text-sm text-neutral-600 dark:text-neutral-400">{project.description}</p>
                {/if}
                <div class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">
                    {project.tasks_count ?? 0} task{(project.tasks_count ?? 0) === 1 ? '' : 's'}
                </div>
            </a>
        {/each}
    </div>
</AppShell>
