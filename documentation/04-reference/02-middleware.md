# Middleware

This package does not ship custom middleware classes. Instead, it lets you configure middleware for the HTTP documentation routes via `openapi.routes.middleware`.

Example:

```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['auth:sanctum'],
],
```

**Next:** [Events](03-events.md) • [Docs index](../index.md)

## Evidence
- File: config/openapi.php
  - Symbol: return array (routes.middleware)
  - Notes: Configurable middleware array.
- File: src/routes/web.php
  - Symbol: Route::middleware($middleware)
  - Notes: Applies middleware to documentation routes.
