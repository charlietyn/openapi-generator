# Laravel OpenAPI Generator - Complete Documentation

## Advanced Guide for Laravel Developers

**Version**: 1.2.5
**Compatibility**: Laravel 10.x, 11.x, 12.x
**PHP**: 8.1+

---

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Artisan Commands](#artisan-commands)
5. [HTTP Endpoints](#http-endpoints)
6. [Template System](#template-system)
7. [Metadata Extraction](#metadata-extraction)
8. [Test Generation](#test-generation)
9. [Programmatic Usage](#programmatic-usage)
10. [Modular Architecture](#modular-architecture)
11. [Advanced Use Cases](#advanced-use-cases)
12. [Troubleshooting](#troubleshooting)

---

## Introduction

Laravel OpenAPI Generator is a library that automates API documentation generation from your existing Laravel code. It generates specifications in:

- **OpenAPI 3.0.3** (JSON and YAML)
- **Postman Collection v2.1**
- **Insomnia Workspace v4**

### Key Features

- Automatic extraction of validation rules from FormRequests
- Intelligent detection of Eloquent relationships
- Support for multiple API types (api, site, mobile, admin)
- Customizable template system
- Automatic test generation
- Configurable caching for performance optimization
- Native support for modular architectures (Nwidart)

---

## Installation

### Step 1: Install via Composer

```bash
composer require ronu/laravel-openapi-generator
```

### Step 2: Publish Configurations

```bash
# Publish all configurations
php artisan vendor:publish --tag=openapi-config

# This publishes:
# - config/openapi.php
# - config/openapi-docs.php
# - config/openapi-tests.php
# - config/openapi-templates.php
```

### Step 3: Publish Templates (Optional)

```bash
php artisan vendor:publish --tag=openapi-templates

# This creates:
# - resources/openapi/templates/generic/
# - resources/openapi/templates/custom/
```

### Step 4: Verify Installation

```bash
php artisan openapi:generate --help
```

---

## Configuration

### Main File: `config/openapi.php`

#### API Information

```php
'info' => [
    'title' => env('APP_NAME', 'My Laravel API'),
    'description' => 'Complete RESTful API for my application',
    'version' => '1.0.0',
    'contact' => [
        'name' => 'Development Team',
        'email' => 'dev@mycompany.com',
        'url' => 'https://mycompany.com/support',
    ],
    'license' => [
        'name' => 'MIT',
        'url' => 'https://opensource.org/licenses/MIT',
    ],
],
```

#### Server Configuration

```php
'servers' => [
    [
        'url' => 'http://127.0.0.1:8000',
        'description' => 'Development Server (Artisan)',
    ],
    [
        'url' => 'https://localhost/${{projectName}}/public',
        'description' => 'Local Server (XAMPP/WAMP)',
    ],
    [
        'url' => 'https://api.${{projectName}}.com',
        'description' => 'Production Server',
    ],
],
```

> **Note**: `${{projectName}}` is automatically replaced with the project name.

#### API Types

```php
'api_types' => [
    'api' => [
        'prefix' => 'api',
        'folder_name' => 'General API',
        'enabled' => true,
    ],
    'admin' => [
        'prefix' => 'admin',
        'folder_name' => 'Admin API',
        'enabled' => true,
    ],
    'site' => [
        'prefix' => 'site',
        'folder_name' => 'Frontend API',
        'enabled' => true,
    ],
    'mobile' => [
        'prefix' => 'mobile',
        'folder_name' => 'Mobile API',
        'enabled' => true,
    ],
],
```

#### Security Schemes

```php
'security' => [
    'BearerAuth' => [
        'type' => 'http',
        'scheme' => 'bearer',
        'bearerFormat' => 'JWT',
        'description' => 'JWT token authentication',
    ],
    'ApiKeyAuth' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-API-Key',
        'description' => 'API Key authentication',
    ],
    'BasicAuth' => [
        'type' => 'http',
        'scheme' => 'basic',
        'description' => 'HTTP Basic authentication',
    ],
],
```

#### Middleware to Security Mapping

```php
'middleware_security_map' => [
    'auth:sanctum' => ['BearerAuth'],
    'auth:api' => ['BearerAuth'],
    'auth' => ['BearerAuth'],
    'api.key' => ['ApiKeyAuth'],
    'basic.auth' => ['BasicAuth'],
],
```

#### Cache Configuration

```php
'cache' => [
    'enabled' => true,           // Enable/disable cache
    'ttl' => 3600,               // Time to live in seconds (1 hour)
    'key_prefix' => 'openapi_',  // Prefix for cache keys
],
```

#### Route Exclusion

```php
'exclude_routes' => [
    'api/documentation/*',    // Documentation routes
    'sanctum/*',              // Sanctum routes
    '*/create',               // Create forms
    '*/{id}/edit',            // Edit forms
    '_ignition/*',            // Debugger
    '_debugbar/*',            // Debug bar
    'horizon/*',              // Laravel Horizon
    'telescope/*',            // Laravel Telescope
],
```

#### Output Path

```php
'output_path' => storage_path('app/public/openapi'),
// Files will be generated in: storage/app/public/openapi/
```

---

### Documentation File: `config/openapi-docs.php`

#### CRUD Templates

```php
'crud_templates' => [
    'index' => [
        'summary' => 'List __VAR:entity_plural__',
        'description' => 'Gets a paginated list of __VAR:entity_plural__',
        'tags' => ['__VAR:module__'],
    ],
    'show' => [
        'summary' => 'Get __VAR:entity_singular__',
        'description' => 'Gets the details of a __VAR:entity_singular__',
    ],
    'store' => [
        'summary' => 'Create __VAR:entity_singular__',
        'description' => 'Creates a new __VAR:entity_singular__',
    ],
    'update' => [
        'summary' => 'Update __VAR:entity_singular__',
        'description' => 'Updates an existing __VAR:entity_singular__',
    ],
    'destroy' => [
        'summary' => 'Delete __VAR:entity_singular__',
        'description' => 'Permanently deletes a __VAR:entity_singular__',
    ],
],
```

#### Entity Metadata

```php
'entity_metadata' => [
    'User' => [
        'singular' => 'User',
        'plural' => 'Users',
        'module' => 'Security',
        'description' => 'System user management',
    ],
    'Role' => [
        'singular' => 'Role',
        'plural' => 'Roles',
        'module' => 'Security',
        'description' => 'Role and permission management',
    ],
    'Product' => [
        'singular' => 'Product',
        'plural' => 'Products',
        'module' => 'Catalog',
        'description' => 'Product catalog management',
    ],
],
```

#### Custom Endpoints

```php
'custom_endpoints' => [
    'api/auth/login' => [
        'post' => [
            'summary' => 'Login',
            'description' => 'Authenticates a user and returns a JWT token',
            'tags' => ['Authentication'],
            'security' => [],  // No authentication required
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['email', 'password'],
                            'properties' => [
                                'email' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                    'example' => 'user@example.com',
                                ],
                                'password' => [
                                    'type' => 'string',
                                    'format' => 'password',
                                    'example' => 'MyPassword123!',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Successful login',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'token' => ['type' => 'string'],
                                    'user' => ['$ref' => '#/components/schemas/User'],
                                ],
                            ],
                        ],
                    ],
                ],
                '401' => [
                    'description' => 'Invalid credentials',
                ],
            ],
        ],
    ],
],
```

---

### Tests File: `config/openapi-tests.php`

```php
'test_templates' => [
    // Common Postman tests
    'postman' => [
        'status_200' => 'pm.test("Status 200", function() { pm.response.to.have.status(200); });',
        'status_201' => 'pm.test("Status 201", function() { pm.response.to.have.status(201); });',
        'json_response' => 'pm.test("JSON Response", function() { pm.response.to.be.json; });',
        'has_data' => 'pm.test("Has Data", function() { pm.expect(pm.response.json()).to.have.property("data"); });',
        'save_id' => 'var json = pm.response.json(); if(json.data && json.data.id) { pm.collectionVariables.set("last_{{entity}}_id", json.data.id); }',
    ],

    // Insomnia tests
    'insomnia' => [
        'status_200' => 'const response = await insomnia.send(); expect(response.status).to.equal(200);',
        'status_201' => 'const response = await insomnia.send(); expect(response.status).to.equal(201);',
    ],
],

// Action to test mapping
'action_tests' => [
    'index' => ['status_200', 'json_response', 'has_data'],
    'show' => ['status_200', 'json_response'],
    'store' => ['status_201', 'json_response', 'save_id'],
    'update' => ['status_200', 'json_response'],
    'destroy' => ['status_200_or_204'],
],
```

---

## Artisan Commands

### Main Command: `openapi:generate`

#### Full Syntax

```bash
php artisan openapi:generate [options]
```

#### Available Options

| Option | Description | Values |
|--------|-------------|--------|
| `--format` | Output format | `json`, `yaml` |
| `--output` | Custom output path | Absolute or relative path |
| `--no-cache` | Disable cache | Flag |
| `--api-type` | Filter by API type | `api`, `site`, `mobile`, `admin` |
| `--all` | Generate all formats | Flag |
| `--with-postman` | Include Postman collection | Flag |
| `--with-insomnia` | Include Insomnia workspace | Flag |
| `--environment` | Environment setting | `artisan`, `local`, `production` |

### Usage Examples

#### Complete Generation

```bash
# Generate ALL formats for ALL API types
php artisan openapi:generate --all

# Output:
# - openapi-all.json
# - openapi-all.yaml
# - postman-all.json
# - insomnia-all.json
# - postman-env-artisan.json
# - postman-env-local.json
# - postman-env-production.json
```

#### Filter by API Type

```bash
# General API only
php artisan openapi:generate --all --api-type=api

# Mobile API only
php artisan openapi:generate --all --api-type=mobile

# Multiple types
php artisan openapi:generate --all --api-type=api --api-type=mobile --api-type=admin
```

#### Specific Format

```bash
# OpenAPI JSON only
php artisan openapi:generate --format=json

# OpenAPI YAML only
php artisan openapi:generate --format=yaml

# OpenAPI + Postman
php artisan openapi:generate --format=json --with-postman

# OpenAPI + Insomnia
php artisan openapi:generate --format=json --with-insomnia
```

#### Custom Output Path

```bash
# Output to custom directory
php artisan openapi:generate --output=/var/www/docs/api-spec.json

# Project-relative output
php artisan openapi:generate --output=./public/docs/openapi.json
```

#### Specific Environment

```bash
# Generate for production environment
php artisan openapi:generate --all --environment=production

# Generate for local environment
php artisan openapi:generate --all --environment=local
```

#### Without Cache

```bash
# Force regeneration without using cache
php artisan openapi:generate --all --no-cache
```

### Combined Examples

```bash
# Generate OpenAPI YAML only for mobile API without cache
php artisan openapi:generate --format=yaml --api-type=mobile --no-cache

# Generate everything for production excluding admin
php artisan openapi:generate --all --api-type=api --api-type=site --api-type=mobile --environment=production

# Generate JSON with Postman for development
php artisan openapi:generate --format=json --with-postman --environment=artisan
```

---

## HTTP Endpoints

The library exposes HTTP endpoints for dynamic documentation access.

### Route Configuration

```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['web'],  // or ['api', 'auth:sanctum'] for protection
],
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/documentation/openapi.json` | OpenAPI in JSON |
| GET | `/documentation/openapi.yaml` | OpenAPI in YAML |
| GET | `/documentation/postman` | Postman Collection |
| GET | `/documentation/insomnia` | Insomnia Workspace |
| GET | `/documentation/environments/{format}` | Environment configuration |
| POST | `/documentation/clear-cache` | Clear cache |
| GET | `/documentation/info` | API information |

### Query Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `api_type` | Filter by API type(s) | `?api_type=api,mobile` |
| `environment` | Select environment | `?environment=production` |

### cURL Examples

```bash
# Get OpenAPI JSON
curl -X GET "http://localhost:8000/documentation/openapi.json"

# Get OpenAPI YAML
curl -X GET "http://localhost:8000/documentation/openapi.yaml"

# Filter by API type
curl -X GET "http://localhost:8000/documentation/openapi.json?api_type=api"

# Multiple API types
curl -X GET "http://localhost:8000/documentation/openapi.json?api_type=api,mobile,admin"

# Get Postman collection for production
curl -X GET "http://localhost:8000/documentation/postman?environment=production"

# Get Insomnia workspace
curl -X GET "http://localhost:8000/documentation/insomnia?api_type=mobile"

# Clear cache
curl -X POST "http://localhost:8000/documentation/clear-cache"

# Get Postman environments
curl -X GET "http://localhost:8000/documentation/environments/postman"
```

### Axios Examples (JavaScript)

```javascript
// Get OpenAPI specification
const response = await axios.get('/documentation/openapi.json', {
    params: {
        api_type: 'api,mobile',
        environment: 'production'
    }
});

// Download Postman collection
const postman = await axios.get('/documentation/postman');
const blob = new Blob([JSON.stringify(postman.data)], { type: 'application/json' });
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'postman-collection.json';
a.click();
```

### Guzzle Examples (PHP)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:8000']);

// Get OpenAPI spec
$response = $client->get('/documentation/openapi.json', [
    'query' => [
        'api_type' => 'api,mobile',
        'environment' => 'production'
    ]
]);

$spec = json_decode($response->getBody(), true);

// Save Postman collection
$postman = $client->get('/documentation/postman');
file_put_contents('postman-collection.json', $postman->getBody());
```

---

## Template System

### Directory Structure

```
resources/openapi/templates/
├── generic/                    # Generic CRUD templates
│   ├── list.json              # Paginated listing
│   ├── show.json              # Resource detail
│   ├── create.json            # Creation
│   ├── update.json            # Update
│   ├── delete.json            # Deletion
│   ├── validate.json          # Validation
│   ├── bulk_update.json       # Bulk update
│   └── bulk_delete.json       # Bulk deletion
└── custom/                     # Custom templates
    ├── auth.login.json
    ├── auth.register.json
    ├── auth.logout.json
    ├── auth.refresh.json
    ├── auth.user-profile.json
    └── auth.permissions.json
```

### Template Variables

Templates use `__VAR:name__` syntax for variable interpolation:

| Variable | Description | Example |
|----------|-------------|---------|
| `__VAR:entity_plural__` | Plural name | "Users", "Products" |
| `__VAR:entity_singular__` | Singular name | "User", "Product" |
| `__VAR:entity_url__` | URL slug | "users", "products" |
| `__VAR:module__` | Module name | "Security", "Catalog" |
| `__VAR:fields_description__` | Field descriptions | "name: User name..." |
| `__VAR:relations_description__` | Relation descriptions | "roles: Assigned roles..." |
| `__VAR:fields_list__` | JSON field list | `["id", "name", "email"]` |
| `__VAR:relations_list__` | JSON relation list | `["roles", "permissions"]` |
| `__VAR:model_schema__` | OpenAPI model schema | `{type: "object", ...}` |
| `__VAR:request_schema__` | Request body schema | `{type: "object", ...}` |
| `__VAR:request_example__` | Request example | `{"name": "John", ...}` |
| `__VAR:response_example__` | Response example | `{"data": {...}}` |
| `__VAR:validation_description__` | Validation rules | "required, string, max:255" |
| `__VAR:validation_errors_example__` | Validation errors | `{"name": ["required"]}` |
| `__VAR:example_id__` | Example ID | "1" or "uuid-here" |

### Template Example: `list.json`

```json
{
    "summary": "List __VAR:entity_plural__",
    "description": "Gets a paginated list of __VAR:entity_plural__.\n\n__VAR:fields_description__\n\n__VAR:relations_description__",
    "operationId": "list__VAR:entity_plural__",
    "tags": ["__VAR:module__"],
    "parameters": [
        {
            "name": "page",
            "in": "query",
            "description": "Page number",
            "schema": { "type": "integer", "default": 1 }
        },
        {
            "name": "per_page",
            "in": "query",
            "description": "Results per page",
            "schema": { "type": "integer", "default": 15 }
        },
        {
            "name": "search",
            "in": "query",
            "description": "Search term",
            "schema": { "type": "string" }
        },
        {
            "name": "sort_by",
            "in": "query",
            "description": "Sort field",
            "schema": { "type": "string" }
        },
        {
            "name": "sort_order",
            "in": "query",
            "description": "Sort direction",
            "schema": { "type": "string", "enum": ["asc", "desc"] }
        },
        {
            "name": "with",
            "in": "query",
            "description": "Relations to include: __VAR:relations_list__",
            "schema": { "type": "string" }
        }
    ],
    "responses": {
        "200": {
            "description": "List of __VAR:entity_plural__ retrieved successfully",
            "content": {
                "application/json": {
                    "schema": {
                        "type": "object",
                        "properties": {
                            "data": {
                                "type": "array",
                                "items": __VAR:model_schema__
                            },
                            "meta": {
                                "type": "object",
                                "properties": {
                                    "current_page": { "type": "integer" },
                                    "last_page": { "type": "integer" },
                                    "per_page": { "type": "integer" },
                                    "total": { "type": "integer" }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

### Creating Custom Templates

#### Step 1: Create the template file

```bash
# Create template for advanced search endpoint
touch resources/openapi/templates/custom/search.advanced.json
```

#### Step 2: Define the template

```json
{
    "summary": "Advanced search for __VAR:entity_plural__",
    "description": "Performs an advanced search with multiple filters.\n\nAvailable fields:\n__VAR:fields_description__",
    "operationId": "advancedSearch__VAR:entity_plural__",
    "tags": ["__VAR:module__", "Search"],
    "requestBody": {
        "required": true,
        "content": {
            "application/json": {
                "schema": {
                    "type": "object",
                    "properties": {
                        "filters": {
                            "type": "array",
                            "items": {
                                "type": "object",
                                "properties": {
                                    "field": { "type": "string" },
                                    "operator": {
                                        "type": "string",
                                        "enum": ["=", "!=", ">", "<", ">=", "<=", "like", "in"]
                                    },
                                    "value": { "type": "string" }
                                }
                            }
                        },
                        "sort": {
                            "type": "object",
                            "properties": {
                                "field": { "type": "string" },
                                "direction": { "type": "string", "enum": ["asc", "desc"] }
                            }
                        }
                    }
                },
                "example": {
                    "filters": [
                        { "field": "status", "operator": "=", "value": "active" },
                        { "field": "created_at", "operator": ">=", "value": "2024-01-01" }
                    ],
                    "sort": { "field": "name", "direction": "asc" }
                }
            }
        }
    },
    "responses": {
        "200": {
            "description": "Search results",
            "content": {
                "application/json": {
                    "schema": {
                        "type": "object",
                        "properties": {
                            "data": {
                                "type": "array",
                                "items": __VAR:model_schema__
                            },
                            "meta": {
                                "type": "object",
                                "properties": {
                                    "total_results": { "type": "integer" },
                                    "query_time_ms": { "type": "number" }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

#### Step 3: Register in configuration

```php
// config/openapi-docs.php
'custom_templates' => [
    'search.advanced' => [
        'methods' => ['POST'],
        'pattern' => '*/search/advanced',
    ],
],
```

---

## Metadata Extraction

### How It Works

The library uses a 4-strategy cascade system to extract validation rules from FormRequests:

1. **Normal instantiation**: Attempts to create the instance directly
2. **Mock dependency injection**: Resolves dependencies with mocks
3. **Reflection without constructor**: Uses ReflectionClass to bypass constructor
4. **File parsing**: Analyzes the source code directly

### Supported Validation Rules

```php
// Example FormRequest
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],

            // Optional fields
            'phone' => 'nullable|string|max:20',
            'age' => 'nullable|integer|min:18|max:120',

            // Arrays and objects
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'address' => 'required|array',
            'address.street' => 'required|string',
            'address.city' => 'required|string',
            'address.zip' => 'required|string|regex:/^\d{5}$/',

            // Files
            'avatar' => 'nullable|image|mimes:jpg,png|max:2048',
            'documents' => 'array',
            'documents.*' => 'file|mimes:pdf|max:10240',

            // Relations
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',

            // Conditionals
            'company_name' => 'required_if:type,business|string|max:255',
            'tax_id' => 'required_with:company_name|string|max:20',

            // Complex rules with Rule
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($this->user?->id),
            ],

            // Enums
            'status' => 'required|in:active,inactive,pending',
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ];
    }
}
```

### Generated OpenAPI Types

| Laravel Rule | OpenAPI Type | Format |
|--------------|--------------|--------|
| `string` | `string` | - |
| `integer` | `integer` | - |
| `numeric` | `number` | - |
| `boolean` | `boolean` | - |
| `array` | `array` | - |
| `email` | `string` | `email` |
| `url` | `string` | `uri` |
| `date` | `string` | `date` |
| `date_format:Y-m-d H:i:s` | `string` | `date-time` |
| `uuid` | `string` | `uuid` |
| `ip` | `string` | `ipv4` |
| `json` | `object` | - |
| `image`, `file` | `string` | `binary` |

### Model Extraction

```php
// Example model
class User extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    // Relations automatically detected
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

The generator automatically detects:
- `fillable` and `hidden` fields
- Data types from `$casts`
- Relations (`hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, etc.)

---

## Test Generation

### Postman Tests

```javascript
// Automatically generated test for "store" endpoint
pm.test("Status code is 201", function() {
    pm.response.to.have.status(201);
});

pm.test("Response is JSON", function() {
    pm.response.to.be.json;
});

pm.test("Response has data object", function() {
    pm.expect(pm.response.json()).to.have.property("data");
});

pm.test("Save ID to collection variables", function() {
    var json = pm.response.json();
    if (json.data && json.data.id) {
        pm.collectionVariables.set("last_user_id", json.data.id);
    }
});
```

### Insomnia Tests

```javascript
// Generated test for Insomnia
const response = await insomnia.send();

expect(response.status).to.equal(201);
expect(response.data).to.have.property('data');

// Save ID for next requests
if (response.data.data && response.data.data.id) {
    insomnia.environment.set('last_user_id', response.data.data.id);
}
```

### Customizing Tests

```php
// config/openapi-tests.php
'custom_tests' => [
    'api/users' => [
        'store' => [
            'postman' => [
                'pm.test("Email format is valid", function() {
                    var email = pm.response.json().data.email;
                    pm.expect(email).to.match(/^[^@]+@[^@]+\.[^@]+$/);
                });',
            ],
        ],
    ],
],
```

---

## Programmatic Usage

### Facade

```php
use Ronu\OpenApiGenerator\Facades\OpenApiGenerator;

// Generate OpenAPI specification
$spec = OpenApiGenerator::generateOpenApi();

// With options
$spec = OpenApiGenerator::generateOpenApi(
    useCache: false,
    apiTypes: ['api', 'mobile'],
    environment: 'production'
);

// Generate Postman collection
$postman = OpenApiGenerator::generatePostman(
    apiTypes: ['api'],
    environment: 'local'
);

// Generate Insomnia workspace
$insomnia = OpenApiGenerator::generateInsomnia();
```

### Direct Service

```php
use Ronu\OpenApiGenerator\Services\OpenApiServices;

class DocumentationController extends Controller
{
    public function __construct(
        private OpenApiServices $openApiService
    ) {}

    public function export(Request $request)
    {
        $format = $request->input('format', 'json');
        $apiTypes = $request->input('api_types', []);

        $spec = $this->openApiService->generateOpenApi(
            useCache: true,
            apiTypes: $apiTypes
        );

        if ($format === 'yaml') {
            return response($spec->toYaml())
                ->header('Content-Type', 'application/x-yaml');
        }

        return response()->json($spec->toArray());
    }
}
```

### Events and Hooks

```php
// AppServiceProvider.php
use Ronu\OpenApiGenerator\Events\OpenApiGenerated;
use Ronu\OpenApiGenerator\Events\PostmanGenerated;

public function boot()
{
    Event::listen(OpenApiGenerated::class, function ($event) {
        // Send notification, update CDN, etc.
        Log::info('OpenAPI spec generated', [
            'api_types' => $event->apiTypes,
            'path' => $event->outputPath,
        ]);
    });

    Event::listen(PostmanGenerated::class, function ($event) {
        // Automatically upload to Postman cloud
        $this->uploadToPostmanCloud($event->collection);
    });
}
```

---

## Modular Architecture

### Nwidart Modules Support

The library automatically detects modular structure:

```
app/
├── Modules/
│   ├── User/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       └── UserController.php
│   │   │   └── Requests/
│   │   │       ├── StoreUserRequest.php
│   │   │       └── UpdateUserRequest.php
│   │   └── Entities/
│   │       └── User.php
│   ├── Product/
│   │   └── ...
│   └── Order/
│       └── ...
```

### Detected Route Structure

```
api_type / module / entity / {id} / action
   |         |        |       |       |
  api     Security   users    1    permissions
```

### Module Configuration

```php
// config/openapi.php
'modules' => [
    'enabled' => true,
    'namespace' => 'Modules',
    'path' => base_path('Modules'),
],

// Module to readable name mapping
'module_names' => [
    'User' => 'User Management',
    'Product' => 'Product Catalog',
    'Order' => 'Order Management',
],
```

---

## Advanced Use Cases

### CI/CD Integration

```yaml
# .github/workflows/docs.yml
name: Generate API Documentation

on:
  push:
    branches: [main, develop]
    paths:
      - 'app/**'
      - 'routes/**'
      - 'Modules/**'

jobs:
  generate-docs:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install --no-dev

      - name: Generate OpenAPI Spec
        run: php artisan openapi:generate --all --no-cache

      - name: Upload to Artifacts
        uses: actions/upload-artifact@v3
        with:
          name: api-docs
          path: storage/app/public/openapi/

      - name: Deploy to SwaggerHub
        run: |
          curl -X POST "https://api.swaggerhub.com/apis/myorg/myapi" \
            -H "Authorization: Bearer ${{ secrets.SWAGGERHUB_KEY }}" \
            -H "Content-Type: application/json" \
            -d @storage/app/public/openapi/openapi-all.json
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Regenerate documentation if routes or controllers changed
if git diff --cached --name-only | grep -E "(routes/|Controllers/|Requests/)" > /dev/null; then
    echo "Regenerating API documentation..."
    php artisan openapi:generate --all --no-cache
    git add storage/app/public/openapi/
fi
```

### Specification Validation

```php
// tests/Feature/OpenApiSpecTest.php
use Cebe\OpenApi\Reader;
use Cebe\OpenApi\Spec\OpenApi;

class OpenApiSpecTest extends TestCase
{
    public function test_openapi_spec_is_valid()
    {
        Artisan::call('openapi:generate', ['--format' => 'json']);

        $specPath = storage_path('app/public/openapi/openapi-all.json');

        $this->assertFileExists($specPath);

        // Validate OpenAPI structure
        $spec = Reader::readFromJsonFile($specPath);

        $this->assertInstanceOf(OpenApi::class, $spec);
        $this->assertEquals('3.0.3', $spec->openapi);
        $this->assertNotEmpty($spec->paths);
    }

    public function test_all_routes_are_documented()
    {
        $specPath = storage_path('app/public/openapi/openapi-all.json');
        $spec = json_decode(file_get_contents($specPath), true);

        $documentedPaths = array_keys($spec['paths']);

        // Get application routes
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            if ($this->shouldBeDocumented($route)) {
                $path = '/' . $route->uri();
                $this->assertContains(
                    $this->normalizePathForOpenApi($path),
                    $documentedPaths,
                    "Route {$path} is not documented"
                );
            }
        }
    }
}
```

### Scheduled Generation

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Regenerate documentation every night
    $schedule->command('openapi:generate --all --no-cache')
        ->dailyAt('03:00')
        ->environments(['production'])
        ->onSuccess(function () {
            // Notify the team
            Notification::send(
                User::whereRole('developer')->get(),
                new ApiDocsUpdatedNotification()
            );
        });
}
```

### Multi-tenant Documentation

```php
// Generate documentation per tenant
foreach (Tenant::all() as $tenant) {
    tenancy()->initialize($tenant);

    Artisan::call('openapi:generate', [
        '--all' => true,
        '--output' => storage_path("tenants/{$tenant->id}/openapi/"),
    ]);

    tenancy()->end();
}
```

---

## Troubleshooting

### Problem: FormRequest rules not detected

**Cause**: The FormRequest has complex dependencies in the constructor.

**Solution**: The library uses 4 cascade strategies, but if all fail:

```php
// Add static method for rules
class ComplexRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->getRulesArray();
    }

    // Method the library can call without instantiation
    public static function documentationRules(): array
    {
        return [
            'field' => 'required|string',
            // ...
        ];
    }
}
```

### Problem: Cache not updating

**Solution**:

```bash
# Clear cache manually
php artisan openapi:generate --all --no-cache

# Or via endpoint
curl -X POST http://localhost:8000/documentation/clear-cache
```

### Problem: Routes not appearing in documentation

**Check**:

1. The route is not in `exclude_routes`
2. The API type is enabled in `api_types`
3. The route has the correct prefix (api, site, mobile, admin)

```php
// Verify detected routes
$routes = app(\Ronu\OpenApiGenerator\Services\OpenApiServices::class)
    ->inspectRoutes();

dd($routes);
```

### Problem: Incorrect data types

**Solution**: Explicitly specify types in FormRequest:

```php
// Use PHPDoc to help detection
class StoreRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** @var string */
            'name' => 'required|string',
            /** @var int */
            'age' => 'required|integer',
        ];
    }
}
```

### Problem: Relations not detected

**Solution**: Ensure relations have return types:

```php
// Incorrect - not detected
public function roles()
{
    return $this->belongsToMany(Role::class);
}

// Correct - automatically detected
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

### Problem: Memory error

**Solution**: Increase memory limit or use filters:

```bash
# Increase memory
php -d memory_limit=512M artisan openapi:generate --all

# Or generate by type
php artisan openapi:generate --api-type=api
php artisan openapi:generate --api-type=mobile
```

---

## Additional Resources

- [OpenAPI Specification](https://spec.openapis.org/oas/v3.0.3)
- [Postman Collection Format](https://learning.postman.com/collection-format/getting-started/overview/)
- [Insomnia Documentation](https://docs.insomnia.rest/)
- [Laravel Validation Rules](https://laravel.com/docs/validation#available-validation-rules)

---

**License**: MIT
**Author**: Ronu
**Repository**: https://github.com/charlietyn/openapi-generator
