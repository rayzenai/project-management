<script lang="ts">
    import { inertia, page, router } from '@inertiajs/svelte';
    import { initials } from '../lib/format';
    import { notesBoard } from '../lib/notesBoard.svelte';
    import { palette } from '../lib/palette.svelte';
    import { peek } from '../lib/peek.svelte';
    import { quickAdd } from '../lib/quickAdd.svelte';
    import { toast } from '../lib/toast.svelte';
    import type { SharedProps } from '../lib/types';
    import type { AppearanceProps } from '../lib/appearance';
    import AppearanceConfig from './AppearanceConfig.svelte';
    import CommandPalette from './CommandPalette.svelte';
    import QuickAddOverlay from './QuickAddOverlay.svelte';
    import TaskPeek from './TaskPeek.svelte';
    import Toasts from './Toasts.svelte';
    import WorkspaceNotesBoard from './WorkspaceNotesBoard.svelte';

    let { children } = $props<{ children: import('svelte').Snippet }>();

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const user = $derived(shared.auth?.user ?? null);
    const flash = $derived(shared.flash ?? null);
    const path = $derived(page.url ?? '/workspace');
    const noteCount = $derived(shared.workspaceNotes?.length ?? 0);
    const unreadNotifications = $derived(shared.unreadNotifications ?? 0);
    const appearance = $derived((shared.appearance ?? null) as AppearanceProps | null);

    let mobileOpen = $state(false);
    let lastFlash = $state<string | null>(null);

    // First-run gate: blocks the workspace until the user saves preferences.
    // Local flag lets us hide the overlay immediately on save; the next Inertia
    // prop refresh carries `configured: true`.
    let onboardingDismissed = $state(false);
    const showOnboarding = $derived(appearance?.configured === false && !onboardingDismissed);

    // Settings → Appearance, reachable any time from the header as a modal panel.
    let settingsOpen = $state(false);

    $effect(() => {
        const message = flash?.message ?? null;
        if (message && message !== lastFlash) {
            lastFlash = message;
            const undo = flash?.undo;
            toast.show(message, {
                variant: flash?.success === false ? 'error' : 'success',
                undo: undo
                    ? {
                          label: undo.label,
                          run: () => router.post(undo.url, {}, { preserveScroll: true, preserveState: false }),
                      }
                    : undefined,
            });
        }
    });

    $effect(() => {
        if (typeof window === 'undefined') return;
        const id = setInterval(() => router.reload({ only: ['unreadNotifications'] }), 30000);
        return () => clearInterval(id);
    });

    const nav = [
        { label: 'Overview', href: '/workspace', icon: '▦' },
        { label: 'My Workspace', href: '/workspace/my', icon: '✦' },
        { label: 'Projects', href: '/workspace/projects', icon: '▤' },
        { label: 'Team', href: '/workspace/team', icon: '◎' },
    ];

    function isActive(href: string) {
        return path === href || (href !== '/workspace' && path.startsWith(href + '/'));
    }

    function logout() {
        router.post('/logout');
    }

    function skipOnboarding() {
        // Save the defaults so `configured` flips true — never trap the user.
        router.patch(
            '/workspace/preferences',
            { theme: 'system', font_override: { display: null, body: null, mono: null }, email_notifications: true },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => (onboardingDismissed = true),
            },
        );
    }

    function isEditable(target: EventTarget | null): boolean {
        const el = target as HTMLElement | null;
        if (!el) return false;
        const tag = el.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    const overlayOpen = $derived(quickAdd.isOpen || palette.isOpen || notesBoard.open || peek.target !== null);

    function onGlobalKey(e: KeyboardEvent) {
        if (e.defaultPrevented) return;

        // Esc fallback — the overlays handle Esc themselves when focused; this
        // catches the case where focus drifted back to the document.
        if (e.key === 'Escape') {
            if (palette.isOpen) {
                e.preventDefault();
                palette.close();
            } else if (quickAdd.isOpen) {
                e.preventDefault();
                quickAdd.close();
            }
            return;
        }

        // ⌘K / Ctrl+K toggles the palette even from inside an input.
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (palette.isOpen) {
                palette.close();
            } else if (!quickAdd.isOpen && !notesBoard.open) {
                palette.open();
            }
            return;
        }

        // Bare-key shortcuts only when nothing editable is focused, no overlay
        // is open, and no modifier is held.
        if (isEditable(e.target) || overlayOpen) return;
        if (e.metaKey || e.ctrlKey || e.altKey) return;

        if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            quickAdd.open();
            return;
        }
        if (e.key === '/') {
            e.preventDefault();
            palette.open();
        }
    }
</script>

<div class="ws-canvas flex min-h-screen bg-bg text-fg">
    {#if mobileOpen}
        <button type="button" aria-label="Close menu" class="fixed inset-0 z-30 bg-black/40 lg:hidden" onclick={() => (mobileOpen = false)}></button>
    {/if}

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface px-4 py-6 transition-transform lg:static lg:translate-x-0"
        class:translate-x-0={mobileOpen}
    >
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="ws-eyebrow text-accent">Kiran Timsina</div>
                <div class="font-display text-lg font-bold tracking-tight">Workspace</div>
            </div>
            <button
                type="button"
                aria-label="Close sidebar"
                class="rounded-md p-1 text-fg-muted hover:bg-surface-alt lg:hidden"
                onclick={() => (mobileOpen = false)}>✕</button
            >
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            {#each nav as item (item.href)}
                {@const active = isActive(item.href)}
                <a
                    href={item.href}
                    class={`flex items-center gap-3 rounded-lg border-l-2 px-3 py-2 font-mono text-[0.8rem] font-medium tracking-wide transition ${
                        active
                            ? 'border-accent bg-accent/10 text-accent'
                            : 'border-transparent text-fg-muted hover:bg-surface-alt'
                    }`}
                >
                    <span class="w-5 text-center text-base">{item.icon}</span>
                    <span>{item.label}</span>
                </a>
            {/each}
        </nav>

        <div class="mt-6 border-t border-line pt-4">
            {#if user}
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-alt text-sm font-semibold text-fg-muted"
                    >
                        {initials(user.name)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{user.name}</div>
                        <div class="truncate text-xs text-fg-muted">{user.email}</div>
                    </div>
                    <button
                        type="button"
                        aria-label="Sign out"
                        class="rounded-md p-1 text-fg-muted hover:bg-surface-alt hover:text-fg"
                        onclick={logout}
                        title="Sign out">↩</button
                    >
                </div>
            {/if}
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header
            class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-line bg-surface/80 px-4 backdrop-blur-md lg:px-8"
        >
            <button
                type="button"
                aria-label="Open menu"
                class="rounded-md p-2 text-fg-muted hover:bg-surface-alt lg:hidden"
                onclick={() => (mobileOpen = true)}>☰</button
            >
            <div class="flex-1"></div>
            <div class="flex items-center gap-2">
                <a
                    href="/workspace/notifications"
                    use:inertia
                    aria-label={`Notifications${unreadNotifications ? ` (${unreadNotifications} unread)` : ''}`}
                    title="Notifications"
                    class="relative rounded-md p-2 text-fg-muted hover:bg-surface-alt"
                >
                    <span class="text-base">🔔</span>
                    {#if unreadNotifications > 0}
                        <span
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] leading-none font-semibold text-bg"
                            >{unreadNotifications > 99 ? '99+' : unreadNotifications}</span
                        >
                    {/if}
                </a>
                <button
                    type="button"
                    onclick={() => notesBoard.toggle()}
                    aria-label={`My notes${noteCount ? ` (${noteCount})` : ''}`}
                    aria-expanded={notesBoard.open}
                    title="My notes"
                    class="relative rounded-md p-2 text-fg-muted hover:bg-surface-alt"
                >
                    <span class="text-base">🗒</span>
                    {#if noteCount > 0}
                        <span
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] leading-none font-semibold text-bg"
                            >{noteCount}</span
                        >
                    {/if}
                </button>
                <button
                    type="button"
                    onclick={() => (settingsOpen = true)}
                    aria-label="Appearance settings"
                    title="Appearance"
                    class="rounded-md p-2 text-fg-muted hover:bg-surface-alt"
                >
                    <span class="text-base">⚙</span>
                </button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 lg:px-8">
            {@render children()}
        </main>
    </div>

    <WorkspaceNotesBoard open={notesBoard.open} onClose={() => notesBoard.hide()} />
    <TaskPeek />
    <QuickAddOverlay />
    <CommandPalette />
    <Toasts />

    <!-- Settings → Appearance: a modal panel reachable any time from the header -->
    {#if settingsOpen && appearance}
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm">
            <button type="button" aria-label="Close" class="fixed inset-0" onclick={() => (settingsOpen = false)}></button>
            <div class="relative my-8 w-full max-w-2xl rounded-2xl border border-line bg-bg p-6 text-fg shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold tracking-tight">Appearance</h2>
                    <button
                        type="button"
                        aria-label="Close"
                        class="rounded-md p-1 text-fg-muted hover:bg-surface-alt hover:text-fg"
                        onclick={() => (settingsOpen = false)}>✕</button
                    >
                </div>
                <AppearanceConfig {appearance} onsaved={() => (settingsOpen = false)} />
            </div>
        </div>
    {/if}

    <!-- First-run gate: full-screen blocking overlay until the user configures -->
    {#if showOnboarding && appearance}
        <div class="fixed inset-0 z-50 overflow-y-auto bg-bg text-fg">
            <div class="mx-auto w-full max-w-2xl px-4 py-12">
                <div class="mb-8 text-center">
                    <div class="ws-eyebrow text-accent">Welcome</div>
                    <h1 class="font-display text-3xl font-bold tracking-tight">Make it yours</h1>
                    <p class="mt-2 text-sm text-fg-muted">
                        Choose a theme and fonts before you start. You can change these any time from the ⚙ menu.
                    </p>
                </div>
                <AppearanceConfig {appearance} onsaved={() => (onboardingDismissed = true)} />
                <div class="mt-6 text-center">
                    <button
                        type="button"
                        onclick={skipOnboarding}
                        class="text-sm text-fg-faint underline-offset-4 hover:text-fg-muted hover:underline"
                    >
                        Skip — use defaults
                    </button>
                </div>
            </div>
        </div>
    {/if}
</div>

<svelte:window onkeydown={onGlobalKey} />
