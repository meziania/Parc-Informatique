import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AppNotification, Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    items: Paginated<AppNotification>;
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function Index({ items }: Props) {
    const openNotification = (notification: AppNotification) => {
        const visit = () => {
            if (notification.data.url) {
                router.visit(notification.data.url);
            }
        };

        if (notification.read_at) {
            visit();

            return;
        }

        router.post(
            route('notifications.read', notification.id),
            {},
            {
                preserveScroll: true,
                onFinish: visit,
            },
        );
    };

    const markAllRead = () => {
        router.post(route('notifications.read-all'), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Notifications"
                    description="Historique des alertes liées à vos tickets."
                    actions={
                        items.data.some((item) => !item.read_at) ? (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition hover:bg-surface-muted ui-focus"
                            >
                                Tout marquer comme lu
                            </button>
                        ) : undefined
                    }
                />
            }
        >
            <Head title="Notifications" />

            <Surface className="overflow-hidden">
                {items.data.length === 0 ? (
                    <EmptyState
                        title="Aucune notification"
                        description="Les créations, assignations, résolutions et commentaires de tickets apparaîtront ici."
                    />
                ) : (
                    <ul className="divide-y divide-line">
                        {items.data.map((notification) => (
                            <li key={notification.id}>
                                <button
                                    type="button"
                                    onClick={() => openNotification(notification)}
                                    className={`flex w-full items-start gap-3 px-5 py-4 text-start transition hover:bg-surface-muted ${
                                        notification.read_at
                                            ? 'bg-white'
                                            : 'bg-brand-soft/30'
                                    }`}
                                >
                                    <span
                                        className={`mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${
                                            notification.read_at
                                                ? 'bg-line'
                                                : 'bg-brand'
                                        }`}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                                            <p className="font-medium text-ink">
                                                {notification.data.title}
                                            </p>
                                            <time className="text-xs text-ink-muted">
                                                {formatDate(
                                                    notification.created_at,
                                                )}
                                            </time>
                                        </div>
                                        <p className="mt-1 text-sm text-ink-muted">
                                            {notification.data.body}
                                        </p>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Surface>

            <div className="mt-6">
                <Pagination links={items.links} />
            </div>
        </AuthenticatedLayout>
    );
}
