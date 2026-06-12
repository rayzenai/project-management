<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import type { Project } from '../../lib/types';

    let { project, status, onClose }: { project: Project; status: string; onClose?: () => void } = $props();

    let input = $state<HTMLInputElement | null>(null);
    const form = useForm({ title: '', status });

    $effect(() => {
        input?.focus();
    });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        if (!form.title.trim()) return;
        form.status = status;
        form.post(`/workspace/projects/${project.slug}/tasks`, {
            preserveScroll: true,
            onSuccess: () => {
                // Stay open and keep focus for rapid entry.
                form.title = '';
                input?.focus();
            },
        });
    }
</script>

<form onsubmit={submit} class="rounded-lg border border-amber-300 bg-white p-2 dark:border-amber-500/40 dark:bg-neutral-900">
    <input
        type="text"
        bind:this={input}
        bind:value={form.title}
        placeholder="Add a task…"
        class="w-full bg-transparent text-sm outline-none placeholder:text-neutral-400 dark:text-neutral-100 dark:placeholder:text-neutral-500"
        onkeydown={(e) => {
            if (e.key === 'Escape') {
                form.title = '';
                onClose?.();
            }
        }}
    />
    {#if form.errors.title}
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{form.errors.title}</p>
    {/if}
    <div class="mt-2 flex items-center justify-end gap-2">
        <button
            type="button"
            class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100"
            onclick={() => {
                form.title = '';
                onClose?.();
            }}
        >
            Cancel
        </button>
        <button
            type="submit"
            disabled={form.processing || !form.title.trim()}
            class="rounded-md bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-600 disabled:opacity-50 dark:text-neutral-950"
        >
            Add
        </button>
    </div>
</form>
