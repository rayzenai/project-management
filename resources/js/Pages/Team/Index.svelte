<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import { initials } from '../../lib/format';
    import type { Member, Team } from '../../lib/types';

    let { teams, members }: { teams: Team[]; members: Member[] } = $props();

    // ---- Teams panel ----
    let creatingTeam = $state(false);
    let editingTeamId = $state<number | null>(null);
    let teamNameDraft = $state('');

    const teamForm = useForm({ name: '', description: '' });

    function createTeam(e: SubmitEvent) {
        e.preventDefault();
        if (!teamForm.name.trim()) return;
        teamForm.post('/workspace/teams', {
            preserveScroll: true,
            onSuccess: () => {
                teamForm.reset();
                creatingTeam = false;
            },
        });
    }

    function startRename(team: Team) {
        editingTeamId = team.id;
        teamNameDraft = team.name;
    }

    function renameTeam(team: Team) {
        const name = teamNameDraft.trim();
        editingTeamId = null;
        if (!name || name === team.name) return;
        router.patch(`/workspace/teams/${team.id}`, { name }, { preserveScroll: true });
    }

    function deleteTeam(team: Team) {
        if (!confirm(`Delete team "${team.name}"? Members are kept; only the grouping is removed.`)) return;
        router.delete(`/workspace/teams/${team.id}`, { preserveScroll: true });
    }

    function toggleTeamMember(team: Team, member: Member) {
        const current = team.member_ids ?? [];
        const next = current.includes(member.id) ? current.filter((id) => id !== member.id) : [...current, member.id];
        router.patch(`/workspace/teams/${team.id}`, { member_ids: next }, { preserveScroll: true });
    }

    // ---- Members panel ----
    let addingMember = $state(false);
    let editingMemberId = $state<number | null>(null);

    const memberForm = useForm({ name: '', email: '', password: '', title: '' });
    const memberEditForm = useForm({ name: '', email: '', password: '', title: '' });

    function addMember(e: SubmitEvent) {
        e.preventDefault();
        if (!memberForm.name.trim()) return;
        memberForm.post('/workspace/members', {
            preserveScroll: true,
            onSuccess: () => {
                memberForm.reset();
                addingMember = false;
            },
        });
    }

    function startEditMember(member: Member) {
        editingMemberId = member.id;
        memberEditForm.name = member.name;
        memberEditForm.email = member.email ?? '';
        memberEditForm.password = '';
        memberEditForm.title = member.title ?? '';
    }

    function saveMember(member: Member) {
        memberEditForm.patch(`/workspace/members/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => (editingMemberId = null),
        });
    }

    function setMemberActive(member: Member, active: boolean) {
        router.patch(`/workspace/members/${member.id}`, { is_active: active }, { preserveScroll: true });
    }

    function deleteMember(member: Member) {
        if (!confirm(`Remove ${member.name} entirely? Their task assignments${member.user_id ? ' and login' : ''} are deleted too. Prefer "Deactivate" to keep history.`)) return;
        router.delete(`/workspace/members/${member.id}`, { preserveScroll: true });
    }

    const memberTeamNames = $derived((member: Member) => teams.filter((t) => (member.team_ids ?? []).includes(t.id)).map((t) => t.name));

    const inputClass =
        'w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100';
</script>

<svelte:head><title>Team · Workspace</title></svelte:head>

<AppShell>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Team</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            People who can be assigned work — with or without a login — and the teams that group them.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <!-- Teams panel -->
        <section class="xl:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="ws-eyebrow text-neutral-500 dark:text-neutral-400">Teams · {teams.length}</h2>
                <button
                    type="button"
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 dark:text-neutral-950"
                    onclick={() => (creatingTeam = !creatingTeam)}>{creatingTeam ? 'Cancel' : '+ New team'}</button
                >
            </div>

            {#if creatingTeam}
                <form onsubmit={createTeam} class="mb-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="team-name">Name</label>
                    <input id="team-name" type="text" bind:value={teamForm.name} required class={inputClass} />
                    <label class="mt-3 mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="team-description"
                        >Description (optional)</label
                    >
                    <input id="team-description" type="text" bind:value={teamForm.description} class={inputClass} />
                    <div class="mt-3 flex justify-end">
                        <button
                            type="submit"
                            disabled={teamForm.processing || !teamForm.name.trim()}
                            class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50 dark:text-neutral-950"
                            >Create team</button
                        >
                    </div>
                    {#if teamForm.errors.name}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{teamForm.errors.name}</p>{/if}
                </form>
            {/if}

            {#if teams.length === 0 && !creatingTeam}
                <div
                    class="rounded-xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400"
                >
                    No teams yet. Until a project is attached to a team, every active member is assignable everywhere.
                </div>
            {/if}

            <div class="space-y-3">
                {#each teams as team (team.id)}
                    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-2">
                            {#if editingTeamId === team.id}
                                <input
                                    type="text"
                                    bind:value={teamNameDraft}
                                    class={inputClass}
                                    onblur={() => renameTeam(team)}
                                    onkeydown={(e) => {
                                        if (e.key === 'Enter') renameTeam(team);
                                        if (e.key === 'Escape') editingTeamId = null;
                                    }}
                                />
                            {:else}
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 truncate text-left text-base font-semibold hover:text-amber-700 dark:hover:text-amber-400"
                                    title="Rename"
                                    onclick={() => startRename(team)}>{team.name}</button
                                >
                                <span class="ws-eyebrow shrink-0 text-neutral-500 dark:text-neutral-400">
                                    {team.member_ids?.length ?? 0} member{(team.member_ids?.length ?? 0) === 1 ? '' : 's'}
                                </span>
                                <button
                                    type="button"
                                    aria-label={`Delete ${team.name}`}
                                    class="shrink-0 rounded p-1 text-neutral-400 hover:text-red-500"
                                    onclick={() => deleteTeam(team)}>✕</button
                                >
                            {/if}
                        </div>
                        {#if team.description}
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{team.description}</p>
                        {/if}
                        {#if members.length > 0}
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                {#each members.filter((m) => m.is_active !== false) as member (member.id)}
                                    {@const inTeam = (team.member_ids ?? []).includes(member.id)}
                                    <button
                                        type="button"
                                        aria-pressed={inTeam}
                                        class={`rounded-full px-2.5 py-1 text-xs font-medium transition ${
                                            inTeam
                                                ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-400 dark:bg-amber-500/20 dark:text-amber-200 dark:ring-amber-500/60'
                                                : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700'
                                        }`}
                                        onclick={() => toggleTeamMember(team, member)}
                                    >
                                        {member.name}
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        </section>

        <!-- Members panel -->
        <section class="xl:col-span-3">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="ws-eyebrow text-neutral-500 dark:text-neutral-400">Members · {members.length}</h2>
                <button
                    type="button"
                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 dark:text-neutral-950"
                    onclick={() => (addingMember = !addingMember)}>{addingMember ? 'Cancel' : '+ Add person'}</button
                >
            </div>

            {#if addingMember}
                <form onsubmit={addMember} class="mb-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="member-name">Name</label>
                            <input id="member-name" type="text" bind:value={memberForm.name} required class={inputClass} />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="member-email"
                                >Email {memberForm.password ? '' : '(optional)'}</label
                            >
                            <input
                                id="member-email"
                                type="email"
                                bind:value={memberForm.email}
                                required={!!memberForm.password}
                                class={inputClass}
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="member-password"
                                >Password (optional)</label
                            >
                            <input
                                id="member-password"
                                type="password"
                                bind:value={memberForm.password}
                                minlength="8"
                                autocomplete="new-password"
                                class={inputClass}
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400" for="member-title"
                                >Title (optional)</label
                            >
                            <input id="member-title" type="text" bind:value={memberForm.title} class={inputClass} />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                        Set a password to give them a login right away — or leave it blank and upgrade them later from Edit.
                    </p>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="submit"
                            disabled={memberForm.processing || !memberForm.name.trim()}
                            class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50 dark:text-neutral-950"
                            >Add person</button
                        >
                    </div>
                    {#if memberForm.errors.name}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{memberForm.errors.name}</p>{/if}
                    {#if memberForm.errors.email}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{memberForm.errors.email}</p>{/if}
                    {#if memberForm.errors.password}<p class="mt-2 text-xs text-red-600 dark:text-red-400">{memberForm.errors.password}</p>{/if}
                </form>
            {/if}

            {#if members.length === 0 && !addingMember}
                <div
                    class="rounded-xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400"
                >
                    No members yet. Add anyone you assign work to — give them a password now or upgrade them to a login later.
                </div>
            {/if}

            <div class="space-y-2">
                {#each members as member (member.id)}
                    <div
                        class={`rounded-xl border bg-white p-4 dark:bg-neutral-900 ${
                            member.is_active === false
                                ? 'border-neutral-200 opacity-60 dark:border-neutral-800'
                                : 'border-neutral-200 dark:border-neutral-800'
                        }`}
                    >
                        {#if editingMemberId === member.id}
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <input type="text" bind:value={memberEditForm.name} required class={inputClass} placeholder="Name" />
                                <input type="email" bind:value={memberEditForm.email} class={inputClass} placeholder="Email" />
                                <input type="text" bind:value={memberEditForm.title} class={inputClass} placeholder="Title" />
                                <input
                                    type="password"
                                    bind:value={memberEditForm.password}
                                    minlength="8"
                                    autocomplete="new-password"
                                    class={inputClass}
                                    placeholder={member.user_id ? 'New password (leave blank to keep)' : 'Set a password to enable login'}
                                />
                            </div>
                            {#if memberEditForm.errors.email}<p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {memberEditForm.errors.email}
                                </p>{/if}
                            {#if memberEditForm.errors.password}<p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {memberEditForm.errors.password}
                                </p>{/if}
                            <div class="mt-3 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-1.5 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800"
                                    onclick={() => (editingMemberId = null)}>Cancel</button
                                >
                                <button
                                    type="button"
                                    disabled={memberEditForm.processing}
                                    class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50 dark:text-neutral-950"
                                    onclick={() => saveMember(member)}>Save</button
                                >
                            </div>
                        {:else}
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200"
                                >
                                    {initials(member.name)}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-semibold">{member.name}</span>
                                        {#if !member.user_id}
                                            <span
                                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400"
                                                title="Edit and set a password to enable login">no login</span
                                            >
                                        {/if}
                                        {#if member.is_active === false}
                                            <span
                                                class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400"
                                                >inactive</span
                                            >
                                        {/if}
                                    </div>
                                    <div class="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                        {[member.title, member.email].filter(Boolean).join(' · ') || '—'}
                                        {#if memberTeamNames(member).length > 0}
                                            · {memberTeamNames(member).join(', ')}
                                        {/if}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                                    onclick={() => startEditMember(member)}>Edit</button
                                >
                                {#if member.is_active === false}
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs text-emerald-600 hover:underline dark:text-emerald-400"
                                        onclick={() => setMemberActive(member, true)}>Reactivate</button
                                    >
                                {:else}
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100"
                                        onclick={() => setMemberActive(member, false)}>Deactivate</button
                                    >
                                {/if}
                                <button
                                    type="button"
                                    aria-label={`Delete ${member.name}`}
                                    class="shrink-0 rounded p-1 text-neutral-400 hover:text-red-500"
                                    onclick={() => deleteMember(member)}>✕</button
                                >
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        </section>
    </div>
</AppShell>
