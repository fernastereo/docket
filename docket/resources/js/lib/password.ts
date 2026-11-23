/**
 * Largos mínimos de contraseña por política (politicas.md §2). El servidor es quien
 * manda; estos números solo evitan enviar de más y deben mantenerse iguales a
 * config/employee.php y config/platform.php → password_min_length.
 */
export const EMPLOYEE_MIN_PASSWORD_LENGTH = 8;
export const PLATFORM_MIN_PASSWORD_LENGTH = 12;

/** Largo de la contraseña sugerida: por encima de los dos mínimos. */
export const GENERATED_PASSWORD_LENGTH = 14;

// Sin los caracteres que se confunden al leerlos o copiarlos a mano (l, I, 1, O, o, 0).
const LOWER = 'abcdefghijkmnpqrstuvwxyz';
const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
const DIGITS = '23456789';
// Solo símbolos que están a la vista en cualquier teclado y que ningún formulario trata distinto.
const SYMBOLS = '!@#$%&*?+=-_';

export const PASSWORD_CHARACTER_SETS = { LOWER, UPPER, DIGITS, SYMBOLS } as const;

/** Entero uniforme en [0, max) con el generador del navegador (sin sesgo de módulo). */
function randomBelow(max: number): number {
    const limit = Math.floor(0x100000000 / max) * max;
    const buffer = new Uint32Array(1);
    do {
        crypto.getRandomValues(buffer);
    } while (buffer[0] >= limit);

    return buffer[0] % max;
}

function pick(characters: string): string {
    return characters[randomBelow(characters.length)];
}

/**
 * Contraseña aleatoria con minúsculas, mayúsculas, números y símbolos (al menos
 * uno de cada), mezclados de forma uniforme.
 */
export function generatePassword(): string {
    const all = LOWER + UPPER + DIGITS + SYMBOLS;
    const characters = [pick(LOWER), pick(UPPER), pick(DIGITS), pick(SYMBOLS)];
    while (characters.length < GENERATED_PASSWORD_LENGTH) {
        characters.push(pick(all));
    }

    // Fisher-Yates: sin esto, las cuatro primeras posiciones siempre tendrían el mismo orden de clases.
    for (let i = characters.length - 1; i > 0; i--) {
        const j = randomBelow(i + 1);
        [characters[i], characters[j]] = [characters[j], characters[i]];
    }

    return characters.join('');
}
