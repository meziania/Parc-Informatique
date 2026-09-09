import Badge from '@/Components/Badge';
import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Contract, PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

interface AssetOption {
    id: number;
    name: string;
    inventory_number: string;
}

interface Props {
    contract: Contract;
    availableAssets: AssetOption[];
    canManage: boolean;
}

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

function formatDate(value: string | null | undefined): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

export default function Show({ contract, availableAssets, canManage }: Props) {
    const { flash } = usePage<PageProps>().props;
    const attachForm = useForm({ asset_id: '' as string | number });

    const attach = (e: FormEvent) => {
        e.preventDefault();
        attachForm.post(route('contracts.assets.attach', contract.id), {
            preserveScroll: true,
            onSuccess: () => attachForm.reset('asset_id'),
        });
    };

    const detach = (assetId: number) => {
        if (confirm('Retirer ce lien ?')) {
            router.delete(
                route('contracts.assets.detach', [contract.id, assetId]),
                { preserveScroll: true },
            );
        }
    };

    const destroy = () => {
        if (confirm(`Supprimer le contrat « ${contract.title} » ?`)) {
            router.delete(route('contracts.destroy', contract.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={contract.title}
                    description={
                        contract.supplier?.name ??
                        contract.reference ??
                        'Contrat'
                    }
                    actions={
                        canManage ? (
                            <>
                                <Link
                                    href={route('contracts.edit', contract.id)}
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
            <Head title={contract.title} />

            <div className="mx-auto max-w-4xl space-y-6">
                {flash?.status && (
                    <p className="rounded-lg border border-ok/30 bg-emerald-50 px-4 py-3 text-sm text-ok">
                        {flash.status}
                    </p>
                )}

                <Surface padding={false} className="overflow-hidden">
                    <div className="flex flex-wrap items-center gap-3 border-b border-line/70 bg-surface-muted/50 px-6 py-4">
                        <Badge
                            label={contract.expiry_label ?? '—'}
                            color={expiryColor(contract.expiry_status)}
                        />
                        <span className="text-sm text-ink-muted">
                            {contract.assets_count ??
                                contract.assets?.length ??
                                0}{' '}
                            équipement(s)
                        </span>
                    </div>
                    <dl className="divide-y divide-line/70">
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Référence
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {contract.reference ?? '—'}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Fournisseur
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {contract.supplier ? (
                                    <Link
                                        href={route(
                                            'suppliers.show',
                                            contract.supplier.id,
                                        )}
                                        className="font-medium text-brand hover:text-brand-strong"
                                    >
                                        {contract.supplier.name}
                                    </Link>
                                ) : (
                                    '—'
                                )}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Période
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {formatDate(contract.starts_on)} →{' '}
                                {formatDate(contract.ends_on)}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Montant
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {contract.amount != null && contract.amount !== ''
                                    ? `${contract.amount} ${contract.currency ?? ''}`.trim()
                                    : '—'}
                            </dd>
                        </div>
                        {contract.notes && (
                            <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt className="text-sm font-medium text-ink-muted">
                                    Notes
                                </dt>
                                <dd className="mt-1 whitespace-pre-wrap text-sm text-ink sm:col-span-2 sm:mt-0">
                                    {contract.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Surface>

                <Surface>
                    <h2 className="text-base font-semibold text-ink">
                        Équipements liés
                    </h2>

                    <ul className="mt-4 divide-y divide-line/70">
                        {(contract.assets ?? []).length === 0 && (
                            <li className="py-3 text-sm text-ink-muted">
                                Aucun équipement lié.
                            </li>
                        )}
                        {(contract.assets ?? []).map((asset) => (
                            <li
                                key={asset.id}
                                className="flex items-center justify-between gap-3 py-3"
                            >
                                <Link
                                    href={route('assets.show', asset.id)}
                                    className="text-sm font-medium text-brand hover:text-brand-strong"
                                >
                                    {asset.name}
                                    <span className="ms-2 text-ink-muted">
                                        {asset.inventory_number}
                                    </span>
                                </Link>
                                {canManage && (
                                    <button
                                        type="button"
                                        onClick={() => detach(asset.id)}
                                        className="text-sm font-medium text-danger hover:text-red-800"
                                    >
                                        Retirer
                                    </button>
                                )}
                            </li>
                        ))}
                    </ul>

                    {canManage && availableAssets.length > 0 && (
                        <form
                            onSubmit={attach}
                            className="mt-4 flex flex-wrap items-start gap-3 border-t border-line/70 pt-4"
                        >
                            <div className="min-w-48 grow">
                                <select
                                    value={attachForm.data.asset_id}
                                    onChange={(e) =>
                                        attachForm.setData(
                                            'asset_id',
                                            e.target.value,
                                        )
                                    }
                                    className="ui-input w-full"
                                    required
                                >
                                    <option value="">Lier un équipement…</option>
                                    {availableAssets.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.name} ({asset.inventory_number})
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={attachForm.errors.asset_id}
                                    className="mt-1"
                                />
                            </div>
                            <PrimaryButton
                                disabled={
                                    attachForm.processing ||
                                    !attachForm.data.asset_id
                                }
                            >
                                Lier
                            </PrimaryButton>
                        </form>
                    )}
                </Surface>

                <Link
                    href={route('contracts.index')}
                    className="text-sm font-medium text-brand hover:text-brand-strong"
                >
                    &larr; Retour aux contrats
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
