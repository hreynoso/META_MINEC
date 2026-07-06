import { ref, computed, type Ref } from 'vue';

/**
 * Busqueda por tokens: divide el termino en palabras y exige que TODAS
 * aparezcan en el registro (matchesAllTokens). Espejo del helper backend TokenSearch.
 */
export function matchesAllTokens(
    haystack: string,
    term: string,
): boolean {
    const tokens = term.trim().toLowerCase().split(/\s+/).filter(Boolean);
    if (tokens.length === 0) return true;
    const h = haystack.toLowerCase();
    return tokens.every((t) => h.includes(t));
}

export function useTokenSearch<T extends Record<string, any>>(
    rows: Ref<T[]>,
    fields: (keyof T)[],
) {
    const query = ref('');

    const filtered = computed<T[]>(() => {
        if (!query.value.trim()) return rows.value;
        return rows.value.filter((row) => {
            const haystack = fields
                .map((f) => String(row[f] ?? ''))
                .join(' ');
            return matchesAllTokens(haystack, query.value);
        });
    });

    return { query, filtered };
}
