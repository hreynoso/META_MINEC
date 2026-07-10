<script setup lang="ts">
import { ref, computed, useSlots } from 'vue';
import { useI18n } from 'vue-i18n';
import { Columns3, FileSpreadsheet, Filter, Search } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

export interface GridColumn { key: string; label: string; visible: boolean }

const props = withDefaults(defineProps<{
    total: number;
    search: string;
    pageSize: number;
    searchPlaceholder?: string;
    columns?: GridColumn[];
    pageSizes?: number[];
    exportUrl?: string;
    showExport?: boolean;
}>(), {
    pageSizes: () => [10, 25, 50, 100],
    showExport: true,
});

const emit = defineEmits<{
    (e: 'update:search', v: string): void;
    (e: 'update:pageSize', v: number): void;
    (e: 'update:columns', v: GridColumn[]): void;
    (e: 'export'): void;
}>();

const slots = useSlots();
const hasFilters = computed(() => !!slots.filters);

const showColumns = ref(false);
const showFilters = ref(false);

function toggleColumn(key: string) {
    if (!props.columns) return;
    emit('update:columns', props.columns.map((c) => (c.key === key ? { ...c, visible: !c.visible } : c)));
}
</script>

<template>
    <div class="border-b border-slate-200 dark:border-slate-700">
        <div class="flex flex-wrap items-center gap-2 p-3">
            <!-- Columnas -->
            <div v-if="columns?.length" class="relative">
                <button class="tbtn" type="button" @click="showColumns = !showColumns">
                    <Columns3 class="h-4 w-4" /> {{ t('grid.columns') }}
                </button>
                <div v-if="showColumns" class="absolute z-20 mt-1 w-56 rounded-lg border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                    <label v-for="c in columns" :key="c.key" class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                        <input type="checkbox" :checked="c.visible" class="rounded border-slate-300 text-brand focus:ring-brand" @change="toggleColumn(c.key)" />
                        {{ c.label }}
                    </label>
                </div>
            </div>

            <!-- XLSX (descarga nativa server-side si hay exportUrl) -->
            <a v-if="showExport && exportUrl" :href="exportUrl" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                <FileSpreadsheet class="h-4 w-4" /> XLSX
            </a>
            <button v-else-if="showExport" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-green-700" type="button" @click="emit('export')">
                <FileSpreadsheet class="h-4 w-4" /> XLSX
            </button>

            <!-- Mostrar -->
            <div class="flex items-center gap-1.5 text-sm text-slate-500">
                <span>{{ t('grid.show') }}</span>
                <select
                    :value="pageSize"
                    class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900"
                    @change="emit('update:pageSize', Number(($event.target as HTMLSelectElement).value))"
                >
                    <option v-for="n in pageSizes" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>

            <span class="text-sm text-slate-400">{{ t('toolbar.records', { count: total.toLocaleString('es-DO') }) }}</span>

            <!-- Derecha: filtros + búsqueda -->
            <div class="ml-auto flex items-center gap-2">
                <button v-if="hasFilters" class="tbtn" type="button" @click="showFilters = !showFilters">
                    <Filter class="h-4 w-4" /> {{ t('grid.filters') }}
                </button>
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-2 dark:border-slate-600 dark:bg-slate-900">
                    <Search class="h-4 w-4 opacity-50" />
                    <input
                        :value="search" type="text" :placeholder="searchPlaceholder ?? t('grid.search')"
                        class="w-56 bg-transparent py-1.5 text-sm outline-none"
                        @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                    />
                </div>
            </div>
        </div>

        <!-- Panel de filtros (opcional) -->
        <div v-if="hasFilters && showFilters" class="flex flex-wrap items-end gap-3 border-t border-slate-100 bg-slate-50 p-3 dark:border-slate-700/60 dark:bg-slate-900/40">
            <slot name="filters" />
        </div>
    </div>
</template>

<style scoped>
.tbtn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border-radius: 0.5rem;
    border: 1px solid rgb(203 213 225);
    padding: 0.375rem 0.625rem;
    font-size: 0.875rem;
}
:global(.dark) .tbtn { border-color: rgb(71 85 105); }
</style>
