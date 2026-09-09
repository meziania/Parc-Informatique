import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SoftwareLicense } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    license: SoftwareLicense | null;
}

export default function Form({ license }: Props) {
    const editing = license !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        name: license?.name ?? '',
        vendor: license?.vendor ?? '',
        product_key: license?.product_key ?? '',
        seats: license?.seats ?? 1,
        purchase_date: license?.purchase_date?.slice(0, 10) ?? '',
        expiry_date: license?.expiry_date?.slice(0, 10) ?? '',
        notes: license?.notes ?? '',
        is_active: license?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(route('licenses.update', license.id));
        } else {
            post(route('licenses.store'));
        }
    };

    const title = editing ? 'Modifier la licence' : 'Nouvelle licence';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description="Renseignez les sièges et la date d’expiration."
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
                                <InputLabel htmlFor="vendor" value="Éditeur" />
                                <TextInput
                                    id="vendor"
                                    value={data.vendor}
                                    onChange={(e) =>
                                        setData('vendor', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.vendor}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="seats"
                                    value="Nombre de sièges *"
                                />
                                <TextInput
                                    id="seats"
                                    type="number"
                                    min={1}
                                    value={data.seats}
                                    onChange={(e) =>
                                        setData(
                                            'seats',
                                            Number(e.target.value) || 1,
                                        )
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.seats}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="product_key"
                                value="Clé produit"
                            />
                            <TextInput
                                id="product_key"
                                value={data.product_key}
                                onChange={(e) =>
                                    setData('product_key', e.target.value)
                                }
                                className="mt-1 w-full"
                            />
                            <InputError
                                message={errors.product_key}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="purchase_date"
                                    value="Date d’achat"
                                />
                                <TextInput
                                    id="purchase_date"
                                    type="date"
                                    value={data.purchase_date}
                                    onChange={(e) =>
                                        setData('purchase_date', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.purchase_date}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="expiry_date"
                                    value="Date d’expiration"
                                />
                                <TextInput
                                    id="expiry_date"
                                    type="date"
                                    value={data.expiry_date}
                                    onChange={(e) =>
                                        setData('expiry_date', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.expiry_date}
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
                            Licence active
                        </label>

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={
                                    editing
                                        ? route('licenses.show', license.id)
                                        : route('licenses.index')
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
