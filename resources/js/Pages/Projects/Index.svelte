<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import type { Project } from '../../lib/types';

    let {
        projects,
        archivedView = false,
        archivedCount = 0,
        assignableTeams = [],
        canCreate = false,
        isSuperAdmin = false,
    }: {
        projects: Project[];
        archivedView?: boolean;
        archivedCount?: number;
        assignableTeams?: { id: number; name: string; slug: string }[];
        canCreate?: boolean;
        isSuperAdmin?: boolean;
    } = $props();

    let creating = $state(false);
    const uid = $props.id();
    const form = useForm({ title: '', title_np: '', description: '', is_public: false, team_ids: [] as number[] });

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

    function archive(project: Project) {
        if (!confirm(`Archive "${project.title}"? It will disappear from My Workspace, the dashboard, and quick-add until restored.`)) return;
        router.patch(`/workspace/projects/${project.slug}/archive`, {}, { preserveScroll: true });
    }

    function restore(project: Project) {
        router.patch(`/workspace/projects/${project.slug}/restore`, {}, { preserveScroll: true });
    }
</script>

<svelte:head><title>Projects · Workspace</title></svelte:head>

<AppShell>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Projects</h1>
            <p class="mt-1 text-sm text-fg-muted">Every initiative the office is tracking.</p>
        </div>
        {#if canCreate}
            <button
                type="button"
                class="rounded-md bg-accent px-3 py-1.5 text-sm font-semibold text-bg hover:bg-accent-dim"
                onclick={() => (creating = !creating)}>{creating ? 'Cancel' : '+ New project'}</button
            >
        {/if}
    </div>

    <div class="mb-4 flex items-center gap-4 text-sm">
        <a
            href="/workspace/projects"
            class={archivedView ? 'text-fg-muted hover:text-fg' : 'font-semibold text-accent'}
        >Active</a>
        <a
            href="/workspace/projects?archived=1"
            class={archivedView ? 'font-semibold text-accent' : 'text-fg-muted hover:text-fg'}
        >Archived{archivedCount ? ` (${archivedCount})` : ''}</a>
    </div>

    {#if creating}
        <form onsubmit={submit} class="mb-6 rounded-xl border border-line bg-surface p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for={`${uid}-title`} class="mb-1 block text-xs font-medium text-fg-muted">Title</label>
                    <input
                        id={`${uid}-title`}
                        type="text"
                        bind:value={form.title}
                        required
                        class="w-full rounded-md border border-line bg-surface px-3 py-1.5 text-sm text-fg"
                    />
                </div>
                <div>
                    <label for={`${uid}-title-np`} class="mb-1 block text-xs font-medium text-fg-muted">Title (Nepali)</label>
                    <input
                        id={`${uid}-title-np`}
                        type="text"
                        bind:value={form.title_np}
                        class="w-full rounded-md border border-line bg-surface px-3 py-1.5 text-sm text-fg"
                    />
                </div>
            </div>
            <div class="mt-3">
                <label for={`${uid}-description`} class="mb-1 block text-xs font-medium text-fg-muted">Description</label>
                <textarea
                    id={`${uid}-description`}
                    bind:value={form.description}
                    rows="2"
                    class="w-full rounded-md border border-line bg-surface px-3 py-1.5 text-sm text-fg"
                ></textarea>
            </div>
            <fieldset class="mt-4">
                <legend class="text-sm font-medium text-fg">Teams with access</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    {#each assignableTeams as team (team.id)}
                        <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-1.5 text-sm">
                            <input type="checkbox" value={team.id}
                                checked={form.team_ids.includes(team.id)}
                                onchange={(e) => {
                                    const id = team.id;
                                    form.team_ids = e.currentTarget.checked
                                        ? [...form.team_ids, id]
                                        : form.team_ids.filter((t) => t !== id);
                                }} />
                            {team.name}
                        </label>
                    {/each}
                </div>
                {#if form.errors.team_ids}<p class="mt-1 text-xs text-danger">{form.errors.team_ids}</p>{/if}
            </fieldset>

            {#if isSuperAdmin}
                <label class="mt-4 flex items-center gap-2 text-sm text-fg-muted">
                    <input type="checkbox" bind:checked={form.is_public} /> Public (visible to everyone)
                </label>
            {/if}

            <div class="mt-3 flex items-center gap-2">
                <div class="flex-1"></div>
                <button
                    type="submit"
                    disabled={form.processing}
                    class="rounded-md bg-accent px-3 py-1.5 text-sm font-semibold text-bg hover:bg-accent-dim disabled:opacity-50">Create</button
                >
            </div>
            {#if form.errors.title}<p class="mt-2 text-xs text-danger">{form.errors.title}</p>{/if}
        </form>
    {/if}

    {#if projects.length === 0}
        <div class="rounded-xl border border-dashed border-line bg-surface p-10 text-center">
            <p class="text-base font-medium">No projects yet.</p>
            <p class="mt-1 text-sm text-fg-muted">Click "+ New project" to start one.</p>
        </div>
    {/if}

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {#each projects as project (project.id)}
            <a
                href={`/workspace/projects/${project.slug}`}
                class="group rounded-xl border border-line bg-surface p-4 transition hover:border-accent hover:shadow-sm"
            >
                <div class="flex items-start justify-between">
                    <h3
                        class="text-base font-semibold text-fg group-hover:text-accent"
                    >
                        {project.title}
                    </h3>
                    {#if project.is_public}
                        <span
                            class="rounded-full bg-success/15 px-2 py-0.5 text-[10px] font-medium text-success"
                            >public</span
                        >
                    {/if}
                </div>
                {#if project.title_np}
                    <div class="mt-1 text-sm text-fg-muted">{project.title_np}</div>
                {/if}
                {#if project.description}
                    <p class="mt-2 line-clamp-2 text-sm text-fg-muted">{project.description}</p>
                {/if}
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-fg-muted">
                        {project.tasks_count ?? 0} task{(project.tasks_count ?? 0) === 1 ? '' : 's'}
                    </span>
                    {#if project.can_archive}
                        {#if project.is_archived}
                            <button
                                type="button"
                                class="rounded-md border border-line px-2 py-1 text-xs font-medium text-fg-muted hover:bg-surface-alt"
                                onclick={(e) => { e.stopPropagation(); e.preventDefault(); restore(project); }}
                            >Restore</button>
                        {:else}
                            <button
                                type="button"
                                class="rounded-md border border-line px-2 py-1 text-xs font-medium text-fg-muted hover:bg-surface-alt"
                                onclick={(e) => { e.stopPropagation(); e.preventDefault(); archive(project); }}
                            >Archive</button>
                        {/if}
                    {/if}
                </div>
            </a>
        {/each}
    </div>
</AppShell>
