import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option, Paginated, PageProps, Ticket } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    tickets: Paginated<Ticket>;
    filters: {
        search?: string;
        type?: string;
        priority?: string;
        status?: string;
        sla?: string;
    };
    types: Option[];
    priorities: Option[];
    statuses: Option[];
    slaFilters: Option[];
}

const selectClass = 'rounded-lg border-line text-sm ui-focus';

export default function Index({
    tickets,
    filters,
    types,
    priorities,
    statuses,
    slaFilters,
}: Props) {
    const { auth } = usePage<PageProps>().props;
    const isTechnician = auth.user.role !== 'user';
    const title = isTechnician ? 'Tickets' : 'Mes tickets';

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [priority, setPriority] = useState(filters.priority ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [sla, setSla] = useState(filters.sla ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('tickets.index'),
            {
                search: search || undefined,
                type: type || undefined,
                priority: priority || undefined,
                status: status || undefined,
                sla: sla || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setType('');
        setPriority('');
        setStatus('');
        setSla('');
        router.get(route('tickets.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description={
                        isTechnician
                            ? "File d'attente, priorités et suivi des demandes."
                            : 'Suivez vos demandes et incidents ouverts.'
                    }
                    actions={
                        <>
                            <a
                                href={route('tickets.export', {
                                    search: search || undefined,
                                    type: type || undefined,
                                    priority: priority || undefined,
                                    status: status || undefined,
                                    sla: sla || undefined,
                                })}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Exporter CSV
                            </a>
                            <Link
                                href={route('tickets.create')}
                                className="inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouveau ticket
                            </Link>
                        </>
                    }
                />
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-7xl">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <div className="min-w-52 grow">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={
                                isTechnician
                                    ? 'Rechercher (titre, n°, demandeur)…'
                                    : 'Rechercher (titre, numéro)…'
                            }
                            className="w-full"
                        />
                    </div>
                    <select
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Tous les types</option>
                        {types.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={priority}
                        onChange={(e) => setPriority(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Toutes les priorités</option>
                        {priorities.map((p) => (
                            <option key={p.value} value={p.value}>
                                {p.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={sla}
                        onChange={(e) => setSla(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Tous les SLA</option>
                        {slaFilters.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {tickets.data.length === 0 ? (
                        <EmptyState
                            title="Aucun ticket trouvé"
                            description="Ajustez les filtres ou créez une nouvelle demande."
                            actionLabel="Créer un ticket"
                            actionHref={route('tickets.create')}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Ticket
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Priorité
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            SLA
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Équipement
                                        </th>
                                        {isTechnician && (
                                            <>
                                                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                    Demandeur
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                    Technicien
                                                </th>
                                            </>
                                        )}
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Créé le
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {tickets.data.map((ticket) => (
                                        <tr
                                            key={ticket.id}
                                            className="transition hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'tickets.show',
                                                        ticket.id,
                                                    )}
                                                    className="font-medium text-brand-strong hover:text-brand"
                                                >
                                                    {ticket.title}
                                                </Link>
                                                <div className="text-xs text-ink-muted">
                                                    {ticket.number} ·{' '}
                                                    {ticket.type_label}
                                                </div>
                                                {isTechnician && (
                                                    <div className="mt-1 text-xs font-medium text-ink">
                                                        Demandeur :{' '}
                                                        {ticket.requester.name}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={ticket.priority_label}
                                                    color={ticket.priority_color}
                                                />
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={ticket.status_label}
                                                    color={ticket.status_color}
                                                />
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={ticket.sla_label}
                                                    color={ticket.sla_color}
                                                />
                                                {ticket.due_at && (
                                                    <div className="mt-1 text-xs text-ink-muted">
                                                        Échéance{' '}
                                                        {new Date(
                                                            ticket.due_at,
                                                        ).toLocaleString(
                                                            'fr-FR',
                                                            {
                                                                day: 'numeric',
                                                                month: 'short',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {ticket.asset?.name ?? '—'}
                                            </td>
                                            {isTechnician && (
                                                <>
                                                    <td className="px-6 py-4 text-sm text-ink">
                                                        <div className="font-medium">
                                                            {ticket.requester.name}
                                                        </div>
                                                        {ticket.requester.email && (
                                                            <div className="mt-0.5 text-xs text-ink-muted">
                                                                {
                                                                    ticket
                                                                        .requester
                                                                        .email
                                                                }
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-ink">
                                                        {ticket.assignee
                                                            ?.name ?? (
                                                            <span className="text-ink-muted">
                                                                Non assigné
                                                            </span>
                                                        )}
                                                    </td>
                                                </>
                                            )}
                                            <td className="px-6 py-4 text-sm text-ink-muted">
                                                {new Date(
                                                    ticket.created_at,
                                                ).toLocaleDateString('fr-FR')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Surface>

                <Pagination links={tickets.links} />
            </div>
        </AuthenticatedLayout>
    );
}
