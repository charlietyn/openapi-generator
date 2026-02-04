# Route filtering

## Overview
El generador filtra rutas por tipo de API, patrones excluidos y módulos.  
Evidence:
- File: src/Services/OpenApiServices.php
  - Notes: Inspección de rutas y filtros.
- File: config/openapi.php
  - Notes: `exclude_routes` y `api_types`.

## When to use / When NOT to use

**Úsalo cuando**:
- Necesitas omitir rutas internas (telescope, horizon, etc.).

**No lo uses cuando**:
- Quieres documentar absolutamente todas las rutas (vacía `exclude_routes`).

Evidence:
- File: config/openapi.php
  - Notes: `exclude_routes` con patrones de ejemplo.

## How it works (internals resumidos)

1) Carga rutas con `Route::getRoutes()`.
2) Excluye por patrones definidos.
3) Filtra por API type (prefijo + config).
4) Excluye rutas raíz de módulos si aplica.

Evidence:
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::inspectRoutes()
  - Notes: Filtros principales.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::isModuleRootRoute()
  - Notes: Excluye rutas raíz de módulos.

## Configuration (keys/env involucradas)

- `openapi.exclude_routes`
- `openapi.api_types`
- `openapi.modules_path`
- `openapi.exclude_modules` (si se usa)
- `openapi.exclude_module_routes` (si se usa)

Evidence:
- File: config/openapi.php
  - Notes: `exclude_routes`, `api_types`, `modules_path`.
- File: src/Services/OpenApiServices.php
  - Notes: Lee `exclude_modules` y `exclude_module_routes`.

## Usage examples

```php
// config/openapi.php
'exclude_routes' => [
    'telescope/*',
    'horizon/*',
    'api/documentation/*',
],
```

Evidence:
- File: config/openapi.php
  - Notes: Ejemplos de exclusión.

## Edge cases / pitfalls

- Si el prefijo no coincide con `api_types`, el endpoint no se documenta.
- Rutas sin nombre usan detección por URI; asegúrate de que el último segmento sea estable.

Evidence:
- File: src/Services/OpenApiServices.php
  - Notes: Detección de acción por URI.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: config keys](../09-reference/config-keys.md)

## Evidence
- File: src/Services/OpenApiServices.php
  - Notes: Lógica de filtros.
