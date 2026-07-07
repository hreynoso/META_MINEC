/**
 * Formateo y estilos compartidos del dominio de proyectos.
 * Paleta institucional META: teal / cyan / sky / amber (morado prohibido).
 */

const currencyFmt = new Intl.NumberFormat('es-SV', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const numberFmt = new Intl.NumberFormat('es-SV');

/** Valor monetario en USD sin decimales (p. ej. "$4,500,000"). */
export function currency(value: number | null | undefined): string {
    return currencyFmt.format(Number(value ?? 0));
}

/** Número con separador de miles (p. ej. "12,500"). */
export function number(value: number | null | undefined): string {
    return numberFmt.format(Number(value ?? 0));
}

/** Etiquetas legibles de estado de proyecto. */
export const STATUS_LABEL: Record<string, string> = {
    planificado: 'Planificado',
    en_ejecucion: 'En ejecución',
    en_riesgo: 'En riesgo',
    completado: 'Completado',
    retrasado: 'Retrasado',
};

/** Estados válidos para selects/filtros, en el orden de presentación. */
export const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: 'planificado', label: 'Planificado' },
    { value: 'en_ejecucion', label: 'En ejecución' },
    { value: 'en_riesgo', label: 'En riesgo' },
    { value: 'retrasado', label: 'Retrasado' },
    { value: 'completado', label: 'Completado' },
];

/** Niveles de riesgo válidos para selects. */
export const RISK_OPTIONS: { value: string; label: string }[] = [
    { value: 'bajo', label: 'Bajo' },
    { value: 'medio', label: 'Medio' },
    { value: 'alto', label: 'Alto' },
];

/** Etiquetas legibles de nivel de riesgo. */
export const RISK_LABEL: Record<string, string> = {
    bajo: 'Bajo',
    medio: 'Medio',
    alto: 'Alto',
};

/** Clases de badge según el estado del proyecto. */
export function statusClass(status: string): string {
    const map: Record<string, string> = {
        planificado: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        en_ejecucion: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
        en_riesgo: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        retrasado: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        completado: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
    };

    return map[status] ?? map.planificado;
}

/** Clases de badge según el nivel de riesgo. */
export function riskClass(risk: string): string {
    const map: Record<string, string> = {
        bajo: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
        medio: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        alto: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    };

    return map[risk] ?? map.bajo;
}

/** Color de la barra de progreso según el avance (0–100). */
export function progressBarClass(progress: number): string {
    if (progress >= 100) return 'bg-sky-500';
    if (progress >= 60) return 'bg-teal-500';
    if (progress >= 30) return 'bg-cyan-500';

    return 'bg-amber-500';
}
