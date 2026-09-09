import { Link } from '@inertiajs/react';

export default function StatCard({
    label,
    value,
    href,
    tone = 'default',
}: {
    label: string;
    value: number | string;
    href?: string;
    tone?: 'default' | 'warn' | 'danger' | 'ok';
}) {
    const tones = {
        default: 'border-line bg-surface',
        warn: 'border-orange-200 bg-orange-50',
        danger: 'border-red-200 bg-red-50',
        ok: 'border-teal-200 bg-brand-soft/60',
    };

    const content = (
        <>
            <div className="text-sm font-medium text-ink-muted">{label}</div>
            <div className="mt-2 font-display text-3xl font-semibold text-ink">
                {value}
            </div>
        </>
    );

    const className = `block rounded-panel border p-5 shadow-panel transition hover:border-brand/40 ${tones[tone]}`;

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return <div className={className}>{content}</div>;
}
