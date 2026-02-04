# Generación vía Artisan (CLI)

## Overview
Genera documentación desde consola con el comando `openapi:generate`, exportando OpenAPI (JSON/YAML) y opcionalmente Postman e Insomnia.  
Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Definición del comando y opciones.

## When to use / When NOT to use

**Úsalo cuando**:
- Necesitas generar documentación como artefacto en CI/CD.
- Quieres exportar archivos a `storage/app/public/openapi`.

**No lo uses cuando**:
- Requieres respuesta inmediata vía HTTP (usa endpoints HTTP).

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: `output_path` por defecto y flags.
- File: src/Controllers/OpenApiController.php
  - Notes: Endpoints HTTP para formatos.

## How it works (internals resumidos)

1) Valida formato y entorno.
2) Aplica filtros de API type.
3) Invoca `OpenApiServices::generate()`.
4) Escribe archivos JSON/YAML y (opcional) Postman/Insomnia.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Symbol: GenerateOpenApiSpec::handle()
  - Notes: Flujo principal.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate()
  - Notes: Generación del spec.

## Configuration (keys/env involucradas)

- `openapi.output_path`
- `openapi.cache.enabled`
- `openapi.cache.ttl`
- `openapi.api_types`
- `openapi.environments`

Evidence:
- File: config/openapi.php
  - Notes: Secciones `output_path`, `cache`, `api_types`, `environments`.
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Usa `output_path` y `cache`.

## Usage examples

```bash
# OpenAPI JSON
php artisan openapi:generate

# OpenAPI YAML
php artisan openapi:generate --format=yaml

# Postman + Insomnia (todas las API types)
php artisan openapi:generate --all

# Filtrar por tipo de API
php artisan openapi:generate --api-type=admin --api-type=mobile

# Desactivar cache
php artisan openapi:generate --no-cache

# Elegir environment
php artisan openapi:generate --environment=production
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Signature y opciones.

## Edge cases / pitfalls

- Si no hay rutas que coincidan, el comando termina en success con advertencia.
- `--format` solo acepta `json`, `yaml` o `yml`.
- El environment debe existir en `openapi.environments` (si no, usa `artisan`).

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Validación de formato y environment.

## Related docs

- [Installation](../01-installation.md)
- [Configuration](../02-configuration.md)
- [Reference: Artisan commands](../09-reference/artisan-commands.md)

## Evidence
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Implementación del comando.
