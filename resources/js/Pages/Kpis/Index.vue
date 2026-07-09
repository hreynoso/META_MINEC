<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Plus, Pencil, Trash2, Star, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import { number } from '@/Composables/useProjectFormat';
import { TREND_LABEL, trendClass, achievementBarClass } from '@/Composables/useKpiFormat';
import { useConfirm } from '@/Composables/useConfirm';
import { matchesAllTokens } from '@/Composables/useTokenSearch';
import GridToolbar, { type GridColumn } from '@/Components/GridToolbar.vue';
import KpiFormModal from '@/Components/KpiFormModal.vue';

interface Kpi {
    id: number; key: string; label: string; value: number; unit: string | null;
    target: number; achievement: number; trend: string; strategic: boolean; sort: number;
}

const props = defineProps<{ kpis: Kpi[] }>();

const { t } = useI18n({ useScope: 'global' });

const strategic = computed(() => props.kpis.filter((k) => k.strategic));

// Toolbar uniforme.
const search = ref('');
const pageSize = ref(25);
const fStrategic = ref('');
const columns = ref<GridColumn[]>([
    { key: 'label', label: t('kpis.col_indicator'), visible: true },
    { key: 'value', label: t('kpis.col_value'), visible: true },
    { key: 'target', label: t('kpis.col_target'), visible: true },
    { key: 'achievement', label: t('kpis.col_achievement'), visible: true },
    { key: 'trend', label: t('kpis.col_trend'), visible: true },
]);
const vis = (k: string) => columns.value.find((c) => c.key === k)?.visible ?? true;

const filtered = computed(() =>
    props.kpis.filter((k) => {
        if (fStrategic.value === 'si' && !k.strategic) return false;
        if (fStrategic.value === 'no' && k.strategic) return false;
        if (search.value.trim() && !matchesAllTokens(`${k.label} ${k.key} ${k.unit ?? ''}`, search.value)) return false;
        return true;
    }),
);
const visibleRows = computed(() => filtered.value.slice(0, pageSize.value));

const formOpen = ref(false);
const editing = ref<Kpi | null>(null);

const { ask } = useConfirm();

const trendIcon = (t: string) => (t === 'up' ? TrendingUp : t === 'down' ? TrendingDown : Minus);

function openCreate() {
    editing.value = null;
    formOpen.value = true;
}

function openEdit(k: Kpi) {
    editing.value = k;
    formOpen.value = true;
}

function onSaved() {
    formOpen.value = false;
    editing.value = null;
}

function confirmDelete(k: Kpi) {
    ask({
        header: t('kpis.delete_header'),
        message: t('kpis.delete_message', { label: k.label }),
        acceptLabel: t('actions.delete'),
        accept: () => {
            router.delete(route('kpis.destroy', k.id), { preserveScroll: true });
        },
    });
}
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ t('kpis.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('kpis.subtitle') }}</p>
            </div>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                <Plus class="h-4 w-4" /> {{ t('kpis.create_kpi') }}
            </button>
        </header>

        <!-- Indicadores estratégicos (los que se muestran en el Dashboard) -->
        <section v-if="strategic.length" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ t('kpis.strategic_section') }}</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="k in strategic" :key="k.id">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs">{{ k.label }}</span>
                        <component :is="trendIcon(k.trend)" class="h-4 w-4" :class="trendClass(k.trend)" />
                    </div>
                    <p class="mt-1 text-xl font-bold">
                        {{ number(k.value) }} <span class="text-sm font-normal text-slate-400">{{ k.unit }}</span>
                    </p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-1.5 rounded-full" :class="achievementBarClass(k.achievement)" :style="{ width: Math.min(k.achievement, 100) + '%' }" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ t('kpis.achievement_of_target', { achievement: k.achievement, target: number(k.target) }) }}</p>
                </div>
            </div>
        </section>

        <!-- Tabla de gestión de todos los indicadores -->
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <GridToolbar
                v-model:search="search"
                v-model:page-size="pageSize"
                v-model:columns="columns"
                :total="filtered.length"
                :search-placeholder="t('kpis.search_placeholder')"
                :export-url="route('kpis.export')"
            >
                <template #filters>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('kpis.strategic_label') }}</label>
                        <select v-model="fStrategic" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('kpis.filter_all') }}</option>
                            <option value="si">{{ t('kpis.filter_yes') }}</option>
                            <option value="no">{{ t('kpis.filter_no') }}</option>
                        </select>
                    </div>
                </template>
            </GridToolbar>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                        <tr>
                            <th v-if="vis('label')" class="px-4 py-3 font-medium">{{ t('kpis.col_indicator') }}</th>
                            <th v-if="vis('value')" class="px-4 py-3 font-medium">{{ t('kpis.col_value') }}</th>
                            <th v-if="vis('target')" class="px-4 py-3 font-medium">{{ t('kpis.col_target') }}</th>
                            <th v-if="vis('achievement')" class="px-4 py-3 font-medium">{{ t('kpis.col_achievement') }}</th>
                            <th v-if="vis('trend')" class="px-4 py-3 font-medium">{{ t('kpis.col_trend') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ t('kpis.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in visibleRows" :key="k.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                            <td v-if="vis('label')" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Star v-if="k.strategic" class="h-3.5 w-3.5 text-amber-500" fill="currentColor" />
                                    <div>
                                        <p class="font-medium">{{ k.label }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ k.key }}</p>
                                    </div>
                                </div>
                            </td>
                            <td v-if="vis('value')" class="px-4 py-3">{{ number(k.value) }} <span class="text-xs text-slate-400">{{ k.unit }}</span></td>
                            <td v-if="vis('target')" class="px-4 py-3">{{ number(k.target) }}</td>
                            <td v-if="vis('achievement')" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div class="h-1.5 rounded-full" :class="achievementBarClass(k.achievement)" :style="{ width: Math.min(k.achievement, 100) + '%' }" />
                                    </div>
                                    <span class="text-xs text-slate-500">{{ k.achievement }}%</span>
                                </div>
                            </td>
                            <td v-if="vis('trend')" class="px-4 py-3">
                                <span class="inline-flex items-center gap-1" :class="trendClass(k.trend)">
                                    <component :is="trendIcon(k.trend)" class="h-4 w-4" /> {{ TREND_LABEL[k.trend] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button class="rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('kpis.edit')" @click="openEdit(k)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button class="rounded p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" :title="t('actions.delete')" @click="confirmDelete(k)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">{{ t('kpis.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal crear/editar KPI -->
        <KpiFormModal
            v-if="formOpen"
            :key="editing?.id ?? 'new'"
            :kpi="editing"
            @close="formOpen = false"
            @saved="onSaved"
        />
    </AppLayout>
</template>
