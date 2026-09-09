import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Location } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface AssetOption {
    id: number;
    name: string;
    inventory_number: string;
    type: string;
}

interface Props {
    assets: AssetOption[];
    locations: Location[];
}

function toLocalInput(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default function Form({ assets, locations }: Props) {
    const start = new Date();
    start.setHours(start.getHours() + 1, 0, 0, 0);
    const end = new Date(start);
    end.setHours(end.getHours() + 2);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        purpose: '',
        asset_id: '' as string | number,
        location_id: '' as string | number,
        starts_at: toLocalInput(start),
        ends_at: toLocalInput(end),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('reservations.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Nouvelle réservation"
                    description="Demandez un équipement ou une salle pour un créneau précis."
                />
            }
        >
            <Head title="Nouvelle réservation" />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="title" value="Titre *" />
                            <TextInput
                                id="title"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                className="mt-1 w-full"
                                placeholder="Ex. Formation Excel — salle B"
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="purpose" value="Motif" />
                            <textarea
                                id="purpose"
                                value={data.purpose}
                                onChange={(e) =>
                                    setData('purpose', e.target.value)
                                }
                                rows={3}
                                className="mt-1 ui-input"
                            />
                            <InputError
                                message={errors.purpose}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="asset_id"
                                    value="Équipement"
                                />
                                <select
                                    id="asset_id"
                                    value={data.asset_id}
                                    onChange={(e) =>
                                        setData('asset_id', e.target.value)
                                    }
                                    className="mt-1 ui-input w-full"
                                >
                                    <option value="">— Aucun —</option>
                                    {assets.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.name} ({asset.inventory_number})
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.asset_id}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="location_id"
                                    value="Lieu / salle"
                                />
                                <select
                                    id="location_id"
                                    value={data.location_id}
                                    onChange={(e) =>
                                        setData('location_id', e.target.value)
                                    }
                                    className="mt-1 ui-input w-full"
                                >
                                    <option value="">— Aucun —</option>
                                    {locations.map((loc) => (
                                        <option key={loc.id} value={loc.id}>
                                            {loc.name}
                                            {loc.building
                                                ? ` — ${loc.building}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.location_id}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                        <p className="text-xs text-ink-muted">
                            Choisissez au moins un équipement ou un lieu.
                        </p>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="starts_at"
                                    value="Début *"
                                />
                                <TextInput
                                    id="starts_at"
                                    type="datetime-local"
                                    value={data.starts_at}
                                    onChange={(e) =>
                                        setData('starts_at', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.starts_at}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="ends_at" value="Fin *" />
                                <TextInput
                                    id="ends_at"
                                    type="datetime-local"
                                    value={data.ends_at}
                                    onChange={(e) =>
                                        setData('ends_at', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.ends_at}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={route('reservations.index')}
                                className="text-sm font-medium text-brand hover:text-brand-strong"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                Envoyer la demande
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
