<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { CalendarRange, CalendarClock, CheckCircle2, Clock, Save } from 'lucide-vue-next';

const props = defineProps<{
    executionYear: number;
    planningYear: number;
    planningActivationDate: string | null;
    planningActive: boolean;
    currentYear: number;
}>();

const { t } = useI18n({ useScope: 'global' });

const form = useForm({
    execution_year: props.executionYear,
    planning_activation_date: props.planningActivationDate ?? '',
});

// El año de planificación siempre es el de ejecución + 1 (se calcula en vivo).
const planningYear = computed(() => Number(form.execution_year || 0) + 1);

// Estado del período de planificación (informativo). Se recalcula en vivo al
// cambiar la fecha: comparación lexicográfica de fechas ISO (YYYY-MM-DD) contra
// la fecha local de hoy.
const todayStr = new Date().toLocaleDateString('en-CA');
const planningActive = computed(
    () => !!form.planning_activation_date && form.planning_activation_date <= todayStr,
);

function save() {
    form.post(route('configuracion.periodos.update'), { preserveScroll: true });
}
</script>

<template>
    <ConfigLayout section="periodos">
        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <CalendarRange class="h-5 w-5 text-brand" /> {{ t('config.periods.title') }}
            </h2>
            <p class="text-sm text-slate-500">{{ t('config.periods.description') }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ t('config.periods.current_year_note', { year: props.currentYear }) }}</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Período de Ejecución -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-base font-semibold">
                            <CalendarRange class="h-4 w-4 text-brand" /> {{ t('config.periods.execution_title') }}
                        </h3>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                        <CheckCircle2 class="h-3.5 w-3.5" /> {{ t('config.periods.execution_badge') }}
                    </span>
                </div>

                <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('config.periods.year_label') }}</label>
                <input
                    v-model.number="form.execution_year"
                    type="number"
                    min="2000"
                    max="2100"
                    step="1"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-2xl font-semibold tabular-nums outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                />
                <p v-if="form.errors.execution_year" class="mt-1 text-xs text-red-500">{{ form.errors.execution_year }}</p>

                <p class="mt-3 text-xs text-slate-400">{{ t('config.periods.execution_hint') }}</p>
            </div>

            <!-- Período de Planificación -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-base font-semibold">
                            <CalendarClock class="h-4 w-4 text-brand" /> {{ t('config.periods.planning_title') }}
                        </h3>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="planningActive
                            ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
                    >
                        <CheckCircle2 v-if="planningActive" class="h-3.5 w-3.5" />
                        <Clock v-else class="h-3.5 w-3.5" />
                        {{ planningActive ? t('config.periods.status_active') : t('config.periods.status_scheduled') }}
                    </span>
                </div>

                <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('config.periods.year_label') }}</label>
                <div class="mt-1 w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-2xl font-semibold tabular-nums text-slate-500 dark:border-slate-600 dark:bg-slate-900/50">
                    {{ planningYear }}
                </div>
                <p class="mt-1 text-xs text-slate-400">{{ t('config.periods.planning_year_note') }}</p>

                <label class="mt-4 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('config.periods.activation_label') }}</label>
                <input
                    v-model="form.planning_activation_date"
                    type="date"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                />
                <p v-if="form.errors.planning_activation_date" class="mt-1 text-xs text-red-500">{{ form.errors.planning_activation_date }}</p>
                <p class="mt-2 text-xs text-slate-400">
                    {{ form.planning_activation_date ? t('config.periods.activation_hint') : t('config.periods.activation_none') }}
                </p>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button
                type="button"
                :disabled="form.processing || !form.isDirty"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                @click="save"
            >
                <Save class="h-4 w-4" /> {{ t('actions.save') }}
            </button>
        </div>
    </ConfigLayout>
</template>
