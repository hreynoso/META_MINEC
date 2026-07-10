<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ProfileModal from '@/Components/ProfileModal.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import {
    LayoutDashboard, Crown, FolderKanban, Gauge, FileBarChart,
    Sparkles, BookText, MessagesSquare, Settings, LogOut, ChevronLeft, ChevronRight, ScrollText,
} from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

const page = usePage();
const sidebarOpen = ref(true);
const profileOpen = ref(false);

// Datos del usuario autenticado (compartidos por el backend).
const authUser = computed(() => (page.props.auth as any)?.user ?? {});
const authRoles = computed<string[]>(() => (page.props.auth as any)?.roles ?? []);
const primaryRole = computed(() => authRoles.value[0] ?? 'Usuario');

// Colores administrables (Configuración → Branding) aplicados como variables CSS.
const colors = computed(() => (page.props.branding as any)?.colors ?? {});
const themeVars = computed(() => ({
    '--color-shell': colors.value.sidebar,
    '--color-shell-hover': colors.value.sidebar_hover,
    '--color-brand': colors.value.brand,
}));

// Los mensajes flash del backend se muestran en la barra superior a todo lo
// ancho (FlashBanner), con duración de 7 s.

// Logo del sidebar administrable desde Configuración (null = usa el ícono).
const logoSidebar = computed(() => (page.props.branding as any)?.assets?.logo_sidebar as string | undefined);

// Acceso al área de administración (Configuración y Logs).
const isAdmin = computed(() => authRoles.value.includes('Super Admin') || authRoles.value.includes('Administrador'));

// key: clave de traducción nav.<key>. route: ruta Ziggy si existe; null = fase
// posterior. adminOnly: solo visible para Super Admin / Administrador.
const nav = [
    { key: 'dashboard', icon: LayoutDashboard, route: 'dashboard' },
    { key: 'minister', icon: Crown, route: 'ministra.index' },
    { key: 'projects', icon: FolderKanban, route: 'proyectos.index' },
    { key: 'kpis', icon: Gauge, route: 'kpis.index' },
    { key: 'reports', icon: FileBarChart, route: 'reportes.index' },
    { key: 'predictive', icon: Sparkles, route: 'ia-predictiva.index' },
    { key: 'memoirs', icon: BookText, route: 'memorias.index' },
    { key: 'network', icon: MessagesSquare, route: 'red-gestores.index' },
    { key: 'logs', icon: ScrollText, route: 'logs.index', adminOnly: true },
    { key: 'settings', icon: Settings, route: 'configuracion.edit', adminOnly: true },
];

const visibleNav = computed(() => nav.filter((item) => !item.adminOnly || isAdmin.value));

function isActive(name: string | null): boolean {
    if (!name) return false;
    try { return route().current(name); } catch { return false; }
}
</script>

<template>
    <div class="h-screen bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100" :style="themeVars">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar fijo: no se desplaza con el scroll del contenido -->
            <aside
                v-if="sidebarOpen"
                class="relative h-screen w-64 shrink-0 caret-transparent bg-shell text-slate-300"
            >
                <!-- Botón circular de colapso (estilo SED), sobre el borde derecho.
                     Va fuera del contenedor con scroll para que no lo recorte. -->
                <button
                    class="absolute -right-3 top-5 z-10 flex h-6 w-6 items-center justify-center rounded-full border border-white/20 bg-shell text-slate-200 shadow transition hover:text-white"
                    :title="t('nav.close_menu')"
                    @click="sidebarOpen = false"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>

                <!-- Contenido desplazable (sin scroll horizontal) -->
                <div class="flex h-full flex-col overflow-y-auto overflow-x-hidden">
                <!-- Logo institucional (enlaza al Dashboard) -->
                <Link v-if="logoSidebar" :href="route('dashboard')" class="block px-3 py-4" :title="t('header.app_title')">
                    <img
                        :src="logoSidebar"
                        alt="Logo institucional"
                        class="h-auto w-full object-contain"
                    />
                </Link>
                <Link v-else :href="route('dashboard')" class="flex items-center gap-3 px-5 py-4" :title="t('header.app_title')">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand/20 text-brand">
                        <LayoutDashboard class="h-5 w-5" />
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-white">{{ t('app.name') }}</p>
                        <p class="text-[11px] text-slate-400">{{ t('app.sidebar_subtitle') }}</p>
                    </div>
                </Link>

                <nav class="mt-2 flex-1 space-y-1 px-3">
                    <template v-for="item in visibleNav" :key="item.key">
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-[15px] transition"
                            :class="isActive(item.route)
                                ? 'bg-brand text-white'
                                : 'hover:bg-shell-hover hover:text-white'"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ t(`nav.${item.key}`) }}
                        </Link>
                        <span
                            v-else
                            class="flex cursor-default items-center gap-3 rounded-lg px-3 py-2 text-[15px] text-slate-500"
                            :title="t('nav.next_phase')"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ t(`nav.${item.key}`) }}
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
                        <LogOut class="h-4 w-4" /> {{ t('nav.logout') }}
                    </Link>
                </div>
                </div>
            </aside>

            <!-- Main -->
            <div class="relative flex flex-1 flex-col overflow-hidden">
                <header class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3">
                        <button
                            v-if="!sidebarOpen"
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:text-brand dark:border-slate-600 dark:bg-slate-800"
                            :title="t('nav.open_menu')"
                            @click="sidebarOpen = true"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                        <p class="text-lg font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ t('header.app_title') }}</p>
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-lg px-1.5 py-1 transition hover:bg-slate-100 dark:hover:bg-slate-700"
                        :title="t('header.view_profile')"
                        @click="profileOpen = true"
                    >
                        <div class="text-right leading-tight">
                            <p class="text-sm font-medium">{{ authUser.name ?? t('header.user') }}</p>
                            <p class="text-xs text-slate-500">{{ primaryRole }} · MINEC</p>
                        </div>
                        <img
                            v-if="authUser.avatar"
                            :src="authUser.avatar"
                            alt="Foto de perfil"
                            class="h-9 w-9 rounded-full object-cover"
                        />
                        <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-brand font-semibold text-white">
                            {{ (authUser.name ?? 'U').slice(0, 1) }}
                        </div>
                    </button>
                </header>

                <!-- Franja informativa: superpuesta, debajo del encabezado y sin
                     llegar al sidebar (queda dentro de esta columna de contenido). -->
                <FlashBanner />

                <main class="flex-1 overflow-y-auto p-6">
                    <slot />
                </main>

                <footer class="shrink-0 border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400 dark:border-slate-700">
                    {{ t('footer.credits') }}
                </footer>
            </div>
        </div>

        <!-- Perfil del usuario -->
        <ProfileModal v-if="profileOpen" @close="profileOpen = false" />

        <ConfirmDialog />
    </div>
</template>
