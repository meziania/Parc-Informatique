import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
            <div
                className="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_20%,#1a4d5c_0%,transparent_50%),radial-gradient(ellipse_at_80%_10%,#0f766e_0%,transparent_40%),linear-gradient(160deg,#0b1620_0%,#122433_45%,#0d1f28_100%)]"
                aria-hidden
            />
            <div className="relative z-10 w-full max-w-md slide-up">
                <div className="mb-6 text-center">
                    <Link
                        href="/"
                        className="font-display text-2xl font-semibold tracking-tight text-white"
                    >
                        Parc Informatique
                    </Link>
                    <p className="mt-2 text-sm text-slate-300">
                        Support IT et gestion du parc
                    </p>
                </div>
                <div className="rounded-panel border border-white/10 bg-white p-6 shadow-panel sm:p-8">
                    {children}
                </div>
            </div>
        </div>
    );
}
