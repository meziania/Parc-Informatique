import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Location } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    locations: Location[];
}

export default function Index({ locations }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        building: '',
        room: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('locations.store'), {
            onSuccess: () => reset(),
            preserveScroll: true,
        });
    };

    const destroy = (location: Location) => {
        const message =
            (location.assets_count ?? 0) > 0
                ? `Supprimer le lieu « ${location.name} » ? Les ${location.assets_count} équipements associés seront détachés.`
                : `Supprimer le lieu « ${location.name} » ?`;
        if (confirm(message)) {
            router.delete(route('locations.destroy', location.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Lieux"
                    description="Bâtiments, salles et zones d’affectation du parc."
                />
            }
        >
            <Head title="Lieux" />

            <div className="mx-auto max-w-5xl space-y-6">
                <Surface>
                    <form
                        onSubmit={submit}
                        className="flex flex-wrap items-start gap-3"
                    >
                        <div className="min-w-40 grow">
                            <TextInput
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="Nom du lieu *"
                                className="w-full"
                                required
                            />
                            <InputError
                                message={errors.name}
                                className="mt-1"
                            />
                        </div>
                        <div className="min-w-40 grow">
                            <TextInput
                                value={data.building}
                                onChange={(e) =>
                                    setData('building', e.target.value)
                                }
                                placeholder="Bâtiment"
                                className="w-full"
                            />
                            <InputError
                                message={errors.building}
                                className="mt-1"
                            />
                        </div>
                        <div className="min-w-40 grow">
                            <TextInput
                                value={data.room}
                                onChange={(e) =>
                                    setData('room', e.target.value)
                                }
                                placeholder="Salle / étage"
                                className="w-full"
                            />
                            <InputError
                                message={errors.room}
                                className="mt-1"
                            />
                        </div>
                        <PrimaryButton disabled={processing}>
                            Ajouter
                        </PrimaryButton>
                    </form>
                </Surface>

                <Surface padding={false} className="overflow-hidden">
                    {locations.length === 0 ? (
                        <EmptyState
                            title="Aucun lieu enregistré."
                            description="Ajoutez un lieu ci-dessus pour commencer."
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
                                            Bâtiment
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Salle
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Équipements
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {locations.map((location) => (
                                        <tr
                                            key={location.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4 text-sm font-medium text-ink">
                                                {location.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {location.building ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {location.room ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {location.assets_count ?? 0}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'locations.edit',
                                                        location.id,
                                                    )}
                                                    className="me-3 font-medium text-brand hover:text-brand-strong"
                                                >
                                                    Modifier
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(location)
                                                    }
                                                    className="font-medium text-danger hover:text-red-800"
                                                >
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
