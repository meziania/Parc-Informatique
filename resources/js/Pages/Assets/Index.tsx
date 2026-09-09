import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Asset, Option, Paginated, PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    assets: Paginated<Asset>;
    filters: { search?: string; type?: string; status?: string };
    types: Option[];
    statuses: Option[];
    canManage: boolean;
}

export default function Index({
    assets,
    filters,
    types,
    statuses,
    canManage,
}: Props) {
    const { auth } = usePage<PageProps>().props;
    const isTechnician = auth.user.role !== 'user';
    const title = isTechnician ? 'Parc informatique' : 'Mes équipements';

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('assets.index'),
            {
                search: search || undefined,
                type: type || undefined,
                status: status || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setType('');
        setStatus('');
        router.get(route('assets.index'), {}, { preserveState: true });
    };

    const destroy = (asset: Asset) => {
        if (confirm(`Supprimer l'équipement « ${asset.name} » ?`)) {
            router.delete(route('assets.destroy', asset.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description={
                        isTechnician
                            ? 'Inventaire, statuts et affectations du parc.'
                            : 'Équipements qui vous sont affectés.'
                    }
                    actions={
                        <>
                            <a
                                href={route('assets.export', {
                                    search: search || undefined,
                                    type: type || undefined,
                                    status: status || undefined,
                                })}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Exporter CSV
                            </a>
                            {canManage && (
                                <>
                                    <Link
                                        href={route('assets.import')}
                                        className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                                    >
                                        Importer CSV
                                    </Link>
                                    <Link
                                        href={route('assets.create')}
                                        className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                                    >
                                        Nouvel équipement
                                    </Link>
                                </>
                            )}
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
                            placeholder="Rechercher (nom, n° inventaire, n° série)…"
                            className="w-full"
                        />
                    </div>
                    <select
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className="rounded-lg border-line text-sm ui-focus"
                    >
                        <option value="">Tous les types</option>
                        {types.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-lg border-line text-sm ui-focus"
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {assets.data.length === 0 ? (
                        <EmptyState
                            title="Aucun équipement trouvé."
                            description="Ajustez les filtres ou créez un nouvel équipement."
                            actionLabel={
                                canManage ? 'Nouvel équipement' : undefined
                            }
                            actionHref={
                                canManage ? route('assets.create') : undefined
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Équipement
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Utilisateur
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Lieu
                                        </th>
                                        {canManage && (
                                            <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                Actions
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {assets.data.map((asset) => (
                                        <tr
                                            key={asset.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'assets.show',
                                                        asset.id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    {asset.name}
                                                </Link>
                                                <div className="text-xs text-ink-muted">
                                                    {asset.inventory_number}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {asset.type_label}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={asset.status_label}
                                                    color={asset.status_color}
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {asset.user?.name ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {asset.location?.name ?? '—'}
                                            </td>
                                            {canManage && (
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <Link
                                                        href={route(
                                                            'assets.edit',
                                                            asset.id,
                                                        )}
                                                        className="me-3 font-medium text-brand hover:text-brand-strong"
                                                    >
                                                        Modifier
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            destroy(asset)
                                                        }
                                                        className="font-medium text-danger hover:text-red-800"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Surface>

                <Pagination links={assets.links} />
            </div>
        </AuthenticatedLayout>
    );
}
