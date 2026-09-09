import Dropdown from '@/Components/Dropdown';
import GlobalSearch from '@/Components/GlobalSearch';
import NotificationBell from '@/Components/NotificationBell';
import { syncCsrfMeta } from '@/lib/http';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

function NavItem({
    href,
    active,
    children,
}: {
    href: string;
    active: boolean;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
                active
                    ? 'bg-brand-soft text-brand-strong'
                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
            }`}
        >
            {children}
        </Link>
    );
}

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage();
    const user = page.props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        syncCsrfMeta(
            (page.props as { csrf_token?: string }).csrf_token,
        );
    }, [page.props]);

    const nav = (
        <>
            <NavItem href={route('dashboard')} active={route().current('dashboard')}>
                Tableau de bord
            </NavItem>
            <NavItem
                href={route('assets.index')}
                active={!!route().current('assets.*')}
            >
                {user.role === 'user' ? 'Mes équipements' : 'Parc'}
            </NavItem>
            <NavItem
                href={route('tickets.index')}
                active={!!route().current('tickets.*')}
            >
                {user.role === 'user' ? 'Mes tickets' : 'Tickets'}
            </NavItem>
            <NavItem
                href={route('reservations.index')}
                active={!!route().current('reservations.*')}
            >
                Réservations
            </NavItem>
            {user.role !== 'user' && (
                <>
                    <NavItem
                        href={route('licenses.index')}
                        active={!!route().current('licenses.*')}
                    >
                        Licences
                    </NavItem>
                    <NavItem
                        href={route('contracts.index')}
                        active={!!route().current('contracts.*')}
                    >
                        Contrats
                    </NavItem>
                    <NavItem
                        href={route('suppliers.index')}
                        active={!!route().current('suppliers.*')}
                    >
                        Fournisseurs
                    </NavItem>
                    <NavItem
                        href={route('consumables.index')}
                        active={!!route().current('consumables.*')}
                    >
                        Consommables
                    </NavItem>
                </>
            )}
            <NavItem href={route('faq.index')} active={!!route().current('faq.*')}>
                FAQ
            </NavItem>
            {user.role !== 'user' && (
                <NavItem
                    href={route('analytics.index')}
                    active={!!route().current('analytics.*')}
                >
                    Analytics
                </NavItem>
            )}
            {user.role !== 'user' && (
                <NavItem
                    href={route('locations.index')}
                    active={!!route().current('locations.*')}
                >
                    Lieux
                </NavItem>
            )}
            {user.role === 'admin' && (
                <>
                    <NavItem
                        href={route('admin.users.index')}
                        active={!!route().current('admin.users.*')}
                    >
                        Utilisateurs
                    </NavItem>
                    <NavItem
                        href={route('admin.audit-logs.index')}
                        active={!!route().current('admin.audit-logs.*')}
                    >
                        Audit
                    </NavItem>
                </>
            )}
        </>
    );

    return (
        <div className="min-h-screen bg-mist">
            <div className="lg:flex">
                <aside className="hidden w-64 shrink-0 bg-ink text-white lg:fixed lg:inset-y-0 lg:flex lg:flex-col">
                    <div className="flex h-16 items-center px-5">
                        <Link
                            href={route('dashboard')}
                            className="font-display text-lg font-semibold tracking-tight"
                        >
                            Parc Informatique
                        </Link>
                    </div>
                    <nav className="flex-1 space-y-1 px-3 py-2">{nav}</nav>
                    <div className="border-t border-white/10 p-4 text-xs text-slate-400">
                        Connecté en tant que
                        <div className="mt-1 truncate font-medium text-white">
                            {user.name}
                        </div>
                        <div className="mt-0.5 truncate text-slate-300">
                            {user.role_label ?? user.role}
                        </div>
                    </div>
                </aside>

                <div className="min-w-0 flex-1 lg:pl-64">
                    <header className="sticky top-0 z-20 border-b border-line/80 bg-surface/90 backdrop-blur">
                        <div className="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center gap-3 lg:hidden">
                                <button
                                    type="button"
                                    onClick={() => setMobileOpen((v) => !v)}
                                    className="rounded-lg border border-line px-3 py-2 text-sm text-ink ui-focus"
                                >
                                    Menu
                                </button>
                                <span className="font-display text-sm font-semibold text-ink">
                                    Parc Informatique
                                </span>
                            </div>

                            <div className="ms-auto flex min-w-0 flex-1 items-center justify-end gap-2 lg:flex-none">
                                <GlobalSearch />
                                <NotificationBell />
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button
                                            type="button"
                                            className="inline-flex max-w-[14rem] items-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition hover:bg-surface-muted ui-focus"
                                        >
                                            <span className="min-w-0 truncate text-left">
                                                <span className="block truncate">
                                                    {user.name}
                                                </span>
                                                <span className="block truncate text-xs font-normal text-ink-muted">
                                                    {user.role_label ??
                                                        user.role}
                                                </span>
                                            </span>
                                            <svg
                                                className="ms-2 h-4 w-4 shrink-0 text-ink-muted"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fillRule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clipRule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Profil
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('notifications.index')}
                                        >
                                            Notifications
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Déconnexion
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        {mobileOpen && (
                            <nav className="space-y-1 border-t border-line bg-ink px-3 py-3 lg:hidden">
                                {nav}
                            </nav>
                        )}
                    </header>

                    {header && (
                        <div className="border-b border-line/70 bg-surface px-4 py-5 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    )}

                    <main className="fade-in px-4 py-8 sm:px-6 lg:px-8">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
