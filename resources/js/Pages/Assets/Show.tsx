import Attachments from '@/Components/Attachments';
import Badge from '@/Components/Badge';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Asset, AssetEvent } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    asset: Asset;
    events: AssetEvent[];
    canManage: boolean;
    availableLicenses?: { id: number; name: string }[];
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm font-medium text-ink-muted">{label}</dt>
            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                {children}
            </dd>
        </div>
    );
}

function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
}

function formatDateTime(date: string): string {
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function eventDotClass(type: string): string {
    switch (type) {
        case 'created':
            return 'bg-brand';
        case 'status_changed':
            return 'bg-warn';
        case 'assigned':
            return 'bg-ok';
        case 'location_changed':
            return 'bg-ink-muted';
        case 'ticket_opened':
            return 'bg-danger';
        case 'ticket_resolved':
            return 'bg-ok';
        default:
            return 'bg-line';
    }
}

export default function Show({
    asset,
    events,
    canManage,
    availableLicenses = [],
}: Props) {
    const underWarranty =
        asset.warranty_end !== null &&
        new Date(asset.warranty_end) > new Date();

    const softwares = asset.installed_softwares ?? [];

    const softwareForm = useForm({
        name: '',
        vendor: '',
        version: '',
        software_license_id: '',
        installed_at: '',
    });

    const addSoftware = (e: FormEvent) => {
        e.preventDefault();
        softwareForm.post(route('assets.softwares.store', asset.id), {
            preserveScroll: true,
            onSuccess: () => softwareForm.reset(),
        });
    };

    const removeSoftware = (softwareId: number) => {
        if (confirm('Retirer ce logiciel ?')) {
            router.delete(
                route('assets.softwares.destroy', [asset.id, softwareId]),
                { preserveScroll: true },
            );
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={asset.name}
                    description={`${asset.type_label} — ${asset.inventory_number}`}
                    actions={
                        canManage ? (
                            <Link
                                href={route('assets.edit', asset.id)}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Modifier
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title={asset.name} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Surface padding={false} className="overflow-hidden">
                    <div className="flex flex-wrap items-center gap-3 border-b border-line/70 bg-surface-muted/50 px-6 py-4">
                        <Badge
                            label={asset.status_label}
                            color={asset.status_color}
                        />
                        <span className="text-sm text-ink-muted">
                            {asset.type_label} — {asset.inventory_number}
                        </span>
                    </div>

                    <dl className="divide-y divide-line/70">
                        <Row label="N° de série">
                            {asset.serial_number ?? '—'}
                        </Row>
                        <Row label="Fabricant / Modèle">
                            {asset.manufacturer || asset.model
                                ? [asset.manufacturer, asset.model]
                                      .filter(Boolean)
                                      .join(' — ')
                                : '—'}
                        </Row>
                        <Row label="Date d'achat">
                            {formatDate(asset.purchase_date)}
                        </Row>
                        <Row label="Garantie">
                            {asset.warranty_end ? (
                                <span
                                    className={
                                        underWarranty
                                            ? 'font-medium text-ok'
                                            : 'font-medium text-danger'
                                    }
                                >
                                    {underWarranty
                                        ? `Sous garantie jusqu'au ${formatDate(asset.warranty_end)}`
                                        : `Expirée depuis le ${formatDate(asset.warranty_end)}`}
                                </span>
                            ) : (
                                '—'
                            )}
                        </Row>
                        <Row label="Prochaine maintenance">
                            {asset.next_maintenance_at ? (
                                <span className="font-medium text-ink">
                                    {formatDate(asset.next_maintenance_at)}
                                    {asset.maintenance_label
                                        ? ` (${asset.maintenance_label})`
                                        : ''}
                                </span>
                            ) : (
                                '—'
                            )}
                        </Row>
                        <Row label="Affecté à">
                            {asset.user ? asset.user.name : '— Non affecté —'}
                        </Row>
                        <Row label="Lieu">
                            {asset.location
                                ? [
                                      asset.location.name,
                                      asset.location.building,
                                      asset.location.room,
                                  ]
                                      .filter(Boolean)
                                      .join(' — ')
                                : '—'}
                        </Row>
                        {asset.notes && (
                            <Row label="Notes">
                                <p className="whitespace-pre-wrap">
                                    {asset.notes}
                                </p>
                            </Row>
                        )}
                    </dl>
                </Surface>

                {(asset.licenses?.length ?? 0) > 0 && (
                    <Surface>
                        <h2 className="text-base font-semibold text-ink">
                            Licences affectées
                        </h2>
                        <ul className="mt-3 divide-y divide-line/70">
                            {asset.licenses!.map((license) => (
                                <li
                                    key={license.id}
                                    className="flex items-center justify-between gap-3 py-2"
                                >
                                    <Link
                                        href={route('licenses.show', license.id)}
                                        className="text-sm font-medium text-brand hover:text-brand-strong"
                                    >
                                        {license.name}
                                    </Link>
                                    <span className="text-xs text-ink-muted">
                                        {license.vendor ?? '—'}
                                        {license.expiry_date
                                            ? ` · exp. ${formatDate(license.expiry_date)}`
                                            : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Surface>
                )}

                <Surface>
                    <h2 className="text-base font-semibold text-ink">
                        Contrats liés
                    </h2>
                    <ul className="mt-3 divide-y divide-line/70">
                        {(asset.contracts ?? []).length === 0 && (
                            <li className="py-2 text-sm text-ink-muted">
                                Aucun contrat lié.
                            </li>
                        )}
                        {(asset.contracts ?? []).map((contract) => (
                            <li
                                key={contract.id}
                                className="flex items-center justify-between gap-3 py-2"
                            >
                                <Link
                                    href={route('contracts.show', contract.id)}
                                    className="text-sm font-medium text-brand hover:text-brand-strong"
                                >
                                    {contract.title}
                                </Link>
                                <span className="text-xs text-ink-muted">
                                    {contract.reference ?? '—'}
                                    {contract.ends_on
                                        ? ` · fin ${formatDate(contract.ends_on)}`
                                        : ''}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Surface>

                <Surface>
                    <h2 className="text-base font-semibold text-ink">
                        Logiciels installés
                    </h2>
                    <ul className="mt-3 divide-y divide-line/70">
                        {softwares.length === 0 && (
                            <li className="py-2 text-sm text-ink-muted">
                                Aucun logiciel inventorié.
                            </li>
                        )}
                        {softwares.map((sw) => (
                            <li
                                key={sw.id}
                                className="flex items-center justify-between gap-3 py-2"
                            >
                                <div>
                                    <p className="text-sm font-medium text-ink">
                                        {sw.name}
                                        {sw.version ? ` ${sw.version}` : ''}
                                    </p>
                                    <p className="text-xs text-ink-muted">
                                        {[sw.vendor, formatDate(sw.installed_at)]
                                            .filter((v) => v && v !== '—')
                                            .join(' · ') || '—'}
                                        {sw.license
                                            ? ` · licence ${sw.license.name}`
                                            : ''}
                                    </p>
                                </div>
                                {canManage && (
                                    <button
                                        type="button"
                                        onClick={() => removeSoftware(sw.id)}
                                        className="text-sm font-medium text-danger hover:text-red-800"
                                    >
                                        Supprimer
                                    </button>
                                )}
                            </li>
                        ))}
                    </ul>

                    {canManage && (
                        <form
                            onSubmit={addSoftware}
                            className="mt-4 space-y-3 border-t border-line/70 pt-4"
                        >
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="sw_name" value="Nom *" />
                                    <TextInput
                                        id="sw_name"
                                        value={softwareForm.data.name}
                                        onChange={(e) =>
                                            softwareForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full"
                                        required
                                    />
                                    <InputError
                                        message={softwareForm.errors.name}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        htmlFor="sw_vendor"
                                        value="Éditeur"
                                    />
                                    <TextInput
                                        id="sw_vendor"
                                        value={softwareForm.data.vendor}
                                        onChange={(e) =>
                                            softwareForm.setData(
                                                'vendor',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        htmlFor="sw_version"
                                        value="Version"
                                    />
                                    <TextInput
                                        id="sw_version"
                                        value={softwareForm.data.version}
                                        onChange={(e) =>
                                            softwareForm.setData(
                                                'version',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        htmlFor="sw_installed_at"
                                        value="Installé le"
                                    />
                                    <TextInput
                                        id="sw_installed_at"
                                        type="date"
                                        value={softwareForm.data.installed_at}
                                        onChange={(e) =>
                                            softwareForm.setData(
                                                'installed_at',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full"
                                    />
                                </div>
                            </div>
                            {availableLicenses.length > 0 && (
                                <div>
                                    <InputLabel
                                        htmlFor="sw_license"
                                        value="Licence liée"
                                    />
                                    <select
                                        id="sw_license"
                                        value={
                                            softwareForm.data.software_license_id
                                        }
                                        onChange={(e) =>
                                            softwareForm.setData(
                                                'software_license_id',
                                                e.target.value,
                                            )
                                        }
                                        className="ui-input mt-1 w-full"
                                    >
                                        <option value="">Aucune</option>
                                        {availableLicenses.map((lic) => (
                                            <option key={lic.id} value={lic.id}>
                                                {lic.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                            <PrimaryButton disabled={softwareForm.processing}>
                                Ajouter le logiciel
                            </PrimaryButton>
                        </form>
                    )}
                </Surface>

                <Surface padding={false} className="overflow-hidden">
                    <div className="border-b border-line/70 px-6 py-4">
                        <h2 className="text-base font-semibold text-ink">
                            Historique
                        </h2>
                        <p className="mt-1 text-sm text-ink-muted">
                            Affectations, changements de statut et tickets liés
                        </p>
                    </div>

                    {events.length === 0 ? (
                        <p className="px-6 py-8 text-sm text-ink-muted">
                            Aucun événement enregistré pour cet équipement.
                        </p>
                    ) : (
                        <ol className="relative space-y-0 px-6 py-4">
                            {events.map((event, index) => (
                                <li
                                    key={event.id}
                                    className="relative flex gap-4 pb-6 last:pb-2"
                                >
                                    {index < events.length - 1 && (
                                        <span
                                            className="absolute left-[7px] top-3 h-full w-px bg-line"
                                            aria-hidden
                                        />
                                    )}
                                    <span
                                        className={`relative z-10 mt-1.5 h-3.5 w-3.5 shrink-0 rounded-full ring-4 ring-surface ${eventDotClass(event.event_type)}`}
                                        aria-hidden
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                            <p className="text-sm font-medium text-ink">
                                                {event.title}
                                            </p>
                                            <time
                                                className="shrink-0 text-xs text-ink-muted"
                                                dateTime={event.created_at}
                                            >
                                                {formatDateTime(event.created_at)}
                                            </time>
                                        </div>
                                        {event.body && (
                                            <p className="mt-1 whitespace-pre-wrap text-sm text-ink-muted">
                                                {event.body}
                                            </p>
                                        )}
                                        <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-muted">
                                            {event.actor && (
                                                <span>Par {event.actor.name}</span>
                                            )}
                                            {event.ticket_id && (
                                                <Link
                                                    href={route(
                                                        'tickets.show',
                                                        event.ticket_id,
                                                    )}
                                                    className="font-medium text-brand hover:text-brand-strong"
                                                >
                                                    Voir le ticket
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    )}
                </Surface>

                <Attachments
                    documents={asset.documents ?? []}
                    documentableType="asset"
                    documentableId={asset.id}
                    canUpload={canManage}
                    canDeleteAll={canManage}
                />

                <div>
                    <Link
                        href={route('assets.index')}
                        className="text-sm font-medium text-brand hover:text-brand-strong"
                    >
                        &larr; Retour au parc
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
