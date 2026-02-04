# Environment variables

The package reads several environment variables (primarily through configuration). Use these in `.env` or your host environment when publishing config.

## Core variables

| Variable | Used by | Purpose |
| --- | --- | --- |
| `APP_NAME` | `openapi.info.title`, placeholder replacement | Project name and placeholder expansion. |
| `APP_URL` | `openapi.environments.base.variables.base_url` | Base URL for generated environments. |
| `API_VERSION` | `openapi.info.version` | Version string in the OpenAPI info block. |
| `API_CONTACT_NAME` | `openapi.info.contact.name` | Support contact name. |
| `API_CONTACT_EMAIL` | `openapi.info.contact.email` | Support contact email. |
| `API_CONTACT_URL` | `openapi.info.contact.url` | Support contact URL. |

## Routing variables

| Variable | Used by | Purpose |
| --- | --- | --- |
| `OPENAPI_ROUTES_ENABLED` | `openapi.routes.enabled` | Enable/disable HTTP endpoints. |
| `OPENAPI_ROUTES_PREFIX` | `openapi.routes.prefix` | Prefix for documentation routes. |
| `OPENAPI_ROUTES_MIDDLEWARE` | `openapi.routes.middleware` | Comma-separated middleware list. |

## Cache variables

| Variable | Used by | Purpose |
| --- | --- | --- |
| `OPENAPI_CACHE_ENABLED` | `openapi.cache.enabled` | Toggle caching for generated specs. |
| `OPENAPI_CACHE_TTL` | `openapi.cache.ttl` | Cache TTL (seconds). |

## Template system variables

| Variable | Used by | Purpose |
| --- | --- | --- |
| `OPENAPI_TEMPLATES_ENABLED` | `openapi-templates.enabled` | Toggle template system. |
| `OPENAPI_TEMPLATES_DEBUG` | `openapi-templates.rendering.debug` | Debug output for template rendering. |
| `OPENAPI_TEMPLATES_VALIDATE` | `openapi-templates.rendering.validate_output` | Validate template output. |
| `OPENAPI_TEMPLATES_CACHE` | `openapi-templates.rendering.cache_enabled` | Cache rendered templates. |
| `OPENAPI_TEMPLATE_CACHE_TTL` | `openapi-templates.rendering.cache_ttl` | Template cache TTL (seconds). |

## Test generation variables

| Variable | Used by | Purpose |
| --- | --- | --- |
| `OPENAPI_TESTS_VERBOSE` | `openapi-tests.verbose_logging` | Verbose logging for test generation. |

### Note on runtime `env()` usage

This package also uses `env()` directly in runtime code (e.g., environment generation and placeholder replacement). If you rely on `config:cache`, prefer overriding the corresponding config keys rather than changing env at runtime.

**Next:** [Publishing assets](02-publishing-assets.md) • [Docs index](../index.md)
