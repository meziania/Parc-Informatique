import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import StatCard from '@/Components/StatCard';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { roleLabels } from '@/lib/roles';
import {
    DashboardAsset,
    DashboardTicket,
    PageProps,
    TechnicianStats,
    UserStats,
} from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface TechnicianProps {
    view: 'technician';
    stats: TechnicianStats;
    recent_tickets: DashboardTicket[];
    overdue_tickets: DashboardTicket[];
    urgent_tickets: DashboardTicket[];
    broken_assets: DashboardAsset[];
    maintenance_assets: DashboardAsset[];
    my_tickets: DashboardTicket[];
}

interface UserViewProps {
    view: 'user';
    stats: UserStats;
    recent_tickets: DashboardTicket[];
    my_assets: DashboardAsset[];
}

type Props = TechnicianProps | UserViewProps;

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
}

function SectionTitle({
    title,
    href,
}: {
    title: string;
    href?: string;
}) {
    return (
        <div className="mb-3 flex items-center justify-between gap-3">
            <h3 className="font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                {title}
            </h3>
            {href && (
                <Link
                    href={href}
                    className="text-sm font-medium text-brand hover:text-brand-strong"
                >
                    Voir tout
                </Link>
            )}
        </div>
    );
}

function TicketRow({
    ticket,
    showRequester = false,
}: {
    ticket: DashboardTicket;
    showRequester?: boolean;
}) {
    return (
        <li className="flex items-start justify-between gap-3 border-b border-line/70 py-3 last:border-0">
            <div className="min-w-0">
                <Link
                    href={route('tickets.show', ticket.id)}
                    className="truncate font-medium text-brand hover:text-brand-strong"
                >
                    {ticket.title}
                </Link>
                <div className="mt-0.5 text-xs text-ink-muted">
                    {ticket.number} · {ticket.type_label}
                    {showRequester && ticket.requester
                        ? ` · Demandeur : ${ticket.requester.name}`
                        : ''}
                    {ticket.asset ? ` · ${ticket.asset.name}` : ''}
                </div>
            </div>
            <div className="flex shrink-0 flex-col items-end gap-1">
                <Badge
                    label={ticket.status_label}
                    color={ticket.status_color}
                />
                <Badge
                    label={ticket.sla_label}
                    color={ticket.sla_color}
                />
                <span className="text-xs text-ink-muted">
                    {ticket.due_at
                        ? `Échéance ${formatDate(ticket.due_at)}`
                        : formatDate(ticket.created_at)}
                </span>
            </div>
        </li>
    );
}

function TechnicianDashboard({
    stats,
    recent_tickets,
    overdue_tickets,
    urgent_tickets,
    broken_assets,
    maintenance_assets,
    my_tickets,
}: TechnicianProps) {
    return (
        <>
            <div className="mb-4 flex flex-wrap items-center justify-end gap-3">
                <Link
                    href={route('analytics.index')}
                    className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                >
                    Analytics
                </Link>
                <a
                    href={route('reports.weekly')}
                    className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                >
                    Rapport PDF (7 j)
                </a>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <StatCard
                    label="Tickets ouverts"
                    value={stats.open_tickets}
                    href={route('tickets.index')}
                />
                <StatCard
                    label="Non assignés"
                    value={stats.unassigned_tickets}
                    href={route('tickets.index', { status: 'new' })}
                    tone={stats.unassigned_tickets > 0 ? 'warn' : 'default'}
                />
                <StatCard
                    label="SLA dépassé"
                    value={stats.overdue_tickets}
                    href={route('tickets.index', { sla: 'breached' })}
                    tone={stats.overdue_tickets > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="SLA à risque"
                    value={stats.at_risk_tickets}
                    href={route('tickets.index', { sla: 'at_risk' })}
                    tone={stats.at_risk_tickets > 0 ? 'warn' : 'default'}
                />
                <StatCard
                    label="Urgents ouverts"
                    value={stats.urgent_tickets}
                    href={route('tickets.index', { priority: 'urgent' })}
                    tone={stats.urgent_tickets > 0 ? 'danger' : 'default'}
                />
                <StatCard
                    label="Mes tickets en cours"
                    value={stats.my_tickets}
                    href={route('tickets.index')}
                />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Équipements en panne"
                    value={stats.broken_assets}
                    href={route('assets.index', { status: 'broken' })}
                    tone={stats.broken_assets > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="Parc total"
                    value={stats.assets_total}
                    href={route('assets.index')}
                />
                <StatCard
                    label="En stock"
                    value={stats.assets_in_stock}
                    href={route('assets.index', { status: 'in_stock' })}
                />
                <StatCard
                    label={
                        stats.satisfaction_count > 0
                            ? `Satisfaction moy. (${stats.satisfaction_count})`
                            : 'Satisfaction moy.'
                    }
                    value={
                        stats.satisfaction_count > 0
                            ? stats.avg_satisfaction
                            : '—'
                    }
                    tone={
                        stats.awaiting_satisfaction > 0 ? 'warn' : 'ok'
                    }
                />
            </div>
            {stats.awaiting_satisfaction > 0 && (
                <p className="mt-2 text-sm text-ink-muted">
                    {stats.awaiting_satisfaction} ticket(s) résolu(s) en
                    attente d’avis utilisateur.
                </p>
            )}

            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <StatCard
                    label="SLA respecté (30 j)"
                    value={
                        stats.sla_met_rate_30d != null
                            ? `${stats.sla_met_rate_30d} %`
                            : '—'
                    }
                    tone={
                        stats.sla_met_rate_30d == null
                            ? 'default'
                            : stats.sla_met_rate_30d >= 80
                              ? 'ok'
                              : stats.sla_met_rate_30d >= 60
                                ? 'warn'
                                : 'danger'
                    }
                />
                <StatCard
                    label={
                        stats.satisfaction_count_30d > 0
                            ? `Satisfaction 30 j (${stats.satisfaction_count_30d})`
                            : 'Satisfaction 30 j'
                    }
                    value={
                        stats.avg_satisfaction_30d != null
                            ? stats.avg_satisfaction_30d
                            : '—'
                    }
                    tone="ok"
                />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Garantie expirée"
                    value={stats.warranty_expired}
                    href={route('assets.index')}
                    tone={stats.warranty_expired > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="Garantie sous 30 j"
                    value={stats.warranty_expiring_30d}
                    href={route('assets.index')}
                    tone={stats.warranty_expiring_30d > 0 ? 'warn' : 'default'}
                />
                <StatCard
                    label="Maintenance due"
                    value={stats.maintenance_due}
                    href={route('assets.index')}
                    tone={stats.maintenance_due > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="Maintenance sous 30 j"
                    value={stats.maintenance_soon}
                    href={route('assets.index')}
                    tone={stats.maintenance_soon > 0 ? 'warn' : 'default'}
                />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    label="Licences expirées"
                    value={stats.licenses_expired}
                    href={route('licenses.index', { expiry: 'expired' })}
                    tone={stats.licenses_expired > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="Licences sous 30 j"
                    value={stats.licenses_expiring_30d}
                    href={route('licenses.index', { expiry: 'expiring' })}
                    tone={
                        stats.licenses_expiring_30d > 0 ? 'warn' : 'default'
                    }
                />
                <StatCard
                    label="Réservations en attente"
                    value={stats.pending_reservations}
                    href={route('reservations.index', { status: 'pending' })}
                    tone={
                        stats.pending_reservations > 0 ? 'warn' : 'default'
                    }
                />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Surface>
                    <SectionTitle
                        title="File d'attente"
                        href={route('tickets.index')}
                    />
                    {recent_tickets.length === 0 ? (
                        <EmptyState title="Aucun ticket ouvert." />
                    ) : (
                        <ul>
                            {recent_tickets.map((ticket) => (
                                <TicketRow
                                    key={ticket.id}
                                    ticket={ticket}
                                    showRequester
                                />
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle title="Assignés à moi" />
                    {my_tickets.length === 0 ? (
                        <EmptyState title="Aucun ticket qui vous est assigné." />
                    ) : (
                        <ul>
                            {my_tickets.map((ticket) => (
                                <TicketRow
                                    key={ticket.id}
                                    ticket={ticket}
                                    showRequester
                                />
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle
                        title="SLA dépassé"
                        href={route('tickets.index', { sla: 'breached' })}
                    />
                    {overdue_tickets.length === 0 ? (
                        <EmptyState title="Aucun ticket en retard SLA." />
                    ) : (
                        <ul>
                            {overdue_tickets.map((ticket) => (
                                <TicketRow
                                    key={ticket.id}
                                    ticket={ticket}
                                    showRequester
                                />
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle
                        title="Urgents"
                        href={route('tickets.index', { priority: 'urgent' })}
                    />
                    {urgent_tickets.length === 0 ? (
                        <EmptyState title="Aucun ticket urgent ouvert." />
                    ) : (
                        <ul>
                            {urgent_tickets.map((ticket) => (
                                <TicketRow
                                    key={ticket.id}
                                    ticket={ticket}
                                    showRequester
                                />
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle
                        title="Équipements en panne"
                        href={route('assets.index', { status: 'broken' })}
                    />
                    {broken_assets.length === 0 ? (
                        <EmptyState title="Aucun équipement en panne." />
                    ) : (
                        <ul>
                            {broken_assets.map((asset) => (
                                <li
                                    key={asset.id}
                                    className="flex items-center justify-between border-b border-line/70 py-3 last:border-0"
                                >
                                    <div>
                                        <Link
                                            href={route('assets.show', asset.id)}
                                            className="font-medium text-brand hover:text-brand-strong"
                                        >
                                            {asset.name}
                                        </Link>
                                        <div className="text-xs text-ink-muted">
                                            {asset.inventory_number}
                                            {asset.user
                                                ? ` · ${asset.user.name}`
                                                : ''}
                                            {asset.location
                                                ? ` · ${asset.location.name}`
                                                : ''}
                                        </div>
                                    </div>
                                    <Badge label="En panne" color="red" />
                                </li>
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle title="Maintenance / garanties" />
                    {maintenance_assets.length === 0 ? (
                        <EmptyState title="Aucune alerte maintenance ou garantie." />
                    ) : (
                        <ul>
                            {maintenance_assets.map((asset) => (
                                <li
                                    key={asset.id}
                                    className="flex items-center justify-between gap-3 border-b border-line/70 py-3 last:border-0"
                                >
                                    <div className="min-w-0">
                                        <Link
                                            href={route('assets.show', asset.id)}
                                            className="font-medium text-brand hover:text-brand-strong"
                                        >
                                            {asset.name}
                                        </Link>
                                        <div className="text-xs text-ink-muted">
                                            {asset.inventory_number}
                                            {asset.warranty_end
                                                ? ` · Garantie ${formatDate(asset.warranty_end)}`
                                                : ''}
                                            {asset.next_maintenance_at
                                                ? ` · Maint. ${formatDate(asset.next_maintenance_at)}`
                                                : ''}
                                        </div>
                                    </div>
                                    <Badge
                                        label={
                                            asset.maintenance_label ??
                                            'À surveiller'
                                        }
                                        color="orange"
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </Surface>
            </div>
        </>
    );
}

function UserDashboard({ stats, recent_tickets, my_assets }: UserViewProps) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    label="Mes tickets ouverts"
                    value={stats.open_tickets}
                    href={route('tickets.index')}
                />
                <StatCard
                    label="SLA dépassé"
                    value={stats.overdue_tickets}
                    href={route('tickets.index', { sla: 'breached' })}
                    tone={stats.overdue_tickets > 0 ? 'danger' : 'ok'}
                />
                <StatCard
                    label="À clôturer"
                    value={stats.resolved_tickets}
                    href={route('tickets.index', { status: 'resolved' })}
                    tone={stats.resolved_tickets > 0 ? 'ok' : 'default'}
                />
                <StatCard
                    label="Avis à donner"
                    value={stats.pending_satisfaction}
                    href={route('tickets.index', { status: 'closed' })}
                    tone={
                        stats.pending_satisfaction > 0 ? 'warn' : 'default'
                    }
                />
                <StatCard
                    label="Mes équipements"
                    value={stats.my_assets}
                    href={route('assets.index')}
                />
                <StatCard
                    label="Mes réservations"
                    value={stats.my_reservations}
                    href={route('reservations.index', { scope: 'upcoming' })}
                    tone={stats.my_reservations > 0 ? 'warn' : 'default'}
                />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Surface>
                    <SectionTitle
                        title="Mes tickets récents"
                        href={route('tickets.index')}
                    />
                    {recent_tickets.length === 0 ? (
                        <EmptyState
                            title="Vous n'avez encore aucun ticket."
                            actionLabel="Nouveau ticket"
                            actionHref={route('tickets.create')}
                        />
                    ) : (
                        <ul>
                            {recent_tickets.map((ticket) => (
                                <TicketRow key={ticket.id} ticket={ticket} />
                            ))}
                        </ul>
                    )}
                </Surface>

                <Surface>
                    <SectionTitle
                        title="Mes équipements"
                        href={route('assets.index')}
                    />
                    {my_assets.length === 0 ? (
                        <EmptyState title="Aucun équipement ne vous est affecté." />
                    ) : (
                        <ul>
                            {my_assets.map((asset) => (
                                <li
                                    key={asset.id}
                                    className="flex items-center justify-between border-b border-line/70 py-3 last:border-0"
                                >
                                    <div>
                                        <Link
                                            href={route('assets.show', asset.id)}
                                            className="font-medium text-brand hover:text-brand-strong"
                                        >
                                            {asset.name}
                                        </Link>
                                        <div className="text-xs text-ink-muted">
                                            {asset.inventory_number}
                                            {asset.location
                                                ? ` · ${asset.location.name}`
                                                : ''}
                                        </div>
                                    </div>
                                    {asset.status_label &&
                                        asset.status_color && (
                                            <Badge
                                                label={asset.status_label}
                                                color={asset.status_color}
                                            />
                                        )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Surface>
            </div>
        </>
    );
}

export default function Dashboard(props: Props) {
    const { auth } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Tableau de bord"
                    description={`Bonjour ${auth.user.name} — ${roleLabels[auth.user.role]}`}
                    actions={
                        props.view === 'user' ? (
                            <Link
                                href={route('tickets.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouveau ticket
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Tableau de bord" />

            <div className="mx-auto max-w-7xl">
                {props.view === 'technician' ? (
                    <TechnicianDashboard {...props} />
                ) : (
                    <UserDashboard {...props} />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
