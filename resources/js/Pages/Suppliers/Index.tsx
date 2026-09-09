import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated, Supplier } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    suppliers: Paginated<Supplier>;
    filters: { search?: string };
    canManage: boolean;
}

export default function Index({ suppliers, filters, canManage }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('suppliers.index'),
            { search: search || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        router.get(route('suppliers.index'), {}, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Fournisseurs"
                    description="Contacts, contrats et partenaires du parc."
                    actions={
                        canManage ? (
                            <Link
                                href={route('suppliers.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouveau fournisseur
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Fournisseurs" />

            <div className="mx-auto max-w-6xl space-y-6">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nom, contact, e-mail…"
                        className="w-full min-w-48"
                    />
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {suppliers.data.length === 0 ? (
                        <EmptyState
                            title="Aucun fournisseur"
                            description="Ajoutez les fournisseurs liés aux contrats et consommables."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Nom
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Contact
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            E-mail
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Contrats
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {suppliers.data.map((supplier) => (
                                        <tr
                                            key={supplier.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={route(
                                                        'suppliers.show',
                                                        supplier.id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    {supplier.name}
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {supplier.contact_name ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {supplier.email ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {supplier.contracts_count ?? 0}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={suppliers.links} />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
