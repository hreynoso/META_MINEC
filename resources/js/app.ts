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

// Zona horaria del equipo del usuario: se guarda en una cookie que el backend
// lee para mostrar todas las fechas/horas en la hora local de cada dispositivo.
try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (tz) document.cookie = `tz=${tz}; path=/; max-age=31536000; SameSite=Lax`;
} catch { /* sin soporte de Intl: el backend usa la zona por defecto */ }

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

// El keep-alive y el cierre por inactividad se manejan en useIdleTimeout()
// (montado en AppLayout), que solo mantiene viva la sesión cuando hay actividad.
