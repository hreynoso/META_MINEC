<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Palette, Mail, Users, ShieldCheck, Bell, Sparkles, ChevronRight, KeyRound, X, RefreshCw, Languages, Lock, DatabaseBackup } from 'lucide-vue-next';

interface ConnectedUser { id: number; name: string; email: string; avatar: string | null; initials: string; lastActive: string | null }

const props = defineProps<{ section: string }>();

const { t, locale } = useI18n({ useScope: 'global' });

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user ?? {});
const online = computed(() => (page.props.auth as any)?.online ?? 1);
const roles = computed<string[]>(() => (page.props.auth as any)?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('Super Admin'));

// superOnly: secciones de máximo privilegio (solo Super Admin). Las etiquetas se
// traducen con las claves config.sections.<key> y <key>_sub.
const sections = [
    { key: 'branding', icon: Palette, route: 'configuracion.edit' },
    { key: 'correo', icon: Mail, route: 'configuracion.correo.edit' },
    { key: 'usuarios', icon: Users, route: 'configuracion.usuarios.index', superOnly: true },
    { key: 'roles', icon: ShieldCheck, route: 'configuracion.roles.index', superOnly: true },
    { key: 'sso', icon: KeyRound, route: 'configuracion.sso.edit', superOnly: true },
    { key: 'notificaciones', icon: Bell, route: 'configuracion.notificaciones.edit' },
    { key: 'ia', icon: Sparkles, route: 'configuracion.ia.edit', superOnly: true },
    { key: 'seguridad', icon: Lock, route: 'configuracion.seguridad' },
    { key: 'respaldos', icon: DatabaseBackup, route: 'configuracion.respaldos.edit', superOnly: true },
    { key: 'idioma', icon: Languages, route: 'configuracion.idioma.edit' },
];

const visibleSections = computed(() => sections.filter((s) => !s.superOnly || isSuperAdmin.value));

const activeLabel = computed(() =>
    sections.find((s) => s.key === props.section)
        ? t(`config.sections.${props.section}`)
        : t('config.default_section'));

// Reloj en vivo para la tarjeta de fecha y hora. La fecha muestra el día y el
// nombre del mes (p. ej. "9 de julio de 2026"). Sigue el idioma del sistema.
function formatNow(): string {
    return new Date().toLocaleString(locale.value === 'en' ? 'en-US' : 'es-DO', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    });
}
const now = ref(formatNow());
let timer: number | undefined;
onMounted(() => { timer = window.setInterval(() => { now.value = formatNow(); }, 1000); });
onBeforeUnmount(() => { if (timer) window.clearInterval(timer); });

// Modal de usuarios conectados (se abre al pulsar el contador "Conectados").
const currentEmail = computed(() => (user.value?.email as string | undefined) ?? '');
const showConnected = ref(false);
const loadingConnected = ref(false);
const errorConnected = ref(false);
const connectedUsers = ref<ConnectedUser[]>([]);

async function loadConnected() {
    loadingConnected.value = true;
    errorConnected.value = false;
    try {
        // URL directa (sin depender de Ziggy) + credenciales para la sesión.
        const res = await fetch('/usuarios-conectados', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        connectedUsers.value = Array.isArray(data.users) ? data.users : [];
    } catch (e) {
        console.error('No se pudo cargar usuarios conectados:', e);
        errorConnected.value = true;
        connectedUsers.value = [];
    } finally {
        loadingConnected.value = false;
    }
}

function openConnected() {
    showConnected.value = true;
    loadConnected();
}
</script>

<template>
    <AppLayout>
        <!-- Breadcrumb -->
        <nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-400">
            <Link :href="route('dashboard')" class="hover:text-slate-600 dark:hover:text-slate-300">{{ t('config.breadcrumb_home') }}</Link>
            <ChevronRight class="h-3.5 w-3.5" />
            <span class="text-slate-600 dark:text-slate-300">{{ t('config.breadcrumb') }}</span>
        </nav>

        <!-- Cabecera -->
        <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">{{ t('config.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ t('config.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('config.card_datetime') }}</p>
                    <p class="text-sm font-medium tabular-nums">{{ now }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('config.card_user') }}</p>
                    <p class="text-sm font-medium">{{ user.name ?? '—' }}</p>
                    <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('config.card_section') }}</p>
                    <p class="text-sm font-medium">{{ activeLabel }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-left transition hover:border-teal-300 hover:bg-teal-100 dark:border-teal-900/50 dark:bg-teal-900/20 dark:hover:bg-teal-900/40"
                    :title="t('config.connected.title')"
                    @click="openConnected"
                >
                    <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-teal-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500" /> {{ t('config.card_connected') }}
                    </p>
                    <p class="text-sm font-medium text-teal-700 dark:text-teal-300">{{ t('config.users_count', { count: online }, online) }}</p>
                </button>
            </div>
        </div>

        <!-- Dos paneles: menú vertical + contenido -->
        <div class="flex flex-col gap-6 lg:flex-row">
            <aside class="w-full shrink-0 lg:sticky lg:top-0 lg:w-72 lg:self-start">
                <nav class="space-y-1 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                    <Link
                        v-for="s in visibleSections" :key="s.key"
                        :href="route(s.route)"
                        class="flex items-start gap-3 rounded-lg border-l-2 px-3 py-2.5 transition"
                        :class="s.key === section
                            ? 'border-brand bg-brand/5 text-brand'
                            : 'border-transparent text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50'"
                    >
                        <component :is="s.icon" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{ t(`config.sections.${s.key}`) }}</span>
                            <span class="block text-xs text-slate-400">{{ t(`config.sections.${s.key}_sub`) }}</span>
                        </span>
                    </Link>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <slot />
            </div>
        </div>

        <!-- Modal: usuarios conectados -->
        <Teleport to="body">
            <div
                v-if="showConnected"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-6"
                @click.self="showConnected = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 text-xl font-semibold">
                                <span class="h-2 w-2 rounded-full bg-teal-500" /> {{ t('config.connected.title') }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ t('config.connected.subtitle') }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                class="rounded p-1 text-slate-400 hover:bg-slate-100 disabled:opacity-50 dark:hover:bg-slate-700"
                                :title="t('actions.refresh')"
                                :disabled="loadingConnected"
                                @click="loadConnected"
                            >
                                <RefreshCw class="h-4 w-4" :class="loadingConnected ? 'animate-spin' : ''" />
                            </button>
                            <button class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" @click="showConnected = false">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Estado de carga -->
                    <div v-if="loadingConnected && !connectedUsers.length" class="mt-6 py-8 text-center text-sm text-slate-400">
                        {{ t('config.connected.loading') }}
                    </div>

                    <!-- Error -->
                    <div v-else-if="errorConnected" class="mt-6 py-8 text-center text-sm text-red-500">
                        {{ t('config.connected.error') }}
                    </div>

                    <!-- Lista de conectados -->
                    <ul v-else-if="connectedUsers.length" class="mt-5 max-h-96 space-y-1 overflow-y-auto">
                        <li
                            v-for="u in connectedUsers" :key="u.id"
                            class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                        >
                            <div class="relative shrink-0">
                                <img
                                    v-if="u.avatar" :src="u.avatar" :alt="u.name"
                                    class="h-10 w-10 rounded-full object-cover ring-2 ring-teal-500/30"
                                />
                                <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-brand text-sm font-semibold text-white ring-2 ring-teal-500/30">
                                    {{ u.initials }}
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-teal-500 dark:border-slate-800" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ u.name }}
                                    <span v-if="u.email === currentEmail" class="text-xs font-normal text-teal-600">{{ t('config.connected.you') }}</span>
                                </p>
                                <p class="truncate text-xs text-slate-400">{{ u.email }}</p>
                            </div>
                            <span v-if="u.lastActive" class="shrink-0 text-[11px] text-slate-400">{{ u.lastActive }}</span>
                        </li>
                    </ul>

                    <!-- Vacío -->
                    <div v-else class="mt-6 py-8 text-center text-sm text-slate-400">
                        {{ t('config.connected.empty') }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                            @click="showConnected = false"
                        >
                            {{ t('actions.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
