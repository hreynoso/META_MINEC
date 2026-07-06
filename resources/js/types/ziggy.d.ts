import { route as routeFn } from 'ziggy-js';

declare global {
    // Ziggy expone route() global en el cliente
    const route: typeof routeFn;
}

// route() también disponible dentro de los <template> (lo inyecta ZiggyVue)
declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
    }
}

export {};
