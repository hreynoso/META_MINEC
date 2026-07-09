<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ScrollText } from 'lucide-vue-next';
import { matchesAllTokens } from '@/Composables/useTokenSearch';
import GridToolbar, { type GridColumn } from '@/Components/GridToolbar.vue';

const { t } = useI18n({ useScope: 'global' });

interface LogEntry { id: number; datetime: string | null; user: string; action: string; section: string; detail: string }

const props = defineProps<{ logs: LogEntry[] }>();

const search = ref('');
const pageSize = ref(50);
const fSection = ref('');
const fAction = ref('');

const columns = ref<GridColumn[]>([
    { key: 'datetime', label: t('logs.col_datetime'), visible: true },
    { key: 'user', label: t('logs.col_user'), visible: true },
    { key: 'action', label: t('logs.col_action'), visible: true },
    { key: 'section', label: t('logs.col_section'), visible: true },
    { key: 'detail', label: t('logs.col_detail'), visible: true },
]);
const vis = (k: string) => columns.value.find((c) => c.key === k)?.visible ?? true;

const sections = computed(() => [...new Set(props.logs.map((l) => l.section))].sort());
const actions = computed(() => [...new Set(props.logs.map((l) => l.action))].sort());

const filtered = computed(() =>
    props.logs.filter((l) => {
        if (fSection.value && l.section !== fSection.value) return false;
        if (fAction.value && l.action !== fAction.value) return false;
        if (search.value.trim() && !matchesAllTokens(`${l.user} ${l.action} ${l.section} ${l.detail}`, search.value)) return false;
        return true;
    }),
);
const visibleRows = computed(() => filtered.value.slice(0, pageSize.value));

const actionClass = (a: string) => ({
    'Creación': 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
    'Actualización': 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    'Eliminación': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
}[a] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300');
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="flex items-center gap-2 text-2xl font-semibold"><ScrollText class="h-6 w-6 text-brand" /> {{ t('logs.title') }}</h1>
            <p class="text-sm text-slate-500">{{ t('logs.subtitle') }}</p>
        </header>

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <GridToolbar
                v-model:search="search"
                v-model:page-size="pageSize"
                v-model:columns="columns"
                :total="filtered.length"
                :search-placeholder="t('logs.search_placeholder')"
                :export-url="route('logs.export')"
            >
                <template #filters>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('logs.col_section') }}</label>
                        <select v-model="fSection" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('logs.all') }}</option>
                            <option v-for="s in sections" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ t('logs.col_action') }}</label>
                        <select v-model="fAction" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ t('logs.all') }}</option>
                            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>
                </template>
            </GridToolbar>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                        <tr>
                            <th v-if="vis('datetime')" class="px-4 py-3 font-medium">{{ t('logs.col_datetime') }}</th>
                            <th v-if="vis('user')" class="px-4 py-3 font-medium">{{ t('logs.col_user') }}</th>
                            <th v-if="vis('action')" class="px-4 py-3 font-medium">{{ t('logs.col_action') }}</th>
                            <th v-if="vis('section')" class="px-4 py-3 font-medium">{{ t('logs.col_section') }}</th>
                            <th v-if="vis('detail')" class="px-4 py-3 font-medium">{{ t('logs.col_detail') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in visibleRows" :key="l.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                            <td v-if="vis('datetime')" class="whitespace-nowrap px-4 py-3 text-slate-500">{{ l.datetime }}</td>
                            <td v-if="vis('user')" class="px-4 py-3 font-medium">{{ l.user }}</td>
                            <td v-if="vis('action')" class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs" :class="actionClass(l.action)">{{ l.action }}</span></td>
                            <td v-if="vis('section')" class="px-4 py-3">{{ l.section }}</td>
                            <td v-if="vis('detail')" class="px-4 py-3 text-slate-500">{{ l.detail }}</td>
                        </tr>
                        <tr v-if="!filtered.length"><td colspan="5" class="px-4 py-10 text-center text-slate-400">{{ t('logs.empty') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
