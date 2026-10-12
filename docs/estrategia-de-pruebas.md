# Estrategia de pruebas

**Estado**: propuesta acordada el 2026-10-12, **para revisar** cuando existan las
primeras tablas reales del tenant y los primeros cálculos de expensas. Complementa
ADR-011 (amendment 2026-10-12). Lo que ya está construido se marca como tal; el
resto es intención, no código.

## 1. Principios

1. **Los tests normales no usan datos reales.** Un test que depende de datos que
   cambian falla sin que el código haya cambiado, y los datos reales son
   personales y de actos con valor legal (Ley 1581, ADR-017): no deben circular
   por CI ni por laptops.
2. **Una base vacía puede ocultar problemas**: migraciones que fallan sobre filas
   existentes, índices ausentes, volúmenes, codificaciones raras. Eso no se
   resuelve metiendo datos reales en la suite, sino con capas distintas, cada una
   para un riesgo (sección 4).
3. **Los tests nunca tocan las bases de desarrollo** (`docket_central`,
   `tenant<uuid>`). Esto lo imponen `phpunit.xml` y `tests/TestCase.php`.
4. **Nunca se clona un tenant de desarrollo** para probar.

## 2. Qué existe hoy (construido)

| Pieza | Para qué |
|---|---|
| Base `docket_test` | Central de los tests (catálogo desechable), siempre PostgreSQL real |
| `tests/Feature/Platform/` | Provisioning y compensación de fallos: crean tenants reales en cada test |
| `tests/Feature/Tenancy/DatabaseIsolationTest` | Aislamiento: dos tenants reales y una tabla sonda; bloquea el deploy (ADR-017) |
| `Tests\Concerns\UsesTenantDatabase` | Tests de **dominio**: un tenant de pruebas por proceso + transacción revertida por test |
| Prefijo `tenanttest_` y `make test-clean` | Distinguir y limpiar las bases físicas que crean los tests |

## 3. Rendimiento de la suite

Hoy `database/migrations/tenant/` está vacía, así que no hay nada que medir y
cualquier estimación sería inventada. Palancas, de menos a más costosa:

1. **Hecho:** el tenant compartido se crea una vez por proceso, no por test. Solo
   los pocos tests de provisioning y aislamiento crean bases propias.
2. **Base plantilla de pruebas:** migrar una vez y clonar con `CREATE DATABASE …
   TEMPLATE` (stancl: `template_tenant_connection`). Puede conservarse entre
   corridas y reconstruirse solo si cambia la carpeta de migraciones. La
   construyen los tests; **no es un tenant real**.
3. **`schema:dump` de Laravel:** cargar el esquema desde un SQL en vez de ejecutar
   cientos de migraciones. Requiere `pg_dump` en la imagen del contenedor.

**Regla de decisión:** no construir 2 ni 3 antes de medir con las tablas reales.
Si el arranque de la suite supera unos diez segundos, pasar a la plantilla.

## 4. Regresión con datos reales: capas

| Capa | Riesgo que cubre | Cómo | Cuándo entra |
|---|---|---|---|
| **Casos borde sintéticos** | Errores de lógica en situaciones raras | Factories con estados: expediente con suspensiones, prórrogas, varios predios, estados históricos. La fuente es el conocimiento del legado | Con las primeras transiciones del Expediente y las expensas |
| **Tests "dorados" de cálculos críticos** (expensas, plazos) | Que un cambio altere un resultado legal | Casos reales del legado, **anonimizados**, con el resultado que el sistema viejo ya calculó como oráculo (20 años de resultados) | Con el primer cálculo de expensas / días hábiles (ADR-014) |
| **Ensayo de migración sobre copia real** | Migraciones que fallan por la forma real de los datos | Restaurar el backup de un tenant (`pg_dump` por tenant, ADR-009) en una base desechable, correr `tenants:migrate` y verificaciones de humo | Antes del primer deploy a producción con datos reales |
| **Migración del legado** (.mdb a PostgreSQL) | Pérdida o deformación de datos | Corrida aparte, con conciliación de conteos y totales por curaduría | Con la fase de migración del legado (principio 6) |

Ejemplo real de lo que solo la tercera capa atrapa: la migración que agregó
`name NOT NULL` a `tenants` pasa en cualquier test con base vacía y fallaría en
un deploy si el entorno ya tuviera filas.

### Reglas de privacidad para las capas con datos reales

- El ensayo de migración con datos reales corre solo en un entorno controlado
  (no en CI público, no en laptops). Si los datos salen de ese entorno, antes se
  anonimizan.
- Los casos dorados y las factories no contienen identificadores reales
  (documentos, nombres, direcciones, matrículas).
- Un backup restaurado para ensayo se destruye al terminar.

## 5. Pendiente de revisar

- [ ] Medir el tiempo de provisionar el tenant compartido con el primer esquema real
      y decidir si hace falta la plantilla.
- [ ] Test que recorra todas las tablas del tenant y verifique que ninguna exista
      en la central.
- [ ] Definir el procedimiento (y el entorno) del ensayo de migración.
- [ ] Definir cómo se anonimizan los casos dorados y quién valida el resultado
      esperado.
- [ ] Decidir si esta estrategia se eleva a ADR cuando se cierre.
