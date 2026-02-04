# Middleware reference

## Middleware mapping (security schemes)

El paquete permite mapear middlewares a esquemas de seguridad OpenAPI.

| Middleware | Security Scheme | Evidence |
| --- | --- | --- |
| `auth:sanctum` | `BearerAuth` | config/openapi.php (`middleware_security_map`) |
| `auth:api` | `BearerAuth` | config/openapi.php (`middleware_security_map`) |
| `api.key` | `ApiKeyAuth` | config/openapi.php (`middleware_security_map`) |

Evidence:
- File: config/openapi.php
  - Notes: `middleware_security_map`.
- File: src/Services/OpenApiServices.php
  - Notes: Lee `middleware_security_map`.

## Middlewares propios

No se detectan clases de middleware propias en el paquete.

Evidence:
- File: src
  - Notes: No hay carpeta `Middleware` ni clases relacionadas.
