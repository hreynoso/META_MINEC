<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Sparkles, KeyRound, CheckCircle2, AlertTriangle, Loader2, Plug } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Settings {
    provider: string;
    model: string;
    enabled: boolean;
    has_key: boolean;
}

interface AiModel { value: string; label: string }
interface Provider { value: string; label: string; recommended: string; models: AiModel[] }

const props = defineProps<{ settings: Settings }>();

const PROVIDERS: Provider[] = [
    {
        value: 'anthropic', label: 'Anthropic (Claude)', recommended: 'claude-sonnet-5',
        models: [
            { value: 'claude-sonnet-5', label: 'Claude Sonnet 5 (recomendado)' },
            { value: 'claude-opus-4-8', label: 'Claude Opus 4.8' },
            { value: 'claude-haiku-4-5-20251001', label: 'Claude Haiku 4.5' },
        ],
    },
    {
        value: 'gemini', label: 'Google Gemini', recommended: 'gemini-1.5-pro',
        models: [
            { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro (recomendado)' },
            { value: 'gemini-1.5-flash', label: 'Gemini 1.5 Flash' },
            { value: 'gemini-2.0-flash', label: 'Gemini 2.0 Flash' },
        ],
    },
    {
        value: 'openai', label: 'OpenAI', recommended: 'gpt-4o',
        models: [
            { value: 'gpt-4o', label: 'GPT-4o (recomendado)' },
            { value: 'gpt-4o-mini', label: 'GPT-4o mini' },
            { value: 'gpt-4-turbo', label: 'GPT-4 Turbo' },
        ],
    },
];

const form = useForm({
    provider: props.settings.provider || 'anthropic',
    model: props.settings.model || '',
    enabled: props.settings.enabled,
    api_key: '',
});

const currentModels = computed(() => PROVIDERS.find((p) => p.value === form.provider)?.models ?? []);

// Al cambiar de proveedor, se autoselecciona el modelo recomendado.
watch(() => form.provider, (prov) => {
    const p = PROVIDERS.find((x) => x.value === prov);
    if (p) form.model = p.recommended;
});

// Si el modelo guardado no pertenece al proveedor actual, cae al recomendado.
onMounted(() => {
    const p = PROVIDERS.find((x) => x.value === form.provider);
    if (p && !p.models.some((m) => m.value === form.model)) form.model = p.recommended;
});

const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function submit() {
    form.post(route('configuracion.ia.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('api_key'),
    });
}

// Prueba de conexión con el proveedor (usa la clave escrita o la guardada).
const testing = ref(false);
const testResult = ref<{ ok: boolean; message: string } | null>(null);

async function testConnection() {
    testing.value = true;
    testResult.value = null;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(route('configuracion.ia.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ provider: form.provider, model: form.model, api_key: form.api_key }),
        });
        const data = await res.json();
        testResult.value = { ok: !!data.ok, message: data.message ?? '' };
    } catch {
        testResult.value = { ok: false, message: t('ai.test_failed') };
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <ConfigLayout section="ia">
        <div class="mb-5">
            <h2 class="text-lg font-semibold">{{ t('ai.title') }}</h2>
            <p class="text-sm text-slate-500">{{ t('ai.subtitle') }}</p>
        </div>

        <form class="max-w-2xl" @submit.prevent="submit">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-2 text-brand">
                    <Sparkles class="h-5 w-5" />
                    <h2 class="text-sm font-semibold">{{ t('ai.provider_section') }}</h2>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">{{ t('ai.provider_label') }}</label>
                        <select v-model="form.provider" :class="input">
                            <option v-for="p in PROVIDERS" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <p v-if="form.errors.provider" class="mt-1 text-xs text-red-600">{{ form.errors.provider }}</p>
                    </div>
                    <div>
                        <label :class="label">{{ t('ai.model_label') }}</label>
                        <select v-model="form.model" :class="input">
                            <option v-for="m in currentModels" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-400">{{ t('ai.models_hint') }}</p>
                        <p v-if="form.errors.model" class="mt-1 text-xs text-red-600">{{ form.errors.model }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label :class="label">{{ t('ai.api_key_label') }}</label>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 focus-within:border-brand focus-within:bg-white dark:border-slate-600 dark:bg-slate-900">
                        <KeyRound class="h-4 w-4 text-slate-400" />
                        <input
                            v-model="form.api_key" type="password" autocomplete="off"
                            class="w-full bg-transparent py-2 text-sm outline-none"
                            :placeholder="settings.has_key ? t('ai.api_key_placeholder_saved') : t('ai.api_key_placeholder_empty')"
                        />
                    </div>
                    <p class="mt-1 flex items-center gap-1 text-xs" :class="settings.has_key ? 'text-teal-600' : 'text-slate-400'">
                        <CheckCircle2 v-if="settings.has_key" class="h-3.5 w-3.5" />
                        {{ settings.has_key ? t('ai.key_configured') : t('ai.key_not_configured') }}
                    </p>
                    <p v-if="form.errors.api_key" class="mt-1 text-xs text-red-600">{{ form.errors.api_key }}</p>
                </div>

                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                    {{ t('ai.enable_ai') }}
                </label>
            </div>

            <!-- Resultado de la prueba de conexión -->
            <div
                v-if="testResult"
                class="mt-4 flex items-start gap-2 rounded-lg border px-4 py-3 text-sm"
                :class="testResult.ok
                    ? 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900/50 dark:bg-teal-900/20 dark:text-teal-300'
                    : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300'"
            >
                <CheckCircle2 v-if="testResult.ok" class="mt-0.5 h-4 w-4 shrink-0" />
                <AlertTriangle v-else class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ testResult.message }}</span>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                >
                    {{ form.processing ? t('ai.saving') : t('ai.save_config') }}
                </button>
                <button
                    type="button"
                    :disabled="testing"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-5 py-2.5 text-sm font-medium text-brand transition hover:bg-brand hover:text-white disabled:opacity-50"
                    @click="testConnection"
                >
                    <Loader2 v-if="testing" class="h-4 w-4 animate-spin" />
                    <Plug v-else class="h-4 w-4" />
                    {{ testing ? t('ai.testing') : t('ai.test_connection') }}
                </button>
            </div>
        </form>
    </ConfigLayout>
</template>
