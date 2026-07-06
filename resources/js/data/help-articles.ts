/** Contenido estatico de ayuda (se renderiza en la seccion de Ayuda). */
export interface HelpArticle {
    slug: string;
    title: string;
    body: string;
}

export const HELP_ARTICLES: HelpArticle[] = [
    {
        slug: 'inicio-sesion',
        title: '¿Cómo inicio sesión?',
        body: 'El acceso es exclusivamente con su cuenta institucional de Office 365. Pulse "Iniciar sesión con Office 365".',
    },
];
