import { ref, computed, type Ref } from 'vue';

export type SortDirection = 'asc' | 'desc' | null;

/**
 * Orden client-side. Ciclo por columna: asc -> desc -> sin orden (3er click).
 * Comparacion con localeCompare('es', { numeric: true }).
 */
export function useSortable<T extends Record<string, any>>(rows: Ref<T[]>) {
    const sortKey = ref<string | null>(null);
    const sortDir = ref<SortDirection>(null);

    function toggle(key: string) {
        if (sortKey.value !== key) {
            sortKey.value = key;
            sortDir.value = 'asc';
            return;
        }
        if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else if (sortDir.value === 'desc') {
            sortKey.value = null;
            sortDir.value = null;
        } else {
            sortDir.value = 'asc';
        }
    }

    const sorted = computed<T[]>(() => {
        if (!sortKey.value || !sortDir.value) return rows.value;

        const key = sortKey.value;
        const factor = sortDir.value === 'asc' ? 1 : -1;

        return [...rows.value].sort((a, b) => {
            const av = a[key] ?? '';
            const bv = b[key] ?? '';
            return (
                String(av).localeCompare(String(bv), 'es', { numeric: true }) *
                factor
            );
        });
    });

    return { sortKey, sortDir, toggle, sorted };
}
