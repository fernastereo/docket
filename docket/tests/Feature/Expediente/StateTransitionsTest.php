<?php

declare(strict_types=1);

// Obligatorio (ADR-011): la máquina de estados del Expediente
// (docs/dominio/flujo-tramite.md) se activa en la fase "máquina de estados
// del Expediente" (CLAUDE.md, Estado actual) — no hay Expediente todavía.
test('legal state transitions succeed and illegal ones are rejected', function () {
    // ...
})->todo();
