# Troubleshooting

## Error: Invalid format

**Síntoma**: El comando/endpoint devuelve error sobre formato inválido.

**Causa**: Formato no es `json`, `yaml` o `yml` (CLI) / `json|yaml|yml|postman|insomnia` (HTTP).

**Solución**:
- Usa `--format=json|yaml` en CLI.
- En HTTP, usa `/openapi.json` o `/openapi.yaml`.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Validación de formato CLI.
- File: src/Controllers/OpenApiController.php
  - Notes: Validación de formato HTTP.

## Error: Invalid api_type

**Síntoma**: Respuesta 422 o excepción por api_type desconocido.

**Causa**: `api_type` no existe o está deshabilitado en `config/openapi.php`.

**Solución**:
- Verifica `openapi.api_types` y `enabled`.

Evidence:
- File: src/Controllers/OpenApiController.php
  - Notes: `parseApiTypes()` valida api_type.
- File: src/Services/OpenApiServices.php
  - Notes: `validateApiTypes()`.
- File: config/openapi.php
  - Notes: `api_types`.

## Warning: No routes found

**Síntoma**: CLI muestra warning “No routes found”.

**Causa**: No hay rutas que pasen los filtros (prefijo/API type/exclusiones).

**Solución**:
- Revisa `exclude_routes` y tus prefijos.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Warning cuando `paths` está vacío.
- File: src/Services/OpenApiServices.php
  - Notes: `inspectRoutes()` filtra por prefijo y exclusiones.

## HTTP endpoints no responden

**Síntoma**: 404 al acceder a `/documentation/openapi.json`.

**Causa**: El archivo de rutas del paquete no fue cargado en tu app.

**Solución**:
- Asegura que tu app cargue `src/routes/web.php` (ej. en un Service Provider propio).

Evidence:
- File: src/routes/web.php
  - Notes: Rutas definidas.
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: No registra rutas.

## Environment no válido

**Síntoma**: CLI usa environment `artisan` aunque especificaste otro.

**Causa**: El environment no existe en `openapi.environments`.

**Solución**:
- Agrega el environment en `config/openapi.php`.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Validación de environment.
- File: config/openapi.php
  - Notes: `environments`.

## Evidence
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Mensajes de error/advertencia.
