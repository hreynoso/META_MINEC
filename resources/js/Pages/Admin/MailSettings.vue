<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Mail, Cloud, Webhook, Send, KeyRound } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Settings {
    provider: string; from_address: string; from_name: string;
    mg_domain: string; mg_region: string; has_mg_secret: boolean; has_mg_webhook: boolean;
    smtp_host: string; smtp_port: string; smtp_username: string; smtp_encryption: string; has_smtp_password: boolean;
}
interface MailEventRow { id: number; event: string; severity: string | null; recipient: string | null; reason: string | null; date: string | null }

const props = defineProps<{ settings: Settings; events: MailEventRow[] }>();

const form = useForm({
    provider: props.settings.provider || 'mailgun',
    from_address: props.settings.from_address || '',
    from_name: props.settings.from_name || '',
    mg_domain: props.settings.mg_domain || '',
    mg_region: props.settings.mg_region || 'us',
    mg_secret: '',
    mg_webhook_key: '',
    smtp_host: props.settings.smtp_host || '',
    smtp_port: props.settings.smtp_port || '587',
    smtp_username: props.settings.smtp_username || '',
    smtp_password: '',
    smtp_encryption: props.settings.smtp_encryption || 'tls',
});

const testForm = useForm({ to: '', subject: '', message: '' });

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

const webhookUrl = computed(() => route('webhooks.mailgun'));

function submit() {
    form.post(route('configuracion.correo.update'), { preserveScroll: true, onSuccess: () => form.reset('mg_secret', 'mg_webhook_key', 'smtp_password') });
}
function sendTest() {
    testForm.post(route('configuracion.correo.test'), { preserveScroll: true });
}

const eventClass = (e: string) => ({
    delivered: 'text-teal-600', accepted: 'text-sky-600', opened: 'text-sky-600', clicked: 'text-cyan-600',
    failed: 'text-red-600', complained: 'text-amber-600', unsubscribed: 'text-slate-500',
}[e] ?? 'text-slate-500');
</script>

<template>
    <ConfigLayout section="correo">
        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold"><Mail class="h-5 w-5 text-brand" /> {{ t('mail.heading') }}</h2>
            <p class="text-sm text-slate-500">{{ t('mail.subtitle') }}</p>
        </div>

        <!-- Configuración del proveedor -->
        <form class="space-y-4" @submit.prevent="submit">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold"><Mail class="h-4 w-4 text-slate-400" /> {{ t('mail.provider_heading') }}</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('mail.send_type_label') }} <span class="text-red-600">*</span></label>
                        <select v-model="form.provider" :class="input">
                            <option value="mailgun">Mailgun (API)</option>
                            <option value="smtp">SMTP</option>
                        </select>
                    </div>
                    <div></div>
                    <div>
                        <label :class="label">{{ t('mail.from_address_label') }} <span class="text-red-600">*</span></label>
                        <input v-model="form.from_address" type="email" :class="input" placeholder="noreply@minec.gob.sv" />
                        <p v-if="form.errors.from_address" class="mt-1 text-xs text-red-600">{{ form.errors.from_address }}</p>
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.from_name_label') }} <span class="text-red-600">*</span></label>
                        <input v-model="form.from_name" :class="input" :placeholder="t('mail.from_name_placeholder')" />
                        <p v-if="form.errors.from_name" class="mt-1 text-xs text-red-600">{{ form.errors.from_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Mailgun -->
            <div v-if="form.provider === 'mailgun'" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold"><Cloud class="h-4 w-4 text-slate-400" /> Mailgun <span class="font-normal text-slate-400">{{ t('mail.mailgun_note') }}</span></p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('mail.domain_label') }}</label>
                        <input v-model="form.mg_domain" :class="input" placeholder="mg.minec.gob.sv" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.region_label') }}</label>
                        <select v-model="form.mg_region" :class="input">
                            <option value="us">{{ t('mail.region_us') }}</option>
                            <option value="eu">{{ t('mail.region_eu') }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">API Key</label>
                        <input v-model="form.mg_secret" type="password" autocomplete="off" :class="input" :placeholder="settings.has_mg_secret ? t('mail.secret_saved_placeholder') : 'key-…'" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.webhook_key_label') }}</label>
                        <input v-model="form.mg_webhook_key" type="password" autocomplete="off" :class="input" :placeholder="settings.has_mg_webhook ? t('mail.webhook_key_saved_placeholder') : t('mail.optional_placeholder')" />
                    </div>
                </div>
            </div>

            <!-- SMTP -->
            <div v-else class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold"><KeyRound class="h-4 w-4 text-slate-400" /> SMTP</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('mail.smtp_host_label') }}</label>
                        <input v-model="form.smtp_host" :class="input" placeholder="smtp.office365.com" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.smtp_port_label') }}</label>
                        <input v-model="form.smtp_port" type="number" :class="input" placeholder="587" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.smtp_username_label') }}</label>
                        <input v-model="form.smtp_username" :class="input" autocomplete="off" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.smtp_password_label') }}</label>
                        <input v-model="form.smtp_password" type="password" autocomplete="new-password" :class="input" :placeholder="settings.has_smtp_password ? t('mail.secret_saved_placeholder') : ''" />
                    </div>
                    <div>
                        <label :class="label">{{ t('mail.smtp_encryption_label') }}</label>
                        <select v-model="form.smtp_encryption" :class="input">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">{{ t('mail.encryption_none') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Webhook -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="mb-1 flex items-center gap-2 text-sm font-semibold"><Webhook class="h-4 w-4 text-slate-400" /> {{ t('mail.webhook_heading') }}</p>
                <p class="text-xs text-slate-500">{{ t('mail.webhook_desc') }}</p>
                <code class="mt-2 block rounded-lg bg-slate-100 px-3 py-2 text-xs dark:bg-slate-900">{{ webhookUrl }}</code>
            </div>

            <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50">
                {{ form.processing ? t('mail.saving') : t('mail.save_config') }}
            </button>
        </form>

        <!-- Enviar correo de prueba -->
        <form class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="sendTest">
            <p class="mb-3 flex items-center gap-2 text-sm font-semibold"><Send class="h-4 w-4 text-slate-400" /> {{ t('mail.test_heading') }} <span class="font-normal text-slate-400">{{ t('mail.test_note') }}</span></p>
            <div class="space-y-3">
                <div>
                    <label :class="label">{{ t('mail.recipient_label') }} <span class="text-red-600">*</span></label>
                    <input v-model="testForm.to" type="email" :class="input" placeholder="destinatario@correo.com" />
                    <p v-if="testForm.errors.to" class="mt-1 text-xs text-red-600">{{ testForm.errors.to }}</p>
                </div>
                <div>
                    <label :class="label">{{ t('mail.subject_label') }}</label>
                    <input v-model="testForm.subject" :class="input" :placeholder="t('mail.subject_placeholder')" />
                </div>
                <div>
                    <label :class="label">{{ t('mail.message_label') }}</label>
                    <textarea v-model="testForm.message" rows="3" :class="input" :placeholder="t('mail.message_placeholder')" />
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" :disabled="testForm.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50">
                    <Send class="h-4 w-4" /> {{ testForm.processing ? t('mail.sending') : t('mail.send_test') }}
                </button>
            </div>
        </form>

        <!-- Eventos recientes -->
        <div class="mt-6 rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <p class="border-b border-slate-200 px-5 py-4 text-sm font-semibold dark:border-slate-700">{{ t('mail.events_heading') }} <span class="font-normal text-slate-400">{{ t('mail.events_note') }}</span></p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ t('mail.col_event') }}</th>
                            <th class="px-5 py-3 font-medium">{{ t('mail.recipient_label') }}</th>
                            <th class="px-5 py-3 font-medium">{{ t('mail.col_reason') }}</th>
                            <th class="px-5 py-3 font-medium">{{ t('mail.col_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in events" :key="e.id" class="border-t border-slate-100 dark:border-slate-700/60">
                            <td class="px-5 py-3 font-medium" :class="eventClass(e.event)">
                                {{ e.event }}<span v-if="e.severity" class="text-xs font-normal text-slate-400"> ({{ e.severity }})</span>
                            </td>
                            <td class="px-5 py-3">{{ e.recipient ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ e.reason ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ e.date ?? '—' }}</td>
                        </tr>
                        <tr v-if="!events.length"><td colspan="4" class="px-5 py-8 text-center text-slate-400">{{ t('mail.events_empty') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </ConfigLayout>
</template>
