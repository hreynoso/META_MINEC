<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Hash, TriangleAlert, Radio, Bell, Send, Users } from 'lucide-vue-next';

interface Message { id: number; author: string; institution: string; initials: string; body: string; project: string | null; system: boolean; time: string }
interface Channel { key: string; label: string; description: string; count: number }
interface Gestor { name: string; institution: string; initials: string; online: boolean }

const props = defineProps<{
    channels: Channel[];
    activeChannel: string;
    messages: Message[];
    gestores: Gestor[];
    onlineCount: number;
    institutionsConnected: number;
    projects: { id: number; label: string }[];
    currentUser: string;
}>();

const active = ref(props.activeChannel);
const messages = ref<Message[]>([...props.messages]);
const onlineCount = ref(props.onlineCount);
const channels = ref<Channel[]>([...props.channels]);
const body = ref('');
const projectId = ref<number | null>(null);
const sending = ref(false);
const notifying = ref(false);
const scroller = ref<HTMLElement | null>(null);

const CHANNEL_ICON: Record<string, any> = { general: Hash, alertas: TriangleAlert, seguimiento: Radio, metas: Bell };
const activeMeta = computed(() => channels.value.find((c) => c.key === active.value) ?? props.channels[0]);

const token = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function scrollToBottom() {
    nextTick(() => { if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight; });
}

async function fetchMessages(channel: string) {
    const res = await fetch(route('red-gestores.messages', { canal: channel }), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await res.json();
    if (channel !== active.value) return; // el usuario cambió de canal mientras cargaba
    messages.value = data.messages ?? [];
    onlineCount.value = data.onlineCount ?? onlineCount.value;
    scrollToBottom();
}

function selectChannel(key: string) {
    if (key === active.value) return;
    active.value = key;
    messages.value = [];
    fetchMessages(key);
}

async function send() {
    if (!body.value.trim() || sending.value) return;
    sending.value = true;
    try {
        const res = await fetch(route('red-gestores.store'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token() },
            body: JSON.stringify({ channel: active.value, body: body.value, project_id: projectId.value }),
        });
        if (res.ok) {
            const data = await res.json();
            messages.value.push(data.message);
            body.value = '';
            projectId.value = null;
            bumpCount(active.value);
            scrollToBottom();
        }
    } finally {
        sending.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

async function notifyRisks() {
    if (notifying.value) return;
    notifying.value = true;
    try {
        const res = await fetch(route('red-gestores.notify'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token() },
        });
        const data = await res.json();
        if (data.created > 0) {
            bumpCount('alertas');
            selectChannel('alertas');
        }
    } finally {
        notifying.value = false;
    }
}

function bumpCount(key: string) {
    const c = channels.value.find((x) => x.key === key);
    if (c) c.count += 1;
}

let timer: number | undefined;
onMounted(() => {
    scrollToBottom();
    timer = window.setInterval(() => fetchMessages(active.value), 12000);
});
onBeforeUnmount(() => { if (timer) window.clearInterval(timer); });
</script>

<template>
    <AppLayout>
        <header class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Red de Gestores</h1>
                <p class="text-sm text-slate-500">Chat institucional para alertas y seguimiento de proyectos</p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_1fr_260px]">
            <!-- Canales -->
            <section class="flex flex-col gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Canales</p>
                    <button
                        v-for="c in channels" :key="c.key"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="active === c.key ? 'bg-brand text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700'"
                        @click="selectChannel(c.key)"
                    >
                        <component :is="CHANNEL_ICON[c.key] ?? Hash" class="h-4 w-4" />
                        <span class="flex-1 text-left">{{ c.label }}</span>
                        <span v-if="c.count" class="rounded-full bg-black/10 px-1.5 text-xs" :class="active === c.key ? 'text-white' : 'text-slate-500'">{{ c.count }}</span>
                    </button>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-900/20">
                    <button
                        :disabled="notifying"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-50"
                        @click="notifyRisks"
                    >
                        <TriangleAlert class="h-4 w-4" /> {{ notifying ? 'Notificando…' : 'Notificar riesgos' }}
                    </button>
                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Publica alertas automáticas al canal de Alertas para todos los proyectos en riesgo o retrasados.</p>
                </div>
            </section>

            <!-- Canal activo -->
            <section class="flex min-h-[70vh] flex-col rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-700">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <component :is="CHANNEL_ICON[active] ?? Hash" class="h-4 w-4 text-slate-400" /> {{ activeMeta?.label }}
                        </h2>
                        <p class="text-xs text-slate-500">{{ activeMeta?.description }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs text-slate-400"><Users class="h-3.5 w-3.5" /> {{ gestores.length }} gestores</span>
                </div>

                <!-- Mensajes -->
                <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto px-5 py-4">
                    <div v-for="m in messages" :key="m.id" class="flex gap-3" :class="m.author === currentUser ? 'flex-row-reverse' : ''">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                            :class="m.system ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200'">
                            {{ m.system ? '⚠' : m.initials }}
                        </div>
                        <div class="max-w-[75%]">
                            <div class="flex items-center gap-2 text-xs text-slate-400" :class="m.author === currentUser ? 'flex-row-reverse' : ''">
                                <span class="font-medium text-slate-600 dark:text-slate-300">{{ m.author }}</span>
                                <span v-if="m.institution">· {{ m.institution }}</span>
                                <span>· {{ m.time }}</span>
                            </div>
                            <div class="mt-1 whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm"
                                :class="m.system
                                    ? 'border border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200'
                                    : m.author === currentUser
                                        ? 'bg-brand text-white'
                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-100'">
                                {{ m.body }}
                            </div>
                            <p v-if="m.project" class="mt-1 text-xs text-slate-400" :class="m.author === currentUser ? 'text-right' : ''">↳ Proyecto {{ m.project }}</p>
                        </div>
                    </div>
                    <p v-if="!messages.length" class="py-10 text-center text-sm text-slate-400">Aún no hay mensajes en este canal.</p>
                </div>

                <!-- Composer -->
                <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-700">
                    <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <select v-model="projectId" class="rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-900">
                            <option :value="null">Sin proyecto asociado</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.label }}</option>
                        </select>
                        <span>Publicando en <strong>#{{ activeMeta?.label }}</strong> como {{ currentUser }}</span>
                    </div>
                    <div class="flex items-end gap-2">
                        <textarea
                            v-model="body" rows="2" maxlength="800"
                            placeholder="Escribe un mensaje… (Enter para enviar, Shift+Enter salto de línea)"
                            class="flex-1 resize-none rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand focus:bg-white dark:border-slate-600 dark:bg-slate-900"
                            @keydown="onKeydown"
                        />
                        <button
                            :disabled="sending || !body.trim()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
                            @click="send"
                        >
                            <Send class="h-4 w-4" /> Enviar
                        </button>
                    </div>
                    <p class="mt-1 text-right text-xs text-slate-400">{{ body.length }}/800</p>
                </div>
            </section>

            <!-- Gestores -->
            <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Gestores</p>
                    <span class="text-xs text-teal-600">{{ onlineCount }} en línea</span>
                </div>
                <div class="space-y-3">
                    <div v-for="(g, idx) in gestores" :key="idx" class="flex items-center gap-2">
                        <div class="relative">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">{{ g.initials }}</div>
                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white dark:border-slate-800"
                                :class="g.online ? 'bg-teal-500' : 'bg-slate-300 dark:bg-slate-600'" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ g.name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ g.institution }}</p>
                        </div>
                    </div>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-400 dark:border-slate-700">Instituciones conectadas: {{ institutionsConnected }}</p>
            </section>
        </div>
    </AppLayout>
</template>
