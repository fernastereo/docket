/** Lo que toda cuenta con sesión (plataforma o empleado) expone a la SPA. */
export interface AuthUser {
    id: string;
    name: string;
    email: string;
    /** Ya activó su app de autenticación. */
    mfa_enabled: boolean;
    /** Esta sesión ya pasó el segundo factor (sin eso la API responde 403). */
    mfa_verified: boolean;
    /** Su rol exige MFA. Solo lo informan los empleados; plataforma siempre la exige. */
    mfa_required?: boolean;
}

export interface MfaSetup {
    /** Clave en base32 para teclearla si no se puede escanear el QR. */
    secret: string;
    uri: string;
    /** Imagen SVG en data: URI. */
    qr: string;
}

/** La parte de un store de sesión que usan las pantallas compartidas de login y MFA. */
export interface AuthSession {
    user: AuthUser | null;
    login(email: string, password: string): Promise<void>;
    startMfaSetup(): Promise<MfaSetup>;
    confirmMfa(code: string): Promise<string[]>;
    verifyMfa(code: string): Promise<void>;
    logout(): Promise<void>;
}

/** Con contraseña pero sin segundo factor al día: solo puede llegar a la pantalla de MFA. */
export function needsMfa(user: AuthUser): boolean {
    return !user.mfa_verified || (user.mfa_required === true && !user.mfa_enabled);
}
