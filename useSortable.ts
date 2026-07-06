import { route as routeFn } from 'ziggy-js';

declare global {
    // route() disponible como global en el cliente (uso en <script setup>)
    const route: typeof routeFn;
}

// route() disponible dentro de los <template> (lo inyecta ZiggyVue)
declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
    }
}

export {};
