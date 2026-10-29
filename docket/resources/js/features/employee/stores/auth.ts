import { defineAuthStore } from '@/features/auth/createAuthStore';
import type { EmployeeUser } from '@/features/employee/types';

/** Sesión del empleado en su curaduría (API en `/api/employee/*`). */
export const useEmployeeAuthStore = defineAuthStore<EmployeeUser>('employeeAuth', '/employee');
