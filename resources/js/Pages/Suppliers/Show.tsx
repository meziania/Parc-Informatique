import Badge from '@/Components/Badge';
import PageHeader from '@/Components/PageHeader';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Supplier } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

interface Props {
    supplier: Supplier;
    canManage: boolean;
}

export default function Show({ supplier, canManage }: Props) {
    const { flash } = usePage<PageProps>().props;

    const destroy = () => {
        if (confirm(`Supprimer le fournisseur « ${supplier.name} » ?`)) {
            router.delete(route('suppliers.destroy', supplier.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={supplier.name}
                    description={supplier.contact_name ?? 'Fournisseur'}
                    actions={
                        canManage ? (
                            <>
                                <Link
                                    href={route('suppliers.edit', supplier.id)}
                                    className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                                >
                                    Modifier
                                </Link>
                                <button
                                    type="button"
                                    onClick={destroy}
                                    className="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-danger transition hover:bg-surface-muted ui-focus"
                                >
                                    Supprimer
                                </button>
                            </>
                        ) : undefined
                    }
                />
            }
        >
            <Head title={supplier.name} />

            <div className="mx-auto max-w-4xl space-y-6">
                {flash?.status && (
                    <p className="rounded-lg border border-ok/30 bg-emerald-50 px-4 py-3 text-sm text-ok">
                        {flash.status}
                    </p>
                )}

                <Surface padding={false} className="overflow-hidden">
                    <div className="flex flex-wrap items-center gap-3 border-b border-line/70 bg-surface-muted/50 px-6 py-4">
                        <Badge
                            label={supplier.is_active ? 'Actif' : 'Inactif'}
                            color={supplier.is_active ? 'green' : 'gray'}
                        />
                        <span className="text-sm text-ink-muted">
                            {supplier.contracts_count ?? 0} contrat(s)
                        </span>
                    </div>
                    <dl className="divide-y divide-line/70">
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Contact
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {supplier.contact_name ?? '—'}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                E-mail
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {supplier.email ?? '—'}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Téléphone
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {supplier.phone ?? '—'}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Site web
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {supplier.website ? (
                                    <a
                                        href={supplier.website}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-brand hover:text-brand-strong"
                                    >
                                        {supplier.website}
                                    </a>
                                ) : (
                                    '—'
                                )}
                            </dd>
                        </div>
                        {supplier.notes && (
                            <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt className="text-sm font-medium text-ink-muted">
                                    Notes
                                </dt>
                                <dd className="mt-1 whitespace-pre-wrap text-sm text-ink sm:col-span-2 sm:mt-0">
                                    {supplier.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Surface>

                <Surface>
                    <h2 className="text-base font-semibold text-ink">
                        Contrats
                    </h2>
                    <ul className="mt-4 divide-y divide-line/70">
                        {(supplier.contracts ?? []).length === 0 && (
                            <li className="py-3 text-sm text-ink-muted">
                                Aucun contrat associé.
                            </li>
                        )}
                        {(supplier.contracts ?? []).map((contract) => (
                            <li
                                key={contract.id}
                                className="flex items-center justify-between gap-3 py-3"
                            >
                                <Link
                                    href={route('contracts.show', contract.id)}
                                    className="text-sm font-medium text-brand hover:text-brand-strong"
                                >
                                    {contract.title}
                                    {contract.reference && (
                                        <span className="ms-2 text-ink-muted">
                                            {contract.reference}
                                        </span>
                                    )}
                                </Link>
                                {contract.expiry_label && (
                                    <Badge
                                        label={contract.expiry_label}
                                        color={
                                            contract.expiry_status === 'expired'
                                                ? 'red'
                                                : contract.expiry_status ===
                                                    'expiring'
                                                  ? 'orange'
                                                  : contract.expiry_status ===
                                                      'ok'
                                                    ? 'green'
                                                    : 'gray'
                                        }
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                </Surface>

                <Link
                    href={route('suppliers.index')}
                    className="text-sm font-medium text-brand hover:text-brand-strong"
                >
                    &larr; Retour aux fournisseurs
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
