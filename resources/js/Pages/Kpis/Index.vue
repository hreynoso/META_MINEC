<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Plus, Pencil, Trash2, Star, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import { number } from '@/Composables/useProjectFormat';
import { TREND_LABEL, trendClass, achievementBarClass } from '@/Composables/useKpiFormat';
import { useConfirm } from '@/Composables/useConfirm';
import KpiFormModal from '@/Components/KpiFormModal.vue';

interface Kpi {
    id: number; key: string; label: string; value: number; unit: string | null;
    target: number; achievement: number; trend: string; strategic: boolean; sort: number;
}

const props = defineProps<{ kpis: Kpi[] }>();

const strategic = computed(() => props.kpis.filter((k) => k.strategic));

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
        header: 'Eliminar indicador',
        message: `¿Eliminar el indicador "${k.label}"? Esta acción no se puede deshacer.`,
        acceptLabel: 'Eliminar',
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
                <h1 class="text-2xl font-semibold">Indicadores de gestión</h1>
                <p class="text-sm text-slate-500">KPIs institucionales y su avance frente a la meta</p>
            </div>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                <Plus class="h-4 w-4" /> Crear KPI
            </button>
        </header>

        <!-- Indicadores estratégicos (los que se muestran en el Dashboard) -->
        <section v-if="strategic.length" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Indicadores estratégicos</h2>
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
                    <p class="mt-1 text-xs text-slate-500">{{ k.achievement }}% de la meta ({{ number(k.target) }})</p>
                </div>
            </div>
        </section>

        <!-- Tabla de gestión de todos los indicadores -->
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">Indicador</th>
                        <th class="px-4 py-3 font-medium">Valor</th>
                        <th class="px-4 py-3 font-medium">Meta</th>
                        <th class="px-4 py-3 font-medium">Logro</th>
                        <th class="px-4 py-3 font-medium">Tendencia</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="k in kpis" :key="k.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Star v-if="k.strategic" class="h-3.5 w-3.5 text-amber-500" fill="currentColor" />
                                <div>
                                    <p class="font-medium">{{ k.label }}</p>
                                    <p class="font-mono text-xs text-slate-400">{{ k.key }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ number(k.value) }} <span class="text-xs text-slate-400">{{ k.unit }}</span></td>
                        <td class="px-4 py-3">{{ number(k.target) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-20 rounded-full bg-slate-100 dark:bg-slate-700">
                                    <div class="h-1.5 rounded-full" :class="achievementBarClass(k.achievement)" :style="{ width: Math.min(k.achievement, 100) + '%' }" />
                                </div>
                                <span class="text-xs text-slate-500">{{ k.achievement }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1" :class="trendClass(k.trend)">
                                <component :is="trendIcon(k.trend)" class="h-4 w-4" /> {{ TREND_LABEL[k.trend] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button class="rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" title="Editar" @click="openEdit(k)">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button class="rounded p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" title="Eliminar" @click="confirmDelete(k)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!kpis.length">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay indicadores registrados.</td>
                    </tr>
                </tbody>
            </table>
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
