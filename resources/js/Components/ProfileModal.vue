<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { X, Camera, Save, Mail, ShieldCheck, UserRound, LogOut } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({ useScope: 'global' });

const emit = defineEmits<{ (e: 'close'): void }>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user ?? {});
const roles = computed<string[]>(() => (page.props.auth as any)?.roles ?? []);
const roleLabel = computed(() => (roles.value.length ? roles.value.join(', ') : t('profile.no_role_assigned')));

// Vista previa local de la foto seleccionada antes de subirla.
const preview = ref<string | null>(null);
const form = useForm<{ photo: File | null }>({ photo: null });

function onFile(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    preview.value = file ? URL.createObjectURL(file) : null;
}

function submit() {
    form.post(route('perfil.foto.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            preview.value = null;
            emit('close');
        },
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-semibold">{{ t('profile.title') }}</h2>
                <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="emit('close')">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Avatar + botón de cambiar foto -->
            <div class="mt-6 flex flex-col items-center">
                <div class="relative h-24 w-24">
                    <img
                        v-if="preview || user.avatar"
                        :src="preview || user.avatar"
                        :alt="t('profile.photo_alt')"
                        class="h-24 w-24 rounded-full object-cover ring-2 ring-brand/30"
                    />
                    <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-brand text-3xl font-semibold text-white">
                        {{ (user.name ?? 'U').slice(0, 1) }}
                    </div>
                    <label
                        class="absolute bottom-0 right-0 flex cursor-pointer items-center justify-center rounded-full bg-brand p-2 text-white shadow transition hover:opacity-90"
                        :title="t('profile.change_photo')"
                    >
                        <Camera class="h-4 w-4" />
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onFile" />
                    </label>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ t('profile.change_photo') }}</p>
                <p v-if="form.errors.photo" class="mt-1 text-xs text-red-600">{{ form.errors.photo }}</p>
            </div>

            <!-- Información -->
            <div class="mt-6 space-y-4 text-sm">
                <div class="flex items-start gap-3">
                    <UserRound class="mt-0.5 h-4 w-4 text-slate-400" />
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('profile.full_name') }}</p>
                        <p class="font-medium">{{ user.name ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <Mail class="mt-0.5 h-4 w-4 text-slate-400" />
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('profile.email') }}</p>
                        <p class="font-medium">{{ user.email ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <ShieldCheck class="mt-0.5 h-4 w-4 text-slate-400" />
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('profile.role') }}</p>
                        <p class="font-medium">{{ roleLabel }}</p>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="mt-6 flex items-center justify-between gap-2">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:hover:bg-red-900/30"
                >
                    <LogOut class="h-4 w-4" /> {{ t('profile.logout') }}
                </Link>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                        @click="emit('close')"
                    >
                        {{ t('actions.close') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!form.photo || form.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                        @click="submit"
                    >
                        <Save class="h-4 w-4" /> {{ t('profile.save_photo') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
