# Publishing assets

The service provider publishes two groups of assets.

## Configuration files

```bash
php artisan vendor:publish --tag=openapi-config
```

Publishes:
- `config/openapi.php`
- `config/openapi-docs.php`
- `config/openapi-tests.php`
- `config/openapi-templates.php`

## Template files

```bash
php artisan vendor:publish --tag=openapi-templates
```

Publishes JSON templates to:

```
resources/openapi/templates/
├─ generic/
└─ custom/
```

**Next:** [Basic usage](../03-usage/00-basic-usage.md) • [Docs index](../index.md)
