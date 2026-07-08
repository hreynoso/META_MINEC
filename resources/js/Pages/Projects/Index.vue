<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Search, Download, Upload, Plus, X, MapPin, Building2, Pencil, Trash2 } from 'lucide-vue-next';
import { matchesAllTokens } from '@/Composables/useTokenSearch';
import { useConfirm } from '@/Composables/useConfirm';
import ProjectFormModal from '@/Components/ProjectFormModal.vue';
import {
    currency, number, STATUS_LABEL, STATUS_OPTIONS, statusClass, riskClass, progressBarClass,
} from '@/Composables/useProjectFormat';

interface Project {
    id: number; code: string; name: string; institution: string; institution_id: number | null;
    goal: string; presidential_goal_id: number | null;
    status: string; risk_level: string; budget: number; executed: number;
    financial_progress: number; physical_progress: number;
    start_date: string | null; end_date: string | null; source: string | null;
    responsible: string | null; beneficiaries: number; location: string | null;
    deliverables: string[] | null; expected_impact: string | null; benefits: string | null;
}

const props = defineProps<{
    projects: Project[];
    institutions: { id: number; code: string; short_name: string; name: string }[];
    goals: { id: number; name: string }[];
}>();

// El estado inicial del filtro puede venir de la URL (?status=en_riesgo),
// p. ej. al llegar desde la tarjeta "Proyectos en alerta" del dashboard.
const initialStatus = new URLSearchParams(window.location.search).get('status') ?? '';

const q = ref('');
const institution = ref('');
const status = ref(STATUS_OPTIONS.some((s) => s.value === initialStatus) ? initialStatus : '');
const selected = ref<Project | null>(null);

// Modal de crear/editar: null = cerrado; se distingue crear (editing === null) de editar.
const formOpen = ref(false);
const editing = ref<Project | null>(null);

const { ask } = useConfirm();

function openCreate() {
    editing.value = null;
    formOpen.value = true;
}

function openEdit(p: Project) {
    editing.value = p;
    selected.value = null;
    formOpen.value = true;
}

function onSaved() {
    formOpen.value = false;
    editing.value = null;
}

function confirmDelete(p: Project) {
    ask({
        header: 'Eliminar proyecto',
        message: `¿Eliminar el proyecto "${p.name}" (${p.code})? Esta acción no se puede deshacer.`,
        acceptLabel: 'Eliminar',
        accept: () => {
            router.delete(route('proyectos.destroy', p.id), {
                preserveScroll: true,
                onSuccess: () => { selected.value = null; },
            });
        },
    });
}

const filtered = computed(() =>
    props.projects.filter((p) => {
        if (institution.value && p.institution !== institution.value) return false;
        if (status.value && p.status !== status.value) return false;
        if (q.value.trim()) {
            const hay = `${p.code} ${p.name} ${p.responsible ?? ''}`;
            if (!matchesAllTokens(hay, q.value)) return false;
        }
        return true;
    }),
);
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Proyectos de inversión pública</h1>
                <p class="text-sm text-slate-500">Cartera completa del Ministerio y sus instituciones adscritas</p>
            </div>
            <div class="flex gap-2">
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <Download class="h-4 w-4" /> Descargar plantilla
                </button>
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <Upload class="h-4 w-4" /> Cargar plantilla
                </button>
                <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                    <Plus class="h-4 w-4" /> Crear proyecto
                </button>
            </div>
        </header>

        <!-- Toolbar de filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <p class="text-sm text-slate-500">{{ filtered.length }} proyecto(s)</p>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 px-2 dark:border-slate-600">
                    <Search class="h-4 w-4 opacity-50" />
                    <input v-model="q" type="text" placeholder="Buscar por nombre, código o responsable..." class="w-64 bg-transparent py-2 text-sm outline-none" />
                </div>
                <select v-model="institution" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">Todas las instituciones</option>
                    <option v-for="i in institutions" :key="i.code" :value="i.short_name">{{ i.short_name }}</option>
                </select>
                <select v-model="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">Cualquier estado</option>
                    <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
        </div>

        <!-- Grid de tarjetas -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <button
                v-for="p in filtered" :key="p.id"
                class="rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                @click="selected = p"
            >
                <div class="flex items-start justify-between">
                    <span class="font-mono text-xs text-slate-400">{{ p.code }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(p.status)">{{ STATUS_LABEL[p.status] }}</span>
                </div>
                <h3 class="mt-2 font-semibold leading-snug">{{ p.name }}</h3>
                <p class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1"><Building2 class="h-3 w-3" /> {{ p.institution }}</span>
                    <span v-if="p.location" class="inline-flex items-center gap-1"><MapPin class="h-3 w-3" /> {{ p.location }}</span>
                </p>
                <div class="mt-3 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                    <div class="h-1.5 rounded-full" :class="progressBarClass(p.physical_progress)" :style="{ width: p.physical_progress + '%' }" />
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                    <span>{{ p.physical_progress }}% avance</span>
                    <span>{{ currency(p.executed) }} / {{ currency(p.budget) }}</span>
                </div>
                <p v-if="p.goal" class="mt-2 text-xs text-slate-400">◎ {{ p.goal }}</p>
            </button>
        </div>

        <!-- Modal de detalle: se cierra SOLO con botones (sin backdrop/Escape) -->
        <div v-if="selected" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-mono text-xs text-slate-400">{{ selected.code }}</p>
                        <h2 class="mt-1 text-xl font-semibold">{{ selected.name }}</h2>
                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(selected.status)">{{ STATUS_LABEL[selected.status] }}</span>
                            <span :class="riskClass(selected.risk_level)">Riesgo: {{ selected.risk_level }}</span>
                            <span class="text-slate-400">· {{ selected.institution }}</span>
                        </div>
                    </div>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="selected = null">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div><p class="text-xs uppercase text-slate-400">Inicio</p><p>{{ selected.start_date ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Fin</p><p>{{ selected.end_date ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Presupuesto</p><p>{{ currency(selected.budget) }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Ejecutado</p><p>{{ currency(selected.executed) }} ({{ selected.financial_progress }}%)</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Fuente</p><p>{{ selected.source ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Responsable</p><p>{{ selected.responsible ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Meta presidencial</p><p>{{ selected.goal ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">Beneficiarios</p><p>{{ number(selected.beneficiaries) }}</p></div>
                </div>

                <div v-if="selected.deliverables?.length" class="mt-5">
                    <p class="text-xs uppercase text-slate-400">Entregables</p>
                    <ul class="mt-1 list-inside list-disc text-sm">
                        <li v-for="d in selected.deliverables" :key="d">{{ d }}</li>
                    </ul>
                </div>
                <div v-if="selected.expected_impact" class="mt-4">
                    <p class="text-xs uppercase text-slate-400">Impacto esperado</p>
                    <p class="text-sm">{{ selected.expected_impact }}</p>
                </div>
                <div v-if="selected.benefits" class="mt-4">
                    <p class="text-xs uppercase text-slate-400">Beneficios</p>
                    <p class="text-sm">{{ selected.benefits }}</p>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="uppercase">Avance físico</span><span>{{ selected.physical_progress }}%</span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-2 rounded-full" :class="progressBarClass(selected.physical_progress)" :style="{ width: selected.physical_progress + '%' }" />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30"
                        @click="confirmDelete(selected)"
                    >
                        <Trash2 class="h-4 w-4" /> Eliminar
                    </button>
                    <div class="flex gap-2">
                        <button
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                            @click="openEdit(selected)"
                        >
                            <Pencil class="h-4 w-4" /> Editar
                        </button>
                        <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90" @click="selected = null">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal crear/editar proyecto. key fuerza remontaje para reinicializar el form. -->
        <ProjectFormModal
            v-if="formOpen"
            :key="editing?.id ?? 'new'"
            :project="editing"
            :institutions="institutions"
            :goals="goals"
            @close="formOpen = false"
            @saved="onSaved"
        />
    </AppLayout>
</template>
