<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ProfileModal from '@/Components/ProfileModal.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { useIdleTimeout } from '@/Composables/useIdleTimeout';
import { useCan } from '@/Composables/useCan';
import {
    LayoutDashboard, Crown, FolderKanban, Gauge, FileBarChart,
    Sparkles, BookText, MessagesSquare, Settings, LogOut, ChevronLeft, ChevronRight, ScrollText, Clock,
} from 'lucide-vue-next';

const { t } = useI18n({ useScope: 'global' });

// Cierre de sesión por inactividad (A.8.9): aviso con cuenta regresiva.
const { warning: idleWarning, secondsLeft: idleSeconds, stayConnected } = useIdleTimeout();

// Aviso de uso aceptable (A.5.10/A.5.34): bloquea hasta aceptar en el 1er acceso.
const needsAup = computed(() => Boolean((page.props.auth as any)?.needsAup));
const aupPoints = ['aup.point_1', 'aup.point_2', 'aup.point_3', 'aup.point_4'];
const aupForm = useForm({});
const aupChecked = ref(false);
function acceptAup() {
    if (!aupChecked.value) return;
    aupForm.post('/aviso-uso/aceptar', { preserveScroll: true, onSuccess: () => { aupChecked.value = false; } });
}

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

// Permisos para ocultar módulos que el usuario no puede ver (A.8.3).
const { can } = useCan();

// key: clave de traducción nav.<key>. route: ruta Ziggy. adminOnly: solo Super
// Admin/Administrador. perm: permiso para ver el módulo (null = abierto).
const nav = [
    { key: 'dashboard', icon: LayoutDashboard, route: 'dashboard', perm: null },
    { key: 'minister', icon: Crown, route: 'ministra.index', perm: 'ministra.ver' },
    { key: 'projects', icon: FolderKanban, route: 'proyectos.index', perm: 'proyectos.ver' },
    { key: 'kpis', icon: Gauge, route: 'kpis.index', perm: 'kpis.ver' },
    { key: 'reports', icon: FileBarChart, route: 'reportes.index', perm: 'reportes.ver' },
    { key: 'predictive', icon: Sparkles, route: 'ia-predictiva.index', perm: 'ia.ver' },
    { key: 'memoirs', icon: BookText, route: 'memorias.index', perm: 'memorias.generar' },
    { key: 'network', icon: MessagesSquare, route: 'red-gestores.index', perm: 'gestores.participar' },
    { key: 'logs', icon: ScrollText, route: 'logs.index', adminOnly: true, perm: null },
    { key: 'settings', icon: Settings, route: 'configuracion.edit', adminOnly: true, perm: null },
];

const visibleNav = computed(() =>
    nav.filter((item) => (!item.adminOnly || isAdmin.value) && (!item.perm || can(item.perm))),
);

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

        <!-- Aviso de cierre de sesión por inactividad (A.8.9) -->
        <Teleport to="body">
            <div v-if="idleWarning" class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-6">
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-slate-800">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <Clock class="h-6 w-6" />
                    </div>
                    <h2 class="mt-4 text-lg font-semibold">{{ t('session.idle_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">{{ t('session.idle_message', { seconds: idleSeconds }) }}</p>
                    <div class="mt-6 flex justify-center gap-2">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            {{ t('session.logout_now') }}
                        </Link>
                        <button
                            type="button"
                            class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white transition hover:opacity-90"
                            @click="stayConnected"
                        >
                            {{ t('session.stay_connected') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Aviso de uso aceptable y privacidad (A.5.10/A.5.34) — bloqueante -->
        <Teleport to="body">
            <div v-if="needsAup" class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/60 p-6">
                <div class="mt-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <ScrollText class="h-5 w-5 text-brand" /> {{ t('aup.title') }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ t('aup.intro') }}</p>

                    <ul class="mt-4 max-h-64 space-y-2 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <li v-for="p in aupPoints" :key="p" class="flex gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
                            <span>{{ t(p) }}</span>
                        </li>
                    </ul>

                    <label class="mt-4 flex items-start gap-2 text-sm">
                        <input v-model="aupChecked" type="checkbox" class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand" />
                        <span>{{ t('aup.checkbox') }}</span>
                    </label>

                    <div class="mt-6 flex justify-between gap-2">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            {{ t('aup.decline') }}
                        </Link>
                        <button
                            type="button"
                            :disabled="!aupChecked || aupForm.processing"
                            class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="acceptAup"
                        >
                            {{ t('aup.accept') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <ConfirmDialog />
    </div>
</template>
