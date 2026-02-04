# HTTP routes

## Endpoints disponibles (cuando se cargan las rutas del paquete)

| Method | Path | Action | Purpose |
| --- | --- | --- | --- |
| GET | `/{prefix}/openapi.{format}` | `OpenApiController::generate()` | OpenAPI JSON/YAML. |
| GET | `/{prefix}/postman` | `OpenApiController::generate('postman')` | Postman collection. |
| GET | `/{prefix}/insomnia` | `OpenApiController::generate('insomnia')` | Insomnia workspace. |
| GET | `/{prefix}/environments/{format?}` | `OpenApiController::environments()` | Lista de environments. |

Evidence:
- File: src/routes/web.php
  - Notes: Definición de rutas y nombres.
- File: src/Controllers/OpenApiController.php
  - Notes: Métodos `generate()` y `environments()`.

## Parámetros

- `format`: `json|yaml|yml` en OpenAPI.
- `api_type`: lista separada por comas.
- `environment`: nombre del environment.

Evidence:
- File: src/Controllers/OpenApiController.php
  - Notes: Query params en `generate()`.

## Nota de carga de rutas

El paquete **no** incluye `loadRoutesFrom()` en su Service Provider. Si tu aplicación no carga `src/routes/web.php`, estos endpoints no estarán disponibles.

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: No registra rutas.
- File: src/routes/web.php
  - Notes: Rutas incluidas en el paquete.
