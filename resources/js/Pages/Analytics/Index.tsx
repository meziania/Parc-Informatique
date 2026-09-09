import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import StatCard from '@/Components/StatCard';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface AnalyticsPayload {
    period: {
        days: number;
        from: string;
        to: string;
        label: string;
    };
    kpis: {
        opened: number;
        resolved: number;
        open_now: number;
        sla_met_rate: number | null;
        avg_satisfaction: number | null;
        satisfaction_count: number;
    };
    tickets_per_day: {
        date: string;
        label: string;
        opened: number;
        resolved: number;
    }[];
    satisfaction_per_day: {
        date: string;
        label: string;
        avg: number | null;
        count: number;
    }[];
    by_catalog: { name: string; count: number }[];
    by_priority: { name: string; count: number }[];
    technician_workload: { name: string; open_tickets: number }[];
}

interface Props {
    analytics: AnalyticsPayload;
    filters: { days: number };
}

const periodOptions = [
    { value: 7, label: '7 jours' },
    { value: 30, label: '30 jours' },
    { value: 90, label: '90 jours' },
];

const chartColors = {
    opened: '#0f766e',
    resolved: '#ea580c',
    satisfaction: '#2563eb',
    bar: '#0d9488',
    workload: '#334155',
};

export default function Index({ analytics, filters }: Props) {
    const satisfactionSeries = analytics.satisfaction_per_day.map((row) => ({
        ...row,
        avg: row.avg ?? 0,
    }));

    const setDays = (days: number) => {
        router.get(
            route('analytics.index'),
            { days },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Analytics"
                    description={`Indicateurs qualité de service — ${analytics.period.label}`}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {periodOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => setDays(option.value)}
                                    className={`rounded-lg px-3 py-2 text-sm font-semibold transition ui-focus ${
                                        filters.days === option.value
                                            ? 'bg-brand text-white'
                                            : 'border border-line bg-surface text-ink hover:bg-surface-muted'
                                    }`}
                                >
                                    {option.label}
                                </button>
                            ))}
                            <a
                                href={route('reports.weekly')}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-3 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Export PDF 7 j
                            </a>
                        </div>
                    }
                />
            }
        >
            <Head title="Analytics" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <StatCard label="Ouverts" value={analytics.kpis.opened} />
                    <StatCard
                        label="Résolus"
                        value={analytics.kpis.resolved}
                        tone="ok"
                    />
                    <StatCard
                        label="Ouverts maintenant"
                        value={analytics.kpis.open_now}
                        tone={analytics.kpis.open_now > 0 ? 'warn' : 'ok'}
                    />
                    <StatCard
                        label="SLA respecté"
                        value={
                            analytics.kpis.sla_met_rate != null
                                ? `${analytics.kpis.sla_met_rate} %`
                                : '—'
                        }
                        tone={
                            analytics.kpis.sla_met_rate == null
                                ? 'default'
                                : analytics.kpis.sla_met_rate >= 80
                                  ? 'ok'
                                  : analytics.kpis.sla_met_rate >= 60
                                    ? 'warn'
                                    : 'danger'
                        }
                    />
                    <StatCard
                        label={
                            analytics.kpis.satisfaction_count > 0
                                ? `Satisfaction (${analytics.kpis.satisfaction_count})`
                                : 'Satisfaction'
                        }
                        value={
                            analytics.kpis.avg_satisfaction != null
                                ? analytics.kpis.avg_satisfaction
                                : '—'
                        }
                        tone="ok"
                    />
                    <StatCard
                        label="Période"
                        value={`${analytics.period.days} j`}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Surface>
                        <h3 className="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                            Tickets ouverts / résolus
                        </h3>
                        <div className="h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={analytics.tickets_per_day}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="#e2e8f0"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fontSize: 11 }}
                                        interval="preserveStartEnd"
                                    />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip />
                                    <Legend />
                                    <Line
                                        type="monotone"
                                        dataKey="opened"
                                        name="Ouverts"
                                        stroke={chartColors.opened}
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="resolved"
                                        name="Résolus"
                                        stroke={chartColors.resolved}
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </Surface>

                    <Surface>
                        <h3 className="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                            Satisfaction moyenne / jour
                        </h3>
                        {analytics.kpis.satisfaction_count === 0 ? (
                            <EmptyState title="Aucun avis sur la période." />
                        ) : (
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={satisfactionSeries}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#e2e8f0"
                                        />
                                        <XAxis
                                            dataKey="label"
                                            tick={{ fontSize: 11 }}
                                            interval="preserveStartEnd"
                                        />
                                        <YAxis
                                            domain={[0, 5]}
                                            tick={{ fontSize: 11 }}
                                        />
                                        <Tooltip />
                                        <Line
                                            type="monotone"
                                            dataKey="avg"
                                            name="Moyenne / 5"
                                            stroke={chartColors.satisfaction}
                                            strokeWidth={2}
                                            connectNulls
                                            dot={false}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </Surface>

                    <Surface>
                        <h3 className="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                            Volume par service catalogue
                        </h3>
                        {analytics.by_catalog.length === 0 ? (
                            <EmptyState title="Aucun ticket sur la période." />
                        ) : (
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={analytics.by_catalog}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#e2e8f0"
                                        />
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fontSize: 10 }}
                                            interval={0}
                                            angle={-20}
                                            textAnchor="end"
                                            height={60}
                                        />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                        <Tooltip />
                                        <Bar
                                            dataKey="count"
                                            name="Tickets"
                                            fill={chartColors.bar}
                                            radius={[6, 6, 0, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </Surface>

                    <Surface>
                        <h3 className="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                            Charge techniciens (ouverts)
                        </h3>
                        {analytics.technician_workload.length === 0 ? (
                            <EmptyState title="Aucun technicien actif." />
                        ) : (
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={analytics.technician_workload}
                                        layout="vertical"
                                        margin={{ left: 24 }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#e2e8f0"
                                        />
                                        <XAxis type="number" allowDecimals={false} />
                                        <YAxis
                                            type="category"
                                            dataKey="name"
                                            width={110}
                                            tick={{ fontSize: 11 }}
                                        />
                                        <Tooltip />
                                        <Bar
                                            dataKey="open_tickets"
                                            name="Ouverts"
                                            fill={chartColors.workload}
                                            radius={[0, 6, 6, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </Surface>
                </div>

                <Surface>
                    <h3 className="mb-3 font-display text-sm font-semibold uppercase tracking-wider text-ink-muted">
                        Répartition par priorité
                    </h3>
                    {analytics.by_priority.length === 0 ? (
                        <EmptyState title="Aucune donnée." />
                    ) : (
                        <div className="flex flex-wrap gap-3">
                            {analytics.by_priority.map((row) => (
                                <div
                                    key={row.name}
                                    className="rounded-lg border border-line bg-surface-muted/40 px-4 py-3"
                                >
                                    <div className="text-xs text-ink-muted">
                                        {row.name}
                                    </div>
                                    <div className="mt-1 flex items-center gap-2">
                                        <span className="font-display text-2xl font-semibold text-ink">
                                            {row.count}
                                        </span>
                                        <Badge label="tickets" color="gray" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
