import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Mot de passe oublié" />

            <h1 className="font-display text-xl font-semibold text-ink">
                Mot de passe oublié
            </h1>
            <p className="mt-2 text-sm text-ink-muted">
                Indiquez votre adresse e-mail et nous vous enverrons un lien pour
                en choisir un nouveau.
            </p>

            {status && (
                <div className="mt-4 text-sm font-medium text-ok">{status}</div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1"
                    isFocused
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="vous@organisation.local"
                />
                <InputError message={errors.email} className="mt-2" />

                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={route('login')}
                        className="text-sm text-ink-muted underline underline-offset-2 hover:text-ink"
                    >
                        Retour
                    </Link>
                    <PrimaryButton disabled={processing}>
                        Envoyer le lien
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
