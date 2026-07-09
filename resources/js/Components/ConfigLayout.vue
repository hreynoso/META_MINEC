<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Palette, Mail, Users, ShieldCheck, Bell, Sparkles, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{ section: string }>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user ?? {});
const online = computed(() => (page.props.auth as any)?.online ?? 1);
const roles = computed<string[]>(() => (page.props.auth as any)?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('Super Admin'));

// superOnly: secciones de máximo privilegio (solo Super Admin).
const sections = [
    { key: 'branding', label: 'Branding', sub: 'Logos, imágenes y colores', icon: Palette, route: 'configuracion.edit' },
    { key: 'correo', label: 'Correo', sub: 'SMTP / Mailgun + prueba', icon: Mail, route: 'configuracion.correo.edit' },
    { key: 'usuarios', label: 'Usuarios', sub: 'Usuarios del sistema', icon: Users, route: 'configuracion.usuarios.index', superOnly: true },
    { key: 'roles', label: 'Roles y permisos', sub: 'Atributos de acceso', icon: ShieldCheck, route: 'configuracion.roles.index', superOnly: true },
    { key: 'notificaciones', label: 'Notificaciones', sub: 'Correos automáticos del sistema', icon: Bell, route: 'configuracion.notificaciones.edit' },
    { key: 'ia', label: 'Inteligencia Artificial', sub: 'Proveedor y credenciales del API', icon: Sparkles, route: 'configuracion.ia.edit', superOnly: true },
];

const visibleSections = computed(() => sections.filter((s) => !s.superOnly || isSuperAdmin.value));

const activeLabel = computed(() => sections.find((s) => s.key === props.section)?.label ?? 'Configuración');

// Reloj en vivo para la tarjeta de fecha y hora. La fecha muestra el día y el
// nombre del mes (p. ej. "9 de julio de 2026").
function formatNow(): string {
    return new Date().toLocaleString('es-DO', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    });
}
const now = ref(formatNow());
let timer: number | undefined;
onMounted(() => { timer = window.setInterval(() => { now.value = formatNow(); }, 1000); });
onBeforeUnmount(() => { if (timer) window.clearInterval(timer); });
</script>

<template>
    <AppLayout>
        <!-- Breadcrumb -->
        <nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-400">
            <Link :href="route('dashboard')" class="hover:text-slate-600 dark:hover:text-slate-300">Inicio</Link>
            <ChevronRight class="h-3.5 w-3.5" />
            <span class="text-slate-600 dark:text-slate-300">Configuración</span>
        </nav>

        <!-- Cabecera -->
        <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Sistema — Configuración</h1>
                <p class="mt-1 text-sm text-slate-500">Administre la configuración general del sistema, módulos e integraciones.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Fecha y hora</p>
                    <p class="text-sm font-medium tabular-nums">{{ now }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Usuario</p>
                    <p class="text-sm font-medium">{{ user.name ?? '—' }}</p>
                    <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 px-4 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Sección</p>
                    <p class="text-sm font-medium">{{ activeLabel }}</p>
                </div>
                <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 dark:border-teal-900/50 dark:bg-teal-900/20">
                    <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-teal-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500" /> Conectados
                    </p>
                    <p class="text-sm font-medium text-teal-700 dark:text-teal-300">{{ online }} usuario{{ online === 1 ? '' : 's' }}</p>
                </div>
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
                            <span class="block text-sm font-medium">{{ s.label }}</span>
                            <span class="block text-xs text-slate-400">{{ s.sub }}</span>
                        </span>
                    </Link>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
