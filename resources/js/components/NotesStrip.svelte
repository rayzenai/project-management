<script lang="ts">
    import { notesBoard } from '../lib/notesBoard.svelte';
    import NoteSticky from './NoteSticky.svelte';
    import type { Note, WorkspaceNote } from '../lib/types';

    let { stickyNotes, taskNotes }: { stickyNotes: WorkspaceNote[]; taskNotes: Note[] } = $props();

    const FREEFORM_PREVIEW = 6;
    const TASK_PREVIEW = 8;

    const freeformPreview = $derived(stickyNotes.slice(0, FREEFORM_PREVIEW));
    const taskPreview = $derived(taskNotes.slice(0, TASK_PREVIEW));
    const freeformOverflow = $derived(stickyNotes.length - freeformPreview.length);
</script>

<div class="flex flex-wrap items-center gap-2.5">
    {#each freeformPreview as note (`w-${note.id}`)}
        <NoteSticky kind="freeform" {note} />
    {/each}

    {#each taskPreview as note (`t-${note.id}`)}
        <NoteSticky kind="task" {note} />
    {/each}

    <button
        type="button"
        onclick={() => notesBoard.show({ compose: true })}
        aria-label="New note"
        class="flex h-20 w-32 flex-col items-center justify-center gap-0.5 rounded-md border border-dashed border-line text-fg-faint transition hover:-translate-y-0.5 hover:border-accent hover:text-accent"
    >
        <span class="text-xl leading-none">+</span>
        <span class="text-[11px] font-medium">New note</span>
    </button>

    {#if freeformOverflow > 0}
        <button
            type="button"
            onclick={() => notesBoard.show()}
            class="h-20 rounded-md px-3 text-xs font-medium text-fg-muted hover:bg-surface-alt"
            >+{freeformOverflow} more</button
        >
    {/if}
</div>
