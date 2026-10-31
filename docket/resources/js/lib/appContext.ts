/**
 * Dónde está corriendo la SPA. El servidor sirve el mismo shell en el dominio
 * central (panel de plataforma) y en cada subdominio de curaduría; el Blade
 * deja el contexto en `data-*` de #app y de ahí se lee.
 */
export type AppContext = 'platform' | 'tenant';

export interface AppInfo {
    context: AppContext;
    /** Nombre de la curaduría; solo en el contexto `tenant`. */
    tenantName: string | null;
}

export function readAppContext(): AppInfo {
    const element = typeof document === 'undefined' ? null : document.getElementById('app');

    return {
        context: element?.dataset.context === 'tenant' ? 'tenant' : 'platform',
        tenantName: element?.dataset.tenantName || null,
    };
}
