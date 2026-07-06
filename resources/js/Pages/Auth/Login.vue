<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ institution: string }>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-900 text-slate-100">
        <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-800 p-8 text-center shadow-xl">
            <h1 class="text-2xl font-bold text-brand">META MINEC</h1>
            <p class="mt-1 text-sm text-slate-400">{{ institution }}</p>

            <a
                :href="route('azure.redirect')"
                class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-medium text-white transition hover:opacity-90"
            >
                Iniciar sesión con Office 365
            </a>

            <p class="mt-6 text-xs text-slate-500">
                Cooperación técnica República Dominicana → El Salvador
            </p>
        </div>
    </div>
</template>
