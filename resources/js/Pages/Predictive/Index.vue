<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Sparkles, TriangleAlert, Bot, ChevronDown, ChevronUp, FileDown } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Generation { recommendation: string; user: string; datetime: string | null }
interface HistoryItem extends Generation { id: number }

interface Prediction {
    id: number; code: string; name: string; institution: string; responsible: string | null;
    physical_progress: number; financial_progress: number;
    risk: string; risk_label: string; status: string; status_label: string;
    score: number; expected: number | null; failing: boolean;
    factors: string[]; recommendation: string; last_generation: Generation | null;
}

const props = defineProps<{ ranking: Prediction[] }>();

const first = props.ranking[0] ?? null;
const selected = ref<Prediction | null>(first);
const recommendation = ref<string>(first?.last_generation?.recommendation ?? first?.recommendation ?? '');
const aiUsed = ref(Boolean(first?.last_generation));
const aiLoading = ref(false);
const aiMessage = ref<string | null>(null);

// Metadatos de la última generación con IA e historial por proyecto.
const lastGeneration = ref<Generation | null>(first?.last_generation ?? null);
const history = ref<HistoryItem[]>([]);
const showHistory = ref(false);
const historyLoading = ref(false);
const historyLoaded = ref(false);

function select(p: Prediction) {
    selected.value = p;
    // Muestra la última generación con IA si existe; si no, la del modelo.
    recommendation.value = p.last_generation?.recommendation ?? p.recommendation;
    aiUsed.value = Boolean(p.last_generation);
    lastGeneration.value = p.last_generation ?? null;
    aiMessage.value = null;
    history.value = [];
    showHistory.value = false;
    historyLoaded.value = false;
}

function scoreClass(s: number): string {
    if (s < 30) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
    if (s < 60) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
    return 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300';
}

async function generateAi() {
    if (!selected.value) return;
    aiLoading.value = true;
    aiMessage.value = null;
    try {
        const res = await fetch(route('ia-predictiva.recommendation', selected.value.id), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        recommendation.value = data.recommendation ?? recommendation.value;
        aiUsed.value = Boolean(data.ai);
        aiMessage.value = data.message ?? null;
        lastGeneration.value = data.generation ?? null;

        // Refresca el historial si hay una nueva generación registrada.
        if (data.generation) {
            historyLoaded.value = false;
            if (showHistory.value) await loadHistory();
        }
    } catch {
        aiMessage.value = t('predictive.ai_error');
    } finally {
        aiLoading.value = false;
    }
}

async function toggleHistory() {
    showHistory.value = !showHistory.value;
    if (showHistory.value && !historyLoaded.value) await loadHistory();
}

async function loadHistory() {
    if (!selected.value) return;
    historyLoading.value = true;
    try {
        const res = await fetch(route('ia-predictiva.history', selected.value.id), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        history.value = data.history ?? [];
        historyLoaded.value = true;
    } catch {
        aiMessage.value = t('predictive.history_error');
    } finally {
        historyLoading.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">{{ t('predictive.page_title') }}</h1>
            <p class="text-sm text-slate-500">{{ t('predictive.page_subtitle') }}</p>
        </header>

        <!-- Descripción del modelo -->
        <div class="mb-6 rounded-2xl border border-sky-100 bg-sky-50/60 p-5 dark:border-sky-900/40 dark:bg-sky-900/10">
            <p class="flex items-center gap-2 text-sm font-semibold text-brand">
                <Sparkles class="h-4 w-4" /> {{ t('predictive.model_badge') }}
            </p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                {{ t('predictive.model_description') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[400px_1fr]">
            <!-- Ranking de riesgo -->
            <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ t('predictive.risk_ranking_title') }}</h2>
                    <p class="text-xs text-slate-400">{{ t('predictive.risk_ranking_subtitle') }}</p>
                </div>
                <div class="max-h-[70vh] overflow-y-auto">
                    <button
                        v-for="p in ranking" :key="p.id"
                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-0 dark:border-slate-700/60"
                        :class="selected?.id === p.id ? 'bg-brand/5' : 'hover:bg-slate-50 dark:hover:bg-slate-700/40'"
                        @click="select(p)"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ p.name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ p.institution }} · {{ p.code }}</p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-semibold" :class="scoreClass(p.score)">{{ p.score }}%</span>
                    </button>
                    <p v-if="!ranking.length" class="px-4 py-8 text-center text-sm text-slate-400">{{ t('predictive.no_projects') }}</p>
                </div>
            </section>

            <!-- Detalle del proyecto seleccionado -->
            <section v-if="selected" class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-xs text-slate-400">{{ selected.code }}</p>
                        <h2 class="mt-1 text-xl font-semibold">{{ selected.name }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ selected.institution }}<span v-if="selected.responsible"> · {{ selected.responsible }}</span></p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <span v-if="selected.failing" class="inline-flex items-center gap-1 rounded-full border border-red-300 px-2 py-0.5 text-xs text-red-700 dark:border-red-800 dark:text-red-400">
                            <TriangleAlert class="h-3.5 w-3.5" /> {{ t('predictive.failure_risk') }}
                        </span>
                        <a
                            :href="route('ia-predictiva.report', selected.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white"
                            :title="t('predictive.download_report_title')"
                        >
                            <FileDown class="h-3.5 w-3.5" /> {{ t('predictive.download_pdf') }}
                        </a>
                    </div>
                </div>

                <!-- Probabilidad de éxito -->
                <p class="mt-5 text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.success_probability') }}</p>
                <div class="mt-1 flex items-center gap-4">
                    <p class="text-3xl font-bold">{{ selected.score }}<span class="text-lg font-normal text-slate-400">%</span></p>
                    <div class="flex-1">
                        <div class="relative h-2.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-2.5 rounded-full" :style="{ width: Math.max(selected.score, 2) + '%', background: 'linear-gradient(to right, #ef4444, #f59e0b, #10b981)' }" />
                        </div>
                        <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                            <span>0%</span><span>50%</span><span>100%</span>
                        </div>
                    </div>
                </div>

                <!-- Métricas -->
                <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.physical_progress') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ selected.physical_progress }}%</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.financial_execution') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ selected.financial_progress }}%</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.risk') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ selected.risk_label }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.status') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ selected.status_label }}</p>
                    </div>
                </div>

                <!-- Factores considerados -->
                <p class="mt-6 text-xs uppercase tracking-wide text-slate-400">{{ t('predictive.factors_considered') }}</p>
                <ul class="mt-2 space-y-2">
                    <li v-for="(f, i) in selected.factors" :key="i" class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900/50">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand" /> {{ f }}
                    </li>
                </ul>

                <!-- Recomendación del modelo (enriquecible con IA) -->
                <div class="mt-6 rounded-lg border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/40 dark:bg-sky-900/10">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand">
                            {{ aiUsed ? t('predictive.ai_recommendation') : t('predictive.model_recommendation') }}
                        </p>
                        <button
                            class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white disabled:opacity-50"
                            :disabled="aiLoading"
                            @click="generateAi"
                        >
                            <Bot class="h-3.5 w-3.5" /> {{ aiLoading ? t('predictive.consulting') : t('predictive.generate_with_ai') }}
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-200">{{ recommendation }}</p>
                    <p v-if="lastGeneration" class="mt-2 text-xs text-slate-400">
                        {{ t('predictive.generated_by') }} <span class="font-medium text-slate-500 dark:text-slate-300">{{ lastGeneration.user }}</span> · {{ lastGeneration.datetime }}
                    </p>
                    <p v-if="aiMessage" class="mt-2 text-xs text-amber-600">{{ aiMessage }}</p>

                    <!-- Historial de generaciones anteriores (ordenadas por fecha) -->
                    <button
                        type="button"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
                        @click="toggleHistory"
                    >
                        <component :is="showHistory ? ChevronUp : ChevronDown" class="h-3.5 w-3.5" />
                        {{ showHistory ? t('predictive.hide_previous_generations') : t('predictive.show_previous_generations') }}
                    </button>

                    <div v-if="showHistory" class="mt-2 space-y-2">
                        <p v-if="historyLoading" class="text-xs text-slate-400">{{ t('predictive.loading') }}</p>
                        <p v-else-if="!history.length" class="text-xs text-slate-400">{{ t('predictive.no_generations') }}</p>
                        <div
                            v-for="h in history" :key="h.id"
                            class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <p class="text-xs text-slate-400">
                                <span class="font-medium text-slate-500 dark:text-slate-300">{{ h.user }}</span> · {{ h.datetime }}
                            </p>
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ h.recommendation }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
