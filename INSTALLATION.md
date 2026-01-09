# Installation Guide

## 1. Install via Composer

```bash
composer require your-vendor/openapi-generator
```

## 2. Publish Configuration

```bash
php artisan vendor:publish --provider="YourVendor\OpenApiGenerator\OpenApiGeneratorServiceProvider"
```

This creates:
- `config/openapi-generator.php`
- `resources/openapi/templates/`

## 3. Configure Your API

Edit `config/openapi-generator.php`:

```php
'info' => [
    'title' => env('APP_NAME', 'My API'),
    'version' => '1.0.0',
],
```

## 4. Generate Documentation

```bash
php artisan openapi:generate
```

## 5. View Generated Files

Check `storage/app/openapi/` for:
- `openapi.json`
- `postman-collection.json`
- `insomnia-workspace.json`

## Next Steps

- [Configuration Guide](configuration.md)
- [Template Customization](templates.md)
- [API Reference](api-reference.md)
