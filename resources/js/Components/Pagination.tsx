import { PaginationLink } from '@/types';
import { Link } from '@inertiajs/react';

export default function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-4 flex flex-wrap gap-1">
            {links.map((link, index) => {
                const className = `rounded-lg px-3 py-1.5 text-sm ${
                    link.active
                        ? 'bg-brand font-semibold text-white'
                        : link.url
                          ? 'bg-surface text-ink hover:bg-surface-muted'
                          : 'cursor-not-allowed bg-surface-muted text-ink-muted/50'
                } border border-line`;

                return link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={className}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={index}
                        className={className}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </nav>
    );
}
