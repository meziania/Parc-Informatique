import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface CatalogFormItem {
    id: number;
    name: string;
    code: string;
    description: string | null;
    default_type: string;
    default_priority: string;
    sla_hours: number;
    is_active: boolean;
    sort_order: number;
}

interface Props {
    item: CatalogFormItem | null;
    types: Option[];
    priorities: Option[];
}

export default function Form({ item, types, priorities }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: item?.name ?? '',
        code: item?.code ?? '',
        description: item?.description ?? '',
        default_type: item?.default_type ?? 'request',
        default_priority: item?.default_priority ?? 'medium',
        sla_hours: item?.sla_hours ?? 24,
        is_active: item?.is_active ?? true,
        sort_order: item?.sort_order ?? 0,
    });

    const title = item ? `Modifier — ${item.name}` : 'Nouveau service';

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (item) {
            put(route('service-catalog.update', item.id));
        } else {
            post(route('service-catalog.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description="Définissez le type, la priorité et le SLA du service."
                />
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="name" value="Nom *" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError className="mt-1" message={errors.name} />
                        </div>

                        <div>
                            <InputLabel htmlFor="code" value="Code *" />
                            <TextInput
                                id="code"
                                className="mt-1 block w-full"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value)}
                                required
                            />
                            <InputError className="mt-1" message={errors.code} />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="description"
                                value="Description"
                            />
                            <textarea
                                id="description"
                                className="ui-input mt-1"
                                rows={3}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                            />
                            <InputError
                                className="mt-1"
                                message={errors.description}
                            />
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="default_type"
                                    value="Type par défaut *"
                                />
                                <select
                                    id="default_type"
                                    className="ui-input mt-1"
                                    value={data.default_type}
                                    onChange={(e) =>
                                        setData('default_type', e.target.value)
                                    }
                                >
                                    {types.map((type) => (
                                        <option
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.default_type}
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="default_priority"
                                    value="Priorité par défaut *"
                                />
                                <select
                                    id="default_priority"
                                    className="ui-input mt-1"
                                    value={data.default_priority}
                                    onChange={(e) =>
                                        setData(
                                            'default_priority',
                                            e.target.value,
                                        )
                                    }
                                >
                                    {priorities.map((priority) => (
                                        <option
                                            key={priority.value}
                                            value={priority.value}
                                        >
                                            {priority.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.default_priority}
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="sla_hours"
                                    value="SLA (heures) *"
                                />
                                <TextInput
                                    id="sla_hours"
                                    type="number"
                                    min={1}
                                    className="mt-1 block w-full"
                                    value={data.sla_hours}
                                    onChange={(e) =>
                                        setData(
                                            'sla_hours',
                                            Number(e.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    className="mt-1"
                                    message={errors.sla_hours}
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="sort_order"
                                    value="Ordre d’affichage *"
                                />
                                <TextInput
                                    id="sort_order"
                                    type="number"
                                    min={0}
                                    className="mt-1 block w-full"
                                    value={data.sort_order}
                                    onChange={(e) =>
                                        setData(
                                            'sort_order',
                                            Number(e.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    className="mt-1"
                                    message={errors.sort_order}
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="is_active"
                                    value="État *"
                                />
                                <select
                                    id="is_active"
                                    className="ui-input mt-1"
                                    value={data.is_active ? '1' : '0'}
                                    onChange={(e) =>
                                        setData(
                                            'is_active',
                                            e.target.value === '1',
                                        )
                                    }
                                >
                                    <option value="1">Actif</option>
                                    <option value="0">Inactif</option>
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.is_active}
                                />
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <PrimaryButton disabled={processing}>
                                {item ? 'Enregistrer' : 'Créer'}
                            </PrimaryButton>
                            <Link href={route('service-catalog.index')}>
                                <SecondaryButton type="button">
                                    Annuler
                                </SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
