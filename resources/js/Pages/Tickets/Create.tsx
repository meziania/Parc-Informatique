import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Surface from '@/Components/Surface';
import TextInput from '@/Components/TextInput';
import AiStatusNote from '@/Components/AiStatusNote';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { postJson } from '@/lib/http';
import { Asset, PriorityOption, ServiceCatalogOption, TicketTypeOption } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface FaqSuggestion {
    id: number;
    title: string;
    category_label: string;
}

interface AiSuggestion {
    title: string;
    description: string;
    type: string;
    priority: string;
    asset_id: number | null;
    rationale: string;
    faq_suggestions: FaqSuggestion[];
    provider: string;
}

interface Props {
    types: TicketTypeOption[];
    priorities: PriorityOption[];
    catalog: ServiceCatalogOption[];
    assets: Pick<Asset, 'id' | 'name' | 'inventory_number'>[];
    aiEnabled: boolean;
    aiLlmEnabled: boolean;
}

export default function Create({
    types,
    priorities,
    catalog,
    assets,
    aiEnabled,
    aiLlmEnabled,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        type: '',
        priority: 'medium',
        asset_id: '',
        service_catalog_item_id: '',
    });

    const [aiMessage, setAiMessage] = useState('');
    const [aiLoading, setAiLoading] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [suggestion, setSuggestion] = useState<AiSuggestion | null>(null);

    const selectedPriority = priorities.find((p) => p.value === data.priority);
    const selectedCatalog = catalog.find(
        (item) => String(item.id) === String(data.service_catalog_item_id),
    );

    const applyCatalog = (itemId: string) => {
        setData('service_catalog_item_id', itemId);
        const item = catalog.find((entry) => String(entry.id) === itemId);
        if (!item) {
            return;
        }
        setData('type', item.type);
        setData('priority', item.default_priority);
        if (!data.title.trim()) {
            setData('title', item.name);
        }
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('tickets.store'));
    };

    const runAi = async () => {
        setAiError(null);
        setAiLoading(true);

        try {
            const response = await postJson<{
                suggestion: AiSuggestion;
                llm_enabled: boolean;
            }>(route('ai.ticket-suggestions'), {
                message: aiMessage,
            });

            setSuggestion(response.suggestion);
            // Préremplit immédiatement le formulaire (l'utilisateur peut encore modifier)
            applySuggestion(response.suggestion);
        } catch (error) {
            setSuggestion(null);
            setAiError(
                error instanceof Error
                    ? error.message
                    : "Impossible d'obtenir une suggestion.",
            );
        } finally {
            setAiLoading(false);
        }
    };

    const applySuggestion = (source: AiSuggestion = suggestion!) => {
        if (!source) {
            return;
        }

        const description =
            source.description?.trim() ||
            aiMessage.trim() ||
            data.description;

        // setData champ par champ : plus fiable qu'un objet partiel avec Inertia
        setData('title', source.title || data.title);
        setData('description', description);
        setData('type', source.type || data.type);
        setData('priority', source.priority || data.priority);
        setData(
            'asset_id',
            source.asset_id ? String(source.asset_id) : data.asset_id,
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Nouveau ticket"
                    description="Décrivez l'incident ou la demande — un technicien prendra le relais."
                />
            }
        >
            <Head title="Nouveau ticket" />

            <div className="mx-auto max-w-3xl space-y-6">
                {aiEnabled && (
                    <Surface className="border-brand/20 bg-brand-soft/20">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="font-display text-base font-semibold text-ink">
                                    Assistant IA
                                </h2>
                                <p className="mt-1 text-sm text-ink-muted">
                                    Décrivez le problème en langage naturel.
                                    L’assistant propose type, priorité, titre et
                                    description — vous validez avant envoi.
                                    <AiStatusNote />
                                </p>
                            </div>
                        </div>

                        <textarea
                            value={aiMessage}
                            onChange={(e) => setAiMessage(e.target.value)}
                            rows={4}
                            className="ui-input mt-4"
                            placeholder="Ex : Mon PC INV-001 affiche un écran bleu depuis ce matin, je ne peux plus travailler…"
                        />

                        <div className="mt-3 flex flex-wrap items-center gap-3">
                            <SecondaryButton
                                type="button"
                                disabled={
                                    aiLoading || aiMessage.trim().length < 10
                                }
                                onClick={runAi}
                            >
                                {aiLoading
                                    ? 'Analyse…'
                                    : 'Analyser avec l’IA'}
                            </SecondaryButton>
                            {suggestion && (
                                <span className="text-xs text-ink-muted">
                                    Formulaire prérempli — vérifiez puis
                                    envoyez.
                                </span>
                            )}
                        </div>

                        {aiError && (
                            <p className="mt-3 text-sm text-red-700">{aiError}</p>
                        )}

                        {suggestion && (
                            <div className="mt-4 rounded-lg border border-line bg-white p-4 text-sm">
                                <p className="font-medium text-ink">
                                    Suggestion : {suggestion.title}
                                </p>
                                <p className="mt-1 text-ink-muted">
                                    {suggestion.rationale}
                                </p>
                                <dl className="mt-3 grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-xs text-ink-muted">
                                            Type
                                        </dt>
                                        <dd className="font-medium text-ink">
                                            {types.find(
                                                (t) =>
                                                    t.value === suggestion.type,
                                            )?.label ?? suggestion.type}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-ink-muted">
                                            Priorité
                                        </dt>
                                        <dd className="font-medium text-ink">
                                            {priorities.find(
                                                (p) =>
                                                    p.value ===
                                                    suggestion.priority,
                                            )?.label ?? suggestion.priority}
                                        </dd>
                                    </div>
                                </dl>

                                {suggestion.faq_suggestions.length > 0 && (
                                    <div className="mt-4 border-t border-line pt-3">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                            FAQ susceptibles d’aider
                                        </p>
                                        <ul className="mt-2 space-y-1">
                                            {suggestion.faq_suggestions.map(
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
                                                        <span className="ms-2 text-xs text-ink-muted">
                                                            {
                                                                article.category_label
                                                            }
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}
                    </Surface>
                )}

                <div>
                    <p className="mb-3 text-sm font-medium text-ink">
                        Catalogue de services (optionnel)
                    </p>
                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                        {catalog.map((item) => {
                            const selected =
                                String(data.service_catalog_item_id) ===
                                String(item.id);

                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => applyCatalog(String(item.id))}
                                    className={`rounded-panel border-2 bg-surface p-4 text-left shadow-panel transition ui-focus ${
                                        selected
                                            ? 'border-brand bg-brand-soft/40 ring-2 ring-brand-soft'
                                            : 'border-line hover:border-brand/40'
                                    }`}
                                >
                                    <div className="font-display text-sm font-semibold text-ink">
                                        {item.name}
                                    </div>
                                    <p className="mt-1 text-xs text-ink-muted">
                                        {item.description}
                                    </p>
                                    <p className="mt-2 text-xs font-medium text-brand-strong">
                                        {item.type_label} · {item.priority_label}{' '}
                                        · {item.sla_label}
                                    </p>
                                </button>
                            );
                        })}
                    </div>
                    {selectedCatalog && (
                        <p className="mb-4 text-xs text-ink-muted">
                            SLA catalogue appliqué : {selectedCatalog.sla_label}.
                            Type et priorité sont préremplis (modifiables).
                        </p>
                    )}
                    {selectedCatalog?.requires_approval && (
                        <p className="mb-4 rounded-lg border border-warn/30 bg-orange-50 px-3 py-2 text-xs text-ink">
                            Cette demande nécessitera une validation IT avant
                            traitement.
                        </p>
                    )}
                    <InputError
                        message={errors.service_catalog_item_id}
                        className="mb-4"
                    />
                </div>

                <div>
                    <p className="mb-3 text-sm font-medium text-ink">
                        De quoi s'agit-il ?
                    </p>
                    <div className="mb-2 grid gap-4 sm:grid-cols-2">
                        {types.map((type) => {
                            const selected = data.type === type.value;

                            return (
                                <button
                                    key={type.value}
                                    type="button"
                                    onClick={() => setData('type', type.value)}
                                    className={`rounded-panel border-2 bg-surface p-5 text-left shadow-panel transition ui-focus ${
                                        selected
                                            ? 'border-brand bg-brand-soft/40 ring-2 ring-brand-soft'
                                            : 'border-line hover:border-brand/40'
                                    }`}
                                >
                                    <div className="font-display text-base font-semibold text-ink">
                                        {type.label}
                                    </div>
                                    <p className="mt-1 text-sm text-ink-muted">
                                        {type.description}
                                    </p>
                                </button>
                            );
                        })}
                    </div>
                    <InputError message={errors.type} className="mb-4" />
                </div>

                <Surface>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="title" value="Titre" />
                            <TextInput
                                id="title"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                className="mt-1 w-full"
                                placeholder="Ex : l'écran clignote depuis la mise à jour"
                            />
                            <InputError
                                message={errors.title}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="description"
                                value="Description détaillée"
                            />
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={5}
                                className="ui-input mt-1"
                                placeholder="Décrivez le problème ou le besoin : messages d'erreur, depuis quand, ce que vous avez déjà essayé…"
                            />
                            <InputError
                                message={errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="priority"
                                    value="Priorité"
                                />
                                <select
                                    id="priority"
                                    value={data.priority}
                                    onChange={(e) =>
                                        setData('priority', e.target.value)
                                    }
                                    className="ui-input mt-1"
                                >
                                    {priorities.map((p) => (
                                        <option key={p.value} value={p.value}>
                                            {p.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.priority}
                                    className="mt-2"
                                />
                                {selectedPriority?.sla_label && (
                                    <p className="mt-2 text-xs text-ink-muted">
                                        SLA cible : résolution sous{' '}
                                        <span className="font-medium text-ink">
                                            {selectedPriority.sla_label}
                                        </span>
                                        .
                                    </p>
                                )}
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="asset_id"
                                    value="Équipement concerné (optionnel)"
                                />
                                <select
                                    id="asset_id"
                                    value={data.asset_id}
                                    onChange={(e) =>
                                        setData('asset_id', e.target.value)
                                    }
                                    className="ui-input mt-1"
                                >
                                    <option value="">Aucun</option>
                                    {assets.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.name} (
                                            {asset.inventory_number})
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.asset_id}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <PrimaryButton
                                disabled={processing || data.type === ''}
                            >
                                Envoyer le ticket
                            </PrimaryButton>
                        </div>
                    </form>
                </Surface>
            </div>
        </AuthenticatedLayout>
    );
}
