<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, router } from '@inertiajs/vue3';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { matchesAllTokens } from '@/Composables/useTokenSearch';
import GridToolbar, { type GridColumn } from '@/Components/GridToolbar.vue';
import { Plus, Pencil, Trash2, X, Save, Building2, MapPin, Landmark, Phone, ClipboardList, ImageUp } from 'lucide-vue-next';

interface Institution {
    id: number; code: string; name: string; short_name: string; type: string | null; sector: string | null; rnc: string | null;
    status: string; logo_url: string | null; parent_id: number | null; parent: string | null; admin_dependency: string | null;
    phone_main: string | null; phone_alt: string | null; email: string | null; website: string | null;
    province: string | null; addr_sector: string | null; addr_street: string | null; addr_number: string | null; addr_reference: string | null; postal_code: string | null;
    authority_name: string | null; authority_position: string | null; authority_email: string | null; authority_phone: string | null;
    projects_count: number; created_by: string | null; updated_by: string | null; created_at: string | null; updated_at: string | null;
}

const props = defineProps<{
    institutions: Institution[];
    catalogs: { types: string[]; sectors: string[]; dependencies: string[]; provinces: string[]; statuses: string[] };
    parents: { id: number; short_name: string; name: string }[];
}>();

const { t } = useI18n({ useScope: 'global' });

const search = ref('');
const pageSize = ref(25);
const fType = ref('');
const fStatus = ref('');
const columns = ref<GridColumn[]>([
    { key: 'code', label: t('institutions.col_code'), visible: true },
    { key: 'name', label: t('institutions.col_name'), visible: true },
    { key: 'short_name', label: t('institutions.col_short'), visible: true },
    { key: 'type', label: t('institutions.col_type'), visible: true },
    { key: 'sector', label: t('institutions.col_sector'), visible: true },
    { key: 'parent', label: t('institutions.col_parent'), visible: false },
    { key: 'status', label: t('institutions.col_status'), visible: true },
]);
const vis = (k: string) => columns.value.find((c) => c.key === k)?.visible ?? true;

const filtered = computed(() =>
    props.institutions.filter((i) => {
        if (fType.value && i.type !== fType.value) return false;
        if (fStatus.value && i.status !== fStatus.value) return false;
        if (search.value.trim() && !matchesAllTokens(`${i.code} ${i.name} ${i.short_name} ${i.type ?? ''} ${i.sector ?? ''}`, search.value)) return false;
        return true;
    }),
);
const visibleRows = computed(() => filtered.value.slice(0, pageSize.value));

const { ask } = useConfirm();
const open = ref(false);
const editing = ref<Institution | null>(null);
const tab = ref('general');

const tabs = [
    { key: 'general', label: t('institutions.tab_general'), icon: Building2 },
    { key: 'location', label: t('institutions.tab_location'), icon: MapPin },
    { key: 'institutional', label: t('institutions.tab_institutional'), icon: Landmark },
    { key: 'contacts', label: t('institutions.tab_contacts'), icon: Phone },
    { key: 'audit', label: t('institutions.tab_audit'), icon: ClipboardList },
];

type Form = {
    name: string; short_name: string; type: string; sector: string; status: string; rnc: string; logo: File | null;
    parent_id: number | null; admin_dependency: string;
    phone_main: string; phone_alt: string; email: string; website: string;
    province: string; addr_sector: string; addr_street: string; addr_number: string; addr_reference: string; postal_code: string;
    authority_name: string; authority_position: string; authority_email: string; authority_phone: string;
};

const blank: Form = {
    name: '', short_name: '', type: '', sector: '', status: 'activa', rnc: '', logo: null,
    parent_id: null, admin_dependency: '',
    phone_main: '', phone_alt: '', email: '', website: '',
    province: '', addr_sector: '', addr_street: '', addr_number: '', addr_reference: '', postal_code: '',
    authority_name: '', authority_position: '', authority_email: '', authority_phone: '',
};

const form = useForm<Form>({ ...blank });
// Al editar, se hace POST con _method=PUT (subida de archivos en PUT no funciona en PHP).
form.transform((d) => (editing.value ? { ...d, _method: 'PUT' } : d));

const logoPreview = ref<string | null>(null);

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';
const label = 'mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500';

function openCreate() {
    editing.value = null;
    tab.value = 'general';
    form.defaults({ ...blank });
    form.reset();
    form.clearErrors();
    logoPreview.value = null;
    open.value = true;
}

function openEdit(i: Institution) {
    editing.value = i;
    tab.value = 'general';
    form.clearErrors();
    form.name = i.name; form.short_name = i.short_name;
    form.type = i.type ?? ''; form.sector = i.sector ?? ''; form.status = i.status ?? 'activa';
    form.rnc = i.rnc ?? ''; form.logo = null;
    form.parent_id = i.parent_id; form.admin_dependency = i.admin_dependency ?? '';
    form.phone_main = i.phone_main ?? ''; form.phone_alt = i.phone_alt ?? '';
    form.email = i.email ?? ''; form.website = i.website ?? '';
    form.province = i.province ?? ''; form.addr_sector = i.addr_sector ?? ''; form.addr_street = i.addr_street ?? '';
    form.addr_number = i.addr_number ?? ''; form.addr_reference = i.addr_reference ?? ''; form.postal_code = i.postal_code ?? '';
    form.authority_name = i.authority_name ?? ''; form.authority_position = i.authority_position ?? '';
    form.authority_email = i.authority_email ?? ''; form.authority_phone = i.authority_phone ?? '';
    logoPreview.value = i.logo_url;
    open.value = true;
}

function onLogo(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : (editing.value?.logo_url ?? null);
}

// Los padres seleccionables excluyen la propia institución en edición.
const parentOptions = computed(() => props.parents.filter((p) => !editing.value || p.id !== editing.value.id));

// ¿Hay errores en una pestaña concreta? (para marcarla).
const fieldsByTab: Record<string, (keyof Form)[]> = {
    general: ['name', 'short_name', 'type', 'sector', 'status', 'logo'],
    location: ['province', 'addr_sector', 'addr_street', 'addr_number', 'addr_reference', 'postal_code'],
    institutional: ['rnc', 'parent_id', 'admin_dependency'],
    contacts: ['phone_main', 'phone_alt', 'email', 'website', 'authority_name', 'authority_position', 'authority_email', 'authority_phone'],
    audit: [],
};
function tabHasError(key: string): boolean {
    return fieldsByTab[key].some((f) => (form.errors as Record<string, string>)[f]);
}

function submit() {
    const opts = { preserveScroll: true, forceFormData: true, onSuccess: () => { open.value = false; } };
    if (editing.value) form.post(route('configuracion.instituciones.update', editing.value.id), opts);
    else form.post(route('configuracion.instituciones.store'), opts);
}

function confirmDelete(i: Institution) {
    ask({
        header: t('institutions.delete_header'),
        message: t('institutions.delete_message', { name: i.name }),
        acceptLabel: t('actions.delete'),
        danger: true,
        accept: () => router.delete(route('configuracion.instituciones.destroy', i.id), { preserveScroll: true }),
    });
}
</script>

<template>
    <ConfigLayout section="instituciones">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold"><Building2 class="h-5 w-5 text-brand" /> {{ t('institutions.page_title') }}</h2>
                <p class="text-sm text-slate-500">{{ t('institutions.page_subtitle') }}</p>
            </div>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90" @click="openCreate">
                <Plus class="h-4 w-4" /> {{ t('institutions.new') }}
            </button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <GridToolbar
                v-model:search="search"
                v-model:page-size="pageSize"
                v-model:columns="columns"
                :total="filtered.length"
                :search-placeholder="t('institutions.search_placeholder')"
                :export-url="route('configuracion.instituciones.export')"
            >
                <template #filters>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('institutions.col_type') }}</label>
                        <select v-model="fType" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('institutions.all') }}</option>
                            <option v-for="ty in catalogs.types" :key="ty" :value="ty">{{ ty }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('institutions.col_status') }}</label>
                        <select v-model="fStatus" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('institutions.all') }}</option>
                            <option value="activa">{{ t('institutions.status_active') }}</option>
                            <option value="inactiva">{{ t('institutions.status_inactive') }}</option>
                        </select>
                    </div>
                </template>
            </GridToolbar>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                        <tr>
                            <th v-if="vis('code')" class="px-4 py-3 font-medium">{{ t('institutions.col_code') }}</th>
                            <th v-if="vis('name')" class="px-4 py-3 font-medium">{{ t('institutions.col_name') }}</th>
                            <th v-if="vis('short_name')" class="px-4 py-3 font-medium">{{ t('institutions.col_short') }}</th>
                            <th v-if="vis('type')" class="px-4 py-3 font-medium">{{ t('institutions.col_type') }}</th>
                            <th v-if="vis('sector')" class="px-4 py-3 font-medium">{{ t('institutions.col_sector') }}</th>
                            <th v-if="vis('parent')" class="px-4 py-3 font-medium">{{ t('institutions.col_parent') }}</th>
                            <th v-if="vis('status')" class="px-4 py-3 font-medium">{{ t('institutions.col_status') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ t('institutions.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="i in visibleRows" :key="i.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                            <td v-if="vis('code')" class="px-4 py-3 font-mono text-xs">{{ i.code }}</td>
                            <td v-if="vis('name')" class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img v-if="i.logo_url" :src="i.logo_url" class="h-6 w-6 shrink-0 rounded object-contain" alt="" />
                                    <span class="font-medium">{{ i.name }}</span>
                                </div>
                            </td>
                            <td v-if="vis('short_name')" class="px-4 py-3">{{ i.short_name }}</td>
                            <td v-if="vis('type')" class="px-4 py-3">{{ i.type ?? '—' }}</td>
                            <td v-if="vis('sector')" class="px-4 py-3">{{ i.sector ?? '—' }}</td>
                            <td v-if="vis('parent')" class="px-4 py-3">{{ i.parent ?? '—' }}</td>
                            <td v-if="vis('status')" class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="i.status === 'activa' ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                                    {{ i.status === 'activa' ? t('institutions.status_active') : t('institutions.status_inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button class="rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('actions.edit')" @click="openEdit(i)"><Pencil class="h-4 w-4" /></button>
                                    <button class="rounded p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" :title="t('actions.delete')" @click="confirmDelete(i)"><Trash2 class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length"><td colspan="8" class="px-4 py-8 text-center text-slate-400">{{ t('institutions.empty') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal crear/editar (formulario por pestañas) -->
        <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <h2 class="text-xl font-semibold">{{ editing ? t('institutions.edit') : t('institutions.new') }}</h2>
                    <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="open = false"><X class="h-5 w-5" /></button>
                </div>

                <!-- Pestañas -->
                <nav class="mt-4 flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-700">
                    <button
                        v-for="tb in tabs" :key="tb.key" type="button"
                        class="-mb-px flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition"
                        :class="tab === tb.key ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        @click="tab = tb.key"
                    >
                        <component :is="tb.icon" class="h-4 w-4" /> {{ tb.label }}
                        <span v-if="tabHasError(tb.key)" class="h-1.5 w-1.5 rounded-full bg-red-500" />
                    </button>
                </nav>

                <form class="mt-5" @submit.prevent="submit">
                    <!-- 1. Información General -->
                    <div v-show="tab === 'general'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label :class="label">{{ t('institutions.f_code') }}</label>
                                <input :value="editing ? editing.code : t('institutions.code_auto')" disabled :class="[input, 'font-mono text-slate-400']" />
                                <p class="mt-1 text-xs text-slate-400">{{ t('institutions.code_hint') }}</p>
                            </div>
                            <div>
                                <label :class="label">{{ t('institutions.f_status') }} <span class="text-red-600">*</span></label>
                                <select v-model="form.status" :class="input">
                                    <option value="activa">{{ t('institutions.status_active') }}</option>
                                    <option value="inactiva">{{ t('institutions.status_inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label :class="label">{{ t('institutions.f_name') }} <span class="text-red-600">*</span></label>
                            <input v-model="form.name" :class="input" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label :class="label">{{ t('institutions.f_short') }} <span class="text-red-600">*</span></label>
                                <input v-model="form.short_name" :class="input" />
                                <p v-if="form.errors.short_name" class="mt-1 text-xs text-red-600">{{ form.errors.short_name }}</p>
                            </div>
                            <div>
                                <label :class="label">{{ t('institutions.f_type') }} <span class="text-red-600">*</span></label>
                                <select v-model="form.type" :class="input">
                                    <option value="" disabled>{{ t('institutions.select') }}</option>
                                    <option v-for="ty in catalogs.types" :key="ty" :value="ty">{{ ty }}</option>
                                </select>
                                <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                            </div>
                            <div>
                                <label :class="label">{{ t('institutions.f_sector') }} <span class="text-red-600">*</span></label>
                                <select v-model="form.sector" :class="input">
                                    <option value="" disabled>{{ t('institutions.select') }}</option>
                                    <option v-for="s in catalogs.sectors" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <p v-if="form.errors.sector" class="mt-1 text-xs text-red-600">{{ form.errors.sector }}</p>
                            </div>
                        </div>
                        <div>
                            <label :class="label">{{ t('institutions.f_logo') }}</label>
                            <div class="flex items-center gap-3">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-900">
                                    <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-contain" alt="" />
                                    <Building2 v-else class="h-6 w-6 text-slate-300" />
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                                    <ImageUp class="h-4 w-4" /> {{ t('institutions.upload_logo') }}
                                    <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" @change="onLogo" />
                                </label>
                            </div>
                            <p v-if="form.errors.logo" class="mt-1 text-xs text-red-600">{{ form.errors.logo }}</p>
                        </div>
                    </div>

                    <!-- 2. Ubicación -->
                    <div v-show="tab === 'location'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label :class="label">{{ t('institutions.f_province') }}</label>
                                <input v-model="form.province" list="inst-provinces" :class="input" />
                                <datalist id="inst-provinces"><option v-for="p in catalogs.provinces" :key="p" :value="p" /></datalist>
                            </div>
                            <div><label :class="label">{{ t('institutions.f_addr_sector') }}</label><input v-model="form.addr_sector" :class="input" /></div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2"><label :class="label">{{ t('institutions.f_street') }}</label><input v-model="form.addr_street" :class="input" /></div>
                            <div><label :class="label">{{ t('institutions.f_number') }}</label><input v-model="form.addr_number" :class="input" /></div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2"><label :class="label">{{ t('institutions.f_reference') }}</label><input v-model="form.addr_reference" :class="input" /></div>
                            <div><label :class="label">{{ t('institutions.f_postal') }}</label><input v-model="form.postal_code" :class="input" /></div>
                        </div>
                    </div>

                    <!-- 3. Información Institucional -->
                    <div v-show="tab === 'institutional'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><label :class="label">{{ t('institutions.f_rnc') }}</label><input v-model="form.rnc" :class="input" /></div>
                            <div>
                                <label :class="label">{{ t('institutions.f_dependency') }}</label>
                                <select v-model="form.admin_dependency" :class="input">
                                    <option value="">{{ t('institutions.select') }}</option>
                                    <option v-for="d in catalogs.dependencies" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label :class="label">{{ t('institutions.f_parent') }}</label>
                            <select v-model="form.parent_id" :class="input">
                                <option :value="null">{{ t('institutions.none') }}</option>
                                <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.short_name }} — {{ p.name }}</option>
                            </select>
                            <p v-if="form.errors.parent_id" class="mt-1 text-xs text-red-600">{{ form.errors.parent_id }}</p>
                        </div>
                    </div>

                    <!-- 4. Contactos -->
                    <div v-show="tab === 'contacts'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><label :class="label">{{ t('institutions.f_phone_main') }}</label><input v-model="form.phone_main" :class="input" /></div>
                            <div><label :class="label">{{ t('institutions.f_phone_alt') }}</label><input v-model="form.phone_alt" :class="input" /></div>
                            <div>
                                <label :class="label">{{ t('institutions.f_email') }}</label>
                                <input v-model="form.email" type="email" :class="input" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label :class="label">{{ t('institutions.f_website') }}</label>
                                <input v-model="form.website" type="url" placeholder="https://" :class="input" />
                                <p v-if="form.errors.website" class="mt-1 text-xs text-red-600">{{ form.errors.website }}</p>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 pt-4 dark:border-slate-700/60">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('institutions.authority_section') }}</p>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div><label :class="label">{{ t('institutions.f_authority') }}</label><input v-model="form.authority_name" :class="input" /></div>
                                <div><label :class="label">{{ t('institutions.f_position') }}</label><input v-model="form.authority_position" :class="input" /></div>
                                <div>
                                    <label :class="label">{{ t('institutions.f_authority_email') }}</label>
                                    <input v-model="form.authority_email" type="email" :class="input" />
                                    <p v-if="form.errors.authority_email" class="mt-1 text-xs text-red-600">{{ form.errors.authority_email }}</p>
                                </div>
                                <div><label :class="label">{{ t('institutions.f_authority_phone') }}</label><input v-model="form.authority_phone" :class="input" /></div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Configuración y Auditoría -->
                    <div v-show="tab === 'audit'" class="space-y-3 text-sm">
                        <p class="text-xs text-slate-400">{{ t('institutions.audit_hint') }}</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('institutions.created_at') }}</p>
                                <p class="mt-0.5">{{ editing?.created_at ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ editing?.created_by ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('institutions.updated_at') }}</p>
                                <p class="mt-0.5">{{ editing?.updated_at ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ editing?.updated_by ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs uppercase tracking-wide text-slate-400">{{ t('institutions.linked_projects') }}</p>
                            <p class="mt-0.5">{{ editing?.projects_count ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-700/60">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700" @click="open = false">{{ t('actions.cancel') }}</button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"><Save class="h-4 w-4" /> {{ t('actions.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </ConfigLayout>
</template>
