import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Asset, Location, Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    asset: Asset | null;
    locations: Location[];
    users: { id: number; name: string }[];
    types: Option[];
    statuses: Option[];
}

const selectClass = 'mt-1 ui-input';

export default function Form({
    asset,
    locations,
    users,
    types,
    statuses,
}: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: asset?.name ?? '',
        type: asset?.type ?? 'computer',
        status: asset?.status ?? 'in_stock',
        inventory_number: asset?.inventory_number ?? '',
        serial_number: asset?.serial_number ?? '',
        manufacturer: asset?.manufacturer ?? '',
        model: asset?.model ?? '',
        purchase_date: asset?.purchase_date?.slice(0, 10) ?? '',
        warranty_end: asset?.warranty_end?.slice(0, 10) ?? '',
        next_maintenance_at: asset?.next_maintenance_at?.slice(0, 10) ?? '',
        location_id: asset?.location_id?.toString() ?? '',
        user_id: asset?.user_id?.toString() ?? '',
        notes: asset?.notes ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (asset) {
            put(route('assets.update', asset.id));
        } else {
            post(route('assets.store'));
        }
    };

    const title = asset ? `Modifier — ${asset.name}` : 'Nouvel équipement';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description={
                        asset
                            ? 'Mettez à jour les informations, le statut et l’affectation.'
                            : 'Renseignez les caractéristiques du nouvel équipement.'
                    }
                />
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-4xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel
                                    htmlFor="name"
                                    value="Nom *"
                                    className="text-ink"
                                />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="type"
                                    value="Type *"
                                    className="text-ink"
                                />
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) =>
                                        setData('type', e.target.value)
                                    }
                                    className={selectClass}
                                >
                                    {types.map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.type}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="status"
                                    value="Statut *"
                                    className="text-ink"
                                />
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) =>
                                        setData('status', e.target.value)
                                    }
                                    className={selectClass}
                                >
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.status}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="inventory_number"
                                    value="N° d'inventaire *"
                                    className="text-ink"
                                />
                                <TextInput
                                    id="inventory_number"
                                    value={data.inventory_number}
                                    onChange={(e) =>
                                        setData(
                                            'inventory_number',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    message={errors.inventory_number}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="serial_number"
                                    value="N° de série"
                                    className="text-ink"
                                />
                                <TextInput
                                    id="serial_number"
                                    value={data.serial_number}
                                    onChange={(e) =>
                                        setData(
                                            'serial_number',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full"
                                />
                                <InputError
                                    message={errors.serial_number}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="manufacturer"
                                    value="Fabricant"
                                    className="text-ink"
                                />
                                <TextInput
                                    id="manufacturer"
                                    value={data.manufacturer}
                                    onChange={(e) =>
                                        setData('manufacturer', e.target.value)
                                    }
                                    className="mt-1 block w-full"
                                />
                                <InputError
                                    message={errors.manufacturer}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="model"
                                    value="Modèle"
                                    className="text-ink"
                                />
                                <TextInput
                                    id="model"
                                    value={data.model}
                                    onChange={(e) =>
                                        setData('model', e.target.value)
                                    }
                                    className="mt-1 block w-full"
                                />
                                <InputError
                                    message={errors.model}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="purchase_date"
                                    value="Date d'achat"
                                    className="text-ink"
                                />
                                <input
                                    id="purchase_date"
                                    type="date"
                                    value={data.purchase_date}
                                    onChange={(e) =>
                                        setData(
                                            'purchase_date',
                                            e.target.value,
                                        )
                                    }
                                    className={selectClass}
                                />
                                <InputError
                                    message={errors.purchase_date}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="warranty_end"
                                    value="Fin de garantie"
                                    className="text-ink"
                                />
                                <input
                                    id="warranty_end"
                                    type="date"
                                    value={data.warranty_end}
                                    onChange={(e) =>
                                        setData(
                                            'warranty_end',
                                            e.target.value,
                                        )
                                    }
                                    className={selectClass}
                                />
                                <InputError
                                    message={errors.warranty_end}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="next_maintenance_at"
                                    value="Prochaine maintenance"
                                    className="text-ink"
                                />
                                <input
                                    id="next_maintenance_at"
                                    type="date"
                                    value={data.next_maintenance_at}
                                    onChange={(e) =>
                                        setData(
                                            'next_maintenance_at',
                                            e.target.value,
                                        )
                                    }
                                    className={selectClass}
                                />
                                <InputError
                                    message={errors.next_maintenance_at}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="location_id"
                                    value="Lieu"
                                    className="text-ink"
                                />
                                <select
                                    id="location_id"
                                    value={data.location_id}
                                    onChange={(e) =>
                                        setData('location_id', e.target.value)
                                    }
                                    className={selectClass}
                                >
                                    <option value="">— Non défini —</option>
                                    {locations.map((location) => (
                                        <option
                                            key={location.id}
                                            value={location.id}
                                        >
                                            {location.name}
                                            {location.building
                                                ? ` (${location.building})`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.location_id}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="user_id"
                                    value="Affecté à"
                                    className="text-ink"
                                />
                                <select
                                    id="user_id"
                                    value={data.user_id}
                                    onChange={(e) =>
                                        setData('user_id', e.target.value)
                                    }
                                    className={selectClass}
                                >
                                    <option value="">— Non affecté —</option>
                                    {users.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.user_id}
                                    className="mt-2"
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <InputLabel
                                    htmlFor="notes"
                                    value="Notes"
                                    className="text-ink"
                                />
                                <textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                    rows={3}
                                    className={selectClass}
                                />
                                <InputError
                                    message={errors.notes}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-line/70 pt-5">
                            <Link
                                href={route('assets.index')}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {asset ? 'Enregistrer' : 'Créer'}
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
