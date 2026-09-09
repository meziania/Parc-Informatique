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

interface Props {
    location: Location;
}

export default function Edit({ location }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: location.name,
        building: location.building ?? '',
        room: location.room ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('locations.update', location.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={`Modifier — ${location.name}`}
                    description="Mettez à jour le nom, le bâtiment ou la salle."
                />
            }
        >
            <Head title={`Modifier — ${location.name}`} />

            <div className="mx-auto max-w-2xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
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
                                htmlFor="building"
                                value="Bâtiment"
                                className="text-ink"
                            />
                            <TextInput
                                id="building"
                                value={data.building}
                                onChange={(e) =>
                                    setData('building', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                message={errors.building}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="room"
                                value="Salle / étage"
                                className="text-ink"
                            />
                            <TextInput
                                id="room"
                                value={data.room}
                                onChange={(e) =>
                                    setData('room', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                message={errors.room}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-line/70 pt-5">
                            <Link
                                href={route('locations.index')}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                Enregistrer
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
