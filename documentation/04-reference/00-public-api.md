# Public API

This section lists the supported public API surface for consumers.

## Service provider

Auto-discovered provider:

- `Ronu\OpenApiGenerator\Providers\OpenApiGeneratorServiceProvider`

Responsibilities:
- Registers `OpenApiServices` and `OpenApiGenerator` singletons.
- Publishes configuration and templates.
- Registers the CLI command.

## Facade

Facade alias:

- `OpenApiGenerator` → `Ronu\OpenApiGenerator\Facades\OpenApiGenerator`

## Programmatic generator

`Ronu\OpenApiGenerator\OpenApiGenerator` exposes:

```php
$generator = app(\Ronu\OpenApiGenerator\OpenApiGenerator::class);

$openapi = $generator->generateOpenApi();
$postman = $generator->generatePostman();
$insomnia = $generator->generateInsomnia();
```

## HTTP controller

`Ronu\OpenApiGenerator\Controllers\OpenApiController` handles HTTP generation routes (`openapi.{format}`, `postman`, `insomnia`, `environments`).

**Next:** [Artisan commands](01-artisan-commands.md) • [Docs index](../index.md)

## Evidence
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Symbol: OpenApiGeneratorServiceProvider::register(), OpenApiGeneratorServiceProvider::boot()
  - Notes: Registers services, publishes assets, and registers commands.
- File: src/Facades/OpenApiGenerator.php
  - Symbol: OpenApiGenerator::getFacadeAccessor()
  - Notes: Facade binding.
- File: src/OpenApiGenerator.php
  - Symbol: OpenApiGenerator::generateOpenApi(), generatePostman(), generateInsomnia()
  - Notes: Programmatic API.
- File: src/Controllers/OpenApiController.php
  - Symbol: OpenApiController::generate(), postman(), insomnia(), environments()
  - Notes: HTTP API endpoints.
