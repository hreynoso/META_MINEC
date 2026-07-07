<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfigTabs from '@/Components/ConfigTabs.vue';
import { Sparkles, KeyRound, CheckCircle2 } from 'lucide-vue-next';

interface Settings {
    provider: string;
    model: string;
    enabled: boolean;
    has_key: boolean;
}

const props = defineProps<{ settings: Settings }>();

const PROVIDERS = [
    { value: 'anthropic', label: 'Anthropic (Claude)', hint: 'Ej. claude-sonnet-4-6' },
    { value: 'gemini', label: 'Google Gemini', hint: 'Ej. gemini-1.5-pro' },
    { value: 'openai', label: 'OpenAI', hint: 'Ej. gpt-4o' },
];

const form = useForm({
    provider: props.settings.provider || 'anthropic',
    model: props.settings.model || '',
    enabled: props.settings.enabled,
    api_key: '',
});

const modelHint = computed(() => PROVIDERS.find((p) => p.value === form.provider)?.hint ?? '');

const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function submit() {
    form.post(route('configuracion.ia.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('api_key'),
    });
}
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">Configuración · Inteligencia Artificial</h1>
            <p class="text-sm text-slate-500">Proveedor y credenciales del API de IA para el informe presidencial y las alertas predictivas.</p>
        </header>

        <ConfigTabs />

        <form class="max-w-2xl" @submit.prevent="submit">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-2 text-brand">
                    <Sparkles class="h-5 w-5" />
                    <h2 class="text-sm font-semibold">Proveedor de IA</h2>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Proveedor</label>
                        <select v-model="form.provider" :class="input">
                            <option v-for="p in PROVIDERS" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <p v-if="form.errors.provider" class="mt-1 text-xs text-red-600">{{ form.errors.provider }}</p>
                    </div>
                    <div>
                        <label :class="label">Modelo</label>
                        <input v-model="form.model" :class="input" :placeholder="modelHint" />
                        <p class="mt-1 text-xs text-slate-400">Déjalo vacío para usar el modelo por defecto del proveedor.</p>
                        <p v-if="form.errors.model" class="mt-1 text-xs text-red-600">{{ form.errors.model }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label :class="label">Clave del API</label>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 focus-within:border-brand focus-within:bg-white dark:border-slate-600 dark:bg-slate-900">
                        <KeyRound class="h-4 w-4 text-slate-400" />
                        <input
                            v-model="form.api_key" type="password" autocomplete="off"
                            class="w-full bg-transparent py-2 text-sm outline-none"
                            :placeholder="settings.has_key ? '•••••••• (clave guardada — escribe para reemplazar)' : 'Pega aquí la clave del API'"
                        />
                    </div>
                    <p class="mt-1 flex items-center gap-1 text-xs" :class="settings.has_key ? 'text-teal-600' : 'text-slate-400'">
                        <CheckCircle2 v-if="settings.has_key" class="h-3.5 w-3.5" />
                        {{ settings.has_key ? 'Hay una clave configurada. Se conserva si dejas el campo vacío.' : 'Aún no hay clave configurada.' }}
                    </p>
                    <p v-if="form.errors.api_key" class="mt-1 text-xs text-red-600">{{ form.errors.api_key }}</p>
                </div>

                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                    Habilitar generación con IA (informe presidencial)
                </label>
            </div>

            <div class="mt-6">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                >
                    {{ form.processing ? 'Guardando…' : 'Guardar configuración' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
