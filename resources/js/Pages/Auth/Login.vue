<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{ institution: string; demoEnabled: boolean }>();

const page = usePage();
const flashError = computed(() => (page.props.flash as any)?.error as string | undefined);

// Color dominante extraído del logo del login (fallback: color de marca).
const logoColor = ref<string | null>(null);
const btnStyle = computed(() => (logoColor.value ? { backgroundColor: logoColor.value } : undefined));

function extractColor(event: Event) {
    const img = event.target as HTMLImageElement;
    try {
        const size = 24;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.drawImage(img, 0, 0, size, size);
        const { data } = ctx.getImageData(0, 0, size, size);

        // Cuantización + frecuencia para hallar el color dominante (ignora
        // transparente, casi blanco y casi negro).
        const buckets = new Map<string, { n: number; r: number; g: number; b: number }>();
        for (let i = 0; i < data.length; i += 4) {
            const [r, g, b, a] = [data[i], data[i + 1], data[i + 2], data[i + 3]];
            if (a < 128) continue;
            if (r > 235 && g > 235 && b > 235) continue;
            if (r < 20 && g < 20 && b < 20) continue;
            const key = `${r >> 4}-${g >> 4}-${b >> 4}`;
            const cur = buckets.get(key) ?? { n: 0, r: 0, g: 0, b: 0 };
            buckets.set(key, { n: cur.n + 1, r: cur.r + r, g: cur.g + g, b: cur.b + b });
        }

        let best: { n: number; r: number; g: number; b: number } | null = null;
        for (const v of buckets.values()) if (!best || v.n > best.n) best = v;
        if (best) logoColor.value = `rgb(${Math.round(best.r / best.n)}, ${Math.round(best.g / best.n)}, ${Math.round(best.b / best.n)})`;
    } catch {
        // Imagen "tainted" u otro error → se conserva el color de marca.
    }
}

// Assets de marca administrables desde Configuración.
const assets = computed(() => (page.props.branding as any)?.assets ?? {});
const logoLogin = computed(() => assets.value.logo_login as string | undefined);
const loginBackground = computed(() => assets.value.login_background as string | undefined);
const bgStyle = computed(() => loginBackground.value
    ? { backgroundImage: `url(${loginBackground.value})`, backgroundSize: 'cover', backgroundPosition: 'center' }
    : undefined);

const form = useForm({ email: '', password: '' });

function submitDemo() {
    form.post(route('demo.login'), { preserveScroll: true });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-900 bg-cover bg-center px-6 text-slate-100 lg:justify-end lg:pr-24 xl:pr-40" :style="bgStyle">
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-700 shadow-2xl">
            <img
                v-if="logoLogin"
                :src="logoLogin"
                alt="Logo institucional"
                crossorigin="anonymous"
                class="mx-auto h-auto max-h-48 w-auto max-w-full object-contain"
                @load="extractColor"
            />
            <div v-else class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand/10 text-brand">
                <span class="text-lg font-bold">M</span>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-slate-900">Sistema META</h1>
            <p class="mt-1 text-sm text-slate-500">Monitoreo Estratégico de Acciones</p>
            <p class="text-xs text-slate-400">{{ institution }}</p>

            <a
                :href="route('google.redirect')"
                :style="btnStyle"
                class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-medium text-white transition hover:opacity-90"
            >
                <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
                Iniciar sesión con Google Workspace
            </a>

            <!-- Acceso temporal de demo -->
            <div v-if="demoEnabled" class="mt-6 border-t border-slate-200 pt-6 text-left">
                <p class="mb-3 text-center text-xs uppercase tracking-wide text-slate-400">Acceso temporal (demo)</p>

                <p v-if="flashError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-center text-xs text-red-600">
                    {{ flashError }}
                </p>

                <label class="mb-1 block text-xs text-slate-500">Correo</label>
                <input
                    v-model="form.email" type="email" autocomplete="username"
                    class="mb-3 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none focus:border-brand focus:bg-white"
                    @keyup.enter="submitDemo"
                />
                <label class="mb-1 block text-xs text-slate-500">Contraseña</label>
                <input
                    v-model="form.password" type="password" autocomplete="current-password"
                    class="mb-4 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none focus:border-brand focus:bg-white"
                    @keyup.enter="submitDemo"
                />
                <button
                    :disabled="form.processing"
                    :style="btnStyle"
                    class="w-full rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                    @click="submitDemo"
                >
                    Acceder
                </button>
            </div>

            <p class="mt-6 text-xs text-slate-400">
                Developed by CityWorks, Powered by Google Cloud Platform
            </p>
        </div>
    </div>
</template>
