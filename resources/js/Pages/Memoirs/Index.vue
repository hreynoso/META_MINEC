<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Sparkles, BookText, Loader2, FileDown, History } from 'lucide-vue-next';

interface Institution { id: number; code: string; short_name: string; name: string; projects_count: number }
interface MemoirItem { id: number; institution: string; periodo: string; user: string; datetime: string | null }

const props = defineProps<{ institutions: Institution[]; provider: string }>();

const institutionId = ref<number | null>(null);
const periodo = ref('Enero – Diciembre 2025');
const draft = ref('');
const loading = ref(false);
const message = ref<string | null>(null);
const currentGenerationId = ref<number | null>(null);

// Trazabilidad de memorias generadas.
const history = ref<MemoirItem[]>([]);
const historyLoading = ref(false);

const selected = computed(() => props.institutions.find((i) => i.id === institutionId.value) ?? null);

async function loadHistory() {
    historyLoading.value = true;
    try {
        const res = await fetch(route('memorias.history'), {
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

async function generate() {
    if (!institutionId.value || !periodo.value.trim()) return;
    loading.value = true;
    message.value = null;
    draft.value = '';
    currentGenerationId.value = null;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(route('memorias.generate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ institution_id: institutionId.value, periodo: periodo.value }),
        });
        const data = await res.json();
        draft.value = data.draft ?? '';
        message.value = data.message ?? null;
        currentGenerationId.value = data.generation?.id ?? null;
        if (data.generation) await loadHistory();
    } catch {
        message.value = 'No se pudo generar la memoria. Intenta de nuevo.';
    } finally {
        loading.value = false;
    }
}

const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">Memorias Institucionales</h1>
            <p class="text-sm text-slate-500">Generación asistida por {{ provider }} con datos de la plataforma META</p>
        </header>

        <!-- Motor de IA -->
        <div class="mb-6 rounded-2xl border border-sky-100 bg-sky-50/60 p-5 dark:border-sky-900/40 dark:bg-sky-900/10">
            <p class="flex items-center gap-2 text-sm font-semibold text-brand">
                <Sparkles class="h-4 w-4" /> Motor de IA: {{ provider }}
            </p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                El sistema alimenta al modelo con datos reales de proyectos, presupuestos, avances y metas de la plataforma META para producir un borrador de memoria institucional listo para revisión editorial.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[380px_1fr]">
            <!-- Configuración -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold"><BookText class="h-4 w-4" /> Configuración</h2>

                <div class="space-y-4">
                    <div>
                        <label :class="label">Institución</label>
                        <select v-model="institutionId" :class="input">
                            <option :value="null" disabled>Seleccione…</option>
                            <option v-for="i in institutions" :key="i.id" :value="i.id">{{ i.short_name }} — {{ i.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">Período</label>
                        <input v-model="periodo" :class="input" placeholder="Ej. Enero – Diciembre 2025" />
                    </div>
                    <button
                        type="button"
                        :disabled="loading || !institutionId || !periodo.trim()"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                        @click="generate"
                    >
                        <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                        <Sparkles v-else class="h-4 w-4" />
                        {{ loading ? 'Generando…' : 'Generar memoria' }}
                    </button>
                </div>

                <div class="mt-5 rounded-lg bg-slate-50 p-4 text-xs dark:bg-slate-900/50">
                    <p class="font-medium text-slate-600 dark:text-slate-300">Datos que se enviarán al modelo:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-slate-500">
                        <li>{{ selected?.projects_count ?? 0 }} proyectos institucionales</li>
                        <li>Presupuesto agregado y ejecución</li>
                        <li>Beneficiarios y metas presidenciales</li>
                        <li>Estado, avance e impacto de cada proyecto</li>
                    </ul>
                </div>
            </section>

            <!-- Borrador de memoria -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-sm font-semibold"><BookText class="h-4 w-4" /> Borrador de memoria</h2>
                    <a
                        v-if="draft && currentGenerationId"
                        :href="route('memorias.report', currentGenerationId)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white"
                    >
                        <FileDown class="h-3.5 w-3.5" /> Descargar PDF
                    </a>
                </div>

                <div class="min-h-[60vh] rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                    <div v-if="loading" class="flex h-full min-h-[50vh] flex-col items-center justify-center text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin" />
                        <p class="mt-3 text-sm">Generando la memoria con {{ provider }}…</p>
                    </div>
                    <div v-else-if="draft" class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ draft }}</div>
                    <div v-else class="flex h-full min-h-[50vh] flex-col items-center justify-center text-center text-slate-400">
                        <BookText class="h-8 w-8" />
                        <p class="mt-3 text-sm">
                            <template v-if="message">{{ message }}</template>
                            <template v-else>Configure institución y período, luego presione <strong>Generar memoria</strong>.</template>
                        </p>
                    </div>
                </div>

                <p v-if="message && draft" class="mt-2 text-xs text-amber-600">{{ message }}</p>

                <!-- Trazabilidad de memorias generadas -->
                <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <History class="h-4 w-4" /> Memorias generadas
                    </h3>
                    <p v-if="historyLoading" class="mt-2 text-xs text-slate-400">Cargando…</p>
                    <p v-else-if="!history.length" class="mt-2 text-xs text-slate-400">Aún no hay memorias generadas.</p>
                    <div v-else class="mt-2 space-y-2">
                        <div
                            v-for="h in history" :key="h.id"
                            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ h.institution }} · {{ h.periodo }}</p>
                                <p class="truncate text-xs text-slate-400">{{ h.user }} · {{ h.datetime }}</p>
                            </div>
                            <a
                                :href="route('memorias.report', h.id)"
                                class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-brand px-2.5 py-1 text-xs font-medium text-brand transition hover:bg-brand hover:text-white"
                            >
                                <FileDown class="h-3.5 w-3.5" /> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
