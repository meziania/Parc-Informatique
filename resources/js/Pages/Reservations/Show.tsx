import Badge from '@/Components/Badge';
import InputError from '@/Components/InputError';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Surface from '@/Components/Surface';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Reservation } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    reservation: Reservation;
    canManage: boolean;
    isOwner: boolean;
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function Show({ reservation, canManage, isOwner }: Props) {
    const { flash } = usePage<PageProps>().props;
    const reviewForm = useForm({ review_note: '' });

    const canReview =
        canManage && reservation.status === 'pending';
    const canCancel =
        (isOwner || canManage) &&
        ['pending', 'approved'].includes(reservation.status);

    const approve = (e: FormEvent) => {
        e.preventDefault();
        reviewForm.post(route('reservations.approve', reservation.id), {
            preserveScroll: true,
        });
    };

    const reject = (e: FormEvent) => {
        e.preventDefault();
        reviewForm.post(route('reservations.reject', reservation.id), {
            preserveScroll: true,
        });
    };

    const cancel = () => {
        if (confirm('Annuler cette réservation ?')) {
            router.post(route('reservations.cancel', reservation.id), {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={reservation.title}
                    description="Détail de la réservation"
                    actions={
                        canCancel ? (
                            <SecondaryButton type="button" onClick={cancel}>
                                Annuler
                            </SecondaryButton>
                        ) : undefined
                    }
                />
            }
        >
            <Head title={reservation.title} />

            <div className="mx-auto max-w-3xl space-y-6">
                {flash?.status && (
                    <p className="rounded-lg border border-ok/30 bg-emerald-50 px-4 py-3 text-sm text-ok">
                        {flash.status}
                    </p>
                )}

                <Surface padding={false} className="overflow-hidden">
                    <div className="flex flex-wrap items-center gap-3 border-b border-line/70 bg-surface-muted/50 px-6 py-4">
                        <Badge
                            label={reservation.status_label}
                            color={reservation.status_color}
                        />
                    </div>
                    <dl className="divide-y divide-line/70">
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Ressource
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {reservation.resource_label}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Demandeur
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {reservation.user?.name ?? '—'}
                                {reservation.user?.email
                                    ? ` (${reservation.user.email})`
                                    : ''}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Début
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {formatDateTime(reservation.starts_at)}
                            </dd>
                        </div>
                        <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt className="text-sm font-medium text-ink-muted">
                                Fin
                            </dt>
                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                {formatDateTime(reservation.ends_at)}
                            </dd>
                        </div>
                        {reservation.purpose && (
                            <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt className="text-sm font-medium text-ink-muted">
                                    Motif
                                </dt>
                                <dd className="mt-1 whitespace-pre-wrap text-sm text-ink sm:col-span-2 sm:mt-0">
                                    {reservation.purpose}
                                </dd>
                            </div>
                        )}
                        {reservation.review_note && (
                            <div className="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt className="text-sm font-medium text-ink-muted">
                                    Note IT
                                </dt>
                                <dd className="mt-1 whitespace-pre-wrap text-sm text-ink sm:col-span-2 sm:mt-0">
                                    {reservation.review_note}
                                    {reservation.reviewer
                                        ? ` — ${reservation.reviewer.name}`
                                        : ''}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Surface>

                {canReview && (
                    <Surface>
                        <h2 className="text-base font-semibold text-ink">
                            Validation
                        </h2>
                        <form className="mt-4 space-y-4">
                            <div>
                                <textarea
                                    value={reviewForm.data.review_note}
                                    onChange={(e) =>
                                        reviewForm.setData(
                                            'review_note',
                                            e.target.value,
                                        )
                                    }
                                    rows={2}
                                    className="ui-input"
                                    placeholder="Note (optionnelle)…"
                                />
                                <InputError
                                    message={reviewForm.errors.review_note}
                                    className="mt-1"
                                />
                                {(
                                    reviewForm.errors as Record<
                                        string,
                                        string | undefined
                                    >
                                ).starts_at && (
                                    <InputError
                                        message={
                                            (
                                                reviewForm.errors as Record<
                                                    string,
                                                    string | undefined
                                                >
                                            ).starts_at
                                        }
                                        className="mt-1"
                                    />
                                )}
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <PrimaryButton
                                    type="button"
                                    disabled={reviewForm.processing}
                                    onClick={approve}
                                >
                                    Approuver
                                </PrimaryButton>
                                <SecondaryButton
                                    type="button"
                                    disabled={reviewForm.processing}
                                    onClick={reject}
                                >
                                    Refuser
                                </SecondaryButton>
                            </div>
                        </form>
                    </Surface>
                )}

                <Link
                    href={route('reservations.index')}
                    className="text-sm font-medium text-brand hover:text-brand-strong"
                >
                    &larr; Retour aux réservations
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
