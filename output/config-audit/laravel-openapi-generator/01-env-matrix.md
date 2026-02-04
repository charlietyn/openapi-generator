# 01-env-matrix.md

## Matriz de variables ENV

| Key | Declared in | Used in (runtime/config) | Suggested action | Risk | Notes |
| --- | --- | --- | --- | --- | --- |
| `APP_NAME` | `config/openapi.php`, docs (`README.md`, `docs/README_*`, `docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php`; runtime `src/Helpers/PlaceholderHelper.php` | KEEP (migrar runtime a `config()` con fallback) | Med | `env()` en runtime rompe `config:cache`. |
| `APP_URL` | `config/openapi.php`, docs (`README.md`, `docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php`; runtime `src/Services/EnvironmentGenerator.php` | KEEP (migrar runtime a `config()`) | Med | `env()` en runtime. |
| `API_VERSION` | `config/openapi.php`, docs (`README.md`, `documentation/.../env-vars.md`) | `config/openapi.php`; runtime `src/Services/OpenApiServices.php` | KEEP (migrar runtime a `config()`) | Med | `env()` en runtime. |
| `API_CONTACT_NAME` | `config/openapi.php`, docs (`documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Solo en config. |
| `API_CONTACT_EMAIL` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Solo en config. |
| `API_CONTACT_URL` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Solo en config. |
| `OPENAPI_CACHE_ENABLED` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Cache control. |
| `OPENAPI_CACHE_TTL` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Cache TTL. |
| `OPENAPI_ROUTES_ENABLED` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Rutas públicas por defecto; revisar seguridad. |
| `OPENAPI_ROUTES_PREFIX` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | Prefijo rutas. |
| `OPENAPI_ROUTES_MIDDLEWARE` | `config/openapi.php`, docs (`docs/INSTALLATION.md`, `documentation/.../env-vars.md`) | `config/openapi.php` | KEEP | Low | CSV; default vacío. |
| `OPENAPI_TEMPLATES_ENABLED` | `config/openapi-templates.php`, docs (`documentation/.../env-vars.md`) | **No runtime usage** | INVESTIGATE / REMOVE | Med | Config `openapi-templates.enabled` no se lee. |
| `OPENAPI_TEMPLATES_DEBUG` | `config/openapi-templates.php`, docs (`documentation/.../env-vars.md`) | `src/Services/Documentation/TemplateDocumentationResolver.php` | KEEP | Low | Debug renderer. |
| `OPENAPI_TEMPLATES_VALIDATE` | `config/openapi-templates.php`, docs (`documentation/.../env-vars.md`) | **No runtime usage** | INVESTIGATE / REMOVE | Med | `openapi-templates.rendering.validate_output` no se lee. |
| `OPENAPI_TEMPLATES_CACHE` | `config/openapi-templates.php`, docs (`documentation/.../env-vars.md`) | `src/Services/Documentation/TemplateDocumentationResolver.php` | KEEP | Low | Cache on/off. |
| `OPENAPI_TEMPLATE_CACHE_TTL` | `config/openapi-templates.php`, docs (`documentation/.../env-vars.md`) | **No runtime usage** | INVESTIGATE / REMOVE | Med | `openapi-templates.rendering.cache_ttl` no se lee. |
| `OPENAPI_TESTS_VERBOSE` | `config/openapi-tests.php`, docs (`documentation/.../env-vars.md`) | **No runtime usage** | INVESTIGATE / REMOVE | Med | `openapi-tests.verbose_logging` no se lee. |
