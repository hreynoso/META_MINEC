<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';

defineProps<{
    stats: { label: string; value: number | string }[];
}>();
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold">Inicio</h1>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                v-for="s in stats"
                :key="s.label"
                :label="s.label"
                :value="s.value"
            />
        </div>
    </AppLayout>
</template>
