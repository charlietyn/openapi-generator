# Endpoints HTTP

## Overview
El paquete incluye rutas HTTP para descargar especificaciones OpenAPI (JSON/YAML) y exportaciones Postman/Insomnia.  
Evidence:
- File: src/routes/web.php
  - Notes: Define rutas `openapi.{format}`, `postman`, `insomnia`.
- File: src/Controllers/OpenApiController.php
  - Notes: Controlador que genera respuestas.

## When to use / When NOT to use

**Úsalo cuando**:
- Necesitas endpoints accesibles por herramientas externas (Postman, Insomnia, CI).

**No lo uses cuando**:
- El entorno no debe exponer documentación pública (usa CLI y guarda artefactos internos).

Evidence:
- File: src/routes/web.php
  - Notes: Rutas disponibles.
- File: config/openapi.php
  - Notes: Sección `routes` permite habilitar/middleware.

## How it works (internals resumidos)

1) `OpenApiController::generate()` valida formato y `api_type`.
2) Llama a `OpenApiServices::generate()`.
3) Devuelve JSON/YAML con headers de descarga.

Evidence:
- File: src/Controllers/OpenApiController.php
  - Symbol: OpenApiController::generate()
  - Notes: Flujo principal y validaciones.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate()
  - Notes: Generación del spec.

## Configuration (keys/env involucradas)

- `openapi.routes.enabled`
- `openapi.routes.prefix`
- `openapi.routes.middleware`

Evidence:
- File: config/openapi.php
  - Notes: Sección `routes`.
- File: src/routes/web.php
  - Notes: Usa configuración para prefix/middleware.

## Usage examples

```bash
# OpenAPI JSON
GET /documentation/openapi.json

# OpenAPI YAML
GET /documentation/openapi.yaml

# Postman
GET /documentation/postman

# Insomnia
GET /documentation/insomnia

# Filtrar por api_type
GET /documentation/openapi.json?api_type=admin,mobile

# Seleccionar environment
GET /documentation/openapi.json?environment=production
```

Evidence:
- File: src/routes/web.php
  - Notes: Rutas definidas.
- File: src/Controllers/OpenApiController.php
  - Notes: Soporta `api_type` y `environment`.

## Edge cases / pitfalls

- No hay `loadRoutesFrom()` en el Service Provider; si tu app no carga el archivo `src/routes/web.php`, los endpoints no estarán disponibles.  
- `api_type` inválido devuelve error 422.

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: No registra rutas.
- File: src/routes/web.php
  - Notes: Definiciones de rutas.
- File: src/Controllers/OpenApiController.php
  - Notes: Validación de `api_type`.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: Routes](../09-reference/routes.md)
- [Security](../05-security.md)

## Evidence
- File: src/routes/web.php
  - Notes: Rutas HTTP.
