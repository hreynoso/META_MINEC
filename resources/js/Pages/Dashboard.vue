<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { DollarSign, Clock, Users, TriangleAlert, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import { currency, number, STATUS_LABEL, statusClass, progressBarClass } from '@/Composables/useProjectFormat';

defineProps<{
    summary: {
        budget: number; executed: number; active: number; total: number;
        completed: number; beneficiaries: number; alert: number;
    };
    strategicKpis: { label: string; value: number; unit: string; target: number; achievement: number; trend: string }[];
    goals: { name: string; count: number }[];
    portfolio: { code: string; name: string; institution: string; status: string; progress: number }[];
}>();

const trendIcon = (t: string) => (t === 'up' ? TrendingUp : t === 'down' ? TrendingDown : Minus);
</script>

<template>
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold">Dashboard institucional</h1>
            <p class="text-sm text-slate-500">Vista consolidada del portafolio de inversión pública</p>
        </header>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-medium uppercase tracking-wide">Presupuesto total</span>
                    <DollarSign class="h-4 w-4" />
                </div>
                <p class="mt-2 text-2xl font-bold">{{ currency(summary.budget) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ currency(summary.executed) }} ejecutado</p>
            </div>
            <a
                :href="route('proyectos.index', { status: 'en_ejecucion' })"
                class="block rounded-xl border border-slate-200 bg-white p-5 transition hover:border-teal-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-teal-700"
            >
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-medium uppercase tracking-wide">Proyectos activos</span>
                    <Clock class="h-4 w-4" />
                </div>
                <p class="mt-2 text-2xl font-bold">{{ summary.active }} / {{ summary.total }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ summary.completed }} completados · ver activos →</p>
            </a>
            <a
                :href="route('proyectos.index', { beneficiarios: 1 })"
                class="block rounded-xl border border-slate-200 bg-white p-5 transition hover:border-teal-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-teal-700"
            >
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-medium uppercase tracking-wide">Beneficiarios</span>
                    <Users class="h-4 w-4" />
                </div>
                <p class="mt-2 text-2xl font-bold">{{ number(summary.beneficiaries) }}</p>
                <p class="mt-1 text-xs text-slate-500">Impacto directo · ver proyectos →</p>
            </a>
            <a
                :href="route('proyectos.index', { status: 'en_riesgo' })"
                class="block rounded-xl border border-amber-200 bg-amber-50/60 p-5 transition hover:border-amber-300 hover:shadow-md dark:border-amber-900/40 dark:bg-amber-900/10 dark:hover:border-amber-700"
            >
                <div class="flex items-center justify-between text-amber-500">
                    <span class="text-xs font-medium uppercase tracking-wide">Proyectos en alerta</span>
                    <TriangleAlert class="h-4 w-4" />
                </div>
                <p class="mt-2 text-2xl font-bold text-amber-600">{{ summary.alert }}</p>
                <p class="mt-1 text-xs text-slate-500">En riesgo · ver listado →</p>
            </a>
        </div>

        <!-- Indicadores estratégicos -->
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Indicadores estratégicos</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="k in strategicKpis" :key="k.label">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs">{{ k.label }}</span>
                        <component :is="trendIcon(k.trend)" class="h-4 w-4" />
                    </div>
                    <p class="mt-1 text-xl font-bold">
                        {{ number(k.value) }} <span class="text-sm font-normal text-slate-400">{{ k.unit }}</span>
                    </p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-1.5 rounded-full bg-brand" :style="{ width: Math.min(k.achievement, 100) + '%' }" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ k.achievement }}% de la meta ({{ number(k.target) }})</p>
                </div>
            </div>
        </section>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Metas presidenciales -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Metas presidenciales</h2>
                <ul class="space-y-2">
                    <li v-for="g in goals" :key="g.name" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-700/40">
                        <span>{{ g.name }}</span>
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand text-xs font-semibold text-white">{{ g.count }}</span>
                    </li>
                </ul>
            </section>

            <!-- Cartera de proyectos -->
            <section class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cartera de proyectos</h2>
                    <a :href="route('proyectos.index')" class="text-sm text-brand hover:underline">Ver todos →</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-slate-400 dark:border-slate-700">
                        <tr>
                            <th class="pb-2 font-medium">Código</th>
                            <th class="pb-2 font-medium">Proyecto</th>
                            <th class="pb-2 font-medium">Estado</th>
                            <th class="pb-2 font-medium">Avance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in portfolio" :key="p.code" class="border-b border-slate-100 dark:border-slate-700/50">
                            <td class="py-2 font-mono text-xs text-slate-500">{{ p.code }}</td>
                            <td class="py-2 pr-2">{{ p.name }} <span class="block text-xs text-slate-400">{{ p.institution }}</span></td>
                            <td class="py-2"><span class="rounded-full px-2 py-0.5 text-xs" :class="statusClass(p.status)">{{ STATUS_LABEL[p.status] }}</span></td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div class="h-1.5 rounded-full" :class="progressBarClass(p.progress)" :style="{ width: p.progress + '%' }" />
                                    </div>
                                    <span class="text-xs text-slate-500">{{ p.progress }}%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AppLayout>
</template>
