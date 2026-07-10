import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Comprobación de permisos en el frontend (A.8.3). Los permisos y roles se
 * comparten desde el backend (HandleInertiaRequests). El Super Admin siempre
 * puede (coincide con Gate::before del servidor). La autorización real la impone
 * el backend; esto solo evita mostrar acciones que el usuario no puede ejecutar.
 */
export function useCan() {
    const page = usePage();
    const permissions = computed<string[]>(() => (page.props.auth as any)?.permissions ?? []);
    const roles = computed<string[]>(() => (page.props.auth as any)?.roles ?? []);
    const isSuperAdmin = computed(() => roles.value.includes('Super Admin'));

    function can(permission: string): boolean {
        return isSuperAdmin.value || permissions.value.includes(permission);
    }

    return { can, permissions, roles, isSuperAdmin };
}
