import { route as routeFn } from 'ziggy-js';

declare global {
    // Ziggy expone route() global en el cliente
    const route: typeof routeFn;
}

export {};
