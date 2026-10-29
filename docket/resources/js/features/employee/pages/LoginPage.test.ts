import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createMemoryHistory, createRouter } from 'vue-router';
import LoginPage from '@/features/employee/pages/LoginPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

async function mountLogin() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/login', name: 'employee.login', component: LoginPage },
            { path: '/', name: 'employee.home', component: { template: '<p>home</p>' } },
        ],
    });
    router.push('/login');
    await router.isReady();

    return { wrapper: mount(LoginPage, { global: { plugins: [pinia, router] } }), router };
}

describe('employee LoginPage', () => {
    // Con llaves a propósito (ver platform/pages/LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
        document.getElementById('app')?.remove();
    });

    it('names the curaduría the server is serving', async () => {
        const element = document.createElement('div');
        element.id = 'app';
        element.dataset.context = 'tenant';
        element.dataset.tenantName = 'Curaduría Urbana 1';
        document.body.append(element);

        const { wrapper } = await mountLogin();

        expect(wrapper.text()).toContain('Curaduría Urbana 1');
    });

    it('signs in against the employee API and lands on the home', async () => {
        api.mockResolvedValue({
            data: { id: 'e1', name: 'Ana', email: 'ana@curaduria.test', roles: ['architect'], role_labels: ['Arquitecto'], mfa_enabled: false, mfa_required: false, mfa_verified: true },
        });
        const { wrapper, router } = await mountLogin();

        await wrapper.get('#email').setValue('ana@curaduria.test');
        await wrapper.get('#password').setValue('secreto');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(api).toHaveBeenCalledWith('/employee/login', expect.objectContaining({ method: 'POST' }));
        expect(router.currentRoute.value.name).toBe('employee.home');
    });
});
