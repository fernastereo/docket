# ADR-012: Verificación pública de actos administrativos

**Estado**: Borrador (diseño técnico por validar en implementación)
**Fecha**: 2026-08-31

## Contexto

Los actos administrativos (`docs/dominio/acto-administrativo.md`) son
documentos con valor legal que se entregan físicamente/en PDF al solicitante
y a terceros. Como cualquier documento legal en papel o PDF, son
susceptibles de alteración o falsificación, y hoy no hay forma de que un
tercero (un banco, un notario, un comprador del predio) confirme su
autenticidad sin llamar directamente a la curaduría. Identificado como
diferenciador prioritario por el usuario (2026-08-31).

## Decisión

Cada Acto Administrativo, al quedar **Expedida** (`docs/dominio/
flujo-tramite.md`) y por tanto congelado (`docs/dominio/documento.md`,
mecanismo de congelamiento), recibe:

- Un **código de verificación** único (token opaco, no correlativo/
  adivinable — ej. UUID o similar).
- Un **hash del contenido final** (ej. SHA-256 del documento congelado), 
  calculado en el momento de expedirse, para detectar alteraciones
  posteriores al PDF distribuido.
- Un **código QR** embebido en el PDF/impreso final, que apunta a una
  **página pública de verificación** (sin login) del tipo
  `https://<subdominio-tenant>/verificar/<codigo>`.

La página pública muestra únicamente lo suficiente para confirmar
autenticidad, **sin exponer datos personales sensibles** del solicitante
(no cédula, no teléfono, no email): curaduría emisora, tipo de acto, número
y fecha de expedición, número de radicado del expediente, dirección del
predio, y estado (vigente / ejecutoriada). Si el hash del documento
presentado no coincide con el registrado, la página lo señala explícitamente
como no verificable.

## Consecuencias

- Requiere generar el QR al momento de renderizar el PDF final del acto
  (parte del pipeline de congelamiento ya descrito en
  `docs/dominio/documento.md`).
- La página de verificación es pública por definición — se sirve sin
  autenticación, aislada de datos de negocio sensibles (coherente con
  ADR-002: no debe requerir cruzar a datos internos del tenant más allá de
  lo expuesto).
- Antifraude real y demostrable, buen argumento comercial frente al legado
  (que no ofrece nada equivalente).

## Pendiente

- Definir el algoritmo/formato exacto del código de verificación y su
  resistencia a fuerza bruta (longitud, alfabeto).
- Decidir si la página de verificación vive en el subdominio del tenant o en
  un dominio neutral compartido de la plataforma.
- Confirmar con criterio legal qué tan público puede ser un acto
  administrativo de una curaduría (son actos de función pública — validar
  que mostrar dirección del predio no genera un problema de privacidad
  distinto al que ya asumen los actos administrativos por su naturaleza).
