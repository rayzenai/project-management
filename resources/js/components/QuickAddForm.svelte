<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import type { Priority, ProjectSummary, User } from '../lib/types';
    import AssigneePicker from './AssigneePicker.svelte';
    import PillGroup from './PillGroup.svelte';
    import TokenInput from './TokenInput.svelte';

    let {
        projects,
        team,
        currentUser,
        defaultProjectId = null,
        prefill = '',
        variant = 'inline',
        onSuccess,
        onCancel,
    }: {
        projects: ProjectSummary[];
        team: User[];
        currentUser: User | null;
        defaultProjectId?: number | null;
        prefill?: string;
        variant?: 'inline' | 'overlay';
        onSuccess?: () => void;
        onCancel?: () => void;
    } = $props();

    const initialProject = defaultProjectId ?? projects[0]?.id ?? null;

    // Empty picker values are stripped before POSTing so parsed title tokens
    // (#project @assignee !priority dates) can fill the gaps server-side;
    // anything explicitly picked here still wins over tokens.
    const form = useForm({
        project_id: initialProject,
        title: prefill,
        assignee_user_ids: [] as number[],
        deadline_at: '',
        priority: '' as Priority | '',
    });

    form.transform((data) => {
        const payload: Record<string, unknown> = { project_id: data.project_id, title: data.title };
        if (data.assignee_user_ids.length > 0) payload.assignee_user_ids = data.assignee_user_ids;
        if (data.deadline_at) payload.deadline_at = data.deadline_at;
        if (data.priority) payload.priority = data.priority;
        return payload;
    });

    let advanced = $state(false);
    let tokenInput = $state<{ focus: () => void } | null>(null);

    export function focusInput(): void {
        tokenInput?.focus();
    }

    function submit() {
        if (!form.title.trim() || !form.project_id) return;
        form.post('/workspace/quick-add', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('title', 'deadline_at');
                form.assignee_user_ids = [];
                form.priority = '';
                tokenInput?.focus();
                onSuccess?.();
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

{#snippet advancedFields()}
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
{/snippet}

<form
    onsubmit={(e) => {
        e.preventDefault();
        submit();
    }}
>
    {#if variant === 'inline'}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="flex flex-1 items-center gap-2">
                <span class="rounded-md bg-amber-500/15 px-2 py-1 text-base font-semibold text-amber-600 select-none dark:text-amber-400">+</span>
                <div class="min-w-0 flex-1">
                    <TokenInput
                        bind:this={tokenInput}
                        bind:value={form.title}
                        placeholder="What needs to happen? (press Q anywhere)"
                        disabled={form.processing}
                        onsubmit={submit}
                    />
                </div>
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
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-neutral-950"
                    >Add</button
                >
            </div>
        </div>

        {#if advanced}
            <div class="mt-3 grid grid-cols-1 gap-3 border-t border-neutral-200 pt-3 sm:grid-cols-3 dark:border-neutral-800">
                {@render advancedFields()}
            </div>
        {/if}
    {:else}
        <div class="border-b border-neutral-200 dark:border-neutral-800">
            <TokenInput
                bind:this={tokenInput}
                bind:value={form.title}
                placeholder="What needs to happen?"
                disabled={form.processing}
                onsubmit={submit}
            />
        </div>

        <div
            class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-neutral-200 px-3 py-2 font-mono text-[10px] text-neutral-500 dark:border-neutral-800 dark:text-neutral-400"
        >
            <span>#project</span>
            <span>@assignee</span>
            <span>!low / !medium / !high / !urgent</span>
            <span>today · fri · jun 20</span>
        </div>

        <div class="flex items-center justify-between gap-2 px-3 py-3">
            <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                Project:
                <select
                    bind:value={form.project_id}
                    class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                >
                    {#each projects as project (project.id)}
                        <option value={project.id}>{project.title}</option>
                    {/each}
                </select>
            </label>
            <button
                type="button"
                class="text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                onclick={() => (advanced = !advanced)}>{advanced ? 'Less ▴' : 'More ▾'}</button
            >
        </div>

        {#if advanced}
            <div class="grid grid-cols-1 gap-3 border-t border-neutral-200 px-3 py-3 sm:grid-cols-3 dark:border-neutral-800">
                {@render advancedFields()}
            </div>
        {/if}

        <div class="flex items-center justify-end gap-2 border-t border-neutral-200 px-3 py-3 dark:border-neutral-800">
            {#if onCancel}
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800"
                    onclick={onCancel}>Cancel</button
                >
            {/if}
            <button
                type="submit"
                disabled={form.processing || !form.title.trim()}
                class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-neutral-950"
                >Add task ⏎</button
            >
        </div>
    {/if}

    {#if form.errors.title}
        <p class="mt-2 text-xs text-red-600 dark:text-red-400" class:px-3={variant === 'overlay'} class:pb-3={variant === 'overlay'}>
            {form.errors.title}
        </p>
    {/if}
</form>
