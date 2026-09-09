import Badge from '@/Components/Badge';
import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, SoftwareLicense } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

interface AssetOption {
    id: number;
    name: string;
    inventory_number: string;
}

interface Props {
    license: SoftwareLicense;
    availableAssets: AssetOption[];
    canManage: boolean;
}

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

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

export default function Show({ license, availableAssets, canManage }: Props) {
    const { flash } = usePage<PageProps>().props;
    const attachForm = useForm({ asset_id: '' as string | number });

    const attach = (e: FormEvent) => {
        e.preventDefault();
        attachForm.post(route('licenses.assets.attach', license.id), {
            preserveScroll: true,
            onSuccess: () => attachForm.reset('asset_id'),
        });
    };

    const detach = (assetId: number) => {
        if (confirm('Retirer cette affectation ?')) {
            router.delete(
                route('licenses.assets.detach', [license.id, assetId]),
                { preserveScroll: true },
            );
        }
    };

    const destroy = () => {
        if (confirm(`Supprimer la licence « ${license.name} » ?`)) {
            router.delete(route('licenses.destroy', license.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={license.name}
                    description={license.vendor ?? 'Licence logicielle'}
                    actions={
                        canManage ? (
                            <>
                                <Link
                                    href={route('licenses.edit', license.id)}
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
            <Head title={license.name} />

            <div className="mx-auto max-w-4xl space-y-6">
                {flash?.status && (
                    <p className="rounded-lg border border-ok/30 bg-emerald-50 px-4 py-3 text-sm text-ok">
                        {flash.status}
                    </p>
                )}

                <Surface padding={false} className="overflow-hidden">
                    <div className="flex flex-wrap items-center gap-3 border-b border-line/70 bg-surface-muted/50 px-6 py-4">
                        <Badge
                            label={license.expiry_label}
                            color={expiryColor(license.expiry_status)}
                        />
                        <span className="text-sm text-ink-muted">
                            {license.seats_used} / {license.seats} sièges
                            utilisés
                        </span>
                    </div>
                    <dl className="divide-y divide-line/70">
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Clé produit
                            </dt>
                            <dd className="mt-1 font-mono text-sm text-ink sm:col-span-2 sm:mt-0">
                                {license.product_key ?? '—'}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Achat
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {formatDate(license.purchase_date)}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Expiration
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {formatDate(license.expiry_date)}
                            </dd>
                        </div>
                        {license.notes && (
                            <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt className="text-sm font-medium text-ink-muted">
                                    Notes
                                </dt>
                                <dd className="mt-1 whitespace-pre-wrap text-sm text-ink sm:col-span-2 sm:mt-0">
                                    {license.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Surface>

                <Surface>
                    <h2 className="text-base font-semibold text-ink">
                        Postes affectés
                    </h2>
                    <p className="mt-1 text-sm text-ink-muted">
                        {license.seats_available} siège(s) disponible(s)
                    </p>

                    <ul className="mt-4 divide-y divide-line/70">
                        {(license.assets ?? []).length === 0 && (
                            <li className="py-3 text-sm text-ink-muted">
                                Aucun poste affecté.
                            </li>
                        )}
                        {(license.assets ?? []).map((asset) => (
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
                                    <option value="">Affecter un poste…</option>
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
                                Affecter
                            </PrimaryButton>
                        </form>
                    )}
                </Surface>

                <Link
                    href={route('licenses.index')}
                    className="text-sm font-medium text-brand hover:text-brand-strong"
                >
                    &larr; Retour aux licences
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
