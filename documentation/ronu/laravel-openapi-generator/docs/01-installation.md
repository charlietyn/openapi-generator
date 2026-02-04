# Installation

## Requisitos

- **PHP**: ^8.1
- **Laravel (illuminate/support, console, routing)**: ^10.0 | ^11.0 | ^12.0
- **Composer**: 2.x

Evidence:
- File: composer.json
  - Notes: `require` define compatibilidad de PHP y Laravel.

## Instalación con Composer

```bash
composer require ronu/laravel-openapi-generator
```

Evidence:
- File: composer.json
  - Notes: Paquete publicado como `ronu/laravel-openapi-generator`.

## Auto-discovery y Service Provider

El paquete se auto-registra con Laravel mediante auto-discovery. Si tu app tiene auto-discovery deshabilitado, registra el provider manualmente.

```php
// config/app.php
'providers' => [
    // ...
    Ronu\OpenApiGenerator\Providers\OpenApiGeneratorServiceProvider::class,
],
```

Evidence:
- File: composer.json
  - Notes: `extra.laravel.providers` incluye `OpenApiGeneratorServiceProvider`.
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: Clase del provider.

## Facade alias (opcional)

El alias `OpenApiGenerator` está disponible vía auto-discovery para acceso rápido.

Evidence:
- File: composer.json
  - Notes: `extra.laravel.aliases` define el facade.
- File: src/Facades/OpenApiGenerator.php
  - Notes: Implementación del facade.

## Publicación de configuración

```bash
php artisan vendor:publish --tag=openapi-config
```

Publica:
- `config/openapi.php`
- `config/openapi-docs.php`
- `config/openapi-tests.php`
- `config/openapi-templates.php`

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: `publishes()` con tag `openapi-config`.

## Publicación de templates (opcional)

```bash
php artisan vendor:publish --tag=openapi-templates
```

Publica JSON templates a `resources/openapi/templates/` para personalizar documentación.

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: `publishes()` con tag `openapi-templates`.
- File: resources/templates
  - Notes: Templates base incluidos en el paquete.

## Verificación rápida

```bash
php artisan openapi:generate --help
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Comando `openapi:generate` con opciones.
