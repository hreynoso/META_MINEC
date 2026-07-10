<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { Languages, Check, Save, AlertTriangle, X } from 'lucide-vue-next';

const props = defineProps<{
    current: string;
    options: { code: string; label: string }[];
}>();

const { t } = useI18n({ useScope: 'global' });

const form = useForm({ locale: props.current });

function choose(code: string) {
    form.locale = code;
}

// Confirmación: el cambio afecta a TODO el sistema y a TODOS los usuarios, por lo
// que se exige escribir la palabra de confirmación antes de aplicar.
const confirmOpen = ref(false);
const confirmText = ref('');

const canConfirm = computed(
    () => confirmText.value.trim().toLowerCase() === t('config.language.confirm_word').toLowerCase(),
);

function openConfirm() {
    confirmText.value = '';
    confirmOpen.value = true;
}

function apply() {
    if (!canConfirm.value) return;
    form.post(route('configuracion.idioma.update'), {
        preserveScroll: true,
        onSuccess: () => { confirmOpen.value = false; confirmText.value = ''; },
    });
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
                    @click="openConfirm"
                >
                    <Save class="h-4 w-4" /> {{ t('actions.save') }}
                </button>
            </div>
        </div>

        <!-- Modal de confirmación (cambio global) -->
        <Teleport to="body">
            <div
                v-if="confirmOpen"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6"
                @click.self="confirmOpen = false"
            >
                <div class="mt-20 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                <AlertTriangle class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold">{{ t('config.language.confirm_title') }}</h2>
                            </div>
                        </div>
                        <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="confirmOpen = false">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ t('config.language.confirm_message') }}</p>

                    <label class="mt-4 block text-xs font-medium text-slate-500">
                        {{ t('config.language.confirm_input_label', { word: t('config.language.confirm_word') }) }}
                    </label>
                    <input
                        v-model="confirmText"
                        type="text"
                        autocomplete="off"
                        :placeholder="t('config.language.confirm_word')"
                        class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                        @keyup.enter="apply"
                    />

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="confirmOpen = false"
                        >
                            {{ t('actions.cancel') }}
                        </button>
                        <button
                            type="button"
                            :disabled="!canConfirm || form.processing"
                            class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="apply"
                        >
                            {{ t('config.language.confirm_ok') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </ConfigLayout>
</template>
