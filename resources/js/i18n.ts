import { createI18n } from 'vue-i18n';
import es from './locales/es.json';
import en from './locales/en.json';

export const SUPPORTED_LOCALES = ['es', 'en'] as const;
export type AppLocale = (typeof SUPPORTED_LOCALES)[number];

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: 'es',
    fallbackLocale: 'es',
    messages: { es, en },
});

/** Cambia el idioma activo de la interfaz (validado contra los soportados). */
export function setLocale(locale: string | undefined | null): void {
    if (locale && (SUPPORTED_LOCALES as readonly string[]).includes(locale)) {
        i18n.global.locale.value = locale as AppLocale;
        document.documentElement.setAttribute('lang', locale);
    }
}
