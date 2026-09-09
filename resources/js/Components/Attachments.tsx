import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import Surface from '@/Components/Surface';
import { DocumentFile } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useRef } from 'react';

interface Props {
    documents: DocumentFile[];
    documentableType: 'asset' | 'ticket';
    documentableId: number;
    canUpload: boolean;
    canDeleteAll?: boolean;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function Attachments({
    documents,
    documentableType,
    documentableId,
    canUpload,
    canDeleteAll = false,
}: Props) {
    const { auth } = usePage().props as { auth: { user: { id: number } } };
    const fileRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm<{
            documentable_type: 'asset' | 'ticket';
            documentable_id: number;
            file: File | null;
        }>({
            documentable_type: documentableType,
            documentable_id: documentableId,
            file: null,
        });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!data.file) return;

        post(route('documents.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('file');
                clearErrors();
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    };

    const destroy = (document: DocumentFile) => {
        if (!confirm(`Supprimer « ${document.original_name} » ?`)) {
            return;
        }

        router.delete(route('documents.destroy', document.id), {
            preserveScroll: true,
        });
    };

    return (
        <Surface>
            <h3 className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                Documents ({documents.length})
            </h3>

            {documents.length === 0 ? (
                <p className="mt-4 text-sm text-ink-muted">
                    Aucun document attaché.
                </p>
            ) : (
                <ul className="mt-4 divide-y divide-line">
                    {documents.map((document) => {
                        const canDelete =
                            canDeleteAll ||
                            document.uploader?.id === auth.user.id;

                        return (
                            <li
                                key={document.id}
                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                            >
                                <div className="min-w-0">
                                    <a
                                        href={route(
                                            'documents.download',
                                            document.id,
                                        )}
                                        className="font-medium text-brand-strong hover:text-brand"
                                    >
                                        {document.original_name}
                                    </a>
                                    <div className="text-xs text-ink-muted">
                                        {document.human_size}
                                        {document.uploader
                                            ? ` · ${document.uploader.name}`
                                            : ''}
                                        {' · '}
                                        {formatDate(document.created_at)}
                                    </div>
                                </div>
                                <div className="flex gap-3 text-sm">
                                    <a
                                        href={route(
                                            'documents.download',
                                            document.id,
                                        )}
                                        className="text-brand-strong hover:text-brand"
                                    >
                                        Télécharger
                                    </a>
                                    {canDelete && (
                                        <button
                                            type="button"
                                            onClick={() => destroy(document)}
                                            className="text-danger hover:text-red-700"
                                        >
                                            Supprimer
                                        </button>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {canUpload && (
                <form
                    onSubmit={submit}
                    className="mt-5 border-t border-line pt-5"
                >
                    <label className="block text-sm font-medium text-ink">
                        Ajouter un fichier
                    </label>
                    <p className="mt-1 text-xs text-ink-muted">
                        PDF, images, Office, TXT, CSV ou ZIP — 10 Mo max.
                    </p>
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <input
                            ref={fileRef}
                            type="file"
                            onChange={(e) =>
                                setData('file', e.target.files?.[0] ?? null)
                            }
                            className="block w-full max-w-md text-sm text-ink-muted file:me-3 file:rounded-lg file:border-0 file:bg-brand-soft file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-strong hover:file:bg-teal-100"
                        />
                        <PrimaryButton
                            disabled={processing || data.file === null}
                        >
                            Envoyer
                        </PrimaryButton>
                    </div>
                    <InputError message={errors.file} className="mt-2" />
                </form>
            )}
        </Surface>
    );
}
