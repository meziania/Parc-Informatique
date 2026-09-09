import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option, Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface AuditActor {
    id: number;
    name: string;
    email: string;
}

export interface AuditLogRow {
    id: number;
    action: string;
    action_label: string;
    actor: AuditActor | null;
    subject_type: string | null;
    subject_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip: string | null;
    created_at: string | null;
}

interface Props {
    logs: Paginated<AuditLogRow>;
    filters: { action?: string | null; actor_id?: number | null };
    actions: Option[];
    actors: Option[];
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('fr-FR', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

function formatValues(values: Record<string, unknown> | null): string {
    if (!values || Object.keys(values).length === 0) {
        return '—';
    }

    return Object.entries(values)
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(', ');
}

export default function Index({ logs, filters, actions, actors }: Props) {
    const [action, setAction] = useState(filters.action ?? '');
    const [actorId, setActorId] = useState(
        filters.actor_id ? String(filters.actor_id) : '',
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('admin.audit-logs.index'),
            {
                action: action || undefined,
                actor_id: actorId || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setAction('');
        setActorId('');
        router.get(route('admin.audit-logs.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Audit"
                    description="Historique des actions d'administration."
                />
            }
        >
            <Head title="Audit" />

            <div className="mx-auto max-w-7xl">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-ink-muted">
                            Action
                        </label>
                        <select
                            value={action}
                            onChange={(e) => setAction(e.target.value)}
                            className="rounded-lg border-line text-sm ui-focus"
                        >
                            <option value="">Toutes</option>
                            {actions.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-ink-muted">
                            Acteur
                        </label>
                        <select
                            value={actorId}
                            onChange={(e) => setActorId(e.target.value)}
                            className="rounded-lg border-line text-sm ui-focus"
                        >
                            <option value="">Tous</option>
                            {actors.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {logs.data.length === 0 ? (
                        <EmptyState
                            title="Aucun événement"
                            description="Les changements de rôle et autres actions admin apparaîtront ici."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Action
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Acteur
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Cible
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Avant → Après
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            IP
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {logs.data.map((log) => (
                                        <tr
                                            key={log.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-ink-muted">
                                                {formatDate(log.created_at)}
                                            </td>
                                            <td className="px-4 py-3 text-sm font-medium text-ink">
                                                {log.action_label}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-ink">
                                                {log.actor ? (
                                                    <div>
                                                        <div>{log.actor.name}</div>
                                                        <div className="text-xs text-ink-muted">
                                                            {log.actor.email}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-ink-muted">
                                                {log.subject_type
                                                    ? `${log.subject_type} #${log.subject_id}`
                                                    : '—'}
                                            </td>
                                            <td className="max-w-xs px-4 py-3 text-sm text-ink-muted">
                                                <span className="line-through">
                                                    {formatValues(log.old_values)}
                                                </span>
                                                <span className="mx-1 text-ink">
                                                    →
                                                </span>
                                                <span className="text-ink">
                                                    {formatValues(log.new_values)}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-xs text-ink-muted">
                                                {log.ip ?? '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Surface>

                <div className="mt-6">
                    <Pagination links={logs.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
