<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { ListChecks, Plus, Check, X, Pencil, Trash2, Save, Hash } from 'lucide-vue-next';

interface Option { id: number; label: string; active: boolean; sort: number; in_use: number }

const props = defineProps<{ groups: Record<string, Option[]>; codeFormat: { pattern: string; seq_length: number } }>();

const { t } = useI18n({ useScope: 'global' });
const { ask } = useConfirm();

// Títulos por grupo; los grupos se descubren de lo que envía el backend, así al
// registrar un catálogo nuevo aparece aquí sin tocar esta vista (solo su título).
const titles: Record<string, string> = {
    institution_type: t('catalogs.g_type'),
    institution_sector: t('catalogs.g_sector'),
    institution_dependency: t('catalogs.g_dependency'),
    institution_province: t('catalogs.g_province'),
    kpi_unit: t('catalogs.g_unit'),
};
const meta = Object.keys(props.groups).map((g) => ({ group: g, title: titles[g] ?? g }));

const input = 'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900';

// Nomenclatura del código de institución.
const codePattern = ref(props.codeFormat.pattern);
const codeSeq = ref(props.codeFormat.seq_length);
const codeSaving = ref(false);
const codePreview = computed(() => {
    const year = new Date().getFullYear().toString();
    const seq = '1'.padStart(Math.max(1, Math.min(codeSeq.value || 4, 10)), '0');
    return (codePattern.value || '')
        .replace(/\{SIGLAS\}/g, 'MINEC')
        .replace(/\{(AÑO|ANIO|ANO|YEAR)\}/g, year)
        .replace(/\{(AÑO2|ANIO2)\}/g, year.slice(-2))
        .replace(/\{SEC\}/g, seq);
});
function saveCodeFormat() {
    codeSaving.value = true;
    router.post(route('configuracion.catalogos.nomenclatura'),
        { pattern: codePattern.value, seq_length: codeSeq.value },
        { preserveScroll: true, onFinish: () => { codeSaving.value = false; } },
    );
}

// Estado de "nueva opción" por grupo.
const newLabel = reactive<Record<string, string>>({});
// Fila en edición: id → borrador de etiqueta.
const editing = ref<number | null>(null);
const draft = ref('');

function addOption(group: string) {
    const label = (newLabel[group] ?? '').trim();
    if (!label) return;
    router.post(route('configuracion.catalogos.store'), { group, label }, {
        preserveScroll: true,
        onSuccess: () => { newLabel[group] = ''; },
    });
}

function startEdit(o: Option) {
    editing.value = o.id;
    draft.value = o.label;
}

function saveEdit(o: Option) {
    const label = draft.value.trim();
    if (!label) return;
    router.put(route('configuracion.catalogos.update', o.id), { label, active: o.active }, {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

function toggleActive(o: Option) {
    router.put(route('configuracion.catalogos.update', o.id), { label: o.label, active: !o.active }, { preserveScroll: true });
}

function remove(o: Option) {
    if (o.in_use > 0) return;
    ask({
        header: t('catalogs.delete_header'),
        message: t('catalogs.delete_message', { label: o.label }),
        acceptLabel: t('actions.delete'),
        danger: true,
        accept: () => router.delete(route('configuracion.catalogos.destroy', o.id), { preserveScroll: true }),
    });
}
</script>

<template>
    <ConfigLayout section="catalogos">
        <div class="mb-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold"><ListChecks class="h-5 w-5 text-brand" /> {{ t('catalogs.page_title') }}</h2>
            <p class="text-sm text-slate-500">{{ t('catalogs.page_subtitle') }}</p>
        </div>

        <!-- Nomenclatura del código de institución -->
        <section class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <Hash class="h-4 w-4 text-brand" /> {{ t('catalogs.code_title') }}
            </h3>
            <p class="mt-1 text-xs text-slate-500">{{ t('catalogs.code_subtitle') }}</p>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('catalogs.code_pattern') }}</label>
                    <input v-model="codePattern" :class="input" placeholder="{SIGLAS}-{AÑO}-{SEC}" />
                </div>
                <div class="w-32">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('catalogs.code_seq') }}</label>
                    <input v-model.number="codeSeq" type="number" min="1" max="10" :class="input" />
                </div>
                <button
                    type="button" :disabled="codeSaving"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    @click="saveCodeFormat"
                >
                    <Save class="h-4 w-4" /> {{ t('actions.save') }}
                </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span>{{ t('catalogs.code_preview') }}: <strong class="font-mono text-slate-700 dark:text-slate-200">{{ codePreview }}</strong></span>
                <span class="text-slate-400">{{ t('catalogs.code_tokens') }}</span>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <section v-for="m in meta" :key="m.group" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ m.title }}</h3>

                <ul class="space-y-1.5">
                    <li v-for="o in props.groups[m.group]" :key="o.id" class="flex items-center gap-2 rounded-lg border border-slate-100 px-2 py-1.5 dark:border-slate-700/60">
                        <template v-if="editing === o.id">
                            <input v-model="draft" :class="input" @keyup.enter="saveEdit(o)" />
                            <button class="rounded p-1.5 text-brand hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('actions.save')" @click="saveEdit(o)"><Save class="h-4 w-4" /></button>
                            <button class="rounded p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('actions.cancel')" @click="editing = null"><X class="h-4 w-4" /></button>
                        </template>
                        <template v-else>
                            <span class="min-w-0 flex-1 truncate text-sm" :class="o.active ? '' : 'text-slate-400 line-through'">{{ o.label }}</span>
                            <span v-if="o.in_use > 0" class="shrink-0 rounded-full bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-500 dark:bg-slate-700 dark:text-slate-300" :title="t('catalogs.in_use_hint')">{{ o.in_use }}</span>
                            <button
                                class="shrink-0 rounded p-1.5"
                                :class="o.active ? 'text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                :title="o.active ? t('catalogs.active') : t('catalogs.inactive')"
                                @click="toggleActive(o)"
                            ><Check class="h-4 w-4" /></button>
                            <button class="shrink-0 rounded p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" :title="t('actions.edit')" @click="startEdit(o)"><Pencil class="h-4 w-4" /></button>
                            <button
                                class="shrink-0 rounded p-1.5 disabled:opacity-30"
                                :class="o.in_use > 0 ? 'text-slate-300' : 'text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30'"
                                :disabled="o.in_use > 0"
                                :title="o.in_use > 0 ? t('catalogs.in_use_hint') : t('actions.delete')"
                                @click="remove(o)"
                            ><Trash2 class="h-4 w-4" /></button>
                        </template>
                    </li>
                    <li v-if="!props.groups[m.group] || !props.groups[m.group].length" class="py-2 text-center text-xs text-slate-400">{{ t('catalogs.empty') }}</li>
                </ul>

                <div class="mt-3 flex items-center gap-2">
                    <input v-model="newLabel[m.group]" :class="input" :placeholder="t('catalogs.new_placeholder')" @keyup.enter="addOption(m.group)" />
                    <button class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-brand px-2.5 py-1.5 text-sm font-medium text-white hover:opacity-90" @click="addOption(m.group)">
                        <Plus class="h-4 w-4" /> {{ t('catalogs.add') }}
                    </button>
                </div>
            </section>
        </div>
    </ConfigLayout>
</template>
