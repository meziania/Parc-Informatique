import PageHeader from '@/Components/PageHeader';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Profil"
                    description="Informations du compte, mot de passe et suppression."
                />
            }
        >
            <Head title="Profil" />

            <div className="mx-auto max-w-3xl space-y-6">
                <Surface>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </Surface>

                <Surface>
                    <UpdatePasswordForm className="max-w-xl" />
                </Surface>

                <Surface>
                    <DeleteUserForm className="max-w-xl" />
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
