import type { AuthUser, MfaSetup } from '@/features/auth/types';

export type { MfaSetup };

/** Usuario de plataforma: la MFA siempre es obligatoria. */
export type PlatformUser = AuthUser;

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
