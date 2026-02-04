# Environment variables

| Env var | Default | Used in | Purpose |
| --- | --- | --- | --- |
| `APP_NAME` | `Laravel API` | config/openapi.php | Título base del API y placeholders. |
| `API_VERSION` | `1.0.0` | config/openapi.php | Versión del API en `info`. |
| `API_CONTACT_NAME` | `API Support` | config/openapi.php | Contact name. |
| `API_CONTACT_EMAIL` | `support@example.com` | config/openapi.php | Contact email. |
| `API_CONTACT_URL` | `https://example.com/support` | config/openapi.php | Contact URL. |
| `APP_URL` | `http://localhost:8000` | config/openapi.php | Base URL default en environments. |
| `OPENAPI_CACHE_ENABLED` | `true` | config/openapi.php | Activa cache del spec. |
| `OPENAPI_CACHE_TTL` | `3600` | config/openapi.php | TTL del cache (segundos). |
| `OPENAPI_ROUTES_ENABLED` | `true` | config/openapi.php | Habilita rutas HTTP. |
| `OPENAPI_ROUTES_PREFIX` | `documentation` | config/openapi.php | Prefijo para rutas HTTP. |
| `OPENAPI_ROUTES_MIDDLEWARE` | `` | config/openapi.php | Middleware para rutas HTTP (CSV). |
| `OPENAPI_TEMPLATES_ENABLED` | `true` | config/openapi-templates.php | Activa templates JSON. |
| `OPENAPI_TEMPLATES_DEBUG` | `false` | config/openapi-templates.php | Debug del renderer. |
| `OPENAPI_TEMPLATES_VALIDATE` | `false` | config/openapi-templates.php | Validación de salida del template. |
| `OPENAPI_TEMPLATES_CACHE` | `true` | config/openapi-templates.php | Cache de templates. |
| `OPENAPI_TEMPLATE_CACHE_TTL` | `3600` | config/openapi-templates.php | TTL cache de templates. |
| `OPENAPI_TESTS_VERBOSE` | `false` | config/openapi-tests.php | Logging detallado de tests. |

Evidence:
- File: config/openapi.php
  - Notes: Variables de entorno para info, cache y rutas.
- File: config/openapi-templates.php
  - Notes: Variables de entorno para templates.
- File: config/openapi-tests.php
  - Notes: `OPENAPI_TESTS_VERBOSE`.
