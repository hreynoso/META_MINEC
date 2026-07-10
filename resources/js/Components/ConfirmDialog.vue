<script setup lang="ts">
import ConfirmDialog from 'primevue/confirmdialog';
import { AlertTriangle } from 'lucide-vue-next';
</script>

<template>
    <!--
      Diálogo de confirmación global. Se controla vía useConfirm().
      Se cierra SOLO con botones (sin Escape ni click en el fondo).
      Botones con colores distintos: Cancelar (neutro) vs Aceptar/Eliminar
      (marca, o rojo cuando la acción es destructiva: message.danger === true).
    -->
    <ConfirmDialog :closable="false" :close-on-escape="false" :dismissable-mask="false">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="(message as any).danger
                            ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                            : 'bg-brand/10 text-brand'"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold">{{ message.header }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">{{ message.message }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="rejectCallback"
                    >
                        {{ message.rejectLabel }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90"
                        :class="(message as any).danger ? 'bg-red-600' : 'bg-brand'"
                        @click="acceptCallback"
                    >
                        {{ message.acceptLabel }}
                    </button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
