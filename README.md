# Laravel OpenAPI Generator

<p align="center">
    <img src="https://img.shields.io/packagist/v/ronu/laravel-openapi-generator.svg?style=flat-square" alt="Latest Version">
    <img src="https://img.shields.io/packagist/dt/ronu/laravel-openapi-generator.svg?style=flat-square" alt="Total Downloads">
    <img src="https://img.shields.io/packagist/l/ronu/laravel-openapi-generator.svg?style=flat-square" alt="License">
    <img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20?style=flat-square&logo=laravel" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php" alt="PHP">
</p>

<p align="center">
    <strong>The ultimate automatic API documentation generator for Laravel</strong><br>
    Generate complete, production-ready documentation in <strong>three formats</strong> with <strong>99% automation</strong>
</p>

---

## 🎯 Why This Package?

Most documentation tools require extensive annotations, complex configurations, or manual work. **This package takes a different approach**:

- ✅ **Zero Annotations** - Extract everything from existing code
- ✅ **Three Formats** - OpenAPI 3.0.3, Postman Collection v2.1, Insomnia Workspace v4
- ✅ **99% Automatic** - Intelligent extraction from FormRequests, Models, and Routes
- ✅ **Modular Support** - First-class support for Nwidart modularized Laravel apps
- ✅ **Battle-Tested** - Improved from 20% accuracy to 95%+ in production use

```bash
# One command to rule them all
php artisan openapi:generate --all
```

That's it. Your API documentation is ready in OpenAPI, Postman, and Insomnia formats.

---

## 📚 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Library Usage](#-library-usage)
- [Documentation Formats](#-documentation-formats)
- [Configuration](#-configuration)
- [Advanced Usage](#-advanced-usage)
- [Examples](#-examples)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### 1. **Multi-Format Export**
Generate documentation in three industry-standard formats:

```bash
# OpenAPI 3.0.3 (JSON/YAML)
GET /documentation/openapi.json
GET /documentation/openapi.yaml

# Postman Collection v2.1
GET /documentation/postman

# Insomnia Workspace v4
GET /documentation/insomnia
```

### 2. **Intelligent Metadata Extraction**

**From FormRequests**:
```php
// Your existing validation rules
class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'nullable|integer|min:18',
        ];
    }
}

// Automatically becomes
{
    "name": { "type": "string", "maxLength": 255 },
    "email": { "type": "string", "format": "email" },
    "age": { "type": "integer", "minimum": 18, "nullable": true }
}
```

**From Models**:
```php
// Your existing Eloquent model
class User extends Model
{
    protected $fillable = ['name', 'email', 'status'];
    
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

// Automatically extracts
- Field names and types
- Fillable attributes
- Relations (hasMany, belongsTo, etc.)
- Soft deletes detection
```

### 3. **Modular Architecture**

Perfect for applications organized with [Nwidart Laravel Modules](https://github.com/nWidart/laravel-modules):

```
API Type (api, site, mobile)
  └─ Module (Security, Catalog, Sales)
      └─ Entity (users, products, orders)
          └─ Actions (list, show, create, update, delete, custom)
```

### 4. **Smart Validation Rule Handling**

Handles complex Laravel validation rules using a **4-strategy cascade**:

```php
// Even complex rules like this
Rule::unique($this->connection, 'table', 'field')
    ->ignore($this->route('id'))
    ->where(fn($q) => $q->where('status', 'active'))

// Are extracted correctly through
1. Normal instantiation
2. Mock dependency injection
3. Reflection without constructor
4. File parsing (fallback)
```

### 5. **Environment Management**

Generate pre-configured environments for different stages:

```bash
# Postman: 3 separate environment files
postman-env-artisan.json     # http://127.0.0.1:8000
postman-env-local.json       # http://localhost/project
postman-env-production.json  # https://api.production.com

# Insomnia: Integrated workspace with hierarchical environments
- Base Environment (shared variables)
  ├─ Artisan Environment
  ├─ Local Environment
  └─ Production Environment
```

### 6. **Automatic Test Generation**

Every request comes with tests:

```javascript
// Postman tests
pm.test("Status is 200", function() { 
    pm.response.to.have.status(200); 
});
pm.test("Response has data", function() { 
    pm.expect(pm.response.json()).to.have.property('data'); 
});
pm.globals.set('last_user_id', pm.response.json().data.id);

// Insomnia tests (similar format)
```

### 7. **API Type Filtering**

Filter documentation by API type (only enabled channels in `config/openapi.php` are allowed):

```bash
# Generate only API admin documentation
php artisan openapi:generate --all --api-type=api

# Generate for mobile and public site
php artisan openapi:generate --all --api-type=mobile --api-type=site

# Via URL
GET /documentation/openapi.json?api_type=api,mobile
```

If an API type is unknown or disabled, the generator returns a validation error.

---

## 📦 Installation

### Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Composer 2.0+

### Step 1: Install Package

```bash
composer require ronu/laravel-openapi-generator
```

The service provider will be automatically registered via Laravel's auto-discovery.

### Step 2: Publish Configuration

```bash
# Publish configuration files
php artisan vendor:publish --tag=openapi-config

# Publish template files (optional, for customization)
php artisan vendor:publish --tag=openapi-templates
```

This creates:
- `config/openapi.php` - Main configuration
- `config/openapi-docs.php` - Documentation templates
- `config/openapi-tests.php` - Test generation
- `resources/openapi/templates/` - Customizable templates (optional)

### Step 3: Configure (Optional)

Edit `config/openapi.php`:

```php
return [
    'info' => [
        'title' => env('APP_NAME', 'Laravel API'),
        'version' => '1.0.0',
        // ...
    ],
    
    'servers' => [
        [
            'url' => 'http://127.0.0.1:8000',
            'description' => 'Artisan server',
        ],
        // ...
    ],
    
    // Define your API types
    'api_types' => [
        'api' => [
            'prefix' => 'api',
            'folder_name' => 'API Admin',
            // ...
        ],
    ],
];
```

### Step 4: Verify Installation

```bash
php artisan openapi:generate --help
```

You should see the command help with all available options.

---

## 🚀 Quick Start

### Generate All Formats

```bash
php artisan openapi:generate --all
```

This generates (default output path: `storage/app/public/openapi`):
- `storage/app/public/openapi/openapi-all.json`
- `storage/app/public/openapi/openapi-all.yaml`
- `storage/app/public/openapi/postman-all.json`
- `storage/app/public/openapi/postman-env-*.json` (3 files)
- `storage/app/public/openapi/insomnia-all.json`

### Generate Specific Format

```bash
# Only OpenAPI JSON
php artisan openapi:generate

# Only OpenAPI YAML
php artisan openapi:generate --format=yaml

# Only Postman
php artisan openapi:generate --with-postman

# Only Insomnia
php artisan openapi:generate --with-insomnia
```

### Channel-Based Export Examples

Use `--api-type` (CLI) or `api_type` (HTTP) to generate per-channel exports. Filenames always include the channel suffix or `-all`.

```bash
# CLI: generate only mobile channel
php artisan openapi:generate --all --api-type=mobile
# Outputs:
# - storage/app/public/openapi/openapi-mobile.json
# - storage/app/public/openapi/openapi-mobile.yaml
# - storage/app/public/openapi/postman-mobile.json
# - storage/app/public/openapi/insomnia-mobile.json

# CLI: generate all channels
php artisan openapi:generate --all
# Outputs:
# - storage/app/public/openapi/openapi-all.json
# - storage/app/public/openapi/openapi-all.yaml
# - storage/app/public/openapi/postman-all.json
# - storage/app/public/openapi/insomnia-all.json
```

```bash
# HTTP: generate only api + mobile channels
curl "http://localhost:8000/documentation/openapi.json?api_type=api,mobile"
curl "http://localhost:8000/documentation/postman?api_type=mobile"
curl "http://localhost:8000/documentation/insomnia?api_type=mobile"
```

### Access via HTTP

```bash
# OpenAPI
curl http://localhost:8000/documentation/openapi.json
curl http://localhost:8000/documentation/openapi.yaml

# Postman
curl http://localhost:8000/documentation/postman

# Insomnia
curl http://localhost:8000/documentation/insomnia
```

### Import into Tools

**Postman**:
1. Open Postman
2. Import → File → Select `postman-all.json` or `postman-<channel>.json`
3. Import each `postman-env-*.json` as environments

**Insomnia**:
1. Open Insomnia
2. Import → From File → Select `insomnia-all.json`
3. The Spec tab shows a minimal descriptive OpenAPI (info + servers only), while the Collections contain the routes.

**Swagger UI**:
1. Go to [Swagger Editor](https://editor.swagger.io/)
2. File → Import File → Select `openapi-all.yaml` or `openapi-<channel>.yaml`

---

## 📦 Library Usage

If you prefer to use the generator as a library in your Laravel codebase, you can call the service directly:

```php
use Ronu\OpenApiGenerator\OpenApiGenerator;

$generator = app(OpenApiGenerator::class);

$openApiSpec = $generator->generateOpenApi();
$postmanCollection = $generator->generatePostman();
$insomniaWorkspace = $generator->generateInsomnia();
```

The methods accept optional parameters for cache usage, API type filtering, and environment selection.

---

## 📋 Documentation Formats

### OpenAPI 3.0.3

Complete specification with:
- Info and metadata
- Server configurations
- Security schemes (Bearer, API Key)
- Paths with operations
- Request/response schemas
- Examples for all endpoints

**Custom Format (Not Standard)**:
This package uses a custom OpenAPI format where endpoints are grouped in a `collections` array instead of the standard `paths` object, optimized for modular Laravel applications.

### Postman Collection v2.1

Includes:
- Hierarchical folder structure (API Type → Module → Entity)
- Pre-configured environments
- Test scripts for response validation
- Global variables for tracking (e.g., `last_user_id`)
- Bearer token authentication

### Insomnia Workspace v4

Features:
- Design-first workspace
- Integrated API specification
- Environment hierarchy (Base + Sub-environments)
- Request organization matching module structure
- Variable inheritance

---

## ⚙️ Configuration

### Main Configuration (`config/openapi.php`)

```php
return [
    // API Information
    'info' => [
        'title' => env('APP_NAME'),
        'description' => 'Complete API documentation',
        'version' => '1.0.0',
        'contact' => [...],
        'license' => [...],
    ],

    // Server Environments
    'servers' => [
        ['url' => 'http://127.0.0.1:8000', 'description' => 'Artisan'],
        ['url' => env('APP_URL'), 'description' => 'Local'],
        ['url' => env('PRODUCTION_URL'), 'description' => 'Production'],
    ],

    // Security Schemes
    'security' => [
        'bearer_auth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
        'api_key' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
        ],
    ],

    // API Types (for filtering)
    'api_types' => [
        'api' => [
            'prefix' => 'api',
            'folder_name' => 'API Admin',
            'description' => 'Administration API',
            'enabled' => true,
        ],
        'mobile' => [
            'prefix' => 'mobile',
            'folder_name' => 'API Mobile',
            'description' => 'Mobile App API',
            'enabled' => true,
        ],
    ],

    // Export output directory
    'output_path' => storage_path('app/public/openapi'),

    // Nwidart Modules Support
    'nwidart' => [
        'enabled' => true,
        'path' => base_path('Modules'),
        'namespace' => 'Modules',
    ],

    // Middleware to Security Mapping
    'middleware_security' => [
        'auth:sanctum' => 'bearer_auth',
        'api_key' => 'api_key',
    ],
];
```

### Documentation Templates (`config/openapi-docs.php`)

```php
return [
    // Entity Configuration
    'entities' => [
        'users' => [
            'module' => 'Security',
            'singular' => 'user',
            'plural' => 'users',
            'model' => App\Models\User::class,
        ],
    ],

    // Custom Endpoint Documentation
    'custom_endpoints' => [
        'auth.login' => [
            'summary' => 'User Login',
            'description' => 'Authenticate user and return JWT token',
            'request_fields' => [
                'email' => 'User email address',
                'password' => 'User password',
            ],
        ],
    ],

    // Generic CRUD Templates
    'crud_templates' => [
        'list' => 'Retrieve paginated list of {entity_plural}',
        'show' => 'Retrieve details of a specific {entity_singular}',
        'create' => 'Create a new {entity_singular}',
        'update' => 'Update an existing {entity_singular}',
        'delete' => 'Delete a {entity_singular}',
    ],
];
```

### Test Generation (`config/openapi-tests.php`)

```php
return [
    // Test Templates for Actions
    'templates' => [
        'list' => ['status_200', 'json_response', 'has_data', 'is_array'],
        'show' => ['status_200', 'json_response', 'has_data', 'is_object'],
        'create' => ['status_201', 'json_response', 'save_to_global_var'],
        'update' => ['status_200', 'json_response'],
        'delete' => ['status_204_or_200'],
    ],

    // Reusable Test Snippets
    'snippets' => [
        'status_200' => "pm.test('Status is 200', function() { pm.response.to.have.status(200); });",
        'json_response' => "pm.test('Response is JSON', function() { pm.response.to.be.json; });",
        // ...
    ],

    // Custom Tests for Specific Endpoints
    'custom_tests' => [
        'auth.login' => [
            "pm.test('Response has token', function() { pm.expect(pm.response.json()).to.have.property('token'); });",
            "pm.globals.set('token', pm.response.json().token);",
        ],
    ],
];
```

---

## 🎓 Advanced Usage

### Filtering by API Type

```bash
# Command line
php artisan openapi:generate --all --api-type=api --api-type=mobile

# Via HTTP
GET /documentation/openapi.json?api_type=api,mobile
GET /documentation/postman?api_type=site
```

**Backward compatibility note**: the legacy API type key `movile` is still accepted as a temporary alias for `mobile` in CLI flags and HTTP query parameters, but it is deprecated and may be removed in a future major release.

If an API type is unknown or disabled, generation fails with a validation error.

### Specifying Environment

```bash
php artisan openapi:generate --all --environment=production
```

### Custom Output Path

```bash
php artisan openapi:generate --output=/custom/path/openapi-all.json
```

### Disable Cache

```bash
php artisan openapi:generate --no-cache
```

### Combining Options

```bash
php artisan openapi:generate \
    --all \
    --api-type=api \
    --api-type=mobile \
    --environment=production \
    --no-cache
```

---

## 💡 Examples

### Example 1: Basic CRUD

**Your Code**:
```php
// routes/api.php
Route::apiResource('users', UserController::class);

// app/Http/Requests/CreateUserRequest.php
class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}
```

**Generated Documentation**:
```json
{
  "paths": {
    "/api/users": {
      "post": {
        "summary": "Create a new user",
        "requestBody": {
          "content": {
            "application/json": {
              "schema": {
                "properties": {
                  "name": { "type": "string", "maxLength": 255 },
                  "email": { "type": "string", "format": "email" }
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

### Example 2: Custom Action

**Your Code**:
```php
// config/openapi-docs.php
'custom_endpoints' => [
    'auth.login' => [
        'summary' => 'User Login',
        'description' => 'Authenticate user and return JWT token',
        'request_fields' => [
            'email' => 'User email address',
            'password' => 'User password',
        ],
    ],
],
```

**Generated Documentation**:
- Automatically creates complete OpenAPI spec
- Generates Postman request with tests
- Creates Insomnia request
- Includes in all formats

### Example 3: Complex Validation

**Your Code**:
```php
class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($this->user),
            ],
            'password' => [
                'sometimes',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }
}
```

**Result**:
- Correctly extracts `email` as unique (ignoring current user)
- Extracts `password` requirements (min 8, mixed case, numbers)
- Marks both as optional (`sometimes`)

---

## 🐛 Troubleshooting

### Issue: "Class not found" errors

```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
```

### Issue: Empty request bodies

**Problem**: Generated requests show empty `{}` body

**Solution**:
1. Verify FormRequest has `rules()` method
2. Check scenario detection in middleware
3. Enable debug mode:

```php
// config/openapi.php
'debug' => true,
```

### Issue: Missing modules in documentation

**Problem**: Nwidart modules not appearing

**Solution**:
```bash
# Verify modules are enabled
php artisan module:list

# Check config
// config/openapi.php
'nwidart' => [
    'enabled' => true,
    'path' => base_path('Modules'),
],
```

### Issue: Variables not working in Insomnia

**Problem**: `{{ _.token }}` shows as literal text

**Solution**: Verify environment structure has `_type: environment` in JSON

### Issue: Routes not accessible

**Problem**: HTTP routes not working

**Solution**: Check if routes are enabled:
```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
],
```

For more troubleshooting, see [INSTALLATION.md](INSTALLATION.md).

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/ronu/laravel-openapi-generator.git
cd laravel-openapi-generator
composer install
vendor/bin/phpunit
```

### Code Style

```bash
vendor/bin/php-cs-fixer fix
```

### Static Analysis

```bash
vendor/bin/phpstan analyse
```

---

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🙏 Credits

- **Author**: charlietyn ([charlietyn@gmail.com](mailto:charlietyn@gmail.com))
- **Inspired by**: Real-world needs in production Laravel applications
- **Special thanks**: Laravel community and all contributors

---

## 📞 Support

- **Documentation**: [Full Documentation](https://github.com/ronu/laravel-openapi-generator/wiki)
- **Issues**: [GitHub Issues](https://github.com/ronu/laravel-openapi-generator/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ronu/laravel-openapi-generator/discussions)
- **Email**: [charlietyn@gmail.com](mailto:charlietyn@gmail.com)

---

## 🗺️ Roadmap

- [ ] Swagger UI integration
- [ ] ReDoc theme support
- [ ] GraphQL endpoint detection
- [ ] Custom rule library expansion
- [ ] Performance optimizations with caching
- [ ] Multi-language support
- [ ] Laravel 13 support

---

## ⭐ Show Your Support

If this package helped you, please consider:

- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting features
- 🤝 Contributing code
- 📢 Sharing with others

---

<p align="center">
    <strong>Made with ❤️ for the Laravel community</strong><br>
    <sub>From hours of manual documentation to minutes of automation</sub>
</p>
