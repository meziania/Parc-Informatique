import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    types: Option[];
    statuses: Option[];
}

export default function Import({ types, statuses }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('assets.import.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Importer des équipements"
                    description="Chargez un CSV pour créer ou mettre à jour le parc."
                />
            }
        >
            <Head title="Import CSV" />

            <div className="mx-auto max-w-3xl space-y-6">
                <Surface>
                    <p className="text-sm text-ink-muted">
                        Téléchargez le modèle, renseignez les lignes, puis
                        importez le fichier. Les n° d’inventaire existants sont
                        mis à jour ; les nouveaux sont créés.
                    </p>
                    <a
                        href={route('assets.import.template')}
                        className="mt-4 inline-flex text-sm font-medium text-brand hover:text-brand-strong"
                    >
                        Télécharger le modèle CSV
                    </a>

                    <div className="mt-6 rounded-lg border border-line/70 bg-surface-muted/40 p-4 text-sm text-ink">
                        <p className="font-medium">Colonnes attendues</p>
                        <ul className="mt-2 list-disc space-y-1 ps-5 text-ink-muted">
                            <li>
                                <span className="font-medium text-ink">
                                    Obligatoires :
                                </span>{' '}
                                N° inventaire, Nom, Type, Statut
                            </li>
                            <li>
                                Optionnelles : N° série, Fabricant, Modèle, Date
                                achat, Fin garantie, Notes
                            </li>
                            <li>Séparateur ; ou , — encodage UTF-8</li>
                        </ul>
                        <p className="mt-3 text-xs text-ink-muted">
                            Types :{' '}
                            {types.map((t) => t.value).join(', ')}. Statuts :{' '}
                            {statuses.map((s) => s.value).join(', ')}.
                        </p>
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <div>
                            <InputLabel htmlFor="file" value="Fichier CSV *" />
                            <input
                                id="file"
                                type="file"
                                accept=".csv,text/csv,text/plain"
                                onChange={(e) =>
                                    setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="mt-1 block w-full text-sm text-ink file:me-3 file:rounded-lg file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-strong"
                                required
                            />
                            <InputError message={errors.file} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-between gap-3">
                            <Link
                                href={route('assets.index')}
                                className="text-sm font-medium text-brand hover:text-brand-strong"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton
                                disabled={processing || !data.file}
                            >
                                Importer
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
