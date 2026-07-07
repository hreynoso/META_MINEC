<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import {
    LayoutDashboard, Crown, FolderKanban, Gauge, FileBarChart,
    Sparkles, BookText, MessagesSquare, Settings, LogOut, Menu, ScrollText,
} from 'lucide-vue-next';

const page = usePage();
const sidebarOpen = ref(true);

// Colores administrables (Configuración → Branding) aplicados como variables CSS.
const colors = computed(() => (page.props.branding as any)?.colors ?? {});
const themeVars = computed(() => ({
    '--color-shell': colors.value.sidebar,
    '--color-shell-hover': colors.value.sidebar_hover,
    '--color-brand': colors.value.brand,
}));

// Toast global de éxito: escucha el flash compartido por el backend. Se observa
// la referencia del objeto flash (Inertia crea uno nuevo por visita) para que
// dos acciones con el MISMO mensaje disparen el toast ambas veces.
const toast = useToast();
watch(
    () => page.props.flash,
    (flash) => {
        const ok = (flash as any)?.success as string | undefined;
        const err = (flash as any)?.error as string | undefined;
        if (ok) toast.add({ severity: 'success', summary: 'Listo', detail: ok, life: 3500 });
        if (err) toast.add({ severity: 'error', summary: 'Error', detail: err, life: 6000 });
    },
);

// Logo del sidebar administrable desde Configuración (null = usa el ícono).
const logoSidebar = computed(() => (page.props.branding as any)?.assets?.logo_sidebar as string | undefined);

// route: nombre de ruta Ziggy si ya existe; null = módulo de fase posterior.
const nav = [
    { label: 'Dashboard', icon: LayoutDashboard, route: 'dashboard' },
    { label: 'Ministra', icon: Crown, route: 'ministra.index' },
    { label: 'Proyectos', icon: FolderKanban, route: 'proyectos.index' },
    { label: 'KPIs', icon: Gauge, route: 'kpis.index' },
    { label: 'Reportes', icon: FileBarChart, route: 'reportes.index' },
    { label: 'IA Predictiva', icon: Sparkles, route: 'ia-predictiva.index' },
    { label: 'Memorias', icon: BookText, route: 'memorias.index' },
    { label: 'Red de Gestores', icon: MessagesSquare, route: 'red-gestores.index' },
    { label: 'Logs del Sistema', icon: ScrollText, route: 'logs.index' },
    { label: 'Configuración', icon: Settings, route: 'configuracion.edit' },
];

function isActive(name: string | null): boolean {
    if (!name) return false;
    try { return route().current(name); } catch { return false; }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100" :style="themeVars">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside
                v-if="sidebarOpen"
                class="flex w-64 shrink-0 flex-col bg-shell text-slate-300"
            >
                <!-- Logo institucional a todo el ancho del sidebar -->
                <div v-if="logoSidebar" class="px-3 py-4">
                    <img
                        :src="logoSidebar"
                        alt="Logo institucional"
                        class="h-auto w-full object-contain"
                    />
                </div>
                <div v-else class="flex items-center gap-3 px-5 py-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand/20 text-brand">
                        <LayoutDashboard class="h-5 w-5" />
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-white">Sistema META</p>
                        <p class="text-[11px] text-slate-400">MINEC · El Salvador</p>
                    </div>
                </div>

                <nav class="mt-2 flex-1 space-y-1 px-3">
                    <template v-for="item in nav" :key="item.label">
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-[15px] transition"
                            :class="isActive(item.route)
                                ? 'bg-brand text-white'
                                : 'hover:bg-shell-hover hover:text-white'"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.label }}
                        </Link>
                        <span
                            v-else
                            class="flex cursor-default items-center gap-3 rounded-lg px-3 py-2 text-[15px] text-slate-500"
                            title="Disponible en próxima fase"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.label }}
                        </span>
                    </template>
                </nav>

                <div class="border-t border-slate-700/60 p-3">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-shell-hover hover:text-white"
                    >
                        <LogOut class="h-4 w-4" /> Salir
                    </Link>
                </div>
            </aside>

            <!-- Main -->
            <div class="flex flex-1 flex-col">
                <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 dark:border-slate-700 dark:bg-slate-800">
                    <button class="rounded p-1.5 hover:bg-slate-100 dark:hover:bg-slate-700" @click="sidebarOpen = !sidebarOpen">
                        <Menu class="h-5 w-5" />
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="text-right leading-tight">
                            <p class="text-sm font-medium">{{ (page.props.auth as any)?.user?.name ?? 'Usuario' }}</p>
                            <p class="text-xs text-slate-500">Administrador · MINEC</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand font-semibold text-white">
                            {{ ((page.props.auth as any)?.user?.name ?? 'U').slice(0, 1) }}
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-6">
                    <slot />
                </main>

                <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400 dark:border-slate-700">
                    Desarrollado por José David Montilla. Todos los derechos reservados 2026.
                </footer>
            </div>
        </div>

        <!-- Servicios globales de UI: confirmaciones y toasts -->
        <ConfirmDialog />
        <Toast position="top-right" />
    </div>
</template>
