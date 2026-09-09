import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ManagedUserForm, RoleOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface EntityOption {
    id: number;
    name: string;
}

interface Props {
    user: ManagedUserForm | null;
    roles: RoleOption[];
    entities: EntityOption[];
}

export default function Form({ user, roles, entities }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        role: user?.role ?? 'technician',
        entity_id: user?.entity_id?.toString() ?? entities[0]?.id?.toString() ?? '',
        is_active: user?.is_active ?? true,
        password: '',
    });

    const selectedRoleLabel =
        roles.find((role) => role.value === data.role)?.label ?? data.role;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (user) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    const title = user ? `Modifier — ${user.name}` : 'Nouveau compte';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={title}
                    description={
                        user
                            ? 'Mettez à jour le profil, le rôle et l’état du compte.'
                            : 'Choisissez le rôle : l’utilisateur le verra dans son interface après connexion.'
                    }
                />
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-3xl">
                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="rounded-lg border border-brand/20 bg-brand-soft/30 px-4 py-3 text-sm">
                            <p className="text-ink-muted">Aperçu après connexion</p>
                            <p className="mt-1 font-medium text-ink">
                                {data.name.trim() || 'Nom du compte'}
                                <span className="mx-2 text-ink-muted">·</span>
                                <span className="text-brand-strong">
                                    {selectedRoleLabel}
                                </span>
                            </p>
                        </div>

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="name" value="Nom *" />
                                <TextInput
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    className="mt-1"
                                    message={errors.name}
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="email" value="E-mail *" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    className="mt-1"
                                    message={errors.email}
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="role" value="Rôle *" />
                                <select
                                    id="role"
                                    className="ui-input mt-1"
                                    value={data.role}
                                    onChange={(e) =>
                                        setData(
                                            'role',
                                            e.target.value as typeof data.role,
                                        )
                                    }
                                >
                                    {roles.map((role) => (
                                        <option
                                            key={role.value}
                                            value={role.value}
                                        >
                                            {role.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.role}
                                />
                                <p className="mt-1 text-xs text-ink-muted">
                                    Ce rôle définit les droits et s’affiche à
                                    côté du nom après connexion.
                                </p>
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="entity_id"
                                    value="Entité *"
                                />
                                <select
                                    id="entity_id"
                                    className="ui-input mt-1"
                                    value={data.entity_id}
                                    onChange={(e) =>
                                        setData('entity_id', e.target.value)
                                    }
                                    required
                                >
                                    {entities.map((entity) => (
                                        <option
                                            key={entity.id}
                                            value={entity.id}
                                        >
                                            {entity.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.entity_id}
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
                                    <option value="0">Désactivé</option>
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={errors.is_active}
                                />
                            </div>

                            {!user && (
                                <div>
                                    <InputLabel
                                        htmlFor="password"
                                        value="Mot de passe (optionnel)"
                                    />
                                    <TextInput
                                        id="password"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        placeholder="Généré automatiquement si vide"
                                    />
                                    <InputError
                                        className="mt-1"
                                        message={errors.password}
                                    />
                                    <p className="mt-1 text-xs text-ink-muted">
                                        Laissez vide pour générer un mot de passe
                                        temporaire affiché une seule fois.
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <PrimaryButton disabled={processing}>
                                {user ? 'Enregistrer' : 'Créer le compte'}
                            </PrimaryButton>
                            <Link href={route('admin.users.index')}>
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
