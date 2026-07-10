<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import SecurityTabs from '@/Components/SecurityTabs.vue';
import { Users, FileSpreadsheet, ClipboardCheck } from 'lucide-vue-next';

interface Row {
    id: number; name: string; email: string; institution: string | null;
    roles: string[]; blocked: boolean; privileged: boolean;
    last_login: string | null; never: boolean; dormant: boolean;
}

defineProps<{
    users: Row[];
    lastReview: { at: string | null; by: string } | null;
}>();

const { t } = useI18n({ useScope: 'global' });

const reviewForm = useForm({});
function recordReview() {
    reviewForm.post(route('configuracion.seguridad.accesos.registrar'), { preserveScroll: true });
}
</script>

<template>
    <ConfigLayout section="seguridad">
        <SecurityTabs />

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <Users class="h-5 w-5 text-brand" /> {{ t('security.access.title') }}
                </h2>
                <p class="text-sm text-slate-500">{{ t('security.access.subtitle') }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    <template v-if="lastReview">{{ t('security.access.last_review', { when: lastReview.at, who: lastReview.by }) }}</template>
                    <template v-else>{{ t('security.access.no_review') }}</template>
                </p>
            </div>
            <div class="flex shrink-0 gap-2">
                <a
                    :href="route('configuracion.seguridad.accesos.export')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                >
                    <FileSpreadsheet class="h-4 w-4" /> {{ t('security.access.export') }}
                </a>
                <button
                    type="button"
                    :disabled="reviewForm.processing"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                    @click="recordReview"
                >
                    <ClipboardCheck class="h-4 w-4" /> {{ t('security.access.record_review') }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('security.access.col_user') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('security.access.col_roles') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('security.access.col_institution') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('security.access.col_last_login') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('security.access.col_flags') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ u.name }}</p>
                            <p class="text-xs text-slate-400">{{ u.email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span v-for="r in u.roles" :key="r" class="mr-1 inline-block rounded-full bg-brand/10 px-2 py-0.5 text-xs text-brand">{{ r }}</span>
                            <span v-if="!u.roles.length" class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3">{{ u.institution ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            <span v-if="u.last_login">{{ u.last_login }}</span>
                            <span v-else class="text-amber-600">{{ t('security.access.never') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span v-if="u.privileged" class="rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ t('security.access.privileged') }}</span>
                                <span v-if="u.blocked" class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ t('security.access.blocked') }}</span>
                                <span v-else-if="u.dormant" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ t('security.access.dormant') }}</span>
                                <span v-else class="rounded-full bg-teal-100 px-2 py-0.5 text-xs text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">{{ t('security.access.active') }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </ConfigLayout>
</template>
