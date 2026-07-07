<script setup lang="ts">
import { reactive, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { UploadCloud, ImageIcon, CheckCircle2 } from 'lucide-vue-next';

interface Assets {
    logo_sidebar: string | null;
    logo_login: string | null;
    login_background: string | null;
    favicon: string | null;
}

const props = defineProps<{ assets: Assets }>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success as string | undefined);

type FieldKey = keyof Assets;

const fields: { key: FieldKey; title: string; hint: string; dark?: boolean }[] = [
    { key: 'logo_sidebar', title: 'Logo del sidebar', hint: 'PNG o SVG con fondo transparente. Se muestra sobre el fondo azul del menú.', dark: true },
    { key: 'logo_login', title: 'Logo del login', hint: 'PNG o SVG. Aparece en la tarjeta de inicio de sesión.' },
    { key: 'login_background', title: 'Imagen de fondo del login', hint: 'JPG o PNG apaisado (ej. 1920×1080).' },
    { key: 'favicon', title: 'Favicon', hint: 'ICO, PNG o SVG cuadrado (ej. 32×32 o 64×64).' },
];

const form = useForm<Record<FieldKey, File | null>>({
    logo_sidebar: null,
    logo_login: null,
    login_background: null,
    favicon: null,
});

// Vista previa local del archivo recién seleccionado (object URL).
const previews = reactive<Record<string, string | null>>({
    logo_sidebar: null, logo_login: null, login_background: null, favicon: null,
});

function onFile(key: FieldKey, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form[key] = file;
    previews[key] = file ? URL.createObjectURL(file) : null;
}

// URL a mostrar: preview local si hay archivo nuevo, si no el guardado.
function currentSrc(key: FieldKey): string | null {
    return previews[key] ?? props.assets[key];
}

const hasChanges = computed(() => fields.some((f) => form[f.key] instanceof File));

function submit() {
    form.post(route('configuracion.branding.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Object.keys(previews).forEach((k) => (previews[k] = null));
        },
    });
}
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">Configuración · Identidad visual</h1>
            <p class="text-sm text-slate-500">Carga el logo del menú, el logo y el fondo del login, y el favicon del sistema.</p>
        </header>

        <p v-if="flashSuccess" class="mb-4 flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm text-teal-800">
            <CheckCircle2 class="h-4 w-4" /> {{ flashSuccess }}
        </p>

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div
                    v-for="f in fields"
                    :key="f.key"
                    class="rounded-xl border border-slate-200 bg-white p-5"
                >
                    <h2 class="text-sm font-semibold text-slate-800">{{ f.title }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ f.hint }}</p>

                    <!-- Vista previa -->
                    <div
                        class="mt-3 flex h-32 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300"
                        :class="f.dark ? 'bg-shell' : 'bg-slate-50'"
                    >
                        <img
                            v-if="currentSrc(f.key)"
                            :src="currentSrc(f.key)!"
                            :alt="f.title"
                            class="max-h-28 max-w-full object-contain"
                            :class="f.key === 'login_background' ? 'h-full w-full object-cover' : ''"
                        />
                        <span v-else class="flex flex-col items-center gap-1 text-xs" :class="f.dark ? 'text-slate-400' : 'text-slate-400'">
                            <ImageIcon class="h-6 w-6" /> Sin cargar
                        </span>
                    </div>

                    <!-- Selector de archivo -->
                    <label
                        class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                    >
                        <UploadCloud class="h-4 w-4" />
                        <span>{{ form[f.key] ? (form[f.key] as File).name : 'Seleccionar archivo…' }}</span>
                        <input type="file" class="hidden" accept="image/*,.ico,.svg" @change="onFile(f.key, $event)" />
                    </label>

                    <p v-if="form.errors[f.key]" class="mt-2 text-xs text-red-600">{{ form.errors[f.key] }}</p>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="!hasChanges || form.processing"
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                </button>
                <span v-if="!hasChanges" class="text-xs text-slate-400">Selecciona al menos un archivo para guardar.</span>
            </div>
        </form>
    </AppLayout>
</template>
