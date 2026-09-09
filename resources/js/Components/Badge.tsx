const colors: Record<string, string> = {
    green: 'bg-emerald-100 text-emerald-800',
    blue: 'bg-sky-100 text-sky-800',
    red: 'bg-red-100 text-red-800',
    gray: 'bg-slate-100 text-slate-700',
    orange: 'bg-orange-100 text-orange-800',
    purple: 'bg-teal-100 text-teal-900',
};

export default function Badge({
    label,
    color,
}: {
    label: string;
    color: string;
}) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${colors[color] ?? colors.gray}`}
        >
            {label}
        </span>
    );
}
