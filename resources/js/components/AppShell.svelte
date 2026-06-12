<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { initials } from '../lib/format';
    import { notesBoard } from '../lib/notesBoard.svelte';
    import type { SharedProps } from '../lib/types';
    import WorkspaceNotesBoard from './WorkspaceNotesBoard.svelte';

    let { children } = $props<{ children: import('svelte').Snippet }>();

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const user = $derived(shared.auth?.user ?? null);
    const flash = $derived(shared.flash ?? null);
    const path = $derived(page.url ?? '/workspace');
    const noteCount = $derived(shared.workspaceNotes?.length ?? 0);

    let isDark = $state(false);
    let mobileOpen = $state(false);
    let flashShown = $state<string | null>(null);

    $effect(() => {
        if (typeof window === 'undefined') return;
        const stored = window.localStorage.getItem('workspace.theme');
        const prefersDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        isDark = prefersDark;
        document.documentElement.classList.toggle('dark', prefersDark);
    });

    function toggleTheme() {
        isDark = !isDark;
        document.documentElement.classList.toggle('dark', isDark);
        if (typeof window !== 'undefined') {
            window.localStorage.setItem('workspace.theme', isDark ? 'dark' : 'light');
        }
    }

    $effect(() => {
        const message = flash?.message ?? null;
        if (message && message !== flashShown) {
            flashShown = message;
            const timeout = setTimeout(() => {
                flashShown = null;
            }, 3200);
            return () => clearTimeout(timeout);
        }
    });

    const nav = [
        { label: 'Overview', href: '/workspace', icon: '▦' },
        { label: 'My Workspace', href: '/workspace/my', icon: '✦' },
        { label: 'Projects', href: '/workspace/projects', icon: '▤' },
        { label: '100-Point Tracker', href: '/workspace/100-point-tracker', icon: '◉' },
    ];

    function isActive(href: string) {
        if (href === '/workspace/projects') {
            return path.startsWith('/workspace/projects') && !path.startsWith('/workspace/projects/100-day-plan');
        }
        return path === href || (href !== '/workspace' && path.startsWith(href + '/'));
    }

    function logout() {
        router.post('/logout');
    }
</script>

<div class="flex min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
    {#if mobileOpen}
        <button type="button" aria-label="Close menu" class="fixed inset-0 z-30 bg-black/40 lg:hidden" onclick={() => (mobileOpen = false)}></button>
    {/if}

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-neutral-200 bg-white px-4 py-6 transition-transform lg:static lg:translate-x-0 dark:border-neutral-800 dark:bg-neutral-900"
        class:translate-x-0={mobileOpen}
    >
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold tracking-wider text-amber-600 uppercase dark:text-amber-500">Kiran Timsina</div>
                <div class="text-lg font-bold">Workspace</div>
            </div>
            <button
                type="button"
                aria-label="Close sidebar"
                class="rounded-md p-1 text-neutral-500 hover:bg-neutral-100 lg:hidden dark:hover:bg-neutral-800"
                onclick={() => (mobileOpen = false)}>✕</button
            >
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            {#each nav as item (item.href)}
                {@const active = isActive(item.href)}
                <a
                    href={item.href}
                    class={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ${
                        active
                            ? 'bg-amber-50 text-amber-900 dark:bg-amber-500/10 dark:text-amber-400'
                            : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                    }`}
                >
                    <span class="w-5 text-center text-base">{item.icon}</span>
                    <span>{item.label}</span>
                </a>
            {/each}
        </nav>

        <div class="mt-6 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            {#if user}
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200"
                    >
                        {initials(user.name)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{user.name}</div>
                        <div class="truncate text-xs text-neutral-500 dark:text-neutral-400">{user.email}</div>
                    </div>
                    <button
                        type="button"
                        aria-label="Sign out"
                        class="rounded-md p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                        onclick={logout}
                        title="Sign out">↩</button
                    >
                </div>
            {/if}
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header
            class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-neutral-200 bg-white/80 px-4 backdrop-blur-md lg:px-8 dark:border-neutral-800 dark:bg-neutral-900/80"
        >
            <button
                type="button"
                aria-label="Open menu"
                class="rounded-md p-2 text-neutral-600 hover:bg-neutral-100 lg:hidden dark:text-neutral-300 dark:hover:bg-neutral-800"
                onclick={() => (mobileOpen = true)}>☰</button
            >
            <div class="flex-1"></div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick={() => notesBoard.toggle()}
                    aria-label={`My notes${noteCount ? ` (${noteCount})` : ''}`}
                    aria-expanded={notesBoard.open}
                    title="My notes"
                    class="relative rounded-md p-2 text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                >
                    <span class="text-base">🗒</span>
                    {#if noteCount > 0}
                        <span
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] leading-none font-semibold text-white"
                            >{noteCount}</span
                        >
                    {/if}
                </button>
                <button
                    type="button"
                    onclick={toggleTheme}
                    aria-label="Toggle theme"
                    class="rounded-md p-2 text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >{isDark ? '☀' : '☾'}</button
                >
            </div>
        </header>

        {#if flashShown}
            {@const isError = flash?.success === false}
            <div
                class={`mx-auto mt-3 w-full max-w-3xl rounded-lg px-4 py-2 text-sm shadow-sm ring-1 ${
                    isError
                        ? 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'
                        : 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30'
                }`}
            >
                {flashShown}
            </div>
        {/if}

        <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 lg:px-8">
            {@render children()}
        </main>
    </div>

    <WorkspaceNotesBoard open={notesBoard.open} onClose={() => notesBoard.hide()} />
</div>
