# Caching

## Overview
El generador puede cachear el spec generado para mejorar performance.  
Evidence:
- File: config/openapi.php
  - Notes: Sección `cache`.
- File: src/Services/OpenApiServices.php
  - Notes: Uso de `Cache::get/put`.

## When to use / When NOT to use

**Úsalo cuando**:
- Tienes muchas rutas y la generación es costosa.

**No lo uses cuando**:
- Estás ajustando rutas/configs frecuentemente en desarrollo.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Flag `--no-cache`.

## How it works (internals resumidos)

1) Construye una cache key según API types, environment y formato.
2) Recupera el resultado si existe.
3) Guarda el resultado al finalizar.

Evidence:
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::buildCacheKey()
  - Notes: Construcción de cache key.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate()
  - Notes: Uso de `Cache::get/put`.

## Configuration (keys/env involucradas)

- `openapi.cache.enabled`
- `openapi.cache.ttl`
- `openapi.cache.key_prefix`

Evidence:
- File: config/openapi.php
  - Notes: Configuración de cache.

## Usage examples

```bash
# Desactivar cache
php artisan openapi:generate --no-cache
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Flag `--no-cache`.

## Edge cases / pitfalls

- Cambios en rutas/metadata pueden no reflejarse si el cache está habilitado.

Evidence:
- File: src/Services/OpenApiServices.php
  - Notes: Cache habilitado por config.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: config keys](../09-reference/config-keys.md)

## Evidence
- File: config/openapi.php
  - Notes: Config de cache.
