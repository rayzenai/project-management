export function formatDate(value: string | null | undefined): string {
    if (!value) return '';
    const d = new Date(value);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

export function formatRelative(value: string | null | undefined): string {
    if (!value) return '';
    const target = new Date(value).getTime();
    const now = Date.now();
    const diffMs = target - now;
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'today';
    if (diffDays === 1) return 'tomorrow';
    if (diffDays === -1) return 'yesterday';
    if (diffDays > 0) return `in ${diffDays}d`;
    return `${Math.abs(diffDays)}d overdue`;
}

export function initials(name: string | null | undefined): string {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function priorityColor(priority: string | null | undefined): string {
    switch (priority) {
        case 'urgent':
            return 'bg-red-100 text-red-800 ring-red-200 dark:bg-red-500/15 dark:text-red-400 dark:ring-red-500/30';
        case 'high':
            return 'bg-orange-100 text-orange-800 ring-orange-200 dark:bg-orange-500/15 dark:text-orange-400 dark:ring-orange-500/30';
        case 'medium':
            return 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:ring-amber-500/30';
        case 'low':
            return 'bg-neutral-100 text-neutral-700 ring-neutral-200 dark:bg-neutral-700/40 dark:text-neutral-300 dark:ring-neutral-600';
        default:
            return 'bg-neutral-100 text-neutral-700 ring-neutral-200 dark:bg-neutral-700/40 dark:text-neutral-300 dark:ring-neutral-600';
    }
}
