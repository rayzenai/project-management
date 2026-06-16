<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import { formatTimeAgo } from '../../lib/format';

    type NotificationRow = {
        id: string;
        read_at: string | null;
        created_at: string | null;
        data: {
            title?: string;
            body?: string;
            url?: string;
            [key: string]: unknown;
        };
    };

    type Paginated = {
        data: NotificationRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };

    let { notifications }: { notifications: Paginated } = $props();

    const hasUnread = $derived(notifications.data.some((n) => n.read_at === null));

    function markRead(n: NotificationRow) {
        if (n.read_at) return;
        router.post(`/workspace/notifications/${n.id}/read`, {}, { preserveScroll: true, preserveState: true });
    }

    function open(n: NotificationRow) {
        markRead(n);
        const url = n.data.url;
        if (url) {
            router.visit(url);
        }
    }

    function markAllRead() {
        router.post('/workspace/notifications/read-all', {}, { preserveScroll: true });
    }
</script>

<AppShell>
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Notifications</h1>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                Activity that involves you — assignments, mentions, and updates.
            </p>
        </div>
        {#if hasUnread}
            <button
                type="button"
                class="rounded-md border border-neutral-300 px-3 py-1.5 font-mono text-xs font-medium tracking-wide text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                onclick={markAllRead}>Mark all read</button
            >
        {/if}
    </div>

    {#if notifications.data.length === 0}
        <div
            class="rounded-xl border border-dashed border-neutral-300 px-6 py-16 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400"
        >
            <div class="mb-2 text-2xl">🔔</div>
            You're all caught up — no notifications yet.
        </div>
    {:else}
        <ul class="flex flex-col gap-2">
            {#snippet body(n: NotificationRow, unread: boolean)}
                <span
                    class={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${unread ? 'bg-amber-500' : 'bg-transparent'}`}
                    aria-hidden="true"
                ></span>
                <div class="min-w-0 flex-1">
                    <div class={`text-sm ${unread ? 'font-semibold' : 'font-medium'}`}>
                        {n.data.title ?? 'Notification'}
                    </div>
                    {#if n.data.body}
                        <div class="mt-0.5 truncate text-sm text-neutral-600 dark:text-neutral-400">
                            {n.data.body}
                        </div>
                    {/if}
                </div>
                <span class="ws-eyebrow shrink-0 text-neutral-400 dark:text-neutral-500">
                    {formatTimeAgo(n.created_at)}
                </span>
            {/snippet}

            {#each notifications.data as n (n.id)}
                {@const unread = n.read_at === null}
                {@const itemClass = `flex w-full items-start gap-3 rounded-lg border px-4 py-3 text-left transition ${
                    unread
                        ? 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10'
                        : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900'
                } hover:border-neutral-300 dark:hover:border-neutral-700`}
                <li>
                    {#if n.data.url}
                        <a
                            href={n.data.url}
                            onclick={(e) => {
                                e.preventDefault();
                                open(n);
                            }}
                            class={itemClass}
                        >
                            {@render body(n, unread)}
                        </a>
                    {:else}
                        <button type="button" onclick={() => open(n)} class={itemClass}>
                            {@render body(n, unread)}
                        </button>
                    {/if}
                </li>
            {/each}
        </ul>

        {#if notifications.links.length > 3}
            <div class="mt-6 flex flex-wrap items-center gap-1">
                {#each notifications.links as link (link.label)}
                    {#if link.url}
                        <button
                            type="button"
                            onclick={() => router.visit(link.url as string, { preserveScroll: true })}
                            class={`rounded-md px-3 py-1.5 font-mono text-xs transition ${
                                link.active
                                    ? 'bg-amber-500 text-white dark:text-neutral-950'
                                    : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                            }`}>{@html link.label}</button
                        >
                    {:else}
                        <span class="px-3 py-1.5 font-mono text-xs text-neutral-300 dark:text-neutral-600">{@html link.label}</span>
                    {/if}
                {/each}
            </div>
        {/if}
    {/if}
</AppShell>
