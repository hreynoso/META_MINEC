<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X, Save } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { STATUS_OPTIONS, RISK_OPTIONS } from '@/Composables/useProjectFormat';

const { t } = useI18n({ useScope: 'global' });

interface ProjectData {
    id: number;
    code: string;
    name: string;
    institution_id: number | null;
    presidential_goal_id: number | null;
    status: string;
    risk_level: string;
    budget: number;
    executed: number;
    physical_progress: number;
    start_date: string | null;
    end_date: string | null;
    source: string | null;
    responsible: string | null;
    beneficiaries: number;
    location: string | null;
    deliverables: string[] | null;
    expected_impact: string | null;
    benefits: string | null;
}

const props = defineProps<{
    project: ProjectData | null;
    institutions: { id: number; code: string; short_name: string; name: string }[];
    goals: { id: number; name: string }[];
}>();

const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>();

const isEdit = props.project !== null;

const form = useForm({
    code: props.project?.code ?? '',
    name: props.project?.name ?? '',
    institution_id: props.project?.institution_id ?? null,
    presidential_goal_id: props.project?.presidential_goal_id ?? null,
    status: props.project?.status ?? '',
    risk_level: props.project?.risk_level ?? '',
    budget: (props.project?.budget ?? '') as number | string,
    executed: (props.project?.executed ?? 0) as number | string,
    physical_progress: (props.project?.physical_progress ?? 0) as number | string,
    start_date: props.project?.start_date ?? '',
    end_date: props.project?.end_date ?? '',
    source: props.project?.source ?? '',
    responsible: props.project?.responsible ?? '',
    beneficiaries: (props.project?.beneficiaries ?? 0) as number | string,
    location: props.project?.location ?? '',
    // El backend acepta texto (uno por línea) y lo normaliza a arreglo.
    deliverables: (props.project?.deliverables ?? []).join('\n'),
    expected_impact: props.project?.expected_impact ?? '',
    benefits: props.project?.benefits ?? '',
});

// Clases compartidas de campos, alineadas con el resto del sistema.
const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function submit() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    };

    if (props.project) {
        form.put(route('proyectos.update', props.project.id), opts);
    } else {
        form.post(route('proyectos.store'), opts);
    }
}
</script>

<template>
    <!-- Modal: se cierra SOLO con botones (sin backdrop/Escape). -->
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
        <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ isEdit ? t('project_form.title_edit') : t('project_form.title_create') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('project_form.required_hint_prefix') }} <span class="text-red-600">*</span> {{ t('project_form.required_hint_suffix') }}</p>
                </div>
                <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="emit('close')">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label :class="label">{{ t('project_form.field_code') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.code" :class="input" placeholder="MINEC-2026-001" />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_institution') }} <span class="text-red-600">*</span></label>
                    <select v-model="form.institution_id" :class="input">
                        <option :value="null" disabled>{{ t('project_form.select_placeholder') }}</option>
                        <option v-for="i in institutions" :key="i.id" :value="i.id">{{ i.short_name }}</option>
                    </select>
                    <p v-if="form.errors.institution_id" class="mt-1 text-xs text-red-600">{{ form.errors.institution_id }}</p>
                </div>

                <div class="md:col-span-2">
                    <label :class="label">{{ t('project_form.field_name') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.name" :class="input" :placeholder="t('project_form.placeholder_name')" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_start_date') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.start_date" type="date" :class="input" />
                    <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_end_date') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.end_date" type="date" :class="input" />
                    <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_budget') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.budget" type="number" min="0" step="0.01" :class="input" placeholder="1000000" />
                    <p v-if="form.errors.budget" class="mt-1 text-xs text-red-600">{{ form.errors.budget }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_executed') }}</label>
                    <input v-model="form.executed" type="number" min="0" step="0.01" :class="input" placeholder="0" />
                    <p v-if="form.errors.executed" class="mt-1 text-xs text-red-600">{{ form.errors.executed }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_source') }}</label>
                    <input v-model="form.source" :class="input" placeholder="GOES" />
                    <p v-if="form.errors.source" class="mt-1 text-xs text-red-600">{{ form.errors.source }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_responsible') }} <span class="text-red-600">*</span></label>
                    <input v-model="form.responsible" :class="input" :placeholder="t('project_form.placeholder_responsible')" />
                    <p v-if="form.errors.responsible" class="mt-1 text-xs text-red-600">{{ form.errors.responsible }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_presidential_goal') }}</label>
                    <select v-model="form.presidential_goal_id" :class="input">
                        <option :value="null">{{ t('project_form.select_placeholder') }}</option>
                        <option v-for="g in goals" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                    <p v-if="form.errors.presidential_goal_id" class="mt-1 text-xs text-red-600">{{ form.errors.presidential_goal_id }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_location') }}</label>
                    <input v-model="form.location" :class="input" :placeholder="t('project_form.placeholder_location')" />
                    <p v-if="form.errors.location" class="mt-1 text-xs text-red-600">{{ form.errors.location }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_status') }} <span class="text-red-600">*</span></label>
                    <select v-model="form.status" :class="input">
                        <option value="" disabled>{{ t('project_form.select_placeholder') }}</option>
                        <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_risk_level') }} <span class="text-red-600">*</span></label>
                    <select v-model="form.risk_level" :class="input">
                        <option value="" disabled>{{ t('project_form.select_placeholder') }}</option>
                        <option v-for="r in RISK_OPTIONS" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <p v-if="form.errors.risk_level" class="mt-1 text-xs text-red-600">{{ form.errors.risk_level }}</p>
                </div>

                <div>
                    <label :class="label">{{ t('project_form.field_physical_progress') }}</label>
                    <input v-model="form.physical_progress" type="number" min="0" max="100" :class="input" placeholder="0" />
                    <p v-if="form.errors.physical_progress" class="mt-1 text-xs text-red-600">{{ form.errors.physical_progress }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('project_form.field_beneficiaries') }}</label>
                    <input v-model="form.beneficiaries" type="number" min="0" :class="input" placeholder="0" />
                    <p v-if="form.errors.beneficiaries" class="mt-1 text-xs text-red-600">{{ form.errors.beneficiaries }}</p>
                </div>

                <div class="md:col-span-2">
                    <label :class="label">{{ t('project_form.field_deliverables') }}</label>
                    <textarea v-model="form.deliverables" rows="3" :class="input" :placeholder="t('project_form.placeholder_deliverables')" />
                    <p v-if="form.errors.deliverables" class="mt-1 text-xs text-red-600">{{ form.errors.deliverables }}</p>
                </div>
                <div class="md:col-span-2">
                    <label :class="label">{{ t('project_form.field_expected_impact') }}</label>
                    <textarea v-model="form.expected_impact" rows="2" :class="input" />
                    <p v-if="form.errors.expected_impact" class="mt-1 text-xs text-red-600">{{ form.errors.expected_impact }}</p>
                </div>
                <div class="md:col-span-2">
                    <label :class="label">{{ t('project_form.field_benefits') }}</label>
                    <textarea v-model="form.benefits" rows="2" :class="input" />
                    <p v-if="form.errors.benefits" class="mt-1 text-xs text-red-600">{{ form.errors.benefits }}</p>
                </div>

                <div class="mt-2 flex justify-end gap-2 md:col-span-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                        @click="emit('close')"
                    >
                        {{ t('actions.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" /> {{ t('project_form.save_button') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
