# Edge and extreme scenarios

## 1) Config caching with runtime `env()` usage

**Symptom**: Changing `.env` values (e.g., `APP_NAME`) does not affect generated output.

**Cause**: Some runtime code reads `env()` directly instead of relying solely on config; `config:cache` freezes those values.

**Mitigation**:
- Prefer overriding `openapi.info`, `openapi.environments`, or template settings in config files.
- Clear config cache when changing env values: `php artisan config:clear`.

**How to reproduce**:
1. Run `php artisan config:cache`.
2. Change `APP_NAME` in `.env`.
3. Generate docs and observe unchanged placeholders.

**How to test**:
- Compare output before and after `config:clear`.

---

## 2) Concurrent generation writes to the same output file

**Symptom**: Partially written or inconsistent JSON/YAML files under `storage/app/public/openapi`.

**Cause**: Multiple workers or developers running `openapi:generate` simultaneously can write to the same output filenames.

**Mitigation**:
- Use `--output` to write to a unique file name per job.
- Run generation in a single queue worker or lock the command.

**How to reproduce**:
1. Run two `openapi:generate --all` commands in parallel.
2. Inspect output files for partial content.

**How to test**:
- Add a file lock around the generation command in your app and confirm stable output.

---

## 3) Long-running queue workers and stale caches

**Symptom**: Old or stale docs when running generation from a long-lived worker.

**Cause**: The generator caches output when `openapi.cache.enabled` is true.

**Mitigation**:
- Run with `--no-cache` for queue jobs that should always regenerate.
- Reduce `openapi.cache.ttl` for fast-changing APIs.

**How to reproduce**:
1. Set `openapi.cache.enabled` to true and `ttl` to a high value.
2. Regenerate after adding new routes.

**How to test**:
- Compare outputs with `--no-cache` vs cached runs.

---

## 4) Large route sets causing timeouts

**Symptom**: HTTP requests to `/documentation/openapi.json` time out or return 500.

**Cause**: Large numbers of routes or heavy FormRequest/model introspection.

**Mitigation**:
- Use the CLI for heavy generation and serve the files from storage.
- Increase PHP execution time for the HTTP endpoint.
- Use API type filters or route exclusions.

**How to reproduce**:
1. Create hundreds of routes.
2. Hit the HTTP endpoint without filtering.

**How to test**:
- Compare response times with `api_type` filters and `exclude_routes` patterns.

---

## 5) Multi-tenant deployments

**Symptom**: OpenAPI info title, server URLs, and environments do not reflect tenant-specific settings.

**Cause**: Configuration is global and placeholders are resolved from app-level values.

**Mitigation**:
- Generate docs per tenant and store them under tenant-specific output paths.
- Override `openapi.info` and `openapi.servers` per tenant before generating.

**How to reproduce**:
1. Run multi-tenant app with per-tenant `APP_NAME`.
2. Generate docs without per-tenant overrides.

**How to test**:
- Generate docs with per-tenant config overrides and verify `info.title` changes.

---

## 6) Rate limiting or auth middleware blocking HTTP docs

**Symptom**: 401/403/429 responses on documentation routes.

**Cause**: The `openapi.routes.middleware` stack includes auth or throttling.

**Mitigation**:
- Use a dedicated middleware stack for docs.
- Serve documentation from static files instead of HTTP generation.

**How to reproduce**:
1. Add `auth:sanctum` or `throttle` middleware to `openapi.routes.middleware`.
2. Request `/documentation/openapi.json` without credentials.

**How to test**:
- Verify the response code changes when middleware is removed.

---

## 7) Invalid API type filters

**Symptom**: 422 response or CLI error stating API type is invalid.

**Cause**: Requested API type is missing or disabled in `openapi.api_types`.

**Mitigation**:
- Add the API type in config or enable it.

**How to reproduce**:
1. Request `?api_type=unknown` via HTTP.
2. Observe 422 validation error.

**How to test**:
- Add the API type to config and ensure the request succeeds.

---

## 8) Template JSON parsing failures

**Symptom**: Generation fails with JSON template errors.

**Cause**: Invalid JSON in `resources/openapi/templates` or mismatched placeholder variables.

**Mitigation**:
- Validate template JSON and enable `openapi-templates.rendering.validate_output` when debugging.

**How to reproduce**:
1. Introduce invalid JSON in a template file.
2. Run generation and observe errors.

**How to test**:
- Fix JSON and regenerate; confirm output succeeds.

**Next:** [Public API](../04-reference/00-public-api.md) • [Docs index](../index.md)

## Evidence
- File: src/Helpers/PlaceholderHelper.php
  - Symbol: PlaceholderHelper::getProjectName()
  - Notes: Uses `env()` at runtime; relevant to config caching.
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::buildBaseEnvironmentData()
  - Notes: Reads `APP_URL` via `env()` in runtime generation.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate(), OpenApiServices::buildCacheKey()
  - Notes: Caching and file generation behavior.
- File: src/routes/web.php
  - Symbol: Route::prefix(), Route::middleware()
  - Notes: HTTP route middleware and docs endpoints.
- File: config/openapi.php
  - Symbol: return array (cache, api_types, routes, exclude_routes)
  - Notes: Configuration impacting filtering and caching.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Symbol: ValidJSONTemplateProcessor::loadTemplate()
  - Notes: Throws exceptions on invalid JSON templates.
