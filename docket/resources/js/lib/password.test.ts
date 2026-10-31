import { describe, expect, it } from 'vitest';
import { GENERATED_PASSWORD_LENGTH, generatePassword, MIN_PASSWORD_LENGTH, PASSWORD_CHARACTER_SETS } from '@/lib/password';

const { LOWER, UPPER, DIGITS, SYMBOLS } = PASSWORD_CHARACTER_SETS;
const has = (password: string, characters: string) => [...password].some((c) => characters.includes(c));

describe('generatePassword', () => {
    it('is long, mixes lowercase, uppercase, digits and symbols, and only uses allowed characters', () => {
        for (let i = 0; i < 300; i++) {
            const password = generatePassword();

            expect(password).toHaveLength(GENERATED_PASSWORD_LENGTH);
            expect(password.length).toBeGreaterThanOrEqual(MIN_PASSWORD_LENGTH);
            expect(has(password, LOWER)).toBe(true);
            expect(has(password, UPPER)).toBe(true);
            expect(has(password, DIGITS)).toBe(true);
            expect(has(password, SYMBOLS)).toBe(true);
            expect([...password].every((c) => (LOWER + UPPER + DIGITS + SYMBOLS).includes(c))).toBe(true);
        }
    });

    it('never uses characters that are easy to confuse', () => {
        const confusing = /[lIO01o]/;

        for (let i = 0; i < 300; i++) {
            expect(generatePassword()).not.toMatch(confusing);
        }
    });

    it('does not repeat itself', () => {
        const generated = new Set(Array.from({ length: 100 }, () => generatePassword()));

        expect(generated.size).toBe(100);
    });

    it('does not always put the character classes in the same positions', () => {
        const firstIsLower = Array.from({ length: 200 }, () => LOWER.includes(generatePassword()[0]));

        expect(firstIsLower.some(Boolean)).toBe(true);
        expect(firstIsLower.some((isLower) => !isLower)).toBe(true);
    });
});
