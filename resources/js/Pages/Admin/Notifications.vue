<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Bell } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Settings {
    email_enabled: boolean; project_at_risk: boolean; project_updated: boolean;
    memoir_generated: boolean; weekly_digest: boolean;
}

const props = defineProps<{ settings: Settings; recipients: string }>();

const form = useForm({
    email_enabled: props.settings.email_enabled,
    project_at_risk: props.settings.project_at_risk,
    project_updated: props.settings.project_updated,
    memoir_generated: props.settings.memoir_generated,
    weekly_digest: props.settings.weekly_digest,
    recipients: props.recipients,
});

const toggles = computed<{ key: keyof Settings; title: string; hint: string }[]>(() => [
    { key: 'email_enabled', title: t('notifications.toggle_email_title'), hint: t('notifications.toggle_email_hint') },
    { key: 'project_at_risk', title: t('notifications.toggle_at_risk_title'), hint: t('notifications.toggle_at_risk_hint') },
    { key: 'project_updated', title: t('notifications.toggle_updated_title'), hint: t('notifications.toggle_updated_hint') },
    { key: 'memoir_generated', title: t('notifications.toggle_memoir_title'), hint: t('notifications.toggle_memoir_hint') },
    { key: 'weekly_digest', title: t('notifications.toggle_digest_title'), hint: t('notifications.toggle_digest_hint') },
]);

function submit() {
    form.post(route('configuracion.notificaciones.update'), { preserveScroll: true });
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

            <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50">
                {{ form.processing ? t('notifications.saving') : t('notifications.save_button') }}
            </button>
        </form>
    </ConfigLayout>
</template>
