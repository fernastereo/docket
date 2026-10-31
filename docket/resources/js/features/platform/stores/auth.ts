import { defineAuthStore } from '@/features/auth/createAuthStore';
import type { PlatformUser } from '@/features/platform/types';

/** Sesión del usuario de plataforma (API en `/api/platform/*`). */
export const usePlatformAuthStore = defineAuthStore<PlatformUser>('platformAuth', '/platform');
