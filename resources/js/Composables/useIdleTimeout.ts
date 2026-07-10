import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Cierre de sesión por inactividad (A.8.5/A.8.9). Muestra un aviso con cuenta
 * regresiva antes de expirar y cierra sesión si no hay actividad. También envía
 * el keep-alive (solo cuando hay actividad reciente) para mantener viva la sesión
 * del servidor mientras se usa el sistema.
 */
export function useIdleTimeout() {
    const page = usePage();

    const warning = ref(false);
    const secondsLeft = ref(0);

    let lastActivity = Date.now();
    let keepAliveAt = Date.now();
    let ticker: number | undefined;
    let loggingOut = false;

    const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
    const KEEP_ALIVE_MS = 5 * 60 * 1000;

    function cfg() {
        const c = (page.props as any)?.security?.idle ?? {};
        return {
            enabled: c.enabled ?? true,
            minutes: c.minutes ?? 30,
            warnSeconds: c.warnSeconds ?? 60,
        };
    }

    function onActivity() {
        lastActivity = Date.now();
        if (warning.value) warning.value = false;
    }

    function pingKeepAlive() {
        keepAliveAt = Date.now();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        fetch('/keep-alive', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
            .then((res) => { if (res.status === 401 || res.status === 419) window.location.href = '/login'; })
            .catch(() => { /* silencioso */ });
    }

    /** El usuario confirma que sigue activo desde el aviso. */
    function stayConnected() {
        onActivity();
        pingKeepAlive();
    }

    function tick() {
        const { enabled, minutes, warnSeconds } = cfg();
        const idle = Date.now() - lastActivity;

        // Keep-alive mientras haya actividad reciente (aunque el timeout esté
        // desactivado): mantiene viva la sesión del servidor durante el uso.
        const activeWindowMs = (enabled ? minutes : 120) * 60_000;
        if (idle < activeWindowMs && Date.now() - keepAliveAt > KEEP_ALIVE_MS) pingKeepAlive();

        if (!enabled) { warning.value = false; return; }

        const idleMs = minutes * 60_000;
        const warnMs = warnSeconds * 1000;

        if (idle >= idleMs) {
            if (!loggingOut) { loggingOut = true; router.post('/logout'); }
            return;
        }

        if (idle >= idleMs - warnMs) {
            warning.value = true;
            secondsLeft.value = Math.max(0, Math.ceil((idleMs - idle) / 1000));
        } else {
            warning.value = false;
        }
    }

    onMounted(() => {
        ACTIVITY_EVENTS.forEach((e) => window.addEventListener(e, onActivity, { passive: true }));
        ticker = window.setInterval(tick, 1000);
    });

    onBeforeUnmount(() => {
        ACTIVITY_EVENTS.forEach((e) => window.removeEventListener(e, onActivity));
        if (ticker) window.clearInterval(ticker);
    });

    return { warning, secondsLeft, stayConnected };
}
