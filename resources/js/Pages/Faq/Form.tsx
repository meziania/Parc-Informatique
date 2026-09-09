import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaqArticle, Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    article: FaqArticle | null;
    categories: Option[];
}

const selectClass = 'mt-1 ui-input';

export default function Form({ article, categories }: Props) {
    const editing = article !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        title: article?.title ?? '',
        body: article?.body ?? '',
        category: article?.category ?? 'other',
        is_published: article?.is_published ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            put(route('faq.update', article.id));
        } else {
            post(route('faq.store'));
        }
    };

    const title = editing ? "Modifier l'article" : 'Nouvel article FAQ';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description={
                        editing
                            ? 'Mettez à jour le contenu et la publication.'
                            : 'Rédigez une réponse claire pour les utilisateurs.'
                    }
                />
            }
        >
            <Head title={editing ? 'Modifier FAQ' : 'Nouvel article FAQ'} />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="title" value="Titre" />
                            <TextInput
                                id="title"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                className="mt-1 w-full"
                            />
                            <InputError
                                message={errors.title}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="category"
                                    value="Catégorie"
                                />
                                <select
                                    id="category"
                                    value={data.category}
                                    onChange={(e) =>
                                        setData('category', e.target.value)
                                    }
                                    className={selectClass}
                                >
                                    {categories.map((c) => (
                                        <option key={c.value} value={c.value}>
                                            {c.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.category}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex items-end pb-1">
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        checked={data.is_published}
                                        onChange={(e) =>
                                            setData(
                                                'is_published',
                                                e.target.checked,
                                            )
                                        }
                                        className="rounded border-line text-brand shadow-sm focus:ring-brand"
                                    />
                                    Publié (visible aux utilisateurs)
                                </label>
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="body" value="Contenu" />
                            <textarea
                                id="body"
                                value={data.body}
                                onChange={(e) =>
                                    setData('body', e.target.value)
                                }
                                rows={12}
                                className={selectClass}
                                placeholder="Rédigez la réponse de façon claire, avec des étapes numérotées si besoin…"
                            />
                            <InputError message={errors.body} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-line/70 pt-5">
                            <Link
                                href={route('faq.index')}
                                className="inline-flex items-center justify-center rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {editing ? 'Enregistrer' : "Créer l'article"}
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
