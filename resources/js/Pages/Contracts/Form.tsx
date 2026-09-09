import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Contract } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface SupplierOption {
    id: number;
    name: string;
}

interface Props {
    contract: Contract | null;
    suppliers: SupplierOption[];
}

export default function Form({ contract, suppliers }: Props) {
    const editing = contract !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        title: contract?.title ?? '',
        reference: contract?.reference ?? '',
        supplier_id: contract?.supplier_id ? String(contract.supplier_id) : '',
        starts_on: contract?.starts_on?.slice(0, 10) ?? '',
        ends_on: contract?.ends_on?.slice(0, 10) ?? '',
        amount: contract?.amount ?? '',
        currency: contract?.currency ?? 'MAD',
        notes: contract?.notes ?? '',
        is_active: contract?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(route('contracts.update', contract.id));
        } else {
            post(route('contracts.store'));
        }
    };

    const title = editing ? 'Modifier le contrat' : 'Nouveau contrat';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description="Période, montant et fournisseur associé."
                />
            }
        >
            <Head title={title} />

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
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="reference"
                                    value="Référence"
                                />
                                <TextInput
                                    id="reference"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.reference}
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

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="starts_on"
                                    value="Début"
                                />
                                <TextInput
                                    id="starts_on"
                                    type="date"
                                    value={data.starts_on}
                                    onChange={(e) =>
                                        setData('starts_on', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.starts_on}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="ends_on" value="Fin" />
                                <TextInput
                                    id="ends_on"
                                    type="date"
                                    value={data.ends_on}
                                    onChange={(e) =>
                                        setData('ends_on', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.ends_on}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="amount" value="Montant" />
                                <TextInput
                                    id="amount"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.amount}
                                    onChange={(e) =>
                                        setData('amount', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                />
                                <InputError
                                    message={errors.amount}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="currency"
                                    value="Devise"
                                />
                                <TextInput
                                    id="currency"
                                    value={data.currency}
                                    onChange={(e) =>
                                        setData('currency', e.target.value)
                                    }
                                    className="mt-1 w-full"
                                    maxLength={3}
                                />
                                <InputError
                                    message={errors.currency}
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
                            Contrat actif
                        </label>

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={
                                    editing
                                        ? route('contracts.show', contract.id)
                                        : route('contracts.index')
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
