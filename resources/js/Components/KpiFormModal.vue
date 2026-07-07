<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X, Save } from 'lucide-vue-next';
import { TREND_OPTIONS } from '@/Composables/useKpiFormat';

interface KpiData {
    id: number;
    key: string;
    label: string;
    value: number;
    unit: string | null;
    target: number;
    trend: string;
    strategic: boolean;
    sort: number;
}

const props = defineProps<{ kpi: KpiData | null }>();

const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>();

const isEdit = props.kpi !== null;

const form = useForm({
    key: props.kpi?.key ?? '',
    label: props.kpi?.label ?? '',
    value: (props.kpi?.value ?? '') as number | string,
    unit: props.kpi?.unit ?? '',
    target: (props.kpi?.target ?? '') as number | string,
    trend: props.kpi?.trend ?? 'flat',
    strategic: props.kpi?.strategic ?? false,
    sort: (props.kpi?.sort ?? 0) as number | string,
});

const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function submit() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    };

    if (props.kpi) {
        form.put(route('kpis.update', props.kpi.id), opts);
    } else {
        form.post(route('kpis.store'), opts);
    }
}
</script>

<template>
    <!-- Modal: se cierra SOLO con botones (sin backdrop/Escape). -->
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ isEdit ? 'Editar indicador' : 'Crear indicador' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Complete la información. Los campos con * son obligatorios.</p>
                </div>
                <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="emit('close')">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div class="md:col-span-2">
                    <label :class="label">Nombre del indicador *</label>
                    <input v-model="form.label" :class="input" placeholder="Ej. Ejecución presupuestaria global" />
                    <p v-if="form.errors.label" class="mt-1 text-xs text-red-600">{{ form.errors.label }}</p>
                </div>

                <div>
                    <label :class="label">Clave *</label>
                    <input v-model="form.key" :class="input" placeholder="ejecucion_presupuestaria" />
                    <p class="mt-1 text-xs text-slate-400">Identificador único (sin espacios).</p>
                    <p v-if="form.errors.key" class="mt-1 text-xs text-red-600">{{ form.errors.key }}</p>
                </div>
                <div>
                    <label :class="label">Unidad</label>
                    <input v-model="form.unit" :class="input" placeholder="%, M USD, empresas…" />
                    <p v-if="form.errors.unit" class="mt-1 text-xs text-red-600">{{ form.errors.unit }}</p>
                </div>

                <div>
                    <label :class="label">Valor actual *</label>
                    <input v-model="form.value" type="number" step="0.01" :class="input" placeholder="0" />
                    <p v-if="form.errors.value" class="mt-1 text-xs text-red-600">{{ form.errors.value }}</p>
                </div>
                <div>
                    <label :class="label">Meta</label>
                    <input v-model="form.target" type="number" min="0" step="0.01" :class="input" placeholder="0" />
                    <p v-if="form.errors.target" class="mt-1 text-xs text-red-600">{{ form.errors.target }}</p>
                </div>

                <div>
                    <label :class="label">Tendencia</label>
                    <select v-model="form.trend" :class="input">
                        <option v-for="t in TREND_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <p v-if="form.errors.trend" class="mt-1 text-xs text-red-600">{{ form.errors.trend }}</p>
                </div>
                <div>
                    <label :class="label">Orden</label>
                    <input v-model="form.sort" type="number" min="0" :class="input" placeholder="0" />
                    <p v-if="form.errors.sort" class="mt-1 text-xs text-red-600">{{ form.errors.sort }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.strategic" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                        Mostrar en el Dashboard como indicador estratégico
                    </label>
                    <p v-if="form.errors.strategic" class="mt-1 text-xs text-red-600">{{ form.errors.strategic }}</p>
                </div>

                <div class="mt-2 flex justify-end gap-2 md:col-span-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                        @click="emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" /> Guardar indicador
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
