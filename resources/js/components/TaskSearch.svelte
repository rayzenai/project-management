<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { notesBoard } from '../lib/notesBoard.svelte';

    type TaskResult = {
        id: number;
        slug: string;
        item_number: number | null;
        title: string;
        short_title: string | null;
        status_label: string | null;
        project: { slug: string; title: string } | null;
    };
    type TaskRef = { slug: string; title: string; project: { slug: string; title: string } | null } | null;
    type NoteResult = { kind: 'task' | 'sticky'; id: number; title: string | null; body: string; task: TaskRef };
    type ContactResult = { id: number; name: string; role: string | null; organization: string | null; task: TaskRef };
    type Results = { tasks: TaskResult[]; notes: NoteResult[]; contacts: ContactResult[] };

    const empty: Results = { tasks: [], notes: [], contacts: [] };

    let open = $state(false);
    let query = $state('');
    let results = $state<Results>(empty);
    let loading = $state(false);
    let timer: ReturnType<typeof setTimeout> | undefined;
    let seq = 0;
    let inputEl = $state<HTMLInputElement | null>(null);

    const total = $derived(results.tasks.length + results.notes.length + results.contacts.length);
    const hasQuery = $derived(query.trim().length >= 2);

    function openModal() {
        open = true;
        queueMicrotask(() => inputEl?.focus());
    }

    function close() {
        open = false;
        query = '';
        results = empty;
        loading = false;
        clearTimeout(timer);
    }

    function onInput() {
        clearTimeout(timer);
        const q = query.trim();
        if (q.length < 2) {
            results = empty;
            loading = false;
            return;
        }
        loading = true;
        timer = setTimeout(() => runSearch(q), 200);
    }

    async function runSearch(q: string) {
        const mySeq = ++seq;
        try {
            const res = await fetch(`/workspace/search?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json' },
            });
            const body = await res.json();
            if (mySeq !== seq) return;
            results = (body.data ?? empty) as Results;
        } catch {
            if (mySeq === seq) results = empty;
        } finally {
            if (mySeq === seq) loading = false;
        }
    }

    function taskHref(ref: TaskRef): string | null {
        if (!ref?.project?.slug || !ref.slug) return null;
        return `/workspace/projects/${ref.project.slug}/tasks/${ref.slug}`;
    }

    function go(href: string | null) {
        if (!href) return;
        close();
        router.visit(href);
    }

    function openNote(n: NoteResult) {
        if (n.kind === 'sticky') {
            close();
            notesBoard.show({ noteId: n.id });
            return;
        }
        go(taskHref(n.task));
    }

    function contactMeta(c: ContactResult): string {
        return [c.role, c.organization].filter(Boolean).join(' · ');
    }

    function onWindowKey(event: KeyboardEvent) {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            if (open) {
                inputEl?.focus();
            } else {
                openModal();
            }
            return;
        }
        if (event.key === 'Escape' && open) {
            event.preventDefault();
            close();
        }
    }
</script>

<svelte:window onkeydown={onWindowKey} />

<button
    type="button"
    onclick={openModal}
    class="flex w-full items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-left text-sm text-neutral-400 hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700"
>
    <span>⌕</span>
    <span class="flex-1">Search all tasks, notes, contacts…</span>
    <kbd class="rounded border border-neutral-300 px-1.5 py-0.5 text-[10px] text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">⌘K</kbd>
</button>

{#if open}
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-neutral-950/40 px-4 py-16 backdrop-blur-md"
        onclick={close}
        role="presentation"
    >
        <div
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            onclick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-label="Search workspace"
        >
            <div class="flex items-center gap-2 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <span class="text-neutral-400">⌕</span>
                <input
                    bind:this={inputEl}
                    type="text"
                    bind:value={query}
                    oninput={onInput}
                    placeholder="Search all tasks, notes, contacts…"
                    class="w-full bg-transparent text-sm outline-none placeholder:text-neutral-400"
                />
                <kbd
                    class="rounded border border-neutral-300 px-1.5 py-0.5 text-[10px] text-neutral-500 dark:border-neutral-700 dark:text-neutral-400"
                    >Esc</kbd
                >
            </div>

            {#if !hasQuery}
                <div class="px-4 py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Type at least 2 characters to search.</div>
            {:else if loading && total === 0}
                <div class="px-4 py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">Searching…</div>
            {:else if total === 0}
                <div class="px-4 py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">No matches.</div>
            {:else}
                <div
                    class="grid max-h-[60vh] grid-cols-1 divide-y divide-neutral-200 overflow-y-auto sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-neutral-800"
                >
                    <div class="min-w-0">
                        <div class="px-3 pt-3 pb-1 text-[10px] font-semibold tracking-wider text-neutral-400 uppercase">
                            Tasks · {results.tasks.length}
                        </div>
                        {#each results.tasks as t (t.id)}
                            <button
                                type="button"
                                onclick={() => go(taskHref({ slug: t.slug, title: t.title, project: t.project }))}
                                class="flex w-full flex-col gap-0.5 px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            >
                                <span class="flex items-center gap-1.5 text-sm">
                                    {#if t.item_number}<span class="shrink-0 font-mono text-xs text-neutral-400">#{t.item_number}</span>{/if}
                                    <span class="min-w-0 truncate">{t.title}</span>
                                </span>
                                {#if t.project}<span class="truncate text-xs text-neutral-500 dark:text-neutral-400">{t.project.title}</span>{/if}
                            </button>
                        {:else}
                            <div class="px-3 py-2 text-xs text-neutral-400">No tasks.</div>
                        {/each}
                    </div>

                    <div class="min-w-0">
                        <div class="px-3 pt-3 pb-1 text-[10px] font-semibold tracking-wider text-neutral-400 uppercase">
                            Notes · {results.notes.length}
                        </div>
                        {#each results.notes as n (`${n.kind}-${n.id}`)}
                            <button
                                type="button"
                                onclick={() => openNote(n)}
                                class="flex w-full flex-col gap-0.5 px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            >
                                {#if n.title}<span class="truncate text-sm font-medium">{n.title}</span>{/if}
                                <span class="line-clamp-2 text-sm" class:text-neutral-600={!!n.title} class:dark:text-neutral-300={!!n.title}
                                    >{n.body}</span
                                >
                                {#if n.task}
                                    <span class="truncate text-xs text-neutral-500 dark:text-neutral-400">{n.task.title}</span>
                                {:else if n.kind === 'sticky'}
                                    <span class="truncate text-xs text-amber-600 dark:text-amber-400">Sticky note</span>
                                {/if}
                            </button>
                        {:else}
                            <div class="px-3 py-2 text-xs text-neutral-400">No notes.</div>
                        {/each}
                    </div>

                    <div class="min-w-0">
                        <div class="px-3 pt-3 pb-1 text-[10px] font-semibold tracking-wider text-neutral-400 uppercase">
                            Contacts · {results.contacts.length}
                        </div>
                        {#each results.contacts as c (c.id)}
                            <button
                                type="button"
                                onclick={() => go(taskHref(c.task))}
                                class="flex w-full flex-col gap-0.5 px-3 py-2 text-left hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            >
                                <span class="truncate text-sm">{c.name}</span>
                                {#if contactMeta(c)}<span class="truncate text-xs text-neutral-500 dark:text-neutral-400">{contactMeta(c)}</span>{/if}
                                {#if c.task}<span class="truncate text-[11px] text-neutral-400">{c.task.title}</span>{/if}
                            </button>
                        {:else}
                            <div class="px-3 py-2 text-xs text-neutral-400">No contacts.</div>
                        {/each}
                    </div>
                </div>
            {/if}
        </div>
    </div>
{/if}
