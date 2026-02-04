# Installation Guide

Complete step-by-step installation and configuration guide for **Laravel OpenAPI Generator**.

---

## 📋 Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Publishing Assets](#publishing-assets)
- [Nwidart Modules Setup](#nwidart-modules-setup)
- [Verification](#verification)
- [HTTP Routes Setup](#http-routes-setup)
- [Troubleshooting](#troubleshooting)
- [Upgrading](#upgrading)
- [Uninstallation](#uninstallation)

---

## ✅ Requirements

### Minimum Requirements

| Component | Version |
|-----------|---------|
| **PHP** | 8.1+ |
| **Laravel** | 10.x, 11.x, or 12.x |
| **Composer** | 2.0+ |

### Optional Requirements (for Enhanced Features)

| Component | Purpose | Version |
|-----------|---------|---------|
| **Nwidart Modules** | Modular Laravel apps | ^10.0 |
| **MySQL** | Database introspection | 8.0+ |
| **PostgreSQL** | Database introspection | 13+ |
| **SQLite** | Testing environments | 3.35+ |

### Required PHP Extensions

```bash
# Check if required extensions are installed
php -m | grep -E "json|mbstring|pdo"

# For advanced features
php -m | grep -E "reflection|tokenizer"
```

If any are missing:
```bash
# Ubuntu/Debian
sudo apt-get install php8.1-json php8.1-mbstring php8.1-pdo

# macOS (via Homebrew)
brew install php@8.1

# Windows (via XAMPP/WAMP)
# Enable extensions in php.ini
```

---

## 🚀 Installation

### Step 1: Install via Composer

#### Production Installation (Recommended)
```bash
composer require ronu/laravel-openapi-generator
```

#### Development-Only Installation
If you only need documentation generation in development:
```bash
composer require ronu/laravel-openapi-generator --dev
```

### Step 2: Verify Auto-Discovery

Laravel will automatically discover and register the service provider. Verify with:

```bash
php artisan package:discover
```

You should see:
```
Discovered Package: ronu/laravel-openapi-generator
```

#### Manual Registration (If Auto-Discovery Disabled)

If you've disabled auto-discovery, manually register in `config/app.php`:

```php
'providers' => [
    // Other Service Providers...
    
    Ronu\OpenApiGenerator\OpenApiGeneratorServiceProvider::class,
],
```

### Step 3: Verify Installation

Check that the Artisan command is available:

```bash
php artisan list openapi
```

**Expected Output**:
```
Available commands:
  openapi
    openapi:generate         Generate OpenAPI documentation
    openapi:validate         Validate generated specification
    openapi:clear-cache      Clear documentation cache
```

---

## ⚙️ Configuration

### Step 1: Publish Configuration Files

```bash
# Publish all configuration files
php artisan vendor:publish --tag=openapi-config
```

This creates:
- `config/openapi.php` - Main configuration
- `config/openapi-docs.php` - Documentation templates
- `config/openapi-tests.php` - Test generation
- `config/openapi-templates.php` - Template engine config

#### Publish Specific Config
```bash
# Only main config
php artisan vendor:publish --tag=openapi-config --force

# Re-publish (overwrite existing)
php artisan vendor:publish --tag=openapi-config --force
```

### Step 2: Configure Main Settings

Edit `config/openapi.php`:

```php
<?php

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
            'email' => env('API_CONTACT_EMAIL', 'support@example.com'),
            'url' => env('API_CONTACT_URL', 'https://example.com/support'),
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
            'description' => 'Artisan Development Server',
        ],
        [
            'url' => env('APP_URL', 'http://localhost'),
            'description' => 'Local Server',
        ],
        [
            'url' => 'https://${{projectName}}.com',
            'description' => 'Production Server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    */
    'security' => [
        'bearer_auth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT Bearer Token Authentication',
        ],
        'api_key' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'API Key Authentication',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Types
    |--------------------------------------------------------------------------
    */
    'api_types' => [
        'api' => [
            'prefix' => 'api',
            'folder_name' => 'API Admin',
            'description' => 'Administrative API for backend operations',
        ],
        'mobile' => [
            'prefix' => 'mobile',
            'folder_name' => 'API Mobile',
            'description' => 'Mobile application API',
        ],
        'site' => [
            'prefix' => 'site',
            'folder_name' => 'API Public',
            'description' => 'Public website API',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('OPENAPI_ROUTES_ENABLED', true),
        'prefix' => env('OPENAPI_ROUTES_PREFIX', 'documentation'),
        'middleware' => explode(',', env('OPENAPI_ROUTES_MIDDLEWARE', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nwidart Modules
    |--------------------------------------------------------------------------
    */
    'nwidart' => [
        'enabled' => true,
        'path' => base_path('Modules'),
        'namespace' => 'Modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('OPENAPI_CACHE_ENABLED', true),
        'ttl' => env('OPENAPI_CACHE_TTL', 3600), // 1 hour
        'key' => 'openapi_spec',
    ],
];
```

### Step 3: Configure Entity Documentation

Edit `config/openapi-docs.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entity Configuration
    |--------------------------------------------------------------------------
    */
    'entities' => [
        'users' => [
            'module' => 'Security',
            'singular' => 'user',
            'plural' => 'users',
            'model' => App\Models\User::class,
        ],
        'products' => [
            'module' => 'Catalog',
            'singular' => 'product',
            'plural' => 'products',
            'model' => App\Models\Product::class,
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
            'summary' => 'User Login',
            'description' => 'Authenticate user and return JWT token',
            'request_fields' => [
                'email' => 'User email address',
                'password' => 'User password (min 8 characters)',
            ],
        ],
        'auth.register' => [
            'summary' => 'User Registration',
            'description' => 'Create a new user account',
            'request_fields' => [
                'name' => 'Full name',
                'email' => 'Email address',
                'password' => 'Password (min 8 characters)',
                'password_confirmation' => 'Password confirmation',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic CRUD Templates
    |--------------------------------------------------------------------------
    */
    'crud_templates' => [
        'list' => 'Retrieve a paginated list of {entity_plural} with optional filtering',
        'show' => 'Retrieve details of a specific {entity_singular} by ID',
        'create' => 'Create a new {entity_singular}',
        'update' => 'Update an existing {entity_singular}',
        'delete' => 'Delete a {entity_singular} (soft delete if enabled)',
    ],
];
```

### Step 4: Configure Test Generation

Edit `config/openapi-tests.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'list' => ['status_200', 'json_response', 'has_data', 'is_array'],
        'show' => ['status_200', 'json_response', 'has_data', 'is_object'],
        'create' => ['status_201', 'json_response', 'has_data', 'save_to_global_var'],
        'update' => ['status_200', 'json_response', 'has_data'],
        'delete' => ['status_204_or_200'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Snippets
    |--------------------------------------------------------------------------
    */
    'snippets' => [
        'status_200' => "pm.test('Status is 200', function() { pm.response.to.have.status(200); });",
        'status_201' => "pm.test('Status is 201', function() { pm.response.to.have.status(201); });",
        'status_204_or_200' => "pm.test('Status is 204 or 200', function() { pm.expect([200, 204]).to.include(pm.response.code); });",
        'json_response' => "pm.test('Response is JSON', function() { pm.response.to.be.json; });",
        'has_data' => "pm.test('Response has data', function() { pm.expect(pm.response.json()).to.have.property('data'); });",
        'is_array' => "pm.test('Data is array', function() { pm.expect(pm.response.json().data).to.be.an('array'); });",
        'is_object' => "pm.test('Data is object', function() { pm.expect(pm.response.json().data).to.be.an('object'); });",
        'save_to_global_var' => "if (pm.response.json().data && pm.response.json().data.id) { pm.globals.set('last_{entity}_id', pm.response.json().data.id); }",
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Tests
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

### Publish Templates (Optional)

If you want to customize the documentation templates:

```bash
php artisan vendor:publish --tag=openapi-templates
```

This creates:
```
resources/openapi/templates/
├── generic/
│   ├── list.json
│   ├── show.json
│   ├── create.json
│   ├── update.json
│   └── delete.json
└── custom/
    ├── auth.login.json
    └── auth.register.json
```

### Customize Templates

Templates use Mustache-style syntax:

```json
{
  "summary": "List all {entity_plural}",
  "description": "Retrieve a paginated list of {entity_plural}",
  "parameters": [
    {
      "name": "page",
      "in": "query",
      "description": "Page number",
      "schema": {
        "type": "integer",
        "default": 1
      }
    }
  ]
}
```

Variables are automatically replaced:
- `{entity_singular}` → `user`
- `{entity_plural}` → `users`
- `{module}` → `Security`

---

## 🔧 Nwidart Modules Setup

If you're using [Nwidart Laravel Modules](https://github.com/nWidart/laravel-modules):

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
php artisan module:make Sales
```

### Step 4: Enable in OpenAPI Config

Ensure `config/openapi.php` has:

```php
'nwidart' => [
    'enabled' => true,
    'path' => base_path('Modules'),
    'namespace' => 'Modules',
],

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
```

### Step 5: Define Entities

In `config/openapi-docs.php`:

```php
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

**Expected Output**:
```
🔍 Filtering API types: api, mobile
📋 Inspecting routes...
✅ Found 47 unique paths
💾 Writing OpenAPI specification...
✅ OpenAPI specification generated!
📄 File: /storage/app/openapi.json
📦 Format: json
📢 Paths: 47

📮 Generating Postman collection...
✅ Postman collection generated!
📄 File: /storage/app/postman-collection.json

📮 Generating Postman environments...
✅ Environment: artisan
✅ Environment: local
✅ Environment: production

💤 Generating Insomnia workspace...
✅ Insomnia workspace generated!
📄 File: /storage/app/insomnia-workspace.json
```

### Step 2: Check Generated Files

```bash
ls -lh storage/app/documentation/

# Expected files:
# openapi.json
# openapi.yaml
# postman-collection.json
# postman-env-artisan.json
# postman-env-local.json
# postman-env-production.json
# insomnia-workspace.json
```

### Step 3: Validate OpenAPI Spec

#### Option A: Using swagger-cli (if installed)
```bash
npm install -g swagger-cli
swagger-cli validate storage/app/openapi.json
```

#### Option B: Online validator
1. Go to [Swagger Editor](https://editor.swagger.io/)
2. File → Import File → Select `openapi.json`
3. Check for validation errors

### Step 4: Test in Postman

1. Open Postman
2. Import → File → Select `postman-collection.json`
3. Import environments:
    - `postman-env-artisan.json`
    - `postman-env-local.json`
    - `postman-env-production.json`
4. Select environment: Artisan
5. Send a test request

### Step 5: Test in Insomnia

1. Open Insomnia
2. Import → From File → Select `insomnia-workspace.json`
3. Verify workspace structure:
    - Base Environment
    - API Spec
    - Cookie Jar
    - Requests organized by module
    - Sub-environments (Artisan, Local, Production)
4. Send a test request

---

## 🌐 HTTP Routes Setup

### Enable HTTP Access

The package can expose documentation via HTTP routes.

### Step 1: Configure Routes

In `config/openapi.php`:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['web'],  // or ['api'], or custom middleware
],
```

### Step 2: Access Documentation

```bash
# OpenAPI JSON
curl http://localhost:8000/documentation/openapi.json

# OpenAPI YAML
curl http://localhost:8000/documentation/openapi.yaml

# Postman Collection
curl http://localhost:8000/documentation/postman

# Insomnia Workspace
curl http://localhost:8000/documentation/insomnia
```

### Step 3: Protect with Middleware

For production, protect routes:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['auth', 'admin'],  // Only authenticated admins
],
```

### Step 4: Disable in Production

In `.env`:

```env
OPENAPI_ROUTES_ENABLED=false
```

---

## 🐛 Troubleshooting

### Issue 1: "Class not found" errors

**Symptom**:
```
ReflectionException: Class App\Http\Requests\CreateUserRequest does not exist
```

**Solutions**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan clear-compiled

# Regenerate autoload files
composer dump-autoload

# Rebuild package discovery
composer dump-autoload
php artisan package:discover --ansi
```

### Issue 2: Empty request bodies

**Symptom**: Generated requests show empty `{}` body.

**Solutions**:
1. **Verify FormRequest has `rules()` method**
```php
// app/Http/Requests/CreateUserRequest.php
public function rules()
{
    return [
        'name' => 'required|string',
        'email' => 'required|email',
    ];
}
```

2. **Check scenario detection**
```bash
# Enable debug logging
// config/openapi.php
'debug' => true,

# Check logs
tail -f storage/logs/laravel.log | grep openapi
```

3. **Manual configuration**
```php
// config/openapi-docs.php
'custom_endpoints' => [
    'users.create' => [
        'request_fields' => [
            'name' => 'User full name',
            'email' => 'User email address',
        ],
    ],
],
```

### Issue 3: Missing modules in documentation

**Symptom**: Nwidart modules not appearing.

**Solutions**:
1. **Verify modules are enabled**
```bash
php artisan module:list

# Expected:
# +---------+---------+---------+
# | Name    | Status  | Path    |
# +---------+---------+---------+
# | Security| Enabled | Modules |
# | Catalog | Enabled | Modules |
# +---------+---------+---------+
```

2. **Check Nwidart config**
```php
// config/openapi.php
'nwidart' => [
    'enabled' => true,  // Must be true
    'path' => base_path('Modules'),
],
```

3. **Regenerate with verbose output**
```bash
php artisan openapi:generate --all -vvv
```

### Issue 4: Variables not working in Insomnia

**Symptom**: `{{ _.token }}` shows as literal text.

**Solution**: Verify environment structure:
```json
{
  "_type": "environment",
  "name": "Base Environment",
  "data": {
    "base_url": "http://127.0.0.1:8000",
    "token": ""
  }
}
```

### Issue 5: Tracking variables not saving

**Symptom**: `last_user_id` not updating after create requests.

**Solution**: Ensure tracking variables are in Base Environment:
```php
// config/openapi.php
'environments' => [
    'base' => [
        'tracking_variables' => [
            'last_user_id' => '',    // ✅ In base
            'last_product_id' => '',
        ],
    ],
    'artisan' => [
        // ❌ NOT here
    ],
],
```

### Issue 6: Permission denied when writing files

**Symptom**: Cannot write to `storage/app/`

**Solutions**:
```bash
# Set correct permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set correct ownership (Linux/macOS)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Or for development
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

---

## 🔄 Upgrading

### From v1.x to v2.x

```bash
# Step 1: Backup current configs
cp config/openapi.php config/openapi.php.backup
cp config/openapi-docs.php config/openapi-docs.php.backup

# Step 2: Update package
composer update ronu/laravel-openapi-generator

# Step 3: Republish configs
php artisan vendor:publish --tag=openapi-config --force

# Step 4: Review changes
git diff config/openapi.php.backup config/openapi.php

# Step 5: Merge custom changes
# Manually merge your customizations

# Step 6: Clear caches
php artisan cache:clear
php artisan config:clear

# Step 7: Regenerate documentation
php artisan openapi:generate --all --no-cache

# Step 8: Test
# Verify generated documentation works
```

### Breaking Changes

**v2.0.0**:
- Environment structure changed (tracking_variables moved to base)
- Insomnia format updated to v4 (v5 no longer supported)
- Metadata extraction now uses 4-strategy cascade
- Template variable format changed from `${{var}}` to `{var}`

---

## 🗑️ Uninstallation

### Step 1: Remove Package

```bash
composer remove ronu/laravel-openapi-generator
```

### Step 2: Delete Published Files

```bash
# Delete configs
rm config/openapi.php
rm config/openapi-docs.php
rm config/openapi-tests.php
rm config/openapi-templates.php

# Delete published templates (if any)
rm -rf resources/openapi/

# Delete generated documentation
rm -rf storage/app/documentation/
```

### Step 3: Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
composer dump-autoload
```

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/ronu/laravel-openapi-generator/issues)
- **Documentation**: [Full Docs](https://github.com/ronu/laravel-openapi-generator/wiki)
- **Email**: charlietyn@gmail.com

---

## 🎓 Next Steps

After installation, read:
1. **[IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)** - Advanced patterns and examples
2. **[README.md](../README.md)** - Package overview and features
3. **[CHANGELOG.md](../CHANGELOG.md)** - Version history

**Installation complete! Ready to generate documentation.** 🎉
