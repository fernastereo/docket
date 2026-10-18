export interface PlatformUser {
    id: string;
    name: string;
    email: string;
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
