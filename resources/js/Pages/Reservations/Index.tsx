import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option, Paginated, Reservation } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    reservations: Paginated<Reservation>;
    filters: { status?: string; scope?: string };
    statuses: Option[];
    canManage: boolean;
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function Index({
    reservations,
    filters,
    statuses,
    canManage,
}: Props) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [scope, setScope] = useState(filters.scope ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('reservations.index'),
            {
                status: status || undefined,
                scope: scope || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setStatus('');
        setScope('');
        router.get(route('reservations.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Réservations"
                    description={
                        canManage
                            ? 'Demandes de matériel et de salles — validation IT.'
                            : 'Réservez un portable, un projecteur ou une salle.'
                    }
                    actions={
                        <Link
                            href={route('reservations.create')}
                            className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                        >
                            Nouvelle réservation
                        </Link>
                    }
                />
            }
        >
            <Head title="Réservations" />

            <div className="mx-auto max-w-6xl space-y-6">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="ui-input min-w-40"
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={scope}
                        onChange={(e) => setScope(e.target.value)}
                        className="ui-input min-w-40"
                    >
                        <option value="">Toutes les dates</option>
                        <option value="upcoming">À venir</option>
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {reservations.data.length === 0 ? (
                        <EmptyState
                            title="Aucune réservation"
                            description="Créez une demande pour réserver un équipement ou un lieu."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Titre
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Ressource
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Créneau
                                        </th>
                                        {canManage && (
                                            <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                Demandeur
                                            </th>
                                        )}
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Statut
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {reservations.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'reservations.show',
                                                        item.id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    {item.title}
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {item.resource_label}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {formatDateTime(item.starts_at)}
                                                <span className="text-ink-muted">
                                                    {' '}
                                                    →{' '}
                                                </span>
                                                {formatDateTime(item.ends_at)}
                                            </td>
                                            {canManage && (
                                                <td className="px-6 py-4 text-sm text-ink">
                                                    {item.user?.name ?? '—'}
                                                </td>
                                            )}
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={item.status_label}
                                                    color={item.status_color}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={reservations.links} />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
