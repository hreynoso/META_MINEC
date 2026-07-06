<script setup lang="ts">
import { computed } from 'vue';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-vue-next';
import type { SortDirection } from '@/Composables/useSortable';

const props = defineProps<{
    label: string;
    columnKey: string;
    activeKey: string | null;
    direction: SortDirection;
}>();

const emit = defineEmits<{ (e: 'sort', key: string): void }>();

const isActive = computed(() => props.activeKey === props.columnKey);
</script>

<template>
    <th
        class="cursor-pointer select-none px-3 py-2 text-left text-sm font-semibold"
        @click="emit('sort', columnKey)"
    >
        <span class="inline-flex items-center gap-1">
            {{ label }}
            <ChevronUp v-if="isActive && direction === 'asc'" class="h-4 w-4 text-brand" />
            <ChevronDown v-else-if="isActive && direction === 'desc'" class="h-4 w-4 text-brand" />
            <ChevronsUpDown v-else class="h-4 w-4 opacity-40" />
        </span>
    </th>
</template>
