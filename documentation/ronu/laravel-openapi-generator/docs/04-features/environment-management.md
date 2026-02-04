# Environment management

## Overview
El paquete genera environments para Postman e Insomnia con herencia (base → sub-entornos) y variables de tracking (IDs).  
Evidence:
- File: config/openapi.php
  - Notes: Sección `environments` con `tracking_variables`.
- File: src/Services/EnvironmentGenerator.php
  - Notes: Generación de entornos Postman/Insomnia.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Notes: Base Environment incluye tracking variables.

## When to use / When NOT to use

**Úsalo cuando**:
- Necesitas colecciones con variables y chaining de CRUD.

**No lo uses cuando**:
- No deseas variables de tracking o control de entornos.

Evidence:
- File: config/openapi.php
  - Notes: `tracking_variables` para chaining.

## How it works (internals resumidos)

1) `EnvironmentGenerator` construye variables base y de sub-entornos.
2) Para Insomnia, el workspace incluye un Base Environment y sub-entornos.
3) OpenApiServices puede aplicar un environment al spec (servers).

Evidence:
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::generatePostman()
  - Notes: Entorno Postman con variables.
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::generateInsomnia()
  - Notes: Entornos Insomnia con parentId.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::applyEnvironment()
  - Notes: Ajusta `servers` por environment.

## Configuration (keys/env involucradas)

- `openapi.environments.base.variables`
- `openapi.environments.base.tracking_variables`
- `openapi.environments.{env}.variables`

Evidence:
- File: config/openapi.php
  - Notes: Sección `environments`.

## Usage examples

```bash
# CLI: usar environment "production"
php artisan openapi:generate --environment=production
```

```http
# HTTP: usar environment "production"
GET /documentation/openapi.json?environment=production
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Flag `--environment`.
- File: src/Controllers/OpenApiController.php
  - Notes: Query param `environment`.

## Edge cases / pitfalls

- Si el environment no existe, CLI lo reemplaza por `artisan`.
- Si falta `base_url` en el environment, el spec no actualiza `servers`.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Validación de environment.
- File: src/Services/OpenApiServices.php
  - Notes: `resolveEnvironmentBaseUrl()`.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: env vars](../09-reference/env-vars.md)
- [Reference: config keys](../09-reference/config-keys.md)

## Evidence
- File: src/Services/EnvironmentGenerator.php
  - Notes: Generación de environments.
