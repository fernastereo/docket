import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import HomePage from '@/features/employee/pages/HomePage.vue';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';

describe('employee HomePage', () => {
    it('greets the employee with the names of their roles', () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        useEmployeeAuthStore().user = {
            id: 'e1',
            name: 'Ana Pérez',
            email: 'ana@curaduria.test',
            roles: ['architect', 'curator'],
            role_labels: ['Arquitecto', 'Curador'],
            mfa_enabled: true,
            mfa_required: true,
            mfa_verified: true,
        };

        const wrapper = mount(HomePage, { global: { plugins: [pinia] } });

        expect(wrapper.text()).toContain('Ana Pérez · Arquitecto, Curador');
    });
});
