<script lang="ts">
    import type { ProjectSummary, User } from '../lib/types';
    import QuickAddForm from './QuickAddForm.svelte';

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
</script>

<div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <QuickAddForm {projects} {team} {currentUser} {defaultProjectId} variant="inline" />
</div>
