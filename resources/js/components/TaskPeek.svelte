<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import { SvelteMap } from 'svelte/reactivity';
    import { formatDate } from '../lib/format';
    import { peek } from '../lib/peek.svelte';
    import type { Priority, TaskPreview } from '../lib/types';
    import AssigneeStack from './AssigneeStack.svelte';
    import CompleteCheckbox from './CompleteCheckbox.svelte';
    import DateChip from './DateChip.svelte';
    import PriorityFlag from './PriorityFlag.svelte';
    import StatusChip from './StatusChip.svelte';

    const NOTE_TYPES: { value: string; label: string }[] = [
        { value: 'general', label: 'General note' },
        { value: 'action_taken', label: 'Action taken' },
        { value: 'meeting', label: 'Meeting' },
        { value: 'blocker', label: 'Blocker' },
        { value: 'milestone', label: 'Milestone' },
        { value: 'decision', label: 'Decision' },
    ];

    const cache = new SvelteMap<number, TaskPreview>();

    let preview = $state<TaskPreview | null>(null);
    let loadFailed = $state(false);
    let panel = $state<HTMLElement | null>(null);
    let seq = 0;

    let editingTitle = $state(false);
    let titleDraft = $state('');
    let editingDescription = $state(false);
    let descriptionDraft = $state('');
    let progressDraft = $state(0);
    let subtaskDraft = $state('');
    let noteDraft = $state('');
    let noteType = $state('general');
    let showContactForm = $state(false);
    let contactDraft = $state({ name: '', role: '', organization: '', phone: '', email: '' });
    let showHistory = $state(false);

    let openPathname = '';

    const target = $derived(peek.target);
    const task = $derived(preview?.task ?? null);
    const projectSlug = $derived(task?.project?.slug ?? '');
    const doneSubtasks = $derived(preview?.subtasks.filter((s) => s.is_done).length ?? 0);

    async function load(id: number, { background = false }: { background?: boolean } = {}) {
        const mySeq = ++seq;
        if (!background) loadFailed = false;

        try {
            const response = await fetch(`/workspace/tasks/${id}/preview`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const json = (await response.json()) as { data: TaskPreview };

            if (mySeq !== seq || peek.target?.id !== id) return;
            cache.set(id, json.data);
            preview = json.data;
            progressDraft = json.data.task.progress;
        } catch {
            if (mySeq !== seq || peek.target?.id !== id) return;
            if (!preview) loadFailed = true;
        }
    }

    function revalidate() {
        if (target) void load(target.id, { background: true });
    }

    // Re-runs only when the open target changes — NOT when `cache` mutates.
    // Reading `cache.get()` here would subscribe the effect to the cache key
    // that `load()` writes to, creating an infinite refetch loop that also
    // wiped transient UI state (open drafts, expanded history) on every cycle.
    $effect(() => {
        const opened = target;
        if (!opened) return;

        untrack(() => {
            openPathname = window.location.pathname;
            const cached = cache.get(opened.id) ?? null;
            preview = cached;
            progressDraft = cached?.task.progress ?? 0;
            editingTitle = editingDescription = showContactForm = showHistory = false;
            subtaskDraft = noteDraft = '';
            void load(opened.id, { background: cached !== null });
        });

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        queueMicrotask(() => panel?.focus());

        return () => {
            document.body.style.overflow = previousOverflow;
        };
    });

    // Inertia rewrites the URL after every visit; re-assert ?task= while open,
    // and close the peek when navigation actually changed the page.
    $effect(() => {
        void page.url;
        if (!peek.target) return;
        if (new URL(page.url, window.location.origin).pathname !== openPathname) {
            peek.close();
        } else {
            peek.syncUrl();
        }
    });

    function patchTask(payload: Record<string, string | number | boolean | null>, onSuccess?: () => void) {
        if (!task) return;
        router.patch(`/workspace/projects/${projectSlug}/tasks/${task.slug}`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                onSuccess?.();
                revalidate();
            },
        });
    }

    function applyLocal(patch: Partial<TaskPreview['task']>) {
        if (preview) preview.task = { ...preview.task, ...patch };
    }

    function saveTitle() {
        if (!task || !editingTitle) return;
        editingTitle = false;
        const next = titleDraft.trim();
        if (next === '' || next === task.title) return;
        applyLocal({ title: next });
        patchTask({ title: next });
    }

    function saveDescription() {
        if (!task || !editingDescription) return;
        editingDescription = false;
        if (descriptionDraft === (task.description ?? '')) return;
        applyLocal({ description: descriptionDraft });
        patchTask({ description: descriptionDraft });
    }

    function saveProgress() {
        if (!task || progressDraft === task.progress) return;
        applyLocal({ progress: progressDraft });
        patchTask({ progress: progressDraft });
    }

    function toggleSubtask(id: number, isDone: boolean) {
        if (preview) {
            preview.subtasks = preview.subtasks.map((s) => (s.id === id ? { ...s, is_done: isDone } : s));
        }
        router.patch(`/workspace/subtasks/${id}`, { is_done: isDone }, { preserveState: true, preserveScroll: true, onSuccess: revalidate });
    }

    function addSubtask() {
        if (!task || subtaskDraft.trim() === '') return;
        const body = subtaskDraft.trim();
        subtaskDraft = '';
        router.post(`/workspace/tasks/${task.id}/subtasks`, { body }, { preserveState: true, preserveScroll: true, onSuccess: revalidate });
    }

    function deleteSubtask(id: number) {
        if (preview) preview.subtasks = preview.subtasks.filter((s) => s.id !== id);
        router.delete(`/workspace/subtasks/${id}`, { preserveState: true, preserveScroll: true, onSuccess: revalidate });
    }

    function addNote() {
        if (!task || noteDraft.trim() === '') return;
        const body = noteDraft.trim();
        noteDraft = '';
        router.post(
            `/workspace/tasks/${task.id}/notes`,
            { body, type: noteType },
            { preserveState: true, preserveScroll: true, onSuccess: revalidate },
        );
    }

    function deleteNote(id: number) {
        if (preview) preview.notes = preview.notes.filter((n) => n.id !== id);
        router.delete(`/workspace/notes/${id}`, { preserveState: true, preserveScroll: true, onSuccess: revalidate });
    }

    function addContact() {
        if (!task || contactDraft.name.trim() === '') return;
        const payload = { ...contactDraft };
        contactDraft = { name: '', role: '', organization: '', phone: '', email: '' };
        showContactForm = false;
        router.post(`/workspace/tasks/${task.id}/contacts`, payload, { preserveState: true, preserveScroll: true, onSuccess: revalidate });
    }

    function onPanelKeydown(event: KeyboardEvent) {
        if (event.key === 'Escape') {
            event.stopPropagation();
            peek.close();
            return;
        }

        if (event.key !== 'Tab' || !panel) return;
        const focusables = Array.from(
            panel.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input, textarea, select, [tabindex]:not([tabindex="-1"])'),
        ).filter((el) => el.offsetParent !== null);
        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
</script>

{#if target}
    <div aria-hidden="true" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"></div>
    <div
        bind:this={panel}
        role="dialog"
        aria-modal="true"
        aria-labelledby="peek-title"
        aria-busy={!preview && !loadFailed}
        tabindex="-1"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[540px] flex-col border-l border-line bg-surface shadow-2xl outline-none"
        onkeydown={onPanelKeydown}
    >
        <header class="flex items-center gap-3 border-b border-line px-5 py-3">
            <div class="ws-eyebrow min-w-0 flex-1 truncate text-fg-muted">
                {#if task}
                    {#if task.item_number}#{task.item_number} ·
                    {/if}
                    <a href={`/workspace/projects/${projectSlug}`} class="hover:text-accent">
                        {task.project?.title ?? 'Project'}
                    </a>
                {:else}
                    Loading…
                {/if}
            </div>
            {#if task}
                <a
                    href={`/workspace/projects/${projectSlug}/tasks/${task.slug}`}
                    class="font-mono text-[11px] whitespace-nowrap text-fg-muted hover:text-accent"
                >
                    Open full page ↗
                </a>
            {/if}
            <button
                type="button"
                aria-label="Close"
                class="rounded-md p-1 text-fg-muted hover:bg-surface-alt hover:text-fg"
                onclick={() => peek.close()}
            >
                ✕
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            {#if loadFailed}
                <div class="flex flex-col items-center gap-3 py-16 text-center">
                    <p class="text-sm text-fg-muted">Couldn't load this task.</p>
                    <button
                        type="button"
                        class="rounded-md border border-line px-3 py-1.5 font-mono text-xs hover:border-accent"
                        onclick={() => target && load(target.id)}
                    >
                        Retry
                    </button>
                </div>
            {:else if !task}
                <div class="animate-pulse space-y-4 py-2">
                    <div class="h-6 w-3/4 rounded bg-surface-alt"></div>
                    <div class="h-4 w-1/2 rounded bg-surface-alt"></div>
                    <div class="h-24 rounded bg-surface-alt"></div>
                    <div class="h-16 rounded bg-surface-alt"></div>
                </div>
            {:else}
                <div class="space-y-5">
                    <div class="flex items-start gap-3">
                        <div class="pt-1">
                            <CompleteCheckbox {task} {projectSlug} />
                        </div>
                        {#if editingTitle}
                            <!-- svelte-ignore a11y_autofocus -->
                            <input
                                type="text"
                                bind:value={titleDraft}
                                autofocus
                                class="min-w-0 flex-1 border-0 border-b border-accent bg-transparent p-0 font-display text-lg font-bold tracking-tight outline-none"
                                onblur={saveTitle}
                                onkeydown={(e) => {
                                    if (e.key === 'Enter') saveTitle();
                                    if (e.key === 'Escape') {
                                        e.stopPropagation();
                                        editingTitle = false;
                                    }
                                }}
                            />
                        {:else}
                            <button
                                type="button"
                                id="peek-title"
                                class="min-w-0 flex-1 text-left font-display text-lg leading-snug font-bold tracking-tight hover:text-accent"
                                title="Click to rename"
                                onclick={() => {
                                    titleDraft = task.title;
                                    editingTitle = true;
                                }}
                            >
                                {task.title}
                            </button>
                        {/if}
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <StatusChip {task} {projectSlug} onUpdated={(status) => applyLocal({ status })} />
                        <PriorityFlag {task} {projectSlug} onUpdated={(priority: Priority) => applyLocal({ priority })} />
                        <DateChip {task} {projectSlug} onUpdated={(deadline_at) => applyLocal({ deadline_at })} />
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="ws-eyebrow text-fg-muted">Progress</span>
                        <input
                            type="range"
                            min="0"
                            max="100"
                            step="5"
                            bind:value={progressDraft}
                            class="flex-1 accent-accent"
                            onchange={saveProgress}
                        />
                        <span class="w-10 text-right font-mono text-xs text-fg-muted">{progressDraft}%</span>
                    </div>

                    <div>
                        <h3 class="ws-eyebrow mb-2 text-fg-muted">Assignees</h3>
                        <AssigneeStack
                            task={{ id: task.id, slug: task.slug, assignments: preview?.assignments ?? [] }}
                            team={preview?.team ?? []}
                            max={6}
                            align="left"
                            onUpdated={revalidate}
                        />
                    </div>

                    <div>
                        {#if editingDescription}
                            <!-- svelte-ignore a11y_autofocus -->
                            <textarea
                                bind:value={descriptionDraft}
                                rows="4"
                                autofocus
                                class="w-full rounded-md border border-line bg-surface px-2.5 py-2 text-sm"
                                onblur={saveDescription}
                                onkeydown={(e) => {
                                    if (e.key === 'Escape') {
                                        e.stopPropagation();
                                        editingDescription = false;
                                    }
                                }}
                            ></textarea>
                        {:else}
                            <button
                                type="button"
                                class="w-full text-left text-sm whitespace-pre-wrap text-fg-muted hover:text-fg"
                                onclick={() => {
                                    descriptionDraft = task.description ?? '';
                                    editingDescription = true;
                                }}
                            >
                                {#if task.description}
                                    {task.description}
                                {:else}
                                    <span class="text-fg-faint italic">Click to add a description…</span>
                                {/if}
                            </button>
                        {/if}
                    </div>

                    <section class="border-t border-line-soft pt-4">
                        <h3 class="ws-eyebrow mb-2 text-fg-muted">
                            Subtasks {#if preview && preview.subtasks.length > 0}({doneSubtasks}/{preview.subtasks.length}){/if}
                        </h3>
                        <ul class="space-y-1">
                            {#each preview?.subtasks ?? [] as subtask (subtask.id)}
                                <li class="group flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={subtask.is_done}
                                        class="h-4 w-4 rounded accent-success"
                                        onchange={(e) => toggleSubtask(subtask.id, (e.currentTarget as HTMLInputElement).checked)}
                                    />
                                    <span
                                        class={`flex-1 text-sm ${subtask.is_done ? 'text-fg-faint line-through' : 'text-fg-muted'}`}
                                    >
                                        {subtask.body}
                                    </span>
                                    <button
                                        type="button"
                                        aria-label="Delete subtask"
                                        class="text-fg-faint opacity-0 group-hover:opacity-100 hover:text-danger"
                                        onclick={() => deleteSubtask(subtask.id)}
                                    >
                                        ×
                                    </button>
                                </li>
                            {/each}
                        </ul>
                        <input
                            type="text"
                            bind:value={subtaskDraft}
                            placeholder="+ Add a subtask… ⏎"
                            class="mt-2 w-full rounded-md border border-dashed border-line bg-transparent px-2.5 py-1.5 text-sm placeholder:text-fg-faint focus:border-accent focus:outline-none"
                            onkeydown={(e) => {
                                if (e.key === 'Enter') addSubtask();
                            }}
                        />
                    </section>

                    <section class="border-t border-line-soft pt-4">
                        <h3 class="ws-eyebrow mb-2 text-fg-muted">
                            Notes {#if preview && preview.notes.length > 0}({preview.notes.length}){/if}
                        </h3>
                        <div class="space-y-2">
                            {#each preview?.notes ?? [] as note (note.id)}
                                <div class="group rounded-lg border border-line px-3 py-2">
                                    <div class="flex items-center gap-2 font-mono text-[10px] text-fg-muted">
                                        <span>{note.user?.name}</span>
                                        <span>· {note.type_label}</span>
                                        {#if note.happened_at}<span>· {formatDate(note.happened_at)}</span>{/if}
                                        <button
                                            type="button"
                                            aria-label="Delete note"
                                            class="ml-auto text-fg-faint opacity-0 group-hover:opacity-100 hover:text-danger"
                                            onclick={() => deleteNote(note.id)}
                                        >
                                            ×
                                        </button>
                                    </div>
                                    <p class="mt-1 text-sm whitespace-pre-wrap text-fg-muted">{note.body}</p>
                                </div>
                            {/each}
                        </div>
                        <div class="mt-2 flex items-start gap-2">
                            <textarea
                                bind:value={noteDraft}
                                rows="2"
                                placeholder="Add a note…"
                                class="min-w-0 flex-1 rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                            ></textarea>
                            <div class="flex flex-col gap-1.5">
                                <select
                                    bind:value={noteType}
                                    class="rounded-md border border-line bg-surface px-1.5 py-1 text-xs"
                                >
                                    {#each NOTE_TYPES as t (t.value)}
                                        <option value={t.value}>{t.label}</option>
                                    {/each}
                                </select>
                                <button
                                    type="button"
                                    class="rounded-md bg-accent px-2 py-1 text-xs font-semibold text-bg disabled:opacity-40"
                                    disabled={noteDraft.trim() === ''}
                                    onclick={addNote}
                                >
                                    Add
                                </button>
                            </div>
                        </div>
                    </section>

                    {#if preview}
                        <section class="border-t border-line-soft pt-4">
                            <a
                                href={`/workspace/projects/${projectSlug}/tasks/${task.slug}`}
                                class="ws-eyebrow flex items-center gap-1 text-fg-muted hover:text-accent"
                            >
                                Comments ({preview.comments_count}) ↗
                            </a>
                        </section>
                    {/if}

                    <section class="border-t border-line-soft pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="ws-eyebrow text-fg-muted">
                                Contacts {#if preview && preview.contacts.length > 0}({preview.contacts.length}){/if}
                            </h3>
                            <button
                                type="button"
                                class="font-mono text-[11px] text-fg-muted hover:text-accent"
                                onclick={() => (showContactForm = !showContactForm)}
                            >
                                {showContactForm ? 'cancel' : '+ add'}
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            {#each preview?.contacts ?? [] as contact (contact.id)}
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-surface-alt px-2.5 py-1 text-xs text-fg-muted"
                                    title={[contact.organization, contact.phone, contact.email].filter(Boolean).join(' · ')}
                                >
                                    <span class="font-medium">{contact.name}</span>
                                    {#if contact.role}<span class="text-fg-muted">· {contact.role}</span>{/if}
                                </span>
                            {/each}
                        </div>
                        {#if showContactForm}
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <input
                                    type="text"
                                    bind:value={contactDraft.name}
                                    placeholder="Name *"
                                    class="col-span-2 rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                                />
                                <input
                                    type="text"
                                    bind:value={contactDraft.role}
                                    placeholder="Role"
                                    class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                                />
                                <input
                                    type="text"
                                    bind:value={contactDraft.organization}
                                    placeholder="Organization"
                                    class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                                />
                                <input
                                    type="text"
                                    bind:value={contactDraft.phone}
                                    placeholder="Phone"
                                    class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                                />
                                <input
                                    type="email"
                                    bind:value={contactDraft.email}
                                    placeholder="Email"
                                    class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-sm"
                                />
                                <button
                                    type="button"
                                    class="col-span-2 rounded-md bg-accent px-3 py-1.5 text-xs font-semibold text-bg disabled:opacity-40"
                                    disabled={contactDraft.name.trim() === ''}
                                    onclick={addContact}
                                >
                                    Add contact
                                </button>
                            </div>
                        {/if}
                    </section>

                    <section class="border-t border-line-soft pt-4 pb-2">
                        <button
                            type="button"
                            aria-expanded={showHistory}
                            class="ws-eyebrow flex items-center gap-1 text-fg-muted hover:text-fg"
                            onclick={() => (showHistory = !showHistory)}
                        >
                            <span class={`transition-transform ${showHistory ? 'rotate-90' : ''}`}>▸</span>
                            History {#if preview && preview.activity.length > 0}({preview.activity.length}){/if}
                        </button>
                        {#if showHistory}
                            <ul class="mt-2 space-y-1.5">
                                {#each preview?.activity ?? [] as entry (entry.id)}
                                    <li class="flex items-baseline gap-2 text-xs text-fg-muted">
                                        <span class="min-w-0 flex-1">
                                            {#if entry.user}<span class="font-medium text-fg-muted">{entry.user.name}</span
                                                >{/if}
                                            {entry.description}
                                        </span>
                                        <span class="shrink-0 font-mono text-[10px] text-fg-faint"
                                            >{formatDate(entry.created_at)}</span
                                        >
                                    </li>
                                {:else}
                                    <li class="text-xs text-fg-faint">No recorded activity.</li>
                                {/each}
                            </ul>
                        {/if}
                    </section>
                </div>
            {/if}
        </div>
    </div>
    <button
        type="button"
        aria-label="Close panel"
        class="fixed inset-0 z-[45] cursor-default"
        onmousedown={(e) => {
            if (e.target === e.currentTarget) peek.close();
        }}
        tabindex="-1"
    ></button>
{/if}
