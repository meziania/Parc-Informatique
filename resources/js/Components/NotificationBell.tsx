import { AppNotification, PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function relativeTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const diff = Date.now() - new Date(iso).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) {
        return "à l'instant";
    }
    if (minutes < 60) {
        return `il y a ${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `il y a ${hours} h`;
    }

    const days = Math.floor(hours / 24);

    return `il y a ${days} j`;
}

export default function NotificationBell() {
    const page = usePage<PageProps>().props;
    const notifications = page.notifications ?? {
        unread_count: 0,
        recent: [],
    };
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: MouseEvent) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    const openNotification = (notification: AppNotification) => {
        setOpen(false);

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
        router.post(
            route('notifications.read-all'),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="relative" ref={rootRef}>
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-line bg-white text-ink transition hover:bg-surface-muted ui-focus"
                aria-label="Notifications"
                aria-expanded={open}
            >
                <svg
                    className="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.8"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                {notifications.unread_count > 0 && (
                    <span className="absolute -end-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-md bg-brand px-1.5 py-0.5 text-[10px] font-semibold text-white">
                        {notifications.unread_count > 9
                            ? '9+'
                            : notifications.unread_count}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute end-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-line bg-white shadow-lg sm:w-96">
                    <div className="flex items-center justify-between border-b border-line px-4 py-3">
                        <div>
                            <p className="font-display text-sm font-semibold text-ink">
                                Notifications
                            </p>
                            <p className="text-xs text-ink-muted">
                                {notifications.unread_count > 0
                                    ? `${notifications.unread_count} non lue${notifications.unread_count > 1 ? 's' : ''}`
                                    : 'Tout est à jour'}
                            </p>
                        </div>
                        {notifications.unread_count > 0 && (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="text-xs font-medium text-brand-strong hover:underline"
                            >
                                Tout marquer lu
                            </button>
                        )}
                    </div>

                    <div className="max-h-80 overflow-y-auto">
                        {notifications.recent.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-ink-muted">
                                Aucune notification pour le moment.
                            </p>
                        ) : (
                            notifications.recent.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => openNotification(notification)}
                                    className={`block w-full border-b border-line/70 px-4 py-3 text-start transition hover:bg-surface-muted ${
                                        notification.read_at
                                            ? 'bg-white'
                                            : 'bg-brand-soft/40'
                                    }`}
                                >
                                    <div className="flex items-start gap-2">
                                        {!notification.read_at && (
                                            <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand" />
                                        )}
                                        <div
                                            className={
                                                notification.read_at
                                                    ? 'ps-4'
                                                    : ''
                                            }
                                        >
                                            <p className="text-sm font-medium text-ink">
                                                {notification.data.title}
                                            </p>
                                            <p className="mt-0.5 line-clamp-2 text-xs text-ink-muted">
                                                {notification.data.body}
                                            </p>
                                            <p className="mt-1 text-[11px] text-ink-muted">
                                                {relativeTime(
                                                    notification.created_at,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            ))
                        )}
                    </div>

                    <div className="border-t border-line bg-surface px-4 py-2.5">
                        <Link
                            href={route('notifications.index')}
                            className="text-xs font-medium text-brand-strong hover:underline"
                            onClick={() => setOpen(false)}
                        >
                            Voir toutes les notifications
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
