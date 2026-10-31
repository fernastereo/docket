import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import PasswordFields from '@/features/auth/components/PasswordFields.vue';
import { MIN_PASSWORD_LENGTH } from '@/lib/password';

function mountFields() {
    const password = ref('');
    const confirmation = ref('');
    const wrapper = mount(
        {
            components: { PasswordFields },
            setup: () => ({ password, confirmation }),
            template: '<form @submit.prevent="$emit(\'submitted\')"><PasswordFields id="pw" v-model:password="password" v-model:confirmation="confirmation" /></form>',
        },
        { attachTo: document.body },
    );

    return { wrapper, password, confirmation };
}

describe('PasswordFields', () => {
    it('asks for the password twice, hidden, with the minimum length', () => {
        const { wrapper } = mountFields();

        expect(wrapper.get('#pw').attributes('type')).toBe('password');
        expect(wrapper.get('#pw').attributes('minlength')).toBe(String(MIN_PASSWORD_LENGTH));
        expect(wrapper.get('#pw_confirmation').attributes('type')).toBe('password');
        expect(wrapper.text()).toContain(`Al menos ${MIN_PASSWORD_LENGTH} caracteres`);
    });

    it('suggests a password, repeats it in the confirmation and shows it so it can be written down', async () => {
        const { wrapper, password, confirmation } = mountFields();

        await wrapper.get('button[aria-label="Sugerir contraseña"]').trigger('click');

        expect(password.value.length).toBeGreaterThanOrEqual(MIN_PASSWORD_LENGTH);
        expect(confirmation.value).toBe(password.value);
        expect((wrapper.get('#pw').element as HTMLInputElement).value).toBe(password.value);
        expect(wrapper.get('#pw').attributes('type')).toBe('text');
        expect(wrapper.get('#pw_confirmation').attributes('type')).toBe('text');
        expect(wrapper.text()).toContain('Anótala');
    });

    it('shows and hides what was typed', async () => {
        const { wrapper } = mountFields();

        await wrapper.get('button[aria-label="Mostrar contraseña"]').trigger('click');
        expect(wrapper.get('#pw').attributes('type')).toBe('text');

        await wrapper.get('button[aria-label="Ocultar contraseña"]').trigger('click');
        expect(wrapper.get('#pw').attributes('type')).toBe('password');
    });

    it('its icon buttons never submit the form', async () => {
        const { wrapper } = mountFields();

        await wrapper.get('button[aria-label="Sugerir contraseña"]').trigger('click');
        await wrapper.get('button[aria-label="Ocultar contraseña"]').trigger('click');

        expect(wrapper.findAll('button').every((button) => button.attributes('type') === 'button')).toBe(true);
    });
});
