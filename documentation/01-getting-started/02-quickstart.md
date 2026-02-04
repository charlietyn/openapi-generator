# Quickstart

## 1) Publish configuration

```bash
php artisan vendor:publish --tag=openapi-config
```

## 2) Generate OpenAPI (CLI)

```bash
php artisan openapi:generate
```

By default, the output is written under `storage/app/public/openapi` (see `openapi.output_path`).

## 3) Generate all formats

```bash
php artisan openapi:generate --all
```

## 4) Fetch via HTTP routes (optional)

When routes are enabled (`openapi.routes.enabled`), the package exposes:

```bash
curl http://localhost:8000/documentation/openapi.json
curl http://localhost:8000/documentation/openapi.yaml
curl http://localhost:8000/documentation/postman
curl http://localhost:8000/documentation/insomnia
```

**Next:** [Configuration reference](../02-configuration/00-configuration-reference.md) • [Docs index](../index.md)
