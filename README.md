# Laravel OpenAPI Generator

Automatic OpenAPI 3.0.3, Postman, and Insomnia documentation generation for Laravel applications.

<p align="center">
    <img src="https://img.shields.io/packagist/v/ronu/laravel-openapi-generator.svg?style=flat-square" alt="Latest Version">
    <img src="https://img.shields.io/packagist/dt/ronu/laravel-openapi-generator.svg?style=flat-square" alt="Total Downloads">
    <img src="https://img.shields.io/packagist/l/ronu/laravel-openapi-generator.svg?style=flat-square" alt="License">
    <img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20?style=flat-square&logo=laravel" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php" alt="PHP">
</p>

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x
- Composer 2.x

## Installation

```bash
composer require ronu/laravel-openapi-generator
```

Publish configuration (recommended):

```bash
php artisan vendor:publish --tag=openapi-config
```

Publish templates (optional):

```bash
php artisan vendor:publish --tag=openapi-templates
```

## Quickstart

Generate OpenAPI JSON:

```bash
php artisan openapi:generate
```

Generate all formats:

```bash
php artisan openapi:generate --all
```

Fetch via HTTP (when routes are enabled):

```bash
curl http://localhost:8000/documentation/openapi.json
curl http://localhost:8000/documentation/postman
curl http://localhost:8000/documentation/insomnia
```

## Minimum viable configuration

The package works out-of-the-box, but most teams configure API types and routes in `config/openapi.php`:

```php
return [
    'api_types' => [
        'admin' => [
            'prefix' => 'admin',
            'folder_name' => 'API Admin',
            'enabled' => true,
        ],
        'mobile' => [
            'prefix' => 'mobile',
            'folder_name' => 'API Mobile',
            'enabled' => true,
        ],
    ],
    'routes' => [
        'enabled' => true,
        'prefix' => 'documentation',
        'middleware' => [],
    ],
];
```

## Common scenarios

### 1) Filter docs by API type

```bash
php artisan openapi:generate --all --api-type=admin --api-type=mobile
```

### 2) Expose documentation via HTTP

```bash
curl "http://localhost:8000/documentation/openapi.json?api_type=admin,mobile"
```

### 3) Add custom endpoint docs

```php
// config/openapi-docs.php
'custom_endpoints' => [
    'api-apps.rotate' => [
        'summary' => 'Rotate API Key',
        'description' => 'Generates a new API key for the application.',
        'request_fields' => [
            'reason' => 'Optional reason for key rotation',
        ],
    ],
],
```

## Edge scenarios (short list)

- Config caching + runtime `env()` usage can keep old values; prefer config overrides and clear cache.
- Concurrent generation can write to the same output files; use `--output` for unique paths.
- Large route sets may time out on HTTP endpoints; generate via CLI and serve files from storage.

See [Edge and extreme scenarios](documentation/03-usage/03-edge-and-extreme-scenarios.md) for details.

## API reference

- [Public API](documentation/04-reference/00-public-api.md)
- [Artisan command reference](documentation/04-reference/01-artisan-commands.md)

## Troubleshooting (quick)

- **Invalid api_type**: Check `openapi.api_types` and ensure the key is enabled.
- **No routes found**: Review `openapi.exclude_routes` and API type filters.
- **Placeholders not updating**: Clear config cache or set config values explicitly.

## Full documentation

Start here: **[documentation/index.md](documentation/index.md)**

## Contributing & security

- Contributing guide: open a PR with clear scope and tests when possible.
- Security: please report vulnerabilities privately to the maintainers.

## License

MIT. See [LICENSE](LICENSE).
