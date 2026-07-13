<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Bell, ShieldCheck, TriangleAlert, Crown, Send, Loader2, Clock } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Settings {
    email_enabled: boolean; project_at_risk: boolean; project_updated: boolean;
    memoir_generated: boolean; weekly_digest: boolean;
}
interface AccessReview { enabled: boolean; interval_days: number; recipients: string; last_sent_at: string | null }
interface FreqReport { enabled: boolean; frequency: 'daily' | 'weekly' | 'monthly'; time: string; recipients: string; last_sent_at: string | null }
interface Scheduled { access_review: AccessReview; risk_report: FreqReport; minister_report: FreqReport }

const props = defineProps<{ settings: Settings; recipients: string; scheduled: Scheduled }>();

const form = useForm({
    email_enabled: props.settings.email_enabled,
    project_at_risk: props.settings.project_at_risk,
    project_updated: props.settings.project_updated,
    memoir_generated: props.settings.memoir_generated,
    weekly_digest: props.settings.weekly_digest,
    recipients: props.recipients,
    access_review: { ...props.scheduled.access_review },
    risk_report: { ...props.scheduled.risk_report },
    minister_report: { ...props.scheduled.minister_report },
});

const toggles = computed<{ key: keyof Settings; title: string; hint: string }[]>(() => [
    { key: 'email_enabled', title: t('notifications.toggle_email_title'), hint: t('notifications.toggle_email_hint') },
    { key: 'project_at_risk', title: t('notifications.toggle_at_risk_title'), hint: t('notifications.toggle_at_risk_hint') },
    { key: 'project_updated', title: t('notifications.toggle_updated_title'), hint: t('notifications.toggle_updated_hint') },
    { key: 'memoir_generated', title: t('notifications.toggle_memoir_title'), hint: t('notifications.toggle_memoir_hint') },
    { key: 'weekly_digest', title: t('notifications.toggle_digest_title'), hint: t('notifications.toggle_digest_hint') },
]);

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

// Reloj UTC en vivo (la hora programada se interpreta en UTC, como en Respaldos).
const utcClock = ref('');
let clockTimer: number | undefined;
function tickUtc() {
    utcClock.value = new Date().toLocaleTimeString('es-DO', { timeZone: 'UTC', hour12: false });
}
onMounted(() => { tickUtc(); clockTimer = window.setInterval(tickUtc, 1000); });
onBeforeUnmount(() => { if (clockTimer) window.clearInterval(clockTimer); });

function submit() {
    form.post(route('configuracion.notificaciones.update'), { preserveScroll: true });
}

// Envío inmediato (prueba/bajo demanda) de cada informe.
const sending = ref<string | null>(null);
function sendNow(type: 'access_review' | 'risk' | 'minister') {
    sending.value = type;
    router.post(route('configuracion.notificaciones.enviar'), { type }, {
        preserveScroll: true,
        onFinish: () => { sending.value = null; },
    });
}
</script>

<template>
    <ConfigLayout section="notificaciones">
        <div class="mb-5">
            <h2 class="text-lg font-semibold">{{ t('notifications.page_title') }}</h2>
            <p class="text-sm text-slate-500">{{ t('notifications.page_subtitle') }}</p>
        </div>

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white dark:divide-slate-700/60 dark:border-slate-700 dark:bg-slate-800">
                <label v-for="toggle in toggles" :key="toggle.key" class="flex items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <p class="flex items-center gap-2 text-sm font-medium"><Bell class="h-4 w-4 text-slate-400" /> {{ toggle.title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ toggle.hint }}</p>
                    </div>
                    <input v-model="form[toggle.key]" type="checkbox" class="h-5 w-9 shrink-0 rounded-full border-slate-300 text-brand focus:ring-brand" />
                </label>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('notifications.recipients_label') }}</label>
                <textarea
                    v-model="form.recipients" rows="2"
                    placeholder="correo1@minec.gob.sv, correo2@minec.gob.sv"
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                />
                <p class="mt-1 text-xs text-slate-400">{{ t('notifications.recipients_hint') }}</p>
                <p v-if="form.errors.recipients" class="mt-1 text-xs text-red-600">{{ form.errors.recipients }}</p>
            </div>

            <!-- ── Informes automáticos por correo (PDF) ──────────────────── -->
            <div>
                <h3 class="text-base font-semibold">{{ t('notifications.auto_title') }}</h3>
                <p class="text-sm text-slate-500">{{ t('notifications.auto_subtitle') }}</p>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-400">
                    <Clock class="h-3.5 w-3.5" /> {{ t('notifications.utc_note') }}
                    <strong class="font-semibold text-slate-600 dark:text-slate-300">{{ utcClock }} UTC</strong>
                </p>
            </div>

            <!-- Revisión de usuarios y accesos -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center justify-between gap-4">
                    <span class="flex items-center gap-2 text-sm font-medium"><ShieldCheck class="h-4 w-4 text-brand" /> {{ t('notifications.access_title') }}</span>
                    <input v-model="form.access_review.enabled" type="checkbox" class="h-5 w-9 shrink-0 rounded-full border-slate-300 text-brand focus:ring-brand" />
                </label>
                <p class="mt-0.5 text-xs text-slate-500">{{ t('notifications.access_hint') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('notifications.interval_days') }}</label>
                        <input v-model.number="form.access_review.interval_days" type="number" min="1" max="3650" :class="input" />
                        <p v-if="form.errors['access_review.interval_days']" class="mt-1 text-xs text-red-600">{{ form.errors['access_review.interval_days'] }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label :class="label">{{ t('notifications.extra_recipients') }}</label>
                    <textarea v-model="form.access_review.recipients" rows="2" :class="input" placeholder="soc@minec.gob.sv" />
                    <p class="mt-1 text-xs text-slate-400">{{ t('notifications.roles_access') }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-slate-400">{{ form.access_review.last_sent_at ? t('notifications.last_sent', { when: form.access_review.last_sent_at }) : t('notifications.never_sent') }}</span>
                    <button type="button" :disabled="sending === 'access_review'" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="sendNow('access_review')">
                        <Loader2 v-if="sending === 'access_review'" class="h-4 w-4 animate-spin" /><Send v-else class="h-4 w-4" /> {{ t('notifications.send_now') }}
                    </button>
                </div>
            </div>

            <!-- Informe de riesgos (IA) -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center justify-between gap-4">
                    <span class="flex items-center gap-2 text-sm font-medium"><TriangleAlert class="h-4 w-4 text-amber-500" /> {{ t('notifications.risk_title') }}</span>
                    <input v-model="form.risk_report.enabled" type="checkbox" class="h-5 w-9 shrink-0 rounded-full border-slate-300 text-brand focus:ring-brand" />
                </label>
                <p class="mt-0.5 text-xs text-slate-500">{{ t('notifications.risk_hint') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('notifications.frequency') }}</label>
                        <select v-model="form.risk_report.frequency" :class="input">
                            <option value="daily">{{ t('notifications.freq_daily') }}</option>
                            <option value="weekly">{{ t('notifications.freq_weekly') }}</option>
                            <option value="monthly">{{ t('notifications.freq_monthly') }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">{{ t('notifications.time_utc') }}</label>
                        <input v-model="form.risk_report.time" type="time" :class="input" />
                        <p v-if="form.errors['risk_report.time']" class="mt-1 text-xs text-red-600">{{ form.errors['risk_report.time'] }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label :class="label">{{ t('notifications.extra_recipients') }}</label>
                    <textarea v-model="form.risk_report.recipients" rows="2" :class="input" />
                    <p class="mt-1 text-xs text-slate-400">{{ t('notifications.roles_risk') }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-slate-400">{{ form.risk_report.last_sent_at ? t('notifications.last_sent', { when: form.risk_report.last_sent_at }) : t('notifications.never_sent') }}</span>
                    <button type="button" :disabled="sending === 'risk'" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="sendNow('risk')">
                        <Loader2 v-if="sending === 'risk'" class="h-4 w-4 animate-spin" /><Send v-else class="h-4 w-4" /> {{ t('notifications.send_now') }}
                    </button>
                </div>
            </div>

            <!-- Informe de la Ministra -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center justify-between gap-4">
                    <span class="flex items-center gap-2 text-sm font-medium"><Crown class="h-4 w-4 text-brand" /> {{ t('notifications.minister_title') }}</span>
                    <input v-model="form.minister_report.enabled" type="checkbox" class="h-5 w-9 shrink-0 rounded-full border-slate-300 text-brand focus:ring-brand" />
                </label>
                <p class="mt-0.5 text-xs text-slate-500">{{ t('notifications.minister_hint') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('notifications.frequency') }}</label>
                        <select v-model="form.minister_report.frequency" :class="input">
                            <option value="daily">{{ t('notifications.freq_daily') }}</option>
                            <option value="weekly">{{ t('notifications.freq_weekly') }}</option>
                            <option value="monthly">{{ t('notifications.freq_monthly') }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">{{ t('notifications.time_utc') }}</label>
                        <input v-model="form.minister_report.time" type="time" :class="input" />
                        <p v-if="form.errors['minister_report.time']" class="mt-1 text-xs text-red-600">{{ form.errors['minister_report.time'] }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label :class="label">{{ t('notifications.extra_recipients') }}</label>
                    <textarea v-model="form.minister_report.recipients" rows="2" :class="input" placeholder="ministra@minec.gob.sv" />
                    <p class="mt-1 text-xs text-slate-400">{{ t('notifications.roles_minister') }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-slate-400">{{ form.minister_report.last_sent_at ? t('notifications.last_sent', { when: form.minister_report.last_sent_at }) : t('notifications.never_sent') }}</span>
                    <button type="button" :disabled="sending === 'minister'" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="sendNow('minister')">
                        <Loader2 v-if="sending === 'minister'" class="h-4 w-4 animate-spin" /><Send v-else class="h-4 w-4" /> {{ t('notifications.send_now') }}
                    </button>
                </div>
            </div>

            <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50">
                {{ form.processing ? t('notifications.saving') : t('notifications.save_button') }}
            </button>
        </form>
    </ConfigLayout>
</template>
