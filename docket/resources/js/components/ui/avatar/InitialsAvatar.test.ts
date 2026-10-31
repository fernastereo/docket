import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import InitialsAvatar from '@/components/ui/avatar/InitialsAvatar.vue';

const avatar = (name: string) => mount(InitialsAvatar, { props: { name } });

describe('InitialsAvatar', () => {
    it('shows the first letters of the first two words, in capitals', () => {
        expect(avatar('ana pérez gómez').text()).toBe('AP');
        expect(avatar('Beto').text()).toBe('B');
        expect(avatar('  ').text()).toBe('');
    });

    it('gives the same person the same tone every time, and is decorative for screen readers', () => {
        const first = avatar('Ana Pérez');

        expect(avatar('Ana Pérez').classes()).toEqual(first.classes());
        expect(first.attributes('aria-hidden')).toBe('true');
    });
});
