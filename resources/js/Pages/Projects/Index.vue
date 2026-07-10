<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Search, Download, Upload, Plus, X, MapPin, Building2, Pencil, Trash2, Users, Target } from 'lucide-vue-next';
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

// El estado inicial de los filtros puede venir de la URL, p. ej. al llegar
// desde las tarjetas del dashboard:
//   ?status=en_riesgo|en_ejecucion  -> preselecciona el estado
//   ?beneficiarios=1                -> solo proyectos que aportan beneficiarios
//   ?meta=<id>                      -> proyectos de una meta presidencial
const urlParams = new URLSearchParams(window.location.search);
const initialStatus = urlParams.get('status') ?? '';
const initialMeta = Number(urlParams.get('meta')) || null;
const initialProject = Number(urlParams.get('proyecto')) || null;

const q = ref('');
const institution = ref('');
const status = ref(STATUS_OPTIONS.some((s) => s.value === initialStatus) ? initialStatus : '');
const onlyBeneficiaries = ref(urlParams.get('beneficiarios') === '1');
const goalFilter = ref<number | null>(props.goals.some((g) => g.id === initialMeta) ? initialMeta : null);
// Al llegar desde el dashboard (?proyecto=<id>) se abre el detalle del proyecto.
const selected = ref<Project | null>(initialProject ? (props.projects.find((p) => p.id === initialProject) ?? null) : null);

// Modal de crear/editar: null = cerrado; se distingue crear (editing === null) de editar.
const formOpen = ref(false);
const editing = ref<Project | null>(null);

const { t } = useI18n({ useScope: 'global' });
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
        header: t('projects.delete_confirm_header'),
        message: t('projects.delete_confirm_message', { name: p.name, code: p.code }),
        acceptLabel: t('actions.delete'),
        danger: true,
        accept: () => {
            router.delete(route('proyectos.destroy', p.id), {
                preserveScroll: true,
                onSuccess: () => { selected.value = null; },
            });
        },
    });
}

const filtered = computed(() => {
    const list = props.projects.filter((p) => {
        if (institution.value && p.institution !== institution.value) return false;
        if (status.value && p.status !== status.value) return false;
        if (goalFilter.value && p.presidential_goal_id !== goalFilter.value) return false;
        if (onlyBeneficiaries.value && !(p.beneficiaries > 0)) return false;
        if (q.value.trim()) {
            const hay = `${p.code} ${p.name} ${p.responsible ?? ''}`;
            if (!matchesAllTokens(hay, q.value)) return false;
        }
        return true;
    });

    // Al llegar desde la tarjeta "Beneficiarios" ordenamos de mayor a menor
    // para que se vea qué proyectos aportan más al total.
    return onlyBeneficiaries.value
        ? [...list].sort((a, b) => b.beneficiaries - a.beneficiaries)
        : list;
});

// Suma de beneficiarios de los proyectos visibles (coincide con el contador
// del dashboard cuando el filtro está activo).
const beneficiariesTotal = computed(() =>
    filtered.value.reduce((sum, p) => sum + (p.beneficiaries || 0), 0),
);

// Nombre de la meta presidencial activa (para el chip del filtro).
const goalName = computed(() => props.goals.find((g) => g.id === goalFilter.value)?.name ?? '');
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ t('projects.page_title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('projects.page_subtitle') }}</p>
            </div>
            <div class="flex gap-2">
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <Download class="h-4 w-4" /> {{ t('projects.download_template') }}
                </button>
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <Upload class="h-4 w-4" /> {{ t('projects.upload_template') }}
                </button>
                <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                    <Plus class="h-4 w-4" /> {{ t('projects.create_project') }}
                </button>
            </div>
        </header>

        <!-- Toolbar de filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <p class="text-sm text-slate-500">{{ t('projects.projects_count', { count: filtered.length }) }}</p>
            <button
                v-if="goalFilter"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full bg-brand/10 px-3 py-1 text-xs font-medium text-brand hover:bg-brand/20"
                @click="goalFilter = null"
            >
                <Target class="h-3 w-3" /> {{ goalName }}
                <X class="h-3 w-3" />
            </button>
            <button
                v-if="onlyBeneficiaries"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-800 hover:bg-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:hover:bg-teal-900/60"
                @click="onlyBeneficiaries = false"
            >
                <Users class="h-3 w-3" /> {{ t('projects.beneficiaries_chip', { count: number(beneficiariesTotal) }) }}
                <X class="h-3 w-3" />
            </button>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-2 dark:border-slate-600 dark:bg-slate-900">
                    <Search class="h-4 w-4 opacity-50" />
                    <input v-model="q" type="text" :placeholder="t('projects.search_placeholder')" class="w-64 bg-transparent py-2 text-sm outline-none" />
                </div>
                <select v-model="institution" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ t('projects.all_institutions') }}</option>
                    <option v-for="i in institutions" :key="i.code" :value="i.short_name">{{ i.short_name }}</option>
                </select>
                <select v-model="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ t('projects.any_status') }}</option>
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
                    <span>{{ t('projects.progress_percent', { percent: p.physical_progress }) }}</span>
                    <span>{{ currency(p.executed) }} / {{ currency(p.budget) }}</span>
                </div>
                <p v-if="p.goal" class="mt-2 text-xs text-slate-400">◎ {{ p.goal }}</p>
                <p v-if="onlyBeneficiaries" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-teal-700 dark:text-teal-300">
                    <Users class="h-3 w-3" /> {{ t('projects.beneficiaries_chip', { count: number(p.beneficiaries) }) }}
                </p>
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
                            <span :class="riskClass(selected.risk_level)">{{ t('projects.risk_label') }} {{ selected.risk_level }}</span>
                            <span class="text-slate-400">· {{ selected.institution }}</span>
                        </div>
                    </div>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="selected = null">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.start_date') }}</p><p>{{ selected.start_date ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.end_date') }}</p><p>{{ selected.end_date ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.budget') }}</p><p>{{ currency(selected.budget) }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.executed') }}</p><p>{{ currency(selected.executed) }} ({{ selected.financial_progress }}%)</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.source') }}</p><p>{{ selected.source ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.responsible') }}</p><p>{{ selected.responsible ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.presidential_goal') }}</p><p>{{ selected.goal ?? '—' }}</p></div>
                    <div><p class="text-xs uppercase text-slate-400">{{ t('projects.beneficiaries') }}</p><p>{{ number(selected.beneficiaries) }}</p></div>
                </div>

                <div v-if="selected.deliverables?.length" class="mt-5">
                    <p class="text-xs uppercase text-slate-400">{{ t('projects.deliverables') }}</p>
                    <ul class="mt-1 list-inside list-disc text-sm">
                        <li v-for="d in selected.deliverables" :key="d">{{ d }}</li>
                    </ul>
                </div>
                <div v-if="selected.expected_impact" class="mt-4">
                    <p class="text-xs uppercase text-slate-400">{{ t('projects.expected_impact') }}</p>
                    <p class="text-sm">{{ selected.expected_impact }}</p>
                </div>
                <div v-if="selected.benefits" class="mt-4">
                    <p class="text-xs uppercase text-slate-400">{{ t('projects.benefits') }}</p>
                    <p class="text-sm">{{ selected.benefits }}</p>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="uppercase">{{ t('projects.physical_progress') }}</span><span>{{ selected.physical_progress }}%</span>
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
                        <Trash2 class="h-4 w-4" /> {{ t('actions.delete') }}
                    </button>
                    <div class="flex gap-2">
                        <button
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                            @click="openEdit(selected)"
                        >
                            <Pencil class="h-4 w-4" /> {{ t('projects.edit_button') }}
                        </button>
                        <button class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90" @click="selected = null">{{ t('actions.close') }}</button>
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
