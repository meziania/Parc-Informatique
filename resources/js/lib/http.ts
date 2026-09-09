function readCookie(name: string): string | null {
    const match = document.cookie.match(
        new RegExp(
            `(?:^|; )${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`,
        ),
    );

    return match ? decodeURIComponent(match[1]) : null;
}

/**
 * Laravel lit d'abord X-CSRF-TOKEN, puis X-XSRF-TOKEN.
 * Ne pas envoyer un meta CSRF périmé en même temps : il a priorité et provoque un 419.
 */
function csrfHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
        'X-Requested-With': 'XMLHttpRequest',
    };

    const fromCookie = readCookie('XSRF-TOKEN');
    if (fromCookie) {
        headers['X-XSRF-TOKEN'] = fromCookie;
        return headers;
    }

    const fromMeta =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    if (fromMeta) {
        headers['X-CSRF-TOKEN'] = fromMeta;
    }

    return headers;
}

/** Garde le <meta csrf-token> synchronisé après chaque navigation Inertia. */
export function syncCsrfMeta(token: string | undefined | null): void {
    if (!token) {
        return;
    }

    document
        .querySelector('meta[name="csrf-token"]')
        ?.setAttribute('content', token);
}

export async function postJson<T>(
    url: string,
    body: Record<string, unknown> = {},
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...csrfHeaders(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        if (response.status === 419) {
            throw new Error(
                'Session expirée (419). Rechargez la page (F5) puis réessayez.',
            );
        }

        const message =
            payload?.message ||
            payload?.errors?.message?.[0] ||
            `Erreur ${response.status}`;
        throw new Error(message);
    }

    return payload as T;
}
