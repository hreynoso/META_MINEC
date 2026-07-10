<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Plus, Pencil, Trash2, X, Save } from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

interface Role { id: number; name: string; permissions: string[]; users_count: number }
interface Group { group: string; permissions: { name: string; label: string }[] }

const props = defineProps<{ roles: Role[]; catalog: Group[] }>();

const { ask } = useConfirm();
const open = ref(false);
const editing = ref<Role | null>(null);

const form = useForm<{ name: string; permissions: string[] }>({ name: '', permissions: [] });

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(r: Role) {
    editing.value = r;
    form.clearErrors();
    form.name = r.name;
    form.permissions = [...r.permissions];
    open.value = true;
}

function togglePerm(name: string) {
    const i = form.permissions.indexOf(name);
    if (i === -1) form.permissions.push(name);
    else form.permissions.splice(i, 1);
}

function submit() {
    const opts = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    if (editing.value) form.put(route('configuracion.roles.update', editing.value.id), opts);
    else form.post(route('configuracion.roles.store'), opts);
}

function confirmDelete(r: Role) {
    ask({
        header: t('roles.delete_header'),
        message: t('roles.delete_confirm', { name: r.name }),
        acceptLabel: t('actions.delete'),
        danger: true,
        accept: () => router.delete(route('configuracion.roles.destroy', r.id), { preserveScroll: true }),
    });
}
</script>

<template>
    <ConfigLayout section="roles">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ t('roles.title') }}</h2>
                <p class="text-sm text-slate-500">{{ t('roles.subtitle') }}</p>
            </div>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                <Plus class="h-4 w-4" /> {{ t('roles.new_role') }}
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="r in roles" :key="r.id" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold">{{ r.name }}</h3>
                        <p class="text-xs text-slate-400">{{ t('roles.users_count', { count: r.users_count }) }} · {{ t('roles.permissions_count', { count: r.permissions.length }) }}</p>
                    </div>
                    <div class="flex gap-1">
                        <button class="rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('roles.edit')" @click="openEdit(r)"><Pencil class="h-4 w-4" /></button>
                        <button v-if="r.name !== 'Administrador'" class="rounded p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" :title="t('actions.delete')" @click="confirmDelete(r)"><Trash2 class="h-4 w-4" /></button>
                    </div>
                </div>
            </div>
            <p v-if="!roles.length" class="text-sm text-slate-400">{{ t('roles.no_roles') }}</p>
        </div>

        <!-- Modal crear/editar -->
        <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="text-xl font-semibold">{{ editing ? t('roles.edit_role') : t('roles.new_role') }}</h2>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="open = false"><X class="h-5 w-5" /></button>
                </div>

                <form class="mt-5 space-y-5" @submit.prevent="submit">
                    <div>
                        <label :class="label">{{ t('roles.role_name') }} <span class="text-red-600">*</span></label>
                        <input v-model="form.name" :class="input" :placeholder="t('roles.name_placeholder')" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label :class="label">{{ t('roles.permissions') }}</label>
                        <div class="space-y-4">
                            <div v-for="g in catalog" :key="g.group">
                                <p class="mb-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ g.group }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="p in g.permissions" :key="p.name" type="button"
                                        class="rounded-lg border px-2.5 py-1 text-xs transition"
                                        :class="form.permissions.includes(p.name) ? 'border-brand bg-brand text-white' : 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300'"
                                        @click="togglePerm(p.name)"
                                    >{{ p.label }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="open = false">{{ t('actions.cancel') }}</button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"><Save class="h-4 w-4" /> {{ t('actions.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </ConfigLayout>
</template>
