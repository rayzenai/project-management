<script module lang="ts">
    export interface ProjectFiltersState {
        assigneeIds: number[];
        overdueOnly: boolean;
        category: string | null;
    }

    export interface CategoryOption {
        value: string;
        label: string;
        color: string | null;
    }
</script>

<script lang="ts">
    import { initials } from '../../lib/format';
    import type { User } from '../../lib/types';

    let {
        filters = $bindable(),
        teammates,
        categories,
        shownCount,
        totalCount,
    }: {
        filters: ProjectFiltersState;
        teammates: User[];
        categories: CategoryOption[];
        shownCount: number;
        totalCount: number;
    } = $props();

    const anyActive = $derived(filters.assigneeIds.length > 0 || filters.overdueOnly || filters.category !== null);

    function toggleAssignee(id: number) {
        filters.assigneeIds = filters.assigneeIds.includes(id) ? filters.assigneeIds.filter((x) => x !== id) : [...filters.assigneeIds, id];
    }

    function clear() {
        filters = { assigneeIds: [], overdueOnly: false, category: null };
    }
</script>

<section
    class="mb-6 flex flex-wrap items-center gap-x-5 gap-y-3 rounded-xl border border-neutral-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900"
>
    {#if teammates.length > 0}
        <div class="flex items-center gap-2">
            <span class="ws-eyebrow text-neutral-500 dark:text-neutral-400">Assignees</span>
            <div class="flex items-center gap-1">
                {#each teammates as user (user.id)}
                    {@const active = filters.assigneeIds.includes(user.id)}
                    <button
                        type="button"
                        aria-pressed={active}
                        title={user.name}
                        class={`flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold transition ${
                            active
                                ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-400 dark:bg-amber-500/20 dark:text-amber-300 dark:ring-amber-400'
                                : 'bg-neutral-200 text-neutral-700 ring-1 ring-transparent hover:ring-amber-300 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:ring-amber-500/50'
                        }`}
                        onclick={() => toggleAssignee(user.id)}
                    >
                        {initials(user.name)}
                    </button>
                {/each}
            </div>
        </div>
    {/if}

    <button
        type="button"
        aria-pressed={filters.overdueOnly}
        class={`rounded-md border px-2.5 py-1 text-xs font-medium transition ${
            filters.overdueOnly
                ? 'border-amber-400 bg-amber-50 text-amber-800 dark:border-amber-500/60 dark:bg-amber-500/10 dark:text-amber-300'
                : 'border-neutral-300 text-neutral-600 hover:border-amber-300 hover:text-amber-700 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-amber-500/50 dark:hover:text-amber-300'
        }`}
        onclick={() => (filters.overdueOnly = !filters.overdueOnly)}
    >
        ⚠ Overdue only
    </button>

    {#if categories.length > 0}
        <label class="flex items-center gap-2">
            <span class="ws-eyebrow text-neutral-500 dark:text-neutral-400">Category</span>
            <select
                value={filters.category ?? ''}
                onchange={(e) => (filters.category = (e.currentTarget as HTMLSelectElement).value || null)}
                class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-xs text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200"
            >
                <option value="">All</option>
                {#each categories as category (category.value)}
                    <option value={category.value}>{category.label}</option>
                {/each}
            </select>
        </label>
    {/if}

    {#if anyActive}
        <div class="ml-auto flex items-center gap-2 font-mono text-xs">
            <span class={shownCount === 0 ? 'text-red-600 dark:text-red-400' : 'text-neutral-500 dark:text-neutral-400'}>
                {shownCount} of {totalCount} shown
            </span>
            <span class="text-neutral-300 dark:text-neutral-600">·</span>
            <button type="button" class="text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100" onclick={clear}>
                Clear ✕
            </button>
        </div>
    {/if}
</section>
