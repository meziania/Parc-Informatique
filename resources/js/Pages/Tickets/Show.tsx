import Attachments from '@/Components/Attachments';
import Badge from '@/Components/Badge';
import AiStatusNote from '@/Components/AiStatusNote';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { postJson } from '@/lib/http';
import { Option, PageProps, Ticket, TicketUser } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, ReactNode, useState } from 'react';

interface ResolutionAssist {
    summary: string;
    checklist: string[];
    draft_solution: string;
    similar_tickets: {
        id: number;
        number: string;
        title: string;
        solution: string | null;
    }[];
    faq_suggestions: {
        id: number;
        title: string;
        category_label: string;
    }[];
    provider: string;
}

interface FaqDraft {
    title: string;
    body: string;
    category: string;
    rationale: string;
    provider: string;
}

interface Props {
    ticket: Ticket;
    technicians: TicketUser[];
    suggested_assignee?: {
        id: number;
        name: string;
        open_tickets_count: number;
        reason: string;
    } | null;
    canManage: boolean;
    isRequester: boolean;
    canRateSatisfaction: boolean;
    aiEnabled: boolean;
    aiLlmEnabled: boolean;
    faqCategories: Option[];
}

const satisfactionChoices = [
    { value: 1, label: '1 — Très insatisfait' },
    { value: 2, label: '2 — Insatisfait' },
    { value: 3, label: '3 — Neutre' },
    { value: 4, label: '4 — Satisfait' },
    { value: 5, label: '5 — Très satisfait' },
];

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function SectionTitle({ children }: { children: ReactNode }) {
    return (
        <h3 className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
            {children}
        </h3>
    );
}

export default function Show({
    ticket,
    technicians,
    suggested_assignee = null,
    canManage,
    isRequester,
    canRateSatisfaction,
    aiEnabled,
    aiLlmEnabled,
    faqCategories,
}: Props) {
    const { flash } = usePage<PageProps & { flash?: { status?: string } }>()
        .props;
    const assignForm = useForm({ assignee_id: ticket.assignee?.id ?? '' });
    const resolveForm = useForm({ solution: '' });
    const commentForm = useForm({ body: '' });
    const satisfactionForm = useForm({
        satisfaction_rating: '' as string | number,
        satisfaction_comment: '',
    });
    const faqForm = useForm({
        title: '',
        body: '',
        category: 'other',
        is_published: false as boolean,
    });
    const taskForm = useForm({ title: '' });
    const approvalForm = useForm({ approval_note: '' });

    const [aiLoading, setAiLoading] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [assist, setAssist] = useState<ResolutionAssist | null>(null);

    const [faqLoading, setFaqLoading] = useState(false);
    const [faqError, setFaqError] = useState<string | null>(null);
    const [faqDraft, setFaqDraft] = useState<FaqDraft | null>(null);

    const approvalBlocked =
        ticket.approval_status === 'pending' ||
        ticket.approval_status === 'rejected';

    const canAssign =
        canManage &&
        !approvalBlocked &&
        ['new', 'assigned'].includes(ticket.status);
    const canStart =
        canManage &&
        !approvalBlocked &&
        ['new', 'assigned'].includes(ticket.status);
    const canResolve =
        canManage &&
        !approvalBlocked &&
        ['assigned', 'in_progress'].includes(ticket.status);
    const canClose =
        (canManage || isRequester) && ticket.status === 'resolved';
    const canReopen =
        (canManage || isRequester) &&
        ['resolved', 'closed'].includes(ticket.status);
    const canProposeFaq =
        canManage &&
        aiEnabled &&
        !!ticket.solution &&
        ['resolved', 'closed'].includes(ticket.status);

    const runResolutionAssist = async () => {
        setAiError(null);
        setAiLoading(true);

        try {
            const response = await postJson<{
                assist: ResolutionAssist;
            }>(route('ai.ticket-resolution-assist', ticket.id), {});

            setAssist(response.assist);
        } catch (error) {
            setAssist(null);
            setAiError(
                error instanceof Error
                    ? error.message
                    : "Impossible d'obtenir l'aide IA.",
            );
        } finally {
            setAiLoading(false);
        }
    };

    const insertDraftSolution = () => {
        if (!assist?.draft_solution) {
            return;
        }

        resolveForm.setData('solution', assist.draft_solution);
    };

    const runFaqDraft = async () => {
        setFaqError(null);
        setFaqLoading(true);

        try {
            const response = await postJson<{ draft: FaqDraft }>(
                route('ai.ticket-faq-draft', ticket.id),
                {},
            );

            setFaqDraft(response.draft);
            faqForm.setData('title', response.draft.title);
            faqForm.setData('body', response.draft.body);
            faqForm.setData('category', response.draft.category);
            faqForm.setData('is_published', false);
        } catch (error) {
            setFaqDraft(null);
            setFaqError(
                error instanceof Error
                    ? error.message
                    : 'Impossible de générer la FAQ.',
            );
        } finally {
            setFaqLoading(false);
        }
    };

    const submitFaqDraft = (e: FormEvent) => {
        e.preventDefault();
        faqForm.post(route('tickets.faq.store', ticket.id));
    };

    const submitAssign = (e: FormEvent) => {
        e.preventDefault();
        assignForm.post(route('tickets.assign', ticket.id), {
            preserveScroll: true,
        });
    };

    const submitResolve = (e: FormEvent) => {
        e.preventDefault();
        resolveForm.post(route('tickets.resolve', ticket.id), {
            preserveScroll: true,
            onSuccess: () => resolveForm.reset(),
        });
    };

    const submitComment = (e: FormEvent) => {
        e.preventDefault();
        commentForm.post(route('tickets.comments.store', ticket.id), {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

    const simpleAction = (name: 'start' | 'close' | 'reopen') => {
        router.post(route(`tickets.${name}`, ticket.id), {}, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title={ticket.title}
                    description={ticket.number}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Badge label={ticket.type_label} color="gray" />
                            <Badge
                                label={ticket.priority_label}
                                color={ticket.priority_color}
                            />
                            <Badge
                                label={ticket.status_label}
                                color={ticket.status_color}
                            />
                            {ticket.approval_status &&
                                ticket.approval_label && (
                                    <Badge
                                        label={ticket.approval_label}
                                        color={
                                            ticket.approval_color ?? 'gray'
                                        }
                                    />
                                )}
                            <Badge
                                label={ticket.sla_label}
                                color={ticket.sla_color}
                            />
                        </div>
                    }
                />
            }
        >
            <Head title={`${ticket.number} — ${ticket.title}`} />

            <div className="mx-auto mb-4 max-w-7xl space-y-4">
                {flash?.status && (
                    <div className="rounded-lg border border-brand/30 bg-brand-soft/40 px-4 py-3 text-sm text-ink">
                        {flash.status}
                    </div>
                )}

                {canRateSatisfaction && (
                    <Surface>
                        <SectionTitle>Votre satisfaction</SectionTitle>
                        <p className="mt-2 text-sm text-ink-muted">
                            Le ticket est traité. Notez la qualité de la prise en
                            charge (1 à 5).
                        </p>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                satisfactionForm.post(
                                    route('tickets.satisfaction', ticket.id),
                                    { preserveScroll: true },
                                );
                            }}
                            className="mt-4 space-y-4"
                        >
                            <div>
                                <InputLabel
                                    htmlFor="satisfaction_rating"
                                    value="Note *"
                                />
                                <select
                                    id="satisfaction_rating"
                                    className="ui-input mt-1"
                                    value={
                                        satisfactionForm.data
                                            .satisfaction_rating
                                    }
                                    onChange={(e) =>
                                        satisfactionForm.setData(
                                            'satisfaction_rating',
                                            e.target.value,
                                        )
                                    }
                                    required
                                >
                                    <option value="">Choisir…</option>
                                    {satisfactionChoices.map((choice) => (
                                        <option
                                            key={choice.value}
                                            value={choice.value}
                                        >
                                            {choice.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1"
                                    message={
                                        satisfactionForm.errors
                                            .satisfaction_rating
                                    }
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="satisfaction_comment"
                                    value="Commentaire (optionnel)"
                                />
                                <textarea
                                    id="satisfaction_comment"
                                    className="ui-input mt-1"
                                    rows={3}
                                    value={
                                        satisfactionForm.data
                                            .satisfaction_comment
                                    }
                                    onChange={(e) =>
                                        satisfactionForm.setData(
                                            'satisfaction_comment',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Ce qui a bien fonctionné, ou à améliorer…"
                                />
                                <InputError
                                    className="mt-1"
                                    message={
                                        satisfactionForm.errors
                                            .satisfaction_comment
                                    }
                                />
                            </div>
                            <PrimaryButton
                                disabled={
                                    satisfactionForm.processing ||
                                    satisfactionForm.data
                                        .satisfaction_rating === ''
                                }
                            >
                                Envoyer mon avis
                            </PrimaryButton>
                        </form>
                    </Surface>
                )}

                {ticket.satisfaction_rating !== null &&
                    ticket.satisfaction_rating !== undefined && (
                        <Surface>
                            <SectionTitle>Satisfaction</SectionTitle>
                            <p className="mt-2 text-sm font-medium text-ink">
                                {ticket.satisfaction_rating}/5
                                {ticket.satisfaction_label
                                    ? ` — ${ticket.satisfaction_label}`
                                    : ''}
                            </p>
                            {ticket.satisfaction_comment && (
                                <p className="mt-2 whitespace-pre-wrap text-sm text-ink-muted">
                                    {ticket.satisfaction_comment}
                                </p>
                            )}
                            {ticket.satisfaction_rated_at && (
                                <p className="mt-2 text-xs text-ink-muted">
                                    Noté le{' '}
                                    {formatDateTime(ticket.satisfaction_rated_at)}
                                </p>
                            )}
                        </Surface>
                    )}
            </div>

            <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Surface>
                        <SectionTitle>Description</SectionTitle>
                        <p className="mt-3 whitespace-pre-line text-sm text-ink">
                            {ticket.description}
                        </p>
                    </Surface>

                    {ticket.solution && (
                        <Surface className="border-ok/30 bg-emerald-50/80">
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-ok">
                                Solution
                            </h3>
                            <p className="mt-3 whitespace-pre-line text-sm text-ink">
                                {ticket.solution}
                            </p>
                        </Surface>
                    )}

                    <Surface>
                        <SectionTitle>
                            Tâches (
                            {(ticket.tasks ?? []).filter((t) => t.is_done)
                                .length}
                            /{(ticket.tasks ?? []).length})
                        </SectionTitle>

                        <ul className="mt-4 space-y-2">
                            {(ticket.tasks ?? []).length === 0 && (
                                <li className="text-sm text-ink-muted">
                                    Aucune tâche pour l’instant.
                                </li>
                            )}
                            {(ticket.tasks ?? []).map((task) => (
                                <li
                                    key={task.id}
                                    className="flex items-start gap-3 rounded-lg border border-line/70 bg-surface-muted/40 px-3 py-2"
                                >
                                    {canManage ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    route(
                                                        'tickets.tasks.toggle',
                                                        [ticket.id, task.id],
                                                    ),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border ${
                                                task.is_done
                                                    ? 'border-ok bg-ok text-white'
                                                    : 'border-line bg-white'
                                            }`}
                                            aria-label={
                                                task.is_done
                                                    ? 'Marquer non faite'
                                                    : 'Marquer faite'
                                            }
                                        >
                                            {task.is_done ? '✓' : ''}
                                        </button>
                                    ) : (
                                        <span
                                            className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border text-xs ${
                                                task.is_done
                                                    ? 'border-ok bg-ok text-white'
                                                    : 'border-line bg-white'
                                            }`}
                                        >
                                            {task.is_done ? '✓' : ''}
                                        </span>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <p
                                            className={`text-sm ${
                                                task.is_done
                                                    ? 'text-ink-muted line-through'
                                                    : 'text-ink'
                                            }`}
                                        >
                                            {task.title}
                                        </p>
                                        <div className="mt-0.5 flex flex-wrap gap-x-3 text-xs text-ink-muted">
                                            {task.assignee && (
                                                <span>{task.assignee.name}</span>
                                            )}
                                            {task.due_at && (
                                                <span>
                                                    Échéance{' '}
                                                    {formatDate(task.due_at)}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    {canManage && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        'Supprimer cette tâche ?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        route(
                                                            'tickets.tasks.destroy',
                                                            [
                                                                ticket.id,
                                                                task.id,
                                                            ],
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                            className="text-xs font-medium text-danger hover:text-red-800"
                                        >
                                            ×
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>

                        {canManage && (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    taskForm.post(
                                        route('tickets.tasks.store', ticket.id),
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                taskForm.reset('title'),
                                        },
                                    );
                                }}
                                className="mt-4 flex flex-wrap items-start gap-2 border-t border-line/70 pt-4"
                            >
                                <div className="min-w-48 grow">
                                    <TextInput
                                        value={taskForm.data.title}
                                        onChange={(e) =>
                                            taskForm.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Nouvelle tâche…"
                                        className="w-full"
                                    />
                                    <InputError
                                        message={taskForm.errors.title}
                                        className="mt-1"
                                    />
                                </div>
                                <PrimaryButton
                                    disabled={
                                        taskForm.processing ||
                                        taskForm.data.title.trim() === ''
                                    }
                                >
                                    Ajouter
                                </PrimaryButton>
                            </form>
                        )}
                    </Surface>

                    <Surface>
                        <SectionTitle>
                            Suivis ({ticket.comments?.length ?? 0})
                        </SectionTitle>

                        <div className="mt-4 space-y-3">
                            {ticket.comments?.length === 0 && (
                                <p className="text-sm text-ink-muted">
                                    Aucun suivi pour le moment.
                                </p>
                            )}
                            {ticket.comments?.map((comment) => (
                                <div
                                    key={comment.id}
                                    className="rounded-lg border border-line/70 bg-surface-muted/50 p-4"
                                >
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="text-sm font-medium text-ink">
                                            {comment.user?.name ??
                                                'Utilisateur supprimé'}
                                        </span>
                                        <span className="shrink-0 text-xs text-ink-muted">
                                            {formatDateTime(comment.created_at)}
                                        </span>
                                    </div>
                                    <p className="mt-2 whitespace-pre-line text-sm text-ink">
                                        {comment.body}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <form onSubmit={submitComment} className="mt-6">
                            <textarea
                                value={commentForm.data.body}
                                onChange={(e) =>
                                    commentForm.setData('body', e.target.value)
                                }
                                rows={3}
                                className="ui-input"
                                placeholder="Ajouter un suivi…"
                            />
                            <InputError
                                message={commentForm.errors.body}
                                className="mt-1"
                            />
                            <div className="mt-2 flex justify-end">
                                <PrimaryButton
                                    disabled={
                                        commentForm.processing ||
                                        commentForm.data.body.trim() === ''
                                    }
                                >
                                    Commenter
                                </PrimaryButton>
                            </div>
                        </form>
                    </Surface>

                    <Attachments
                        documents={ticket.documents ?? []}
                        documentableType="ticket"
                        documentableId={ticket.id}
                        canUpload={canManage || isRequester}
                        canDeleteAll={canManage}
                    />
                </div>

                <div className="space-y-6">
                    <Surface className="border-brand/25 bg-brand-soft/20">
                        <SectionTitle>Qui a le problème ?</SectionTitle>
                        <div className="mt-3">
                            <p className="font-display text-lg font-semibold text-ink">
                                {ticket.requester.name}
                            </p>
                            {ticket.requester.email && (
                                <p className="mt-1 text-sm text-ink-muted">
                                    {ticket.requester.email}
                                </p>
                            )}
                            <p className="mt-2 text-xs text-ink-muted">
                                Demandeur du ticket — contactez cette personne
                                pour le suivi.
                            </p>
                        </div>
                    </Surface>

                    <Surface>
                        <SectionTitle>Détails</SectionTitle>
                        <dl className="mt-4 space-y-3 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-ink-muted">Demandeur</dt>
                                <dd className="text-right font-medium text-ink">
                                    <div>{ticket.requester.name}</div>
                                    {ticket.requester.email && (
                                        <div className="text-xs font-normal text-ink-muted">
                                            {ticket.requester.email}
                                        </div>
                                    )}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-ink-muted">Technicien</dt>
                                <dd className="font-medium text-ink">
                                    {ticket.assignee?.name ?? (
                                        <span className="font-normal text-ink-muted">
                                            Non assigné
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-ink-muted">Équipement</dt>
                                <dd className="font-medium text-ink">
                                    {ticket.asset ? (
                                        <Link
                                            href={route(
                                                'assets.show',
                                                ticket.asset.id,
                                            )}
                                            className="text-brand-strong hover:text-brand"
                                        >
                                            {ticket.asset.name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-ink-muted">Créé le</dt>
                                <dd className="text-ink">
                                    {formatDate(ticket.created_at)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-ink-muted">Échéance SLA</dt>
                                <dd className="text-end text-ink">
                                    <div>
                                        {ticket.due_at
                                            ? formatDateTime(ticket.due_at)
                                            : '—'}
                                    </div>
                                    <div className="mt-1">
                                        <Badge
                                            label={ticket.sla_label}
                                            color={ticket.sla_color}
                                        />
                                    </div>
                                </dd>
                            </div>
                            {ticket.resolved_at && (
                                <div className="flex justify-between gap-3">
                                    <dt className="text-ink-muted">
                                        Résolu le
                                    </dt>
                                    <dd className="text-ink">
                                        {formatDate(ticket.resolved_at)}
                                    </dd>
                                </div>
                            )}
                            {ticket.closed_at && (
                                <div className="flex justify-between gap-3">
                                    <dt className="text-ink-muted">Clos le</dt>
                                    <dd className="text-ink">
                                        {formatDate(ticket.closed_at)}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </Surface>

                    {canManage && aiEnabled && (
                        <Surface className="border-brand/20 bg-brand-soft/20 space-y-3">
                            <SectionTitle>Assistant IA — résolution</SectionTitle>
                            <p className="text-sm text-ink-muted">
                                Résumé, checklist, tickets similaires et brouillon
                                de solution. Vous validez toujours avant
                                résolution.
                                <AiStatusNote />
                            </p>
                            <SecondaryButton
                                type="button"
                                disabled={aiLoading}
                                onClick={runResolutionAssist}
                                className="w-full"
                            >
                                {aiLoading
                                    ? 'Analyse…'
                                    : 'Analyser ce ticket'}
                            </SecondaryButton>
                            {aiError && (
                                <p className="text-sm text-red-700">{aiError}</p>
                            )}
                            {assist && (
                                <div className="space-y-3 rounded-lg border border-line bg-white p-3 text-sm">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            Résumé
                                        </p>
                                        <p className="mt-1 text-ink">
                                            {assist.summary}
                                        </p>
                                    </div>
                                    {assist.checklist.length > 0 && (
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                Checklist
                                            </p>
                                            <ol className="mt-1 list-decimal space-y-1 ps-4 text-ink">
                                                {assist.checklist.map(
                                                    (step, index) => (
                                                        <li key={index}>
                                                            {step}
                                                        </li>
                                                    ),
                                                )}
                                            </ol>
                                        </div>
                                    )}
                                    {assist.similar_tickets.length > 0 && (
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                Tickets similaires
                                            </p>
                                            <ul className="mt-1 space-y-1">
                                                {assist.similar_tickets.map(
                                                    (item) => (
                                                        <li key={item.id}>
                                                            <Link
                                                                href={route(
                                                                    'tickets.show',
                                                                    item.id,
                                                                )}
                                                                className="font-medium text-brand-strong hover:underline"
                                                            >
                                                                {item.number} —{' '}
                                                                {item.title}
                                                            </Link>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}
                                    {assist.faq_suggestions.length > 0 && (
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                                FAQ
                                            </p>
                                            <ul className="mt-1 space-y-1">
                                                {assist.faq_suggestions.map(
                                                    (article) => (
                                                        <li key={article.id}>
                                                            <Link
                                                                href={route(
                                                                    'faq.show',
                                                                    article.id,
                                                                )}
                                                                className="text-brand-strong hover:underline"
                                                                target="_blank"
                                                            >
                                                                {article.title}
                                                            </Link>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}
                                    {canResolve && (
                                        <PrimaryButton
                                            type="button"
                                            className="w-full"
                                            onClick={insertDraftSolution}
                                        >
                                            Insérer le brouillon de solution
                                        </PrimaryButton>
                                    )}
                                </div>
                            )}
                        </Surface>
                    )}

                    {canProposeFaq && (
                        <Surface className="border-brand/20 bg-brand-soft/20 space-y-3">
                            <SectionTitle>Assistant IA — fiche FAQ</SectionTitle>
                            <p className="text-sm text-ink-muted">
                                Transforme la solution du ticket en article FAQ
                                (brouillon). Vous révisez avant publication.
                                {!aiLlmEnabled && (
                                    <span className="mt-1 block text-xs">
                                        Mode local (heuristique).
                                    </span>
                                )}
                            </p>
                            <SecondaryButton
                                type="button"
                                disabled={faqLoading}
                                onClick={runFaqDraft}
                                className="w-full"
                            >
                                {faqLoading
                                    ? 'Génération…'
                                    : 'Proposer une fiche FAQ'}
                            </SecondaryButton>
                            {faqError && (
                                <p className="text-sm text-red-700">{faqError}</p>
                            )}
                            {faqDraft && (
                                <form
                                    onSubmit={submitFaqDraft}
                                    className="space-y-3 rounded-lg border border-line bg-white p-3"
                                >
                                    <p className="text-xs text-ink-muted">
                                        {faqDraft.rationale}
                                    </p>
                                    <div>
                                        <InputLabel
                                            htmlFor="faq_title"
                                            value="Titre FAQ"
                                        />
                                        <TextInput
                                            id="faq_title"
                                            value={faqForm.data.title}
                                            onChange={(e) =>
                                                faqForm.setData(
                                                    'title',
                                                    e.target.value,
                                                )
                                            }
                                            className="mt-1 w-full"
                                        />
                                        <InputError
                                            message={faqForm.errors.title}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="faq_category"
                                            value="Catégorie"
                                        />
                                        <select
                                            id="faq_category"
                                            value={faqForm.data.category}
                                            onChange={(e) =>
                                                faqForm.setData(
                                                    'category',
                                                    e.target.value,
                                                )
                                            }
                                            className="ui-input mt-1"
                                        >
                                            {faqCategories.map((category) => (
                                                <option
                                                    key={category.value}
                                                    value={category.value}
                                                >
                                                    {category.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={faqForm.errors.category}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="faq_body"
                                            value="Contenu"
                                        />
                                        <textarea
                                            id="faq_body"
                                            value={faqForm.data.body}
                                            onChange={(e) =>
                                                faqForm.setData(
                                                    'body',
                                                    e.target.value,
                                                )
                                            }
                                            rows={8}
                                            className="ui-input mt-1"
                                        />
                                        <InputError
                                            message={faqForm.errors.body}
                                            className="mt-1"
                                        />
                                    </div>
                                    <label className="flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={faqForm.data.is_published}
                                            onChange={(e) =>
                                                faqForm.setData(
                                                    'is_published',
                                                    e.target.checked,
                                                )
                                            }
                                            className="rounded border-line text-brand ui-focus"
                                        />
                                        Publier immédiatement
                                    </label>
                                    <PrimaryButton
                                        className="w-full"
                                        disabled={
                                            faqForm.processing ||
                                            faqForm.data.title.trim() === '' ||
                                            faqForm.data.body.trim() === ''
                                        }
                                    >
                                        Créer la fiche FAQ
                                    </PrimaryButton>
                                </form>
                            )}
                        </Surface>
                    )}

                    {ticket.approval_status && (
                        <Surface className="space-y-3">
                            <SectionTitle>Validation IT</SectionTitle>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    label={
                                        ticket.approval_label ??
                                        ticket.approval_status
                                    }
                                    color={ticket.approval_color ?? 'gray'}
                                />
                            </div>
                            {ticket.approver && (
                                <p className="text-sm text-ink-muted">
                                    Par {ticket.approver.name}
                                    {ticket.approved_at
                                        ? ` · ${formatDateTime(ticket.approved_at)}`
                                        : ''}
                                </p>
                            )}
                            {ticket.approval_note && (
                                <p className="whitespace-pre-wrap text-sm text-ink">
                                    {ticket.approval_note}
                                </p>
                            )}
                            {canManage &&
                                ticket.approval_status === 'pending' && (
                                    <form
                                        className="space-y-3 border-t border-line/70 pt-3"
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                        }}
                                    >
                                        <div>
                                            <InputLabel
                                                htmlFor="approval_note"
                                                value="Note (optionnel)"
                                            />
                                            <textarea
                                                id="approval_note"
                                                value={
                                                    approvalForm.data
                                                        .approval_note
                                                }
                                                onChange={(e) =>
                                                    approvalForm.setData(
                                                        'approval_note',
                                                        e.target.value,
                                                    )
                                                }
                                                rows={2}
                                                className="ui-input mt-1"
                                                placeholder="Motif ou commentaire…"
                                            />
                                            <InputError
                                                message={
                                                    approvalForm.errors
                                                        .approval_note
                                                }
                                                className="mt-1"
                                            />
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <PrimaryButton
                                                type="button"
                                                disabled={
                                                    approvalForm.processing
                                                }
                                                onClick={() =>
                                                    approvalForm.post(
                                                        route(
                                                            'tickets.approve',
                                                            ticket.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Approuver
                                            </PrimaryButton>
                                            <SecondaryButton
                                                type="button"
                                                disabled={
                                                    approvalForm.processing
                                                }
                                                onClick={() =>
                                                    approvalForm.post(
                                                        route(
                                                            'tickets.reject-approval',
                                                            ticket.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Refuser
                                            </SecondaryButton>
                                        </div>
                                    </form>
                                )}
                        </Surface>
                    )}

                    {approvalBlocked && (
                        <Surface className="border-warn/30 bg-orange-50/80">
                            <p className="text-sm text-ink">
                                Les actions de traitement sont bloquées tant que
                                la demande n’est pas validée
                                {ticket.approval_status === 'rejected'
                                    ? ' (demande refusée)'
                                    : ''}
                                .
                            </p>
                        </Surface>
                    )}

                    {(canAssign ||
                        canStart ||
                        canResolve ||
                        canClose ||
                        canReopen) && (
                        <Surface className="space-y-5">
                            <SectionTitle>Actions</SectionTitle>

                            {canAssign && (
                                <form onSubmit={submitAssign} className="space-y-3">
                                    <InputLabel
                                        htmlFor="assignee_id"
                                        value="Assigner à un technicien"
                                    />
                                    <select
                                        id="assignee_id"
                                        value={assignForm.data.assignee_id}
                                        onChange={(e) =>
                                            assignForm.setData(
                                                'assignee_id',
                                                e.target.value,
                                            )
                                        }
                                        className="ui-input mt-1"
                                    >
                                        <option value="">Choisir…</option>
                                        {technicians.map((tech) => (
                                            <option
                                                key={tech.id}
                                                value={tech.id}
                                            >
                                                {tech.name}
                                                {typeof tech.open_tickets_count ===
                                                'number'
                                                    ? ` (${tech.open_tickets_count} ouvert${tech.open_tickets_count > 1 ? 's' : ''})`
                                                    : ''}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={assignForm.errors.assignee_id}
                                        className="mt-1"
                                    />
                                    {suggested_assignee && (
                                        <div className="rounded-lg border border-brand/20 bg-brand-soft/30 p-3 text-sm">
                                            <p className="font-medium text-ink">
                                                Suggestion : {suggested_assignee.name}
                                            </p>
                                            <p className="mt-1 text-ink-muted">
                                                {suggested_assignee.reason}
                                            </p>
                                            <SecondaryButton
                                                type="button"
                                                className="mt-2 w-full"
                                                disabled={assignForm.processing}
                                                onClick={() => {
                                                    assignForm.setData(
                                                        'assignee_id',
                                                        String(suggested_assignee.id),
                                                    );
                                                    assignForm.post(
                                                        route(
                                                            'tickets.assign',
                                                            ticket.id,
                                                        ),
                                                        { preserveScroll: true },
                                                    );
                                                }}
                                            >
                                                Assignation intelligente
                                            </SecondaryButton>
                                        </div>
                                    )}
                                    <PrimaryButton
                                        className="w-full"
                                        disabled={
                                            assignForm.processing ||
                                            assignForm.data.assignee_id === ''
                                        }
                                    >
                                        Assigner
                                    </PrimaryButton>
                                </form>
                            )}

                            {canStart && (
                                <button
                                    type="button"
                                    onClick={() => simpleAction('start')}
                                    className="w-full rounded-lg bg-warn px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600 ui-focus"
                                >
                                    Prendre en charge
                                </button>
                            )}

                            {canResolve && (
                                <form onSubmit={submitResolve}>
                                    <InputLabel
                                        htmlFor="solution"
                                        value="Résoudre le ticket"
                                    />
                                    <textarea
                                        id="solution"
                                        value={resolveForm.data.solution}
                                        onChange={(e) =>
                                            resolveForm.setData(
                                                'solution',
                                                e.target.value,
                                            )
                                        }
                                        rows={3}
                                        className="ui-input mt-1"
                                        placeholder="Décrivez la solution apportée…"
                                    />
                                    <InputError
                                        message={resolveForm.errors.solution}
                                        className="mt-1"
                                    />
                                    <PrimaryButton
                                        className="mt-2 w-full bg-ok hover:bg-emerald-700"
                                        disabled={
                                            resolveForm.processing ||
                                            resolveForm.data.solution.trim() ===
                                                ''
                                        }
                                    >
                                        Marquer résolu
                                    </PrimaryButton>
                                </form>
                            )}

                            {canClose && (
                                <button
                                    type="button"
                                    onClick={() => simpleAction('close')}
                                    className="w-full rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink/90 ui-focus"
                                >
                                    Clôturer le ticket
                                </button>
                            )}

                            {canReopen && (
                                <SecondaryButton
                                    type="button"
                                    onClick={() => simpleAction('reopen')}
                                    className="w-full"
                                >
                                    Rouvrir le ticket
                                </SecondaryButton>
                            )}
                        </Surface>
                    )}

                    <Link
                        href={route('tickets.index')}
                        className="inline-flex text-sm text-brand-strong hover:text-brand"
                    >
                        ← Retour aux tickets
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
