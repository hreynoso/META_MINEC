<script setup lang="ts">
import { reactive, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { UploadCloud, ImageIcon, CheckCircle2 } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Assets {
    logo_sidebar: string | null;
    logo_login: string | null;
    login_background: string | null;
    favicon: string | null;
}

interface Colors { sidebar: string; sidebar_hover: string; brand: string }

const props = defineProps<{ assets: Assets; colors: Colors }>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success as string | undefined);

// Colores del sistema (variables CSS del tema).
const colorForm = useForm({
    sidebar: props.colors.sidebar,
    sidebar_hover: props.colors.sidebar_hover,
    brand: props.colors.brand,
});

const colorFields: { key: keyof Colors; title: string; hint: string }[] = [
    { key: 'sidebar', title: t('branding.color_sidebar_title'), hint: t('branding.color_sidebar_hint') },
    { key: 'sidebar_hover', title: t('branding.color_sidebar_hover_title'), hint: t('branding.color_sidebar_hover_hint') },
    { key: 'brand', title: t('branding.color_brand_title'), hint: t('branding.color_brand_hint') },
];

function submitColors() {
    colorForm.post(route('configuracion.branding.colors'), { preserveScroll: true });
}

type FieldKey = keyof Assets;

const fields: { key: FieldKey; title: string; hint: string; dark?: boolean }[] = [
    { key: 'logo_sidebar', title: t('branding.field_logo_sidebar_title'), hint: t('branding.field_logo_sidebar_hint'), dark: true },
    { key: 'logo_login', title: t('branding.field_logo_login_title'), hint: t('branding.field_logo_login_hint') },
    { key: 'login_background', title: t('branding.field_login_background_title'), hint: t('branding.field_login_background_hint') },
    { key: 'favicon', title: t('branding.field_favicon_title'), hint: t('branding.field_favicon_hint') },
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
    <ConfigLayout section="branding">
        <div class="mb-5">
            <h2 class="text-lg font-semibold">{{ t('branding.title') }}</h2>
            <p class="text-sm text-slate-500">{{ t('branding.subtitle') }}</p>
        </div>

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
                            <ImageIcon class="h-6 w-6" /> {{ t('branding.empty_preview') }}
                        </span>
                    </div>

                    <!-- Selector de archivo -->
                    <label
                        class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                    >
                        <UploadCloud class="h-4 w-4" />
                        <span>{{ form[f.key] ? (form[f.key] as File).name : t('branding.select_file') }}</span>
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
                    {{ form.processing ? t('branding.saving') : t('branding.save_changes') }}
                </button>
                <span v-if="!hasChanges" class="text-xs text-slate-400">{{ t('branding.select_at_least_one') }}</span>
            </div>
        </form>

        <!-- Colores del sistema -->
        <form class="mt-8" @submit.prevent="submitColors">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ t('branding.colors_title') }}</h2>
            <p class="mb-4 text-xs text-slate-500">{{ t('branding.colors_subtitle') }}</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div v-for="c in colorFields" :key="c.key" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-sm font-medium">{{ c.title }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ c.hint }}</p>
                    <div class="mt-3 flex items-center gap-3">
                        <input v-model="colorForm[c.key]" type="color" class="h-10 w-14 cursor-pointer rounded border border-slate-300 dark:border-slate-600" />
                        <input v-model="colorForm[c.key]" type="text" class="w-28 rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 font-mono text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900" />
                    </div>
                    <p v-if="colorForm.errors[c.key]" class="mt-1 text-xs text-red-600">{{ colorForm.errors[c.key] }}</p>
                </div>
            </div>

            <button
                type="submit"
                :disabled="colorForm.processing"
                class="mt-6 rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
            >
                {{ colorForm.processing ? t('branding.saving') : t('branding.save_colors') }}
            </button>
        </form>
    </ConfigLayout>
</template>
