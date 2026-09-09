import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaqArticle, Option, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    articles: Paginated<FaqArticle>;
    filters: { search?: string; category?: string };
    categories: Option[];
    canManage: boolean;
}

export default function Index({
    articles,
    filters,
    categories,
    canManage,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('faq.index'),
            {
                search: search || undefined,
                category: category || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setCategory('');
        router.get(route('faq.index'), {}, { preserveState: true });
    };

    const destroy = (article: FaqArticle) => {
        if (confirm(`Supprimer l'article « ${article.title} » ?`)) {
            router.delete(route('faq.destroy', article.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="FAQ"
                    description="Réponses aux questions fréquentes — consultez la FAQ avant d'ouvrir un ticket."
                    actions={
                        canManage ? (
                            <Link
                                href={route('faq.create')}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Nouvel article
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="FAQ" />

            <div className="mx-auto max-w-7xl">
                <FilterBar onSubmit={submit} onReset={reset}>
                    <div className="min-w-52 grow">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher dans la FAQ…"
                            className="w-full"
                        />
                    </div>
                    <select
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        className="rounded-lg border-line text-sm ui-focus"
                    >
                        <option value="">Toutes les catégories</option>
                        {categories.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {articles.data.length === 0 ? (
                        <EmptyState
                            title="Aucun article trouvé."
                            description="Ajustez les filtres ou créez un nouvel article."
                            actionLabel={
                                canManage ? 'Nouvel article' : undefined
                            }
                            actionHref={
                                canManage ? route('faq.create') : undefined
                            }
                        />
                    ) : (
                        <ul className="divide-y divide-line">
                            {articles.data.map((article) => (
                                <li
                                    key={article.id}
                                    className="flex flex-wrap items-start justify-between gap-4 px-6 py-4 hover:bg-surface-muted/60"
                                >
                                    <div className="min-w-0">
                                        <Link
                                            href={route(
                                                'faq.show',
                                                article.id,
                                            )}
                                            className="text-base font-medium text-brand hover:text-brand-strong"
                                        >
                                            {article.title}
                                        </Link>
                                        <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                                            <span>{article.category_label}</span>
                                            <span>·</span>
                                            <span>
                                                {article.views_count} vue
                                                {article.views_count > 1
                                                    ? 's'
                                                    : ''}
                                            </span>
                                            {canManage &&
                                                !article.is_published && (
                                                    <Badge
                                                        label="Brouillon"
                                                        color="orange"
                                                    />
                                                )}
                                        </div>
                                        <p className="mt-2 line-clamp-2 text-sm text-ink-muted">
                                            {article.body}
                                        </p>
                                    </div>
                                    {canManage && (
                                        <div className="flex shrink-0 gap-3 text-sm">
                                            <Link
                                                href={route(
                                                    'faq.edit',
                                                    article.id,
                                                )}
                                                className="font-medium text-brand hover:text-brand-strong"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    destroy(article)
                                                }
                                                className="font-medium text-danger hover:text-red-800"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Surface>

                <Pagination links={articles.links} />
            </div>
        </AuthenticatedLayout>
    );
}
