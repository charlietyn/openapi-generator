# Installation Guide

Complete step-by-step installation and configuration guide for Laravel OpenAPI Generator.

---

## 📋 Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Publishing Assets](#publishing-assets)
- [Nwidart Modules Setup](#nwidart-modules-setup)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)
- [Upgrading](#upgrading)

---

## ✅ Requirements

### Minimum Requirements

- **PHP:** 8.1 or higher
- **Laravel:** 10.x or 11.x or 12.x
- **Composer:** 2.0 or higher

### Optional (Enhanced Features)

- **Nwidart Modules:** ^10.0 (for modular Laravel apps)
- **Database:** MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.35+

### PHP Extensions

```bash
# Required
php -m | grep -E "json|mbstring|pdo"

# For advanced features
php -m | grep -E "reflection|tokenizer"
```

---

## 🚀 Installation

### Step 1: Install via Composer

```bash
composer require ronu/laravel-openapi-generator
```

**For development only:**
```bash
composer require ronu/laravel-openapi-generator --dev
```

### Step 2: Service Provider Auto-Discovery

Laravel will automatically discover the service provider. If auto-discovery is disabled, manually register:

```php
// config/app.php
'providers' => [
    // Other providers...
    Ronu\OpenApiGenerator\OpenApiGeneratorServiceProvider::class,
],
```

### Step 3: Verify Installation

```bash
php artisan list openapi
```

**Expected output:**
```
Available commands:
  openapi
    openapi:generate    Generate OpenAPI documentation in multiple formats
    openapi:validate    Validate generated OpenAPI specification
    openapi:clear-cache Clear documentation cache
```

---

## ⚙️ Configuration

### Step 1: Publish Configuration Files

```bash
# Publish all configs
php artisan vendor:publish --tag=openapi-config

# Publish specific configs
php artisan vendor:publish --tag=openapi-config --force
```

**Published files:**
```
config/
├── openapi.php           # Main configuration
├── openapi-docs.php      # Documentation templates
└── openapi-tests.php     # Test generation
```

### Step 2: Configure Main Settings

Edit `config/openapi.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    */
    'info' => [
        'title' => env('APP_NAME', 'Laravel API'),
        'description' => 'Complete API documentation for all application modules',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'API Support Team',
            'email' => 'support@example.com',
            'url' => 'https://example.com/support',
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Servers
    |--------------------------------------------------------------------------
    */
    'servers' => [
        [
            'url' => 'http://127.0.0.1:8000',
            'description' => 'Artisan server',
        ],
        [
            'url' => env('APP_URL', 'http://localhost'),
            'description' => 'Local Server',
        ],
        [
            'url' => env('PRODUCTION_URL', 'https://api.example.com'),
            'description' => 'Production Server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Types
    |--------------------------------------------------------------------------
    */
    'api_types' => [
        'api' => 'API Admin (api)',
        'site' => 'Public Website API (site)',
        'mobile' => 'Mobile App API (mobile)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    */
    'security' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT token for authentication',
        ],
        'api_key' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'API key for application authentication',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'base' => [
            'name' => 'Base Environment',
            'variables' => [
                'api_key' => 'app_default_key',
            ],
            'tracking_variables' => [
                'last_user_id' => '',
                'last_product_id' => '',
                'last_order_id' => '',
            ],
        ],
        'artisan' => [
            'name' => 'Artisan Environment',
            'base_url' => 'http://127.0.0.1:8000',
            'variables' => [
                'token' => '',
            ],
        ],
        'local' => [
            'name' => 'Local Environment',
            'base_url' => env('APP_URL', 'http://localhost'),
            'variables' => [
                'token' => '',
            ],
        ],
        'production' => [
            'name' => 'Production Environment',
            'base_url' => env('PRODUCTION_URL', 'https://api.example.com'),
            'variables' => [
                'token' => '',
                'api_key' => env('PRODUCTION_API_KEY', ''),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'models' => [
            'App\\Models',
            'Modules\\{module}\\Entities',
        ],
        'requests' => [
            'App\\Http\\Requests',
            'Modules\\{module}\\Http\\Requests',
        ],
        'controllers' => [
            'App\\Http\\Controllers',
            'Modules\\{module}\\Http\\Controllers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nwidart Modules
    |--------------------------------------------------------------------------
    */
    'nwidart' => [
        'enabled' => class_exists(\Nwidart\Modules\Facades\Module::class),
        'path' => base_path('Modules'),
        'namespace' => 'Modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    */
    'output' => [
        'path' => storage_path('app/documentation'),
        'openapi' => [
            'json' => 'openapi.json',
            'yaml' => 'openapi.yaml',
        ],
        'postman' => 'postman-collection.json',
        'insomnia' => 'insomnia-workspace.json',
        'environments' => [
            'postman' => 'postman-env-{environment}.json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware to Security Mapping
    |--------------------------------------------------------------------------
    */
    'middleware_security' => [
        'auth:sanctum' => 'bearer',
        'auth:api' => 'bearer',
        'api.key' => 'api_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Filters
    |--------------------------------------------------------------------------
    */
    'route_filters' => [
        'exclude_methods' => ['HEAD', 'OPTIONS'],
        'exclude_prefixes' => [
            'sanctum',
            'telescope',
            'horizon',
            '_debugbar',
        ],
        'exclude_names' => [
            '*.create',   // Exclude web form routes
            '*.edit',     // Exclude web form routes
        ],
        'include_only_api' => true,  // Only include routes starting with 'api/*'
    ],
];
```

### Step 3: Configure Documentation Templates

Edit `config/openapi-docs.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Entity Definitions
    |--------------------------------------------------------------------------
    */
    'entities' => [
        'users' => [
            'singular' => 'user',
            'plural' => 'users',
            'model' => \App\Models\User::class,
            'description' => 'System users with role-based access control',
        ],
        'products' => [
            'singular' => 'product',
            'plural' => 'products',
            'model' => \Modules\Catalog\Entities\Product::class,
            'description' => 'Product catalog items',
        ],
        // Add your entities here...
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Endpoint Documentation
    |--------------------------------------------------------------------------
    */
    'custom_endpoints' => [
        'auth.login' => [
            'summary' => 'User authentication',
            'description' => 'Authenticate user with email and password, returns JWT token',
            'request_example' => [
                'email' => 'admin@example.com',
                'password' => 'password123',
            ],
            'responses' => [
                200 => [
                    'description' => 'Login successful',
                    'example' => [
                        'token' => 'eyJ0eXAiOiJKV1QiLCJhbGc...',
                        'user' => [
                            'id' => 1,
                            'name' => 'Admin User',
                            'email' => 'admin@example.com',
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Invalid credentials',
                    'example' => [
                        'message' => 'Invalid email or password',
                    ],
                ],
            ],
        ],
        // Add your custom endpoints here...
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Descriptions
    |--------------------------------------------------------------------------
    */
    'field_descriptions' => [
        'id' => 'Unique identifier',
        'name' => 'Name of the resource',
        'email' => 'Email address',
        'created_at' => 'Creation timestamp',
        'updated_at' => 'Last update timestamp',
        // Add common field descriptions...
    ],
];
```

### Step 4: Configure Test Templates

Edit `config/openapi-tests.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Test Templates by Action
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'index' => [
            'status_200',
            'json_response',
            'has_data',
            'is_array',
        ],
        'store' => [
            'status_201',
            'json_response',
            'has_data',
            'save_to_global_var',
        ],
        'show' => [
            'status_200',
            'json_response',
            'has_data',
            'is_object',
        ],
        'update' => [
            'status_200',
            'json_response',
            'has_data',
        ],
        'destroy' => [
            'status_204_or_200',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reusable Test Snippets
    |--------------------------------------------------------------------------
    */
    'snippets' => [
        'status_200' => "pm.test('Status code is 200', function() { pm.response.to.have.status(200); });",
        'status_201' => "pm.test('Status code is 201', function() { pm.response.to.have.status(201); });",
        'status_204_or_200' => "pm.test('Status code is 204 or 200', function() { pm.expect([200, 204]).to.include(pm.response.code); });",
        'json_response' => "pm.test('Response is JSON', function() { pm.response.to.be.json; });",
        'has_data' => "pm.test('Response has data', function() { pm.expect(pm.response.json()).to.have.property('data'); });",
        'is_array' => "pm.test('Data is array', function() { pm.expect(pm.response.json().data).to.be.an('array'); });",
        'is_object' => "pm.test('Data is object', function() { pm.expect(pm.response.json().data).to.be.an('object'); });",
        'save_to_global_var' => "if (pm.response.json().data && pm.response.json().data.id) { pm.globals.set('last_{entity}_id', pm.response.json().data.id); }",
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Tests for Specific Endpoints
    |--------------------------------------------------------------------------
    */
    'custom_tests' => [
        'auth.login' => [
            "pm.test('Response has token', function() { pm.expect(pm.response.json()).to.have.property('token'); });",
            "pm.test('Token is not empty', function() { pm.expect(pm.response.json().token).to.not.be.empty; });",
            "pm.globals.set('token', pm.response.json().token);",
        ],
    ],
];
```

---

## 📦 Publishing Assets

### Publish YAML Templates

```bash
php artisan vendor:publish --tag=openapi-templates
```

**Published to:**
```
resources/vendor/openapi/templates/
├── yaml/
│   ├── generic_list.yaml
│   ├── generic_show.yaml
│   ├── generic_create.yaml
│   ├── generic_update.yaml
│   ├── generic_delete.yaml
│   └── custom/
│       ├── auth.login.yaml
│       └── auth.register.yaml
```

### Customize Templates

Edit templates in `resources/vendor/openapi/templates/yaml/`:

```yaml
# generic_list.yaml
summary: "List all {entity_plural}"
description: "Retrieve a paginated list of {entity_plural} with optional filtering and sorting"
tags:
  - {module}
  - {entity_plural}
parameters:
  - name: page
    in: query
    description: "Page number for pagination"
    schema:
      type: integer
      default: 1
  - name: per_page
    in: query
    description: "Number of items per page"
    schema:
      type: integer
      default: 15
      maximum: 100
responses:
  '200':
    description: "Successful response"
    content:
      application/json:
        schema:
          type: object
          properties:
            data:
              type: array
              items:
                $ref: '#/components/schemas/{entity_singular}'
```

---

## 🔧 Nwidart Modules Setup

### Step 1: Install Nwidart Modules

```bash
composer require nwidart/laravel-modules
```

### Step 2: Publish Modules Config

```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

### Step 3: Create Module Structure

```bash
php artisan module:make Security
php artisan module:make Catalog
```

### Step 4: Update OpenAPI Config

Ensure `config/openapi.php` has correct module paths:

```php
'paths' => [
    'models' => [
        'App\\Models',
        'Modules\\{module}\\Entities',  // Nwidart pattern
    ],
    'requests' => [
        'App\\Http\\Requests',
        'Modules\\{module}\\Http\\Requests',
    ],
],

'nwidart' => [
    'enabled' => true,
    'path' => base_path('Modules'),
    'namespace' => 'Modules',
],
```

### Step 5: Define Module Entities

```php
// config/openapi-docs.php
'entities' => [
    'users' => [
        'module' => 'Security',
        'model' => \Modules\Security\Entities\User::class,
    ],
    'products' => [
        'module' => 'Catalog',
        'model' => \Modules\Catalog\Entities\Product::class,
    ],
],
```

---

## ✅ Verification

### Step 1: Generate Documentation

```bash
php artisan openapi:generate --all
```

### Step 2: Check Generated Files

```bash
ls -lh storage/app/documentation/

# Expected output:
# openapi.json
# openapi.yaml
# postman-collection.json
# postman-env-artisan.json
# postman-env-local.json
# postman-env-production.json
# insomnia-workspace.json
```

### Step 3: Validate OpenAPI Spec

```bash
# Using swagger-cli (if installed)
swagger-cli validate storage/app/documentation/openapi.json

# Using online validator
# Upload openapi.json to https://editor.swagger.io/
```

### Step 4: Import into Postman

1. Open Postman
2. Import → File → Select `postman-collection.json`
3. Import environments (`postman-env-*.json`)
4. Send test request

### Step 5: Import into Insomnia

1. Open Insomnia
2. Import → From File → Select `insomnia-workspace.json`
3. Verify workspace structure
4. Test authentication requests

---

## 🐛 Troubleshooting

### Issue 1: "Class not found" errors

**Symptom:**
```
ReflectionException: Class App\Http\Requests\CreateUserRequest does not exist
```

**Solution:**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
```

### Issue 2: Empty request bodies

**Symptom:**
Generated requests show empty `{}` body.

**Solution:**
1. Verify FormRequest has `rules()` method
2. Check scenario detection in middleware
3. Enable debug mode to see extraction logs

```php
// config/openapi.php
'debug' => env('OPENAPI_DEBUG', false),
```

### Issue 3: Missing modules in documentation

**Symptom:**
Nwidart modules not appearing in generated docs.

**Solution:**
1. Verify modules are enabled:
```bash
php artisan module:list
```

2. Check config paths:
```php
// config/openapi.php
'nwidart' => [
    'enabled' => true,  // Must be true
    'path' => base_path('Modules'),
],
```

3. Regenerate with verbose output:
```bash
php artisan openapi:generate --all -vvv
```

### Issue 4: Variables not working in Insomnia

**Symptom:**
`{{ _.token }}` shows as literal text.

**Solution:**
Verify environment structure has `_type: environment`:

```json
{
  "_type": "environment",
  "name": "Base Environment",
  "data": {
    "token": ""
  }
}
```

### Issue 5: Tracking variables not saving

**Symptom:**
`last_user_id` not updating after create requests.

**Solution:**
Ensure tracking variables are in **Base Environment only**:

```php
// config/openapi.php
'environments' => [
    'base' => [
        'tracking_variables' => [
            'last_user_id' => '',  // ✅ In base
        ],
    ],
    'artisan' => [
        // ❌ NOT here
    ],
],
```

---

## 🔄 Upgrading

### From v1.x to v2.x

```bash
# Backup configs
cp config/openapi.php config/openapi.php.backup

# Update package
composer update ronu/laravel-openapi-generator

# Republish configs
php artisan vendor:publish --tag=openapi-config --force

# Review breaking changes
git diff config/openapi.php.backup config/openapi.php

# Regenerate documentation
php artisan openapi:generate --all
```

### Breaking Changes

**v2.0.0:**
- Environment structure changed (tracking_variables moved to base)
- Insomnia format updated to v4 (v5 no longer supported)
- Metadata extraction now uses 4-strategy cascade

---

## 🎓 Next Steps

1. **Read Implementation Guide:** [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)
2. **Customize Templates:** Edit JSON files in `resources/openapi/templates/`
3. **Add Custom Endpoints:** Define in `config/openapi-docs.php`
4. **Configure Tests:** Adjust `config/openapi-tests.php`
5. **Set Up CI/CD:** Auto-generate docs on deployment

---

## 📞 Support

- **Documentation Issues:** [GitHub Issues](https://github.com/ronu/laravel-openapi-generator/issues)
- **Questions:** [GitHub Discussions](https://github.com/ronu/laravel-openapi-generator/discussions)
- **Email:** charlietyn@gmail.com

---

**Installation successful! Ready to generate documentation.**
