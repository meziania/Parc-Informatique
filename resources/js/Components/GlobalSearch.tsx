import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type SearchHit = {
    id: number;
    title?: string;
    name?: string;
    number?: string;
    inventory_number?: string | null;
    status_label?: string;
    category_label?: string;
    url: string;
};

type SearchResult = {
    q: string;
    tickets: SearchHit[];
    assets: SearchHit[];
    faq: SearchHit[];
};

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export default function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<SearchResult | null>(null);
    const rootRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        const onPointerDown = (event: MouseEvent) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, []);

    useEffect(() => {
        const trimmed = query.trim();
        if (trimmed.length < 2) {
            setResults(null);
            setLoading(false);
            abortRef.current?.abort();

            return;
        }

        const timer = window.setTimeout(async () => {
            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;
            setLoading(true);
            setOpen(true);

            try {
                const response = await fetch(
                    `${route('search')}?q=${encodeURIComponent(trimmed)}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        signal: controller.signal,
                        credentials: 'same-origin',
                    },
                );

                if (!response.ok) {
                    setResults(null);

                    return;
                }

                setResults((await response.json()) as SearchResult);
            } catch (error) {
                if ((error as Error).name !== 'AbortError') {
                    setResults(null);
                }
            } finally {
                setLoading(false);
            }
        }, 250);

        return () => window.clearTimeout(timer);
    }, [query]);

    const go = (url: string) => {
        setOpen(false);
        setQuery('');
        router.visit(url);
    };

    const hasHits =
        !!results &&
        (results.tickets.length > 0 ||
            results.assets.length > 0 ||
            results.faq.length > 0);

    return (
        <div ref={rootRef} className="relative hidden min-w-0 flex-1 md:block">
            <label className="sr-only" htmlFor="global-search">
                Recherche globale
            </label>
            <input
                id="global-search"
                type="search"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onFocus={() => {
                    if (query.trim().length >= 2) {
                        setOpen(true);
                    }
                }}
                placeholder="Rechercher tickets, parc, FAQ…"
                className="ui-input w-full max-w-md"
                autoComplete="off"
            />

            {open && query.trim().length >= 2 && (
                <div className="absolute left-0 right-0 z-30 mt-2 max-h-96 overflow-auto rounded-panel border border-line bg-white p-2 shadow-panel sm:right-auto sm:w-[28rem]">
                    {loading && (
                        <p className="px-2 py-3 text-sm text-ink-muted">
                            Recherche…
                        </p>
                    )}

                    {!loading && results && !hasHits && (
                        <p className="px-2 py-3 text-sm text-ink-muted">
                            Aucun résultat pour « {results.q} ».
                        </p>
                    )}

                    {!loading && results && hasHits && (
                        <div className="space-y-3">
                            <ResultGroup
                                title="Tickets"
                                items={results.tickets}
                                label={(item) =>
                                    `${item.number} — ${item.title}`
                                }
                                meta={(item) => item.status_label}
                                onSelect={go}
                            />
                            <ResultGroup
                                title="Parc"
                                items={results.assets}
                                label={(item) =>
                                    `${item.name}${item.inventory_number ? ` (${item.inventory_number})` : ''}`
                                }
                                meta={(item) => item.status_label}
                                onSelect={go}
                            />
                            <ResultGroup
                                title="FAQ"
                                items={results.faq}
                                label={(item) => item.title ?? ''}
                                meta={(item) => item.category_label}
                                onSelect={go}
                            />
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function ResultGroup({
    title,
    items,
    label,
    meta,
    onSelect,
}: {
    title: string;
    items: SearchHit[];
    label: (item: SearchHit) => string;
    meta?: (item: SearchHit) => string | undefined;
    onSelect: (url: string) => void;
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            <div className="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                {title}
            </div>
            <ul className="space-y-0.5">
                {items.map((item) => (
                    <li key={`${title}-${item.id}`}>
                        <button
                            type="button"
                            onClick={() => onSelect(item.url)}
                            className="flex w-full items-start justify-between gap-3 rounded-lg px-2 py-2 text-left text-sm hover:bg-surface-muted ui-focus"
                        >
                            <span className="min-w-0 truncate text-ink">
                                {label(item)}
                            </span>
                            {meta?.(item) && (
                                <span className="shrink-0 text-xs text-ink-muted">
                                    {meta(item)}
                                </span>
                            )}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
