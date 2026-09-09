import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

export default function EmptyState({
    title,
    description,
    actionLabel,
    actionHref,
    children,
}: {
    title: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
    children?: ReactNode;
}) {
    return (
        <div className="px-6 py-12 text-center">
            <p className="font-display text-base font-semibold text-ink">{title}</p>
            {description && (
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-muted">
                    {description}
                </p>
            )}
            {actionHref && actionLabel && (
                <Link
                    href={actionHref}
                    className="mt-4 inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong"
                >
                    {actionLabel}
                </Link>
            )}
            {children}
        </div>
    );
}
