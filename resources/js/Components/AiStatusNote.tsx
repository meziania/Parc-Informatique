import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export default function AiStatusNote({ className = '' }: { className?: string }) {
    const { ai } = usePage<PageProps>().props;

    if (!ai?.enabled) {
        return (
            <span className={`mt-1 block text-xs text-ink-muted ${className}`}>
                Assistant IA désactivé (<code className="rounded bg-white/80 px-1">AI_ENABLED</code>).
            </span>
        );
    }

    if (ai.mode === 'ollama' || ai.mode === 'llm') {
        return (
            <span className={`mt-1 block text-xs text-ok ${className}`}>
                {ai.message}
                {ai.latency_ms != null ? ` (${ai.latency_ms} ms)` : ''}
            </span>
        );
    }

    return (
        <span className={`mt-1 block text-xs text-ink-muted ${className}`}>
            {ai.message}
            {ai.hint && (
                <span className="mt-1 block font-mono text-[11px] text-ink">
                    {ai.hint}
                </span>
            )}
        </span>
    );
}
