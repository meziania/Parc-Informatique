import { FormEvent, PropsWithChildren } from 'react';

export default function FilterBar({
    onSubmit,
    onReset,
    children,
}: PropsWithChildren<{
    onSubmit: (e: FormEvent) => void;
    onReset?: () => void;
}>) {
    return (
        <form
            onSubmit={onSubmit}
            className="mb-6 flex flex-wrap items-end gap-3 rounded-panel border border-line/80 bg-surface p-4 shadow-panel"
        >
            {children}
            <button
                type="submit"
                className="inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
            >
                Filtrer
            </button>
            {onReset && (
                <button
                    type="button"
                    onClick={onReset}
                    className="text-sm text-ink-muted underline decoration-line underline-offset-2 hover:text-ink"
                >
                    Réinitialiser
                </button>
            )}
        </form>
    );
}
