# Advanced usage

## API type filtering

API types are configured in `openapi.api_types` and can be filtered at runtime:

```bash
php artisan openapi:generate --api-type=admin --api-type=mobile
```

HTTP:

```bash
curl "http://localhost:8000/documentation/openapi.json?api_type=admin,mobile"
```

A legacy alias `movile` is normalized to `mobile` for backwards compatibility.

## Environments

Use `openapi.environments` to define base variables and sub-environment overrides. In CLI mode you can specify:

```bash
php artisan openapi:generate --environment=production
```

HTTP:

```bash
curl "http://localhost:8000/documentation/postman?environment=production"
```

## Disable cache

```bash
php artisan openapi:generate --no-cache
```

Caching is controlled by `openapi.cache.enabled` and `openapi.cache.ttl`.

## Override output paths

```bash
php artisan openapi:generate --output=/custom/path/openapi.json
```

## Template-driven documentation

Enable template processing with `openapi-templates.enabled` and place JSON templates in `resources/openapi/templates/generic` or `resources/openapi/templates/custom`.

**Next:** [Scenarios](02-scenarios.md) • [Docs index](../index.md)

## Evidence
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::setApiTypeFilter(), OpenApiServices::normalizeApiTypes()
  - Notes: Validates/normalizes API type filtering (including `movile`).
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::generatePostman(), generateInsomnia()
  - Notes: Builds environment payloads based on config.
- File: src/Commands/GenerateOpenApiSpec.php
  - Symbol: GenerateOpenApiSpec::$signature
  - Notes: Supports --environment, --no-cache, --output.
- File: config/openapi-templates.php
  - Symbol: return array
  - Notes: Template system configuration and paths.
