<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import type { Contact, Id } from '../lib/types';

    let { contacts }: { contacts: Contact[] } = $props();

    let openId = $state<Id | null>(null);

    function toggle(id: Id) {
        openId = openId === id ? null : id;
    }

    function close() {
        openId = null;
    }

    function taskHref(contact: Contact): string | null {
        const task = contact.task;
        if (!task?.project?.slug || !task.slug) return null;
        return `/workspace/projects/${task.project.slug}/tasks/${task.slug}`;
    }

    function subtitle(contact: Contact): string {
        return [contact.role, contact.organization].filter(Boolean).join(' · ');
    }

    function goToTask(contact: Contact) {
        const href = taskHref(contact);
        if (!href) return;
        close();
        router.visit(href);
    }

    function onWindowKey(event: KeyboardEvent) {
        if (event.key === 'Escape' && openId !== null) {
            event.preventDefault();
            close();
        }
    }
</script>

<svelte:window onkeydown={onWindowKey} />

<div class="flex flex-wrap items-center gap-2">
    <span class="text-[11px] font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">Contacts</span>

    {#if contacts.length === 0}
        <span class="text-xs text-neutral-400 dark:text-neutral-500"> Add a contact on any task and they'll surface here. </span>
    {:else}
        {#each contacts as contact (contact.id)}
            {@const href = taskHref(contact)}
            <div class="relative">
                <button
                    type="button"
                    onclick={(e) => {
                        e.stopPropagation();
                        toggle(contact.id);
                    }}
                    aria-haspopup="dialog"
                    aria-expanded={openId === contact.id}
                    class={`group inline-flex max-w-[16rem] items-center gap-1.5 rounded-full border bg-white py-1 pr-2.5 pl-1.5 text-xs transition hover:border-amber-300 hover:shadow-sm dark:bg-neutral-900 dark:hover:border-amber-500/40 ${
                        openId === contact.id ? 'border-amber-300 shadow-sm dark:border-amber-500/40' : 'border-neutral-200 dark:border-neutral-800'
                    }`}
                >
                    <span
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-[9px] font-semibold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-200"
                    >
                        {contact.name.slice(0, 1).toUpperCase()}
                    </span>
                    <span class="truncate font-medium text-neutral-800 dark:text-neutral-100">{contact.name}</span>
                    {#if subtitle(contact)}
                        <span class="truncate text-neutral-400 dark:text-neutral-500">· {subtitle(contact)}</span>
                    {/if}
                </button>

                {#if openId === contact.id}
                    <div
                        class="absolute top-full left-0 z-30 mt-1.5 w-64 overflow-hidden rounded-xl border border-neutral-200 bg-white text-left shadow-xl dark:border-neutral-700 dark:bg-neutral-900"
                        role="dialog"
                        aria-label={`Contact ${contact.name}`}
                        tabindex="-1"
                    >
                        <div class="border-b border-neutral-100 px-3 py-2.5 dark:border-neutral-800">
                            <div class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{contact.name}</div>
                            {#if subtitle(contact)}
                                <div class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{subtitle(contact)}</div>
                            {/if}
                        </div>

                        <div class="space-y-1.5 px-3 py-2.5 text-xs">
                            {#if contact.email}
                                <a
                                    href={`mailto:${contact.email}`}
                                    class="flex items-center gap-2 text-neutral-700 hover:text-amber-600 dark:text-neutral-300 dark:hover:text-amber-400"
                                >
                                    <span class="text-neutral-400">✉</span><span class="truncate">{contact.email}</span>
                                </a>
                            {/if}
                            {#if contact.phone}
                                <a
                                    href={`tel:${contact.phone}`}
                                    class="flex items-center gap-2 text-neutral-700 hover:text-amber-600 dark:text-neutral-300 dark:hover:text-amber-400"
                                >
                                    <span class="text-neutral-400">☎</span><span class="truncate">{contact.phone}</span>
                                </a>
                            {/if}
                            {#if contact.notes}
                                <p class="text-neutral-600 dark:text-neutral-400">{contact.notes}</p>
                            {/if}
                            {#if !contact.email && !contact.phone && !contact.notes}
                                <p class="text-neutral-400 dark:text-neutral-500">No contact details recorded.</p>
                            {/if}
                        </div>

                        {#if href && contact.task}
                            <button
                                type="button"
                                onclick={() => goToTask(contact)}
                                class="block w-full truncate border-t border-neutral-100 px-3 py-2 text-left text-xs font-medium text-amber-600 hover:bg-amber-50 dark:border-neutral-800 dark:text-amber-400 dark:hover:bg-amber-500/10"
                            >
                                on: {contact.task.short_title || contact.task.title} →
                            </button>
                        {/if}
                    </div>
                {/if}
            </div>
        {/each}
    {/if}
</div>

{#if openId !== null}
    <button type="button" aria-label="Close contact details" class="fixed inset-0 z-20 cursor-default" onclick={close}></button>
{/if}
