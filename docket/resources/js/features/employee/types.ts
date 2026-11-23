import type { AuthUser } from '@/features/auth/types';

/** Empleado con sesión en su curaduría. */
export interface EmployeeUser extends AuthUser {
    /** Valores de rol (`admin`, `architect`…). */
    roles: string[];
    /** Los mismos roles, con el nombre para mostrar. */
    role_labels: string[];
    mfa_required: boolean;
}
