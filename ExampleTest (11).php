<script setup lang="ts">
import ConfirmDialog from 'primevue/confirmdialog';
</script>

<template>
    <!--
      Dialogo de confirmacion global. Se controla via useConfirm().
      Se cierra SOLO con botones (sin cerrar por Escape ni click en backdrop).
    -->
    <ConfirmDialog
        :closable="false"
        :close-on-escape="false"
        :dismissable-mask="false"
    />
</template>
