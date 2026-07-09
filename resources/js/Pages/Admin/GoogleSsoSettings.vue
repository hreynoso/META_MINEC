<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { KeyRound, CheckCircle2, AlertTriangle, Copy } from 'lucide-vue-next';

interface Settings {
    client_id: string;
    redirect: string;
    hosted_domain: string;
    has_secret: boolean;
    configured: boolean;
}

const props = defineProps<{ settings: Settings; callbackUrl: string }>();

const form = useForm({
    client_id: props.settings.client_id || '',
    client_secret: '',
    redirect: props.settings.redirect || props.callbackUrl,
    hosted_domain: props.settings.hosted_domain || '',
});

const input =
    'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function copyCallback() {
    navigator.clipboard?.writeText(props.callbackUrl);
}

function submit() {
    form.post(route('configuracion.sso.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('client_secret'),
    });
}
</script>

<template>
    <ConfigLayout section="sso">
        <div class="mb-5">
            <h2 class="text-lg font-semibold">SSO Google Workspace</h2>
            <p class="text-sm text-slate-500">Credenciales OAuth para el inicio de sesión con Google. Las provee el administrador de TI desde Google Cloud Console.</p>
        </div>

        <form class="max-w-2xl" @submit.prevent="submit">
            <!-- Estado -->
            <div
                class="mb-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm"
                :class="settings.configured
                    ? 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900/50 dark:bg-teal-900/20 dark:text-teal-300'
                    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300'"
            >
                <CheckCircle2 v-if="settings.configured" class="h-4 w-4" />
                <AlertTriangle v-else class="h-4 w-4" />
                {{ settings.configured
                    ? 'El SSO de Google está configurado.'
                    : 'Falta información: el SSO de Google aún no está completo.' }}
            </div>

            <!-- URL de retorno para TI -->
            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">URL de retorno autorizada (dásela a TI)</p>
                <div class="mt-1 flex items-center gap-2">
                    <code class="flex-1 break-all rounded bg-white px-2 py-1 text-xs dark:bg-slate-800">{{ callbackUrl }}</code>
                    <button type="button" class="rounded p-1.5 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700" title="Copiar" @click="copyCallback">
                        <Copy class="h-4 w-4" />
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-400">TI debe registrar exactamente esta URL en «URIs de redireccionamiento autorizados» del cliente OAuth en Google Cloud Console.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-2 text-brand">
                    <KeyRound class="h-5 w-5" />
                    <h2 class="text-sm font-semibold">Credenciales OAuth 2.0</h2>
                </div>

                <div class="mt-4">
                    <label :class="label">ID de cliente (Client ID)</label>
                    <input v-model="form.client_id" :class="input" placeholder="xxxxxxxx.apps.googleusercontent.com" />
                    <p v-if="form.errors.client_id" class="mt-1 text-xs text-red-600">{{ form.errors.client_id }}</p>
                </div>

                <div class="mt-4">
                    <label :class="label">Secreto de cliente (Client Secret)</label>
                    <input
                        v-model="form.client_secret" type="password" autocomplete="off" :class="input"
                        :placeholder="settings.has_secret ? '•••••••• (guardado — escribe para reemplazar)' : 'Pega aquí el secreto de cliente'"
                    />
                    <p class="mt-1 flex items-center gap-1 text-xs" :class="settings.has_secret ? 'text-teal-600' : 'text-slate-400'">
                        <CheckCircle2 v-if="settings.has_secret" class="h-3.5 w-3.5" />
                        {{ settings.has_secret ? 'Hay un secreto configurado. Se conserva si dejas el campo vacío.' : 'Aún no hay secreto configurado.' }}
                    </p>
                    <p v-if="form.errors.client_secret" class="mt-1 text-xs text-red-600">{{ form.errors.client_secret }}</p>
                </div>

                <div class="mt-4">
                    <label :class="label">URL de retorno (Redirect URI)</label>
                    <input v-model="form.redirect" :class="input" :placeholder="callbackUrl" />
                    <p class="mt-1 text-xs text-slate-400">Debe coincidir con la URL autorizada en Google Cloud Console (normalmente la de arriba).</p>
                    <p v-if="form.errors.redirect" class="mt-1 text-xs text-red-600">{{ form.errors.redirect }}</p>
                </div>

                <div class="mt-4">
                    <label :class="label">Dominio de Workspace permitido</label>
                    <input v-model="form.hosted_domain" :class="input" placeholder="economia.gob.sv" />
                    <p class="mt-1 text-xs text-slate-400">Restringe el acceso a cuentas de este dominio. Déjalo vacío para permitir cualquier cuenta de Google.</p>
                    <p v-if="form.errors.hosted_domain" class="mt-1 text-xs text-red-600">{{ form.errors.hosted_domain }}</p>
                </div>
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
    </ConfigLayout>
</template>
