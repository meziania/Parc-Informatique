import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="Parc Informatique" />

            <div className="welcome-page relative min-h-screen overflow-hidden text-slate-100">
                <div
                    className="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_20%,#1a4d5c_0%,transparent_50%),radial-gradient(ellipse_at_80%_10%,#0f766e_0%,transparent_40%),linear-gradient(160deg,#0b1620_0%,#122433_45%,#0d1f28_100%)]"
                    aria-hidden
                />
                <div
                    className="welcome-grid absolute inset-0 opacity-[0.18]"
                    aria-hidden
                />

                <svg
                    className="welcome-visual pointer-events-none absolute inset-y-0 right-0 hidden h-full w-[58%] lg:block"
                    viewBox="0 0 720 900"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden
                >
                    <rect
                        x="80"
                        y="120"
                        width="520"
                        height="340"
                        rx="18"
                        fill="#143041"
                        stroke="#2dd4bf"
                        strokeOpacity="0.35"
                    />
                    <rect
                        x="110"
                        y="155"
                        width="200"
                        height="14"
                        rx="7"
                        fill="#2dd4bf"
                        fillOpacity="0.85"
                    />
                    <rect
                        x="110"
                        y="190"
                        width="460"
                        height="10"
                        rx="5"
                        fill="#94a3b8"
                        fillOpacity="0.35"
                    />
                    <rect
                        x="110"
                        y="215"
                        width="390"
                        height="10"
                        rx="5"
                        fill="#94a3b8"
                        fillOpacity="0.25"
                    />
                    <g>
                        <rect
                            x="110"
                            y="260"
                            width="140"
                            height="72"
                            rx="10"
                            fill="#0f766e"
                            fillOpacity="0.55"
                        />
                        <rect
                            x="270"
                            y="260"
                            width="140"
                            height="72"
                            rx="10"
                            fill="#1e3a4c"
                            stroke="#64748b"
                            strokeOpacity="0.4"
                        />
                        <rect
                            x="430"
                            y="260"
                            width="140"
                            height="72"
                            rx="10"
                            fill="#1e3a4c"
                            stroke="#64748b"
                            strokeOpacity="0.4"
                        />
                    </g>
                    <rect
                        x="160"
                        y="520"
                        width="420"
                        height="260"
                        rx="16"
                        fill="#102636"
                        stroke="#38bdf8"
                        strokeOpacity="0.25"
                    />
                    <rect
                        x="190"
                        y="555"
                        width="180"
                        height="12"
                        rx="6"
                        fill="#38bdf8"
                        fillOpacity="0.7"
                    />
                    <rect
                        x="190"
                        y="590"
                        width="360"
                        height="8"
                        rx="4"
                        fill="#64748b"
                        fillOpacity="0.35"
                    />
                    <rect
                        x="190"
                        y="615"
                        width="300"
                        height="8"
                        rx="4"
                        fill="#64748b"
                        fillOpacity="0.25"
                    />
                    <rect
                        x="190"
                        y="660"
                        width="110"
                        height="36"
                        rx="8"
                        fill="#0d9488"
                    />
                    <circle cx="560" cy="200" r="70" fill="#0d9488" fillOpacity="0.12" />
                    <circle cx="600" cy="680" r="110" fill="#38bdf8" fillOpacity="0.08" />
                                </svg>

                <div className="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8 sm:px-10 lg:px-12">
                    <header className="flex items-center justify-end">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                className="welcome-fade text-sm font-medium text-teal-200/90 transition hover:text-white"
                                style={{ animationDelay: '0.05s' }}
                                    >
                                Tableau de bord
                                    </Link>
                                ) : (
                                        <Link
                                            href={route('login')}
                                className="welcome-fade text-sm font-medium text-teal-200/90 transition hover:text-white"
                                style={{ animationDelay: '0.05s' }}
                            >
                                Connexion
                                        </Link>
                                )}
                        </header>

                    <main className="flex flex-1 flex-col justify-center py-16 lg:max-w-xl lg:py-24">
                        <p
                            className="welcome-fade font-display text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl"
                            style={{ animationDelay: '0.12s' }}
                        >
                            Parc Informatique
                        </p>
                        <h1
                            className="welcome-fade mt-6 font-display text-2xl font-semibold leading-snug text-teal-100 sm:text-3xl"
                            style={{ animationDelay: '0.28s' }}
                        >
                            Le support IT et le parc, au même endroit.
                        </h1>
                        <p
                            className="welcome-fade mt-5 max-w-md text-lg leading-relaxed text-slate-300"
                            style={{ animationDelay: '0.42s' }}
                        >
                            Déclarez un incident, suivez vos tickets et consultez
                            vos équipements en quelques clics.
                        </p>
                        <div
                            className="welcome-fade mt-10 flex flex-wrap items-center gap-4"
                            style={{ animationDelay: '0.56s' }}
                        >
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-strong"
                                >
                                    Accéder au tableau de bord
                                </Link>
                            ) : (
                                <Link
                                    href={route('login')}
                                    className="inline-flex rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-strong"
                                >
                                    Se connecter
                                </Link>
                            )}
                            </div>
                        </main>
                </div>

                <style>{`
                    .welcome-grid {
                        background-image:
                            linear-gradient(rgba(148, 163, 184, 0.15) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(148, 163, 184, 0.15) 1px, transparent 1px);
                        background-size: 48px 48px;
                        mask-image: radial-gradient(ellipse at 30% 40%, black 20%, transparent 70%);
                    }
                    .welcome-fade {
                        opacity: 0;
                        transform: translateY(14px);
                        animation: welcomeIn 0.7s ease forwards;
                    }
                    .welcome-visual {
                        opacity: 0;
                        transform: translateX(24px);
                        animation: welcomeIn 1s ease 0.2s forwards;
                    }
                    @keyframes welcomeIn {
                        to {
                            opacity: 1;
                            transform: none;
                        }
                    }
                `}</style>
            </div>
        </>
    );
}
