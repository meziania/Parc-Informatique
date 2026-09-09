import { PropsWithChildren } from 'react';

export default function Surface({
    children,
    className = '',
    padding = true,
}: PropsWithChildren<{ className?: string; padding?: boolean }>) {
    return (
        <div
            className={`rounded-panel border border-line/80 bg-surface shadow-panel ${
                padding ? 'p-5 sm:p-6' : ''
            } ${className}`}
        >
            {children}
        </div>
    );
}
