<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { DatabaseBackup, KeyRound, CheckCircle2, AlertTriangle, Loader2, Plug, Cloud, HardDriveUpload, ChevronDown, History, Clock } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Settings {
    enabled: boolean;
    provider: 'dropbox' | 'google_cloud';
    frequency: 'daily' | 'weekly';
    time: string;
    retention_days: number;
    dropbox_folder: string;
    has_dropbox_token: boolean;
    gcs_bucket: string;
    gcs_prefix: string;
    has_gcs_credentials: boolean;
    last_status: string;
    last_run_at: string | null;
}

interface HistoryEntry { at: string | null; date: string | null; status: string; provider: string; detail: string }

const props = defineProps<{ settings: Settings; history: HistoryEntry[] }>();

// Reloj en vivo de la hora del servidor (UTC), solo informativo para el admin.
const utcClock = ref('');
let clockTimer: number | undefined;
function tickUtc() {
    utcClock.value = new Date().toLocaleString('es-DO', {
        timeZone: 'UTC', hour12: false,
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}
onMounted(() => { tickUtc(); clockTimer = window.setInterval(tickUtc, 1000); });
onBeforeUnmount(() => { if (clockTimer) window.clearInterval(clockTimer); });

// Historial agrupado por día (más reciente primero; el backend ya lo ordena).
const showHistory = ref(false);
const groupedHistory = computed(() => {
    const groups: { date: string; items: HistoryEntry[] }[] = [];
    for (const e of props.history) {
        const key = e.date ?? '—';
        let g = groups.find((x) => x.date === key);
        if (!g) { g = { date: key, items: [] }; groups.push(g); }
        g.items.push(e);
    }
    return groups;
});

const form = useForm({
    enabled: props.settings.enabled,
    provider: props.settings.provider || 'dropbox',
    frequency: props.settings.frequency || 'daily',
    time: props.settings.time || '02:00',
    retention_days: props.settings.retention_days ?? 30,
    dropbox_folder: props.settings.dropbox_folder || '/META/backups',
    dropbox_token: '',
    gcs_bucket: props.settings.gcs_bucket || '',
    gcs_prefix: props.settings.gcs_prefix || 'meta/backups',
    gcs_credentials: '',
});

const isDropbox = computed(() => form.provider === 'dropbox');
const gcsPlaceholder = '{ "type": "service_account", "project_id": "...", "private_key": "...", "client_email": "..." }';

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function submit() {
    form.post(route('configuracion.respaldos.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('dropbox_token', 'gcs_credentials'),
    });
}

// Prueba de conexión con el proveedor (usa credenciales escritas o guardadas).
const testing = ref(false);
const testResult = ref<{ ok: boolean; message: string } | null>(null);

async function testConnection() {
    testing.value = true;
    testResult.value = null;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(route('configuracion.respaldos.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                provider: form.provider,
                dropbox_token: form.dropbox_token,
                gcs_bucket: form.gcs_bucket,
                gcs_credentials: form.gcs_credentials,
            }),
        });
        const data = await res.json();
        testResult.value = { ok: !!data.ok, message: data.message ?? '' };
    } catch {
        testResult.value = { ok: false, message: t('backup.test_failed') };
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <ConfigLayout section="respaldos">
        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <DatabaseBackup class="h-5 w-5 text-brand" /> {{ t('backup.title') }}
            </h2>
            <p class="text-sm text-slate-500">{{ t('backup.subtitle') }}</p>
        </div>

        <form class="max-w-2xl space-y-5" @submit.prevent="submit">
            <!-- Programación -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                    {{ t('backup.enable') }}
                </label>
                <p class="mt-1 text-xs text-slate-400">{{ t('backup.enable_hint') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label :class="label">{{ t('backup.frequency_label') }}</label>
                        <select v-model="form.frequency" :class="input">
                            <option value="daily">{{ t('backup.freq_daily') }}</option>
                            <option value="weekly">{{ t('backup.freq_weekly') }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">{{ t('backup.time_label') }}</label>
                        <input v-model="form.time" type="time" :class="input" />
                        <p v-if="form.errors.time" class="mt-1 text-xs text-red-600">{{ form.errors.time }}</p>
                    </div>
                    <div>
                        <label :class="label">{{ t('backup.retention_label') }}</label>
                        <input v-model.number="form.retention_days" type="number" min="1" max="3650" :class="input" />
                        <p v-if="form.errors.retention_days" class="mt-1 text-xs text-red-600">{{ form.errors.retention_days }}</p>
                    </div>
                </div>
                <p v-if="form.frequency === 'weekly'" class="mt-2 text-xs text-slate-400">{{ t('backup.weekly_hint') }}</p>

                <!-- Nota informativa: la hora se interpreta en UTC (hora del servidor). -->
                <div class="mt-4 flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                    <Clock class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>
                        {{ t('backup.utc_note') }}
                        <strong class="font-semibold text-slate-700 dark:text-slate-200">{{ utcClock }} UTC</strong>
                    </span>
                </div>
            </div>

            <!-- Proveedor de almacenamiento -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-2 text-brand">
                    <Cloud class="h-5 w-5" />
                    <h3 class="text-sm font-semibold">{{ t('backup.provider_section') }}</h3>
                </div>

                <!-- Selector de proveedor -->
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition"
                        :class="isDropbox ? 'border-brand bg-brand/5 text-brand' : 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300'"
                        @click="form.provider = 'dropbox'"
                    >
                        <HardDriveUpload class="h-4 w-4" /> Dropbox
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition"
                        :class="!isDropbox ? 'border-brand bg-brand/5 text-brand' : 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300'"
                        @click="form.provider = 'google_cloud'"
                    >
                        <Cloud class="h-4 w-4" /> Google Cloud
                    </button>
                </div>

                <!-- Campos Dropbox -->
                <div v-if="isDropbox" class="mt-5 space-y-4">
                    <div>
                        <label :class="label">{{ t('backup.dropbox_token_label') }}</label>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 focus-within:border-brand focus-within:bg-white dark:border-slate-600 dark:bg-slate-900">
                            <KeyRound class="h-4 w-4 text-slate-400" />
                            <input
                                v-model="form.dropbox_token" type="password" autocomplete="off"
                                class="w-full bg-transparent py-2 text-sm outline-none"
                                :placeholder="settings.has_dropbox_token ? t('backup.secret_saved') : t('backup.secret_empty')"
                            />
                        </div>
                        <p class="mt-1 flex items-center gap-1 text-xs" :class="settings.has_dropbox_token ? 'text-teal-600' : 'text-slate-400'">
                            <CheckCircle2 v-if="settings.has_dropbox_token" class="h-3.5 w-3.5" />
                            {{ settings.has_dropbox_token ? t('backup.configured') : t('backup.not_configured') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ t('backup.dropbox_token_hint') }}</p>
                        <p v-if="form.errors.dropbox_token" class="mt-1 text-xs text-red-600">{{ form.errors.dropbox_token }}</p>
                    </div>
                    <div>
                        <label :class="label">{{ t('backup.dropbox_folder_label') }}</label>
                        <input v-model="form.dropbox_folder" :class="input" placeholder="/META/backups" />
                    </div>
                </div>

                <!-- Campos Google Cloud -->
                <div v-else class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label :class="label">{{ t('backup.gcs_bucket_label') }}</label>
                            <input v-model="form.gcs_bucket" :class="input" placeholder="mi-bucket-respaldos" />
                            <p v-if="form.errors.gcs_bucket" class="mt-1 text-xs text-red-600">{{ form.errors.gcs_bucket }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('backup.gcs_prefix_label') }}</label>
                            <input v-model="form.gcs_prefix" :class="input" placeholder="meta/backups" />
                        </div>
                    </div>
                    <div>
                        <label :class="label">{{ t('backup.gcs_credentials_label') }}</label>
                        <textarea
                            v-model="form.gcs_credentials" rows="4" autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-xs outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                            :placeholder="settings.has_gcs_credentials ? t('backup.secret_saved') : gcsPlaceholder"
                        />
                        <p class="mt-1 flex items-center gap-1 text-xs" :class="settings.has_gcs_credentials ? 'text-teal-600' : 'text-slate-400'">
                            <CheckCircle2 v-if="settings.has_gcs_credentials" class="h-3.5 w-3.5" />
                            {{ settings.has_gcs_credentials ? t('backup.configured') : t('backup.not_configured') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ t('backup.gcs_credentials_hint') }}</p>
                        <p v-if="form.errors.gcs_credentials" class="mt-1 text-xs text-red-600">{{ form.errors.gcs_credentials }}</p>
                    </div>
                </div>
            </div>

            <!-- Estado del último respaldo -->
            <div v-if="settings.last_run_at" class="flex items-center gap-2 rounded-lg border px-4 py-3 text-sm"
                :class="settings.last_status === 'ok'
                    ? 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900/50 dark:bg-teal-900/20 dark:text-teal-300'
                    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300'"
            >
                <CheckCircle2 v-if="settings.last_status === 'ok'" class="h-4 w-4 shrink-0" />
                <AlertTriangle v-else class="h-4 w-4 shrink-0" />
                <span>{{ t('backup.last_run', { when: settings.last_run_at }) }}</span>
            </div>

            <!-- Resultado de la prueba -->
            <div
                v-if="testResult"
                class="flex items-start gap-2 rounded-lg border px-4 py-3 text-sm"
                :class="testResult.ok
                    ? 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900/50 dark:bg-teal-900/20 dark:text-teal-300'
                    : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300'"
            >
                <CheckCircle2 v-if="testResult.ok" class="mt-0.5 h-4 w-4 shrink-0" />
                <AlertTriangle v-else class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ testResult.message }}</span>
            </div>

            <!-- Historial de respaldos automáticos (colapsable) -->
            <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-5 py-4 text-left"
                    @click="showHistory = !showHistory"
                >
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <History class="h-4 w-4 text-brand" /> {{ t('backup.history_title') }}
                        <span v-if="history.length" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-slate-700 dark:text-slate-300">{{ history.length }}</span>
                    </span>
                    <ChevronDown class="h-5 w-5 text-slate-400 transition-transform" :class="showHistory ? 'rotate-180' : ''" />
                </button>

                <div v-if="showHistory" class="border-t border-slate-100 px-5 py-4 dark:border-slate-700/60">
                    <p v-if="!history.length" class="py-4 text-center text-sm text-slate-400">{{ t('backup.history_empty') }}</p>

                    <div v-else class="space-y-4">
                        <div v-for="g in groupedHistory" :key="g.date">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ g.date }}</p>
                            <ul class="space-y-1.5">
                                <li
                                    v-for="(e, i) in g.items" :key="i"
                                    class="flex items-start gap-3 rounded-lg border px-3 py-2 text-sm"
                                    :class="e.status === 'ok'
                                        ? 'border-teal-200 bg-teal-50/60 dark:border-teal-900/40 dark:bg-teal-900/10'
                                        : 'border-red-200 bg-red-50/60 dark:border-red-900/40 dark:bg-red-900/10'"
                                >
                                    <CheckCircle2 v-if="e.status === 'ok'" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" />
                                    <AlertTriangle v-else class="mt-0.5 h-4 w-4 shrink-0 text-red-600 dark:text-red-400" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                            <span class="font-medium" :class="e.status === 'ok' ? 'text-teal-700 dark:text-teal-300' : 'text-red-700 dark:text-red-300'">
                                                {{ e.status === 'ok' ? t('backup.status_ok') : t('backup.status_error') }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ e.at }}</span>
                                            <span v-if="e.provider" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                                {{ e.provider === 'google_cloud' ? 'Google Cloud' : 'Dropbox' }}
                                            </span>
                                        </div>
                                        <p v-if="e.status !== 'ok' && e.detail" class="mt-0.5 break-words text-xs text-red-600 dark:text-red-400">{{ e.detail }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                >
                    {{ form.processing ? t('backup.saving') : t('backup.save') }}
                </button>
                <button
                    type="button"
                    :disabled="testing"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-5 py-2.5 text-sm font-medium text-brand transition hover:bg-brand hover:text-white disabled:opacity-50"
                    @click="testConnection"
                >
                    <Loader2 v-if="testing" class="h-4 w-4 animate-spin" />
                    <Plug v-else class="h-4 w-4" />
                    {{ testing ? t('backup.testing') : t('backup.test_connection') }}
                </button>
            </div>
        </form>
    </ConfigLayout>
</template>
