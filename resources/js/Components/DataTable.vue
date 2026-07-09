<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, computed, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { Columns3, FileSpreadsheet, Filter, Search } from 'lucide-vue-next';
import SortableTh from '@/Components/SortableTh.vue';
import { useSortable } from '@/Composables/useSortable';
import { useTokenSearch } from '@/Composables/useTokenSearch';

const { t } = useI18n({ useScope: 'global' });

const props = defineProps<{
    rows: T[];
    columns: { key: string; label: string }[];
    searchFields: (keyof T)[];
}>();

const emit = defineEmits<{ (e: 'export'): void }>();

const rowsRef = toRef(props, 'rows');
const pageSize = ref(25);

const { query, filtered } = useTokenSearch(rowsRef, props.searchFields);
const { sortKey, sortDir, toggle, sorted } = useSortable(filtered as any);

const visible = computed(() => (sorted.value as T[]).slice(0, pageSize.value));
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
        <!-- Toolbar uniforme -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3 dark:border-slate-700">
            <button class="tbtn"><Columns3 class="h-4 w-4" /> {{ t('grid.columns') }}</button>
            <button class="tbtn" @click="emit('export')"><FileSpreadsheet class="h-4 w-4" /> {{ t('grid.export_xlsx') }}</button>
            <select v-model.number="pageSize" class="tbtn">
                <option :value="10">{{ t('grid.show') }} 10</option>
                <option :value="25">{{ t('grid.show') }} 25</option>
                <option :value="50">{{ t('grid.show') }} 50</option>
                <option :value="100">{{ t('grid.show') }} 100</option>
            </select>
            <button class="tbtn"><Filter class="h-4 w-4" /> {{ t('grid.filters') }}</button>
            <div class="ml-auto flex items-center gap-2 rounded-lg border border-slate-300 px-2 dark:border-slate-600">
                <Search class="h-4 w-4 opacity-50" />
                <input v-model="query" type="text" :placeholder="t('grid.search')" class="bg-transparent py-1.5 text-sm outline-none" />
            </div>
        </div>

        <table class="w-full">
            <thead class="border-b border-slate-200 dark:border-slate-700">
                <tr>
                    <SortableTh
                        v-for="c in columns"
                        :key="c.key"
                        :label="c.label"
                        :column-key="c.key"
                        :active-key="sortKey"
                        :direction="sortDir"
                        @sort="toggle"
                    />
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in visible" :key="i" class="border-b border-slate-100 dark:border-slate-700/50">
                    <td v-for="c in columns" :key="c.key" class="px-3 py-2 text-sm">{{ row[c.key] }}</td>
                </tr>
                <tr v-if="visible.length === 0">
                    <td :colspan="columns.length" class="px-3 py-6 text-center text-sm text-slate-400">{{ t('grid.empty') }}</td>
                </tr>
            </tbody>
        </table>
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
