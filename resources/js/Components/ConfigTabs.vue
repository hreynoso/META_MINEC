<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ImageIcon, Sparkles } from 'lucide-vue-next';

const tabs = [
    { label: 'Identidad visual', icon: ImageIcon, route: 'configuracion.edit' },
    { label: 'Inteligencia Artificial', icon: Sparkles, route: 'configuracion.ia.edit' },
];

function isActive(name: string): boolean {
    try { return route().current(name); } catch { return false; }
}
</script>

<template>
    <nav class="mb-6 flex gap-1 border-b border-slate-200 dark:border-slate-700">
        <Link
            v-for="t in tabs" :key="t.route"
            :href="route(t.route)"
            class="inline-flex items-center gap-1.5 border-b-2 px-4 py-2 text-sm transition"
            :class="isActive(t.route)
                ? 'border-brand font-medium text-brand'
                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
        >
            <component :is="t.icon" class="h-4 w-4" /> {{ t.label }}
        </Link>
    </nav>
</template>
