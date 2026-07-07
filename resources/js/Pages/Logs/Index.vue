<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Search, ScrollText } from 'lucide-vue-next';
import { matchesAllTokens } from '@/Composables/useTokenSearch';

interface LogEntry { id: number; datetime: string | null; user: string; action: string; section: string; detail: string }

const props = defineProps<{ logs: LogEntry[] }>();

const q = ref('');
const filtered = computed(() =>
    props.logs.filter((l) => {
        if (!q.value.trim()) return true;
        return matchesAllTokens(`${l.user} ${l.action} ${l.section} ${l.detail}`, q.value);
    }),
);

const actionClass = (a: string) => ({
    'Creación': 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
    'Actualización': 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    'Eliminación': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
}[a] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300');
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-semibold"><ScrollText class="h-6 w-6 text-brand" /> Logs del Sistema</h1>
                <p class="text-sm text-slate-500">Registros y actualizaciones más recientes en el sistema.</p>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-slate-300 px-2 dark:border-slate-600">
                <Search class="h-4 w-4 opacity-50" />
                <input v-model="q" type="text" placeholder="Buscar en los logs…" class="w-64 bg-transparent py-2 text-sm outline-none" />
            </div>
        </header>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha y hora</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Acción</th>
                        <th class="px-4 py-3 font-medium">Sección</th>
                        <th class="px-4 py-3 font-medium">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in filtered" :key="l.id" class="border-b border-slate-100 last:border-0 dark:border-slate-700/60">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ l.datetime }}</td>
                        <td class="px-4 py-3 font-medium">{{ l.user }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs" :class="actionClass(l.action)">{{ l.action }}</span></td>
                        <td class="px-4 py-3">{{ l.section }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ l.detail }}</td>
                    </tr>
                    <tr v-if="!filtered.length"><td colspan="5" class="px-4 py-10 text-center text-slate-400">No hay registros de actividad.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
