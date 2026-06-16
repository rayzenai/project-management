/**
 * Applies a server-resolved theme to the document by writing its token values
 * onto CSS custom properties the stylesheets already consume.
 *
 * Two var families are written so the theme actually takes visual effect:
 *  - the portfolio "terminal-noir" vars (`--color-ink`, `--color-signal`, …)
 *  - the Tailwind palette scale vars the workspace components resolve through
 *    (`--color-neutral-*`, `--color-amber-*`), which `workspace.css` remaps
 *    under `.dark`. Setting them on the root lets any chosen theme drive the
 *    same utility classes (`bg-neutral-900`, `text-amber-400`, …).
 */

type Tokens = {
    color: Record<string, string>;
    font: { display: string; body: string; mono: string };
};

/** token key → portfolio design-system CSS variable */
const COLOR_VAR: Record<string, string> = {
    bg: '--color-ink',
    surface: '--color-panel',
    surfaceAlt: '--color-panel-2',
    line: '--color-line',
    lineSoft: '--color-line-soft',
    text: '--color-bone',
    textMuted: '--color-mute',
    textFaint: '--color-faint',
    accent: '--color-signal',
    accentDim: '--color-signal-dim',
    warn: '--color-amber',
    danger: '--color-danger',
    success: '--color-success',
};

/**
 * token key → list of Tailwind palette scale vars the workspace utilities use.
 * Mirrors the `.dark` remap in `workspace.css` so a chosen theme drives every
 * `neutral-*` / `amber-*` utility class without touching component markup.
 */
const SCALE_VAR: Record<string, string[]> = {
    bg: ['--color-neutral-950'],
    surface: ['--color-neutral-900'],
    line: ['--color-neutral-800'],
    textFaint: ['--color-neutral-600'],
    textMuted: ['--color-neutral-400'],
    text: ['--color-neutral-100'],
    accent: ['--color-amber-400', '--color-amber-500'],
    accentDim: ['--color-amber-600', '--color-amber-700'],
};

export function applyTheme(tokens: Tokens, mode: 'light' | 'dark'): void {
    const root = document.documentElement;

    for (const [key, value] of Object.entries(tokens.color)) {
        const cssVar = COLOR_VAR[key];
        if (cssVar) {
            root.style.setProperty(cssVar, value);
        }
        for (const scaleVar of SCALE_VAR[key] ?? []) {
            root.style.setProperty(scaleVar, value);
        }
    }

    root.style.setProperty('--font-display', tokens.font.display);
    root.style.setProperty('--font-sans', tokens.font.body);
    root.style.setProperty('--font-mono', tokens.font.mono);

    root.style.colorScheme = mode;
    root.dataset.theme = mode;
    root.classList.toggle('dark', mode === 'dark');
}

type SystemTokens = { light: Tokens; dark: Tokens };

export type Appearance = {
    theme: string;
    mode: 'light' | 'dark' | null;
    tokens: Tokens | SystemTokens;
};

/**
 * Applies the shared `appearance` prop. For `system` the OS scheme decides the
 * mode (and a listener re-applies on OS scheme changes); for a concrete theme
 * the server-provided `mode` is authoritative.
 */
export function applyAppearance(appearance: Appearance | null | undefined): void {
    if (!appearance) {
        return;
    }

    if (appearance.theme === 'system') {
        const sys = appearance.tokens as SystemTokens;
        const query = window.matchMedia('(prefers-color-scheme: dark)');
        const render = (dark: boolean): void => applyTheme(dark ? sys.dark : sys.light, dark ? 'dark' : 'light');

        render(query.matches);
        query.addEventListener('change', (event) => render(event.matches));

        return;
    }

    const mode = appearance.mode ?? 'dark';
    applyTheme(appearance.tokens as Tokens, mode);
}
