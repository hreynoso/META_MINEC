<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ institution: string; demoEnabled: boolean }>();

const page = usePage();
const flashError = computed(() => (page.props.flash as any)?.error as string | undefined);

const form = useForm({ email: '', password: '' });

function submitDemo() {
    form.post(route('demo.login'), { preserveScroll: true });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-900 text-slate-100">
        <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-800 p-8 text-center shadow-xl">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand/20 text-brand">
                <span class="text-lg font-bold">M</span>
            </div>
            <h1 class="mt-4 text-2xl font-bold">Sistema META</h1>
            <p class="mt-1 text-sm text-slate-400">Monitoreo Estratégico de Acciones</p>
            <p class="text-xs text-slate-500">{{ institution }}</p>

            <a
                :href="route('azure.redirect')"
                class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-medium text-white transition hover:opacity-90"
            >
                Iniciar sesión con Office 365
            </a>

            <!-- Acceso temporal de demo -->
            <div v-if="demoEnabled" class="mt-6 border-t border-slate-700 pt-6 text-left">
                <p class="mb-3 text-center text-xs uppercase tracking-wide text-slate-500">Acceso temporal (demo)</p>

                <p v-if="flashError" class="mb-3 rounded-lg bg-red-500/10 px-3 py-2 text-center text-xs text-red-300">
                    {{ flashError }}
                </p>

                <label class="mb-1 block text-xs text-slate-400">Correo</label>
                <input
                    v-model="form.email" type="email" autocomplete="username"
                    class="mb-3 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm outline-none focus:border-brand"
                    @keyup.enter="submitDemo"
                />
                <label class="mb-1 block text-xs text-slate-400">Contraseña</label>
                <input
                    v-model="form.password" type="password" autocomplete="current-password"
                    class="mb-4 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm outline-none focus:border-brand"
                    @keyup.enter="submitDemo"
                />
                <button
                    :disabled="form.processing"
                    class="w-full rounded-lg border border-brand px-4 py-2 text-sm font-medium text-brand transition hover:bg-brand hover:text-white disabled:opacity-50"
                    @click="submitDemo"
                >
                    Entrar (demo)
                </button>
            </div>

            <p class="mt-6 text-xs text-slate-500">
                Cooperación técnica República Dominicana → El Salvador
            </p>
        </div>
    </div>
</template>
