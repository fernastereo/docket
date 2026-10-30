import type { AuthUser, MfaSetup } from '@/features/auth/types';

export type { MfaSetup };

/** Usuario de plataforma: la MFA siempre es obligatoria. */
export type PlatformUser = AuthUser;

export type TenantStatus = 'provisioning' | 'failed' | 'demo' | 'active' | 'suspended' | 'terminated';

/** Administrador de una curaduría; `invitation_pending`: aún no eligió contraseña. */
export interface TenantAdmin {
    name: string;
    email: string;
    invitation_pending: boolean;
}

export interface Tenant {
    id: string;
    name: string;
    host: string | null;
    status: TenantStatus;
    /** Null si la curaduría no está operable o no tiene administrador. */
    admin: TenantAdmin | null;
    failure_reason: string | null;
    provisioned_at: string | null;
    created_at: string;
}

export interface NewTenant {
    name: string;
    subdomain: string;
    active: boolean;
    /** Primer administrador de la curaduría: recibe la invitación por correo. */
    admin_name: string;
    admin_email: string;
}
