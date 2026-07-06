<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    hint?: string;
}>();
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold text-brand">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>
