<?php

declare(strict_types=1);

// Obligatorio (ADR-011): un test de aislamiento en rojo bloquea el deploy
// (ADR-017). Necesita Postgres real (stancl/tenancy crea una base física por
// tenant) y al menos una migración/tabla de tenant para tener algo que
// aislar — ninguna de las dos existe todavía en esta fase de esqueleto.
//
// Activar en la fase "migraciones del núcleo" (CLAUDE.md, Estado actual):
// crear dos tenants reales, escribir un registro en la base del tenant A, y
// afirmar que la conexión del tenant B jamás lo ve.
test('tenant A never sees tenant B\'s data', function () {
    // ...
})->todo();
