<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import SecurityTabs from '@/Components/SecurityTabs.vue';
import { BellRing, Save, TriangleAlert } from 'lucide-vue-next';

const props = defineProps<{
    settings: { enabled: boolean; recipients: string };
}>();

const { t } = useI18n({ useScope: 'global' });

const form = useForm({
    enabled: props.settings.enabled,
    recipients: props.settings.recipients,
});

function submit() {
    form.post(route('configuracion.seguridad.alertas.update'), { preserveScroll: true });
}

const events = ['security.alerts.event_lockout', 'security.alerts.event_blocked'];
</script>

<template>
    <ConfigLayout section="seguridad">
        <SecurityTabs />

        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <BellRing class="h-5 w-5 text-brand" /> {{ t('security.alerts.title') }}
            </h2>
            <p class="text-sm text-slate-500">{{ t('security.alerts.subtitle') }}</p>
        </div>

        <div class="max-w-2xl space-y-5">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                    {{ t('security.alerts.enabled_label') }}
                </label>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
                        {{ t('security.alerts.recipients_label') }}
                    </label>
                    <textarea
                        v-model="form.recipients"
                        rows="2"
                        placeholder="seguridad-tic@minec.gob.sv, soc@minec.gob.sv"
                        class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                    />
                    <p class="mt-1 text-xs text-slate-400">{{ t('security.alerts.recipients_hint') }}</p>
                    <p v-if="form.errors.recipients" class="mt-1 text-xs text-red-600">{{ form.errors.recipients }}</p>
                </div>
            </div>

            <!-- Eventos que disparan alerta -->
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-900/10">
                <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                    <TriangleAlert class="h-4 w-4" /> {{ t('security.alerts.events_title') }}
                </p>
                <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    <li v-for="e in events" :key="e" class="flex gap-2">
                        <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500" />
                        <span>{{ t(e) }}</span>
                    </li>
                </ul>
            </div>

            <div class="flex justify-end">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                    @click="submit"
                >
                    <Save class="h-4 w-4" /> {{ t('security.alerts.save') }}
                </button>
            </div>
        </div>
    </ConfigLayout>
</template>
