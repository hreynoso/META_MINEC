/**
 * Formato y opciones compartidas del dominio de KPIs (indicadores de gestión).
 * La paleta institucional sigue siendo teal / cyan / sky / amber (morado prohibido).
 */

/** Etiquetas legibles de tendencia. */
export const TREND_LABEL: Record<string, string> = {
    up: 'Al alza',
    down: 'A la baja',
    flat: 'Estable',
};

/** Opciones de tendencia para selects, en orden de presentación. */
export const TREND_OPTIONS: { value: string; label: string }[] = [
    { value: 'up', label: 'Al alza' },
    { value: 'down', label: 'A la baja' },
    { value: 'flat', label: 'Estable' },
];

/** Color del texto/ícono según la tendencia. */
export function trendClass(trend: string): string {
    const map: Record<string, string> = {
        up: 'text-teal-600 dark:text-teal-400',
        down: 'text-red-600 dark:text-red-400',
        flat: 'text-slate-400',
    };

    return map[trend] ?? map.flat;
}

/** Color de la barra de logro según el % alcanzado de la meta (0–100+). */
export function achievementBarClass(pct: number): string {
    if (pct >= 100) return 'bg-sky-500';
    if (pct >= 60) return 'bg-teal-500';
    if (pct >= 30) return 'bg-cyan-500';

    return 'bg-amber-500';
}
