<script lang="ts">
    import { untrack } from 'svelte';
    import { useForm } from '@inertiajs/svelte';
    import type { Member, Priority, ProjectSummary } from '../lib/types';
    import AssigneePicker from './AssigneePicker.svelte';
    import PillGroup from './PillGroup.svelte';
    import TokenInput from './TokenInput.svelte';

    let {
        projects,
        team,
        currentMemberId,
        defaultProjectId = null,
        lockProject = false,
        prefill = '',
        variant = 'inline',
        onSuccess,
        onCancel,
    }: {
        projects: ProjectSummary[];
        team: Member[];
        currentMemberId: number | null;
        defaultProjectId?: number | null;
        lockProject?: boolean;
        prefill?: string;
        variant?: 'inline' | 'overlay';
        onSuccess?: () => void;
        onCancel?: () => void;
    } = $props();

    const uid = $props.id();
    const initialProject = untrack(() => defaultProjectId ?? projects[0]?.id ?? null);

    // When launched from inside a project, the project is fixed — we hide the
    // selector and show its name as static text instead.
    const lockedProjectName = $derived(lockProject ? (projects.find((p) => p.id === defaultProjectId)?.title ?? null) : null);

    // Empty picker values are stripped before POSTing so parsed title tokens
    // (#project @assignee !priority dates) can fill the gaps server-side;
    // anything explicitly picked here still wins over tokens.
    const form = useForm(
        untrack(() => ({
            project_id: initialProject,
            title: prefill,
            assignee_member_ids: [] as number[],
            deadline_at: '',
            priority: '' as Priority | '',
        })),
    );

    form.transform((data) => {
        const payload: Record<string, unknown> = { project_id: data.project_id, title: data.title };
        if (data.assignee_member_ids.length > 0) payload.assignee_member_ids = data.assignee_member_ids;
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
                form.assignee_member_ids = [];
                form.priority = '';
                tokenInput?.focus();
                onSuccess?.();
            },
        });
    }

    function assignMe() {
        if (!currentMemberId) return;
        if (!form.assignee_member_ids.includes(currentMemberId)) {
            form.assignee_member_ids = [...form.assignee_member_ids, currentMemberId];
        }
    }
</script>

{#snippet advancedFields()}
    <div>
        <span class="text-fg-muted mb-1 block text-xs font-medium">Assign to</span>
        <AssigneePicker {team} bind:selectedIds={form.assignee_member_ids} max={5} placeholder="Pick teammates..." flow={variant === 'overlay'} />
        <button type="button" class="text-accent mt-1 text-xs hover:underline" onclick={assignMe}>Assign me</button>
    </div>
    <div>
        <label for={`${uid}-due`} class="text-fg-muted mb-1 block text-xs font-medium">Due date</label>
        <input id={`${uid}-due`} type="date" bind:value={form.deadline_at} class="bg-surface w-full rounded-md border border-line px-2 py-1 text-sm" />
    </div>
    <div>
        <span class="text-fg-muted mb-1 block text-xs font-medium">Priority</span>
        <PillGroup
            dot
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
                <span class="bg-accent/15 text-accent rounded-md px-2 py-1 text-base font-semibold select-none">+</span>
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
                {#if lockProject}
                    {#if lockedProjectName}
                        <span class="text-fg-muted text-sm">Project: <span class="text-fg">{lockedProjectName}</span></span>
                    {/if}
                {:else}
                    <select bind:value={form.project_id} class="bg-surface rounded-md border border-line px-2 py-1 text-sm">
                        {#each projects as project (project.id)}
                            <option value={project.id}>{project.title}</option>
                        {/each}
                    </select>
                {/if}
                <button type="button" class="text-fg-muted hover:text-fg text-xs" onclick={() => (advanced = !advanced)}
                    >{advanced ? 'Less' : 'More'}</button
                >
                <button
                    type="submit"
                    disabled={form.processing || !form.title.trim()}
                    class="bg-accent text-bg hover:bg-accent-dim rounded-md px-3 py-1.5 text-sm font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                    >Add</button
                >
            </div>
        </div>

        {#if advanced}
            <div class="mt-3 grid grid-cols-1 gap-3 border-t border-line pt-3 sm:grid-cols-3">
                {@render advancedFields()}
            </div>
        {/if}
    {:else}
        <div class="border-b border-line">
            <TokenInput
                bind:this={tokenInput}
                bind:value={form.title}
                placeholder="What needs to happen?"
                disabled={form.processing}
                onsubmit={submit}
            />
        </div>

        <div class="text-fg-muted flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-line px-3 py-2 font-mono text-[10px]">
            <span>#project</span>
            <span>@assignee</span>
            <span>!low / !medium / !high / !urgent</span>
            <span>today · fri · jun 20</span>
        </div>

        <div class="flex items-center justify-between gap-2 px-3 py-3">
            {#if lockProject}
                <span class="text-fg-muted text-sm">
                    Project:
                    {#if lockedProjectName}<span class="text-fg">{lockedProjectName}</span>{/if}
                </span>
            {:else}
                <label class="text-fg-muted flex items-center gap-2 text-sm">
                    Project:
                    <select bind:value={form.project_id} class="bg-surface text-fg rounded-md border border-line px-2 py-1 text-sm">
                        {#each projects as project (project.id)}
                            <option value={project.id}>{project.title}</option>
                        {/each}
                    </select>
                </label>
            {/if}
            <button type="button" class="text-fg-muted hover:text-fg text-xs" onclick={() => (advanced = !advanced)}
                >{advanced ? 'Less ▴' : 'More ▾'}</button
            >
        </div>

        {#if advanced}
            <div class="grid grid-cols-1 items-start gap-3 border-t border-line px-3 py-3 sm:grid-cols-3">
                {@render advancedFields()}
            </div>
        {/if}

        <div class="flex items-center justify-end gap-2 border-t border-line px-3 py-3">
            {#if onCancel}
                <button type="button" class="text-fg-muted hover:bg-surface-alt rounded-md px-3 py-1.5 text-sm" onclick={onCancel}>Cancel</button>
            {/if}
            <button
                type="submit"
                disabled={form.processing || !form.title.trim()}
                class="bg-accent text-bg hover:bg-accent-dim rounded-md px-3 py-1.5 text-sm font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                >Add task ⏎</button
            >
        </div>
    {/if}

    {#if form.errors.title}
        <p class="mt-2 text-xs text-danger" class:px-3={variant === 'overlay'} class:pb-3={variant === 'overlay'}>
            {form.errors.title}
        </p>
    {/if}
</form>
