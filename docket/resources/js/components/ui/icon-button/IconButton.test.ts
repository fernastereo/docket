import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { h } from 'vue';
import { IconButton } from '@/components/ui/icon-button';

// jsdom no trae ResizeObserver y el posicionamiento del tooltip lo usa al abrirse.
beforeAll(() => {
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            unobserve() {}
            disconnect() {}
        },
    );
});

const mounted: Array<ReturnType<typeof mount>> = [];

function mountButton(props: Record<string, unknown> = {}) {
    const wrapper = mount(IconButton, {
        attachTo: document.body,
        props: { label: 'Cambiar contraseña', ...props },
        slots: { default: () => h('svg', { 'data-icon': true }) },
    });
    mounted.push(wrapper);

    return wrapper;
}

describe('IconButton', () => {
    afterEach(() => {
        mounted.splice(0).forEach((wrapper) => wrapper.unmount());
        document.body.innerHTML = '';
    });

    it('is named for assistive technology: the label by default, or a more specific one', () => {
        expect(mountButton().get('button').attributes('aria-label')).toBe('Cambiar contraseña');
        expect(mountButton({ ariaLabel: 'Cambiar la contraseña de ana@cu.test' }).get('button').attributes('aria-label')).toBe('Cambiar la contraseña de ana@cu.test');
    });

    it('looks like a link, not like a button: no border or background, a hand, and a color change on hover', () => {
        const classes = mountButton().get('button').classes();

        expect(classes).toContain('cursor-pointer');
        expect(classes).toContain('text-muted-foreground');
        expect(classes).toContain('hover:text-action');
        expect(classes.some((c) => c === 'border' || c.startsWith('bg-') || c.startsWith('shadow'))).toBe(false);
    });

    it('shows the label as a tooltip when it gets keyboard focus', async () => {
        const wrapper = mountButton();
        expect(document.body.textContent).not.toContain('Cambiar contraseña');

        await wrapper.get('button').trigger('focus');
        await flushPromises();

        expect(document.body.textContent).toContain('Cambiar contraseña');
    });

    it('emits click when active', async () => {
        const wrapper = mountButton();

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('does not emit click when disabled, and explains why in its tooltip', async () => {
        const wrapper = mountButton({ disabled: true, disabledReason: 'Solo en una curaduría activa.' });

        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('click')).toBeUndefined();
        expect(wrapper.get('button').attributes('disabled')).toBeDefined();

        // El foco va al span que envuelve al botón (un botón deshabilitado no lo recibe).
        const trigger = wrapper.get('span[tabindex="0"]');
        await trigger.trigger('focus');
        await flushPromises();

        expect(document.body.textContent).toContain('Solo en una curaduría activa.');
    });
});
