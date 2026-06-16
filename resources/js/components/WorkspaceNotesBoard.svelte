<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { formatDate } from '../lib/format';
    import { NOTE_COLORS as COLORS, paperClass, swatchClass, tilt } from '../lib/noteColors';
    import { notesBoard } from '../lib/notesBoard.svelte';
    import type { SharedProps, WorkspaceNote, WorkspaceNoteColor } from '../lib/types';

    let { open = false, onClose }: { open?: boolean; onClose: () => void } = $props();

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const notes = $derived(shared.workspaceNotes ?? []);

    // Local position overrides during/after a drag (id -> {x, y}).
    let pos = $state<Record<number, { x: number; y: number }>>({});
    function coords(note: WorkspaceNote): { x: number; y: number } {
        return pos[note.id] ?? { x: note.position_x, y: note.position_y };
    }

    let expandedId = $state<number | null>(null);
    let composing = $state(false);
    let editingId = $state<number | null>(null);
    let saving = $state(false);

    let draftTitle = $state('');
    let draftBody = $state('');

    // Drag bookkeeping.
    let dragId = $state<number | null>(null);
    let startX = 0;
    let startY = 0;
    let originX = 0;
    let originY = 0;
    let moved = false;

    const visitOptions = {
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            saving = true;
        },
        onFinish: () => {
            saving = false;
        },
    };

    function clampX(x: number): number {
        const max = (typeof window !== 'undefined' ? window.innerWidth : 1200) - 70;
        return Math.max(8, Math.min(x, max));
    }
    function clampY(y: number): number {
        const max = (typeof window !== 'undefined' ? window.innerHeight : 800) - 70;
        return Math.max(64, Math.min(y, max));
    }

    function beginDrag(note: WorkspaceNote, e: PointerEvent) {
        if (e.button !== 0) return;
        if ((e.target as HTMLElement)?.closest('[data-no-drag]')) return;
        const p = coords(note);
        dragId = note.id;
        startX = e.clientX;
        startY = e.clientY;
        originX = p.x;
        originY = p.y;
        moved = false;
    }

    function onPointerMove(e: PointerEvent) {
        if (dragId === null) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        if (Math.abs(dx) + Math.abs(dy) > 4) moved = true;
        pos = { ...pos, [dragId]: { x: clampX(originX + dx), y: clampY(originY + dy) } };
    }

    function onPointerUp() {
        if (dragId === null) return;
        const id = dragId;
        dragId = null;
        if (moved) {
            const p = pos[id];
            if (p) {
                router.patch(`/workspace/my-notes/${id}/placement`, { position_x: Math.round(p.x), position_y: Math.round(p.y) }, visitOptions);
            }
        } else if (expandedId !== id) {
            expandedId = id;
        }
    }

    function startCompose() {
        draftTitle = '';
        draftBody = '';
        composing = true;
    }

    function createNote() {
        if (!draftBody.trim() || saving) return;
        router.post(
            '/workspace/my-notes',
            { title: draftTitle.trim(), body: draftBody.trim() },
            {
                ...visitOptions,
                onSuccess: () => {
                    composing = false;
                },
            },
        );
    }

    function startEdit(note: WorkspaceNote) {
        draftTitle = note.title ?? '';
        draftBody = note.body;
        editingId = note.id;
    }

    function saveEdit(note: WorkspaceNote) {
        if (!draftBody.trim() || saving) return;
        router.patch(
            `/workspace/my-notes/${note.id}`,
            { title: draftTitle.trim(), body: draftBody.trim() },
            {
                ...visitOptions,
                onSuccess: () => {
                    editingId = null;
                },
            },
        );
    }

    function recolor(note: WorkspaceNote, color: WorkspaceNoteColor) {
        if (note.color === color) return;
        router.patch(`/workspace/my-notes/${note.id}/placement`, { color }, visitOptions);
    }

    function deleteNote(note: WorkspaceNote) {
        if (!confirm('Delete this note?')) return;
        router.delete(`/workspace/my-notes/${note.id}`, {
            ...visitOptions,
            onSuccess: () => {
                if (expandedId === note.id) expandedId = null;
            },
        });
    }

    function collapse() {
        expandedId = null;
        editingId = null;
    }

    function onWindowKey(event: KeyboardEvent) {
        if (event.key !== 'Escape' || !open) return;
        event.preventDefault();
        if (composing) {
            composing = false;
        } else if (expandedId !== null) {
            collapse();
        } else {
            onClose();
        }
    }

    $effect(() => {
        if (!open) {
            expandedId = null;
            editingId = null;
            composing = false;
            return;
        }
        // Apply the intent the opener requested (focus a note / start composing).
        if (notesBoard.compose) {
            composing = true;
        } else if (notesBoard.focusId != null) {
            expandedId = notesBoard.focusId;
        }
    });
</script>

<svelte:window onkeydown={onWindowKey} onpointermove={onPointerMove} onpointerup={onPointerUp} />

{#if open}
    <div class="fixed inset-0 z-50 bg-black/30 backdrop-blur-[2px] select-none">
        <!-- click empty board to close -->
        <button
            type="button"
            aria-label="Close notes board"
            class="absolute inset-0 h-full w-full cursor-default"
            onclick={() => {
                if (expandedId !== null) {
                    collapse();
                } else {
                    onClose();
                }
            }}
        ></button>

        <!-- toolbar -->
        <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex items-center justify-between px-4 py-3">
            <div
                class="pointer-events-auto flex items-center gap-2 rounded-full bg-surface/90 px-4 py-1.5 text-sm font-semibold shadow-sm ring-1 ring-line"
            >
                <span>🗒 My notes</span>
                <span class="rounded-full bg-surface-alt px-2 py-0.5 text-xs font-medium text-fg-muted"
                    >{notes.length}</span
                >
            </div>
            <div class="pointer-events-auto flex items-center gap-2">
                <button
                    type="button"
                    onclick={startCompose}
                    class="rounded-full bg-accent px-4 py-1.5 text-sm font-semibold text-bg shadow-sm hover:bg-accent-dim">+ New note</button
                >
                <button
                    type="button"
                    onclick={onClose}
                    aria-label="Close"
                    class="rounded-full bg-surface/90 p-2 text-fg-muted shadow-sm ring-1 ring-line hover:text-fg"
                    >✕</button
                >
            </div>
        </div>

        {#if notes.length === 0 && !composing}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <p class="rounded-xl bg-surface/80 px-4 py-2 text-sm text-fg-muted shadow-sm">
                    No notes yet — tap “+ New note” to pin one.
                </p>
            </div>
        {/if}

        <!-- notes -->
        {#each notes as note (note.id)}
            {@const p = coords(note)}
            {@const isExpanded = expandedId === note.id}
            {@const isDragging = dragId === note.id}
            <div
                class="absolute touch-none"
                style:left={`${p.x}px`}
                style:top={`${p.y}px`}
                style:transform={`rotate(${isExpanded || isDragging ? 0 : tilt(note.id)}deg)`}
                style:z-index={isExpanded ? 40 : isDragging ? 35 : 20}
                style:transition={isDragging ? 'none' : 'transform 150ms ease, width 150ms ease'}
                onpointerdown={(e) => beginDrag(note, e)}
                role="button"
                tabindex="-1"
            >
                <div
                    class={`flex flex-col rounded-md border shadow-lg ${paperClass[note.color]} ${isExpanded ? 'w-72 cursor-default p-3' : 'w-44 cursor-grab p-2.5 active:cursor-grabbing'}`}
                >
                    {#if isExpanded}
                        <div class="mb-1 flex items-center justify-between">
                            <div class="flex items-center gap-1" data-no-drag>
                                {#each COLORS as c (c)}
                                    <button
                                        type="button"
                                        aria-label={`Colour ${c}`}
                                        onclick={() => recolor(note, c)}
                                        class={`h-4 w-4 rounded-full ring-1 ring-black/10 transition ${swatchClass[c]} ${note.color === c ? 'ring-2 ring-neutral-900/50 dark:ring-white/60' : ''}`}
                                    ></button>
                                {/each}
                            </div>
                            <button
                                type="button"
                                data-no-drag
                                onclick={collapse}
                                aria-label="Collapse note"
                                class="rounded p-0.5 text-neutral-600/70 hover:bg-black/10 hover:text-neutral-900 dark:text-neutral-200/70">⤡</button
                            >
                        </div>

                        {#if editingId === note.id}
                            <input
                                type="text"
                                data-no-drag
                                bind:value={draftTitle}
                                placeholder="Heading (optional)"
                                class="mb-2 w-full rounded border border-black/10 bg-white/70 px-2 py-1.5 text-sm font-semibold outline-none focus:border-black/30 dark:bg-neutral-900/40 dark:text-neutral-100"
                            />
                            <textarea
                                data-no-drag
                                bind:value={draftBody}
                                rows="6"
                                class="w-full resize-y rounded border border-black/10 bg-white/70 px-2 py-1.5 text-sm outline-none focus:border-black/30 dark:bg-neutral-900/40 dark:text-neutral-100"
                            ></textarea>
                            <div class="mt-2 flex justify-end gap-2" data-no-drag>
                                <button
                                    type="button"
                                    onclick={() => (editingId = null)}
                                    class="rounded px-2 py-1 text-xs font-medium text-neutral-700 hover:bg-black/10 dark:text-neutral-200"
                                    >Cancel</button
                                >
                                <button
                                    type="button"
                                    onclick={() => saveEdit(note)}
                                    disabled={!draftBody.trim() || saving}
                                    class="rounded bg-neutral-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-neutral-700 disabled:opacity-50 dark:bg-white dark:text-neutral-900"
                                    >Save</button
                                >
                            </div>
                        {:else}
                            {#if note.title}
                                <h3 class="mb-1 text-sm font-bold text-neutral-900 dark:text-neutral-50" data-no-drag>{note.title}</h3>
                            {/if}
                            <p
                                class="max-h-[40vh] overflow-y-auto text-sm leading-relaxed break-words whitespace-pre-wrap text-neutral-800 dark:text-neutral-100"
                                data-no-drag
                            >
                                {note.body}
                            </p>
                            <div class="mt-2 flex items-center justify-between border-t border-black/10 pt-1.5 dark:border-white/10" data-no-drag>
                                <span class="text-[10px] text-neutral-600/80 dark:text-neutral-300/70">{formatDate(note.updated_at)}</span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        onclick={() => startEdit(note)}
                                        class="rounded px-2 py-0.5 text-xs font-medium text-neutral-700 hover:bg-black/10 dark:text-neutral-200"
                                        >Edit</button
                                    >
                                    <button
                                        type="button"
                                        onclick={() => deleteNote(note)}
                                        class="rounded px-2 py-0.5 text-xs font-medium text-red-700 hover:bg-red-500/15 dark:text-red-300"
                                        >Delete</button
                                    >
                                </div>
                            </div>
                        {/if}
                    {:else}
                        {#if note.title}
                            <span class="mb-0.5 line-clamp-2 text-sm font-bold text-neutral-900 dark:text-neutral-50">{note.title}</span>
                        {/if}
                        <span class="line-clamp-4 text-xs leading-snug break-words whitespace-pre-wrap text-neutral-800 dark:text-neutral-100"
                            >{note.body}</span
                        >
                        <span class="mt-1.5 text-[10px] text-neutral-600/70 dark:text-neutral-300/60">{formatDate(note.updated_at)}</span>
                    {/if}
                </div>
            </div>
        {/each}

        <!-- compose -->
        {#if composing}
            <div class="absolute inset-0 z-50 flex items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-xl border border-line bg-surface p-4 shadow-2xl">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">New note</h3>
                        <button
                            type="button"
                            onclick={() => (composing = false)}
                            aria-label="Cancel"
                            class="rounded-md p-1 text-fg-faint hover:bg-surface-alt hover:text-fg">✕</button
                        >
                    </div>
                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            createNote();
                        }}
                        class="flex flex-col gap-2"
                    >
                        <input
                            type="text"
                            bind:value={draftTitle}
                            placeholder="Heading (optional)"
                            class="w-full rounded-lg border border-line bg-surface-alt px-3 py-2 text-sm font-medium outline-none placeholder:text-fg-faint focus:border-accent"
                        />
                        <textarea
                            bind:value={draftBody}
                            placeholder="Write a note…"
                            rows="5"
                            class="w-full resize-y rounded-lg border border-line bg-surface-alt px-3 py-2 text-sm outline-none placeholder:text-fg-faint focus:border-accent"
                        ></textarea>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                onclick={() => (composing = false)}
                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-fg-muted hover:bg-surface-alt"
                                >Cancel</button
                            >
                            <button
                                type="submit"
                                disabled={!draftBody.trim() || saving}
                                class="rounded-lg bg-accent px-4 py-1.5 text-sm font-semibold text-bg shadow-sm hover:bg-accent-dim disabled:cursor-not-allowed disabled:opacity-50"
                                >Add note</button
                            >
                        </div>
                    </form>
                </div>
            </div>
        {/if}
    </div>
{/if}
