<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import SecurityTabs from '@/Components/SecurityTabs.vue';
import { PackageSearch, ShieldCheck, AlertTriangle, Play, Info, CalendarClock, FileText, Mail, Save } from 'lucide-vue-next';

interface Advisory { package: string; title: string; cve: string; severity: string; link: string }

const props = defineProps<{
    audit: { available: boolean; count: number; advisories: Advisory[]; ran_at: string | null };
    schedule: { interval_days: number; reported_at: string | null; next_report_at: string | null; recipients: string[] };
}>();

const { t } = useI18n({ useScope: 'global' });

const runForm = useForm({});
function run() {
    runForm.post(route('configuracion.seguridad.dependencias.ejecutar'), { preserveScroll: true });
}

const scheduleForm = useForm({ interval_days: props.schedule.interval_days });
function saveSchedule() {
    scheduleForm.post(route('configuracion.seguridad.dependencias.programacion'), { preserveScroll: true });
}

const sendForm = useForm({});
function sendNow() {
    sendForm.post(route('configuracion.seguridad.dependencias.enviar'), { preserveScroll: true });
}

const sevClass = (s: string) =>
    s === 'critical' || s === 'high'
        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
        : s === 'medium'
            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
</script>

<template>
    <ConfigLayout section="seguridad">
        <SecurityTabs />

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <PackageSearch class="h-5 w-5 text-brand" /> {{ t('security.deps.title') }}
                </h2>
                <p class="text-sm text-slate-500">{{ t('security.deps.subtitle') }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    <template v-if="audit.ran_at">{{ t('security.deps.last_run', { when: audit.ran_at }) }}</template>
                    <template v-else>{{ t('security.deps.never_run') }}</template>
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <a
                    :href="route('configuracion.seguridad.dependencias.pdf')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                >
                    <FileText class="h-4 w-4" /> {{ t('security.deps.download_pdf') }}
                </a>
                <button
                    type="button"
                    :disabled="runForm.processing"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    @click="run"
                >
                    <Play class="h-4 w-4" /> {{ runForm.processing ? t('security.deps.running') : t('security.deps.run') }}
                </button>
            </div>
        </div>

        <!-- Programación del análisis (A.8.8): periodicidad + informe por correo -->
        <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="flex items-center gap-2 text-sm font-semibold">
                <CalendarClock class="h-4 w-4 text-brand" /> {{ t('security.deps.schedule_title') }}
            </h3>
            <p class="mt-1 text-xs text-slate-500">{{ t('security.deps.schedule_subtitle') }}</p>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        {{ t('security.deps.interval_label') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            v-model.number="scheduleForm.interval_days"
                            type="number" min="1" max="365"
                            class="w-24 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                        />
                        <span class="text-sm text-slate-500">{{ t('security.deps.days') }}</span>
                        <button
                            type="button"
                            :disabled="scheduleForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                            @click="saveSchedule"
                        >
                            <Save class="h-4 w-4" /> {{ t('actions.save') }}
                        </button>
                    </div>
                    <p v-if="scheduleForm.errors.interval_days" class="mt-1 text-xs text-red-600">{{ scheduleForm.errors.interval_days }}</p>
                </div>

                <div class="sm:ml-auto sm:text-right">
                    <button
                        type="button"
                        :disabled="sendForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-700"
                        @click="sendNow"
                    >
                        <Mail class="h-4 w-4" /> {{ sendForm.processing ? t('security.deps.sending') : t('security.deps.send_now') }}
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2 text-xs text-slate-500 sm:grid-cols-2">
                <p>
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('security.deps.next_report') }}:</span>
                    {{ schedule.next_report_at ?? '—' }}
                </p>
                <p>
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('security.deps.last_report') }}:</span>
                    {{ schedule.reported_at ?? t('security.deps.never_reported') }}
                </p>
                <p class="sm:col-span-2">
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('security.deps.recipients') }}:</span>
                    <template v-if="schedule.recipients.length">{{ schedule.recipients.join(', ') }}</template>
                    <template v-else><span class="text-amber-600">{{ t('security.deps.no_recipients') }}</span></template>
                </p>
            </div>
        </div>

        <!-- No disponible en el entorno -->
        <div v-if="!audit.available" class="flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <Info class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
            <span>{{ t('security.deps.unavailable') }}</span>
        </div>

        <!-- Sin vulnerabilidades -->
        <div v-else-if="audit.count === 0" class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-700 dark:border-teal-900/50 dark:bg-teal-900/20 dark:text-teal-300">
            <ShieldCheck class="h-5 w-5 shrink-0" /> {{ t('security.deps.clean') }}
        </div>

        <!-- Vulnerabilidades encontradas -->
        <div v-else>
            <p class="mb-3 flex items-center gap-2 text-sm font-medium text-red-700 dark:text-red-300">
                <AlertTriangle class="h-5 w-5 shrink-0" /> {{ t('security.deps.count', { count: audit.count }) }}
            </p>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('security.deps.col_package') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('security.deps.col_severity') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('security.deps.col_title') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(a, i) in audit.advisories" :key="i" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                            <td class="px-4 py-3 font-mono text-xs">{{ a.package }}</td>
                            <td class="px-4 py-3">
                                <span v-if="a.severity" class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="sevClass(a.severity)">{{ a.severity }}</span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <a v-if="a.link" :href="a.link" target="_blank" rel="noopener" class="text-brand hover:underline">{{ a.title || a.cve }}</a>
                                <span v-else>{{ a.title || a.cve }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-4 flex items-start gap-2 text-xs text-slate-400">
            <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" /> {{ t('security.deps.note_ci') }}
        </p>
    </ConfigLayout>
</template>
