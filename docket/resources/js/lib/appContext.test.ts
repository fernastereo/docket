import { afterEach, describe, expect, it } from 'vitest';
import { readAppContext } from '@/lib/appContext';

function mountPoint(data: Record<string, string>): void {
    const element = document.createElement('div');
    element.id = 'app';
    Object.assign(element.dataset, data);
    document.body.append(element);
}

describe('readAppContext', () => {
    afterEach(() => {
        document.getElementById('app')?.remove();
    });

    it('is the platform panel when the server says nothing', () => {
        expect(readAppContext()).toEqual({ context: 'platform', tenantName: null });
    });

    it('reads the curaduría the server is serving', () => {
        mountPoint({ context: 'tenant', tenantName: 'Curaduría Urbana 1' });

        expect(readAppContext()).toEqual({ context: 'tenant', tenantName: 'Curaduría Urbana 1' });
    });

    it('treats anything unknown as the platform panel', () => {
        mountPoint({ context: 'otra-cosa' });

        expect(readAppContext().context).toBe('platform');
    });
});
