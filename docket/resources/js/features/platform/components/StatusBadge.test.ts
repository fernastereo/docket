import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import StatusBadge from '@/features/platform/components/StatusBadge.vue';
import type { TenantStatus } from '@/features/platform/types';

describe('StatusBadge', () => {
    it.each<[TenantStatus, string]>([
        ['provisioning', 'Creando'],
        ['failed', 'Fallida'],
        ['demo', 'Demo'],
        ['active', 'Activa'],
        ['suspended', 'Suspendida'],
        ['terminated', 'Terminada'],
    ])('shows %s as "%s"', (status, label) => {
        expect(mount(StatusBadge, { props: { status } }).text()).toBe(label);
    });
});
