<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import ConfigLayout from '@/Components/ConfigLayout.vue';
import SecurityTabs from '@/Components/SecurityTabs.vue';
import { Lock, ShieldCheck, AlertTriangle, XCircle, Info, RefreshCw } from 'lucide-vue-next';

interface Check { key: string; status: 'ok' | 'warn' | 'fail' | 'info'; params: Record<string, unknown> }

const props = defineProps<{
    checks: Check[];
    summary: { ok: number; warn: number; fail: number; info: number };
}>();

const { t } = useI18n({ useScope: 'global' });

const STATUS_META: Record<string, { icon: any; dot: string; badge: string }> = {
    ok: { icon: ShieldCheck, dot: 'bg-teal-500', badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' },
    warn: { icon: AlertTriangle, dot: 'bg-amber-500', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
    fail: { icon: XCircle, dot: 'bg-red-500', badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
    info: { icon: Info, dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' },
};

const reloading = ref(false);
function rerun() {
    reloading.value = true;
    router.reload({ onFinish: () => { reloading.value = false; } });
}
</script>

<template>
    <ConfigLayout section="seguridad">
        <SecurityTabs />
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <Lock class="h-5 w-5 text-brand" /> {{ t('security.title') }}
                </h2>
                <p class="text-sm text-slate-500">{{ t('security.subtitle') }}</p>
            </div>
            <button
                type="button"
                :disabled="reloading"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-700"
                @click="rerun"
            >
                <RefreshCw class="h-4 w-4" :class="reloading ? 'animate-spin' : ''" /> {{ t('security.rerun') }}
            </button>
        </div>

        <!-- Resumen -->
        <div class="mb-5 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1.5 text-sm font-medium text-teal-700 dark:bg-teal-900/20 dark:text-teal-300">
                <ShieldCheck class="h-4 w-4" /> {{ summary.ok }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                <AlertTriangle class="h-4 w-4" /> {{ summary.warn }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 dark:bg-red-900/20 dark:text-red-300">
                <XCircle class="h-4 w-4" /> {{ summary.fail }}
            </span>
        </div>

        <!-- Lista de comprobaciones -->
        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white dark:divide-slate-700/60 dark:border-slate-700 dark:bg-slate-800">
            <div v-for="c in checks" :key="c.key" class="flex items-start gap-3 p-4">
                <component :is="STATUS_META[c.status].icon" class="mt-0.5 h-5 w-5 shrink-0" :class="{
                    'text-teal-500': c.status === 'ok',
                    'text-amber-500': c.status === 'warn',
                    'text-red-500': c.status === 'fail',
                    'text-slate-400': c.status === 'info',
                }" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">{{ t(`security.checks.${c.key}.label`) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ t(`security.checks.${c.key}.detail`, c.params) }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="STATUS_META[c.status].badge">
                    {{ t(`security.status.${c.status}`) }}
                </span>
            </div>
        </div>
    </ConfigLayout>
</template>
