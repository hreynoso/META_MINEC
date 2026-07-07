<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { FileText, Download, Eye } from 'lucide-vue-next';
import { currency, number } from '@/Composables/useProjectFormat';

interface InstitutionRow {
    code: string; name: string; short_name: string;
    projects_count: number; budget: number; executed: number; pct: number;
}

defineProps<{
    summary: { budget: number; executed: number; executed_pct: number; institutions: number; beneficiaries: number };
    byInstitution: InstitutionRow[];
    reports: { slug: string; title: string; description: string; format: string }[];
}>();
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">Reportes institucionales</h1>
            <p class="text-sm text-slate-500">Generación y exportación de reportes ejecutivos</p>
        </header>

        <!-- Tarjetas de resumen agregado -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs text-slate-500">Presupuesto agregado</p>
                <p class="mt-1 text-2xl font-bold">{{ currency(summary.budget) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ currency(summary.executed) }} ejecutado ({{ summary.executed_pct }}%)</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs text-slate-500">Instituciones adscritas</p>
                <p class="mt-1 text-2xl font-bold">{{ summary.institutions }}</p>
                <p class="mt-1 text-xs text-slate-400">Reportando proyectos activos</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs text-slate-500">Beneficiarios reportados</p>
                <p class="mt-1 text-2xl font-bold">{{ number(summary.beneficiaries) }}</p>
                <p class="mt-1 text-xs text-slate-400">Consolidado nacional</p>
            </div>
        </div>

        <!-- Catálogo de reportes -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="r in reports" :key="r.slug"
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-start justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <FileText class="h-5 w-5" />
                    </div>
                    <span
                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                        :class="r.format === 'pdf'
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                            : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'"
                    >{{ r.format }}</span>
                </div>
                <h3 class="mt-3 font-semibold leading-snug">{{ r.title }}</h3>
                <p class="mt-1 flex-1 text-sm text-slate-500">{{ r.description }}</p>
                <div class="mt-4 flex gap-2">
                    <a
                        :href="route('reportes.download', r.slug)"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:opacity-90"
                    >
                        <Download class="h-4 w-4" /> Descargar
                    </a>
                    <a
                        :href="route('reportes.preview', r.slug)" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                    >
                        <Eye class="h-4 w-4" /> Vista previa
                    </a>
                </div>
            </div>
        </div>

        <!-- Ejecución por institución -->
        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
                Ejecución por institución
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Institución</th>
                            <th class="px-5 py-3 font-medium">Proyectos</th>
                            <th class="px-5 py-3 font-medium">Presupuesto</th>
                            <th class="px-5 py-3 font-medium">Ejecutado</th>
                            <th class="px-5 py-3 font-medium">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="i in byInstitution" :key="i.code" class="border-t border-slate-100 dark:border-slate-700/60">
                            <td class="px-5 py-3">{{ i.short_name }} — {{ i.name }}</td>
                            <td class="px-5 py-3">{{ i.projects_count }}</td>
                            <td class="px-5 py-3">{{ currency(i.budget) }}</td>
                            <td class="px-5 py-3">{{ currency(i.executed) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-24 rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div class="h-1.5 rounded-full bg-brand" :style="{ width: Math.min(i.pct, 100) + '%' }" />
                                    </div>
                                    <span class="text-xs text-slate-500">{{ i.pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
