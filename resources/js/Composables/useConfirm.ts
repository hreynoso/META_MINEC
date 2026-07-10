import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm';

/**
 * Wrapper sobre el ConfirmationService de PrimeVue. SIEMPRE usar esto para
 * confirmaciones — NUNCA el confirm() nativo del navegador.
 *
 * `danger: true` pinta el botón de aceptar en rojo (acciones destructivas como
 * eliminar); si no, usa el color de marca. El botón de cancelar es neutro.
 */
export function useConfirm() {
    const confirm = usePrimeConfirm();

    function ask(options: {
        message: string;
        header?: string;
        accept: () => void;
        reject?: () => void;
        acceptLabel?: string;
        rejectLabel?: string;
        danger?: boolean;
    }) {
        confirm.require({
            message: options.message,
            header: options.header ?? 'Confirmar',
            acceptLabel: options.acceptLabel ?? 'Confirmar',
            rejectLabel: options.rejectLabel ?? 'Cancelar',
            accept: options.accept,
            reject: options.reject,
            // Propiedad propia leída por ConfirmDialog.vue (#container).
            danger: options.danger ?? false,
        } as any);
    }

    return { ask };
}
