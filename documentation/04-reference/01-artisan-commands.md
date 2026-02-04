# Artisan commands

## `openapi:generate`

Generate OpenAPI specifications and optionally Postman and Insomnia artifacts.

```bash
php artisan openapi:generate [options]
```

### Options

| Option | Purpose |
| --- | --- |
| `--format=json` | Output format for OpenAPI (`json`, `yaml`, `yml`). |
| `--output=` | Override output file path. |
| `--no-cache` | Disable cached generation. |
| `--api-type=*` | Filter by API type(s). |
| `--all` | Generate OpenAPI + Postman + Insomnia for all API types. |
| `--with-postman` | Generate Postman collection. |
| `--with-insomnia` | Generate Insomnia workspace. |
| `--environment=artisan` | Select environment for templates (`artisan`, `local`, `production`). |

**Next:** [Middleware](02-middleware.md) • [Docs index](../index.md)

## Evidence
- File: src/Commands/GenerateOpenApiSpec.php
  - Symbol: GenerateOpenApiSpec::$signature
  - Notes: Defines command name and options.
