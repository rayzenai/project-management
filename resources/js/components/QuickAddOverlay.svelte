<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { quickAdd } from '../lib/quickAdd.svelte';
    import type { SharedProps } from '../lib/types';
    import QuickAddForm from './QuickAddForm.svelte';

    const shared = $derived((page.props ?? {}) as unknown as SharedProps);
    const context = $derived(shared.quickAddContext ?? { projects: [], team: [] });
    const currentUser = $derived(shared.auth?.user ?? null);

    let formComp = $state<{ focusInput: () => void } | null>(null);

    $effect(() => {
        if (!quickAdd.isOpen) return;
        queueMicrotask(() => formComp?.focusInput());
    });

    $effect(() => {
        if (typeof document === 'undefined' || !quickAdd.isOpen) return;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = '';
        };
    });

    function onPanelKeydown(e: KeyboardEvent) {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            quickAdd.close();
        }
    }
</script>

{#if quickAdd.isOpen}
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-neutral-950/40 px-4 py-16 backdrop-blur-md"
        onclick={() => quickAdd.close()}
        role="presentation"
    >
        <div
            class="w-full max-w-2xl overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            onclick={(e) => e.stopPropagation()}
            onkeydown={onPanelKeydown}
            role="dialog"
            aria-modal="true"
            aria-label="New task"
            tabindex="-1"
        >
            <div class="flex items-center justify-between border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <div class="ws-eyebrow text-amber-600 dark:text-amber-400">+ New task</div>
                <kbd
                    class="rounded border border-neutral-300 px-1.5 py-0.5 text-[10px] text-neutral-500 dark:border-neutral-700 dark:text-neutral-400"
                    >Esc</kbd
                >
            </div>
            <QuickAddForm
                bind:this={formComp}
                projects={context.projects}
                team={context.team}
                {currentUser}
                defaultProjectId={quickAdd.projectId}
                prefill={quickAdd.prefill}
                variant="overlay"
                onSuccess={() => quickAdd.close()}
                onCancel={() => quickAdd.close()}
            />
        </div>
    </div>
{/if}
