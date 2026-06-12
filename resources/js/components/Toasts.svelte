<script lang="ts">
    import { fly } from 'svelte/transition';
    import { toast } from '../lib/toast.svelte';
</script>

<div class="pointer-events-none fixed inset-x-0 bottom-6 z-[70] flex flex-col items-center gap-2 px-4">
    {#each toast.items as item (item.id)}
        <div
            role="status"
            transition:fly={{ y: 12, duration: 150 }}
            class="pointer-events-auto flex max-w-md min-w-64 items-center gap-3 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm shadow-lg dark:border-neutral-800 dark:bg-neutral-900"
            onmouseenter={() => toast.pause(item.id)}
            onmouseleave={() => toast.resume(item.id)}
        >
            <span
                class={item.variant === 'error'
                    ? 'text-red-500 dark:text-red-400'
                    : item.variant === 'success'
                      ? 'text-emerald-500 dark:text-emerald-400'
                      : 'text-neutral-400'}
            >
                {item.variant === 'error' ? '✕' : item.variant === 'success' ? '✓' : '·'}
            </span>
            <span class="min-w-0 flex-1 truncate text-neutral-800 dark:text-neutral-100">{item.message}</span>
            {#if item.undo}
                <button
                    type="button"
                    class="font-mono text-xs font-semibold text-amber-600 hover:underline dark:text-amber-400"
                    onclick={() => toast.undo(item.id)}
                >
                    {item.undo.label}
                </button>
            {/if}
            <button
                type="button"
                aria-label="Dismiss"
                class="text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                onclick={() => toast.dismiss(item.id)}
            >
                ✕
            </button>
        </div>
    {/each}
</div>
