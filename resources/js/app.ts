import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { ZiggyVue } from 'ziggy-js';
import { i18n, setLocale } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'META MINEC';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Idioma del sistema (Configuración → Idioma): inicial desde el backend y
        // re-sincronizado en cada navegación de Inertia (p. ej. al cambiarlo).
        setLocale((props.initialPage.props as any)?.locale);
        router.on('success', (event) => setLocale((event.detail.page.props as any)?.locale));

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark',
                    },
                },
            })
            .use(ConfirmationService)
            .use(ToastService);

        app.mount(el);
    },
    progress: {
        color: '#0d9488',
    },
});

/* Keep-alive: cada 15 min mientras la pestana este visible.
   Ante 401/419 redirige a /login. */
const KEEP_ALIVE_MS = 15 * 60 * 1000;

setInterval(() => {
    if (document.visibilityState !== 'visible') return;

    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    fetch('/keep-alive', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token ?? '',
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    }).then((res) => {
        if (res.status === 401 || res.status === 419) {
            window.location.href = '/login';
        }
    }).catch(() => {});
}, KEEP_ALIVE_MS);
