<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

defineProps<{
    otherDevice: { label: string; ip: string | null; lastActive: string | null };
}>();

const { t } = useI18n({ useScope: 'global' });

const page = usePage();
const assets = computed(() => (page.props.branding as any)?.assets ?? {});
const logoLogin = computed(() => assets.value.logo_login as string | undefined);
const loginBackground = computed(() => assets.value.login_background as string | undefined);
const bgStyle = computed(() => loginBackground.value
    ? { backgroundImage: `url(${loginBackground.value})`, backgroundSize: 'cover', backgroundPosition: 'center' }
    : undefined);

const continueForm = useForm({});
const cancelForm = useForm({});

function continueHere() {
    continueForm.post(route('device.conflict.continue'));
}

function cancel() {
    cancelForm.post(route('device.conflict.cancel'));
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-900 bg-cover bg-center px-6 text-slate-100 lg:justify-end lg:pr-24 xl:pr-40" :style="bgStyle">
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-700 shadow-2xl">
            <img
                v-if="logoLogin"
                :src="logoLogin"
                alt="Logo institucional"
                class="mx-auto h-auto max-h-32 w-auto max-w-full object-contain"
            />

            <div class="mx-auto mt-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.36 3.591 2.658 16.5a1.5 1.5 0 0 0 1.29 2.25h15.804a1.5 1.5 0 0 0 1.29-2.25L14.64 3.59a1.5 1.5 0 0 0-2.58 0Z" />
                </svg>
            </div>

            <h1 class="mt-4 text-xl font-bold text-slate-900">{{ t('auth.device.title') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ t('auth.device.intro') }}</p>

            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm">
                <p class="font-medium text-slate-800">{{ otherDevice.label }}</p>
                <p v-if="otherDevice.ip" class="text-xs text-slate-500">{{ t('auth.device.ip') }}: {{ otherDevice.ip }}</p>
                <p v-if="otherDevice.lastActive" class="text-xs text-slate-500">{{ t('auth.device.last_activity') }}: {{ otherDevice.lastActive }}</p>
            </div>

            <p class="mt-4 text-sm text-slate-600">{{ t('auth.device.question') }}</p>

            <div class="mt-6 flex gap-3">
                <button
                    :disabled="continueForm.processing || cancelForm.processing"
                    class="flex-1 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-50"
                    @click="continueHere"
                >
                    {{ t('actions.yes_continue') }}
                </button>
                <button
                    :disabled="continueForm.processing || cancelForm.processing"
                    class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                    @click="cancel"
                >
                    {{ t('actions.no') }}
                </button>
            </div>
        </div>
    </div>
</template>
