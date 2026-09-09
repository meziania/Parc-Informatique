import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ManagedUser, PageProps, RoleOption, UserRole } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    users: ManagedUser[];
    roles: RoleOption[];
    filters: {
        search?: string | null;
        role?: string | null;
        active?: string | null;
    };
    generatedPassword?: string | null;
    flashStatus?: string | null;
}

export default function Index({
    users,
    roles,
    filters,
    generatedPassword,
    flashStatus,
}: Props) {
    const { auth } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role ?? '');
    const [active, setActive] = useState(filters.active ?? '');
    const [copied, setCopied] = useState(false);

    const applyFilters = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('admin.users.index'),
            {
                search: search || undefined,
                role: role || undefined,
                active: active || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setRole('');
        setActive('');
        router.get(route('admin.users.index'));
    };

    const toggleActive = (user: ManagedUser) => {
        if (user.id === auth.user.id) return;
        const action = user.is_active ? 'désactiver' : 'réactiver';
        if (!confirm(`Confirmer : ${action} le compte « ${user.name} » ?`)) {
            return;
        }
        router.patch(
            route('admin.users.toggle-active', user.id),
            {},
            { preserveScroll: true },
        );
    };

    const resetPassword = (user: ManagedUser) => {
        if (
            !confirm(
                `Réinitialiser le mot de passe de « ${user.name} » ? Un mot de passe temporaire sera affiché une fois.`,
            )
        ) {
            return;
        }
        router.post(
            route('admin.users.reset-password', user.id),
            {},
            { preserveScroll: true },
        );
    };

    const copyPassword = async () => {
        if (!generatedPassword) return;
        await navigator.clipboard.writeText(generatedPassword);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const roleLabel = (value: UserRole) =>
        roles.find((r) => r.value === value)?.label ?? value;

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Utilisateurs"
                    description="Créez et gérez les comptes, rôles et accès IT."
                    actions={
                        <Link
                            href={route('admin.users.create')}
                            className="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-strong ui-focus"
                        >
                            Nouveau compte
                        </Link>
                    }
                />
            }
        >
            <Head title="Utilisateurs" />

            <div className="mx-auto max-w-7xl space-y-4">
                {(flashStatus || generatedPassword) && (
                    <div className="rounded-lg border border-brand/30 bg-brand-soft/40 px-4 py-3 text-sm text-ink">
                        {flashStatus && <p>{flashStatus}</p>}
                        {generatedPassword && (
                            <div className="mt-2 flex flex-wrap items-center gap-3">
                                <code className="rounded bg-surface px-2 py-1 font-mono text-sm">
                                    {generatedPassword}
                                </code>
                                <button
                                    type="button"
                                    onClick={copyPassword}
                                    className="text-sm font-medium text-brand hover:text-brand-strong"
                                >
                                    {copied ? 'Copié' : 'Copier'}
                                </button>
                                <span className="text-xs text-ink-muted">
                                    Notez-le maintenant — il ne sera plus
                                    réaffiché.
                                </span>
                            </div>
                        )}
                    </div>
                )}

                <FilterBar onSubmit={applyFilters} onReset={resetFilters}>
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nom ou e-mail…"
                        className="ui-input"
                    />
                    <select
                        value={role}
                        onChange={(e) => setRole(e.target.value)}
                        className="ui-input"
                    >
                        <option value="">Tous les rôles</option>
                        {roles.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={active}
                        onChange={(e) => setActive(e.target.value)}
                        className="ui-input"
                    >
                        <option value="">Tous les états</option>
                        <option value="1">Actifs</option>
                        <option value="0">Désactivés</option>
                    </select>
                </FilterBar>

                <Surface padding={false} className="overflow-hidden">
                    {users.length === 0 ? (
                        <EmptyState
                            title="Aucun utilisateur."
                            description="Créez un compte ou assouplissez les filtres."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line">
                                <thead className="bg-surface-muted">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Nom
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            E-mail
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Entité
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Rôle
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Charge
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            État
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line bg-surface">
                                    {users.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="hover:bg-surface-muted/60"
                                        >
                                            <td className="px-6 py-4 text-sm font-medium text-ink">
                                                {user.name}
                                                {user.id === auth.user.id && (
                                                    <span className="ms-2 text-xs text-ink-muted">
                                                        (vous)
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink-muted">
                                                {user.email}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink-muted">
                                                {user.entity?.name ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink">
                                                {roleLabel(user.role)}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-ink-muted">
                                                {user.role === 'user'
                                                    ? '—'
                                                    : `${user.open_tickets_count ?? 0} ticket(s)`}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <Badge
                                                    label={
                                                        user.is_active
                                                            ? 'Actif'
                                                            : 'Désactivé'
                                                    }
                                                    color={
                                                        user.is_active
                                                            ? 'green'
                                                            : 'gray'
                                                    }
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex flex-wrap justify-end gap-2">
                                                    <Link
                                                        href={route(
                                                            'admin.users.edit',
                                                            user.id,
                                                        )}
                                                        className="font-medium text-brand hover:text-brand-strong"
                                                    >
                                                        Modifier
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            resetPassword(user)
                                                        }
                                                        className="font-medium text-ink-muted hover:text-ink"
                                                    >
                                                        MDP
                                                    </button>
                                                    {user.id !==
                                                        auth.user.id && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                toggleActive(
                                                                    user,
                                                                )
                                                            }
                                                            className="font-medium text-ink-muted hover:text-ink"
                                                        >
                                                            {user.is_active
                                                                ? 'Désactiver'
                                                                : 'Activer'}
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
