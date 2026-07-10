<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertTriangle, X } from 'lucide-vue-next';

// Barra informativa del sistema: aparece en la parte superior, a todo lo ancho,
// y se oculta automáticamente a los 7 segundos. Lee el flash compartido por el
// backend (success / error).
const DURATION = 7000;

const page = usePage();
const visible = ref(false);
const type = ref<'success' | 'error'>('success');
const message = ref('');
let timer: number | undefined;

function show(kind: 'success' | 'error', text: string) {
    type.value = kind;
    message.value = text;
    visible.value = true;
    if (timer) window.clearTimeout(timer);
    timer = window.setTimeout(() => { visible.value = false; }, DURATION);
}

function dismiss() {
    visible.value = false;
    if (timer) window.clearTimeout(timer);
}

// Se observa la referencia del objeto flash (Inertia crea uno nuevo por visita),
// de modo que dos acciones con el mismo texto disparen la barra ambas veces.
watch(
    () => page.props.flash,
    (flash) => {
        const ok = (flash as any)?.success as string | undefined;
        const err = (flash as any)?.error as string | undefined;
        if (err) show('error', err);
        else if (ok) show('success', ok);
    },
    { immediate: true },
);

onBeforeUnmount(() => { if (timer) window.clearTimeout(timer); });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="visible"
                class="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-2 px-10 py-3 text-sm font-medium text-white shadow-lg"
                :class="type === 'error' ? 'bg-red-600' : 'bg-teal-600'"
                role="status"
                aria-live="polite"
            >
                <component :is="type === 'error' ? AlertTriangle : CheckCircle2" class="h-5 w-5 shrink-0" />
                <span class="text-center">{{ message }}</span>
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 transition hover:bg-white/20"
                    aria-label="Cerrar"
                    @click="dismiss"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
