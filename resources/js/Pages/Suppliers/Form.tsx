import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Supplier } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    supplier: Supplier | null;
}

export default function Form({ supplier }: Props) {
    const editing = supplier !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        name: supplier?.name ?? '',
        contact_name: supplier?.contact_name ?? '',
        email: supplier?.email ?? '',
        phone: supplier?.phone ?? '',
        website: supplier?.website ?? '',
        notes: supplier?.notes ?? '',
        is_active: supplier?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(route('suppliers.update', supplier.id));
        } else {
            post(route('suppliers.store'));
        }
    };

    const title = editing ? 'Modifier le fournisseur' : 'Nouveau fournisseur';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description="Coordonnées et notes du partenaire."
                />
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="name" value="Nom *" />
                            <TextInput
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="mt-1 w-full"
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="contact_name"
                                    value="Contact"
                                />
                                <TextInput
                                    id="contact_name"
                                    value={data.contact_name}
                                    onChange={(e) =>
                                        setData('contact_name', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.contact_name}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="email" value="E-mail" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.email}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="phone" value="Téléphone" />
                                <TextInput
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.phone}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="website" value="Site web" />
                                <TextInput
                                    id="website"
                                    value={data.website}
                                    onChange={(e) =>
                                        setData('website', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.website}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="notes" value="Notes" />
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={3}
                                className="mt-1 ui-input"
                            />
                            <InputError message={errors.notes} className="mt-2" />
                        </div>

                        <label className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                                className="rounded border-line text-brand ui-focus"
                            />
                            Fournisseur actif
                        </label>

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={
                                    editing
                                        ? route('suppliers.show', supplier.id)
                                        : route('suppliers.index')
                                }
                                className="text-sm font-medium text-brand hover:text-brand-strong"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {editing ? 'Enregistrer' : 'Créer'}
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
