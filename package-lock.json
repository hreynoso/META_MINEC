import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm';

/**
 * Wrapper sobre el ConfirmationService de PrimeVue. SIEMPRE usar esto para
 * confirmaciones — NUNCA el confirm() nativo del navegador.
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
    }) {
        confirm.require({
            message: options.message,
            header: options.header ?? 'Confirmar',
            acceptLabel: options.acceptLabel ?? 'Confirmar',
            rejectLabel: options.rejectLabel ?? 'Cancelar',
            accept: options.accept,
            reject: options.reject,
        });
    }

    return { ask };
}
