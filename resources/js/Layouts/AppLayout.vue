<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Users, FileBarChart, Settings, LogOut, Menu } from 'lucide-vue-next';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import Toast from 'primevue/toast';

const page = usePage();
const sidebarOpen = ref(true);

const nav = [
    { label: 'Inicio', icon: LayoutDashboard, route: 'dashboard' },
    { label: 'Usuarios', icon: Users, route: 'dashboard' },
    { label: 'Reportes', icon: FileBarChart, route: 'dashboard' },
    { label: 'Configuración', icon: Settings, route: 'dashboard' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100">
        <Toast />
        <ConfirmDialog />

        <!-- Topbar -->
        <header class="flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-3">
                <button class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700" @click="sidebarOpen = !sidebarOpen">
                    <Menu class="h-5 w-5" />
                </button>
                <span class="font-semibold text-brand">META MINEC</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span>{{ (page.props.auth as any)?.user?.name }}</span>
                <Link href="/logout" method="post" as="button" class="inline-flex items-center gap-1 text-slate-500 hover:text-brand">
                    <LogOut class="h-4 w-4" /> Salir
                </Link>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <aside v-if="sidebarOpen" class="w-56 shrink-0 border-r border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                <nav class="space-y-1">
                    <Link
                        v-for="item in nav"
                        :key="item.label"
                        :href="route(item.route)"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-teal-50 hover:text-brand dark:hover:bg-slate-700"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <!-- Content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
