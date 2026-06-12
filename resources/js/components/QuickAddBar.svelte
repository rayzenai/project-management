<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AssigneePicker from './AssigneePicker.svelte';
    import PillGroup from './PillGroup.svelte';
    import type { ProjectSummary, User } from '../lib/types';

    let {
        projects,
        team,
        currentUser,
        defaultProjectId = null,
    }: {
        projects: ProjectSummary[];
        team: User[];
        currentUser: User | null;
        defaultProjectId?: number | null;
    } = $props();

    const initialProject = defaultProjectId ?? projects[0]?.id ?? null;

    const form = useForm({
        project_id: initialProject,
        title: '',
        assignee_user_ids: currentUser ? [currentUser.id] : [],
        deadline_at: '',
        priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    });

    let advanced = $state(false);
    let inputEl: HTMLInputElement | null = $state(null);

    $effect(() => {
        if (typeof document === 'undefined') return;
        const onKey = (e: KeyboardEvent) => {
            if ((e.key === 'n' || e.key === 'N') && (e.metaKey || e.ctrlKey) === false) {
                const tag = (e.target as HTMLElement | null)?.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA') return;
                e.preventDefault();
                inputEl?.focus();
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        if (!form.title.trim() || !form.project_id) return;
        form.post('/workspace/quick-add', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('title', 'deadline_at');
                form.assignee_user_ids = currentUser ? [currentUser.id] : [];
                form.priority = 'medium';
                inputEl?.focus();
            },
        });
    }

    function assignMe() {
        if (!currentUser) return;
        if (!form.assignee_user_ids.includes(currentUser.id)) {
            form.assignee_user_ids = [...form.assignee_user_ids, currentUser.id];
        }
    }
</script>

<form onsubmit={submit} class="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="flex flex-1 items-center gap-2">
            <span class="rounded-md bg-amber-500/15 px-2 py-1 text-base font-semibold text-amber-600 select-none dark:text-amber-400">+</span>
            <input
                bind:this={inputEl}
                type="text"
                bind:value={form.title}
                placeholder="What needs to happen? (press N anywhere)"
                class="flex-1 bg-transparent text-base outline-none placeholder:text-neutral-400 dark:placeholder:text-neutral-500"
                disabled={form.processing}
            />
        </div>
        <div class="flex items-center gap-2">
            <select
                bind:value={form.project_id}
                class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            >
                {#each projects as project (project.id)}
                    <option value={project.id}>{project.title}</option>
                {/each}
            </select>
            <button
                type="button"
                class="text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                onclick={() => (advanced = !advanced)}>{advanced ? 'Less' : 'More'}</button
            >
            <button
                type="submit"
                disabled={form.processing || !form.title.trim()}
                class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                >Add</button
            >
        </div>
    </div>

    {#if advanced}
        <div class="mt-3 grid grid-cols-1 gap-3 border-t border-neutral-200 pt-3 sm:grid-cols-3 dark:border-neutral-800">
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Assign to</label>
                <AssigneePicker {team} bind:selectedIds={form.assignee_user_ids} max={5} placeholder="Pick teammates..." />
                <button type="button" class="mt-1 text-xs text-amber-600 hover:underline dark:text-amber-400" onclick={assignMe}>Assign me</button>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Due date</label>
                <input
                    type="date"
                    bind:value={form.deadline_at}
                    class="w-full rounded-md border border-neutral-300 bg-white px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Priority</label>
                <PillGroup
                    bind:value={form.priority}
                    options={[
                        { value: 'low', label: 'Low', tone: 'neutral' },
                        { value: 'medium', label: 'Medium', tone: 'amber' },
                        { value: 'high', label: 'High', tone: 'orange' },
                        { value: 'urgent', label: 'Urgent', tone: 'red' },
                    ]}
                />
            </div>
        </div>
    {/if}

    {#if form.errors.title}
        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{form.errors.title}</p>
    {/if}
</form>
