# ADR-007: Almacenamiento de archivos por tenant

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

Los expedientes son mayormente documentos (planos, escrituras, PDFs pesados).
El aislamiento por tenant aplica también a archivos.

## Decisión

- Almacenamiento **S3-compatible** vía Laravel Flysystem.
  - **Producción**: DigitalOcean **Spaces**.
  - **Desarrollo local**: MinIO en Docker (API idéntica).
- **Aislamiento**: bucket o prefijo raíz por tenant; toda ruta de archivo
  incluye el tenant. stancl/tenancy configura el disk por tenant
  automáticamente.
- Cifrado en reposo habilitado.
- Metadatos y referencias de archivos en la base del tenant.
- El **export de offboarding incluye los archivos** además del dump de la base.

## Consecuencias

- Aplicación stateless respecto a archivos → escala horizontal sin fricción.
- La elección de proveedor es reversible (abstracción Flysystem).
- Definir en implementación: límites de tamaño, tipos permitidos, escaneo
  antivirus de documentos subidos por ciudadanos.
