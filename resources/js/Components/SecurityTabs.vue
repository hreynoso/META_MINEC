<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({ useScope: 'global' });

const tabs = [
    { key: 'checks', route: 'configuracion.seguridad' },
    { key: 'deps', route: 'configuracion.seguridad.dependencias' },
    { key: 'alerts', route: 'configuracion.seguridad.alertas' },
];

function active(name: string): boolean {
    try {
        return route().current(name);
    } catch {
        return false;
    }
}
</script>

<template>
    <nav class="mb-5 flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-700">
        <Link
            v-for="tb in tabs" :key="tb.key"
            :href="route(tb.route)"
            class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition"
            :class="active(tb.route)
                ? 'border-brand text-brand'
                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
        >
            {{ t(`security.tabs.${tb.key}`) }}
        </Link>
    </nav>
</template>
