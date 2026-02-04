# Security

## Protect documentation routes

Documentation routes can expose internal API structure. If used in production, protect them with authentication or restrict access.

```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'middleware' => ['auth:sanctum'],
],
```

## Control output paths

The generator writes to `openapi.output_path`. Ensure the storage path is not publicly accessible unless intended.

## Cache safety

When caching is enabled, ensure cache drivers are secured and scoped appropriately for your environment. Use cache key prefixes to avoid collisions.

## Runtime env usage

Some runtime services call `env()` directly (e.g., `APP_NAME`, `APP_URL`). In production with `config:cache`, prefer setting the corresponding config keys to avoid stale values.

**Next:** [Testing](01-testing.md) • [Docs index](../index.md)
