# FAQ

## Does this package modify my routes?
No. It only inspects routes to generate documentation.

## Can I disable HTTP endpoints?
Yes. Set `openapi.routes.enabled` to `false`.

## Can I customize output structure?
Yes. Use `config/openapi-docs.php` for custom endpoint metadata and `resources/openapi/templates` for JSON templates.

## Does it support multiple API types?
Yes. Configure `openapi.api_types` and filter with `--api-type` or `api_type` query parameters.

**Back to:** [Docs index](../index.md)
