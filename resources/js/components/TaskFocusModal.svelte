<script lang="ts">
    import { SvelteMap } from 'svelte/reactivity';
    import { initials } from '../lib/format';
    import type { Contact, Note, Subtask } from '../lib/types';

    let { todo, onClose }: { todo: Subtask; onClose: () => void } = $props();

    type Preview = {
        task: {
            id: number;
            slug: string;
            title: string;
            short_title?: string | null;
            title_np?: string | null;
            description?: string | null;
            item_number?: number | null;
            status: string;
            status_label?: string;
            progress: number;
            category?: string | null;
            category_label?: string | null;
            category_color?: string | null;
            deadline_at?: string | null;
            days_relative_label?: string;
            responsible_ministry?: string | null;
            project?: { slug: string; title: string } | null;
            assignees: { id?: number; name?: string }[];
        };
        notes: Note[];
        contacts: Contact[];
    };

    // Module-level cache so reopening the same task is instant.
    const cache = new SvelteMap<number, Preview>();

    let preview = $state<Preview | null>(null);
    let loading = $state(false);
    let errorMsg = $state<string | null>(null);

    const href = $derived(
        todo.task?.project?.slug && todo.task.slug ? `/workspace/projects/${todo.task.project.slug}/tasks/${todo.task.slug}` : null,
    );

    $effect(() => {
        const taskId = todo.task?.id;
        if (!taskId) return;
        if (cache.has(taskId)) {
            preview = cache.get(taskId)!;
            return;
        }
        loading = true;
        errorMsg = null;
        fetch(`/workspace/tasks/${taskId}/preview`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`))))
            .then((body) => {
                const data = body.data as Preview;
                cache.set(taskId, data);
                preview = data;
            })
            .catch((err) => {
                errorMsg = err.message ?? 'Failed to load.';
            })
            .finally(() => {
                loading = false;
            });
    });

    $effect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
    });

    function formatDate(value?: string | null): string {
        if (!value) return '';
        return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<div
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-neutral-950/40 px-4 py-10 backdrop-blur-md"
    role="dialog"
    aria-modal="true"
    onclick={onClose}
>
    <div
        class="w-full max-w-2xl overflow-hidden rounded-2xl border border-neutral-800/40 bg-neutral-900 text-neutral-100 shadow-2xl ring-1 ring-white/5"
        onclick={(e) => e.stopPropagation()}
        role="document"
    >
        <header class="flex items-center justify-between border-b border-neutral-800 px-6 py-3">
            <div class="flex items-baseline gap-3 text-xs">
                <span class="font-semibold tracking-wider text-neutral-400 uppercase">
                    Task {preview?.task.item_number ? `#${preview.task.item_number}` : `#${todo.task?.id ?? '—'}`}
                </span>
                {#if preview?.task.project}
                    <span class="text-neutral-500">{preview.task.project.title}</span>
                {/if}
                {#if preview?.task.category_label}
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset"
                        style="background-color: {preview.task.category_color}20; color: {preview.task.category_color}; --tw-ring-color: {preview.task
                            .category_color}40;">{preview.task.category_label}</span
                    >
                {/if}
                {#if preview?.task.status_label}
                    <span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-[10px] font-medium text-sky-300 ring-1 ring-sky-500/30 ring-inset">
                        {preview.task.status_label}
                    </span>
                {/if}
            </div>
            <button
                type="button"
                class="rounded-full p-1 text-neutral-400 hover:bg-neutral-800 hover:text-neutral-100"
                onclick={onClose}
                aria-label="Close">✕</button
            >
        </header>

        <div class="px-6 pt-5 pb-6">
            <h2 class="text-xl leading-snug font-bold">
                {preview?.task.title ?? todo.task?.title ?? todo.body}
            </h2>
            {#if preview?.task.title_np}
                <p class="mt-1 text-base text-neutral-400">{preview.task.title_np}</p>
            {/if}

            {#if preview?.task.description}
                <p class="mt-3 text-sm whitespace-pre-wrap text-neutral-300">{preview.task.description}</p>
            {/if}

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-400">
                {#if preview?.task.progress !== undefined}
                    <span>{preview.task.progress}% complete</span>
                {/if}
                {#if preview?.task.deadline_at}
                    <span
                        >· due {formatDate(preview.task.deadline_at)}
                        {preview.task.days_relative_label ? `(${preview.task.days_relative_label})` : ''}</span
                    >
                {/if}
                {#if preview?.task.responsible_ministry}
                    <span>· {preview.task.responsible_ministry}</span>
                {/if}
            </div>

            {#if preview?.task.assignees && preview.task.assignees.length > 0}
                <div class="mt-3 flex items-center gap-2 text-xs text-neutral-400">
                    <span class="tracking-wider uppercase">Assigned:</span>
                    <div class="flex -space-x-1.5">
                        {#each preview.task.assignees as a (a.id ?? a.name)}
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-neutral-900 bg-neutral-700 text-[9px] font-semibold text-neutral-200"
                                title={a.name}>{initials(a.name)}</span
                            >
                        {/each}
                    </div>
                </div>
            {/if}

            <div class="mt-5 border-t border-neutral-800 pt-4">
                <div class="flex items-baseline gap-3 text-sm">
                    <span class="text-xs font-medium tracking-wider text-neutral-500 uppercase">Todo</span>
                    <p class="font-medium" class:line-through={todo.is_done} class:text-neutral-500={todo.is_done}>
                        {todo.body}
                    </p>
                </div>
                {#if todo.due_at}
                    <p class="mt-1 pl-12 text-xs text-neutral-500">due {formatDate(todo.due_at)}</p>
                {/if}
            </div>

            {#if loading}
                <p class="mt-5 text-xs text-neutral-500">Loading task context…</p>
            {/if}

            {#if errorMsg}
                <p class="mt-5 text-xs text-red-400">{errorMsg}</p>
            {/if}

            {#if preview && preview.notes.length > 0}
                <div class="mt-5 border-t border-neutral-800 pt-4">
                    <h3 class="mb-2 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                        Notes ({preview.notes.length})
                    </h3>
                    <ul class="space-y-2">
                        {#each preview.notes.slice(0, 5) as note (note.id)}
                            <li class="rounded-lg bg-neutral-800/50 p-2.5 text-sm">
                                <div class="mb-1 flex items-baseline gap-2 text-[11px] text-neutral-400">
                                    <span class="rounded bg-neutral-700/60 px-1.5 py-0.5 font-medium tracking-wider text-neutral-300 uppercase">
                                        {note.type_label}
                                    </span>
                                    {#if note.user?.name}<span>{note.user.name}</span>{/if}
                                    {#if note.happened_at}<span>· {formatDate(note.happened_at)}</span>{/if}
                                </div>
                                <p class="leading-snug whitespace-pre-wrap">{note.body}</p>
                            </li>
                        {/each}
                        {#if preview.notes.length > 5}
                            <li class="pt-1 text-xs text-neutral-500">+ {preview.notes.length - 5} more</li>
                        {/if}
                    </ul>
                </div>
            {/if}

            {#if preview && preview.contacts.length > 0}
                <div class="mt-5 border-t border-neutral-800 pt-4">
                    <h3 class="mb-2 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                        Contacts ({preview.contacts.length})
                    </h3>
                    <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {#each preview.contacts as contact (contact.id)}
                            <li class="rounded-lg bg-neutral-800/50 p-2.5 text-sm">
                                <div class="font-medium">{contact.name}</div>
                                {#if contact.role || contact.organization}
                                    <div class="text-xs text-neutral-400">
                                        {[contact.role, contact.organization].filter(Boolean).join(' · ')}
                                    </div>
                                {/if}
                                <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-neutral-300">
                                    {#if contact.email}<a href={`mailto:${contact.email}`} class="hover:underline">✉ {contact.email}</a>{/if}
                                    {#if contact.phone}<a href={`tel:${contact.phone}`} class="hover:underline">☎ {contact.phone}</a>{/if}
                                </div>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}

            {#if href}
                <div class="mt-5 border-t border-neutral-800 pt-4">
                    <a {href} class="inline-flex items-center gap-2 text-sm font-semibold text-amber-400 hover:underline"
                        >Jump to task <span aria-hidden="true">→</span></a
                    >
                </div>
            {/if}
        </div>
    </div>
</div>
