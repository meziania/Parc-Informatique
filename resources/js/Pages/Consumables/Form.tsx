import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Consumable, Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface NamedOption {
    id: number;
    name: string;
}

interface Props {
    consumable: Consumable | null;
    categories: Option[];
    locations: NamedOption[];
    suppliers: NamedOption[];
}

export default function Form({
    consumable,
    categories,
    locations,
    suppliers,
}: Props) {
    const editing = consumable !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        name: consumable?.name ?? '',
        sku: consumable?.sku ?? '',
        category: consumable?.category ?? categories[0]?.value ?? '',
        quantity: consumable?.quantity ?? 0,
        min_quantity: consumable?.min_quantity ?? 0,
        unit: consumable?.unit ?? 'unité',
        location_id: consumable?.location_id
            ? String(consumable.location_id)
            : '',
        supplier_id: consumable?.supplier_id
            ? String(consumable.supplier_id)
            : '',
        notes: consumable?.notes ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(route('consumables.update', consumable.id));
        } else {
            post(route('consumables.store'));
        }
    };

    const title = editing ? 'Modifier le consommable' : 'Nouveau consommable';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description="Quantité, seuil et emplacement de stock."
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
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                className="mt-1 w-full"
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="sku" value="SKU" />
                                <TextInput
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) =>
                                        setData('sku', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.sku}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="category"
                                    value="Catégorie *"
                                />
                                <select
                                    id="category"
                                    value={data.category}
                                    onChange={(e) =>
                                        setData('category', e.target.value)
                                    }
                                    className="ui-input mt-1 w-full"
                                    required
                                >
                                    {categories.map((opt) => (
                                        <option
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.category}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel
                                    htmlFor="quantity"
                                    value="Quantité *"
                                />
                                <TextInput
                                    id="quantity"
                                    type="number"
                                    min={0}
                                    value={data.quantity}
                                    onChange={(e) =>
                                        setData(
                                            'quantity',
                                            Number(e.target.value) || 0,
                                        )
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.quantity}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="min_quantity"
                                    value="Seuil min. *"
                                />
                                <TextInput
                                    id="min_quantity"
                                    type="number"
                                    min={0}
                                    value={data.min_quantity}
                                    onChange={(e) =>
                                        setData(
                                            'min_quantity',
                                            Number(e.target.value) || 0,
                                        )
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.min_quantity}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="unit" value="Unité *" />
                                <TextInput
                                    id="unit"
                                    value={data.unit}
                                    onChange={(e) =>
                                        setData('unit', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.unit}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="location_id"
                                    value="Lieu"
                                />
                                <select
                                    id="location_id"
                                    value={data.location_id}
                                    onChange={(e) =>
                                        setData('location_id', e.target.value)
                                    }
                                    className="ui-input mt-1 w-full"
                                >
                                    <option value="">Aucun</option>
                                    {locations.map((loc) => (
                                        <option key={loc.id} value={loc.id}>
                                            {loc.name}
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
                                    htmlFor="supplier_id"
                                    value="Fournisseur"
                                />
                                <select
                                    id="supplier_id"
                                    value={data.supplier_id}
                                    onChange={(e) =>
                                        setData('supplier_id', e.target.value)
                                    }
                                    className="ui-input mt-1 w-full"
                                >
                                    <option value="">Aucun</option>
                                    {suppliers.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.supplier_id}
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

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={route('consumables.index')}
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
