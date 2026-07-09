<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Languages, Check, Save } from 'lucide-vue-next';

const props = defineProps<{
    current: string;
    options: { code: string; label: string }[];
}>();

const { t } = useI18n({ useScope: 'global' });

const form = useForm({ locale: props.current });

function choose(code: string) {
    form.locale = code;
}

function submit() {
    form.post(route('configuracion.idioma.update'), { preserveScroll: true });
}
</script>

<template>
    <ConfigLayout section="idioma">
        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <Languages class="h-5 w-5 text-brand" /> {{ t('config.language.title') }}
            </h2>
            <p class="text-sm text-slate-500">{{ t('config.language.description') }}</p>
        </div>

        <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('config.language.label') }}</p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button
                    v-for="o in options" :key="o.code" type="button"
                    class="flex items-center justify-between rounded-xl border px-4 py-3 text-left transition"
                    :class="form.locale === o.code
                        ? 'border-brand bg-brand/5 ring-1 ring-brand'
                        : 'border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700/50'"
                    @click="choose(o.code)"
                >
                    <span>
                        <span class="block text-sm font-medium">{{ o.label }}</span>
                        <span class="block text-xs uppercase tracking-wide text-slate-400">{{ o.code }}</span>
                    </span>
                    <span
                        class="flex h-5 w-5 items-center justify-center rounded-full border"
                        :class="form.locale === o.code ? 'border-brand bg-brand text-white' : 'border-slate-300 dark:border-slate-600'"
                    >
                        <Check v-if="form.locale === o.code" class="h-3.5 w-3.5" />
                    </span>
                </button>
            </div>

            <p class="mt-4 text-xs text-slate-400">{{ t('config.language.note') }}</p>

            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    :disabled="form.processing || form.locale === current"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                    @click="submit"
                >
                    <Save class="h-4 w-4" /> {{ t('actions.save') }}
                </button>
            </div>
        </div>
    </ConfigLayout>
</template>
