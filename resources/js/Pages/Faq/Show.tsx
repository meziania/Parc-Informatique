import Badge from '@/Components/Badge';
import PageHeader from '@/Components/PageHeader';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FaqArticle } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    article: FaqArticle;
    canManage: boolean;
}

export default function Show({ article, canManage }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={article.title}
                    description={article.category_label}
                    actions={
                        canManage ? (
                            <Link
                                href={route('faq.edit', article.id)}
                                className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                            >
                                Modifier
                            </Link>
                        ) : undefined
                    }
                />
            }
        >
            <Head title={article.title} />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <div className="mb-6 flex flex-wrap items-center gap-3 text-xs text-ink-muted">
                        <Badge label={article.category_label} color="gray" />
                        {!article.is_published && (
                            <Badge label="Brouillon" color="orange" />
                        )}
                        <span>
                            {article.views_count} vue
                            {article.views_count > 1 ? 's' : ''}
                        </span>
                        {article.author && (
                            <span>par {article.author.name}</span>
                        )}
                    </div>

                    <div className="whitespace-pre-line text-sm leading-relaxed text-ink">
                        {article.body}
                    </div>
                </Surface>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <Link
                        href={route('faq.index')}
                        className="text-sm font-medium text-brand hover:text-brand-strong"
                    >
                        &larr; Retour à la FAQ
                    </Link>
                    <Link
                        href={route('tickets.create')}
                        className="text-sm text-ink-muted underline decoration-line underline-offset-2 hover:text-ink"
                    >
                        Ça ne répond pas ? Ouvrir un ticket
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
