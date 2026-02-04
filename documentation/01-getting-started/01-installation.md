# Installation

## Install via Composer

```bash
composer require ronu/laravel-openapi-generator
```

The service provider and facade are auto-discovered via `composer.json`.

## Publish configuration (recommended)

```bash
php artisan vendor:publish --tag=openapi-config
```

This publishes:
- `config/openapi.php`
- `config/openapi-docs.php`
- `config/openapi-tests.php`
- `config/openapi-templates.php`

## Publish templates (optional)

```bash
php artisan vendor:publish --tag=openapi-templates
```

This publishes JSON templates to `resources/openapi/templates/`.

**Next:** [Quickstart](02-quickstart.md) • [Docs index](../index.md)
