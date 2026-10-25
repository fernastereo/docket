export interface PlatformUser {
    id: string;
    name: string;
    email: string;
    /** El usuario ya activó su app de autenticación. */
    mfa_enabled: boolean;
    /** Esta sesión ya pasó el segundo factor (sin eso el panel responde 403). */
    mfa_verified: boolean;
}

export interface MfaSetup {
    /** Clave en base32 para teclearla si no se puede escanear el QR. */
    secret: string;
    uri: string;
    /** Imagen SVG en data: URI. */
    qr: string;
}

export type TenantStatus = 'provisioning' | 'failed' | 'demo' | 'active' | 'suspended' | 'terminated';

export interface Tenant {
    id: string;
    name: string;
    host: string | null;
    status: TenantStatus;
    failure_reason: string | null;
    provisioned_at: string | null;
    created_at: string;
}

export interface NewTenant {
    name: string;
    subdomain: string;
    active: boolean;
}
