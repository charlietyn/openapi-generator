# Scenarios

## Scenario 1: Generate admin + mobile docs from CLI

**Goal**: Produce OpenAPI + Postman + Insomnia outputs only for `admin` and `mobile` APIs.

**Setup**:
- Define `admin` and `mobile` under `openapi.api_types`.
- Ensure output directory is writable (default: `storage/app/public/openapi`).

**Steps**:
1. Publish configuration: `php artisan vendor:publish --tag=openapi-config`.
2. Run the generator with `--all` and `--api-type` filters.

**Example code**:

```bash
php artisan openapi:generate --all --api-type=admin --api-type=mobile
```

**Notes**:
- Filenames include the filtered API type suffix.
- The generator validates that API types are enabled.

**Common mistakes**:
- Using an API type that is disabled or missing in `openapi.api_types`.
- Forgetting to publish config (so defaults may not match your app).

---

## Scenario 2: Serve docs over HTTP for internal tooling

**Goal**: Allow internal consumers to fetch documentation via routes.

**Setup**:
- Enable routes in `openapi.routes.enabled`.
- Set `openapi.routes.prefix` and middleware as needed.

**Steps**:
1. Configure `openapi.routes` in `config/openapi.php`.
2. Call the OpenAPI route with optional filters.

**Example code**:

```bash
curl "http://localhost:8000/documentation/openapi.json?api_type=admin,mobile"
```

**Notes**:
- HTTP endpoints proxy the same generation logic as the CLI.

**Common mistakes**:
- Forgetting to allow the middleware stack (403/401 responses).
- Using a `format` outside `json|yaml|yml|postman|insomnia`.

---

## Scenario 3: Custom endpoint documentation

**Goal**: Document a non-CRUD endpoint like `api-apps.rotate`.

**Setup**:
- Add custom endpoint configuration in `config/openapi-docs.php`.

**Steps**:
1. Add a custom endpoint definition.
2. Regenerate documentation.

**Example code**:

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

**Notes**:
- Use `entity.action` keys to map the endpoint.

**Common mistakes**:
- Using a key that doesn't match route naming conventions.

---

## Scenario 4: Template-driven CRUD documentation

**Goal**: Standardize list/show/create/update/delete descriptions using JSON templates.

**Setup**:
- Enable the template system (`openapi-templates.enabled`).
- Publish templates to `resources/openapi/templates/`.

**Steps**:
1. Publish templates with `--tag=openapi-templates`.
2. Adjust `openapi-templates.generic_templates` as needed.

**Example code**:

```php
// config/openapi-templates.php
'generic_templates' => [
    'list' => 'list.json',
    'show' => 'show.json',
    'create' => 'create.json',
    'update' => 'update.json',
    'delete' => 'delete.json',
],
```

**Notes**:
- The generator falls back to metadata extraction when templates are disabled.

**Common mistakes**:
- JSON templates not found in expected `generic`/`custom` paths.

---

## Scenario 5: Generate Postman environments

**Goal**: Export Postman environments to use in API testing.

**Setup**:
- Configure `openapi.environments` (base + sub-environments).

**Steps**:
1. Define `artisan`, `local`, and `production` environments.
2. Generate the Postman collection (CLI or HTTP).

**Example code**:

```bash
php artisan openapi:generate --with-postman --environment=production
```

**Notes**:
- The environment generator merges base tracking variables into sub-environments.

**Common mistakes**:
- Defining tracking variables in non-base environments.

**Next:** [Edge and extreme scenarios](03-edge-and-extreme-scenarios.md) • [Docs index](../index.md)

## Evidence
- File: src/Commands/GenerateOpenApiSpec.php
  - Symbol: GenerateOpenApiSpec::$signature, GenerateOpenApiSpec::handle()
  - Notes: CLI filters by API type, environment, and `--all` mode.
- File: src/routes/web.php
  - Symbol: Route::prefix(), Route::get('openapi.{format}')
  - Notes: HTTP routes and format restrictions.
- File: config/openapi.php
  - Symbol: return array (api_types, routes, environments)
  - Notes: API types, route settings, and environment definitions.
- File: config/openapi-docs.php
  - Symbol: return array (custom_endpoints)
  - Notes: Custom endpoint documentation mapping.
- File: config/openapi-templates.php
  - Symbol: return array (generic_templates)
  - Notes: Template mappings for CRUD actions.
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::mergeVariables()
  - Notes: Base + tracking variables merged into environments.
