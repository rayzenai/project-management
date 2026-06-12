<script lang="ts">
    import { initials } from '../lib/format';
    import type { Member } from '../lib/types';

    let {
        team,
        selectedIds = $bindable([]),
        placeholder = 'Assign...',
        max = 1,
    }: {
        team: Member[];
        selectedIds: number[];
        placeholder?: string;
        max?: number;
    } = $props();

    let open = $state(false);
    let query = $state('');

    const filtered = $derived(
        query.trim() === ''
            ? team
            : team.filter((u) => u.name.toLowerCase().includes(query.toLowerCase()) || (u.email ?? '').toLowerCase().includes(query.toLowerCase())),
    );
    const selected = $derived(team.filter((u) => selectedIds.includes(u.id)));

    function toggle(memberId: number) {
        if (selectedIds.includes(memberId)) {
            selectedIds = selectedIds.filter((id) => id !== memberId);
        } else if (max === 1) {
            selectedIds = [memberId];
            open = false;
        } else if (selectedIds.length < max) {
            selectedIds = [...selectedIds, memberId];
        }
    }

    function remove(memberId: number) {
        selectedIds = selectedIds.filter((id) => id !== memberId);
    }
</script>

<div class="relative">
    <button
        type="button"
        class="flex min-h-[32px] w-full flex-wrap items-center gap-1.5 rounded-md border border-neutral-300 bg-white px-2 py-1 text-left text-sm shadow-sm hover:border-neutral-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-200 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:hover:border-neutral-600 dark:focus:ring-amber-500/30"
        onclick={() => (open = !open)}
    >
        {#if selected.length === 0}
            <span class="text-neutral-400 dark:text-neutral-500">{placeholder}</span>
        {/if}
        {#each selected as user (user.id)}
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-500/20 dark:text-amber-200"
            >
                <span
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-200 text-[10px] font-semibold text-amber-900 dark:bg-amber-400/40 dark:text-amber-100"
                >
                    {initials(user.name)}
                </span>
                {user.name}
                <button
                    type="button"
                    aria-label="Remove"
                    class="text-amber-700 hover:text-amber-900 dark:text-amber-300"
                    onclick={(e) => {
                        e.stopPropagation();
                        remove(user.id);
                    }}>×</button
                >
            </span>
        {/each}
    </button>

    {#if open}
        <div
            class="absolute right-0 left-0 z-30 mt-1 max-h-72 overflow-auto rounded-md border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
        >
            <input
                type="text"
                bind:value={query}
                placeholder="Search people..."
                class="w-full border-b border-neutral-200 bg-transparent px-3 py-2 text-sm focus:outline-none dark:border-neutral-700"
            />
            <ul class="max-h-56 overflow-auto py-1">
                {#each filtered as user (user.id)}
                    {@const sel = selectedIds.includes(user.id)}
                    <li>
                        <button
                            type="button"
                            class={`flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800 ${
                                sel ? 'bg-amber-50 dark:bg-amber-500/10' : ''
                            }`}
                            onclick={() => toggle(user.id)}
                        >
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200"
                            >
                                {initials(user.name)}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium">{user.name}</div>
                                <div class="truncate text-xs text-neutral-500 dark:text-neutral-400">{user.email}</div>
                            </div>
                            {#if selectedIds.includes(user.id)}
                                <span class="text-amber-600 dark:text-amber-400">✓</span>
                            {/if}
                        </button>
                    </li>
                {:else}
                    <li class="px-3 py-3 text-sm text-neutral-500 dark:text-neutral-400">No matches.</li>
                {/each}
            </ul>
        </div>
    {/if}
</div>
