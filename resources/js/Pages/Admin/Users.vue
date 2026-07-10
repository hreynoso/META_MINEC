<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, router } from '@inertiajs/vue3';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { matchesAllTokens } from '@/Composables/useTokenSearch';
import GridToolbar, { type GridColumn } from '@/Components/GridToolbar.vue';
import { Plus, Pencil, Trash2, X, Save, ShieldCheck } from 'lucide-vue-next';

interface User {
    id: number; name: string; email: string; institution_id: number | null;
    institution: string | null; roles: string[]; blocked: boolean;
    last_login_at: string | null;
}

const props = defineProps<{
    users: User[];
    roles: string[];
    institutions: { id: number; short_name: string; name: string }[];
}>();

const { t } = useI18n({ useScope: 'global' });

// Toolbar uniforme (búsqueda, paginación, columnas, filtros, export).
const search = ref('');
const pageSize = ref(25);
const fRole = ref('');
const fStatus = ref('');
const columns = ref<GridColumn[]>([
    { key: 'name', label: t('users.col_name'), visible: true },
    { key: 'email', label: t('users.col_email'), visible: true },
    { key: 'institution', label: t('users.col_institution'), visible: true },
    { key: 'roles', label: t('users.col_roles'), visible: true },
    { key: 'blocked', label: t('users.col_status'), visible: true },
    { key: 'last_login', label: t('users.col_last_login'), visible: true },
]);
const vis = (k: string) => columns.value.find((c) => c.key === k)?.visible ?? true;

const filtered = computed(() =>
    props.users.filter((u) => {
        if (fRole.value && !u.roles.includes(fRole.value)) return false;
        if (fStatus.value === 'activo' && u.blocked) return false;
        if (fStatus.value === 'bloqueado' && !u.blocked) return false;
        if (search.value.trim() && !matchesAllTokens(`${u.name} ${u.email} ${u.institution ?? ''} ${u.roles.join(' ')}`, search.value)) return false;
        return true;
    }),
);
const visibleRows = computed(() => filtered.value.slice(0, pageSize.value));

const { ask } = useConfirm();
const open = ref(false);
const editing = ref<User | null>(null);

const form = useForm<{ name: string; email: string; password: string; institution_id: number | null; roles: string[]; blocked: boolean }>({
    name: '', email: '', password: '', institution_id: null, roles: [], blocked: false,
});

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(u: User) {
    editing.value = u;
    form.clearErrors();
    form.name = u.name;
    form.email = u.email;
    form.password = '';
    form.institution_id = u.institution_id;
    form.roles = [...u.roles];
    form.blocked = u.blocked;
    open.value = true;
}

function toggleRole(role: string) {
    const i = form.roles.indexOf(role);
    if (i === -1) form.roles.push(role);
    else form.roles.splice(i, 1);
}

function submit() {
    const opts = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    if (editing.value) form.put(route('configuracion.usuarios.update', editing.value.id), opts);
    else form.post(route('configuracion.usuarios.store'), opts);
}

function confirmDelete(u: User) {
    ask({
        header: t('users.delete_header'),
        message: t('users.delete_message', { name: u.name }),
        acceptLabel: t('actions.delete'),
        danger: true,
        accept: () => router.delete(route('configuracion.usuarios.destroy', u.id), { preserveScroll: true }),
    });
}
</script>

<template>
    <ConfigLayout section="usuarios">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ t('users.page_title') }}</h2>
                <p class="text-sm text-slate-500">{{ t('users.page_subtitle') }}</p>
            </div>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                <Plus class="h-4 w-4" /> {{ t('users.new_user') }}
            </button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <GridToolbar
                v-model:search="search"
                v-model:page-size="pageSize"
                v-model:columns="columns"
                :total="filtered.length"
                :search-placeholder="t('users.search_placeholder')"
                :export-url="route('configuracion.usuarios.export')"
            >
                <template #filters>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('users.filter_role') }}</label>
                        <select v-model="fRole" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('users.all') }}</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('users.col_status') }}</label>
                        <select v-model="fStatus" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('users.all') }}</option>
                            <option value="activo">{{ t('users.status_active') }}</option>
                            <option value="bloqueado">{{ t('users.status_blocked') }}</option>
                        </select>
                    </div>
                </template>
            </GridToolbar>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                        <tr>
                            <th v-if="vis('name')" class="px-4 py-3 font-medium">{{ t('users.col_name') }}</th>
                            <th v-if="vis('email')" class="px-4 py-3 font-medium">{{ t('users.col_email') }}</th>
                            <th v-if="vis('institution')" class="px-4 py-3 font-medium">{{ t('users.col_institution') }}</th>
                            <th v-if="vis('roles')" class="px-4 py-3 font-medium">{{ t('users.col_roles') }}</th>
                            <th v-if="vis('blocked')" class="px-4 py-3 font-medium">{{ t('users.col_status') }}</th>
                            <th v-if="vis('last_login')" class="px-4 py-3 font-medium">{{ t('users.col_last_login') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ t('users.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in visibleRows" :key="u.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                            <td v-if="vis('name')" class="px-4 py-3 font-medium">{{ u.name }}</td>
                            <td v-if="vis('email')" class="px-4 py-3 text-slate-500">{{ u.email }}</td>
                            <td v-if="vis('institution')" class="px-4 py-3">{{ u.institution ?? '—' }}</td>
                            <td v-if="vis('roles')" class="px-4 py-3">
                                <span v-for="r in u.roles" :key="r" class="mr-1 inline-block rounded-full bg-brand/10 px-2 py-0.5 text-xs text-brand">{{ r }}</span>
                                <span v-if="!u.roles.length" class="text-slate-400">—</span>
                            </td>
                            <td v-if="vis('blocked')" class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="u.blocked ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'">
                                    {{ u.blocked ? t('users.status_blocked') : t('users.status_active') }}
                                </span>
                            </td>
                            <td v-if="vis('last_login')" class="px-4 py-3 text-slate-500">{{ u.last_login_at ?? t('users.never') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button class="rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('users.edit')" @click="openEdit(u)"><Pencil class="h-4 w-4" /></button>
                                    <button class="rounded p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" :title="t('actions.delete')" @click="confirmDelete(u)"><Trash2 class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length"><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ t('users.empty') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal crear/editar -->
        <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-[573px] rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="text-xl font-semibold">{{ editing ? t('users.edit_user') : t('users.new_user') }}</h2>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="open = false"><X class="h-5 w-5" /></button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label :class="label">{{ t('users.col_name') }} <span class="text-red-600">*</span></label>
                            <input v-model="form.name" :class="input" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('users.col_email') }} <span class="text-red-600">*</span></label>
                            <input v-model="form.email" type="email" :class="input" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('users.password') }} <span v-if="!editing" class="text-red-600">*</span></label>
                            <input v-model="form.password" type="password" autocomplete="new-password" :class="input" :placeholder="editing ? t('users.password_placeholder') : ''" />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('users.col_institution') }}</label>
                            <select v-model="form.institution_id" :class="input">
                                <option :value="null">{{ t('users.select_placeholder') }}</option>
                                <option v-for="i in institutions" :key="i.id" :value="i.id">{{ i.short_name }} — {{ i.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label :class="label"><ShieldCheck class="mr-1 inline h-3.5 w-3.5" /> {{ t('users.col_roles') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="r in roles" :key="r" type="button"
                                class="rounded-full border px-3 py-1 text-sm transition"
                                :class="form.roles.includes(r) ? 'border-brand bg-brand text-white' : 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300'"
                                @click="toggleRole(r)"
                            >{{ r }}</button>
                            <span v-if="!roles.length" class="text-xs text-slate-400">{{ t('users.no_roles') }}</span>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.blocked" type="checkbox" class="rounded border-slate-300 text-brand focus:ring-brand" />
                        {{ t('users.account_blocked') }}
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="open = false">{{ t('actions.cancel') }}</button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"><Save class="h-4 w-4" /> {{ t('actions.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </ConfigLayout>
</template>
