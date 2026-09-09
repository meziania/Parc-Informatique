import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Contract, Option, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    contracts: Paginated<Contract>;
    filters: { search?: string; expiry?: string };
    canManage: boolean;
}

const expiryOptions: Option[] = [
    { value: '', label: 'Toutes' },
    { value: 'expiring', label: 'Expire sous 30 j' },
    { value: 'expired', label: 'Expirés' },
];

function expiryColor(status?: string): string {
    switch (status) {
        case 'expired':
            return 'red';
        case 'expiring':
            return 'orange';
        case 'ok':
            return 'green';
        default:
            return 'gray';
    }
}

export default function Index({ contracts, filters, canManage }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [expiry, setExpiry] = useState(filters.expiry ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('contracts.index'),
            {
                search: search || undefined,
                expiry: expiry || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setExpiry('');
        router.get(route('contracts.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Contrats"
                    description="Maintenance, support et échéances fournisseurs."
                    actions={
                        canManage ? (
                            <Link
                                href={route('contracts.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouveau contrat
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Contrats" />

            <div className="mx-auto max-w-6xl space-y-6">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Titre, référence…"
                        className="w-full min-w-48"
                    />
                    <select
                        value={expiry}
                        onChange={(e) => setExpiry(e.target.value)}
                        className="ui-input min-w-44"
                    >
                        {expiryOptions.map((opt) => (
                            <option key={opt.value || 'all'} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {contracts.data.length === 0 ? (
                        <EmptyState
                            title="Aucun contrat"
                            description="Enregistrez les contrats liés au parc et aux fournisseurs."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Contrat
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Fournisseur
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Équipements
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Échéance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {contracts.data.map((contract) => (
                                        <tr
                                            key={contract.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'contracts.show',
                                                        contract.id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    {contract.title}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-ink-muted">
                                                    {contract.reference ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {contract.supplier?.name ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {contract.assets_count ?? 0}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={
                                                        contract.expiry_label ??
                                                        '—'
                                                    }
                                                    color={expiryColor(
                                                        contract.expiry_status,
                                                    )}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={contracts.links} />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
