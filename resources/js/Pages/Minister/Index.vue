<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Crown, TriangleAlert, Sparkles, FileText, FileDown, Check, X } from 'lucide-vue-next';
import { currency, number } from '@/Composables/useProjectFormat';

interface Kpi { label: string; value: number; unit: string | null; target: number; achievement: number }
interface Semaforo { code: string; name: string; short_name: string; green: number; amber: number; red: number; status: string }
interface Alert { name: string; institution: string; physical_progress: number; risk: string; success: number }
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
    critico: { label: 'Crítico', dot: 'bg-red-500', badge: 'border-red-300 text-red-700 dark:border-red-800 dark:text-red-400' },
    observacion: { label: 'En observación', dot: 'bg-amber-500', badge: 'border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-400' },
    optimo: { label: 'Óptimo', dot: 'bg-teal-500', badge: 'border-teal-300 text-teal-700 dark:border-teal-800 dark:text-teal-400' },
};
const successClass = (s: number) =>
    s >= 40 ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
        : s >= 15 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
            : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';

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

function generate() {
    form.post(route('ministra.report'), { preserveScroll: true });
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
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Despacho de la Ministra</h1>
                <p class="text-sm text-slate-500">Tablero ejecutivo · Alertas, semáforo institucional e informe presidencial con IA</p>
            </div>
        </header>

        <!-- Banner estratégico -->
        <div class="mb-6 flex items-center gap-4 rounded-2xl bg-gradient-to-r from-brand to-sky-500 p-5 text-white">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                <Crown class="h-6 w-6" />
            </div>
            <div>
                <p class="font-semibold">Panorama estratégico para la Sra. Ministra</p>
                <p class="text-sm text-white/80">
                    {{ summary.projects_count }} proyectos monitoreados · {{ summary.institutions_count }} instituciones adscritas · {{ summary.critical }} requieren atención inmediata
                </p>
            </div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">Presupuesto total</p>
                <p class="mt-1 text-2xl font-bold">{{ currency(summary.budget) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">Ejecución agregada</p>
                <p class="mt-1 text-2xl font-bold">{{ summary.executed_pct }}%</p>
                <p class="mt-1 text-xs text-slate-400">{{ currency(summary.executed) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">Beneficiarios directos</p>
                <p class="mt-1 text-2xl font-bold">{{ number(summary.beneficiaries) }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/50 dark:bg-red-900/20">
                <p class="text-xs uppercase tracking-wide text-red-500">Proyectos críticos</p>
                <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.critical }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- KPIs estratégicos -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 text-sm font-semibold text-slate-700 dark:text-slate-200">KPIs estratégicos</h2>
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

            <!-- Semáforo por institución -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 text-sm font-semibold text-slate-700 dark:text-slate-200">Semáforo por institución</h2>
                <div class="space-y-3">
                    <div v-for="i in byInstitution" :key="i.code" class="flex items-center gap-3">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="statusMeta[i.status].dot" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ i.short_name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ i.name }}</p>
                        </div>
                        <div class="flex items-center gap-1 text-xs">
                            <span class="rounded bg-teal-100 px-1.5 py-0.5 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">{{ i.green }}</span>
                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ i.amber }}</span>
                            <span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ i.red }}</span>
                        </div>
                        <span class="rounded-full border px-2 py-0.5 text-xs" :class="statusMeta[i.status].badge">{{ statusMeta[i.status].label }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Alertas predictivas -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <TriangleAlert class="h-4 w-4 text-amber-500" /> Alertas predictivas de IA
                </h2>
                <div class="space-y-3">
                    <div v-for="(a, idx) in alerts" :key="idx" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium">{{ a.name }}</p>
                            <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium" :class="successClass(a.success)">{{ a.success }}% éxito</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ a.institution }} · Avance {{ a.physical_progress }}% · Riesgo {{ a.risk }}</p>
                        <p class="mt-1 text-xs text-slate-400">Riesgo de fracaso</p>
                    </div>
                    <p v-if="!alerts.length" class="text-sm text-slate-400">Sin proyectos en riesgo de fracaso.</p>
                </div>
            </section>

            <!-- Recomendaciones de acción -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <Sparkles class="h-4 w-4 text-brand" /> Recomendaciones de acción
                </h2>

                <!-- Última recomendación generada con IA (IA Predictiva) -->
                <div v-if="lastAiRecommendation" class="mb-4 rounded-lg border border-sky-200 bg-sky-50/70 p-3 dark:border-sky-900/40 dark:bg-sky-900/10">
                    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-brand">
                        <Sparkles class="h-3.5 w-3.5" /> Última recomendación de IA
                    </p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ lastAiRecommendation.recommendation }}</p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ lastAiRecommendation.project }} · {{ lastAiRecommendation.user }} · {{ lastAiRecommendation.datetime }}
                    </p>
                </div>

                <div class="space-y-3">
                    <div v-for="(r, idx) in recommendations" :key="idx" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium">{{ r.title }}</p>
                            <span class="shrink-0 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">Prioridad {{ r.priority }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ r.detail }}</p>
                    </div>
                    <p v-if="!recommendations.length" class="text-sm text-slate-400">No hay intervenciones prioritarias por ahora.</p>
                </div>
            </section>
        </div>

        <!-- Informe presidencial con IA -->
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <FileText class="h-4 w-4" /> Informe presidencial con inteligencia artificial
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Seleccione una o más instituciones y el rango de fechas. El sistema usará el proveedor de IA configurado con la data de la plataforma META para redactar un informe ejecutivo dirigido a la Presidencia de la República.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Instituciones</p>
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
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Desde</label>
                        <input v-model="form.from" type="date" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900" />
                        <p v-if="form.errors.from" class="mt-1 text-xs text-red-600">{{ form.errors.from }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Hasta</label>
                        <input v-model="form.to" type="date" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900" />
                        <p v-if="form.errors.to" class="mt-1 text-xs text-red-600">{{ form.errors.to }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <button
                    type="button"
                    :disabled="!canGenerate"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    @click="generate"
                >
                    <Sparkles class="h-4 w-4" /> {{ form.processing ? 'Generando…' : 'Generar informe presidencial' }}
                </button>
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

        <!-- Modal del informe generado (cierra solo con botón) -->
        <div v-if="report" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="flex items-center gap-2 text-lg font-semibold"><FileText class="h-5 w-5 text-brand" /> Informe presidencial</h2>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="report = null">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="mt-4 max-h-[60vh] overflow-y-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm leading-relaxed dark:bg-slate-900">{{ report }}</div>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-4 py-2 text-sm font-medium text-brand transition hover:bg-brand hover:text-white"
                        @click="downloadPdf(report ?? '')"
                    >
                        <FileDown class="h-4 w-4" /> Descargar PDF
                    </button>
                    <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90" @click="report = null">Cerrar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
