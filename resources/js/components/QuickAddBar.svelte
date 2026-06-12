<script lang="ts">
    import type { Member, ProjectSummary } from '../lib/types';
    import QuickAddForm from './QuickAddForm.svelte';

    let {
        projects,
        team,
        currentMemberId,
        defaultProjectId = null,
    }: {
        projects: ProjectSummary[];
        team: Member[];
        currentMemberId: number | null;
        defaultProjectId?: number | null;
    } = $props();
</script>

<div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <QuickAddForm {projects} {team} {currentMemberId} {defaultProjectId} variant="inline" />
</div>
