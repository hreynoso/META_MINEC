@import 'tailwindcss';

/* Tailwind 4 config CSS-first (sin tailwind.config.js). Dark mode por clase .dark */
@custom-variant dark (&:where(.dark, .dark *));

@theme {
    /* Paleta institucional META: teal / cyan / sky / amber (morado PROHIBIDO) */
    --color-brand: #0d9488;        /* teal-600 */
    --color-brand-600: #0d9488;
    --color-brand-700: #0f766e;
    --color-brand-accent: #0891b2; /* cyan-600 */
    --color-brand-info: #0284c7;   /* sky-600 */
    --color-brand-warning: #d97706;/* amber-600 */

    /* Shell oscuro del sidebar */
    --color-shell: #0f172a;        /* slate-900 */
    --color-shell-hover: #1e293b;  /* slate-800 */

    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* Barra de progreso de Inertia */
#nprogress .bar { background: #0d9488 !important; }

html { scroll-behavior: smooth; }
