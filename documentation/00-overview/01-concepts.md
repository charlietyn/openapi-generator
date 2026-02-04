# Concepts

This package is built around a few core concepts:

## Formats
- **OpenAPI**: Canonical specification output for Swagger/Redoc or other tooling.
- **Postman Collection**: Request collection plus generated test scripts.
- **Insomnia Workspace**: Workspace export with environments.

## API types
API types are configurable prefixes (e.g., `admin`, `site`, `mobile`). They allow filtering output for only certain route groups and add a naming hierarchy to outputs.

## Environments
Environment definitions (base + sub-environments) are used to generate Postman/Insomnia environment files and to inject server URLs into specifications.

## Templates & metadata
The generator can use JSON templates (resource-based) and the `config/openapi-docs.php` definitions to document CRUD endpoints and custom endpoints. When templates are missing, the generator falls back to metadata extraction via FormRequests and models.

**Next:** [Requirements](../01-getting-started/00-requirements.md) • [Docs index](../index.md)
