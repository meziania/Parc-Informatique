import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option, Paginated, SoftwareLicense } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    licenses: Paginated<SoftwareLicense>;
    filters: { search?: string; expiry?: string };
    canManage: boolean;
}

const expiryOptions: Option[] = [
    { value: '', label: 'Toutes' },
    { value: 'expiring', label: 'Expire sous 30 j' },
    { value: 'expired', label: 'Expirées' },
];

function expiryColor(status: string): string {
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

export default function Index({ licenses, filters, canManage }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [expiry, setExpiry] = useState(filters.expiry ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('licenses.index'),
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
        router.get(route('licenses.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Licences logicielles"
                    description="Quantités, échéances et affectations aux postes."
                    actions={
                        canManage ? (
                            <Link
                                href={route('licenses.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouvelle licence
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Licences" />

            <div className="mx-auto max-w-6xl space-y-6">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nom, éditeur…"
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
                    {licenses.data.length === 0 ? (
                        <EmptyState
                            title="Aucune licence"
                            description="Ajoutez les licences du parc pour suivre sièges et échéances."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Licence
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Sièges
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Échéance
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Statut
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {licenses.data.map((license) => (
                                        <tr
                                            key={license.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'licenses.show',
                                                        license.id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    {license.name}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-ink-muted">
                                                    {license.vendor ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {license.seats_used} /{' '}
                                                {license.seats}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {license.expiry_date
                                                    ? new Date(
                                                          license.expiry_date,
                                                      ).toLocaleDateString(
                                                          'fr-FR',
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    label={license.expiry_label}
                                                    color={expiryColor(
                                                        license.expiry_status,
                                                    )}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={licenses.links} />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
