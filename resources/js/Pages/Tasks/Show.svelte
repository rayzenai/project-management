<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppShell from '../../components/AppShell.svelte';
    import AssigneePicker from '../../components/AssigneePicker.svelte';
    import PillGroup from '../../components/PillGroup.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import { initials, formatDate } from '../../lib/format';
    import type { Assignment, Contact, Note, Project, Subtask, Task, User } from '../../lib/types';

    let {
        project,
        task,
        notes,
        contacts,
        subtasks,
        team,
        statuses,
    }: {
        project: Project;
        task: Task;
        notes: Note[];
        contacts: Contact[];
        subtasks: Subtask[];
        team: User[];
        statuses: { value: string; label: string }[];
    } = $props();

    let activeTab: 'overview' | 'todos' | 'notes' | 'contacts' = $state('overview');

    const todoForm = useForm({ body: '', due_at: '' });

    function submitTodo() {
        if (!todoForm.body.trim() || todoForm.processing) return;
        todoForm.post(`/workspace/tasks/${task.id}/subtasks`, {
            preserveScroll: true,
            onSuccess: () => todoForm.reset(),
        });
    }

    function addTodo(e: SubmitEvent) {
        e.preventDefault();
        submitTodo();
    }

    function toggleTodo(t: Subtask) {
        router.patch(`/workspace/subtasks/${t.id}`, { is_done: !t.is_done }, { preserveScroll: true });
    }

    function deleteTodo(t: Subtask) {
        router.delete(`/workspace/subtasks/${t.id}`, { preserveScroll: true });
    }

    const editForm = useForm({
        title: task.title,
        description: task.description ?? '',
        status: task.status,
        priority: task.priority ?? 'medium',
        task_progress: task.progress,
        deadline_at: task.deadline_at ?? '',
        status_note: task.status_note ?? '',
        source_url: task.source_url ?? '',
    });

    // `progress` is a reserved field name on useForm, so the form tracks it
    // as `task_progress` and maps it back to what the backend validates.
    editForm.transform(({ task_progress, ...data }) => ({ ...data, progress: task_progress }));

    const noteForm = useForm({ body: '', type: 'general', happened_at: '' });
    const contactForm = useForm({ name: '', role: '', email: '', phone: '', organization: '', notes: '' });
    const assignForm = useForm({
        user_id: 0,
        role: '',
    });

    let pickerSelected = $state<number[]>([]);
    let lastAttemptedAssignee = 0;

    // Assign immediately when a teammate is picked — no separate Assign button.
    // `lastAttemptedAssignee` stops a failed request from retrying in a loop.
    $effect(() => {
        const id = pickerSelected[0];
        if (id && id !== lastAttemptedAssignee && !assignForm.processing) {
            lastAttemptedAssignee = id;
            assign();
        }
    });

    function submitEdit() {
        if (!editForm.isDirty || editForm.processing) return;
        editForm.patch(`/workspace/projects/${project.slug}/tasks/${task.slug}`, {
            preserveScroll: true,
            onSuccess: () => editForm.defaults(),
        });
    }

    function saveEdit(e: SubmitEvent) {
        e.preventDefault();
        submitEdit();
    }

    function handleSaveShortcut(e: KeyboardEvent) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            if (activeTab === 'todos') {
                submitTodo();
            } else if (activeTab === 'notes') {
                submitNote();
            } else if (activeTab === 'contacts') {
                submitContact();
            } else {
                submitEdit();
            }
        }
    }

    function deleteTask() {
        if (!confirm('Delete this task? This cannot be undone.')) return;
        router.delete(`/workspace/projects/${project.slug}/tasks/${task.slug}`);
    }

    function submitNote() {
        if (!noteForm.body.trim() || noteForm.processing) return;
        noteForm.post(`/workspace/tasks/${task.id}/notes`, {
            preserveScroll: true,
            onSuccess: () => noteForm.reset(),
        });
    }

    function addNote(e: SubmitEvent) {
        e.preventDefault();
        submitNote();
    }

    function submitContact() {
        if (!contactForm.name.trim() || contactForm.processing) return;
        contactForm.post(`/workspace/tasks/${task.id}/contacts`, {
            preserveScroll: true,
            onSuccess: () => contactForm.reset(),
        });
    }

    function addContact(e: SubmitEvent) {
        e.preventDefault();
        submitContact();
    }

    function assign() {
        if (pickerSelected.length === 0) return;
        assignForm.user_id = pickerSelected[0];
        assignForm.post(`/workspace/tasks/${task.id}/assignments`, {
            preserveScroll: true,
            onSuccess: () => {
                pickerSelected = [];
                lastAttemptedAssignee = 0;
                assignForm.reset();
            },
        });
    }

    function unassign(assignment: Assignment) {
        if (!confirm(`Remove ${assignment.user?.name} from this task?`)) return;
        router.delete(`/workspace/assignments/${assignment.id}`, { preserveScroll: true });
    }

    function deleteNote(note: Note) {
        if (!confirm('Delete this note?')) return;
        router.delete(`/workspace/notes/${note.id}`, { preserveScroll: true });
    }
</script>

<svelte:window onkeydown={handleSaveShortcut} />

<svelte:head><title>{task.title} · Workspace</title></svelte:head>

<AppShell>
    <nav class="mb-3 text-xs text-neutral-500 dark:text-neutral-400">
        <a href="/workspace/projects" class="hover:underline">Projects</a> /
        <a href={`/workspace/projects/${project.slug}`} class="hover:underline">{project.title}</a> /
        <span>{task.short_title || task.title}</span>
    </nav>

    <header class="mb-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                {#if task.item_number}
                    <div
                        class="mb-1 inline-flex items-center rounded bg-neutral-100 px-2 py-0.5 font-mono text-xs text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                    >
                        #{task.item_number}
                    </div>
                {/if}
                <h1 class="text-2xl font-bold tracking-tight">{task.title}</h1>
                {#if task.title_np}
                    <div class="mt-1 text-base text-neutral-600 dark:text-neutral-400">{task.title_np}</div>
                {/if}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <StatusBadge status={task.status} label={task.status_label} />
                    {#if task.deadline_at}
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">
                            Due {formatDate(task.deadline_at)} · {task.days_relative_label}
                        </span>
                    {/if}
                    {#if task.progress > 0}
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">{task.progress}% complete</span>
                    {/if}
                    {#if task.responsible_ministry}
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">· {task.responsible_ministry}</span>
                    {/if}
                </div>
            </div>
            <div class="flex shrink-0 gap-2">
                <button
                    type="button"
                    onclick={deleteTask}
                    class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-sm text-red-700 hover:bg-red-50 dark:border-red-500/30 dark:bg-neutral-900 dark:text-red-400 dark:hover:bg-red-500/10"
                    >Delete</button
                >
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="mb-4 flex gap-1 border-b border-neutral-200 dark:border-neutral-800">
                {#each [['overview', 'Overview'], ['todos', `My todos (${subtasks.filter((s) => !s.is_done).length})`], ['notes', `Notes (${notes.length})`], ['contacts', `Contacts (${contacts.length})`]] as [key, label] (key)}
                    <button
                        type="button"
                        class="border-b-2 px-3 py-2 text-sm font-medium transition"
                        class:border-amber-500={activeTab === key}
                        class:text-amber-700={activeTab === key}
                        class:dark:text-amber-400={activeTab === key}
                        class:border-transparent={activeTab !== key}
                        class:text-neutral-500={activeTab !== key}
                        class:dark:text-neutral-400={activeTab !== key}
                        onclick={() => (activeTab = key as typeof activeTab)}>{label}</button
                    >
                {/each}
            </div>

            <p class="mb-3 text-xs text-neutral-400 dark:text-neutral-500">
                Press
                <kbd class="rounded border border-neutral-300 bg-neutral-100 px-1 font-sans dark:border-neutral-700 dark:bg-neutral-800">Ctrl</kbd
                >/<kbd class="rounded border border-neutral-300 bg-neutral-100 px-1 font-sans dark:border-neutral-700 dark:bg-neutral-800">⌘</kbd>
                +
                <kbd class="rounded border border-neutral-300 bg-neutral-100 px-1 font-sans dark:border-neutral-700 dark:bg-neutral-800">S</kbd>
                to quick save
            </p>

            {#if activeTab === 'overview'}
                <form onsubmit={saveEdit} class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Title</label>
                            <input
                                type="text"
                                bind:value={editForm.title}
                                class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Description</label>
                            <textarea
                                bind:value={editForm.description}
                                rows="4"
                                placeholder="Add a description..."
                                class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                            ></textarea>
                            {#if task.description_np}
                                <p class="mt-2 text-sm whitespace-pre-wrap text-neutral-600 dark:text-neutral-400">{task.description_np}</p>
                            {/if}
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Status</label>
                                <select
                                    bind:value={editForm.status}
                                    class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                                >
                                    {#each statuses as s (s.value)}
                                        <option value={s.value}>{s.label}</option>
                                    {/each}
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 flex items-center justify-between text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    <span>Progress</span>
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{editForm.task_progress}%</span>
                                </label>
                                <input
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="5"
                                    bind:value={editForm.task_progress}
                                    class="mt-2.5 w-full accent-amber-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Due date</label>
                                <input
                                    type="date"
                                    bind:value={editForm.deadline_at}
                                    class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                                />
                            </div>
                        </div>
                        <div>
                            <span class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Priority</span>
                            <PillGroup
                                bind:value={editForm.priority}
                                options={[
                                    { value: 'low', label: 'Low' },
                                    { value: 'medium', label: 'Medium' },
                                    { value: 'high', label: 'High' },
                                    { value: 'urgent', label: 'Urgent' },
                                ]}
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Status note</label>
                            <textarea
                                bind:value={editForm.status_note}
                                rows="3"
                                placeholder="What's the latest on this?"
                                class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                            ></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-400">Source URL</label>
                            <input
                                type="url"
                                bind:value={editForm.source_url}
                                placeholder="https://..."
                                class="w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        {#if editForm.isDirty}
                            <span class="mr-auto text-xs text-neutral-500 dark:text-neutral-400">Unsaved changes</span>
                            <button
                                type="button"
                                onclick={() => editForm.reset()}
                                class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                                >Discard</button
                            >
                        {/if}
                        <button
                            type="submit"
                            disabled={editForm.processing || !editForm.isDirty}
                            title="Save (⌘S / Ctrl+S)"
                            class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                            >Save</button
                        >
                    </div>
                </form>
            {/if}

            {#if activeTab === 'todos'}
                <form onsubmit={addTodo} class="mb-4 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            bind:value={todoForm.body}
                            placeholder="Add a todo for yourself..."
                            class="flex-1 rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <input
                            type="date"
                            bind:value={todoForm.due_at}
                            class="rounded-md border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <button
                            type="submit"
                            disabled={todoForm.processing || !todoForm.body.trim()}
                            class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                            >Add</button
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">Todos are private to you. They show up in My Workspace too.</p>
                </form>

                <ul class="space-y-2">
                    {#each subtasks as t (t.id)}
                        <li
                            class="group flex items-start gap-3 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <button
                                type="button"
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-neutral-300 text-xs text-white transition hover:border-emerald-500 dark:border-neutral-600"
                                class:bg-emerald-500={t.is_done}
                                class:border-emerald-500={t.is_done}
                                onclick={() => toggleTodo(t)}>{t.is_done ? '✓' : ''}</button
                            >
                            <div class="min-w-0 flex-1 text-sm">
                                <p class:line-through={t.is_done} class:text-neutral-400={t.is_done}>{t.body}</p>
                                {#if t.due_at}
                                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">due {formatDate(t.due_at)}</p>
                                {/if}
                            </div>
                            <button
                                type="button"
                                class="invisible text-neutral-400 group-hover:visible hover:text-red-500"
                                onclick={() => deleteTodo(t)}
                                title="Delete">×</button
                            >
                        </li>
                    {:else}
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">No todos yet.</p>
                    {/each}
                </ul>
            {/if}

            {#if activeTab === 'notes'}
                <form onsubmit={addNote} class="mb-4 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                    <textarea
                        bind:value={noteForm.body}
                        rows="2"
                        placeholder="Add a note..."
                        class="w-full resize-none rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    ></textarea>
                    <div class="mt-2 flex items-center gap-2">
                        <select
                            bind:value={noteForm.type}
                            class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-xs dark:border-neutral-700 dark:bg-neutral-900"
                        >
                            <option value="general">General</option>
                            <option value="action_taken">Action taken</option>
                            <option value="meeting">Meeting</option>
                            <option value="blocker">Blocker</option>
                            <option value="milestone">Milestone</option>
                            <option value="decision">Decision</option>
                        </select>
                        <input
                            type="date"
                            bind:value={noteForm.happened_at}
                            class="rounded-md border border-neutral-300 bg-white px-2 py-1 text-xs dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <div class="flex-1"></div>
                        <button
                            type="submit"
                            disabled={noteForm.processing || !noteForm.body.trim()}
                            class="rounded-md bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                            >Add note</button
                        >
                    </div>
                </form>

                <div class="space-y-2">
                    {#each notes as note (note.id)}
                        <div class="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="mb-1 flex items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                                <span class="font-medium text-neutral-700 dark:text-neutral-300">{note.user?.name ?? 'Someone'}</span>
                                <span>· {note.type_label}</span>
                                {#if note.happened_at}<span>· {formatDate(note.happened_at)}</span>{/if}
                                <div class="flex-1"></div>
                                <button type="button" onclick={() => deleteNote(note)} class="text-neutral-400 hover:text-red-500" title="Delete note"
                                    >×</button
                                >
                            </div>
                            <p class="text-sm whitespace-pre-wrap">{note.body}</p>
                        </div>
                    {:else}
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">No notes yet.</p>
                    {/each}
                </div>
            {/if}

            {#if activeTab === 'contacts'}
                <form
                    onsubmit={addContact}
                    class="mb-4 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <input
                            type="text"
                            placeholder="Name *"
                            bind:value={contactForm.name}
                            required
                            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <input
                            type="text"
                            placeholder="Role"
                            bind:value={contactForm.role}
                            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <input
                            type="text"
                            placeholder="Organization"
                            bind:value={contactForm.organization}
                            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <input
                            type="email"
                            placeholder="Email"
                            bind:value={contactForm.email}
                            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <input
                            type="tel"
                            placeholder="Phone"
                            bind:value={contactForm.phone}
                            class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                    </div>
                    <div class="mt-2 flex justify-end">
                        <button
                            type="submit"
                            disabled={contactForm.processing || !contactForm.name.trim()}
                            class="rounded-md bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                            >Add contact</button
                        >
                    </div>
                </form>

                <div class="space-y-2">
                    {#each contacts as contact (contact.id)}
                        <div class="rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="text-sm font-medium">{contact.name}</div>
                            {#if contact.role || contact.organization}
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {[contact.role, contact.organization].filter(Boolean).join(' · ')}
                                </div>
                            {/if}
                            <div class="mt-1 flex flex-wrap gap-3 text-xs text-neutral-600 dark:text-neutral-400">
                                {#if contact.email}<a href={`mailto:${contact.email}`} class="hover:underline">✉ {contact.email}</a>{/if}
                                {#if contact.phone}<span>☎ {contact.phone}</span>{/if}
                            </div>
                        </div>
                    {:else}
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">No contacts yet.</p>
                    {/each}
                </div>
            {/if}
        </div>

        <aside class="space-y-4">
            <section class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                    Assignees ({task.assignments?.length ?? 0})
                </h3>
                <div class="space-y-2">
                    {#each task.assignments ?? [] as a (a.id)}
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200"
                            >
                                {initials(a.user?.name)}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{a.user?.name}</div>
                                <div class="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    {a.personal_progress}%
                                </div>
                            </div>
                            <button type="button" onclick={() => unassign(a)} class="text-neutral-400 hover:text-red-500" title="Unassign">×</button>
                        </div>
                    {:else}
                        <p class="text-sm italic text-neutral-500 dark:text-neutral-400">No one assigned yet.</p>
                    {/each}
                </div>
                <div class="mt-3 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                    <AssigneePicker {team} bind:selectedIds={pickerSelected} placeholder="Add assignee..." />
                </div>
            </section>

            {#if task.category_label || task.deadline_label || task.responsible_ministry}
                <section class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">Plan metadata</h3>
                    <dl class="space-y-2 text-sm">
                        {#if task.category_label}
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500 dark:text-neutral-400">Category</dt>
                                <dd>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        style="background-color: {task.category_color}15; color: {task.category_color}; --tw-ring-color: {task.category_color}40;"
                                        >{task.category_label}</span
                                    >
                                </dd>
                            </div>
                        {/if}
                        {#if task.deadline_label}
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500 dark:text-neutral-400">Deadline type</dt>
                                <dd class="text-neutral-800 dark:text-neutral-200">{task.deadline_label}</dd>
                            </div>
                        {/if}
                        {#if task.responsible_ministry}
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500 dark:text-neutral-400">Ministry</dt>
                                <dd class="text-neutral-800 dark:text-neutral-200">{task.responsible_ministry}</dd>
                            </div>
                        {/if}
                    </dl>
                </section>
            {/if}
        </aside>
    </div>
</AppShell>
