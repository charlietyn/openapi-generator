# Troubleshooting

## `Invalid api_type parameter`

**Cause**: `api_type` not present or disabled in `openapi.api_types`.

**Fix**:
- Update `config/openapi.php` to include/enable the API type.

## No routes found

**Cause**: Route exclusions or API type filters removed all routes.

**Fix**:
- Review `openapi.exclude_routes`.
- Remove `--api-type` filters or enable those API types.

## HTTP 500 when calling `/documentation/openapi.json`

**Cause**: Template JSON errors or underlying exceptions.

**Fix**:
- Validate JSON templates in `resources/openapi/templates/`.
- Run generation via CLI to see full error output.

## Placeholder values not updating

**Cause**: `config:cache` + runtime `env()` usage.

**Fix**:
- Run `php artisan config:clear`.
- Prefer overriding config values over changing env at runtime.

**Next:** [FAQ](03-faq.md) • [Docs index](../index.md)
