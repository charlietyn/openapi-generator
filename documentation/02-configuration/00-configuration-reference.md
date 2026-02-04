# Configuration reference

This section documents the configuration files published by the package.

## `config/openapi.php`

| Key | Type | Purpose |
| --- | --- | --- |
| `info` | array | OpenAPI metadata (title, description, version, contact, license). |
| `servers` | array | Server list used in OpenAPI output. |
| `security` | array | Security scheme definitions (Bearer, API key). |
| `environments` | array | Base + sub-environments for Postman/Insomnia generation. |
| `api_types` | array | API type filters and route prefixes (`admin`, `site`, `mobile`). |
| `modules_path` | string | Path for module scanning (Nwidart style). |
| `exclude_modules` | array | Module names to skip. |
| `exclude_module_routes` | array | Module-specific route exclusions. |
| `cache` | array | Cache toggle, TTL, and key prefix. |
| `output_path` | string | Output directory for generated files. |
| `paths` | array | Namespaces for models and FormRequests. |
| `exclude_routes` | array | URI patterns to skip from documentation. |
| `middleware_security_map` | array | Map middleware to security schemes. |
| `response_examples` | array | Default response examples by status code. |
| `routes` | array | HTTP documentation routes (enabled, prefix, middleware). |

### Example snippet

```php
return [
    'info' => [
        'title' => env('APP_NAME', 'Laravel API'),
        'version' => env('API_VERSION', '1.0.0'),
    ],
    'api_types' => [
        'admin' => [
            'prefix' => 'admin',
            'enabled' => true,
        ],
    ],
    'routes' => [
        'enabled' => env('OPENAPI_ROUTES_ENABLED', true),
        'prefix' => env('OPENAPI_ROUTES_PREFIX', 'documentation'),
    ],
];
```

## `config/openapi-docs.php`

Controls CRUD templates, entity metadata, and custom endpoints.

| Key | Purpose |
| --- | --- |
| `crud_templates` | Summary/description/response templates for CRUD actions. |
| `entities` | Optional metadata for entities (singular/plural, model, description). |
| `custom_endpoints` | Custom documentation for non-CRUD endpoints. |
| `auto_detect` | Enables automatic field/relationship extraction. |
| `field_descriptions` | Overrides field descriptions. |
| `field_examples` | Overrides field examples. |

## `config/openapi-templates.php`

Template engine controls for JSON templates under `resources/openapi/templates/`.

| Key | Purpose |
| --- | --- |
| `enabled` | Toggle template system. |
| `paths` | Paths for generic/custom templates. |
| `generic_templates` | Action-to-template map. |
| `query_builder` | Controls query builder documentation. |
| `auto_detect` | Model metadata extraction toggles. |
| `rendering` | Debug/validate/cache rendering settings. |
| `examples` | Example generation settings. |
| `performance` | Limits and caching for metadata extraction. |

## `config/openapi-tests.php`

Test template definitions for Postman and Insomnia.

| Key | Purpose |
| --- | --- |
| `templates` | Checks for CRUD actions. |
| `snippets` | Actual test scripts for Postman/Insomnia. |
| `custom_tests` | Overrides for endpoint-specific test scripts. |

**Next:** [Environment variables](01-env-vars.md) • [Docs index](../index.md)
