# Basic usage

## Generate via Artisan

```bash
php artisan openapi:generate
```

Common options:

```bash
# Generate all formats (OpenAPI + Postman + Insomnia)
php artisan openapi:generate --all

# Generate only Postman or Insomnia
php artisan openapi:generate --with-postman
php artisan openapi:generate --with-insomnia

# Filter by API type(s)
php artisan openapi:generate --api-type=admin --api-type=mobile
```

## Generate via HTTP routes

When `openapi.routes.enabled` is true, the package registers documentation endpoints:

```bash
curl http://localhost:8000/documentation/openapi.json
curl http://localhost:8000/documentation/openapi.yaml
curl http://localhost:8000/documentation/postman
curl http://localhost:8000/documentation/insomnia
```

Filter with query parameters:

```bash
curl "http://localhost:8000/documentation/openapi.json?api_type=admin,mobile"
curl "http://localhost:8000/documentation/postman?api_type=mobile&environment=production"
```

## Generate programmatically

```php
use Ronu\OpenApiGenerator\OpenApiGenerator;

$generator = app(OpenApiGenerator::class);

$openapi = $generator->generateOpenApi();
$postman = $generator->generatePostman();
$insomnia = $generator->generateInsomnia();
```

**Next:** [Advanced usage](01-advanced-usage.md) • [Docs index](../index.md)

## Evidence
- File: src/Commands/GenerateOpenApiSpec.php
  - Symbol: GenerateOpenApiSpec::$signature
  - Notes: Defines the CLI command and options.
- File: src/routes/web.php
  - Symbol: Route::get('openapi.{format}')
  - Notes: Declares HTTP endpoints for OpenAPI/Postman/Insomnia output.
- File: src/OpenApiGenerator.php
  - Symbol: OpenApiGenerator::generateOpenApi(), generatePostman(), generateInsomnia()
  - Notes: Provides programmatic API surface.
