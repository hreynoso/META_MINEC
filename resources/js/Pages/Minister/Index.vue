<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Crown, TriangleAlert, Sparkles, FileText, FileDown, Check, X, Loader2, ChevronDown, ChevronUp, History } from 'lucide-vue-next';
import { currency, number } from '@/Composables/useProjectFormat';

import { useCan } from '@/Composables/useCan';

const { t } = useI18n({ useScope: 'global' });
const { can } = useCan();

interface Kpi { label: string; value: number; unit: string | null; target: number; achievement: number }
interface SemaforoItem { name: string; code: string; physical_progress: number; status: string }
interface Semaforo { code: string; name: string; short_name: string; green: number; amber: number; red: number; items: { green: SemaforoItem[]; amber: SemaforoItem[]; red: SemaforoItem[] }; status: string }
interface Alert {
    name: string; institution: string; physical_progress: number; risk: string; success: number;
    recommendation: string; recommendation_source: 'ia' | 'modelo'; recommendation_by: string | null; recommendation_at: string | null;
}
interface Recommendation { title: string; detail: string; priority: string }

interface AiRecommendation { project: string; user: string; datetime: string | null; recommendation: string }

const props = defineProps<{
    summary: { budget: number; executed: number; executed_pct: number; beneficiaries: number; critical: number; projects_count: number; institutions_count: number };
    kpis: Kpi[];
    byInstitution: Semaforo[];
    alerts: Alert[];
    recommendations: Recommendation[];
    lastAiRecommendation: AiRecommendation | null;
    institutions: { id: number; short_name: string }[];
}>();

const page = usePage();

// Modal del informe generado (se abre cuando el backend devuelve flash.report).
const report = ref<string | null>(null);
watch(
    () => (page.props.flash as any)?.report as string | undefined,
    (r) => { if (r) report.value = r; },
    { immediate: true },
);

const barClass = (a: number) => (a >= 80 ? 'bg-teal-500' : a >= 50 ? 'bg-amber-500' : 'bg-red-500');
const statusMeta: Record<string, { label: string; dot: string; badge: string }> = {
    critico: { label: t('minister.status_critico'), dot: 'bg-red-500', badge: 'border-red-300 text-red-700 dark:border-red-800 dark:text-red-400' },
    observacion: { label: t('minister.status_observacion'), dot: 'bg-amber-500', badge: 'border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-400' },
    optimo: { label: t('minister.status_optimo'), dot: 'bg-teal-500', badge: 'border-teal-300 text-teal-700 dark:border-teal-800 dark:text-teal-400' },
};
const successClass = (s: number) =>
    s >= 40 ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
        : s >= 15 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
            : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';

// Semáforo: al hacer clic en un color se muestran los proyectos en ese estado.
const openBucket = ref<string | null>(null);
const bucketColors = ['green', 'amber', 'red'] as const;
type BucketColor = typeof bucketColors[number];
const bucketBtn: Record<BucketColor, string> = {
    green: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
};
const bucketLabel = (c: BucketColor) => t(`minister.state_${c}`);
function toggleBucket(code: string, color: BucketColor, count: number) {
    if (count === 0) return;
    const key = `${code}:${color}`;
    openBucket.value = openBucket.value === key ? null : key;
}

// Formulario del informe presidencial.
const form = useForm({
    institutions: props.institutions.map((i) => i.id),
    from: '2024-01-01',
    to: new Date().toISOString().slice(0, 10),
});

function toggle(id: number) {
    const i = form.institutions.indexOf(id);
    if (i === -1) form.institutions.push(id);
    else form.institutions.splice(i, 1);
}

const canGenerate = computed(() => form.institutions.length > 0 && !form.processing);

// true desde que se solicita el informe hasta que se dispara la descarga.
const generating = ref(false);

// Trazabilidad de informes presidenciales generados.
interface ReportHistoryItem { id: number; user: string; datetime: string | null; period: string; institutions: string[] }
const history = ref<ReportHistoryItem[]>([]);
const showHistory = ref(false);
const historyLoading = ref(false);

async function loadHistory() {
    historyLoading.value = true;
    try {
        const res = await fetch(route('ministra.history'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        history.value = data.history ?? [];
    } catch {
        // silencioso: la trazabilidad es complementaria
    } finally {
        historyLoading.value = false;
    }
}

onMounted(loadHistory);

function generate() {
    generating.value = true;
    form.post(route('ministra.report'), {
        preserveScroll: true,
        onSuccess: async () => {
            // Al completar la IA, descarga el PDF con el texto generado.
            const r = (page.props.flash as any)?.report as string | undefined;
            if (r) downloadPdf(r);
            await loadHistory();
            showHistory.value = true;
        },
        onError: () => { generating.value = false; },
        onFinish: () => { generating.value = false; },
    });
}

function toggleHistory() {
    showHistory.value = !showHistory.value;
    if (showHistory.value && !history.value.length) loadHistory();
}

// Descarga del informe en PDF vía formulario nativo (respuesta binaria).
const csrf = computed(() => (page.props as any).csrf as string);
const pdfForm = ref<HTMLFormElement | null>(null);
const pdfReportText = ref('');

async function downloadPdf(text = '') {
    pdfReportText.value = text;
    await nextTick();
    pdfForm.value?.submit();
}

// Descarga del informe en Word (.docx) vía formulario nativo.
const docxForm = ref<HTMLFormElement | null>(null);
const docxReportText = ref('');

async function downloadDocx(text = '') {
    docxReportText.value = text;
    await nextTick();
    docxForm.value?.submit();
}
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ t('minister.office_title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('minister.office_subtitle') }}</p>
            </div>
        </header>

        <!-- Banner estratégico -->
        <div class="mb-6 flex items-center gap-4 rounded-2xl bg-gradient-to-r from-brand to-sky-500 p-5 text-white">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                <Crown class="h-6 w-6" />
            </div>
            <div>
                <p class="font-semibold">{{ t('minister.strategic_banner_title') }}</p>
                <p class="text-sm text-white/80">
                    {{ t('minister.strategic_banner_summary', { projects: summary.projects_count, institutions: summary.institutions_count, critical: summary.critical }) }}
                </p>
            </div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('minister.total_budget') }}</p>
                <p class="mt-1 text-2xl font-bold">{{ currency(summary.budget) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('minister.aggregate_execution') }}</p>
                <p class="mt-1 text-2xl font-bold">{{ summary.executed_pct }}%</p>
                <p class="mt-1 text-xs text-slate-400">{{ currency(summary.executed) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('minister.direct_beneficiaries') }}</p>
                <p class="mt-1 text-2xl font-bold">{{ number(summary.beneficiaries) }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/50 dark:bg-red-900/20">
                <p class="text-xs uppercase tracking-wide text-red-500">{{ t('minister.critical_projects') }}</p>
                <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.critical }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- KPIs estratégicos -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('minister.strategic_kpis') }}</h2>
                <div class="space-y-4">
                    <div v-for="k in kpis" :key="k.label">
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ k.label }}</span>
                            <span class="text-slate-400">{{ number(k.value) }} / {{ number(k.target) }} {{ k.unit }}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-1.5 rounded-full" :class="barClass(k.achievement)" :style="{ width: Math.min(k.achievement, 100) + '%' }" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Semáforo por institución (clic en un color para ver sus proyectos) -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('minister.traffic_light_by_institution') }}</h2>
                <p class="mb-4 text-xs text-slate-400">{{ t('minister.traffic_light_hint') }}</p>
                <div class="space-y-3">
                    <div v-for="i in byInstitution" :key="i.code">
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="statusMeta[i.status].dot" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ i.short_name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ i.name }}</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs">
                                <button
                                    v-for="c in bucketColors" :key="c" type="button"
                                    :disabled="i[c] === 0"
                                    class="rounded px-1.5 py-0.5 font-medium transition disabled:cursor-default disabled:opacity-40"
                                    :class="[bucketBtn[c], openBucket === `${i.code}:${c}` ? 'ring-2 ring-offset-1 ring-slate-400 dark:ring-offset-slate-800' : '']"
                                    :title="bucketLabel(c)"
                                    @click="toggleBucket(i.code, c, i[c])"
                                >{{ i[c] }}</button>
                            </div>
                            <span class="rounded-full border px-2 py-0.5 text-xs" :class="statusMeta[i.status].badge">{{ statusMeta[i.status].label }}</span>
                        </div>

                        <!-- Lista de proyectos del color seleccionado -->
                        <div v-for="c in bucketColors" :key="`list-${i.code}-${c}`">
                            <ul v-if="openBucket === `${i.code}:${c}`" class="mt-2 space-y-1 rounded-lg bg-slate-50 p-2 dark:bg-slate-900/50">
                                <li class="px-1 pb-1">
                                    <span class="rounded px-1.5 py-0.5 text-xs font-medium" :class="bucketBtn[c]">{{ bucketLabel(c) }} · {{ i[c] }}</span>
                                </li>
                                <li v-for="(p, pi) in i.items[c]" :key="pi" class="flex items-center justify-between gap-2 rounded px-2 py-1 text-xs">
                                    <span class="min-w-0 truncate"><span class="font-mono text-slate-400">{{ p.code }}</span> · {{ p.name }}</span>
                                    <span class="shrink-0 text-slate-400">{{ p.status }} · {{ p.physical_progress }}%</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Alertas predictivas de IA con su recomendación de acción (fusionado) -->
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <TriangleAlert class="h-4 w-4 text-amber-500" /> {{ t('minister.ai_predictive_alerts') }}
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div v-for="(a, idx) in alerts" :key="idx" class="flex flex-col rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{{ a.name }}</p>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium" :class="successClass(a.success)">{{ t('minister.success_badge', { success: a.success }) }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500">{{ t('minister.alert_meta', { institution: a.institution, progress: a.physical_progress, risk: a.risk }) }}</p>

                    <!-- Última recomendación de acción del proyecto -->
                    <div class="mt-2 rounded-lg border border-sky-200 bg-sky-50/70 p-2.5 dark:border-sky-900/40 dark:bg-sky-900/10">
                        <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-brand">
                            <Sparkles class="h-3 w-3" />
                            {{ a.recommendation_source === 'ia' ? t('minister.rec_ia') : t('minister.rec_model') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-700 dark:text-slate-200">{{ a.recommendation }}</p>
                        <p v-if="a.recommendation_at" class="mt-1 text-[11px] text-slate-400">{{ a.recommendation_by }} · {{ a.recommendation_at }}</p>
                    </div>
                </div>
                <p v-if="!alerts.length" class="text-sm text-slate-400">{{ t('minister.no_projects_at_risk') }}</p>
            </div>
        </section>

        <!-- Informe presidencial con IA -->
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <FileText class="h-4 w-4" /> {{ t('minister.presidential_report_ai') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ t('minister.report_description') }}
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('minister.institutions') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="i in institutions" :key="i.id" type="button"
                            class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-sm transition"
                            :class="form.institutions.includes(i.id)
                                ? 'border-brand bg-brand text-white'
                                : 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300'"
                            @click="toggle(i.id)"
                        >
                            <Check v-if="form.institutions.includes(i.id)" class="h-3.5 w-3.5" /> {{ i.short_name }}
                        </button>
                    </div>
                    <p v-if="form.errors.institutions" class="mt-1 text-xs text-red-600">{{ form.errors.institutions }}</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('minister.from_date') }}</label>
                        <input v-model="form.from" type="date" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900" />
                        <p v-if="form.errors.from" class="mt-1 text-xs text-red-600">{{ form.errors.from }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('minister.to_date') }}</label>
                        <input v-model="form.to" type="date" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900" />
                        <p v-if="form.errors.to" class="mt-1 text-xs text-red-600">{{ form.errors.to }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <button
                    v-if="can('ministra.informe')"
                    type="button"
                    :disabled="!canGenerate"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    @click="generate"
                >
                    <Sparkles class="h-4 w-4" /> {{ form.processing ? t('minister.generating') : t('minister.generate_report') }}
                </button>
            </div>

            <!-- Trazabilidad de informes generados -->
            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:underline"
                    @click="toggleHistory"
                >
                    <component :is="showHistory ? ChevronUp : ChevronDown" class="h-4 w-4" />
                    <History class="h-4 w-4" /> {{ t('minister.previous_generations') }}
                </button>

                <div v-if="showHistory" class="mt-3 space-y-2">
                    <p v-if="historyLoading" class="text-xs text-slate-400">{{ t('minister.loading') }}</p>
                    <p v-else-if="!history.length" class="text-xs text-slate-400">{{ t('minister.no_reports_yet') }}</p>
                    <div
                        v-for="h in history" :key="h.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ h.user }} · {{ h.datetime }}</p>
                            <p class="truncate text-xs text-slate-400">
                                {{ t('minister.period_label', { period: h.period }) }}<span v-if="h.institutions.length"> · {{ h.institutions.join(', ') }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <a
                                :href="route('ministra.report.stored', h.id)"
                                class="inline-flex items-center gap-1 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white"
                            >
                                <FileDown class="h-3.5 w-3.5" /> PDF
                            </a>
                            <a
                                :href="route('ministra.report.stored.docx', h.id)"
                                class="inline-flex items-center gap-1 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white"
                            >
                                <FileDown class="h-3.5 w-3.5" /> DOCX
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Formulario nativo para la descarga binaria del PDF -->
        <form ref="pdfForm" :action="route('ministra.report.pdf')" method="post" class="hidden">
            <input type="hidden" name="_token" :value="csrf" />
            <input v-for="id in form.institutions" :key="id" type="hidden" name="institutions[]" :value="id" />
            <input type="hidden" name="from" :value="form.from" />
            <input type="hidden" name="to" :value="form.to" />
            <input type="hidden" name="report" :value="pdfReportText" />
        </form>

        <!-- Formulario nativo para la descarga binaria del DOCX -->
        <form ref="docxForm" :action="route('ministra.report.docx')" method="post" class="hidden">
            <input type="hidden" name="_token" :value="csrf" />
            <input v-for="id in form.institutions" :key="`docx-${id}`" type="hidden" name="institutions[]" :value="id" />
            <input type="hidden" name="from" :value="form.from" />
            <input type="hidden" name="to" :value="form.to" />
            <input type="hidden" name="report" :value="docxReportText" />
        </form>

        <!-- Overlay de carga mientras la IA genera el informe -->
        <div v-if="generating || form.processing" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40">
            <div class="mx-6 w-full max-w-sm rounded-2xl bg-white px-8 py-7 text-center shadow-xl dark:bg-slate-800">
                <Loader2 class="mx-auto h-9 w-9 animate-spin text-brand" />
                <p class="mt-4 text-sm font-semibold">{{ t('minister.generating_report_overlay') }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ t('minister.generating_report_hint') }}</p>
            </div>
        </div>

        <!-- Modal del informe generado (cierra solo con botón) -->
        <div v-if="report" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="flex items-center gap-2 text-lg font-semibold"><FileText class="h-5 w-5 text-brand" /> {{ t('minister.presidential_report') }}</h2>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="report = null">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="mt-4 max-h-[60vh] overflow-y-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm leading-relaxed dark:bg-slate-900">{{ report }}</div>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-4 py-2 text-sm font-medium text-brand transition hover:bg-brand hover:text-white"
                        @click="downloadPdf(report ?? '')"
                    >
                        <FileDown class="h-4 w-4" /> {{ t('minister.download_pdf') }}
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-4 py-2 text-sm font-medium text-brand transition hover:bg-brand hover:text-white"
                        @click="downloadDocx(report ?? '')"
                    >
                        <FileDown class="h-4 w-4" /> {{ t('minister.download_docx') }}
                    </button>
                    <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90" @click="report = null">{{ t('actions.close') }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
