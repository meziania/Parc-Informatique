import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: 'var(--ink)',
                    muted: 'var(--ink-muted)',
                },
                brand: {
                    DEFAULT: 'var(--brand)',
                    strong: 'var(--brand-strong)',
                    soft: 'var(--brand-soft)',
                },
                surface: {
                    DEFAULT: 'var(--surface)',
                    muted: 'var(--surface-muted)',
                },
                mist: 'var(--mist)',
                line: 'var(--line)',
                danger: 'var(--danger)',
                warn: 'var(--warn)',
                ok: 'var(--ok)',
            },
            fontFamily: {
                sans: ['"Source Sans 3"', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                panel: 'var(--shadow)',
            },
            borderRadius: {
                panel: 'var(--radius)',
            },
        },
    },

    plugins: [forms],
};
